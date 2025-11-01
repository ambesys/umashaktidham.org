<div class="container">
    <div class="dashboard-container">
        <h1>Welcome, <?php echo htmlspecialchars($dashboardData['name'] ?? $_SESSION['user_name'] ?? 'User'); ?></h1>
        <div class="user-details">
            <h2>Your Details</h2>
            <p>Email: <?php echo htmlspecialchars($dashboardData['email'] ?? $_SESSION['user_email'] ?? 'Not available'); ?></p>
            <p>Membership Status: <?php echo htmlspecialchars($dashboardData['membership_status'] ?? 'Active'); ?></p>
        </div>

        <div class="family-details">
            <h2>Your Family Details</h2>
            <a href="/family" class="btn btn-primary">Manage Family Information</a>
        </div>

        <div class="donation-section">
            <h2>Make a Donation</h2>
            <a href="/donate" class="btn btn-success">Donate Now</a>
        </div>
    </div>
</div>