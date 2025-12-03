<?php

use App\Services\LoggerService;

// Main Layout Template
// This layout handles common setup and renders content within header/footer

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug session information
if (isset($_GET['debug_session'])) {
    LoggerService::debug("Session Debug - Status: " . session_status() . ", ID: " . session_id() . ", Save Path: " . session_save_path());
    // Avoid raw print_r to stdout; use JSON for structured logs
    LoggerService::debug("Session Data: " . json_encode($_SESSION));
}

// Include necessary files using absolute path from project root
$databasePath = __DIR__ . '/../../../config/database.php';
if (file_exists($databasePath)) {
    require_once $databasePath;
} else {
    // Fallback for different directory structures
    $fallbackPath = __DIR__ . '/../../config/database.php';
    if (file_exists($fallbackPath)) {
        require_once $fallbackPath;
    } else {
        LoggerService::error("Database config not found at either path: $databasePath or $fallbackPath");
    }
}

// Check if specific middleware requirements are set
if (isset($middleware) && is_array($middleware)) {
    foreach ($middleware as $requirement) {
        switch ($requirement) {
            case 'admin':
                if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
                    header('Location: /auth/login');
                    exit();
                }
                break;
            case 'moderator':
                if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'moderator'])) {
                    header('Location: /auth/login');
                    exit();
                }
                break;
            case 'authenticated':
                if (!isset($_SESSION['user_id'])) {
                    header('Location: /auth/login');
                    exit();
                }
                break;
        }
    }
}

// Include header
// Access gate: if access not granted or expired, redirect to /access (preserve next param)
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Allow the access page itself and auth routes to load without gate
$allowPaths = ['/access', '/auth/login', '/auth/register', '/auth/logout'];
$isAllowed = false;
foreach ($allowPaths as $p) {
    if (strpos($currentPath, $p) === 0) {
        $isAllowed = true;
        break;
    }
}

// Access gate with inactivity timeout (10 minutes)
// Rules:
// - Require `access_granted` to be true to access any page except allowPaths
// - Track `last_activity_ts`; expire after 10 minutes of inactivity
// - Refresh `last_activity_ts` on each allowed request
// - Clear access flags when expired
if (!$isAllowed) {
    $accessGranted = isset($_SESSION['access_granted']) && $_SESSION['access_granted'] === true;
    $lastActivity = isset($_SESSION['last_activity_ts']) ? intval($_SESSION['last_activity_ts']) : 0;
    $now = time();
    $inactiveLimitSeconds = 10 * 60; // 10 minutes

    $isInactive = ($lastActivity === 0) || (($now - $lastActivity) > $inactiveLimitSeconds);

    if (!$accessGranted || $isInactive) {
        // Clear stale flags if present
        unset($_SESSION['access_granted']);
        unset($_SESSION['last_activity_ts']);
        // Preserve original path for redirect after successful access
        $next = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: /access?next=' . urlencode($next));
        exit();
    }

    // Access is granted and session active; refresh activity timestamp
    $_SESSION['last_activity_ts'] = $now;
}

// Always include header so styles/assets load uniformly (access page needs CSS too)

    include __DIR__ . '/header.php';

?>
<div class="main-container">
    <?php
    // Render the content without automatic container wrapper
    // Each view can decide its own container strategy
    if (isset($content)) {
        echo $content;
    } elseif (isset($contentFile)) {
        include $contentFile;
    }
    ?>
</div>


<?php
if (!$isAllowed) {
    include __DIR__ . '/footer.php';
} ?>

<?php if (isset($pageScripts) && !empty($pageScripts)): ?>
    <!-- Page-specific JavaScript -->
    <?php foreach ($pageScripts as $script): ?>
        <script src="/assets/js/<?php echo htmlspecialchars($script); ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>