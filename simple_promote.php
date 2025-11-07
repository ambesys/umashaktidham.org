<?php
/**
 * Simple promotion script using the same database as the app
 */

echo "Starting user promotion...\n";

try {
    // Load bootstrap to initialize logging and other services
    require_once __DIR__ . '/bootstrap.php';
    
    // Use the same database config as the app
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/config/database.php';

    if (!$pdo) {
        die("Database connection failed\n");
    }

    echo "Connected to database\n";

    $email = 'testadmin@example.com';

    // Get admin role ID
    $roleStmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'admin' LIMIT 1");
    $roleStmt->execute();
    $adminRole = $roleStmt->fetch(PDO::FETCH_ASSOC);

    if (!$adminRole) {
        die("Admin role not found in database\n");
    }

    echo "Admin role ID: {$adminRole['id']}\n";

    // Find the user
    $userStmt = $pdo->prepare("SELECT id, email, role_id FROM users WHERE email = :email LIMIT 1");
    $userStmt->bindParam(':email', $email);
    $userStmt->execute();
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("User with email $email not found\n");
    }

    echo "Current user data:\n";
    echo "- ID: {$user['id']}\n";
    echo "- Email: {$user['email']}\n";
    echo "- Current role_id: " . ($user['role_id'] ?? 'NULL') . "\n";

    // Update user role to admin
    $updateStmt = $pdo->prepare("UPDATE users SET role_id = :role_id WHERE id = :id");
    $updateStmt->bindParam(':role_id', $adminRole['id'], PDO::PARAM_INT);
    $updateStmt->bindParam(':id', $user['id'], PDO::PARAM_INT);

    if ($updateStmt->execute()) {
        echo "✅ Successfully promoted user {$user['email']} to admin role\n";

        // Verify the update
        $verifyStmt = $pdo->prepare("SELECT u.id, u.email, u.role_id, r.name as role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = :id");
        $verifyStmt->bindParam(':id', $user['id'], PDO::PARAM_INT);
        $verifyStmt->execute();
        $verifiedUser = $verifyStmt->fetch(PDO::FETCH_ASSOC);

        echo "Verification:\n";
        echo "- ID: {$verifiedUser['id']}\n";
        echo "- Email: {$verifiedUser['email']}\n";
        echo "- New role_id: {$verifiedUser['role_id']}\n";
        echo "- Role name: {$verifiedUser['role_name']}\n";
    } else {
        echo "❌ Failed to update user role\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>