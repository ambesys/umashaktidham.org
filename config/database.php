<?php
use App\Services\LoggerService;

// Respect an existing PDO instance (useful for tests and special environments)
if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
    $pdo = $GLOBALS['pdo'];
} else {
    $host = getenv('DB_HOST') ?: 'localhost';
    $dbname = getenv('DB_NAME') ?: 'umashakti_dham';
    $username = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASS') ?: '';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        // For development, don't fail if database is not available
        if (getenv('APP_ENV') === 'development') {
            $pdo = null; // Allow app to run without database
            LoggerService::error("Database connection failed (development mode): " . $e->getMessage());
        } else {
            echo "Connection failed: " . $e->getMessage();
            exit(1);
        }
    }
}