<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donate - Uma Shakti Dham</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body>
    <?php include 'layouts/header.php'; ?>

    <div class="donation-container">
        <h1>Support Uma Shakti Dham</h1>
        <p>Your contributions help us continue our mission and support our community.</p>

        <form action="../src/Controllers/DonationController.php" method="POST">
            <label for="amount">Donation Amount:</label>
            <input type="number" id="amount" name="amount" required>

            <label for="message">Message (optional):</label>
            <textarea id="message" name="message"></textarea>

            <button type="submit">Donate Now</button>
        </form>
    </div>

    <?php include 'layouts/footer.php'; ?>
</body>
</html>