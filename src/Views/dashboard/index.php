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

        <div class="dashboard-actions">
            <h2>Quick Actions</h2>
            <ul>
                <li><a href="/profile">Edit Profile</a></li>
                <li><a href="/family">Edit Family Information</a></li>
                <li><a href="/auth/logout">Sign Out</a></li>
            </ul>
        </div>

        <div class="dashboard-grid">
            <!-- Tile 1: Events -->
            <div class="dashboard-tile">
                <h2>Upcoming Events</h2>
                <ul>
                    <?php foreach ($dashboardData['events'] as $event): ?>
                        <li>
                            <strong><?= htmlspecialchars($event['name']) ?></strong><br>
                            <?= htmlspecialchars($event['date']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Tile 2: Your Tickets -->
            <div class="dashboard-tile">
                <h2>Your Tickets</h2>
                <ul>
                    <?php foreach ($dashboardData['tickets'] as $ticket): ?>
                        <li>
                            <strong><?= htmlspecialchars($ticket['event_name']) ?></strong><br>
                            <button class="btn btn-primary">View</button>
                            <button class="btn btn-secondary">Check-in QR</button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Tile 3: About You -->
            <div class="dashboard-tile">
                <h2>About You</h2>
                <p>Name: <?= htmlspecialchars($dashboardData['user']['name']) ?></p>
                <p>Email: <?= htmlspecialchars($dashboardData['user']['email']) ?></p>
                <a href="/profile" class="btn btn-primary">Edit Information</a>
            </div>

            <!-- Tile 4: Your Family -->
            <div class="dashboard-tile">
                <h2>Your Family</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Relation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dashboardData['family'] as $member): ?>
                            <tr>
                                <td><?= htmlspecialchars($member['name']) ?></td>
                                <td><?= htmlspecialchars($member['relation']) ?></td>
                                <td>
                                    <button class="btn btn-primary">Edit</button>
                                    <button class="btn btn-danger">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button class="btn btn-success">Add Family Member</button>
            </div>
        </div>
    </div>
</div>