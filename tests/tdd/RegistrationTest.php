<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Services\AuthService;
use PDO;

class RegistrationTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
    // Use MySQL for tests
    $this->pdo = new PDO('mysql:host=localhost;dbname=u103964107_uma', 'root', 'root');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create minimal schema required for the test
        $this->pdo->exec(
            "CREATE TABLE roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                level INTEGER NOT NULL DEFAULT 11
            );"
        );

        $this->pdo->exec(
            "CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT,
                name TEXT,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                role_id INTEGER NULL,
                first_name TEXT,
                last_name TEXT,
                street_address TEXT,
                address_line_2 TEXT,
                zipcode TEXT,
                city TEXT,
                state TEXT,
                country TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );"
        );

        $this->pdo->exec(
            "CREATE TABLE family_members (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                family_id INTEGER NULL,
                user_id INTEGER NULL,
                first_name TEXT NOT NULL,
                last_name TEXT NULL,
                birth_year INTEGER,
                gender TEXT,
                phone_e164 TEXT,
                email TEXT NULL,
                relationship TEXT,
                relationship_other TEXT,
                occupation TEXT,
                business_info TEXT,
                village TEXT,
                mosal TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );"
        );

        // insert default role
        $stmt = $this->pdo->prepare("INSERT INTO roles (name, level) VALUES (:name, :level)");
        $name = 'user'; $level = 11;
        $stmt->bindParam(':name', $name); $stmt->bindParam(':level', $level);
        $stmt->execute();
    }

    public function testRegistrationCreatesUserAndFamilyMember()
    {
        $auth = new AuthService($this->pdo);

        $data = [
            'username' => 'jdoe',
            'name' => 'John Doe',
            'email' => 'jdoe@example.test',
            'password' => 'secret123'
        ];

        $user = $auth->register($data);
        $this->assertIsArray($user);
        $this->assertArrayHasKey('id', $user);
        $userId = (int)$user['id'];

        // verify family_member exists
        $stmt = $this->pdo->prepare('SELECT * FROM family_members WHERE user_id = :uid LIMIT 1');
        $stmt->bindParam(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $fm = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotFalse($fm, 'family_member row should exist linked to user');
        $this->assertEquals('John Doe', $fm['first_name']);
        $this->assertEquals('jdoe@example.test', $fm['email']);

        // verify user's role_id set to user role
        $stmt = $this->pdo->prepare('SELECT r.id as rid, r.name FROM roles r LIMIT 1');
        $stmt->execute();
        $role = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotFalse($role);

        $stmt = $this->pdo->prepare('SELECT role_id FROM users WHERE id = :id');
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotFalse($u);
        $this->assertEquals($role['rid'], $u['role_id']);
    }
}
