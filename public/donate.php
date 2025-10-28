<?php
session_start();
require_once '../src/Controllers/DonationController.php';

$donationController = new DonationController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'];
    $message = $_POST['message'];
    
    // Process the donation
    $result = $donationController->processDonation($amount, $message);
    
    if ($result) {
        $successMessage = "Thank you for your generous donation!";
    } else {
        $errorMessage = "There was an error processing your donation. Please try again.";
    }
}

include '../src/Views/layouts/header.php';
?>

<div class="donation-container">
    <h1>Donate to Uma Shakti Dham</h1>
    
    <?php if (isset($successMessage)): ?>
        <div class="alert alert-success"><?php echo $successMessage; ?></div>
    <?php elseif (isset($errorMessage)): ?>
        <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
    <?php endif; ?>
    
    <form action="donate.php" method="POST">
        <div class="form-group">
            <label for="amount">Donation Amount:</label>
            <input type="number" name="amount" id="amount" required>
        </div>
        <div class="form-group">
            <label for="message">Message (optional):</label>
            <textarea name="message" id="message"></textarea>
        </div>
        <button type="submit">Donate</button>
    </form>
</div>

<?php include '../src/Views/layouts/footer.php'; ?>