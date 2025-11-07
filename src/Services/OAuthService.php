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
                        'has_given_name' => isset($userData['given_name']),
                        'has_family_name' => isset($userData['family_name']),
                        'has_gender' => isset($userData['gender']),
                        'has_birthday' => isset($userData['birthday']),
                        'has_picture' => isset($userData['picture']),
                        'email_verified' => $userData['email_verified'] ?? false,
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
                        'has_first_name' => isset($userData['first_name']),
                        'has_last_name' => isset($userData['last_name']),
                        'has_gender' => isset($userData['gender']),
                        'has_birthday' => isset($userData['birthday']),
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

        // Create new user from OAuth provider
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
        try {
            // Extract available user data based on provider
            $userInfo = $this->extractUserInfo($provider, $userData);
            
            // Generate a unique username
            $baseUsername = strtolower(str_replace(' ', '', $userInfo['first_name']));
            $username = $baseUsername;
            $counter = 1;
            while ($this->usernameExists($username)) {
                $username = $baseUsername . $counter;
                $counter++;
            }

            if (function_exists('getLogger')) {
                $logger = getLogger();
                $logger->info("Creating OAuth user", [
                    'provider' => $provider,
                    'email' => $userInfo['email'],
                    'username' => $username,
                    'first_name' => $userInfo['first_name'],
                    'last_name' => $userInfo['last_name'],
                    'gender' => $userInfo['gender'],
                    'birth_year' => $userInfo['birth_year']
                ]);
            }

            // Create user with available data
            $stmt = $this->pdo->prepare(
                "INSERT INTO users (username, name, email, first_name, last_name, phone_e164, auth_type, email_verified_at, created_at)
                 VALUES (:username, :name, :email, :first_name, :last_name, :phone, :auth_type, :email_verified_at, CURRENT_TIMESTAMP)"
            );
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':name', $userInfo['full_name']);
            $stmt->bindParam(':email', $userInfo['email']);
            $stmt->bindParam(':first_name', $userInfo['first_name']);
            $stmt->bindParam(':last_name', $userInfo['last_name']);
            $stmt->bindParam(':phone', $userInfo['phone']);
            $stmt->bindParam(':auth_type', $provider);
            $stmt->bindParam(':email_verified_at', $userInfo['email_verified_at']);
            $stmt->execute();

            $userId = (int)$this->pdo->lastInsertId();

            if (function_exists('getLogger')) {
                $logger = getLogger();
                $logger->info("Created user record", ['user_id' => $userId]);
            }

            // Set default role
            $roleStmt = $this->pdo->prepare("SELECT id FROM roles WHERE name = 'user' LIMIT 1");
            $roleStmt->execute();
            $role = $roleStmt->fetch(PDO::FETCH_ASSOC);
            if ($role) {
                $updateStmt = $this->pdo->prepare("UPDATE users SET role_id = :role_id WHERE id = :id");
                $updateStmt->bindParam(':role_id', $role['id'], PDO::PARAM_INT);
                $updateStmt->bindParam(':id', $userId, PDO::PARAM_INT);
                $updateStmt->execute();

                if (function_exists('getLogger')) {
                    $logger = getLogger();
                    $logger->info("Set user role", ['user_id' => $userId, 'role_id' => $role['id']]);
                }
            }

            // Create canonical family_member with relation 'self' and all available data
            $fmStmt = $this->pdo->prepare(
                "INSERT INTO family_members (user_id, first_name, last_name, birth_year, gender, email, phone_e164, relationship, created_at)
                 VALUES (:user_id, :first_name, :last_name, :birth_year, :gender, :email, :phone, :relationship, CURRENT_TIMESTAMP)"
            );
            $fmStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $fmStmt->bindParam(':first_name', $userInfo['first_name']);
            $fmStmt->bindParam(':last_name', $userInfo['last_name']);
            $fmStmt->bindParam(':birth_year', $userInfo['birth_year']);
            $fmStmt->bindParam(':gender', $userInfo['gender']);
            $fmStmt->bindParam(':email', $userInfo['email']);
            $fmStmt->bindParam(':phone', $userInfo['phone']);
            $relationship = 'self';
            $fmStmt->bindParam(':relationship', $relationship);
            $fmStmt->execute();

            if (function_exists('getLogger')) {
                $logger = getLogger();
                $logger->info("Created family member record", ['user_id' => $userId]);
            }

            // Link provider
            $this->linkProviderToUser($userId, $provider, $this->getProviderUserId($provider, $userData), $userData);

            if (function_exists('getLogger')) {
                $logger = getLogger();
                $logger->info("Linked OAuth provider", ['user_id' => $userId, 'provider' => $provider]);
            }

            // Return user data
            $userStmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
            $userStmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $userStmt->execute();
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);

            if (function_exists('getLogger')) {
                $logger = getLogger();
                $logger->info("OAuth user creation completed", ['user_id' => $userId, 'email' => $user['email']]);
            }

            return $user;

        } catch (\Exception $e) {
            if (function_exists('getLogger')) {
                $logger = getLogger();
                $logger->error("Failed to create OAuth user", [
                    'provider' => $provider,
                    'email' => $userData['email'] ?? 'unknown',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
            throw $e; // Re-throw to be handled by controller
        }
    }

    /**
     * Extract and normalize user information from OAuth provider data
     */
    private function extractUserInfo(string $provider, array $userData): array
    {
        $info = [
            'full_name' => '',
            'first_name' => '',
            'last_name' => '',
            'email' => $userData['email'] ?? '',
            'phone' => null,
            'gender' => 'prefer_not_say',
            'birth_year' => null,
            'email_verified_at' => null
        ];

        switch ($provider) {
            case 'google':
                // Google provides structured name data
                $info['full_name'] = trim($userData['name'] ?? '');
                $info['first_name'] = trim($userData['given_name'] ?? $info['full_name']);
                $info['last_name'] = trim($userData['family_name'] ?? '');
                
                // Extract gender if available
                if (isset($userData['gender'])) {
                    $gender = strtolower($userData['gender']);
                    if (in_array($gender, ['male', 'female', 'other'])) {
                        $info['gender'] = $gender;
                    }
                }
                
                // Extract birth year if available (format: YYYY-MM-DD or YYYY)
                if (isset($userData['birthday'])) {
                    $birthday = $userData['birthday'];
                    if (preg_match('/^(\d{4})/', $birthday, $matches)) {
                        $info['birth_year'] = (int)$matches[1];
                    }
                }
                
                // Set email verification if confirmed by Google
                if (isset($userData['email_verified']) && $userData['email_verified']) {
                    $info['email_verified_at'] = date('Y-m-d H:i:s');
                }
                break;

            case 'facebook':
                // Facebook provides first_name and last_name separately
                $info['full_name'] = trim($userData['name'] ?? '');
                $info['first_name'] = trim($userData['first_name'] ?? $info['full_name']);
                $info['last_name'] = trim($userData['last_name'] ?? '');
                
                // Extract gender if available
                if (isset($userData['gender'])) {
                    $gender = strtolower($userData['gender']);
                    if (in_array($gender, ['male', 'female'])) {
                        $info['gender'] = $gender;
                    }
                }
                
                // Extract birth year if available (format: MM/DD/YYYY)
                if (isset($userData['birthday'])) {
                    $birthday = $userData['birthday'];
                    if (preg_match('/(\d{4})$/', $birthday, $matches)) {
                        $info['birth_year'] = (int)$matches[1];
                    }
                }
                break;
        }

        // Fallback: if we don't have first_name but have full_name, try to parse it
        if (empty($info['first_name']) && !empty($info['full_name'])) {
            $parts = explode(' ', $info['full_name'], 2);
            $info['first_name'] = $parts[0];
            $info['last_name'] = $parts[1] ?? '';
        }

        // Ensure we have at least a first name
        if (empty($info['first_name'])) {
            $info['first_name'] = 'User'; // Fallback name
        }

        return $info;
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