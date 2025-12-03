<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../vendor/autoload.php';

class UserFindTest extends TestCase
{
    private $pdo;
    private $userModel;

    public function setUp(): void
    {
    $this->pdo = new PDO('mysql:host=localhost;dbname=u103964107_uma', 'root', 'root');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create schema
    $this->pdo->exec("CREATE TABLE users (id INTEGER PRIMARY KEY, email TEXT, first_name TEXT, last_name TEXT, password TEXT, role_id INTEGER, street_address TEXT, address_line_2 TEXT, zipcode TEXT, city TEXT, state TEXT, country TEXT, created_at TEXT, updated_at TEXT);");
        $this->pdo->exec("CREATE TABLE family_members (id INTEGER PRIMARY KEY, user_id INTEGER, first_name TEXT, last_name TEXT, birth_year INTEGER, gender TEXT, email TEXT, phone_e164 TEXT, relationship TEXT, occupation TEXT, business_info TEXT, village TEXT, mosal TEXT, created_at TEXT, updated_at TEXT);");

        // Insert user without first/last name to force fallback to family_member self
        $this->pdo->exec("INSERT INTO users (id, email, first_name, last_name, created_at, updated_at) VALUES (200001, 'no.name@example.com', '', '', '2025-11-01', '2025-11-01');");
        // Insert a self family member with name
        $this->pdo->exec("INSERT INTO family_members (id, user_id, first_name, last_name, relationship) VALUES (400001, 200001, 'Fallback', 'User', 'self');");

        $this->userModel = new \App\Models\User($this->pdo);
    }

    public function testFindUsesFamilySelfWhenNameMissing()
    {
        $user = $this->userModel->find(200001);
        $this->assertIsArray($user);
        $this->assertEquals('Fallback User', $user['name']);
    }
}
