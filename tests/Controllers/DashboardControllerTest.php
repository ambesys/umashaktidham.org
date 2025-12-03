<?php
use PHPUnit\Framework\TestCase;

/**
 * Simple test for DashboardController::getDashboardData
 * Ensures that family members with relationship 'self' are filtered out
 */
class DashboardControllerTest extends TestCase
{
    protected $pdo;
    protected function setUp(): void
    {
    // Use MySQL for controller tests
    $this->pdo = new PDO('mysql:host=localhost;dbname=u103964107_uma', 'root', 'root');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create minimal tables (columns required by models)
        $this->pdo->exec("CREATE TABLE users (
            id INTEGER PRIMARY KEY,
            email TEXT,
            password TEXT,
            role_id INTEGER,
            first_name TEXT,
            last_name TEXT,
            street_address TEXT,
            address_line_2 TEXT,
            zipcode TEXT,
            city TEXT,
            state TEXT,
            country TEXT,
            created_at TEXT,
            updated_at TEXT
        );");
    $this->pdo->exec("CREATE TABLE family_members (id INTEGER PRIMARY KEY, user_id INTEGER, first_name TEXT, last_name TEXT, relationship TEXT, birth_year INTEGER, gender TEXT, phone_e164 TEXT, email TEXT, occupation TEXT, business_info TEXT, village TEXT, mosal TEXT, created_at TEXT);");

    // Insert a user (include password column expected by User model)
    $this->pdo->exec("INSERT INTO users (id, first_name, last_name, email, password) VALUES (1, 'Test', 'User', 'test@example.com', '');");

        // Insert two family rows: one self and one spouse
    $this->pdo->exec("INSERT INTO family_members (id, user_id, first_name, last_name, relationship, birth_year, gender, created_at) VALUES (1, 1, 'Test', 'User', 'self', 1980, 'male', datetime('now'));");
    $this->pdo->exec("INSERT INTO family_members (id, user_id, first_name, last_name, relationship, birth_year, gender, created_at) VALUES (2, 1, 'Jane', 'User', 'spouse', 1985, 'female', datetime('now'));");

        $GLOBALS['pdo'] = $this->pdo;
        // Simulate authenticated session
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $_SESSION['user_id'] = 1;
    }

    public function testGetDashboardDataFiltersSelf()
    {
        require_once __DIR__ . '/../../src/Controllers/DashboardController.php';

        // Load controller and instantiate without running constructor to avoid session side-effects
        require_once __DIR__ . '/../../src/Controllers/DashboardController.php';
        $ref = new ReflectionClass(App\Controllers\DashboardController::class);
        $controller = $ref->newInstanceWithoutConstructor();
        // inject PDO and a simple sessionService mock via reflection to access protected properties
        $pdoProp = $ref->getProperty('pdo');
        $pdoProp->setAccessible(true);
        $pdoProp->setValue($controller, $this->pdo);

        $sessProp = $ref->getProperty('sessionService');
        $sessProp->setAccessible(true);
        $sessProp->setValue($controller, new class {
            public function isAuthenticated() { return true; }
            public function getCurrentUserId() { return 1; }
        });
        
        $result = $controller->getDashboardData();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('family', $result);
        $this->assertCount(1, $result['family']);
        $this->assertEquals('spouse', $result['family'][0]['relationship']);
    }
}
