<?php
use App\Services\LoggerService;

// Respect an existing PDO instance (useful for tests and special environments)
if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
    $pdo = $GLOBALS['pdo'];
} else {
    // Try MySQL first, fall back to SQLite for development/testing
    $host = getenv('DB_HOST') ?: 'localhost';
    $dbname = getenv('DB_NAME') ?: 'u103964107_uma';
    $username = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASS') ?: 'root';

    // Determine whether to use MySQL or SQLite based on environment variable
    // Default is MySQL; set USE_MYSQL=false to force SQLite (preferred in tests)
    $useMysqlEnv = getenv('USE_MYSQL');
    $useMysql = is_null($useMysqlEnv) ? true : (strtolower($useMysqlEnv) === 'true' || $useMysqlEnv === '1');
    
    if ($useMysql) {
        // Use MySQL if explicitly requested
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            LoggerService::info("Connected to MySQL database: $host/$dbname");
        } catch (PDOException $e) {
            LoggerService::error("Database connection failed: " . $e->getMessage());
            throw $e;
        }
    } else {
        // Use SQLite in-memory database for tests or dev when explicitly requested
        try {
            // Use file-backed SQLite if DB_NAME points to a file name, otherwise in-memory
            $sqlPath = getenv('DB_PATH') ?: ':memory:';
            $dsn = $sqlPath === ':memory:' ? 'sqlite::memory:' : "sqlite:" . $sqlPath;
            $pdo = new PDO($dsn);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            LoggerService::info("Connected to SQLite database: $sqlPath");
        } catch (PDOException $e) {
            LoggerService::error('SQLite connection failed: ' . $e->getMessage());
            throw $e;
        }
    }
}

// Make PDO globally available for all controllers and services
$GLOBALS['pdo'] = $pdo;