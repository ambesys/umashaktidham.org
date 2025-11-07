<?php
// Full header layout: includes DOCTYPE, <head> and opening <body>. Use $pageTitle (optional).
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user = $_SESSION['user'] ?? null;
$role = $_SESSION['user_role'] ?? null;
$pageTitle = $pageTitle ?? 'Uma Shakti Dham';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>

<body>

    <!-- Top Contact Bar -->
    <div class="site-header-wrapper">
        <div class="top-contact-bar">
            <div class="container">
                <div class="contact-social-row">
                    <div class="contact-quick">
                        <span><i class="fas fa-phone"></i> (704) 350-5040</span>
                        <span><i class="fas fa-envelope"></i> umashaktidham@gmail.com</span>
                    </div>



                    <div class="top-auth">
                        <div class="topbar-donate">
                            <a href="/donate" class="donate-btn"><i class="fas fa-donate"></i>DONATE</a>
                        </div>
                        <?php if (!$user): ?>
                            <a href="/login" class="top-login">Login</a>
                            <a href="/register" class="top-register">Join Us</a>
                        <?php else: ?>
                            <div class="topbar">
                                <a href="/donate" class="donate-btn"><i class="fas fa-donate"></i>DONATE</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Header block: logo overlaps top bar and the navigation row (we keep only two rows) -->
        <div class="header-row">

            <!-- Navigation Bar (this is the single row under the top contact bar) -->
            <nav class="main-navigation">
                <div class="container nav-row">
                    <!-- brand-logo placed at beginning of the .container so it's aligned with container start -->
                    <a href="/" class="brand-logo" aria-label="Uma Shakti Dham home">
                        <img src="/assets/images/logo.png" alt="Uma Shakti Dham Community Center Logo"
                            class="site-logo" />
                    </a>

                    <!-- Mobile donate button shown in nav row on small screens (Donate + Hamburger layout) -->

                    <div class="nav-center">

                        <div class="temple-title">
                            <span class="site-title">Uma Shakti Dham</span>
                            <span class="temple-subtitle">Kadva Patidar Community Center, NC</span>
                        </div>

                    </div>

                    <div class="nav-right">


                        <button id="navToggle" class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false"
                            aria-controls="mainNav" type="button">
                            <span class="bar"></span>
                            <span class="bar"></span>
                            <span class="bar"></span>
                        </button>
                    </div>

                    <ul id="mainNav" class="nav-menu">
                        <li><button id="navClose" class="close-btn" aria-label="Close menu">&times;</button></li>
                        <li><a href="/">HOME</a></li>

                        <li class="dropdown">
                            <a href="/about" class="dropbtn">ABOUT</a>
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

                        <li class="dropdown">
                            <a href="/contact" class="dropbtn">CONTACT</a>
                            <div class="dropdown-content">
                                <a href="/contact">Contact Us</a>
                                <a href="/direction">Location & Hours</a>
                                <a href="/facilities">Facilities & Rental</a>
                            </div>
                        </li>


                        <?php if ($user): ?>
                            <?php if ($user && in_array($role, ['admin', 'moderator'])): ?>
                                <li class="dropdown user">
                                    <a href="/admin" class="dropbtn">ADMIN</a>
                                    <div class="dropdown-content">
                                        <a href="/admin/users">Manage Users</a>
                                        <a href="/admin/moderators">Moderators</a>
                                        <a href="/admin/events">Manage Events</a>
                                    </div>
                                </li>
                            <?php else: ?>
                                <li class="dropdown admin-user">
                                    <a href="/dashboard" class="dropbtn">DASHBOARD</a>
                                    <div class="dropdown-content">
                                        <a href="/dashboard">My Dashboard</a>
                                        <a href="/members/profile">Profile Settings</a>
                                        <a href="/membership">Membership Info</a>
                                        <a href="/logout">Logout</a>
                                    </div>
                                </li>
                            <?php endif; ?>
                        <?php else: ?>
                            <!-- <li><a href="/">HOME</a></li> -->
                            <li><a href="/donate" class="nav-donate"><i class="fas fa-donate"></i>DONATE</a></li>
                        <?php endif; ?>


                    </ul>

                    <!-- header actions moved to topbar; nav-actions removed to keep nav row focused on menu -->
                </div> <!-- .container.nav-row -->
            </nav>
        </div> <!-- .header-row -->
        <!-- sticky helper removed; using CSS-only fixed header approach -->
        <!-- <script src="/assets/js/header-sticky.js" defer></script> -->

        <!-- Quick safety script: if scrolling is accidentally disabled (leftover from a script or class),
             restore scrolling. Runs once when DOM is ready. Harmless when not needed. -->

    </div>
