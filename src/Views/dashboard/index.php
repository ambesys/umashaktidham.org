<div class="container">
    <div class="dashboard-container">
        <h1>Welcome, <?= htmlspecialchars($dashboardData['user']['name'] ?? 'User') ?>!</h1>

        <!-- Family Table -->
        <div class="family-section">
            <h2>Your Family</h2>
            <table class="family-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Relation</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?= htmlspecialchars($dashboardData['user']['name']) ?></td>
                        <td>Self</td>
                        <td>
                            <button class="btn btn-primary">Edit</button>
                        </td>
                    </tr>
                    <?php if (!empty($dashboardData['family']) && is_array($dashboardData['family'])): ?>
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
                    <?php endif; ?>
                </tbody>
            </table>
            <button class="btn btn-success">Add Family Member</button>
        </div>

        <!-- Events and Tickets Section -->
        <div class="events-tickets-section">
            <div class="events">
                <h2>Upcoming Events</h2>
                <ul>
                    <?php if (!empty($dashboardData['events']) && is_array($dashboardData['events'])): ?>
                        <?php foreach ($dashboardData['events'] as $event): ?>
                            <li>
                                <strong><?= htmlspecialchars($event['name']) ?></strong><br>
                                <?= htmlspecialchars($event['date']) ?>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>No upcoming events.</li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="tickets">
                <h2>Your Tickets</h2>
                <ul>
                    <?php if (!empty($dashboardData['tickets']) && is_array($dashboardData['tickets'])): ?>
                        <?php foreach ($dashboardData['tickets'] as $ticket): ?>
                            <li>
                                <strong><?= htmlspecialchars($ticket['event_name']) ?></strong><br>
                                <button class="btn btn-primary">View</button>
                                <button class="btn btn-secondary">Check-in QR</button>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>No tickets available.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>