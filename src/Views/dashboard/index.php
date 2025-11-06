<div class="container mt-5">
    <div class="row">
        <div class="col-12 text-center mb-4">
            <h1 class="display-4">Welcome, <?= htmlspecialchars($dashboardData['user']['name'] ?? 'User') ?>!</h1>
        </div>
    </div>

    <div class="row">
        <!-- Family Section -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5>Your Family</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
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
                                    <button class="btn btn-sm btn-primary">Edit</button>
                                </td>
                            </tr>
                            <?php if (!empty($dashboardData['family']) && is_array($dashboardData['family'])): ?>
                                <?php foreach ($dashboardData['family'] as $member): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($member['name']) ?></td>
                                        <td><?= htmlspecialchars($member['relation']) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary">Edit</button>
                                            <button class="btn btn-sm btn-danger">Delete</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <button class="btn btn-success">Add Family Member</button>
                </div>
            </div>
        </div>

        <!-- Events Section -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5>Upcoming Events</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php if (!empty($dashboardData['events']) && is_array($dashboardData['events'])): ?>
                            <?php foreach ($dashboardData['events'] as $event): ?>
                                <li class="list-group-item">
                                    <strong><?= htmlspecialchars($event['name']) ?></strong><br>
                                    <?= htmlspecialchars($event['date']) ?>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item">No upcoming events.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Tickets Section -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5>Your Tickets</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php if (!empty($dashboardData['tickets']) && is_array($dashboardData['tickets'])): ?>
                            <?php foreach ($dashboardData['tickets'] as $ticket): ?>
                                <li class="list-group-item">
                                    <strong><?= htmlspecialchars($ticket['event_name']) ?></strong><br>
                                    <button class="btn btn-sm btn-primary">View</button>
                                    <button class="btn btn-sm btn-secondary">Check-in QR</button>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item">No tickets available.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Profile Section -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5>About You</h5>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?= htmlspecialchars($dashboardData['user']['name'] ?? 'N/A') ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($dashboardData['user']['email'] ?? 'N/A') ?></p>
                    <a href="/profile" class="btn btn-primary">Edit Information</a>
                </div>
            </div>
        </div>
    </div>
</div>