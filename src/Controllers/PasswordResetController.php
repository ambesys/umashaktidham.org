<?php

namespace App\Controllers;

use App\Services\PasswordResetService;

/**
 * PasswordResetController
 *
 * Handles password reset requests and form submissions
 */
class PasswordResetController
{
    private $passwordResetService;

    public function __construct(PasswordResetService $passwordResetService)
    {
        $this->passwordResetService = $passwordResetService;
    }

    /**
     * Show forgot password form
     */
    public function showForgotPasswordForm()
    {
        // Render an HTML form so users can request a password reset link
        // Use the app's render_view helper so header/footer and layout are applied
        if (function_exists('render_view')) {
            render_view('src/Views/auth/forgot-password.php');
            return;
        }

        // Fallback: simple HTML if render_view is not available
        echo '<h1>Forgot Password</h1><p>Please provide your email to receive a reset link.</p>';
    }

    /**
     * Handle forgot password request
     */
    public function handleForgotPassword()
    {
        // Support both form posts (application/x-www-form-urlencoded) and JSON API clients
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $email = '';

        if (stripos($contentType, 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
            $email = $input['email'] ?? '';
        } else {
            $email = $_POST['email'] ?? '';
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if (stripos($contentType, 'application/json') !== false) {
                http_response_code(400);
                echo json_encode(['error' => 'Valid email address required']);
                return;
            }

            // Render form with error message
            if (function_exists('render_view')) {
                render_view('src/Views/auth/forgot-password.php', ['error' => 'Please enter a valid email address.']);
                return;
            }

            http_response_code(400);
            echo 'Valid email address required';
            return;
        }

        $success = $this->passwordResetService->createResetToken($email);

        // For security, always show a generic success message
        if (stripos($contentType, 'application/json') !== false) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'If an account with that email exists, a password reset link has been sent.'
            ]);
            return;
        }

        // Render the reset-password view which instructs the user to check their email
        if (function_exists('render_view')) {
            render_view('src/Views/auth/reset-password.php', ['notice' => 'If an account with that email exists, a reset link has been sent.']);
            return;
        }

        echo 'If an account with that email exists, a reset link has been sent.';
    }

    /**
     * Show reset password form
     */
    public function showResetPasswordForm()
    {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            // Render the reset view without token - this will show the 'check your email' message
            if (function_exists('render_view')) {
                render_view('src/Views/auth/reset-password.php');
                return;
            }

            http_response_code(400);
            echo 'Reset token required';
            return;
        }

        // Validate token
        $reset = $this->passwordResetService->validateResetToken($token);
        if (!$reset) {
            if (function_exists('render_view')) {
                render_view('src/Views/auth/reset-password.php', ['error' => 'Invalid or expired reset token']);
                return;
            }

            http_response_code(400);
            echo 'Invalid or expired reset token';
            return;
        }

        // Render the reset form view, passing token via query so the view picks it up
        $_GET['token'] = $token;
        if (function_exists('render_view')) {
            render_view('src/Views/auth/reset-password.php', ['email' => $reset['email']]);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'message' => 'Reset password form',
            'token' => $token,
            'email' => $reset['email']
        ]);
    }

    /**
     * Handle password reset
     */
    public function handleResetPassword()
    {
        // Support form posts and JSON API requests
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
            $token = $input['token'] ?? '';
            $password = $input['password'] ?? '';
            $confirmPassword = $input['confirm_password'] ?? '';
        } else {
            $token = $_POST['token'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
        }

        if (empty($token)) {
            if (stripos($contentType, 'application/json') !== false) {
                http_response_code(400);
                echo json_encode(['error' => 'Reset token required']);
                return;
            }
            render_view('src/Views/auth/reset-password.php', ['error' => 'Reset token required']);
            return;
        }

        if (empty($password) || strlen($password) < 8) {
            if (stripos($contentType, 'application/json') !== false) {
                http_response_code(400);
                echo json_encode(['error' => 'Password must be at least 8 characters long']);
                return;
            }
            render_view('src/Views/auth/reset-password.php', ['error' => 'Password must be at least 8 characters long']);
            return;
        }

        if ($password !== $confirmPassword) {
            if (stripos($contentType, 'application/json') !== false) {
                http_response_code(400);
                echo json_encode(['error' => 'Passwords do not match']);
                return;
            }
            render_view('src/Views/auth/reset-password.php', ['error' => 'Passwords do not match']);
            return;
        }

        $success = $this->passwordResetService->resetPassword($token, $password);

        if ($success) {
            if (stripos($contentType, 'application/json') !== false) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Password has been reset successfully. You can now log in with your new password.'
                ]);
                return;
            }

            // Redirect to login with a success notice (simple approach)
            header('Location: /login?reset=success');
            exit;
        } else {
            if (stripos($contentType, 'application/json') !== false) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid or expired reset token']);
                return;
            }
            render_view('src/Views/auth/reset-password.php', ['error' => 'Invalid or expired reset token']);
            return;
        }
    }
}