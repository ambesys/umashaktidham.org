<?php
/**
 * Minimal App class to provide a simple grouping/routing surface used by some legacy code.
 * This implementation is intentionally small and safe: it stores routes, supports groups,
 * and dispatches handlers (callables or controller array handlers). Middlewares are
 * accepted but executed only if they are callables; string class names are ignored for now.
 */
class App
{
    private $routes = [];
    private $middlewares = [];
    private $currentGroupPrefix = '';
    private $currentGroupMiddlewares = [];

    public function __construct()
    {
    }

    public function group($prefix, $middlewares = [], $callback = null)
    {
        $previousPrefix = $this->currentGroupPrefix;
        $previousMiddlewares = $this->currentGroupMiddlewares;

        $this->currentGroupPrefix = $previousPrefix . $prefix;
        $this->currentGroupMiddlewares = array_merge($previousMiddlewares, (array)$middlewares);

        if ($callback && is_callable($callback)) {
            $callback($this);
        }

        // Restore previous group state
        $this->currentGroupPrefix = $previousPrefix;
        $this->currentGroupMiddlewares = $previousMiddlewares;

        return $this;
    }

    public function get($path, $handler)
    {
        $this->addRoute('GET', $path, $handler);
        return $this;
    }

    public function post($path, $handler)
    {
        $this->addRoute('POST', $path, $handler);
        return $this;
    }

    public function middleware($middleware)
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    private function addRoute($method, $path, $handler)
    {
        $fullPath = $this->currentGroupPrefix . $path;
        $allMiddlewares = array_merge($this->currentGroupMiddlewares, $this->middlewares);

        $this->routes[] = [
            'method' => $method,
            'path' => $fullPath,
            'handler' => $handler,
            'middlewares' => $allMiddlewares
        ];
        $this->middlewares = []; // Reset middlewares after adding route
    }

    public function run()
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        // Normalize
        $requestUri = rtrim($requestUri, '/') ?: '/';

        $route = $this->findRoute($requestMethod, $requestUri);
        if ($route) {
            $this->executeRoute($route);
        } else {
            $this->serve404();
        }
    }

    private function findRoute($method, $uri)
    {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $uri) {
                return $route;
            }
        }
        return null;
    }

    private function executeRoute($route)
    {
        $handler = $route['handler'];

        // Run any middlewares that are callables
        foreach ($route['middlewares'] as $m) {
            if (is_callable($m)) {
                $result = call_user_func($m, $_REQUEST);
                // Middleware can return false to stop execution
                if ($result === false) {
                    return;
                }
            }
        }

        if (is_callable($handler)) {
            call_user_func($handler);
            return;
        }

        if (is_array($handler) && count($handler) === 2) {
            [$obj, $method] = $handler;
            if (is_object($obj) && method_exists($obj, $method)) {
                return $obj->$method();
            }
            if (is_string($obj) && class_exists($obj)) {
                $inst = new $obj();
                if (method_exists($inst, $method)) {
                    return $inst->$method();
                }
            }
        }

        // As a last resort, if handler is a string function name
        if (is_string($handler) && function_exists($handler)) {
            return $handler();
        }

        $this->serve404();
    }

    private function serve404()
    {
        http_response_code(404);
        echo "<h1>404 - Page Not Found</h1>";
    }
}
