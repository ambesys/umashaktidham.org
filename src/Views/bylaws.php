<?php
// Bylaws page removed. Keep the file as a safe 410 response in case it is rendered directly.
http_response_code(410);
if (headers_sent() === false) {
    header('Content-Type: text/html; charset=utf-8');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Bylaws Removed</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial; padding:40px;color:#333}</style>
</head>
<body>
    <main class="container">
        <h1>Bylaws &amp; Constitution — Removed</h1>
        <p>The Bylaws page has been removed from this website. If you need a copy of the organization's bylaws or constitution, please <a href="/contact">contact us</a> and we'll provide the documents.</p>
        <p><a href="/">Return to Home</a></p>
    </main>
</body>
</html>