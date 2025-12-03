<?php
// Centralized route definitions using Router and controller methods

// Bootstrap dependencies, controllers, services, etc.
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/core/Router.php';
require_once __DIR__ . '/core/View.php';
require_once __DIR__ . '/core/view_helpers.php';


$router = new Router();

if (function_exists('getLogger')) {
	try {
		getLogger()->info('Routes: initializing router', [
			'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
			'uri' => $_SERVER['REQUEST_URI'] ?? ''
		]);
	} catch (\Throwable $e) {
		error_log('Routes: logger unavailable: ' . $e->getMessage());
	}
}

// Ensure bootstrap provided controller instances array
if (!isset($controllers) || !is_array($controllers)) {
	throw new \RuntimeException('Missing $controllers array. Ensure bootstrap.php initializes $controllers with controller instances.');
}

// OAuth wiring: instantiate OAuthService and OAuthController now that config constants (GOOGLE_CLIENT_ID, etc.) are available
try {
	$oauthConfig = [];
	$base = defined('BASE_URL') ? BASE_URL : (getenv('APP_URL') ?: ('http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost:8000')));

	if (defined('GOOGLE_CLIENT_ID') && defined('GOOGLE_CLIENT_SECRET') && GOOGLE_CLIENT_ID && GOOGLE_CLIENT_SECRET) {
		$oauthConfig['google'] = [
			'clientId' => GOOGLE_CLIENT_ID,
			'clientSecret' => GOOGLE_CLIENT_SECRET,
			'redirectUri' => rtrim($base, '/') . '/auth/google/callback',
		];
	}

	if (defined('FACEBOOK_CLIENT_ID') && defined('FACEBOOK_CLIENT_SECRET') && FACEBOOK_CLIENT_ID && FACEBOOK_CLIENT_SECRET) {
		$oauthConfig['facebook'] = [
			'clientId' => FACEBOOK_CLIENT_ID,
			'clientSecret' => FACEBOOK_CLIENT_SECRET,
			'redirectUri' => rtrim($base, '/') . '/auth/facebook/callback',
		];
	}

	// Provide a default Graph API version for Facebook provider if missing (prevents provider constructor errors)
	if (isset($oauthConfig['facebook']) && !isset($oauthConfig['facebook']['graphApiVersion'])) {
		$oauthConfig['facebook']['graphApiVersion'] = 'v17.0';
	}

	if (!empty($oauthConfig) && isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof \PDO) {
		$sessionSrv = null;
		try {
			$sessionSrv = new \App\Services\SessionService($GLOBALS['pdo']);
		} catch (\Throwable $e) {
			// fallback to null; OAuthService will create its own SessionService if needed
			$sessionSrv = null;
		}

		try {
			$oauthService = new \App\Services\OAuthService($GLOBALS['pdo'], $oauthConfig, $sessionSrv);
			// instantiate controller and expose in controllers map
			$controllers['oauth'] = (function() use ($oauthService, $sessionSrv) {
				try {
					return new \App\Controllers\OAuthController($oauthService, $sessionSrv);
				} catch (\Throwable $e) {
					error_log('Failed to instantiate OAuthController: ' . $e->getMessage());
					return null;
				}
			})();
		} catch (\Throwable $e) {
			if (function_exists('getLogger')) {
				getLogger()->error('OAuthService instantiation failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
			} else {
				error_log('OAuthService instantiation failed: ' . $e->getMessage());
			}
		}
	}
} catch (\Throwable $e) {
	if (function_exists('getLogger')) {
		getLogger()->error('OAuth wiring in routes.php failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
	} else {
		error_log('OAuth wiring in routes.php failed: ' . $e->getMessage());
	}
}

// Development helper: quick login/logout for local testing only.
// Usage: /__dev_login?role=admin&user_id=1&next=/admin
//        /__dev_logout?next=/
// This route is intentionally enabled only for requests from localhost.
$router->get('/__dev_login', function() use ($controllers) {
	$remote = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
	if (!in_array($remote, ['127.0.0.1', '::1'])) {
		http_response_code(403);
		echo 'Forbidden';
		return;
	}

	if (session_status() === PHP_SESSION_NONE) {
		@session_start();
	}

	$role = $_GET['role'] ?? 'member';
	$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 1;

	$_SESSION['user_id'] = $userId;
	$_SESSION['user_role'] = $role;

	$next = $_GET['next'] ?? '/';
	header('Location: ' . $next);
	exit;
});

$router->get('/__dev_logout', function() {
	$remote = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
	if (!in_array($remote, ['127.0.0.1', '::1'])) {
		http_response_code(403);
		echo 'Forbidden';
		return;
	}

	if (session_status() === PHP_SESSION_NONE) {
		@session_start();
	}
	unset($_SESSION['user_id'], $_SESSION['user_role']);
	$next = $_GET['next'] ?? '/';
	header('Location: ' . $next);
	exit;
});

// Dev helper: show computed OAuth redirect URIs and current OAuth config (localhost-only)
$router->get('/__dev_oauth_info', function() use ($controllers) {
	$remote = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
	if (!in_array($remote, ['127.0.0.1', '::1'])) {
		http_response_code(403);
		echo 'Forbidden';
		return;
	}

	// compute base URL from constants/env
	$base = defined('BASE_URL') ? BASE_URL : (getenv('APP_URL') ?: ('http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost:8000')));

	$pdoPresent = (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof \PDO);

	$google = [
		'configured' => defined('GOOGLE_CLIENT_ID') && defined('GOOGLE_CLIENT_SECRET') && GOOGLE_CLIENT_ID && GOOGLE_CLIENT_SECRET,
		'client_id' => defined('GOOGLE_CLIENT_ID') ? (GOOGLE_CLIENT_ID ? substr(GOOGLE_CLIENT_ID,0,8) . '...' : '') : '',
		'redirect_uri' => rtrim($base, '/') . '/auth/google/callback'
	];

	$facebook = [
		'configured' => defined('FACEBOOK_CLIENT_ID') && defined('FACEBOOK_CLIENT_SECRET') && FACEBOOK_CLIENT_ID && FACEBOOK_CLIENT_SECRET,
		'client_id' => defined('FACEBOOK_CLIENT_ID') ? (FACEBOOK_CLIENT_ID ? substr(FACEBOOK_CLIENT_ID,0,8) . '...' : '') : '',
		'redirect_uri' => rtrim($base, '/') . '/auth/facebook/callback'
	];

	header('Content-Type: application/json');
	echo json_encode([
		'base' => $base,
		'pdo_present' => $pdoPresent,
		'oauth_controller' => isset($controllers['oauth']) ? (is_object($controllers['oauth']) ? get_class($controllers['oauth']) : (string)$controllers['oauth']) : null,
		'google' => $google,
		'facebook' => $facebook
	], JSON_PRETTY_PRINT);
	return;
});

// Public routes


$router->get('/', function() use ($controllers) {
	// Prefer an explicit home controller if present (e.g. $controllers['home'] or $controllers['index']).
	if (isset($controllers['home']) && method_exists($controllers['home'], 'index')) {
		return $controllers['home']->index();
	}

	// Public homepage: render the index view. Avoid redirecting anonymous visitors to dashboard/login.
	render_view('src/Views/index.php');
});
$router->get('/login', [$controllers['auth'], 'login']);
$router->post('/login', [$controllers['auth'], 'login']);
$router->get('/logout', [$controllers['auth'], 'logout']);
$router->get('/register', [$controllers['auth'], 'register']);
$router->post('/register', [$controllers['auth'], 'register']);

// OAuth routes (parameterized provider) - handles /auth/google and /auth/google/callback
// Uses OAuthController if wired in bootstrap.php
if (isset($controllers['oauth'])) {
	// Redirect to provider (e.g. /auth/google)
	$router->get('/auth/:provider', [$controllers['oauth'], 'redirect']);
	// Callback from provider (e.g. /auth/google/callback)
	$router->get('/auth/:provider/callback', [$controllers['oauth'], 'callback']);
}
$router->get('/donate', function() use ($controllers) {
	// Prefer a Donation controller if bootstrap provided one (keeps DI). Fallback to existing payment handler.
	if (isset($controllers['donation']) && method_exists($controllers['donation'], 'showDonationPage')) {
		return $controllers['donation']->showDonationPage();
	}

	if (isset($controllers['payment']) && is_callable([$controllers['payment'], 'showForm'])) {
		return $controllers['payment']->showForm();
	}

	// Last fallback: render the view via layout helper
	render_view('src/Views/donate.php');
});

$router->post('/donate', [$controllers['donation'], 'processDonation']);

// Static page routes (render views directly when no controller method exists)
$router->get('/about', function() { render_view('src/Views/about.php'); });
$router->get('/kp-history', function() { render_view('src/Views/kp-history.php'); });
$router->get('/committee', function() { render_view('src/Views/committee.php'); });
$router->get('/events', function() { render_view('src/Views/events.php'); });
$router->get('/events/cultural', function() { render_view('src/Views/events/cultural.php'); });
$router->get('/events/festivals', function() { render_view('src/Views/events/festivals.php'); });
$router->get('/indian-holidays', function() { render_view('src/Views/indian-holidays.php'); });
$router->get('/gallery', function() { render_view('src/Views/gallery.php'); });
$router->get('/gallery/events', function() { render_view('src/Views/gallery/events.php'); });
$router->get('/gallery/community', function() { render_view('src/Views/gallery/community.php'); });
$router->get('/membership', function() { render_view('src/Views/membership.php'); });
$router->get('/members/families', function() { render_view('src/Views/members/families.php'); });
$router->get('/youth-corner', function() { render_view('src/Views/youth-corner.php'); });
$router->get('/matrimonial', function() { render_view('src/Views/matrimonial.php'); });
$router->get('/business-directory', function() { render_view('src/Views/business-directory.php'); });
$router->get('/hindu-gods', function() { render_view('src/Views/hindu-gods.php'); });
$router->get('/hindu-rituals', function() { render_view('src/Views/hindu-rituals.php'); });
$router->get('/hindu-scriptures', function() { render_view('src/Views/hindu-scriptures.php'); });
$router->get('/contact', function() { render_view('src/Views/contact.php'); });
$router->get('/direction', function() { render_view('src/Views/direction.php'); });
$router->get('/facilities', function() { render_view('src/Views/facilities.php'); });
$router->get('/under-construction', function() { render_view('src/Views/under-construction.php'); });

// Form POST handlers (try controller methods when available)
$router->post('/contact_submit.php', function() use ($controllers) {
	// Prefer an explicit contact controller if available, then user, otherwise render contact view.
	if (isset($controllers['contact']) && method_exists($controllers['contact'], 'submitContact')) {
		return $controllers['contact']->submitContact();
	}

	if (isset($controllers['user']) && method_exists($controllers['user'], 'submitContact')) {
		return $controllers['user']->submitContact();
	}

	// Fallback: render contact page (server-side form processing not wired)
	render_view('src/Views/contact.php', ['warning' => 'Contact handler not configured on this instance.']);
});

$router->post('/access', [$controllers['auth'], 'handleAccess']);

// Top-level API routes for dashboard AJAX (dashboard.js expects these paths)
// These routes delegate to user controllers with authentication checks.
//
// Authorization model:
// - Regular users (role < 2): can only manage their own family members
// - Elevated roles (admin, moderator, board): can manage any user's family members
//
// Optional user_id parameter:
// - For regular users: if user_id is included in request body, it is ignored/rejected
// - For elevated roles: if user_id is included, they can manage that user's family
// - If user_id is not included: operation applies to the current user
//
// Example requests:
// POST /add-family-member
//   { "first_name": "John", "relationship": "spouse", "user_id": 100002 }  // admin can add for user 100002
//   { "first_name": "Jane", "relationship": "spouse" }  // regular user adds to their own profile
//
// POST /update-family-member
//   { "id": 5, "first_name": "John Updated" }  // regular user updates their own family member
//   { "id": 5, "first_name": "John Updated", "user_id": 100002 }  // admin updates user 100002's family member
//
// POST /delete-family-member
//   { "id": 5 }  // regular user deletes their own family member
//   { "id": 5, "user_id": 100002 }  // admin deletes user 100002's family member

$router->post('/add-family-member', function() use ($controllers) {
	// Ensure user is authenticated
	if (session_status() === PHP_SESSION_NONE) {
		@session_start();
	}
	if (empty($_SESSION['user_id'])) {
		http_response_code(401);
		echo json_encode(['error' => 'Unauthorized']);
		return;
	}
	
	// Controller already handles user_id from JSON input; just delegate
	if (isset($controllers['user']) && method_exists($controllers['user'], 'addFamilyMember')) {
		return $controllers['user']->addFamilyMember();
	}
	http_response_code(404);
	echo json_encode(['error' => 'Not found']);
});

$router->post('/update-family-member', function() use ($controllers) {
	if (session_status() === PHP_SESSION_NONE) {
		@session_start();
	}
	if (empty($_SESSION['user_id'])) {
		http_response_code(401);
		echo json_encode(['error' => 'Unauthorized']);
		return;
	}
	
	// Controller already handles user_id from JSON input; just delegate
	if (isset($controllers['user']) && method_exists($controllers['user'], 'updateFamilyMember')) {
		return $controllers['user']->updateFamilyMember();
	}
	http_response_code(404);
	echo json_encode(['error' => 'Not found']);
});

$router->post('/delete-family-member', function() use ($controllers) {
	if (session_status() === PHP_SESSION_NONE) {
		@session_start();
	}
	if (empty($_SESSION['user_id'])) {
		http_response_code(401);
		echo json_encode(['error' => 'Unauthorized']);
		return;
	}
	
	// Controller already handles user_id from JSON input; just delegate
	if (isset($controllers['user']) && method_exists($controllers['user'], 'deleteFamilyMember')) {
		return $controllers['user']->deleteFamilyMember();
	}
	http_response_code(404);
	echo json_encode(['error' => 'Not found']);
});

$router->post('/update-user', function() use ($controllers) {
	if (session_status() === PHP_SESSION_NONE) {
		@session_start();
	}
	if (empty($_SESSION['user_id'])) {
		http_response_code(401);
		echo json_encode(['error' => 'Unauthorized']);
		return;
	}
	if (isset($controllers['user']) && method_exists($controllers['user'], 'updateProfile')) {
		return $controllers['user']->updateProfile();
	}
	http_response_code(404);
	echo json_encode(['error' => 'Not found']);
});

// Dashboard AJAX endpoints for modal forms (fetch form HTML)
$router->get('/get-user-form', function() use ($controllers) {
	if (session_status() === PHP_SESSION_NONE) {
		@session_start();
	}
	if (empty($_SESSION['user_id'])) {
		http_response_code(401);
		exit('Unauthorized');
	}
	
	if (isset($controllers['dashboard']) && method_exists($controllers['dashboard'], 'getUserForm')) {
		return $controllers['dashboard']->getUserForm();
	}
	http_response_code(404);
	echo 'Endpoint not found';
});

$router->get('/get-family-member-form', function() use ($controllers) {
	if (session_status() === PHP_SESSION_NONE) {
		@session_start();
	}
	if (empty($_SESSION['user_id'])) {
		http_response_code(401);
		exit('Unauthorized');
	}
	
	if (isset($controllers['dashboard']) && method_exists($controllers['dashboard'], 'getFamilyMemberForm')) {
		return $controllers['dashboard']->getFamilyMemberForm();
	}
	http_response_code(404);
	echo 'Endpoint not found';
});

// Unified member form endpoint (add/edit/update users & family members)
$router->get('/get-member-form', function() use ($controllers) {
	if (session_status() === PHP_SESSION_NONE) {
		@session_start();
	}
	if (empty($_SESSION['user_id'])) {
		http_response_code(401);
		exit('Unauthorized');
	}
	
	if (isset($controllers['dashboard']) && method_exists($controllers['dashboard'], 'getMemberForm')) {
		return $controllers['dashboard']->getMemberForm();
	}
	http_response_code(404);
	echo 'Endpoint not found';
});

// Save member endpoint (add/edit/update)
$router->post('/save-member', function() use ($controllers) {
	if (session_status() === PHP_SESSION_NONE) {
		@session_start();
	}
	if (empty($_SESSION['user_id'])) {
		http_response_code(401);
		echo json_encode(['success' => false, 'message' => 'Unauthorized']);
		exit;
	}
	
	if (isset($controllers['dashboard']) && method_exists($controllers['dashboard'], 'saveMember')) {
		return $controllers['dashboard']->saveMember();
	}
	http_response_code(404);
	echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
});

// User routes (protected by UserAuthMiddleware)
$router->group('/user', \App\Middleware\UserAuthMiddleware::class, function($router) use ($controllers) {
	$router->get('/dashboard', function() use ($controllers) {
		if (isset($controllers['user']) && method_exists($controllers['user'], 'dashboard')) {
			$data = $controllers['user']->dashboard();
			if (is_array($data)) {
				render_view('src/Views/dashboard/index.php', ['dashboardData' => $data]);
				return;
			}
			return;
		}

		if (isset($controllers['dashboard']) && method_exists($controllers['dashboard'], 'index')) {
			$data = $controllers['dashboard']->index();
			if (is_array($data)) {
				render_view('src/Views/dashboard/index.php', ['dashboardData' => $data]);
				return;
			}
			return;
		}

		// Fallback: render dashboard index view with minimal data
		render_view('src/Views/dashboard/index.php', ['dashboardData' => ['user' => ['name' => 'Guest']]]);
	});

	$router->get('/profile', function() use ($controllers) {
		if (isset($controllers['user']) && method_exists($controllers['user'], 'profile')) {
			return $controllers['user']->profile();
		}
		// If MemberController exists and can render profile for current user
		if (isset($controllers['member']) && method_exists($controllers['member'], 'profile')) {
			return $controllers['member']->profile($_SESSION['user_id'] ?? null);
		}
		render_view('src/Views/members/profile.php');
	});

		// Change password (user-initiated)
		$router->get('/change-password', function() use ($controllers) {
			if (isset($controllers['user']) && method_exists($controllers['user'], 'showChangePasswordForm')) {
				return $controllers['user']->showChangePasswordForm();
			}
			// fallback view
			render_view('src/Views/user/change-password.php');
		});

		$router->post('/change-password', function() use ($controllers) {
			if (isset($controllers['user']) && method_exists($controllers['user'], 'handleChangePassword')) {
				return $controllers['user']->handleChangePassword();
			}
			// fallback: simple redirect back
			header('Location: /user/change-password');
			exit;
		});

	$router->post('/profile', [$controllers['user'], 'updateProfile']);

	$router->get('/family', function() use ($controllers) {
		if (isset($controllers['user']) && method_exists($controllers['user'], 'family')) {
			return $controllers['user']->family();
		}
		if (isset($controllers['family']) && method_exists($controllers['family'], 'index')) {
			return $controllers['family']->index($_SESSION['user_id'] ?? null);
		}
		render_view('src/Views/members/families.php');
	});

	$router->post('/family/add', [$controllers['user'], 'addFamilyMember']);

	$router->post('/family/update', [$controllers['user'], 'updateFamilyMember']);

	$router->post('/family/delete', [$controllers['user'], 'deleteFamilyMember']);

	$router->get('/events', function() use ($controllers) {
		// Prefer the Event controller when available
		if (isset($controllers['event']) && method_exists($controllers['event'], 'index')) {
			return $controllers['event']->index();
		}

		// Allow user controller to provide a userEvents view if implemented
		if (isset($controllers['user']) && method_exists($controllers['user'], 'userEvents')) {
			return $controllers['user']->userEvents();
		}

		render_view('src/Views/events.php');
	});

	$router->get('/event/view', function() use ($controllers) {
		// Prefer Event controller show()
		if (isset($controllers['event']) && method_exists($controllers['event'], 'show')) {
			$id = $_GET['id'] ?? null;
			return $controllers['event']->show($id);
		}

		// Allow user controller to provide event viewing if implemented
		if (isset($controllers['user']) && method_exists($controllers['user'], 'viewEvent')) {
			return $controllers['user']->viewEvent();
		}

		render_view('src/Views/events.php');
	});

	$router->post('/event/register', [$controllers['event'], 'register']);

	$router->get('/members', function() use ($controllers) {
		if (isset($controllers['user']) && method_exists($controllers['user'], 'members')) {
			return $controllers['user']->members();
		}
		render_view('src/Views/members/index.php');
	});

	$router->get('/member/view', function() use ($controllers) {
		if (isset($controllers['user']) && method_exists($controllers['user'], 'viewMember')) {
			return $controllers['user']->viewMember();
		}
		render_view('src/Views/members/profile.php');
	});

	$router->post('/member/update', [$controllers['user'], 'updateMember']);
});

// Admin routes (protected by AdminAuthMiddleware)
$router->group('/admin', \App\Middleware\AdminAuthMiddleware::class, function($router) use ($controllers) {
	// Support visiting /admin (no trailing dashboard) — render admin index
	$router->get('/', function() use ($controllers) {
		$stats = [];
		$recentActivity = [];
		
		if (isset($controllers['admin'])) {
			if (method_exists($controllers['admin'], 'getDashboardStats')) {
				$stats = $controllers['admin']->getDashboardStats();
			}
			if (method_exists($controllers['admin'], 'getRecentActivity')) {
				$recentActivity = $controllers['admin']->getRecentActivity();
			}
		}

		render_view('src/Views/admin/index.php', ['stats' => $stats, 'recentActivity' => $recentActivity]);
	});
	
	$router->get('/dashboard', function() use ($controllers) {
		$stats = [];
		$recentActivity = [];
		
		if (isset($controllers['admin'])) {
			if (method_exists($controllers['admin'], 'getDashboardStats')) {
				$stats = $controllers['admin']->getDashboardStats();
			}
			if (method_exists($controllers['admin'], 'getRecentActivity')) {
				$recentActivity = $controllers['admin']->getRecentActivity();
			}
		}

		render_view('src/Views/admin/index.php', ['stats' => $stats, 'recentActivity' => $recentActivity]);
	});

	$router->get('/users', function() use ($controllers) {
		$users = [];
		$stats = [];

		if (isset($controllers['admin'])) {
			// If controller provides a rendered response (string/html), return it directly
			if (method_exists($controllers['admin'], 'listUsers')) {
				$res = $controllers['admin']->listUsers();
				if (is_string($res) && strlen($res) > 0) {
					echo $res;
					return;
				}
				if (is_array($res) && !empty($res)) {
					$users = $res;
				}
			}

			if (empty($users) && method_exists($controllers['admin'], 'getUsersWithFamilyData')) {
				$users = $controllers['admin']->getUsersWithFamilyData();
			}

			if (method_exists($controllers['admin'], 'getDashboardStats')) {
				$stats = $controllers['admin']->getDashboardStats();
			}
		}

		// Render via the layout helper so header/footer and access gating are applied consistently
		render_view('src/Views/admin/users.php', ['users' => $users, 'stats' => $stats]);
	});

	$router->post('/user/promote', [$controllers['admin'], 'promoteUser']);
	// Admin user deletion via GET with ID param (simple client-side link or JS fetch to this URL)
	$router->get('/user/delete/:id', [$controllers['admin'], 'deleteUser']);
	
	// Admin: Get member form for adding/editing users and their families
	$router->get('/get-admin-member-form', [$controllers['admin'], 'getAdminMemberForm']);
	// Admin: Save member (user or family member) - reuses DashboardController logic with admin override
	$router->post('/save-admin-member', [$controllers['admin'], 'saveAdminMember']);
});

// Password reset (public)


$router->get('/forgot-password', [$controllers['passwordreset'], 'showForgotPasswordForm']);
$router->post('/forgot-password', [$controllers['passwordreset'], 'handleForgotPassword']);
$router->get('/reset-password', [$controllers['passwordreset'], 'showResetPasswordForm']);
$router->post('/reset-password', [$controllers['passwordreset'], 'handleResetPassword']);

// Dispatch (allow tests to include routes.php and control dispatch)
if (!defined('SKIP_ROUTE_DISPATCH')) {
	// Provide a simple 404 page so Router redirects to /404 don't loop when the view is missing.
	$router->get('/404', function() {
		http_response_code(404);
		echo '<h1>404 - Page Not Found</h1><p>The requested page could not be found.</p>';
	});

	if (function_exists('getLogger')) {
		try {
			getLogger()->info('Routes: dispatching', [
				'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
				'uri' => $_SERVER['REQUEST_URI'] ?? ''
			]);
		} catch (\Throwable $e) {
			// ignore
		}
	}
	$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
}
