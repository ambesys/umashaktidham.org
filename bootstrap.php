<?php

/**
 * Application Bootstrap
 * Initializes core application components and services
 */

// Define root path constant
define('ROOT_PATH', __DIR__);

// Ensure session available for access gating and dev shortcuts
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Composer autoloader (optional but recommended)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Load environment variables
// Check if running on localhost to determine which .env file to use
$isLocalhost = isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false;
$isLocalhost = $isLocalhost || (isset($_SERVER['SERVER_NAME']) && strpos($_SERVER['SERVER_NAME'], 'localhost') !== false);
// Also treat CLI as localhost for development convenience
$isLocalhost = $isLocalhost || (php_sapi_name() === 'cli');

if ($isLocalhost && file_exists(ROOT_PATH . '/.env.local')) {
    $envFile = ROOT_PATH . '/.env.local';
} elseif ($isLocalhost && file_exists(ROOT_PATH . '/.env')) {
    $envFile = ROOT_PATH . '/.env';
} elseif (file_exists(ROOT_PATH . '/.env.prod')) {
    $envFile = ROOT_PATH . '/.env.prod';
} elseif (file_exists('/files/public_html/.env.prod')) {
    $envFile = '/files/public_html/.env.prod';
} elseif (file_exists(ROOT_PATH . '/.env')) {
    $envFile = ROOT_PATH . '/.env';
}

if (isset($envFile) && file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Skip comments
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!empty($name)) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }
    error_log('Bootstrap: env loaded from ' . $envFile);
} else {
    error_log('Bootstrap: no env file found; relying on server env');
}

// Development convenience: when running on localhost, auto-grant temporary access so dev pages
// and header/footer can be tested without the external "access" gate. This is intentionally
// limited to localhost only and sets a short-lived flag.
if (!empty($isLocalhost)) {
    try {
        // give one hour of access for local testing
        $_SESSION['access_granted_until'] = time() + 3600;
    } catch (\Throwable $e) {
        error_log('Failed to set local access flag: ' . $e->getMessage());
    }
}

// Load LoggerService and initialize logging
require_once ROOT_PATH . '/src/Services/LoggerService.php';
\App\Services\LoggerService::init([
    'file' => ROOT_PATH . '/logs/app.log',
    'min_level' => (getenv('APP_DEBUG') === 'true' || getenv('APP_DEBUG') === '1') ? \App\Services\LoggerService::LEVEL_DEBUG : \App\Services\LoggerService::LEVEL_INFO,
    'timezone' => 'UTC'
]);


ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/log/php-error.log');

error_log('Application bootstrap initialized.');

// Create logger instance
$loggerService = new \App\Services\LoggerService();

/**
 * Get the application logger
 */
function getLogger(): \App\Services\LoggerService {
    static $logger = null;
    if ($logger === null) {
        $logger = new \App\Services\LoggerService();
    }
    return $logger;
}

// --- Controller wiring (conservative, minimal DI) ---
// Load database config to initialize $GLOBALS['pdo'] if possible
try {
    require_once __DIR__ . '/config/database.php';
    if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof \PDO) {
        getLogger()->info('Bootstrap: PDO initialized', [
            'use_mysql' => getenv('USE_MYSQL'),
            'db_host' => getenv('DB_HOST'),
            'db_name' => getenv('DB_NAME')
        ]);
    } else {
        getLogger()->warning('Bootstrap: PDO not initialized');
    }
} catch (\Throwable $e) {
    // If database config fails, continue without PDO (some controllers/services will fall back)
    error_log('Database config load failed in bootstrap: ' . $e->getMessage());
}

$controllers = [];
// Instantiate services that are safe without heavy deps
try {
    $sessionService = new \App\Services\SessionService($GLOBALS['pdo'] ?? null);
} catch (\Throwable $e) {
    $sessionService = null;
}

try {
    $paymentService = new \App\Services\PaymentService();
} catch (\Throwable $e) {
    $paymentService = null;
}

// Helper to safe instantiate controllers by class name
$safeInstantiate = function(string $fqcn, array $args = []) {
    try {
        if (!class_exists($fqcn)) return null;
        $ref = new \ReflectionClass($fqcn);
        return $ref->newInstanceArgs($args);
    } catch (\Throwable $e) {
        error_log('Failed to instantiate ' . $fqcn . ': ' . $e->getMessage());
        return null;
    }
};

// Conservative set of controllers used by routes
$controllers['auth'] = $safeInstantiate('\App\\Controllers\\AuthController');
$controllers['donation'] = $safeInstantiate('\App\\Controllers\\DonationController', []);
$controllers['event'] = $safeInstantiate('\App\\Controllers\\EventController', [new \App\Services\EventService($GLOBALS['pdo'] ?? null), $sessionService]);
$controllers['passwordreset'] = $safeInstantiate('\App\\Controllers\\PasswordResetController', [new \App\Services\PasswordResetService($GLOBALS['pdo'] ?? null)]);
$controllers['user'] = $safeInstantiate('\App\\Controllers\\UserController');
$controllers['member'] = $safeInstantiate('\App\\Controllers\\MemberController');
$controllers['family'] = $safeInstantiate('\App\\Controllers\\FamilyController');
$controllers['admin'] = $safeInstantiate('\App\\Controllers\\AdminController');
$controllers['dashboard'] = $safeInstantiate('\App\\Controllers\\DashboardController');

// Expose to global scope for Router fallback resolution
$GLOBALS['controllers'] = $controllers;

error_log('Bootstrap: controllers wired: ' . implode(',', array_keys($controllers)));
if (function_exists('getLogger')) {
    try {
        getLogger()->info('Bootstrap: controllers wired', ['controllers' => array_keys($controllers)]);
    } catch (\Throwable $e) {
        // ignore
    }
}