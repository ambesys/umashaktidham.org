<?php
// Configuration settings for the Uma Shakti Dham application

// Load Composer autoloader for dependencies
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables if .env file exists
// Check if running on localhost to determine which .env file to use
$isLocalhost = isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false;
$isLocalhost = $isLocalhost || (isset($_SERVER['SERVER_NAME']) && strpos($_SERVER['SERVER_NAME'], 'localhost') !== false);
$isLocalhost = $isLocalhost || (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'localhost') !== false);

// Also check if APP_ENV suggests local development
$appEnv = getenv('APP_ENV');
$isLocalEnv = in_array($appEnv, ['local', 'development', 'dev']);

if (($isLocalhost || $isLocalEnv) && file_exists(__DIR__ . '/../.env.local')) {
    $envFile = __DIR__ . '/../.env.local';
} elseif (file_exists(__DIR__ . '/../.env.prod')) {
    $envFile = __DIR__ . '/../.env.prod';
} elseif (file_exists('/files/public_html/.env.prod')) {
    $envFile = '/files/public_html/.env.prod';
} elseif (file_exists(__DIR__ . '/../.env')) {
    $envFile = __DIR__ . '/../.env';
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
define('DB_NAME', getenv('DB_NAME') ?: 'u103964107_uma');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: 'root');

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

// Mail configurations for transactional notifications
$MAIL_CONFIGS = [
    'hostinger' => [
        'agent' => 'hostinger',
        'host' => getenv('HOSTINGER_SMTP_HOST') ?: 'smtp.hostinger.com',
        'username' => getenv('HOSTINGER_SMTP_USER') ?: getenv('SMTP_USER') ?: '',
        'password' => getenv('HOSTINGER_SMTP_PASS') ?: getenv('SMTP_PASS') ?: '',
        'port' => getenv('HOSTINGER_SMTP_PORT') ?: 587,
        'encryption' => getenv('HOSTINGER_SMTP_ENCRYPTION') ?: 'tls',
        'from_address' => getenv('SMTP_FROM') ?: 'noreply@umashaktidham.org',
        'from_name' => getenv('SMTP_FROM_NAME') ?: 'Uma Shakti Dham',
        'reply_to' => getenv('SMTP_REPLY_TO') ?: (getenv('SMTP_FROM') ?: 'noreply@umashaktidham.org'),
    ],
    'zeptomail' => [
        'agent' => 'zeptomail',
        'host' => getenv('ZEPTO_SMTP_HOST') ?: 'smtp.zeptomail.com',
        'username' => getenv('ZEPTO_SMTP_USER') ?: '',
        'password' => getenv('ZEPTO_SMTP_PASS') ?: '',
        'port' => getenv('ZEPTO_SMTP_PORT') ?: 587,
        'encryption' => getenv('ZEPTO_SMTP_ENCRYPTION') ?: 'tls',
        'from_address' => getenv('ZEPTO_FROM') ?: (getenv('SMTP_FROM') ?: 'noreply@umashaktidham.org'),
        'from_name' => getenv('ZEPTO_FROM_NAME') ?: 'Uma Shakti Dham',
        'reply_to' => getenv('ZEPTO_REPLY_TO') ?: (getenv('SMTP_REPLY_TO') ?: 'noreply@umashaktidham.org'),
    ],
];

function get_mail_config(string $name = 'hostinger'): array
{
    global $MAIL_CONFIGS;
    return $MAIL_CONFIGS[$name] ?? $MAIL_CONFIGS['hostinger'];
}

/**
 * Get the configured mail provider name from env or default.
 */
function get_mail_provider(): string
{
    $provider = getenv('MAIL_PROVIDER') ?: null;
    if ($provider) return $provider;
    // Fallback to common env var or default to hostinger
    return getenv('DEFAULT_MAIL_PROVIDER') ?: 'hostinger';
}