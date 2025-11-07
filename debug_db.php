<?php
/**
 * Debug database connection on production server
 */

echo "=== Database Connection Debug ===\n\n";

// Check environment variables
echo "Environment Variables:\n";
echo "DB_HOST: " . (getenv('DB_HOST') ?: 'NOT SET (using default: localhost)') . "\n";
echo "DB_NAME: " . (getenv('DB_NAME') ?: 'NOT SET (using default: umashakti_dham)') . "\n";
echo "DB_USER: " . (getenv('DB_USER') ?: 'NOT SET (using default: root)') . "\n";
echo "DB_PASS: " . (getenv('DB_PASS') ? 'SET' : 'NOT SET (using default: empty)') . "\n\n";

// Check if .env file exists
echo "Environment Files:\n";
echo ".env exists: " . (file_exists(__DIR__ . '/.env') ? 'YES' : 'NO') . "\n";
echo ".env.example exists: " . (file_exists(__DIR__ . '/.env.example') ? 'YES' : 'NO') . "\n";
echo ".env.prod exists: " . (file_exists(__DIR__ . '/.env.prod') ? 'YES' : 'NO') . "\n\n";

// Try to load .env if it exists
if (file_exists(__DIR__ . '/.env')) {
    echo "Loading .env file...\n";
    $envContent = file_get_contents(__DIR__ . '/.env');
    $lines = explode("\n", $envContent);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && !empty(trim($line)) && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (strpos($key, 'DB_') === 0) {
                putenv("$key=$value");
                echo "Set $key from .env file\n";
            }
        }
    }
    echo "\n";
}

// Test database connection with current settings
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'umashakti_dham';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';

echo "Testing database connection:\n";
echo "Host: $host\n";
echo "Database: $dbname\n";
echo "Username: $username\n";
echo "Password: " . (!empty($password) ? 'SET' : 'EMPTY') . "\n\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection successful!\n";

    // Test if tables exist
    $tables = ['users', 'roles', 'user_providers'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        echo "Table '$table': " . ($exists ? 'EXISTS' : 'NOT FOUND') . "\n";
    }

    // Check user data
    echo "\nChecking user patelsarthakr@gmail.com:\n";
    $stmt = $pdo->prepare("SELECT id, email, role_id FROM users WHERE email = ?");
    $stmt->execute(['patelsarthakr@gmail.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "User found:\n";
        echo "- ID: {$user['id']}\n";
        echo "- Email: {$user['email']}\n";
        echo "- Role ID: " . ($user['role_id'] ?? 'NULL') . "\n";

        // Check role name
        if ($user['role_id']) {
            $roleStmt = $pdo->prepare("SELECT name FROM roles WHERE id = ?");
            $roleStmt->execute([$user['role_id']]);
            $role = $roleStmt->fetch(PDO::FETCH_ASSOC);
            echo "- Role Name: " . ($role['name'] ?? 'UNKNOWN') . "\n";
        }
    } else {
        echo "User not found in database\n";
    }

} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";

    // Try to connect without database name to check if server is accessible
    echo "\nTrying to connect to MySQL server without database...\n";
    try {
        $pdo = new PDO("mysql:host=$host", $username, $password);
        echo "✅ MySQL server connection successful\n";

        // List available databases
        $stmt = $pdo->query("SHOW DATABASES");
        $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Available databases:\n";
        foreach ($databases as $db) {
            echo "- $db\n";
        }

        // Check if target database exists
        if (in_array($dbname, $databases)) {
            echo "\nTarget database '$dbname' exists but connection failed.\n";
            echo "This might be a permissions issue.\n";
        } else {
            echo "\nTarget database '$dbname' does not exist.\n";
        }

    } catch (PDOException $e2) {
        echo "❌ MySQL server connection also failed: " . $e2->getMessage() . "\n";
        echo "\nPossible solutions:\n";
        echo "1. Check if MySQL server is running\n";
        echo "2. Verify database credentials\n";
        echo "3. Check if user has access to the database\n";
        echo "4. Contact hosting provider for correct credentials\n";
    }
}

echo "\n=== Debug Complete ===\n";
?>