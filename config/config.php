<?php
// Configuration settings for the Uma Shakti Dham application

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'u103964107_uma');
define('DB_USER', 'u103964107_uma');
define('DB_PASS', 'Cn?o4zw:sT!0');

// Base URL of the application
define('BASE_URL', 'http://localhost/uma-shakti-dham/public');

// Site settings
define('SITE_NAME', 'Uma Shakti Dham');
define('SITE_EMAIL', 'umashaktidham@gmail.com');

// Session settings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
define('SESSION_TIMEOUT', 3600); // 1 hour

// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>