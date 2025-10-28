<?php
session_start();
require_once '../../config/config.php';
require_once '../../src/Controllers/DashboardController.php';

$dashboardController = new DashboardController();
$userData = $dashboardController->getUserData($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <title>User Dashboard</title>
</head>
<body>
    <?php include '../layouts/header.php'; ?>

    <div class="dashboard-container">
        <h1>Welcome, <?php echo htmlspecialchars($userData['name']); ?></h1>
        <div class="user-details">
            <h2>Your Details</h2>
            <p>Email: <?php echo htmlspecialchars($userData['email']); ?></p>
            <p>Membership Status: <?php echo htmlspecialchars($userData['membership_status']); ?></p>
        </div>

        <div class="family-details">
            <h2>Your Family Details</h2>
            <a href="family.php">Manage Family Information</a>
        </div>

        <div class="donation-section">
            <h2>Make a Donation</h2>
            <a href="../donate.php">Donate Now</a>
        </div>
    </div>

    <?php include '../layouts/footer.php'; ?>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>