<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../vendor/autoload.php';

class FamilyMemberTest extends TestCase
{
    private $pdo;
    private $familyModel;

    public function setUp(): void
    {
    // Use MySQL for fast, isolated tests
    $this->pdo = new PDO('mysql:host=localhost;dbname=u103964107_uma', 'root', 'root');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create schema
    $this->pdo->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT, first_name TEXT, last_name TEXT, password TEXT, role_id INTEGER, street_address TEXT, address_line_2 TEXT, zipcode TEXT, city TEXT, state TEXT, country TEXT);");
        $this->pdo->exec("CREATE TABLE family_members (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, first_name TEXT, last_name TEXT, birth_year INTEGER, gender TEXT, email TEXT, phone_e164 TEXT, relationship TEXT, relationship_other TEXT, occupation TEXT, business_info TEXT, village TEXT, mosal TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP);");

        // Insert user and family rows
        $this->pdo->exec("INSERT INTO users (id, email, first_name, last_name) VALUES (100001, 'patelsarthakr@gmail.com', 'Sarthak', 'Patel');");
        $this->pdo->exec("INSERT INTO family_members (id, user_id, first_name, last_name, relationship) VALUES (300001, 100001, 'Sarthak', 'Patel', 'self');");
        $this->pdo->exec("INSERT INTO family_members (id, user_id, first_name, last_name, relationship) VALUES (300002, 100001, 'Dixita', 'Patel', 'spouse');");
        $this->pdo->exec("INSERT INTO family_members (id, user_id, first_name, last_name, relationship) VALUES (300003, 100001, 'Sarojben', 'Patel', 'mother');");

        $this->familyModel = new \App\Models\FamilyMember($this->pdo);
    }

    public function testListByUserIdExcludesSelf()
    {
        $all = $this->familyModel->listByUserId(100001);
        $this->assertCount(3, $all);

        $filtered = $this->familyModel->listByUserId(100001, true);
        // Should exclude the 'self' record
        $this->assertCount(2, $filtered);
        $relationships = array_column($filtered, 'relationship');
        $this->assertNotContains('self', $relationships);
    }
}
