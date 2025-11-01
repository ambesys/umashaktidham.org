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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<!-- Top Contact Bar -->
<div class="top-contact-bar">
    <div class="container">
        <div class="contact-social-row">
            <div class="contact-quick">
                <span><i class="fas fa-phone"></i> (704) 350-5040</span>
                <span><i class="fas fa-envelope"></i> umashaktidham@gmail.com</span>
            </div>
            
            <div class="social-links">
                <!-- <a href="#" title="Facebook">Facebook</a>
                <a href="#" title="YouTube">YouTube</a>
                <a href="#" title="Instagram">Instagram</a>
                <a href="#" title="Twitter">Twitter</a> -->
            </div>
            
            <?php if (!$user): ?>
                <div class="top-auth">
                    <a href="/login" class="top-login">Login</a>
                    <a href="/register" class="top-register">Join Us</a>
                </div>
            <?php else: ?>
                <div class="top-user">
                    <span>Welcome, <?= htmlspecialchars($user['first_name'] ?? $user['name'] ?? 'Devotee') ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Main Header with Logo -->
<header class="main-header">
    <div class="container">
        <div class="header-content">
            <div class="logo-section">
                <a href="/">
                    <img src="/assets/images/logo.png" alt="Uma Shakti Dham Community Center Logo" class="site-logo" />
                    <div class="temple-title">
                        <span class="site-title">Uma Shakti Dham</span>
                        <span class="temple-subtitle">Kadva Patidar Community Center, NC</span>
                    </div>
                </a>
            </div>
            
            <div class="header-actions">
                <a href="/donate" class="donate-btn">
                    <i class="fas fa-heart"></i>
                    Donate
                </a>
                
                <?php if ($user): ?>
                    <div class="user-section">
                        <div class="dropdown">
                            <a href="/dashboard" class="user-welcome">
                                <i class="fas fa-user"></i> My Account
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href="/profile"><i class="fas fa-user"></i> My Profile</a></li>
                                <li><a href="/dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                                <li><a href="/auth/logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<!-- Navigation Bar -->
<nav class="main-navigation">
    <div class="container">
        <button id="navToggle" class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">☰ Menu</button>
        
        <ul id="mainNav" class="nav-menu">
            <li><a href="/">HOME</a></li>
            
            <li class="dropdown">
                <a href="/about" class="dropbtn">ABOUT COMMUNITY</a>
                <div class="dropdown-content">
                    <a href="/about">Community History</a>
                    <a href="/kp-history">Kadva Patidar Heritage</a>
                    <a href="/committee">Leadership Committee</a>
                    <a href="/bylaws">Bylaws & Constitution</a>
                </div>
            </li>
            
            <li class="dropdown">
                <a href="/events" class="dropbtn">EVENTS & PROGRAMS</a>
                <div class="dropdown-content">
                    <a href="/events">Upcoming Events</a>
                    <a href="/events/cultural">Cultural Programs</a>
                    <a href="/events/festivals">Festival Celebrations</a>
                    <a href="/indian-holidays">Cultural Calendar</a>
                </div>
            </li>
            
            <li class="dropdown">
                <a href="/gallery" class="dropbtn">PHOTO GALLERY</a>
                <div class="dropdown-content">
                    <a href="/gallery">Latest Photos</a>
                    <a href="/gallery/events">Event Photos</a>
                    <a href="/gallery/community">Community Photos</a>
                </div>
            </li>
            
            <li class="dropdown">
                <a href="/membership" class="dropbtn">COMMUNITY</a>
                <div class="dropdown-content">
                    <a href="/membership">Membership</a>
                    <a href="/members/families">Family Directory</a>
                    <a href="/youth-corner">Youth Corner</a>
                    <a href="/matrimonial">Business Network</a>
                    <a href="/business-directory">Business Directory</a>
                </div>
            </li>
            
            <li class="dropdown">
                <a href="/indian-holidays" class="dropbtn">RELIGION</a>
                <div class="dropdown-content">
                    <a href="/indian-holidays">Hindu Festivals</a>
                    <a href="/hindu-gods">Hindu Gods</a>
                    <a href="/hindu-rituals">Hindu Rituals</a>
                    <a href="/hindu-scriptures">Hindu Scriptures</a>
                </div>
            </li>
            
            <li><a href="/donate">DONATE</a></li>
            
            <li class="dropdown">
                <a href="/contact" class="dropbtn">CONTACT</a>
                <div class="dropdown-content">
                    <a href="/contact">Contact Us</a>
                    <a href="/direction">Location & Hours</a>
                    <a href="/facilities">Facilities & Rental</a>
                </div>
            </li>
            
            <?php if ($user && in_array($role, ['admin', 'moderator'])): ?>
                <li class="dropdown">
                    <a href="/admin" class="dropbtn">ADMIN</a>
                    <div class="dropdown-content">
                        <a href="/admin/users">Manage Users</a>
                        <a href="/admin/moderators">Moderators</a>
                        <a href="/admin/events">Manage Events</a>
                    </div>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>