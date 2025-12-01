<?php

namespace App\Services;

use PDO;

/**
 * AuthService
 *
 * Responsible for registration/login flows. Accepts an optional PDO instance
 * so tests can inject an in-memory DB. Falls back to the project's
 * `config/database.php` when not provided.
 */
class AuthService
{
    private $pdo;
    private $sessionService;

    public function __construct(PDO $pdo = null, SessionService $sessionService = null)
    {
        if ($pdo) {
            $this->pdo = $pdo;
            $this->sessionService = $sessionService;
            return;
        }

        // fallback to project config (existing pattern)
        $cfg = __DIR__ . '/../../config/database.php';
        if (file_exists($cfg)) {
            require $cfg; // expects $pdo
            if (isset($pdo) && $pdo instanceof PDO) {
                $this->pdo = $pdo;
            }
        }

        if (!$this->pdo) {
            throw new \RuntimeException('AuthService requires a PDO instance');
        }

        // Initialize session service if not provided
        if (!$this->sessionService) {
            require_once __DIR__ . '/SessionService.php';
            $this->sessionService = new SessionService($this->pdo);
        }
    }

    /**
     * Register a new user and create a canonical family_member record.
     * Returns the created user row as associative array on success, false on failure.
     */
    public function register(array $data)
    {
        // minimal validation
        if (empty($data['email']) || empty($data['password'])) {
            return false;
        }

        $username = $data['username'] ?? $data['email']; // Use email as username if not provided
        $name = $data['name'] ?? $username;
        $email = $data['email'];
        $hashed = password_hash($data['password'], PASSWORD_BCRYPT);
        $firstName = $data['first_name'] ?? null;
        $lastName = $data['last_name'] ?? null;
        $phoneE164 = $data['phone_e164'] ?? null;

        // Determine default role id for new users (prefer 'user' role if present)
        try {
            $roleStmt = $this->pdo->prepare("SELECT id FROM roles WHERE name = :name LIMIT 1");
            $rname = 'user';
            $roleStmt->bindParam(':name', $rname);
            $roleStmt->execute();
            $defaultRoleId = (int)($roleStmt->fetchColumn() ?: 1);
        } catch (\Throwable $e) {
            $defaultRoleId = 1;
        }

        // Check if the username already exists
        $checkStmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
        $checkStmt->execute(['username' => $username]);
        if ($checkStmt->fetchColumn() > 0) {
            throw new \Exception("The username '{$username}' is already taken.");
        }

        // Set default role_id = 1 (user role) on initial insert
        // Some test DB schemas may be minimal (no first_name/last_name/phone_e164 columns).
        // Try the full insert first; if it fails due to missing columns, fall back to a simpler insert.
        try {
            $stmt = $this->pdo->prepare("INSERT INTO users (username, name, email, password, first_name, last_name, phone_e164, role_id, created_at) VALUES (:username, :name, :email, :password, :first_name, :last_name, :phone_e164, :role_id, CURRENT_TIMESTAMP)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed);
            $stmt->bindParam(':first_name', $firstName);
            $stmt->bindParam(':last_name', $lastName);
            $stmt->bindParam(':phone_e164', $phoneE164);
            $stmt->bindValue(':role_id', $defaultRoleId, PDO::PARAM_INT);

            if (!$stmt->execute()) {
                return false;
            }
        } catch (\PDOException $e) {
            // Fallback for minimal test schemas that lack some columns (e.g. first_name/last_name)
            // Attempt a simpler insert without those optional columns
            $fallbackSql = "INSERT INTO users (username, name, email, password, role_id, created_at) VALUES (:username, :name, :email, :password, :role_id, CURRENT_TIMESTAMP)";
            $stmt = $this->pdo->prepare($fallbackSql);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed);
            $stmt->bindValue(':role_id', $defaultRoleId, PDO::PARAM_INT);

            if (!$stmt->execute()) {
                return false;
            }
            // Ensure first/last name variables are populated for family_member insert
            if (empty($firstName) && !empty($name)) {
                $firstName = $name; // store full name in family_member.first_name for minimal schemas
            }
        }

        $userId = (int)$this->pdo->lastInsertId();

        // Create canonical family_member for this user with relationship='self'
        // Insert a canonical family_member row for this user. Use a minimal column set
        // to remain compatible with test schemas and lightweight installations.
        $fm = $this->pdo->prepare("INSERT INTO family_members (user_id, first_name, last_name, email, created_at) VALUES (:user_id, :first_name, :last_name, :email, CURRENT_TIMESTAMP)");
        $fm->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $fm->bindParam(':first_name', $firstName);
        $fm->bindParam(':last_name', $lastName);
        $fm->bindParam(':email', $email);
        $fm->execute();

        // return created user row
        $q = $this->pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $q->bindParam(':id', $userId, PDO::PARAM_INT);
        $q->execute();
        return $q->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Basic login: returns user row on success, false otherwise.
     */
    public function login(array $data)
    {
        $logger = new LoggerService(); // Assuming LoggerService is properly included and initialized
        $logger->info('Login attempt started.');

        if (empty($data['email']) || empty($data['password'])) {
            $logger->error('Login failed: Missing email or password.');
            return false;
        }

        $logger->info('Fetching user with email: ' . $data['email']);
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $data['email']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $logger->info('User found: ' . json_encode(['id' => $user['id'], 'email' => $user['email'], 'role_id' => $user['role_id']]));
            if (password_verify($data['password'], $user['password'])) {
                $logger->info('Password verification successful.');
                
                // Get role name from role_id
                $roleName = $this->getRoleName($user['role_id']);
                
                $this->sessionService->setAuthenticatedUser($user['id'], $user['role_id'], $roleName);
                $this->sessionService->setSessionData('auth_type', 'local'); // Store auth type as local/email
                $logger->info('User authenticated and session set.');
                return $user;
            } else {
                $logger->warning('Password verification failed.');
            }
        } else {
            $logger->warning('No user found with email: ' . $data['email']);
        }

        $logger->error('Login attempt failed.');
        return false;
    }

    public function logout()
    {
        $this->sessionService->logout();
    }

    /**
     * Change password for a user after verifying current password.
     * Returns true on success, or throws Exception on validation failure.
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        if (empty($userId) || empty($newPassword)) {
            throw new \InvalidArgumentException('User id and new password are required.');
        }

        $stmt = $this->pdo->prepare("SELECT password FROM users WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new \RuntimeException('User not found.');
        }

        // Verify current password
        if (!password_verify($currentPassword, $row['password'])) {
            throw new \RuntimeException('Current password is incorrect.');
        }

        // Hash new password and update
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $up = $this->pdo->prepare("UPDATE users SET password = :pwd, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        $up->bindParam(':pwd', $hash);
        $up->bindParam(':id', $userId, PDO::PARAM_INT);
        if (!$up->execute()) {
            throw new \RuntimeException('Failed to update password.');
        }

        return true;
    }

    /**
     * Get user by email
     */
    public function getUserByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get the SessionService instance
     */
    public function getSessionService()
    {
        return $this->sessionService;
    }

    /**
     * Get role name by role ID
     */
    private function getRoleName(?int $roleId): string
    {
        if (!$roleId) {
            return 'user'; // Default role
        }
        
        $stmt = $this->pdo->prepare("SELECT name FROM roles WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $roleId, PDO::PARAM_INT);
        $stmt->execute();
        $role = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $role['name'] ?? 'user';
    }
}