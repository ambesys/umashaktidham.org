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
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'username' => $_POST['username'],
                'email' => $_POST['email'],
                'password' => $_POST['password'],
            ];

            $result = $this->authService->register($data);
            if ($result) {
                // Redirect to login page or dashboard
                header('Location: /public/dashboard.php');
                exit;
            } else {
                // Handle registration error
                $error = "Registration failed. Please try again.";
            }
        }

        include '../src/Views/auth/register.php';
    }

    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'email' => $_POST['email'],
                'password' => $_POST['password'],
            ];

            $user = $this->authService->login($data);
            if ($user) {
                // Set session variables
                $_SESSION['user_id'] = $user->id;
                $_SESSION['user_role'] = $user->role;

                // Redirect to dashboard
                header('Location: /public/dashboard.php');
                exit;
            } else {
                // Handle login error
                $error = "Invalid email or password.";
            }
        }

        include '../src/Views/auth/login.php';
    }

    public function logout()
    {
        session_start();
        session_destroy();
        header('Location: /public/index.php');
        exit;
    }
}