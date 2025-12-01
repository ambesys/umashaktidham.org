<?php
session_start();

// Autoload classes
require_once 'vendor/autoload.php';

// Bootstrap the application
require_once 'bootstrap.php';

// Load configuration
require_once 'config/config.php';
require_once 'config/database.php';

// Load our centralized routes (Router-based)
// The legacy App runner is intentionally not used here so the new Router in `routes.php`
// can register and dispatch routes. This ensures `/` and other routes are handled.
require_once __DIR__ . '/routes.php';
?>