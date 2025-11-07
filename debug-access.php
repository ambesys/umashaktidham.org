<!-- <?php
// Debug page to help troubleshoot 403/permission issues when deployed.
// Deploy this to your host and open /debug-access.php in a browser.
header('Content-Type: text/plain');

echo "DEBUG ACCESS PAGE - Uma Shakti Dham\n";
echo "===============================\n\n";

// Basic PHP info
echo "PHP Version: " . PHP_VERSION . "\n";

// Document root and script locations
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'N/A') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n\n";

// Index file check
$indexPath = __DIR__ . '/index.php';
echo "index.php exists: " . (file_exists($indexPath) ? 'yes' : 'no') . "\n";
if (file_exists($indexPath)) {
    $stat = stat($indexPath);
    echo "index.php perms: " . substr(sprintf('%o', $stat['mode']), -4) . "\n";
    echo "index.php owner uid: " . $stat['uid'] . " gid: " . $stat['gid'] . "\n";
    echo "index.php size: " . filesize($indexPath) . " bytes\n";
}

echo "\npublic/ directory listing (ls -la):\n";
$files = @scandir(__DIR__);
if ($files === false) {
    echo "Unable to read public directory - permission denied\n";
} else {
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        $p = __DIR__ . '/' . $f;
        $perms = @fileperms($p);
        $permstr = $perms ? substr(sprintf('%o', $perms), -4) : '----';
        echo sprintf("%s  %s\n", $permstr, $f);
    }
}

// Try to read .htaccess
$ht = __DIR__ . '/.htaccess';
echo "\n.htaccess present: " . (file_exists($ht) ? 'yes' : 'no') . "\n";
if (file_exists($ht)) {
    echo "--- .htaccess (first 300 chars) ---\n";
    $txt = @file_get_contents($ht);
    echo $txt === false ? "(cannot read)\n" : substr($txt,0,300) . "\n";
}

// Show quick environment info
echo "\nServer Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'N/A') . "\n";

// Example of a writable test file
$testFile = __DIR__ . '/.debug-writable';
$w = @file_put_contents($testFile, "test " . time());
if ($w === false) {
    echo "\nCannot write test file (no write permission).\n";
} else {
    echo "\nWrote test file: .debug-writable (remove it after).\n";
    @unlink($testFile);
}

echo "\nEnd of debug output.\n";

?> -->
