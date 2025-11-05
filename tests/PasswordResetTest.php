<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Services\PasswordResetService;
use PDO;

class PasswordResetTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
        // Use in-memory SQLite for tests
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create minimal schema required for the test
        $this->pdo->exec(
            "CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT,
                name TEXT,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                role_id INTEGER NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );"
        );

        $this->pdo->exec(
            "CREATE TABLE password_resets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                email TEXT NOT NULL,
                token TEXT NOT NULL UNIQUE,
                expires_at DATETIME NOT NULL,
                used INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );"
        );

        // Insert test user
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (username, name, email, password, created_at)
             VALUES (:username, :name, :email, :password, CURRENT_TIMESTAMP)"
        );
        $stmt->bindValue(':username', 'testuser');
        $stmt->bindValue(':name', 'Test User');
        $stmt->bindValue(':email', 'test@example.com');
        $stmt->bindValue(':password', password_hash('password123', PASSWORD_BCRYPT));
        $stmt->execute();
    }

    public function testCreateResetToken()
    {
        $service = new PasswordResetService($this->pdo);

        // Test creating reset token for existing user
        $result = $service->createResetToken('test@example.com');
        $this->assertTrue($result);

        // Check that token was created
        $stmt = $this->pdo->prepare("SELECT * FROM password_resets WHERE email = :email");
        $stmt->bindValue(':email', 'test@example.com');
        $stmt->execute();
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($reset);
        $this->assertEquals('test@example.com', $reset['email']);
        $this->assertNotEmpty($reset['token']);
        $this->assertEquals(0, $reset['used']);
    }

    public function testValidateResetToken()
    {
        $service = new PasswordResetService($this->pdo);

        // Create a token first
        $service->createResetToken('test@example.com');

        // Get the token
        $stmt = $this->pdo->prepare("SELECT token FROM password_resets WHERE email = :email");
        $stmt->bindValue(':email', 'test@example.com');
        $stmt->execute();
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);
        $token = $reset['token'];

        // Validate the token
        $result = $service->validateResetToken($token);
        $this->assertNotNull($result);
        $this->assertEquals('test@example.com', $result['email']);
        $this->assertEquals('Test User', $result['name']);
    }

    public function testResetPassword()
    {
        $service = new PasswordResetService($this->pdo);

        // Create a token first
        $service->createResetToken('test@example.com');

        // Get the token
        $stmt = $this->pdo->prepare("SELECT token FROM password_resets WHERE email = :email");
        $stmt->bindValue(':email', 'test@example.com');
        $stmt->execute();
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);
        $token = $reset['token'];

        // Reset password
        $newPassword = 'newpassword123';
        $result = $service->resetPassword($token, $newPassword);
        $this->assertTrue($result);

        // Verify password was changed
        $stmt = $this->pdo->prepare("SELECT password FROM users WHERE email = :email");
        $stmt->bindValue(':email', 'test@example.com');
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertTrue(password_verify($newPassword, $user['password']));

        // Verify token was marked as used
        $stmt = $this->pdo->prepare("SELECT used FROM password_resets WHERE token = :token");
        $stmt->bindValue(':token', $token);
        $stmt->execute();
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals(1, $reset['used']);
    }

    public function testInvalidToken()
    {
        $service = new PasswordResetService($this->pdo);

        // Try to validate non-existent token
        $result = $service->validateResetToken('invalid-token');
        $this->assertNull($result);

        // Try to reset with invalid token
        $result = $service->resetPassword('invalid-token', 'newpassword');
        $this->assertFalse($result);
    }

    public function testNonExistentEmail()
    {
        $service = new PasswordResetService($this->pdo);

        // Try to create token for non-existent email
        // Should return true for security (not revealing if email exists)
        $result = $service->createResetToken('nonexistent@example.com');
        $this->assertTrue($result);

        // But no token should be created
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM password_resets WHERE email = :email");
        $stmt->bindValue(':email', 'nonexistent@example.com');
        $stmt->execute();
        $count = $stmt->fetchColumn();

        $this->assertEquals(0, $count);
    }
}