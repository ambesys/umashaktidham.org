<?php

namespace App\Services;

use PDO;

/**
 * WebAuthnService
 *
 * Handles WebAuthn/Passkey authentication and registration.
 * Manages webauthn_credentials table for storing credential data.
 *
 * Note: This is a simplified implementation. For production use,
 * consider using the full web-auth/webauthn-lib library with proper
 * validation and security measures.
 */
class WebAuthnService
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Generate registration challenge for a user
     * In a real implementation, this would create proper WebAuthn options
     */
    public function generateRegistrationChallenge(int $userId): array
    {
        // Generate a simple challenge (in production, use proper WebAuthn flow)
        $challenge = bin2hex(random_bytes(32));

        // Store challenge
        $this->storeChallenge($userId, $challenge);

        return [
            'challenge' => $challenge,
            'rp' => [
                'name' => 'Uma Shakti Dham',
                'id' => $_SERVER['HTTP_HOST'] ?? 'localhost'
            ],
            'user' => $this->getUserEntity($userId),
            'pubKeyCredParams' => [
                ['alg' => -7, 'type' => 'public-key'], // ES256
                ['alg' => -257, 'type' => 'public-key'] // RS256
            ]
        ];
    }

    /**
     * Register a WebAuthn credential
     * Simplified implementation - stores credential data
     */
    public function registerCredential(int $userId, array $credentialData): bool
    {
        try {
            // Verify challenge
            $storedChallenge = $this->getStoredChallenge($userId);
            if (!$storedChallenge || $storedChallenge !== ($credentialData['challenge'] ?? '')) {
                throw new \RuntimeException('Invalid challenge');
            }

            // Store credential (simplified - in production validate properly)
            $stmt = $this->pdo->prepare(
                "INSERT INTO webauthn_credentials (user_id, credential_id, credential_data, created_at)
                 VALUES (:user_id, :credential_id, :credential_data, CURRENT_TIMESTAMP)"
            );

            $credentialId = $credentialData['id'] ?? bin2hex(random_bytes(16));
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':credential_id', $credentialId);
            $stmt->bindValue(':credential_data', json_encode($credentialData));
            $stmt->execute();

            // Clear challenge
            $this->clearChallenge($userId);

            return true;

        } catch (\Exception $e) {
            $this->clearChallenge($userId);
            throw $e;
        }
    }

    /**
     * Generate authentication challenge
     */
    public function generateAuthenticationChallenge(int $userId): array
    {
        $challenge = bin2hex(random_bytes(32));
        $this->storeChallenge($userId, $challenge);

        return [
            'challenge' => $challenge,
            'rpId' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'allowCredentials' => $this->getUserCredentials($userId)
        ];
    }

    /**
     * Authenticate using WebAuthn
     * Simplified implementation
     */
    public function authenticate(int $userId, array $credentialData): bool
    {
        try {
            // Verify challenge
            $storedChallenge = $this->getStoredChallenge($userId);
            if (!$storedChallenge || $storedChallenge !== ($credentialData['challenge'] ?? '')) {
                throw new \RuntimeException('Invalid challenge');
            }

            // Verify credential exists for user
            $credentialId = $credentialData['id'] ?? '';
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM webauthn_credentials
                 WHERE user_id = :user_id AND credential_id = :credential_id"
            );
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':credential_id', $credentialId);
            $stmt->execute();

            if ($stmt->fetchColumn() == 0) {
                throw new \RuntimeException('Credential not found');
            }

            // Clear challenge
            $this->clearChallenge($userId);

            return true;

        } catch (\Exception $e) {
            $this->clearChallenge($userId);
            throw $e;
        }
    }

    /**
     * Get user entity data
     */
    private function getUserEntity(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT u.id, u.username, u.name, u.email
             FROM users u WHERE u.id = :id LIMIT 1"
        );
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new \RuntimeException("User not found: $userId");
        }

        return [
            'id' => (string)$user['id'],
            'name' => $user['username'] ?: $user['email'],
            'displayName' => $user['name'] ?: $user['username']
        ];
    }

    /**
     * Get user's WebAuthn credentials
     */
    public function getUserCredentials(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT credential_id FROM webauthn_credentials WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function($row) {
            return ['id' => $row['credential_id'], 'type' => 'public-key'];
        }, $rows);
    }

    /**
     * Remove a WebAuthn credential
     */
    public function removeCredential(int $userId, string $credentialId): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM webauthn_credentials WHERE user_id = :user_id AND credential_id = :credential_id"
        );
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':credential_id', $credentialId);
        return $stmt->execute();
    }

    // Challenge management helpers

    private function storeChallenge(int $userId, string $challenge): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $_SESSION['webauthn_challenge_' . $userId] = $challenge;
    }

    private function getStoredChallenge(int $userId): ?string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        return $_SESSION['webauthn_challenge_' . $userId] ?? null;
    }

    private function clearChallenge(int $userId): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        unset($_SESSION['webauthn_challenge_' . $userId]);
    }
}