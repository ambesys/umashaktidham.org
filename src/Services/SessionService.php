<?php

namespace App\Services;

use PDO;

/**
 * SessionService
 *
 * Handles database-backed session management for security and scalability.
 * Manages sessions table for persistent session storage.
 */
class SessionService
{
    private $pdo;
    private $sessionLifetime;

    public function __construct(?PDO $pdo = null, int $sessionLifetime = 7200) // 2 hours default
    {
        $this->pdo = $pdo;
        $this->sessionLifetime = $sessionLifetime;

        // Only configure session handlers if session is not already active
        if (session_status() !== PHP_SESSION_ACTIVE) {
            // If no PDO, use regular PHP sessions
            if ($pdo === null) {
                session_start();
                return;
            }

            // Set up session handlers
            session_set_save_handler(
                [$this, 'open'],
                [$this, 'close'],
                [$this, 'read'],
                [$this, 'write'],
                [$this, 'destroy'],
                [$this, 'gc']
            );

            // Configure session settings
            ini_set('session.gc_maxlifetime', $sessionLifetime);
            ini_set('session.cookie_lifetime', $sessionLifetime);
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.use_only_cookies', 1);

            // Start session
            session_start();
        }
    }

    /**
     * Session handler: open
     */
    public function open(string $savePath, string $sessionName): bool
    {
        return true;
    }

    /**
     * Session handler: close
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * Session handler: read
     */
    public function read(string $sessionId): string
    {
        if ($this->pdo === null) {
            return '';
        }
        
        $stmt = $this->pdo->prepare(
            "SELECT data FROM sessions WHERE session_id = :session_id AND expires_at > CURRENT_TIMESTAMP"
        );
        $stmt->bindParam(':session_id', $sessionId);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['data'] : '';
    }

    /**
     * Session handler: write
     */
    public function write(string $sessionId, string $data): bool
    {
        $expiresAt = date('Y-m-d H:i:s', time() + $this->sessionLifetime);

        // Try to update existing session
        $stmt = $this->pdo->prepare(
            "UPDATE sessions SET data = :data, expires_at = :expires_at, updated_at = CURRENT_TIMESTAMP
             WHERE session_id = :session_id"
        );
        $stmt->bindParam(':session_id', $sessionId);
        $stmt->bindParam(':data', $data);
        $stmt->bindParam(':expires_at', $expiresAt);

        if ($stmt->execute() && $stmt->rowCount() > 0) {
            return true;
        }

        // Insert new session if update didn't affect any rows
        $stmt = $this->pdo->prepare(
            "INSERT INTO sessions (session_id, data, expires_at, created_at, updated_at)
             VALUES (:session_id, :data, :expires_at, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)"
        );
        $stmt->bindParam(':session_id', $sessionId);
        $stmt->bindParam(':data', $data);
        $stmt->bindParam(':expires_at', $expiresAt);

        return $stmt->execute();
    }

    /**
     * Session handler: destroy
     */
    public function destroy(string $sessionId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE session_id = :session_id");
        $stmt->bindParam(':session_id', $sessionId);
        return $stmt->execute();
    }

    /**
     * Session handler: garbage collection
     */
    public function gc(int $maxLifetime): int
    {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE expires_at < CURRENT_TIMESTAMP");
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Get current session data
     */
    public function getSessionData(): array
    {
        return $_SESSION ?? [];
    }

    /**
     * Set session data
     */
    public function setSessionData(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Get session data by key
     */
    public function getSessionDataByKey(string $key)
    {
        return $_SESSION[$key] ?? null;
    }

    /**
     * Remove session data by key
     */
    public function removeSessionData(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Get current user ID
     */
    public function getCurrentUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get current user role
     */
    public function getCurrentUserRole(): ?int
    {
        return $_SESSION['user_role_id'] ?? null;
    }

    /**
     * Set authenticated user
     */
    public function setAuthenticatedUser(int $userId, ?int $roleId = null, ?string $roleName = null): void
    {
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_role_id'] = $roleId;
        $_SESSION['user_role'] = $roleName ?? 'user'; // Store role name for middleware checks

        // Update user's last_login_at timestamp
        $stmt = $this->pdo->prepare("UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Logout user and destroy session
     */
    public function logout(): void
    {
        // Clear session data
        session_unset();
        session_destroy();

        // Regenerate session ID for security
        session_start();
        session_regenerate_id(true);
    }

    /**
     * Regenerate session ID (for security)
     */
    public function regenerateId(): void
    {
        session_regenerate_id(true);
    }

    /**
     * Get all active sessions for a user (for admin purposes)
     */
    public function getUserSessions(int $userId): array
    {
        // Note: This assumes we store user_id in session data
        // In a real implementation, you might want to store user sessions separately
        $stmt = $this->pdo->prepare(
            "SELECT s.* FROM sessions s WHERE s.data LIKE :user_pattern AND s.expires_at > CURRENT_TIMESTAMP"
        );
        $stmt->bindValue(':user_pattern', '%"user_id";i:' . $userId . ';%');
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Destroy all sessions for a user (force logout everywhere)
     */
    public function destroyUserSessions(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM sessions WHERE data LIKE :user_pattern"
        );
        $stmt->bindValue(':user_pattern', '%"user_id";i:' . $userId . ';%');
        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * Clean up expired sessions (should be called periodically)
     */
    public function cleanupExpiredSessions(): int
    {
        return $this->gc($this->sessionLifetime);
    }

    /**
     * Get session statistics
     */
    public function getSessionStats(): array
    {
        $stats = [];

        // Total active sessions
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM sessions WHERE expires_at > CURRENT_TIMESTAMP");
        $stats['active_sessions'] = $stmt->fetchColumn();

        // Total expired sessions
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM sessions WHERE expires_at <= CURRENT_TIMESTAMP");
        $stats['expired_sessions'] = $stmt->fetchColumn();

        // Oldest session
        $stmt = $this->pdo->query("SELECT MIN(created_at) FROM sessions");
        $stats['oldest_session'] = $stmt->fetchColumn();

        return $stats;
    }
}