<?php
// Configuration settings for the Uma Shakti Dham application

// Load Composer autoloader for dependencies
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables if .env file exists
if (file_exists(__DIR__ . '/../.env')) {
    $envFile = __DIR__ . '/../.env';
} elseif (file_exists(__DIR__ . '/../.env.prod')) {
    $envFile = __DIR__ . '/../.env.prod';
} elseif (file_exists(__DIR__ . '/../.env.local')) {
    $envFile = __DIR__ . '/../.env.local';
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

// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'umashakti_dham');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Application settings
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_DEBUG', getenv('APP_DEBUG') ?: false);
define('APP_URL', getenv('APP_URL') ?: 'https://umashaktidham.org');

// Base URL of the application
define('BASE_URL', APP_URL);

// Site settings
define('SITE_NAME', 'Uma Shakti Dham');
define('SITE_EMAIL', getenv('SMTP_FROM') ?: 'umashaktidham@gmail.com');

// Session settings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
define('SESSION_TIMEOUT', getenv('SESSION_LIFETIME') ?: 7200);

// OAuth settings (for social login)
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID'));
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET'));
define('FACEBOOK_CLIENT_ID', getenv('FACEBOOK_CLIENT_ID'));
define('FACEBOOK_CLIENT_SECRET', getenv('FACEBOOK_CLIENT_SECRET'));

// Error reporting
if (APP_DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Logging configuration is handled in bootstrap.php