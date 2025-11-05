<?php
session_start();

// Autoload classes
require_once '../vendor/autoload.php';

// Bootstrap the application
require_once '../bootstrap.php';

// Load configuration
require_once '../config/config.php';
require_once '../config/database.php';

// Load our custom App class
require_once '../src/App.php';

// Initialize the application
$app = new App();

// Define routes
require_once '../routes/web.php';

// Run the application
$app->run();
?>