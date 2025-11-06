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
                $data = [
                    'username' => trim($_POST['email']), // Use email as username
                    'name' => trim($_POST['first_name'] . ' ' . $_POST['last_name']),
                    'email' => $_POST['email'],
                    'password' => $_POST['password'],
                    'first_name' => $_POST['first_name'],
                    'last_name' => $_POST['last_name'],
                    'phone_e164' => !empty($_POST['phone']) ? $this->formatPhoneNumber($_POST['phone']) : null,
                ];

                $result = $this->authService->register($data);
                if ($result) {
                    // Redirect to login page with success message
                    header('Location: /login?message=Registration successful! Please log in.');
                    exit;
                } else {
                    // Handle registration error
                    $error = "Registration failed. Please try again.";
                }
            }
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
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role_id'] = $user['role_id'];
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                ];

                // Redirect to dashboard
                header('Location: /dashboard');
                exit;
            } else {
                // Handle login error
                $error = "Invalid email or password.";
            }
        }

        include 'src/Views/auth/login.php';
    }

    public function logout()
    {
        session_start();
        session_unset();
        session_destroy();
        header('Location: /login');
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