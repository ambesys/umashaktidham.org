<?php
// web.php is now a minimal route registration file. Define routes using Router and controller methods only.

require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../src/Controllers/DashboardController.php';

use App\Controllers\DashboardController;

// Example: $router->get('/', [HomeController::class, 'index']);
// Example: $router->post('/login', [AuthController::class, 'login']);

// Dashboard AJAX endpoints for modal forms
$router->get('/get-user-form', [DashboardController::class, 'getUserForm']);
$router->get('/get-family-member-form', [DashboardController::class, 'getFamilyMemberForm']);

// Add your clean route definitions here, one GET and one POST per page/action, using controller methods.