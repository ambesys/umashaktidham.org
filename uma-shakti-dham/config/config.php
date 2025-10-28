<?php
// Configuration settings for the Uma Shakti Dham application

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'uma_shakti_dham');
define('DB_USER', 'root');
define('DB_PASS', 'your_password_here');

// Base URL of the application
define('BASE_URL', 'http://localhost/uma-shakti-dham/public');

// Site settings
define('SITE_NAME', 'Uma Shakti Dham');
define('SITE_EMAIL', 'info@umashaktidham.org');

// Session settings
session_start();
define('SESSION_TIMEOUT', 3600); // 1 hour

// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>