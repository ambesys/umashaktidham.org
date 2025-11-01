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

<!-- Top Header with Logo and Contact -->
<header class="top-header">
    <div class="container">
        <div class="top-header-content">
            <div class="logo-section">
                <a href="/">
                    <img src="/assets/images/logo.png" alt="Uma Shakti Dham Temple Logo" class="site-logo" />
                    <div class="temple-title">
                        <span class="site-title">Uma Shakti Dham</span>
                        <span class="temple-subtitle">Hindu Temple & Community Center</span>
                    </div>
                </a>
            </div>
            
            <div class="contact-info">
                <div class="contact-item">
                    <span class="contact-label">� Temple Phone:</span>
                    <a href="tel:+17043505040">(704) 350-5040</a>
                </div>
                <div class="contact-item">
                    <span class="contact-label">📧 Email:</span>
                    <a href="mailto:umashaktidham@gmail.com">umashaktidham@gmail.com</a>
                </div>
                <div class="contact-item">
                    <span class="contact-label">⏰ Arti Times:</span>
                    <span>7:00 AM | 7:00 PM</span>
                </div>
            </div>
            
            <?php if ($user): ?>
                <div class="user-section">
                    <div class="dropdown">
                        <a href="/dashboard" class="user-welcome">🙏 Welcome, <?= htmlspecialchars($user['first_name'] ?? $user['name'] ?? 'Devotee') ?></a>
                        <ul class="dropdown-menu">
                            <li><a href="/profile">👤 My Profile</a></li>
                            <li><a href="/dashboard">📊 Dashboard</a></li>
                            <li><a href="/auth/logout">🚪 Logout</a></li>
                        </ul>
                    </div>
                </div>
            <?php else: ?>
                <div class="auth-buttons">
                    <a href="/login" class="btn-login">🔑 Login</a>
                    <a href="/register" class="btn-register">✨ Join Us</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- Navigation Bar -->
<nav class="main-navigation">
    <div class="container">
        <button id="navToggle" class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">☰</button>
        
        <ul id="mainNav" class="nav-menu">
            <li><a href="/">🏠 Home</a></li>
            <li><a href="/about">📖 About Temple</a></li>
            <li><a href="/events">🎉 Events & Festivals</a></li>
            <li><a href="/gallery">📸 Photo Gallery</a></li>
            <li><a href="/membership">👥 Community</a></li>
            <li><a href="/donate">🎁 Donate</a></li>
            <li><a href="/contact">📞 Contact</a></li>
            <?php if ($user && in_array($role, ['admin', 'moderator'])): ?>
                <li><a href="/admin">⚙️ Admin</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
