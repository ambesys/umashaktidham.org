<?php

namespace App\Services;

use PDO;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\Facebook;

/**
 * OAuthService
 *
 * Handles OAuth authentication with Google and Facebook providers.
 * Manages user_providers table for linking OAuth accounts to users.
 */
class OAuthService
{
    private $pdo;
    private $googleProvider;
    private $facebookProvider;
    private $sessionService;

    public function __construct(PDO $pdo, array $config = [], SessionService $sessionService = null)
    {
        $this->pdo = $pdo;
        $this->sessionService = $sessionService ?: new SessionService($pdo);

        // Initialize OAuth providers with config
        if (isset($config['google'])) {
            $this->googleProvider = new Google($config['google']);
        }

        if (isset($config['facebook'])) {
            $this->facebookProvider = new Facebook($config['facebook']);
        }
    }

    /**
     * Get authorization URL for a provider
     */
    public function getAuthorizationUrl(string $provider): string
    {
        switch ($provider) {
            case 'google':
                if (!$this->googleProvider) {
                    throw new \RuntimeException('Google OAuth not configured');
                }
                return $this->googleProvider->getAuthorizationUrl([
                    'scope' => ['email', 'profile'],
                    'prompt' => 'select_account' // Force account selection screen
                ]);

            case 'facebook':
                if (!$this->facebookProvider) {
                    throw new \RuntimeException('Facebook OAuth not configured');
                }
                return $this->facebookProvider->getAuthorizationUrl([
                    'scope' => ['email']
                ]);

            default:
                throw new \InvalidArgumentException("Unsupported provider: $provider");
        }
    }

    /**
     * Handle OAuth callback and create/link user account
     */
    public function handleCallback(string $provider, string $code): array
    {
        $userData = null;

        switch ($provider) {
            case 'google':
                if (!$this->googleProvider) {
                    throw new \RuntimeException('Google OAuth not configured');
                }
                
                if (function_exists('getLogger')) {
                    $logger = getLogger();
                    $logger->info("Starting Google OAuth callback", ['code_length' => strlen($code)]);
                }
                
                $token = $this->googleProvider->getAccessToken('authorization_code', [
                    'code' => $code
                ]);
                $userData = $this->googleProvider->getResourceOwner($token)->toArray();
                
                if (function_exists('getLogger')) {
                    $logger = getLogger();
                    $logger->info("Google OAuth user data received", [
                        'has_email' => isset($userData['email']),
                        'has_sub' => isset($userData['sub']),
                        'has_name' => isset($userData['name']),
                        'email' => $userData['email'] ?? 'missing'
                    ]);
                }
                break;

            case 'facebook':
                if (!$this->facebookProvider) {
                    throw new \RuntimeException('Facebook OAuth not configured');
                }
                
                if (function_exists('getLogger')) {
                    $logger = getLogger();
                    $logger->info("Starting Facebook OAuth callback", ['code_length' => strlen($code)]);
                }
                
                $token = $this->facebookProvider->getAccessToken('authorization_code', [
                    'code' => $code
                ]);
                $userData = $this->facebookProvider->getResourceOwner($token)->toArray();
                
                if (function_exists('getLogger')) {
                    $logger = getLogger();
                    $logger->info("Facebook OAuth user data received", [
                        'has_email' => isset($userData['email']),
                        'has_id' => isset($userData['id']),
                        'has_name' => isset($userData['name']),
                        'email' => $userData['email'] ?? 'missing'
                    ]);
                }
                break;

            default:
                throw new \InvalidArgumentException("Unsupported provider: $provider");
        }

        // Validate required user data
        if (empty($userData['email'])) {
            throw new \RuntimeException('Email is required from OAuth provider');
        }

        // Get provider-specific user ID
        $providerId = $this->getProviderUserId($provider, $userData);

        // Check if user already exists via provider
        $existingUser = $this->findUserByProvider($provider, $providerId);

        if ($existingUser) {
            // Link existing user
            if (function_exists('getLogger')) {
                $logger = getLogger();
                $logger->info("Found existing user via OAuth provider", [
                    'provider' => $provider,
                    'provider_id' => $providerId,
                    'user_id' => $existingUser['id'],
                    'email' => $existingUser['email']
                ]);
            }
            return $existingUser;
        }

        // Check if user exists by email
        $existingUser = $this->findUserByEmail($userData['email']);

        if ($existingUser) {
            // Link provider to existing user
            $this->linkProviderToUser($existingUser['id'], $provider, $providerId, $userData);
            if (function_exists('getLogger')) {
                $logger = getLogger();
                $logger->info("Linked OAuth provider to existing user", [
                    'provider' => $provider,
                    'provider_id' => $providerId,
                    'user_id' => $existingUser['id'],
                    'email' => $existingUser['email']
                ]);
            }
            return $existingUser;
        }

        // Create new user
        if (function_exists('getLogger')) {
            $logger = getLogger();
            $logger->info("Creating new user from OAuth provider", [
                'provider' => $provider,
                'provider_id' => $providerId,
                'email' => $userData['email'],
                'name' => $userData['name'] ?? 'unknown'
            ]);
        }
        $newUser = $this->createUserFromProvider($provider, $userData);
        return $newUser;
    }

    /**
     * Get provider-specific user ID from OAuth user data
     */
    private function getProviderUserId(string $provider, array $userData): string
    {
        switch ($provider) {
            case 'google':
                // Google uses 'sub' (subject) as the unique identifier
                if (!isset($userData['sub'])) {
                    throw new \RuntimeException('Google OAuth did not return a valid user ID (sub field)');
                }
                return (string)$userData['sub'];

            case 'facebook':
                // Facebook uses 'id' as the unique identifier
                if (!isset($userData['id'])) {
                    throw new \RuntimeException('Facebook OAuth did not return a valid user ID (id field)');
                }
                return (string)$userData['id'];

            default:
                throw new \InvalidArgumentException("Unsupported provider: $provider");
        }
    }

    /**
     * Find user by OAuth provider ID
     */
    private function findUserByProvider(string $provider, string $providerId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT u.* FROM users u
             JOIN user_providers up ON u.id = up.user_id
             WHERE up.provider = :provider AND up.provider_user_id = :provider_id LIMIT 1"
        );
        $stmt->bindParam(':provider', $provider);
        $stmt->bindParam(':provider_id', $providerId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Find user by email
     */
    private function findUserByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Link OAuth provider to existing user
     */
    private function linkProviderToUser(int $userId, string $provider, string $providerId, array $userData): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO user_providers (user_id, provider, provider_user_id, profile, created_at)
             VALUES (:user_id, :provider, :provider_id, :profile_data, CURRENT_TIMESTAMP)"
        );
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':provider', $provider);
        $stmt->bindValue(':provider_id', $providerId);
        $stmt->bindValue(':profile_data', json_encode($userData));
        $stmt->execute();
    }

    /**
     * Create new user from OAuth provider data
     */
    private function createUserFromProvider(string $provider, array $userData): array
    {
        // Parse name into first and last name
        $fullName = trim($userData['name'] ?? '');
        $firstName = $fullName;
        $lastName = '';
        if (strpos($fullName, ' ') !== false) {
            $parts = explode(' ', $fullName, 2);
            $firstName = $parts[0];
            $lastName = $parts[1];
        }

        // Generate a unique username
        $baseUsername = strtolower(str_replace(' ', '', $firstName));
        $username = $baseUsername;
        $counter = 1;
        while ($this->usernameExists($username)) {
            $username = $baseUsername . $counter;
            $counter++;
        }

        // Create user
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (username, name, email, first_name, last_name, created_at)
             VALUES (:username, :name, :email, :first_name, :last_name, CURRENT_TIMESTAMP)"
        );
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':name', $fullName);
        $stmt->bindParam(':email', $userData['email']);
        $stmt->bindParam(':first_name', $firstName);
        $stmt->bindParam(':last_name', $lastName);
        $stmt->execute();

        $userId = (int)$this->pdo->lastInsertId();

        // Set default role
        $roleStmt = $this->pdo->prepare("SELECT id FROM roles WHERE name = 'user' LIMIT 1");
        $roleStmt->execute();
        $role = $roleStmt->fetch(PDO::FETCH_ASSOC);
        if ($role) {
            $updateStmt = $this->pdo->prepare("UPDATE users SET role_id = :role_id WHERE id = :id");
            $updateStmt->bindParam(':role_id', $role['id'], PDO::PARAM_INT);
            $updateStmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $updateStmt->execute();
        }

        // Create canonical family_member with relation 'self'
        $fmStmt = $this->pdo->prepare(
            "INSERT INTO family_members (user_id, first_name, last_name, email, relation, created_at)
             VALUES (:user_id, :first_name, :last_name, :email, :relation, CURRENT_TIMESTAMP)"
        );
        $fmStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $fmStmt->bindParam(':first_name', $firstName);
        $fmStmt->bindParam(':last_name', $lastName);
        $fmStmt->bindParam(':email', $userData['email']);
        $relation = 'self';
        $fmStmt->bindParam(':relation', $relation);
        $fmStmt->execute();

        // Link provider
        $this->linkProviderToUser($userId, $provider, $this->getProviderUserId($provider, $userData), $userData);

        // Return user data
        $userStmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $userStmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $userStmt->execute();
        return $userStmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Check if username exists
     */
    private function usernameExists(string $username): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Get linked providers for a user
     */
    public function getUserProviders(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM user_providers WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}