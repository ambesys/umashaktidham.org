<?php

namespace App\Services;

use PDO;

/**
 * PasswordResetService
 *
 * Handles password reset tokens, email sending, and validation.
 * Manages password_resets table for secure password recovery.
 */
class PasswordResetService
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Generate a password reset token for a user
     */
    public function createResetToken(string $email): bool
    {
        // Find user by email
        $user = $this->findUserByEmail($email);
        if (!$user) {
            // Don't reveal if email exists or not for security
            return true;
        }

        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour')); // 1 hour expiry

        // Delete any existing tokens for this user
        $this->deleteExistingTokens($user['id']);

        // Insert new token
        $stmt = $this->pdo->prepare(
            "INSERT INTO password_resets (user_id, email, token, expires_at, created_at)
             VALUES (:user_id, :email, :token, :expires_at, CURRENT_TIMESTAMP)"
        );

        $stmt->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expires_at', $expiresAt);

        if ($stmt->execute()) {
            // Send reset email (simplified - in production use proper email service)
            $this->sendResetEmail($email, $token);
            return true;
        }

        return false;
    }

    /**
     * Validate a reset token and return user info
     */
    public function validateResetToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT pr.*, u.name, u.email as user_email
             FROM password_resets pr
             JOIN users u ON pr.user_id = u.id
             WHERE pr.token = :token AND pr.expires_at > CURRENT_TIMESTAMP
             AND pr.used = 0 LIMIT 1"
        );

        $stmt->bindParam(':token', $token);
        $stmt->execute();

        $reset = $stmt->fetch(PDO::FETCH_ASSOC);
        return $reset ?: null;
    }

    /**
     * Reset password using valid token
     */
    public function resetPassword(string $token, string $newPassword): bool
    {
        $reset = $this->validateResetToken($token);
        if (!$reset) {
            return false;
        }

        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

        // Update user password
        $stmt = $this->pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':id', $reset['user_id'], PDO::PARAM_INT);

        if ($stmt->execute()) {
            // Mark token as used
            $this->markTokenAsUsed($token);
            return true;
        }

        return false;
    }

    /**
     * Clean up expired tokens (should be called periodically)
     */
    public function cleanupExpiredTokens(): int
    {
        $stmt = $this->pdo->prepare("DELETE FROM password_resets WHERE expires_at < CURRENT_TIMESTAMP");
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Find user by email
     */
    private function findUserByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, email FROM users WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Delete existing tokens for a user
     */
    private function deleteExistingTokens(int $userId): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM password_resets WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Mark token as used
     */
    private function markTokenAsUsed(string $token): void
    {
        $stmt = $this->pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = :token");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
    }

    /**
     * Send reset email (simplified implementation)
     * In production, use a proper email service like SendGrid, Mailgun, etc.
     */
    private function sendResetEmail(string $email, string $token): void
    {
        $resetUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/reset-password?token=' . $token;
        // Prefer NotificationService when available (renders template + sends via Mailer)
        try {
            if (class_exists('\App\\Services\\NotificationService')) {
                // Use NotificationService which will pick provider via env helper
                $notif = new \App\Services\NotificationService();
                // Minimal user array
                $user = ['email' => $email, 'name' => ''];
                $notif->sendForgotPassword($user, $token);
                return;
            }
        } catch (\Throwable $e) {
            error_log('PasswordResetService: NotificationService send failed: ' . $e->getMessage());
        }

        // Fallback to simple mail() if NotificationService not available
        $resetUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/reset-password?token=' . $token;
        $subject = 'Password Reset - Uma Shakti Dham';
        $message = "Hello,\n\nYou have requested to reset your password for your Uma Shakti Dham account.\n\nPlease click the following link to reset your password: {$resetUrl}\n\nThis link will expire in 1 hour.\n\nIf you did not request this password reset, please ignore this email.\n\nBest regards,\nUma Shakti Dham Team";
        $headers = 'From: ' . (getenv('SMTP_FROM') ?: 'noreply@umashaktidham.org') . "\r\n" .
                   'Reply-To: ' . (getenv('SMTP_REPLY_TO') ?: (getenv('SMTP_FROM') ?: 'noreply@umashaktidham.org')) . "\r\n" .
                   'X-Mailer: PHP/' . phpversion();
        mail($email, $subject, $message, $headers);
    }
}