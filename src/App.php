<?php

class App
{
    private $routes = [];
    private $middlewares = [];
    
    public function __construct()
    {
        // Initialize the application
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
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $this->middlewares
        ];
        $this->middlewares = []; // Reset middlewares after adding route
    }
    
    public function run()
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Handle static assets (CSS, JS, images)
        if ($this->isStaticAsset($requestUri)) {
            $this->serveStaticAsset($requestUri);
            return;
        }
        
        // Handle the request
        $this->handleRequest($requestMethod, $requestUri);
    }
    
    private function isStaticAsset($uri)
    {
        $extensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf'];
        $extension = pathinfo($uri, PATHINFO_EXTENSION);
        return in_array(strtolower($extension), $extensions);
    }
    
    private function serveStaticAsset($uri)
    {
        $filePath = __DIR__ . '/../public' . $uri;
        
        if (file_exists($filePath)) {
            $extension = pathinfo($uri, PATHINFO_EXTENSION);
            $mimeTypes = [
                'css' => 'text/css',
                'js' => 'application/javascript',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'svg' => 'image/svg+xml',
                'ico' => 'image/x-icon',
                'woff' => 'font/woff',
                'woff2' => 'font/woff2',
                'ttf' => 'font/ttf'
            ];
            
            $mimeType = $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
            header('Content-Type: ' . $mimeType);
            readfile($filePath);
        } else {
            http_response_code(404);
            echo "Asset not found: " . htmlspecialchars($uri);
        }
    }
    
    private function handleRequest($method, $uri)
    {
        // Remove trailing slash and .php extension for consistency
        $uri = rtrim($uri, '/');
        $uri = preg_replace('/\.php$/', '', $uri);
        
        // Handle empty URI as home
        if ($uri === '') {
            $uri = '/';
        }
        
        // Simple routing - handle all main pages
        switch ($uri) {
            case '/':
            case '/index':
                $this->serveView('index');
                break;
            case '/about':
                $this->serveView('about');
                break;
            case '/kp-history':
                $this->serveView('kp-history');
                break;
            case '/committee':
                $this->serveView('committee');
                break;
            case '/bylaws':
                $this->serveView('bylaws');
                break;
            case '/events':
                $this->serveView('events');
                break;
            case '/events/cultural':
                $this->serveView('events/cultural');
                break;
            case '/events/festivals':
                $this->serveView('events/festivals');
                break;
            case '/indian-holidays':
                $this->serveView('indian-holidays');
                break;
            case '/hindu-gods':
                $this->serveView('hindu-gods');
                break;
            case '/hindu-rituals':
                $this->serveView('hindu-rituals');
                break;
            case '/hindu-scriptures':
                $this->serveView('hindu-scriptures');
                break;
            case '/gallery':
                $this->serveView('gallery');
                break;
            case '/gallery/events':
                $this->serveView('gallery/events');
                break;
            case '/gallery/community':
                $this->serveView('gallery/community');
                break;
            case '/membership':
                $this->serveView('membership');
                break;
            case '/members/families':
                $this->serveView('members/families');
                break;
            case '/youth-corner':
                $this->serveView('youth-corner');
                break;
            case '/matrimonial':
                $this->serveView('matrimonial');
                break;
            case '/business-directory':
                $this->serveView('business-directory');
                break;
            case '/donate':
                $this->serveView('donate');
                break;
            case '/contact':
                $this->serveView('contact');
                break;
            case '/access':
                $this->serveView('access');
                break;
            case '/direction':
                $this->serveView('direction');
                break;
            case '/facilities':
                $this->serveView('facilities');
                break;
            case '/login':
            case '/auth/login':
                $this->serveView('auth/login');
                break;
            case '/register':
            case '/auth/register':
                $this->serveView('auth/register');
                break;
            case '/dashboard':
                $this->serveView('dashboard/index');
                break;
            case '/profile':
                $this->serveView('members/profile');
                break;
            case '/family':
                $this->serveView('members/family');
                break;
            case '/admin':
            case '/admin/index':
                $this->serveView('admin/users');
                break;
            case '/admin/users':
                $this->serveView('admin/users');
                break;
            case '/admin/moderators':
                $this->serveView('admin/moderators');
                break;
            case '/admin/events':
                $this->serveView('admin/events');
                break;
            default:
                $this->serve404();
                break;
        }
    }
    
    private function serveView($view, $data = [], $middleware = [])
    {
        // Include the Layout class
        require_once __DIR__ . '/Views/layouts/Layout.php';
        
        // Set up route-specific requirements
        $routeConfig = $this->getRouteConfig($view);
        
        // Include required files for this route BEFORE executing logic
        if (!empty($routeConfig['includes'])) {
            Layout::includeFiles($routeConfig['includes']);
        }
        
        // Execute route-specific logic after includes are loaded
        if (!empty($routeConfig['logic'])) {
            $data = array_merge($data, $routeConfig['logic']());
        }
        
        // Merge middleware from route config
        $middleware = array_merge($middleware, $routeConfig['middleware']);
        
        // Render the view with layout
        Layout::render($view . '.php', $data, $middleware, $routeConfig['options'] ?? []);
    }
    
    private function getRouteConfig($view)
    {
        $configs = [
            'index' => [
                'middleware' => [],
                'includes' => [],
                'options' => ['title' => 'Welcome to Uma Shakti Dham - Hindu Temple & Community Center'],
                'logic' => null
            ],
            'access' => [
                'middleware' => [],
                'includes' => [],
                'options' => ['title' => 'Access Required - Uma Shakti Dham'],
                'logic' => function() {
                    $error = '';
                    // Allow POST to validate access code
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $code = $_POST['access_code'] ?? '';
                        $next = $_POST['next'] ?? '/';
                        if (trim($code) === 'jayumiya') {
                            // grant access for 2 hours
                            $_SESSION['access_granted_until'] = time() + (2 * 60 * 60);
                            header('Location: ' . $next);
                            exit();
                        } else {
                            $error = 'Invalid access code. Please try again.';
                        }
                    }
                    return ['error' => $error];
                }
            ],
            'about' => [
                'middleware' => [],
                'includes' => [],
                'options' => ['title' => 'About - Uma Shakti Dham'],
                'logic' => null
            ],
            'kp-history' => [
                'middleware' => [],
                'includes' => [],
                'options' => ['title' => 'Kadva Patidar Heritage - Uma Shakti Dham'],
                'logic' => null
            ],
            'committee' => [
                'middleware' => [],
                'includes' => [],
                'options' => ['title' => 'Leadership Committee - Uma Shakti Dham'],
                'logic' => null
            ],
            'bylaws' => [
                'middleware' => [],
                'includes' => [],
                'options' => ['title' => 'Bylaws & Constitution - Uma Shakti Dham'],
                'logic' => null
            ],
            'events' => [
                'middleware' => [],
                'includes' => [],
                'options' => ['title' => 'Events - Uma Shakti Dham'],
                'logic' => null
            ],
            'events/cultural' => [
                'middleware' => [],
                'includes' => [],
                'options' => ['title' => 'Cultural Programs - Uma Shakti Dham'],
                'logic' => null
            ],
            'events/festivals' => [
                'middleware' => [],
                'includes' => [],
                'options' => ['title' => 'Festival Celebrations - Uma Shakti Dham'],
                'logic' => null
            ],
            'gallery' => [
                'middleware' => [],
                'includes' => [],
                'options' => ['title' => 'Gallery - Uma Shakti Dham'],
                'logic' => null
            ],
            'gallery/events' => [
                'middleware' => [],
                'includes' => [],
                'options' => ['title' => 'Event Photos - Uma Shakti Dham'],
                'logic' => null
            ],
            'gallery/community' => [
                'middleware' => [],
                'includes' => [],
                'options' => ['title' => 'Community Photos - Uma Shakti Dham'],
                'logic' => null
            ],
            'membership' => [
                'middleware' => [],
                'includes' => [],
                'options' => ['title' => 'Membership - Uma Shakti Dham'],
                'logic' => null
            ],
            'members/families' => [
                'middleware' => [],
                'includes' => [],
                'options' => ['title' => 'Family Directory - Uma Shakti Dham'],
                'logic' => null
            ],
            'youth-corner' => [
                'middleware' => [],
                'includes' => [],
                'options' => ['title' => 'Youth Corner - Uma Shakti Dham'],
                'logic' => null
            ],
            'matrimonial' => [
                'middleware' => [],
                'includes' => [],
                'options' => ['title' => 'Business Network - Uma Shakti Dham'],
                'logic' => null
            ],
            'business-directory' => [
                'middleware' => [],
                'includes' => [],
                'options' => ['title' => 'Business Directory - Uma Shakti Dham'],
                'logic' => null
            ],
            'donate' => [
                'middleware' => [],
                'includes' => [],
                'options' => ['title' => 'Donate - Uma Shakti Dham'],
                'logic' => null
            ],
            'contact' => [
                'middleware' => [],
                'includes' => [],
                'options' => ['title' => 'Contact - Uma Shakti Dham'],
                'logic' => null
            ],
            'direction' => [
                'middleware' => [],
                'includes' => [],
                'options' => ['title' => 'Location & Hours - Uma Shakti Dham'],
                'logic' => null
            ],
            'facilities' => [
                'middleware' => [],
                'includes' => [],
                'options' => ['title' => 'Facilities & Rental - Uma Shakti Dham'],
                'logic' => null
            ],
            'indian-holidays' => [
                'middleware' => [],
                'includes' => [],
                'options' => ['title' => 'Indian Holidays - Uma Shakti Dham'],
                'logic' => null
            ],
            'hindu-gods' => [
                'middleware' => [],
                'includes' => [],
                'options' => ['title' => 'Hindu Gods - Uma Shakti Dham'],
                'logic' => null
            ],
            'hindu-rituals' => [
                'middleware' => [],
                'includes' => [],
                'options' => ['title' => 'Hindu Rituals - Uma Shakti Dham'],
                'logic' => null
            ],
            'hindu-scriptures' => [
                'middleware' => [],
                'includes' => [],
                'options' => ['title' => 'Hindu Scriptures - Uma Shakti Dham'],
                'logic' => null
            ],
            'admin/users' => [
                'middleware' => ['admin'],
                'includes' => ['Models/User.php', 'Controllers/AdminController.php'],
                'options' => ['title' => 'Manage Users - Admin'],
                'logic' => function() {
                    $adminController = new AdminController();
                    return ['users' => $adminController->getAllUsers()];
                }
            ],
            'admin/moderators' => [
                'middleware' => ['admin'],
                'includes' => ['Models/User.php', 'Controllers/AdminController.php'],
                'options' => ['title' => 'Manage Moderators - Admin'],
                'logic' => function() {
                    $adminController = new AdminController();
                    return ['moderators' => $adminController->getModerators()];
                }
            ],
            'dashboard/index' => [
                'middleware' => ['authenticated'],
                'includes' => ['Controllers/DashboardController.php'],
                'options' => ['scripts' => ['dashboard.js'], 'title' => 'Dashboard - Uma Shakti Dham'],
                'logic' => function() {
                    $dashboardController = new DashboardController();
                    return ['dashboardData' => $dashboardController->getDashboardData()];
                }
            ],
            'members/profile' => [
                'middleware' => ['authenticated'],
                'includes' => ['Models/Member.php', 'Controllers/MemberController.php'],
                'options' => ['title' => 'Profile - Uma Shakti Dham'],
                'logic' => function() {
                    $memberController = new MemberController();
                    return ['member' => $memberController->getCurrentMember()];
                }
            ],
            'members/family' => [
                'middleware' => ['authenticated'],
                'includes' => ['Models/Family.php', 'Controllers/FamilyController.php'],
                'options' => ['title' => 'Family - Uma Shakti Dham'],
                'logic' => function() {
                    $familyController = new FamilyController();
                    return ['family' => $familyController->getCurrentFamily()];
                }
            ]
        ];
        
        return $configs[$view] ?? ['middleware' => [], 'includes' => [], 'logic' => null, 'options' => []];
    }
    
    private function serve404()
    {
        http_response_code(404);
        echo "<h1>404 - Page Not Found</h1>";
    }
}