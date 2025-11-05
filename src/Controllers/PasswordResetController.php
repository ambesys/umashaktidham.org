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
        // This would typically render a view, but for now return JSON
        header('Content-Type: application/json');
        echo json_encode([
            'message' => 'Forgot password form',
            'endpoint' => '/auth/forgot-password'
        ]);
    }

    /**
     * Handle forgot password request
     */
    public function handleForgotPassword()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid email address required']);
            return;
        }

        $success = $this->passwordResetService->createResetToken($email);

        // Always return success for security (don't reveal if email exists)
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'If an account with that email exists, a password reset link has been sent.'
        ]);
    }

    /**
     * Show reset password form
     */
    public function showResetPasswordForm()
    {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            http_response_code(400);
            echo json_encode(['error' => 'Reset token required']);
            return;
        }

        // Validate token
        $reset = $this->passwordResetService->validateResetToken($token);
        if (!$reset) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid or expired reset token']);
            return;
        }

        // This would typically render a view, but for now return JSON
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
        $input = json_decode(file_get_contents('php://input'), true);
        $token = $input['token'] ?? '';
        $password = $input['password'] ?? '';
        $confirmPassword = $input['confirm_password'] ?? '';

        if (empty($token)) {
            http_response_code(400);
            echo json_encode(['error' => 'Reset token required']);
            return;
        }

        if (empty($password) || strlen($password) < 8) {
            http_response_code(400);
            echo json_encode(['error' => 'Password must be at least 8 characters long']);
            return;
        }

        if ($password !== $confirmPassword) {
            http_response_code(400);
            echo json_encode(['error' => 'Passwords do not match']);
            return;
        }

        $success = $this->passwordResetService->resetPassword($token, $password);

        if ($success) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Password has been reset successfully. You can now log in with your new password.'
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid or expired reset token']);
        }
    }
}