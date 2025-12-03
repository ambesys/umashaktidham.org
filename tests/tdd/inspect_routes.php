<?php
// Debug helper: include routes.php with dispatch skipped and dump registered routes for GET/POST
define('SKIP_ROUTE_DISPATCH', true);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../core/view_helpers.php';

require __DIR__ . '/../routes.php';

if (!isset($router)) {
    echo "No router variable found\n";
    exit(1);
}

$ref = new ReflectionClass($router);
$routesExact = $ref->getProperty('routesExact');
$routesExact->setAccessible(true);
$routesPattern = $ref->getProperty('routesPattern');
$routesPattern->setAccessible(true);

$exact = $routesExact->getValue($router);
$pattern = $routesPattern->getValue($router);

echo "Exact GET routes:\n";
if (isset($exact['GET'])) {
    foreach ($exact['GET'] as $k => $v) {
        echo "  GET $k\n";
    }
}

echo "\nPattern GET routes:\n";
if (isset($pattern['GET'])) {
    foreach ($pattern['GET'] as $entry) {
        echo "  GET pattern: " . $entry['path'] . "\n";
    }
}

echo "\nExact POST routes:\n";
if (isset($exact['POST'])) {
    foreach ($exact['POST'] as $k => $v) {
        echo "  POST $k\n";
    }
}

echo "\nPattern POST routes:\n";
if (isset($pattern['POST'])) {
    foreach ($pattern['POST'] as $entry) {
        echo "  POST pattern: " . $entry['path'] . "\n";
    }
}
