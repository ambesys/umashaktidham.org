<?php
/**
 * Consolidated Router
 * - Fast exact lookup for static routes
 * - Pattern matching for parameterized routes (":id")
 * - Supports middleware as class names or simple config arrays (e.g. ['roles'=>[...]])
 * - Handlers may be controller instances (objects), class names (strings) or callables
 */
class Router
{
    private $routesExact = [
        'GET' => [],
        'POST' => []
    ];

    private $routesPattern = [
        'GET' => [],
        'POST' => []
    ];

    private $currentGroupPrefix = '';
    private $currentGroupMiddleware = [];

    public function get($path, $handler, $middleware = [])
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post($path, $handler, $middleware = [])
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    private function addRoute($method, $path, $handler, $middleware = [])
    {
        $fullPath = $this->normalize($this->currentGroupPrefix . '/' . $path);
        $route = [
            'handler' => $handler,
            'middleware' => array_merge($this->currentGroupMiddleware, (array)$middleware),
            'options' => []
        ];

        if (strpos($fullPath, ':') !== false) {
            // parameterized route -> patterns list
            $this->routesPattern[$method][] = ['path' => $fullPath, 'route' => $route];
        } else {
            $this->routesExact[$method][$fullPath] = $route;
        }
    }

    /**
     * Group routes: $router->group('/admin', function($r){...}, ['roles'=>['ADMIN']]);
     * $middleware may be an array or a middleware class name
     */
    /**
     * Group routes. Supports both signatures for backward compatibility:
     * - group($prefix, $callback, $middleware)
     * - group($prefix, $middleware, $callback)
     */
    public function group($prefix, $arg2, $arg3 = null)
    {
        // normalize arguments: determine which arg is the callback and which is middleware
        if (is_callable($arg2)) {
            $callback = $arg2;
            $middleware = $arg3 ?? [];
        } else {
            $middleware = $arg2 ?? [];
            $callback = $arg3;
        }

        $previousPrefix = $this->currentGroupPrefix;
        $previousMiddleware = $this->currentGroupMiddleware;

        $this->currentGroupPrefix = rtrim($previousPrefix, '/') . '/' . trim($prefix, '/');
        $this->currentGroupPrefix = $this->normalize($this->currentGroupPrefix);
        // merge middleware (allow passing ['roles'=>...] or class names)
        $this->currentGroupMiddleware = array_merge($this->currentGroupMiddleware, (array)$middleware);

        if (is_callable($callback)) {
            $callback($this);
        }

        $this->currentGroupPrefix = $previousPrefix;
        $this->currentGroupMiddleware = $previousMiddleware;
    }

    /**
     * Normalize a path to a canonical form with leading slash, no trailing slash (except root)
     */
    private function normalize($path)
    {
        // Remove duplicate slashes and ensure a single leading slash, no trailing slash (except root)
        $p = '/' . trim($path, '/');
        // Collapse multiple consecutive slashes
        $p = preg_replace('#/+#', '/', $p);
        return $p === '/' ? '/' : rtrim($p, '/');
    }

    /**
     * Try to match a parameterized route like /article/:id/:slug?
     * Returns param array on match, false otherwise.
     */
    private function matchRoute($routePattern, $requestPath)
    {
        $routeParts = explode('/', trim($routePattern, '/'));
        $pathParts = explode('/', trim($requestPath, '/'));

        // Allow optional last param (common pattern for slug)
        if (count($pathParts) < count($routeParts)) {
            if (count($pathParts) + 1 !== count($routeParts)) {
                return false;
            }
        } elseif (count($pathParts) > count($routeParts)) {
            return false;
        }

        $params = [];

        foreach ($routeParts as $i => $part) {
            if (!isset($pathParts[$i])) {
                if (strpos($part, ':') === 0) {
                    $params[substr($part, 1)] = null;
                    continue;
                }
                return false;
            }

            if (strpos($part, ':') === 0) {
                $params[substr($part, 1)] = $pathParts[$i];
                continue;
            }

            if ($part !== $pathParts[$i]) {
                return false;
            }
        }

        return $params;
    }

    /**
     * Dispatch the request: tries exact lookup first, then pattern list.
     */
    public function dispatch($method, $path)
    {
        $method = strtoupper($method);
        // Treat HEAD requests as GET for routing purposes (common webserver behavior)
        if ($method === 'HEAD') {
            $method = 'GET';
        }
        $requestPath = $this->normalize(parse_url($path, PHP_URL_PATH));

        // Support legacy links that include .php (e.g. /login.php) by stripping the extension
        if (substr($requestPath, -4) === '.php') {
            $requestPath = preg_replace('/\.php$/', '', $requestPath);
            $requestPath = $requestPath === '' ? '/' : $requestPath;
        }

        $route = $this->routesExact[$method][$requestPath] ?? null;
        $params = [];

        if (!$route) {
            // try pattern routes
            foreach ($this->routesPattern[$method] as $entry) {
                $match = $this->matchRoute($entry['path'], $requestPath);
                if ($match !== false) {
                    $route = $entry['route'];
                    $params = $match;
                    break;
                }
            }
        }

        if (!$route) {
            header('Location: /404');
            exit;
        }

        // middleware processing: middleware can be class name strings or arrays with 'roles'
        foreach ($route['middleware'] as $m) {
            if (is_array($m) && isset($m['roles'])) {
                // simple role check short-circuit
                if (function_exists('check_role')) {
                    $fn = 'check_role';
                    if (!$fn($m['roles'])) {
                        header('Location: /403');
                        exit;
                    }
                } else {
                    // fallback to AuthorizationService if available
                    if (isset($GLOBALS['authorizationService']) && method_exists($GLOBALS['authorizationService'], 'currentUserHasAnyRole')) {
                        if (!$GLOBALS['authorizationService']->currentUserHasAnyRole($m['roles'])) {
                            header('Location: /403');
                            exit;
                        }
                    }
                }
                continue;
            }

            if (is_string($m) && class_exists($m)) {
                $middleware = new $m();
                $request = $_REQUEST;
                $next = function ($req) use (&$route, &$params) {
                    // next simply continues to handler
                    return true;
                };
                // If middleware returns false or exits, it will have already handled response
                $middleware->handle($request, $next, $route['options'] ?? []);
                continue;
            }
        }

        // invoke handler
        $handler = $route['handler'];

        if (is_array($handler) && count($handler) === 2) {
            $controller = $handler[0];
            $methodName = $handler[1];

            // handler[0] may be an object instance or a class name
            if (is_object($controller)) {
                return call_user_func_array([$controller, $methodName], array_values($params));
            }

            if (is_string($controller)) {
                if (isset($GLOBALS['controllers']) && is_array($GLOBALS['controllers'])) {
                    // try to find instance by key (lowercase keys in bootstrap)
                    $key = strtolower(preg_replace('/Controller$/', '', (new \ReflectionClass($controller))->getShortName()));
                    if (isset($GLOBALS['controllers'][$key])) {
                        return call_user_func_array([$GLOBALS['controllers'][$key], $methodName], array_values($params));
                    }
                }

                // fallback: instantiate class
                if (class_exists($controller)) {
                    $inst = new $controller();
                    return call_user_func_array([$inst, $methodName], array_values($params));
                }
            }
        }

        if (is_callable($handler)) {
            return call_user_func_array($handler, array_values($params));
        }

        // Unknown handler shape
        header('Location: /500');
        exit;
    }
}
