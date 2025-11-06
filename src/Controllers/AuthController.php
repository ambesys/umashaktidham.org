<?php

namespace App\Controllers;

use App\Services\AuthService;

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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate required fields
            if (empty($_POST['email']) || empty($_POST['password']) || empty($_POST['first_name']) || empty($_POST['last_name'])) {
                $error = "All required fields must be filled.";
            } elseif ($_POST['password'] !== $_POST['confirm_password']) {
                $error = "Passwords do not match.";
            } elseif (!isset($_POST['terms']) || $_POST['terms'] !== 'on') {
                $error = "You must agree to the Terms & Conditions.";
            } else {
                // Initialize $registrationSuccess to avoid undefined variable warning
                $registrationSuccess = false;

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

    
            $user = $this->authService->login($data);

            if ($user) {
                // Use SessionService for session management
                $this->authService->getSessionService()->setSessionData('user', [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                ]);

                // Redirect to dashboard
                header('Location: /dashboard');
                exit;
            } 
        }

        include 'src/Views/auth/login.php';
    }

    public function logout()
    {
        // Clear all session data
        session_start();
        
        // Store auth type if available before clearing session
        $authType = $_SESSION['auth_type'] ?? 'local';
        
        $_SESSION = [];
        
        // Destroy the session
        session_unset();
        session_destroy();

        // Clear all session-related cookies
        if (isset($_COOKIE['PHPSESSID'])) {
            setcookie('PHPSESSID', '', time() - 3600, '/');
        }
        
        // Additional security: clear any OAuth state cookies
        setcookie('oauth_state', '', time() - 3600, '/');
        setcookie('oauth_nonce', '', time() - 3600, '/');
        setcookie('oauth_provider', '', time() - 3600, '/');

        // For Google OAuth specifically, we need to redirect to Google logout first
        // This ensures Google clears its cached session
        if ($authType === 'google') {
            // Redirect to Google logout endpoint
            // This will clear Google's session cache
            $googleLogoutUrl = 'https://accounts.google.com/Logout';
            $returnUrl = urlencode(($_SERVER['HTTP_ORIGIN'] ?? 'http://localhost') . '/login?message=You have been logged out successfully.');
            
            // Use JavaScript to handle the logout flow since we need to clear our session
            // before redirecting to Google
            echo '<!DOCTYPE html>
<html>
<head>
    <title>Logging out...</title>
    <script>
        window.location.href = "' . $googleLogoutUrl . '?continue=' . $returnUrl . '";
    </script>
</head>
<body>
    <p>Logging you out...</p>
    <noscript>
        <a href="' . $googleLogoutUrl . '?continue=' . $returnUrl . '">
            Click here to complete logout
        </a>
    </noscript>
</body>
</html>';
            exit();
        }

        // For regular email/password logout, redirect to login page
        header('Location: /login?message=You have been logged out successfully.');
        exit();
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