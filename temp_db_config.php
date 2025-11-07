<?php
/**
 * Temporary database config override for production
 * Uncomment and update the values below with your actual database credentials
 */

// TEMPORARY OVERRIDE - Update these values with your actual database credentials
putenv('DB_HOST=localhost');
putenv('DB_NAME=your_actual_database_name');
putenv('DB_USER=your_actual_db_username');
putenv('DB_PASS=your_actual_db_password');

// Now load the normal database config
require_once __DIR__ . '/config/database.php';

echo "Database config loaded with temporary credentials\n";
echo "DB_HOST: " . getenv('DB_HOST') . "\n";
echo "DB_NAME: " . getenv('DB_NAME') . "\n";
echo "DB_USER: " . getenv('DB_USER') . "\n";
echo "DB_PASS: " . (getenv('DB_PASS') ? 'SET' : 'NOT SET') . "\n\n";

// Test connection
if ($pdo) {
    echo "✅ Database connection successful!\n";
} else {
    echo "❌ Database connection failed\n";
}
?>