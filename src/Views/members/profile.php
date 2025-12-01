<?php
session_start();
require_once '../../config/database.php';
require_once '../../src/Models/User.php';
require_once '../../src/Models/Member.php';

$userModel = new User($db);
$memberModel = new Member($db);

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

$user = $userModel->getUserById($_SESSION['user_id']);
$member = $memberModel->getMemberByUserId($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $member->updateProfile($_POST);
    header("Location: profile.php?success=Profile updated successfully");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <title>User Profile</title>
</head>
<body>
    <?php include '../layouts/header.php'; ?>
    
    <div class="page-heading">
        <div class="container">
            <h1>User Profile</h1>
            <p>Manage and update your personal profile information.</p>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo $_GET['success']; ?></div>
        <?php endif; ?>
        
        <form action="profile.php" method="POST">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($member->name); ?>" required>
            
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user->email); ?>" required>
            
            <label for="phone">Phone:</label>
            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($member->phone); ?>" required>
            
            <button type="submit">Update Profile</button>
        </form>
    </div>

    <?php include '../layouts/footer.php'; ?>
</body>
</html>