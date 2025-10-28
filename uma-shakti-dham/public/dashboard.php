<?php
session_start();
require_once '../src/Controllers/DashboardController.php';

$dashboardController = new DashboardController();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$userDetails = $dashboardController->getUserDetails($_SESSION['user_id']);
$familyDetails = $dashboardController->getFamilyDetails($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <title>User Dashboard</title>
</head>
<body>
    <?php include '../src/Views/layouts/header.php'; ?>

    <div class="dashboard-container">
        <h1>Welcome, <?php echo htmlspecialchars($userDetails['name']); ?></h1>
        
        <div class="user-details">
            <h2>Your Details</h2>
            <p>Email: <?php echo htmlspecialchars($userDetails['email']); ?></p>
            <p>Phone: <?php echo htmlspecialchars($userDetails['phone']); ?></p>
            <a href="members/profile.php">Edit Profile</a>
        </div>

        <div class="family-details">
            <h2>Your Family Members</h2>
            <ul>
                <?php foreach ($familyDetails as $familyMember): ?>
                    <li><?php echo htmlspecialchars($familyMember['name']); ?> - <?php echo htmlspecialchars($familyMember['relation']); ?></li>
                <?php endforeach; ?>
            </ul>
            <a href="members/family.php">Manage Family Details</a>
        </div>

        <div class="donation-section">
            <h2>Support Us</h2>
            <a href="donate.php">Make a Donation</a>
        </div>
    </div>

    <?php include '../src/Views/layouts/footer.php'; ?>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>