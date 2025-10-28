<?php
$host = 'localhost';
$dbname = 'u103964107_uma';
$username = 'u103964107_uma';
$password = 'Cn?o4zw:sT!0';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>