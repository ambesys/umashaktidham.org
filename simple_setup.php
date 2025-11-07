<?php
/**
 * Simple SQLite setup script
 */

echo "Starting simple SQLite setup...\n";

try {
    $sqlitePath = __DIR__ . '/umashaktidham.db';
    echo "SQLite path: $sqlitePath\n";

    $pdo = new PDO("sqlite:$sqlitePath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to SQLite database\n";

    // Manually create essential tables for testing
    echo "Creating essential tables...\n";

    $tables = [
        "CREATE TABLE IF NOT EXISTS roles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            description TEXT,
            level INTEGER DEFAULT 11,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",

        "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            name TEXT,
            first_name TEXT,
            last_name TEXT,
            phone_e164 TEXT,
            role_id INTEGER,
            email_verified_at DATETIME,
            remember_token TEXT,
            last_login_at DATETIME,
            is_active INTEGER DEFAULT 1,
            auth_type TEXT DEFAULT 'local',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (role_id) REFERENCES roles(id)
        )",

        "CREATE TABLE IF NOT EXISTS user_providers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            provider TEXT NOT NULL,
            provider_user_id TEXT,
            access_token TEXT,
            refresh_token TEXT,
            expires_at DATETIME,
            profile TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            UNIQUE(provider, provider_user_id)
        )"
    ];

    foreach ($tables as $tableSql) {
        try {
            $pdo->exec($tableSql);
            echo "Created table\n";
        } catch (Exception $e) {
            echo "Error creating table: " . $e->getMessage() . "\n";
        }
    }

    // Seed roles
    echo "Seeding roles...\n";
    $roleInserts = [
        "INSERT OR IGNORE INTO roles (name, description, level) VALUES ('user', 'Regular member user', 11)",
        "INSERT OR IGNORE INTO roles (name, description, level) VALUES ('sponsor', 'Sponsor with donor-level access', 21)",
        "INSERT OR IGNORE INTO roles (name, description, level) VALUES ('committee_member', 'Committee member with committee access', 31)",
        "INSERT OR IGNORE INTO roles (name, description, level) VALUES ('moderator', 'Moderator with permissions to manage content', 41)",
        "INSERT OR IGNORE INTO roles (name, description, level) VALUES ('admin', 'Administrator with full access', 51)"
    ];

    foreach ($roleInserts as $insert) {
        try {
            $pdo->exec($insert);
            echo "Inserted role\n";
        } catch (Exception $e) {
            echo "Error inserting role: " . $e->getMessage() . "\n";
        }
    }

    // Create test admin user
    try {
        // Check if admin role exists
        $roleStmt = $pdo->query("SELECT id FROM roles WHERE name = 'admin' LIMIT 1");
        $adminRole = $roleStmt->fetch(PDO::FETCH_ASSOC);

        if ($adminRole) {
            echo "Admin role found: ID {$adminRole['id']}\n";

            // Hash password (simple hash for testing)
            $password = password_hash('password123', PASSWORD_DEFAULT);

            // Insert test admin user
            $userStmt = $pdo->prepare("INSERT OR IGNORE INTO users (username, email, password, name, role_id, is_active, auth_type) VALUES (?, ?, ?, ?, ?, 1, 'local')");
            $result = $userStmt->execute(['testadmin', 'testadmin@example.com', $password, 'Test Admin', $adminRole['id']]);

            if ($result) {
                echo "Test admin user created: testadmin@example.com / password123\n";
            } else {
                echo "Failed to create test user\n";
            }
        } else {
            echo "Admin role not found, cannot create test user\n";
        }
    } catch (Exception $e) {
        echo "Error creating test user: " . $e->getMessage() . "\n";
    }

    echo "Setup complete!\n";

} catch (Exception $e) {
    echo "Setup failed: " . $e->getMessage() . "\n";
}
?>