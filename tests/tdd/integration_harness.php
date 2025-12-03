<?php
// Simple integration harness: include routes without dispatching, then run a few requests through the Router.
// Run: php tests/integration_harness.php

// Reduce noise from notices/warnings for missing optional controllers during harness
error_reporting(E_ERROR);

// Provide minimal controller stubs so routes.php can register handlers without full bootstrap wiring
// These are only for the integration harness and do not alter application code.
class IntegrationJournalController
{
    public function home()
    {
        if (function_exists('render_view')) {
            render_view('src/Views/index.php');
            return;
        }
        echo file_get_contents(__DIR__ . '/../src/Views/index.php');
    }
}

class IntegrationAuthController
{
    public function login()
    {
        if (function_exists('render_view')) {
            render_view('src/Views/auth/login.php');
            return;
        }
        echo file_get_contents(__DIR__ . '/../src/Views/auth/login.php');
    }
}

// Expose $controllers expected by routes.php
$controllers = [
    'journal' => new IntegrationJournalController(),
    'auth' => new IntegrationAuthController(),
];

define('SKIP_ROUTE_DISPATCH', true);
require_once __DIR__ . '/../routes.php';

echo "[integration] routes.php included\n";

// Debug: show registered exact GET routes
$ref = new ReflectionObject($router);
$prop = $ref->getProperty('routesExact');
$prop->setAccessible(true);
$routesExact = $prop->getValue($router);
echo "[integration] Registered GET exact routes: \n";
foreach ($routesExact['GET'] as $p => $_) {
    echo " - $p\n";
}

// Sanity check: call journal->home directly (prefer local test stub, fallback to bootstrapped controllers)
$journalCtrl = $controllers['journal'] ?? ($GLOBALS['controllers']['journal'] ?? null);
if ($journalCtrl) {
    $len = strlen((function() use ($journalCtrl) { ob_start(); $journalCtrl->home(); return ob_get_clean(); })());
    echo "[integration] direct journal->home() call output_len=$len\n";
} else {
    echo "[integration] no journal controller available for direct call\n";
}

// $router should be available after including routes.php
if (!isset($router) || !is_object($router)) {
    echo "Router not available after including routes.php\n";
    exit(1);
}

$cases = [
    ['method' => 'GET', 'uri' => '/'],
    ['method' => 'GET', 'uri' => '/login'],
    ['method' => 'GET', 'uri' => '/donate'],
    ['method' => 'GET', 'uri' => '/about'],
    ['method' => 'GET', 'uri' => '/contact'],
];

$results = [];
foreach ($cases as $c) {
    // Set server values used by Router/handlers
    $_SERVER['REQUEST_METHOD'] = $c['method'];
    $_SERVER['REQUEST_URI'] = $c['uri'];

    echo "[integration] dispatching {$c['method']} {$c['uri']}\n";
    ob_start();
    try {
        $router->dispatch($c['method'], $c['uri']);
        $out = ob_get_clean();
        $results[] = [
            'case' => $c,
            'status' => 'ok',
            'output_len' => strlen($out)
        ];
    } catch (Exception $e) {
        ob_end_clean();
        $results[] = [
            'case' => $c,
            'status' => 'exception',
            'message' => $e->getMessage()
        ];
    } catch (Error $err) {
        ob_end_clean();
        $results[] = [
            'case' => $c,
            'status' => 'error',
            'message' => $err->getMessage()
        ];
    }
}

// Print summary
foreach ($results as $r) {
    $case = $r['case'];
    echo "[{$r['status']}] {$case['method']} {$case['uri']} -> ";
    if ($r['status'] === 'ok') {
        echo "output_len=" . $r['output_len'] . "\n";
    } else {
        echo ($r['message'] ?? 'no message') . "\n";
    }
}

exit(0);
