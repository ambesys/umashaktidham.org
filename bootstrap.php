<?php

/**
 * Application Bootstrap
 * Initializes core application components and services
 */

// Define root path constant
define('ROOT_PATH', __DIR__);

// Load LoggerService and initialize logging
require_once ROOT_PATH . '/src/Services/LoggerService.php';
\App\Services\LoggerService::init([
    'file' => ROOT_PATH . '/logs/app.log',
    'min_level' => \App\Services\LoggerService::LEVEL_DEBUG, // Start with debug for testing
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