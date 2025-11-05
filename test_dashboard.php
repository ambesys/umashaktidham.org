<?php
// Test script to simulate authentication and test dashboard

// Set server variables before starting session
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/dashboard';
$_SERVER['HTTP_HOST'] = 'localhost:8000';
$_SERVER['REQUEST_SCHEME'] = 'http';

session_start();

// Simulate user authentication
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';

// Create an in-memory PDO for testing and expose it as $pdo/global
$testPdo = new PDO('sqlite::memory:');
$testPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create minimal schema needed by dashboard
$testPdo->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT, role_id INTEGER);");
$testPdo->exec("CREATE TABLE families (id INTEGER PRIMARY KEY AUTOINCREMENT, family_name TEXT, created_by_user_id INTEGER);");
$testPdo->exec("CREATE TABLE family_members (id INTEGER PRIMARY KEY AUTOINCREMENT, family_id INTEGER, user_id INTEGER, first_name TEXT, last_name TEXT, birth_year INTEGER, gender TEXT, email TEXT, phone_e164 TEXT, relationship TEXT, relationship_other TEXT, occupation TEXT, business_info TEXT, created_at TEXT, updated_at TEXT);");

// Insert a test user (id = 1)
$stmt = $testPdo->prepare('INSERT INTO users (name, email, role_id) VALUES (:name, :email, :role)');
$stmt->execute([':name' => 'Test User', ':email' => 'test@example.com', ':role' => 51]);

// Expose as global $pdo used by config and models
$GLOBALS['pdo'] = $testPdo;

// Include the application
require_once __DIR__ . '/index.php';

// The app should handle this
?>