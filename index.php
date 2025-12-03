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
if (function_exists('getLogger')) {
	try {
		getLogger()->info('Index: starting route include', [
			'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
			'uri' => $_SERVER['REQUEST_URI'] ?? '',
			'host' => $_SERVER['HTTP_HOST'] ?? ''
		]);
	} catch (\Throwable $e) {
		error_log('Index: logger unavailable: ' . $e->getMessage());
	}
}
require_once __DIR__ . '/routes.php';

if (function_exists('getLogger')) {
	try {
		getLogger()->info('Index: routes included and dispatched');
	} catch (\Throwable $e) {
		error_log('Index: post-dispatch log failed: ' . $e->getMessage());
	}
}
?>