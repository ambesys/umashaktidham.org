<?php
use App\Services\LoggerService;

class Layout {
    private static $middleware = [];
    private static $data = [];
    private static $scripts = [];
    private static $title = 'Uma Shakti Dham';
    
    /**
     * Set middleware requirements for the current page
     * @param array $requirements - Array of middleware names (admin, moderator, authenticated)
     */
    public static function setMiddleware($requirements) {
        self::$middleware = is_array($requirements) ? $requirements : [$requirements];
    }
    
    /**
     * Set data to be available in the layout and content
     * @param array $data - Associative array of data
     */
    public static function setData($data) {
        self::$data = array_merge(self::$data, $data);
    }
    
    /**
     * Add JavaScript files to be included
     * @param array|string $scripts - Script file names
     */
    public static function addScripts($scripts) {
        $scripts = is_array($scripts) ? $scripts : [$scripts];
        self::$scripts = array_merge(self::$scripts, $scripts);
    }
    
    /**
     * Set page title
     * @param string $title - Page title
     */
    public static function setTitle($title) {
        self::$title = $title;
    }
    
    /**
     * Render a view with the main layout
     * @param string $view - Path to the view file relative to Views directory
     * @param array $data - Data to pass to the view
     * @param array $middleware - Middleware requirements
     * @param array $options - Additional options (scripts, title)
     */
    public static function render($view, $data = [], $middleware = [], $options = []) {
        // Set middleware and data
        if (!empty($middleware)) {
            self::setMiddleware($middleware);
        }
        if (!empty($data)) {
            self::setData($data);
        }
        
        // Set options
        if (isset($options['scripts'])) {
            self::addScripts($options['scripts']);
        }
        if (isset($options['title'])) {
            self::setTitle($options['title']);
        }
        
        // Extract data for use in views
        extract(self::$data);
        
        // Set middleware for main layout
        $middleware = self::$middleware;
        $pageTitle = self::$title;
        $pageScripts = self::$scripts;
        
        // Capture the view content
        ob_start();
        $viewPath = __DIR__ . '/../' . ltrim($view, '/');
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            echo "<div class='alert alert-danger'>View not found: $view</div>";
        }
        $content = ob_get_clean();
        
        // Render with main layout
        include __DIR__ . '/main.php';
    }
    
    /**
     * Render content directly with layout
     * @param string $content - HTML content to render
     * @param array $middleware - Middleware requirements
     */
    public static function renderContent($content, $middleware = []) {
        if (!empty($middleware)) {
            self::setMiddleware($middleware);
        }
        
        // Set middleware for main layout
        $middleware = self::$middleware;
        
        // Render with main layout
        include __DIR__ . '/main.php';
    }
    
    /**
     * Include required model and controller files
     * @param array $files - Array of file paths to include
     */
    public static function includeFiles($files) {
        foreach ($files as $file) {
            // Try src relative path first (most common case)
            $srcPath = __DIR__ . '/../../' . ltrim($file, '/');
            if (file_exists($srcPath)) {
                require_once $srcPath;
            } else {
                // Try project root relative path as fallback
                $rootPath = __DIR__ . '/../../../' . ltrim($file, '/');
                if (file_exists($rootPath)) {
                    require_once $rootPath;
                } else {
                    LoggerService::error("Required file not found: $file (tried $srcPath and $rootPath)");
                }
            }
        }
    }
}