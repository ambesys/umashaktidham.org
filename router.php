<?php
// Router for PHP Development Server
// This file is used when running: php -S localhost:8000 router.php

$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

// DEBUG: Log requests for .js files
if (strpos($uri, '.js') !== false) {
    error_log("Router: Handling JS request: $uri");
}

// Check if file exists in root
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    if (is_file(__DIR__ . $uri)) {
        return false; // Let the dev server handle static files from root
    }
}

// Check if file exists in public directory
$publicPath = __DIR__ . "/public" . $uri;
if (strpos($uri, '.js') !== false) {
    error_log("Router: Checking public path: $publicPath");
    error_log("Router: File exists? " . (file_exists($publicPath) ? 'YES' : 'NO'));
}

if (file_exists($publicPath)) {
    if (is_file($publicPath)) {
        // Serve the file with proper content type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $contentType = finfo_file($finfo, $publicPath);
        finfo_close($finfo);
        
        if (strpos($uri, '.js') !== false) {
            error_log("Router: Serving file with content-type: $contentType");
        }
        
        header('Content-Type: ' . $contentType);
        readfile($publicPath);
        return true;
    }
}

// Otherwise, route to index.php
require __DIR__ . "/index.php";
?>
