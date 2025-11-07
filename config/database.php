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
        // For development, try SQLite fallback if MySQL fails
        // But only if we're not connecting to a specific production-like database
        if ((getenv('APP_ENV') === 'development' || getenv('APP_ENV') === 'local') && $dbname !== 'u103964107_uma') {
            try {
                $sqlitePath = __DIR__ . '/../umashaktidham.db';
                $pdo = new PDO("sqlite:$sqlitePath");
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                LoggerService::info("Using SQLite database for development: $sqlitePath");
            } catch (PDOException $sqliteError) {
                $pdo = null; // Allow app to run without database
                LoggerService::error("Database connection failed (SQLite fallback): " . $sqliteError->getMessage());
            }
        } else {
            // For production or when connecting to specific databases, don't fall back to SQLite
            LoggerService::error("Database connection failed: " . $e->getMessage());
            throw $e; // Re-throw the exception instead of falling back
        }
    }
}

// Make PDO globally available for all controllers and services
$GLOBALS['pdo'] = $pdo;