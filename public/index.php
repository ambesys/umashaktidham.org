<?php
session_start();

// Autoload classes
require_once '../vendor/autoload.php';

// Load configuration
require_once '../config/config.php';
require_once '../config/database.php';

// Initialize the application
$app = new App();

// Define routes
require_once '../routes/web.php';

// Run the application
$app->run();
?>