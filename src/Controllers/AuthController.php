<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\LoggerService;

class AuthController
{
    protected $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function register()
    {
        if (isset($_SESSION['user'])) {
            header('Location: /dashboard');
            exit;
        }

        // Initialize variables
        $registrationSuccess = false;
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate required fields
            if (empty($_POST['email']) || empty($_POST['password']) || empty($_POST['first_name']) || empty($_POST['last_name'])) {
                $error = "All required fields must be filled.";
            } elseif ($_POST['password'] !== $_POST['confirm_password']) {
                $error = "Passwords do not match.";
            } elseif (!isset($_POST['terms']) || (isset($_POST['terms']) && $_POST['terms'] !== 'on')) {
                $error = "You must agree to the Terms & Conditions.";
            } else {

                $data = [
                    'username' => trim($_POST['email']), // Use email as username
                    'name' => trim($_POST['first_name'] . ' ' . $_POST['last_name']),
                    'email' => $_POST['email'],
                    'password' => $_POST['password'],
                    'first_name' => $_POST['first_name'],
                    'last_name' => $_POST['last_name'],
                    'phone_e164' => !empty($_POST['phone']) ? $this->formatPhoneNumber($_POST['phone']) : null,
                ];

                try {
                    $result = $this->authService->register($data);
                    if ($result) {
                        $registrationSuccess = true;
                            // Send welcome email (best-effort)
                            try {
                                $notif = new \App\Services\NotificationService();
                                $notif->sendRegistration($result);
                            } catch (\Throwable $e) {
                                error_log('AuthController: failed to send welcome email: ' . $e->getMessage());
                            }
                    } else {
                        $error = "Registration failed. Please try again.";
                    }
                } catch (\Exception $e) {
                    $error = $e->getMessage(); // Display the exception message on the registration page
                }
            }
        }

        if ($registrationSuccess) {
            // Redirect to login page with success message
            header('Location: /login?success=1');
            exit;
        }

        // Render using layout helper when available so header/footer are applied
        $data = [];
        if (isset($error)) $data['error'] = $error;
        if (isset($registrationSuccess)) $data['registrationSuccess'] = $registrationSuccess;

        if (function_exists('render_view')) {
            render_view('src/Views/auth/register.php', $data);
            return;
        }

        include 'src/Views/auth/register.php';
    }

    public function login()
    {
        if (isset($_SESSION['user'])) {
            header('Location: /dashboard');
            exit;
        }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'email' => $_POST['email'],
                'password' => $_POST['password'],
            ];

            error_log("Login attempt for email: " . $data['email']);
    
            $user = $this->authService->login($data);

            if ($user) {
                error_log("Login successful for user: " . $user['email'] . " with role_id: " . ($user['role_id'] ?? 'NULL'));
                // Use SessionService for session management
                $this->authService->getSessionService()->setSessionData('user', [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                ]);

                error_log("Session data set, redirecting to user dashboard");
                // Redirect to user dashboard (routes are registered under /user group)
                // Use 303 See Other so user agents convert the POST to a GET for the subsequent request
                header('Location: /user/dashboard', true, 303);
                exit;
            } else {
                error_log("Login failed for email: " . $data['email']);
                $error = "Invalid email or password. Please try again.";
            }
        }

        // Render using layout helper when available so header/footer are applied
        $viewData = [];
        if (isset($error)) $viewData['error'] = $error;
        if (isset($_GET['message'])) $viewData['message'] = $_GET['message'];
        if (isset($_GET['success'])) $viewData['success'] = $_GET['success'];

        if (function_exists('render_view')) {
            render_view('src/Views/auth/login.php', $viewData);
            return;
        }

        include 'src/Views/auth/login.php';
    }

    public function logout()
    {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Clear all session data
        $_SESSION = [];
        session_unset();
        session_destroy();

        // Clear session cookies
        setcookie('PHPSESSID', '', time() - 3600, '/', '', false, true);
        
        // Redirect to login with success message
        header('Location: /login?message=You have been logged out successfully.');
        exit();
    }

    /**
     * Access gate handler: grants temporary access and redirects
     */
    public function handleAccess()
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Read posted password; support both 'access_password' and generic 'password'
            $postedPassword = '';
            if (isset($_POST['access_password'])) {
                $postedPassword = (string)$_POST['access_password'];
            } elseif (isset($_POST['password'])) {
                $postedPassword = (string)$_POST['password'];
            }
            $postedPassword = trim($postedPassword);

            // Debug: log what we received (safe, non-sensitive metadata)
            LoggerService::debug('Access attempt', [
                'has_post_access_password' => isset($_POST['access_password']),
                'has_post_password' => isset($_POST['password']),
                'posted_len' => strlen($postedPassword),
                'post_keys' => array_keys($_POST)
            ]);

            // Resolve configured access password from env or config
            $configuredPassword = getenv('ACCESS_PASSWORD');
            if ($configuredPassword === false || $configuredPassword === null || $configuredPassword === '') {
                // Optional: fallback to config/global
                if (isset($GLOBALS['config']['access_password'])) {
                    $configuredPassword = (string)$GLOBALS['config']['access_password'];
                }
            }

            $next = isset($_GET['next']) ? $_GET['next'] : '/';

            // Validate
            if ($configuredPassword === null || $configuredPassword === false) {
                $configuredPassword = '';
            }
            $configuredPassword = trim((string)$configuredPassword);

            // Debug: log configured password status
            LoggerService::debug('Access config check', [
                'has_env_password' => getenv('ACCESS_PASSWORD') !== false,
                'configured_len' => strlen($configuredPassword)
            ]);

            if ($configuredPassword === '') {
                LoggerService::error('Access gate misconfigured: ACCESS_PASSWORD not set');
                // Fail closed
                header('Location: /access?next=' . urlencode($next) . '&error=Access%20not%20configured');
                exit();
            }

            if (!hash_equals((string)$configuredPassword, (string)$postedPassword)) {
                // Log metadata without exposing secrets
                LoggerService::warning('Access denied: incorrect password', [
                    'posted_len' => strlen($postedPassword),
                    'configured_len' => strlen($configuredPassword)
                ]);
                header('Location: /access?next=' . urlencode($next) . '&error=Incorrect%20password');
                exit();
            }

            // Success: set session flags
            $_SESSION['access_granted'] = true;
            $_SESSION['last_activity_ts'] = time();
            LoggerService::info('Access granted; redirecting to next: ' . $next);
            header('Location: ' . $next);
            exit();

        // Optional: record acceptance
        if (function_exists('getLogger')) {
            try {
                getLogger()->info('Access granted', [
                    'until' => $_SESSION['access_granted_until'] ?? null,
                    'remote' => $_SERVER['REMOTE_ADDR'] ?? ''
                ]);
            } catch (\Throwable $e) {}
        }

        // Redirect to next path if provided
        $next = $_POST['next'] ?? $_GET['next'] ?? '/';
        header('Location: ' . (string)$next);
        exit;
    }

    /**
     * Format phone number to E.164 format
     */
    private function formatPhoneNumber($phone)
    {
        // Remove all non-digit characters
        $phone = preg_replace('/\D/', '', $phone);

        // If it starts with country code, assume it's already formatted
        if (strlen($phone) > 10 && $phone[0] === '1') {
            return '+' . $phone;
        }

        // For US numbers, add +1 prefix
        if (strlen($phone) === 10) {
            return '+1' . $phone;
        }

        // For numbers with country code but no +, add +
        if (strlen($phone) === 11 && $phone[0] === '1') {
            return '+' . $phone;
        }

        // Return as-is if we can't determine format
        return '+' . $phone;
    }
}