<?php
// Full header layout: includes DOCTYPE, <head> and opening <body>. Use $pageTitle (optional).
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pageTitle = $pageTitle ?? 'Uma Shakti Dham';
$isAccessPage = isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/access') === 0;
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
    <link rel="stylesheet" href="/assets/css/modal-forms.css">
    <!-- International Phone Input Library -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/css/intlTelInput.css">
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/intlTelInput.min.js"></script>
    <!-- Zipcode Lookup Library (US Zipcode Database) -->
    <script src="https://cdn.jsdelivr.net/npm/zipcode-database/dist/zipcode.min.js"></script>
</head>

<body>

<!-- Admin Navbar Styles -->
<style>
    /* User role badges */
    .user-badge {
        display: inline-block;
        padding: 0.2rem 0.4rem;
        border-radius: 3px;
        font-size: 0.7rem;
        font-weight: 600;
        margin-right: 0.3rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .user-badge.admin {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }
    
    .user-badge.member {
        background: #28a745;
        color: white;
    }
    
    /* Dropdown section titles */
    .dropdown-section-title {
        padding: 0.5rem 1rem 0.3rem;
        font-size: 0.8rem;
        font-weight: 600;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #e9ecef;
        margin-bottom: 0.3rem;
    }
    
    /* Admin links in dropdown */
    .admin-link {
        color: #667eea !important;
        font-weight: 600;
    }
    
    .admin-link:hover {
        background: #f0f4ff !important;
        color: #4c63d2 !important;
    }
    
    .admin-link i {
        margin-right: 0.5rem;
        width: 16px;
        text-align: center;
    }
    
    /* Logout link styling */
    .logout-link {
        color: #dc3545 !important;
        border-top: 1px solid #f8f9fa;
        margin-top: 0.3rem;
        padding-top: 0.8rem;
    }
    
    .logout-link:hover {
        background: #f8d7da !important;
        color: #c82333 !important;
    }
    
    .dropdown-divider {
        height: 1px;
        background: #e9ecef;
        margin: 0.5rem 0;
        border: none;
    }
    
    /* Enhanced dropdown styling */
    .admin-user .dropbtn {
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }
    
    .admin-user .dropbtn i:first-child {
        font-size: 1rem;
    }
    
    /* Mobile styles for enhanced dropdown */
    @media (max-width: 768px) {
        .admin-link {
            padding-left: 2rem;
            font-size: 0.9rem;
        }
        
        .dropdown-section-title {
            padding-left: 1.5rem;
            font-size: 0.75rem;
        }
        
        .user-badge {
            font-size: 0.6rem;
            padding: 0.15rem 0.3rem;
        }
        
        .logout-link {
            padding-left: 2rem;
        }
    }
</style>

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
                        <?php if (!isset($_SESSION['user_id'])): ?>
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

            <!-- Navigation Bar (hidden on access page to keep UI minimal) -->
            <?php if (!$isAccessPage): ?>
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


                        <button id="navToggle" class="navbar-toggler nav-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                    </div>

                    <ul id="mainNav" class="nav-menu collapse navbar-collapse">
                        <li><button id="navClose" class="close-btn" aria-label="Close menu">&times;</button></li>
                        <li><a href="/">HOME</a></li>

                        <li class="dropdown">
                            <a  class="dropbtn">ABOUT</a>
                            <div class="dropdown-content">
                                <a href="/about">About Community</a>
                                <a href="/kp-history">Kadva Patidar Heritage</a>
                                <a href="/committee">Leadership Committee</a>
                                <!-- Bylaws link removed per request -->
                            </div>
                        </li>

                        <li class="dropdown">
                            <a  class="dropbtn">EVENTS & PROGRAMS</a>
                            <div class="dropdown-content">
                                <a href="/events">Upcoming Events</a>
                                <a href="/events/cultural">Cultural Programs</a>
                                <a href="/events/festivals">Festival Celebrations</a>
                                <a href="/indian-holidays">Cultural Calendar</a>
                            </div>
                        </li>

                        <li class="dropdown">
                            <a  class="dropbtn">PHOTO GALLERY</a>
                            <div class="dropdown-content">
                                <a href="/gallery">Latest Photos</a>
                                <a href="/gallery/events">Event Photos</a>
                                <a href="/gallery/community">Community Photos</a>
                            </div>
                        </li>

                        <li class="dropdown">
                            <a  class="dropbtn">COMMUNITY</a>
                            <div class="dropdown-content">
                                <a href="/membership">Membership</a>
                                <a href="/members/families">Family Directory</a>
                                <a href="/youth-corner">Youth Corner</a>
                                <a href="/matrimonial">Business Network</a>
                                <a href="/business-directory">Business Directory</a>
                            </div>
                        </li>

                        <li class="dropdown">
                            <a  class="dropbtn">RELIGION</a>
                            <div class="dropdown-content">
                                <a href="/indian-holidays">Hindu Festivals</a>
                                <a href="/hindu-gods">Hindu Gods</a>
                                <a href="/hindu-rituals">Hindu Rituals</a>
                                <a href="/hindu-scriptures">Hindu Scriptures</a>
                            </div>
                        </li>

                        <li class="dropdown">
                            <a  class="dropbtn">CONTACT</a>
                            <div class="dropdown-content">
                                <a href="/contact">Contact Us</a>
                                <a href="/direction">Location & Hours</a>
                                <a href="/facilities">Facilities & Rental</a>
                            </div>
                        </li>


                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li class="dropdown admin-user">
                                <a  class="dropbtn">
                                    <i class="fas fa-user-circle"></i> 
                                    <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'moderator'])): ?>
                                        <span class="user-badge admin">ADMIN</span>
                                    <?php elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'member'): ?>
                                        <span class="user-badge member">MEMBER</span>
                                    <?php endif; ?>
                                    DASHBOARD
                                </a>
                                <div class="dropdown-content">
                                    <a href="/user/dashboard"><i class="fas fa-home"></i> Dashboard</a>
                                    <a href="/user/change-password"><i class="fas fa-key"></i> Change Password</a>
                                    
                                    <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'moderator'])): ?>
                                        <div class="dropdown-divider"></div>
                                        <div class="dropdown-section-title">Administration</div>
                                        <a href="/admin" class="admin-link"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</a>
                                        <a href="/admin/users" class="admin-link"><i class="fas fa-users"></i> Manage Users</a>
                                        <a href="/admin/events" class="admin-link"><i class="fas fa-calendar-alt"></i> Manage Events</a>
                                        <a href="/admin/moderators" class="admin-link"><i class="fas fa-user-shield"></i> Moderators</a>
                                        <a href="/admin/reports" class="admin-link"><i class="fas fa-chart-bar"></i> Reports</a>
                                        <a href="/admin/settings" class="admin-link"><i class="fas fa-cog"></i> Settings</a>
                                    <?php endif; ?>
                                    
                                    <div class="dropdown-divider"></div>
                                    <a href="/logout" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                                </div>
                            </li>
                        <?php else: ?>
                            <!-- <li><a href="/">HOME</a></li> -->
                            <li><a href="/donate" class="nav-donate"><i class="fas fa-donate"></i>DONATE</a></li>
                        <?php endif; ?>


                    </ul>

                    <!-- header actions moved to topbar; nav-actions removed to keep nav row focused on menu -->
                </div> <!-- .container.nav-row -->
            </nav>
            <?php endif; ?>
        </div> <!-- .header-row -->
        <!-- sticky helper removed; using CSS-only fixed header approach -->
        <!-- <script src="/assets/js/header-sticky.js" defer></script> -->

        <!-- Quick safety script: if scrolling is accidentally disabled (leftover from a script or class),
             restore scrolling. Runs once when DOM is ready. Harmless when not needed. -->

    </div>


