<?php

namespace App\Services;

use Exception;
use SplFileObject;

/**
 * LoggerService - Centralized logging service
 * Provides structured logging with different levels and context tracking
 */
class LoggerService
{
    const LEVEL_DEBUG = 'DEBUG';
    const LEVEL_INFO = 'INFO';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_CRITICAL = 'CRITICAL';

    private static $logFile;
    private static $minLevel;
    private static $timezone;

    /**
     * Initialize logger
     */
    public static function init($config = [])
    {
        self::$logFile = $config['file'] ?? __DIR__ . '/../../logs/app.log'; // Ensure default path is relative to project root
        self::$minLevel = $config['min_level'] ?? self::LEVEL_DEBUG;
        self::$timezone = $config['timezone'] ?? 'UTC';

        // Ensure log directory exists
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            if (!mkdir($logDir, 0755, true) && !is_dir($logDir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $logDir));
            }
        }
    }

    /**
     * Create a logger instance
     */
    public static function create()
    {
        return new self();
    }

    /**
     * Set minimum log level
     */
    public static function setMinLevel($level)
    {
        self::$minLevel = $level;
    }

    /**
     * Check if level should be logged
     */
    private static function shouldLog($level)
    {
        $levels = [
            self::LEVEL_DEBUG => 1,
            self::LEVEL_INFO => 2,
            self::LEVEL_WARNING => 3,
            self::LEVEL_ERROR => 4,
            self::LEVEL_CRITICAL => 5
        ];

        return $levels[$level] >= $levels[self::$minLevel];
    }

    /**
     * Format log message
     */
    private static function formatMessage($level, $message, $context = [])
    {
        $timestamp = date('Y-m-d H:i:s T');
        $contextStr = empty($context) ? '' : ' | ' . json_encode($context);

        return "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;
    }

    /**
     * Write to log file
     */
    private static function write($level, $message, $context = [])
    {
        if (!self::shouldLog($level)) {
            return;
        }

        $logMessage = self::formatMessage($level, $message, $context);

        // Rotate log file if too large
        if (file_exists(self::$logFile) && filesize(self::$logFile) > 10 * 1024 * 1024) { // 10MB
            $backupFile = self::$logFile . '.' . date('Y-m-d_H-i-s');
            rename(self::$logFile, $backupFile);
        }

        file_put_contents(self::$logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    /**
     * Log debug message
     */
    public static function debug($message, $context = [])
    {
        self::write(self::LEVEL_DEBUG, $message, $context);
    }

    /**
     * Instance method for debug
     */
    public function logDebug($message, $context = [])
    {
        self::debug($message, $context);
    }

    /**
     * Log info message
     */
    public static function info($message, $context = [])
    {
        self::write(self::LEVEL_INFO, $message, $context);
    }

    /**
     * Instance method for info
     */
    public function logInfo($message, $context = [])
    {
        self::info($message, $context);
    }

    /**
     * Log warning message
     */
    public static function warning($message, $context = [])
    {
        self::write(self::LEVEL_WARNING, $message, $context);
    }

    /**
     * Instance method for warning
     */
    public function logWarning($message, $context = [])
    {
        self::warning($message, $context);
    }

    /**
     * Log error message
     */
    public static function error($message, $context = [])
    {
        self::write(self::LEVEL_ERROR, $message, $context);
    }

    /**
     * Instance method for error
     */
    public function logError($message, $context = [])
    {
        self::error($message, $context);
    }

    /**
     * Log critical message
     */
    public static function critical($message, $context = [])
    {
        self::write(self::LEVEL_CRITICAL, $message, $context);
    }

    /**
     * Instance method for critical
     */
    public function logCritical($message, $context = [])
    {
        self::critical($message, $context);
    }

    /**
     * Log with context information
     */
    public static function logWithContext($level, $message, $userId = null, $action = null, $resource = null, $extra = [])
    {
        $context = array_filter([
            'user_id' => $userId,
            'action' => $action,
            'resource' => $resource,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'session_id' => session_id(),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
            'method' => $_SERVER['REQUEST_METHOD'] ?? null
        ]);

        $context = array_merge($context, $extra);

        self::write($level, $message, $context);
    }

    /**
     * Log user action
     */
    public static function logUserAction($userId, $action, $resource = null, $details = [])
    {
        $message = "User action: {$action}";
        if ($resource) {
            $message .= " on {$resource}";
        }

        self::logWithContext(self::LEVEL_INFO, $message, $userId, $action, $resource, $details);
    }

    /**
     * Log security event
     */
    public static function logSecurityEvent($event, $userId = null, $details = [])
    {
        $message = "Security event: {$event}";
        self::logWithContext(self::LEVEL_WARNING, $message, $userId, 'security', null, $details);
    }

    /**
     * Log API request
     */
    public static function logApiRequest($method, $endpoint, $statusCode, $duration = null, $userId = null)
    {
        $message = "API {$method} {$endpoint} - {$statusCode}";
        if ($duration) {
            $message .= " ({$duration}ms)";
        }

        $level = $statusCode >= 500 ? self::LEVEL_ERROR :
                ($statusCode >= 400 ? self::LEVEL_WARNING : self::LEVEL_INFO);

        self::logWithContext($level, $message, $userId, 'api_request', $endpoint, [
            'status_code' => $statusCode,
            'duration' => $duration
        ]);
    }

    /**
     * Log database query (for debugging)
     */
    public static function logQuery($query, $params = [], $executionTime = null)
    {
        if (!defined('APP_DEBUG') || !APP_DEBUG) {
            return; // Only log queries in debug mode
        }

        $message = "DB Query: {$query}";
        if ($executionTime) {
            $message .= " ({$executionTime}ms)";
        }

        self::write(self::LEVEL_DEBUG, $message, [
            'params' => $params,
            'execution_time' => $executionTime
        ]);
    }

    /**
     * Log exception
     */
    public static function logException(Exception $e, $context = [])
    {
        $message = "Exception: " . $e->getMessage();
        $context = array_merge($context, [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        self::write(self::LEVEL_ERROR, $message, $context);
    }

    /**
     * Get log file path
     */
    public static function getLogFile()
    {
        return self::$logFile;
    }

    /**
     * Get recent logs
     */
    public static function getRecentLogs($lines = 100)
    {
        if (!file_exists(self::$logFile)) {
            return [];
        }

        $logs = [];
        $file = new SplFileObject(self::$logFile, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();

        $startLine = max(0, $totalLines - $lines);
        $file->seek($startLine);

        while (!$file->eof()) {
            $line = trim($file->fgets());
            if (!empty($line)) {
                $logs[] = $line;
            }
        }

        return array_reverse($logs);
    }

    /**
     * Clear log file
     */
    public static function clearLogs()
    {
        if (file_exists(self::$logFile)) {
            file_put_contents(self::$logFile, '');
        }
    }
}