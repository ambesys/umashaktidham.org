<?php
// Main Layout Template
// This layout handles common setup and renders content within header/footer

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
        error_log("Database config not found at either path: $databasePath or $fallbackPath");
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

// Check access session
$accessUntil = $_SESSION['access_granted_until'] ?? 0;
if (!$isAllowed && (empty($accessUntil) || time() > intval($accessUntil))) {
    // remove expired flag if present
    if (!empty($accessUntil) && time() > intval($accessUntil)) {
        unset($_SESSION['access_granted_until']);
    }
    // redirect to access page and include original path as next
    $next = $_SERVER['REQUEST_URI'] ?? '/';
    header('Location: /access?next=' . urlencode($next));
    exit();
}

// Determine if this is the access page so we can hide header/footer
$isAccessPage = (strpos($currentPath, '/access') === 0);

if (!$isAccessPage) {
    include __DIR__ . '/header.php';
}
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

<?php if (!$isAccessPage) { include __DIR__ . '/footer.php'; } ?>

<?php if (isset($pageScripts) && !empty($pageScripts)): ?>
    <!-- Page-specific JavaScript -->
    <?php foreach ($pageScripts as $script): ?>
        <script src="/assets/js/<?php echo htmlspecialchars($script); ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>