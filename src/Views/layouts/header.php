<?php
// Full header layout: includes DOCTYPE, <head> and opening <body>. Use $pageTitle (optional).
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user = $_SESSION['user'] ?? null;
$role = $_SESSION['role'] ?? null;
$pageTitle = $pageTitle ?? 'Uma Shakti Dham';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>

<header class="site-header">
    <div class="container header-inner">
        <div class="logo">
            <a href="/index.php">
                <img src="/assets/images/logo.svg" alt="Uma Shakti Dham Logo" class="site-logo" />
                <span class="site-title">Uma Shakti Dham</span>
            </a>
        </div>

        <button id="navToggle" class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">☰</button>

        <nav id="mainNav" class="main-nav">
            <ul class="nav-left">
                <li><a href="/index.php">Home</a></li>
                <li><a href="/about.php">About</a></li>
                <li><a href="/events.php">Events</a></li>
                <li><a href="/indian-holidays.php">Indian Holidays</a></li>
                <li><a href="/gallery.php">Gallery</a></li>
                <li><a href="/membership.php">Membership</a></li>
                <li><a href="/donate.php">Donate</a></li>
                <li><a href="/contact.php">Contact</a></li>
                <?php if ($user): ?>
                    <li><a href="/dashboard.php">Dashboard</a></li>
                    <?php if (in_array($role, ['admin', 'moderator'])): ?>
                        <li><a href="/admin/index.php">Admin</a></li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>

            <ul class="nav-right">
                <?php if ($user): ?>
                    <li class="dropdown">
                        <a href="/dashboard.php" class="drop-toggle"><?= htmlspecialchars($user['first_name'] ?? $user['name'] ?? 'Account') ?></a>
                        <ul class="dropdown-menu">
                            <li><a href="/profile.php">Profile</a></li>
                            <li><a href="/auth/logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li><a href="/auth/login.php">Login</a></li>
                    <li><a class="btn-register" href="/auth/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>
