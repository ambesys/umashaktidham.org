<?php
// Worker that dispatches a single URI and prints the output length or error
// Usage: php tests/nav_links_worker.php "/path"

if ($argc < 2) {
    fwrite(STDERR, "Usage: php tests/nav_links_worker.php <uri>\n");
    exit(2);
}
$uri = $argv[1];
error_reporting(E_ERROR);
if (session_status() === PHP_SESSION_NONE) session_start();

// Prepare admin session so admin links render and admin middleware trusts us
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';
$_SESSION['access_granted_until'] = time() + 3600;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../core/view_helpers.php';

// Provide minimal journal stub if needed
$controllers['journal'] = new class {
    public function home() { echo '[stub journal home]'; }
    public function userEvents() { echo '[stub userEvents]'; }
    public function viewEvent() { echo '[stub viewEvent]'; }
    public function submitContact() { echo '[stub submitContact]'; }
};

// Provide lightweight controller stubs for user/admin to avoid DB/session redirects during checks
$controllers['user'] = new class {
    public function dashboard() { echo '[stub user dashboard]'; }
    public function profile() { echo '[stub user profile]'; }
    public function family() { echo '[stub user family]'; }
    public function members() { echo '[stub user members]'; }
};

$controllers['admin'] = new class {
    public function index() { echo '[stub admin index]'; }
    public function listUsers() { echo '[stub admin users]'; }
    public function getDashboardStats() { return ['ok' => true]; }
    public function getUsers() { return []; }
};

// Provide no-op middleware stubs to avoid real middleware performing redirects/exits during automated checks
if (!class_exists('\App\Middleware\UserAuthMiddleware')) {
    eval('namespace App\\Middleware; class UserAuthMiddleware { public function __construct() {} public function handle($request, $next, $options = []) { return $next($request); } }');
}
if (!class_exists('\App\Middleware\AdminAuthMiddleware')) {
    eval('namespace App\\Middleware; class AdminAuthMiddleware { public function __construct() {} public function handle($request, $next, $options = []) { return $next($request); } }');
}

define('SKIP_ROUTE_DISPATCH', true);
require_once __DIR__ . '/../routes.php';

// Capture output and exit code
ob_start();
try {
    $router->dispatch('GET', $uri);
    $out = ob_get_clean();
    echo "OK:len=" . strlen($out);
    exit(0);
} catch (Exception $e) {
    ob_end_clean();
    fwrite(STDERR, "EXC: " . $e->getMessage() . "\n");
    exit(3);
} catch (Error $err) {
    ob_end_clean();
    fwrite(STDERR, "ERR: " . $err->getMessage() . "\n");
    exit(4);
}
