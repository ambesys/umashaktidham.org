<?php

/**
 * Application Bootstrap
 * Initializes core application components and services
 */

// Define root path constant
define('ROOT_PATH', __DIR__);

// Load environment variables
// Check if running on localhost to determine which .env file to use
$isLocalhost = isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false;
$isLocalhost = $isLocalhost || (isset($_SERVER['SERVER_NAME']) && strpos($_SERVER['SERVER_NAME'], 'localhost') !== false);

if ($isLocalhost && file_exists(ROOT_PATH . '/.env.local')) {
    $envFile = ROOT_PATH . '/.env.local';
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
}

// Load LoggerService and initialize logging
require_once ROOT_PATH . '/src/Services/LoggerService.php';
\App\Services\LoggerService::init([
    'file' => ROOT_PATH . '/logs/app.log',
    'min_level' => (getenv('APP_DEBUG') === 'true' || getenv('APP_DEBUG') === '1') ? \App\Services\LoggerService::LEVEL_DEBUG : \App\Services\LoggerService::LEVEL_INFO,
    'timezone' => 'UTC'
]);

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