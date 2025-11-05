<?php
// Secure admin seed script
// Usage: ADMIN_PASSWORD=strongpass php database/seeds/seed_admin.php

require_once __DIR__ . '/../../config/database.php';

// $pdo is created in config/database.php
if (!isset($pdo) || !$pdo instanceof PDO) {
    echo "Database connection not available.\n";
    exit(1);
}

require_once __DIR__ . '/../../src/Services/RoleService.php';

use App\Services\RoleService;

$roleSvc = new RoleService($pdo);

// ensure roles
$roles = [
    ['name' => 'admin', 'description' => 'Administrator with full access'],
    ['name' => 'moderator', 'description' => 'Moderator with permissions to manage content'],
    ['name' => 'committee_member', 'description' => 'Committee member with committee access'],
    ['name' => 'sponsor', 'description' => 'Sponsor with donor-level access'],
    ['name' => 'user', 'description' => 'Regular member user'],
];
$roleSvc->ensureRoles($roles);

// set default levels (gap-based)
$roleSvc->setRoleLevels([
    'user' => 11,
    'sponsor' => 21,
    'committee_member' => 31,
    'moderator' => 41,
    'admin' => 51,
]);

// admin credentials
$adminEmail = getenv('ADMIN_EMAIL') ?: 'admin@umashaktidham.org';
$adminPassword = getenv('ADMIN_PASSWORD');

if (!$adminPassword) {
    // generate a secure random password and show it once
    $adminPassword = bin2hex(random_bytes(6)); // 12 hex chars
    echo "Generated admin password: $adminPassword\n";
    echo "You may set ADMIN_PASSWORD env variable to override this.\n";
}

// check if admin user exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
$stmt->bindParam(':email', $adminEmail);
$stmt->execute();
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

$roleRow = $pdo->prepare("SELECT id FROM roles WHERE name = 'admin' LIMIT 1");
$roleRow->execute();
$role = $roleRow->fetch(PDO::FETCH_ASSOC);
$adminRoleId = $role['id'] ?? null;

if (!$adminRoleId) {
    echo "Admin role not found. Ensure roles table exists and migrations have been run.\n";
    exit(1);
}

if ($existing) {
    echo "Admin user already exists (email = $adminEmail). Updating role to admin.\n";
    $upd = $pdo->prepare("UPDATE users SET role_id = :role_id WHERE id = :id");
    $upd->bindParam(':role_id', $adminRoleId, PDO::PARAM_INT);
    $upd->bindParam(':id', $existing['id'], PDO::PARAM_INT);
    $upd->execute();
    echo "Done.\n";
    exit(0);
}

// create admin user
$hashed = password_hash($adminPassword, PASSWORD_DEFAULT);
$name = 'Site Admin';
$username = 'admin';

$ins = $pdo->prepare("INSERT INTO users (username, name, email, password, role_id) VALUES (:username, :name, :email, :password, :role_id)");
$ins->bindParam(':username', $username);
$ins->bindParam(':name', $name);
$ins->bindParam(':email', $adminEmail);
$ins->bindParam(':password', $hashed);
$ins->bindParam(':role_id', $adminRoleId, PDO::PARAM_INT);

if ($ins->execute()) {
    echo "Admin user created: $adminEmail\n";
    echo "Password: set via ADMIN_PASSWORD env or the generated password shown above.\n";
    echo "Please change the password after first login.\n";
} else {
    echo "Failed to create admin user.\n";
}
