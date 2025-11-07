<?php
/**
 * Setup SQLite database for local development
 */

echo "Starting SQLite database setup...\n";

require_once __DIR__ . '/../config/database.php';

if (!$pdo) {
    die("Database connection failed\n");
}

echo "Connected to SQLite database\n";

// Read and execute the schema
$schemaPath = __DIR__ . '/../database/migrations/2025_01_15_create_initial_schema.sql';
if (!file_exists($schemaPath)) {
    die("Schema file not found: $schemaPath\n");
}

$schema = file_get_contents($schemaPath);

// Split into individual statements (basic approach)
$statements = array_filter(array_map('trim', explode(';', $schema)));

// Convert MySQL syntax to SQLite where needed
foreach ($statements as $statement) {
    if (empty($statement) || strpos($statement, '--') === 0 || strpos(strtoupper($statement), 'SET') === 0) {
        continue;
    }

    // Skip MySQL-specific statements
    if (strpos(strtoupper($statement), 'ENGINE=') !== false ||
        strpos(strtoupper($statement), 'DEFAULT CHARSET=') !== false ||
        strpos(strtoupper($statement), 'COLLATE=') !== false ||
        strpos(strtoupper($statement), 'AUTO_INCREMENT') !== false) {
        continue;
    }

    // Convert AUTO_INCREMENT to AUTOINCREMENT
    $statement = str_replace('AUTO_INCREMENT', 'AUTOINCREMENT', $statement);

    // Convert ENUM to TEXT with CHECK constraint (simplified)
    if (strpos(strtoupper($statement), 'ENUM(') !== false) {
        // For simplicity, convert ENUM to TEXT
        $statement = preg_replace('/ENUM\([^)]+\)/', 'TEXT', $statement);
    }

    try {
        $pdo->exec($statement);
        echo "Executed: " . substr($statement, 0, 50) . "...\n";
    } catch (Exception $e) {
        echo "Error executing statement: " . $e->getMessage() . "\n";
        echo "Statement: $statement\n";
    }
}

echo "Database setup completed\n";

// Now seed the roles
$seedPath = __DIR__ . '/../database/seeds/roles_seed.sql';
if (file_exists($seedPath)) {
    $seedSql = file_get_contents($seedPath);
    try {
        $pdo->exec($seedSql);
        echo "Roles seeded successfully\n";
    } catch (Exception $e) {
        echo "Error seeding roles: " . $e->getMessage() . "\n";
    }
}

// Create test admin user
try {
    // Check if admin role exists
    $roleStmt = $pdo->query("SELECT id FROM roles WHERE name = 'admin' LIMIT 1");
    $adminRole = $roleStmt->fetch(PDO::FETCH_ASSOC);

    if ($adminRole) {
        // Hash password (simple hash for testing)
        $password = password_hash('password123', PASSWORD_DEFAULT);

        // Insert test admin user
        $userStmt = $pdo->prepare("INSERT OR IGNORE INTO users (username, email, password, name, role_id, is_active, auth_type) VALUES (?, ?, ?, ?, ?, 1, 'local')");
        $userStmt->execute(['testadmin', 'testadmin@example.com', $password, 'Test Admin', $adminRole['id']]);

        echo "Test admin user created: testadmin@example.com / password123\n";
    } else {
        echo "Admin role not found, cannot create test user\n";
    }
} catch (Exception $e) {
    echo "Error creating test user: " . $e->getMessage() . "\n";
}

echo "Setup complete!\n";
?>