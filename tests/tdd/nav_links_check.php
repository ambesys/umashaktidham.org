<?php
// Script to programmatically request navbar links via the Router
// Run: php tests/nav_links_check.php

error_reporting(E_ERROR);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure bootstrap and router are available
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../core/view_helpers.php';

// Prepare admin session so admin links render and admin middleware trusts us
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';
// Grant access flag so layout doesn't redirect to /access during automated checks
$_SESSION['access_granted_until'] = time() + 3600;

$controllers['journal'] = new class {
    public function home() {
        if (function_exists('render_view')) {
            // attempt to render the main index view if available
            if (file_exists(__DIR__ . '/../src/Views/index.php')) {
                render_view('src/Views/index.php');
                return;
            }
        }
        echo "[stub journal home]";
    }
    public function userEvents() { echo "[stub userEvents]"; }
    public function viewEvent() { echo "[stub viewEvent]"; }
    public function submitContact() { echo "[stub submitContact]"; }
};

// Prevent routes.php from auto-dispatching on include
define('SKIP_ROUTE_DISPATCH', true);
require_once __DIR__ . '/../routes.php';

if (!isset($router) || !is_object($router)) {
    echo "Router not available after including routes.php\n";
    exit(1);
}

$navLinks = [
    '/',
    '/about', '/kp-history', '/committee',
    '/events', '/events/cultural', '/events/festivals', '/indian-holidays',
    '/gallery', '/gallery/events', '/gallery/community',
    '/membership', '/members/families', '/youth-corner', '/matrimonial', '/business-directory',
    '/indian-holidays', '/hindu-gods', '/hindu-rituals', '/hindu-scriptures',
    '/contact', '/direction', '/facilities',
    '/donate',
    // User/admin links
    '/user/dashboard', '/user/profile', '/user/family', '/user/events', '/user/members',
    '/admin', '/admin/users', '/admin/events', '/admin/moderators', '/admin/reports', '/admin/settings',
];

// Use a worker subprocess per-URI to isolate redirects/exits from stopping the main script
$results = [];
foreach ($navLinks as $uri) {
    echo "Dispatching: $uri\n";
    $cmd = 'php ' . escapeshellarg(__DIR__ . '/nav_links_worker.php') . ' ' . escapeshellarg($uri);
    $output = [];
    $exit = 0;
    exec($cmd . ' 2>&1', $output, $exit);
    $outStr = implode("\n", $output);
    if (preg_match('/OK:len=(\d+)/', $outStr, $m)) {
        $len = (int)$m[1];
        $results[$uri] = ['status' => 'ok', 'len' => $len];
    } else {
        $results[$uri] = ['status' => 'fail', 'message' => $outStr, 'exit' => $exit];
    }
}

// Print results
foreach ($results as $uri => $res) {
    if ($res['status'] === 'ok') {
        echo "[OK]  $uri -> output length={$res['len']}\n";
    } else {
        echo "[FAIL] $uri -> {$res['status']}: {$res['message']}\n";
    }
}

echo "Done.\n";
