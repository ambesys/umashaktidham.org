<?php
/**
 * Seed a test user for Selenium end-to-end tests.
 * Usage: php scripts/seed_test_user.php
 * 
 * This script inserts a test user with known credentials into the database.
 * Email: testuser@example.com
 * Password: password123
 * Role: user
 */

echo "Starting test user seeding...\n";

try {
    // Use the same bootstrap + config as the app
    require_once __DIR__ . '/../bootstrap.php';
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/database.php';

    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception('PDO connection not initialized');
    }

    echo "✅ Connected to database.\n";

    // Ensure roles table has user role
    $checkRole = $pdo->prepare("SELECT id FROM roles WHERE name = 'user'");
    $checkRole->execute();
    $userRole = $checkRole->fetch(PDO::FETCH_ASSOC);
    $userRoleId = $userRole['id'] ?? 11; // Default fallback

    // Check if test user already exists
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->execute(['testuser@example.com']);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        echo "✅ Test user already exists (ID: {$existing['id']}, email: testuser@example.com)\n";
    } else {
        // Insert test user
        $insertStmt = $pdo->prepare(
            "INSERT INTO users (username, email, password, first_name, last_name, role_id, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );

        $username = 'testuser';
        $email = 'testuser@example.com';
        $passwordHash = password_hash('password123', PASSWORD_BCRYPT);
        $firstName = 'Test';
        $lastName = 'User';
        $roleId = $userRoleId;

        $insertStmt->execute([$username, $email, $passwordHash, $firstName, $lastName, $roleId]);
        $newUserId = $pdo->lastInsertId();

        echo "✅ Test user created successfully!\n";
        echo "   ID: $newUserId\n";
        echo "   Username: $username\n";
        echo "   Email: $email\n";
        echo "   Password: password123\n";
        echo "   Name: $firstName $lastName\n";
        echo "   Role ID: $roleId\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
