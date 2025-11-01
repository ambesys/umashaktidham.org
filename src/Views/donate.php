<div class="container">
    <div class="donation-container">
        <h1><i class="fas fa-hand-holding-heart"></i> Support Uma Shakti Dham</h1>
        <p>Your contributions help us continue our mission and support our community.</p>

        <form action="../src/Controllers/DonationController.php" method="POST">
            <label for="amount">Donation Amount:</label>
            <input type="number" id="amount" name="amount" required>

            <label for="message">Message (optional):</label>
            <textarea id="message" name="message"></textarea>

            <button type="submit">Donate Now</button>
        </form>
    </div>
</div>