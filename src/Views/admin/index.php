<div class="admin-dashboard mt-3">
    <div class="page-heading">
        <div class="container">
            <h2 class="h4 mb-1"><i class="fas fa-tachometer-alt text-muted me-2"></i> Admin Dashboard</h2>
            <p class="text-muted small mb-0">Quick snapshot of community activity.</p>
        </div>
    </div>

    <style>
        /* Compact dashboard spacing */
        .admin-dashboard .card-header,
        .admin-dashboard .card-body,
        .admin-dashboard .card-footer { padding: 0.5rem 0.75rem; }
        .admin-dashboard .card-body { padding-top: 0.5rem; padding-bottom: 0.5rem; }
        .admin-dashboard .table thead th { padding-top: 0.35rem; padding-bottom: 0.35rem; }
        .admin-dashboard .table tbody td { padding-top: 0.35rem; padding-bottom: 0.35rem; }
        .admin-dashboard .page-heading { margin-bottom: 0.75rem; }

        .progress-row {
            position: relative;
        }

        .progress-row::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            height: 3px;
            background-color: #28a745;
            width: var(--ratio, 0%);
            transition: width 0.3s ease;
        }

        .card-icon {
            width: 28px;
            height: 28px;
            background: linear-gradient(90deg, rgba(13, 110, 253, 0.2), rgba(25, 135, 84, 0.2));
            color: #0d6efd;
        }

        .info-card {
            border: 1px solid rgba(0, 0, 0, .125);
            border-radius: 0.375rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .info-card .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid rgba(0, 0, 0, .125);
        }

        .info-card .card-footer {
            background-color: #f8f9fa;
            border-top: 1px solid rgba(0, 0, 0, .125);
        }
    </style>

    <div class="container">
        <!-- Management Overview Cards -->
        <div class="row g-3 mb-3">
            <!-- User Management Card -->
            <div class="col-lg-4 col-md-6">
                <div class="card info-card sales-card h-100">
                    <div class="card-header d-flex align-items-center small">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="ps-3">
                            <h6 class="mb-0">User Management</h6>
                            <span class="text-success fw-bold">Total: <?php echo $stats['total_users'] ?? 0; ?>
                                users</span>
                        </div>
                    </div>
                    <div class="card-body py-2">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Category</th>
                                    <th class="text-end">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><small>Total Users</small></td>
                                    <td class="text-end"><small><?php echo $stats['total_users'] ?? 0; ?></small></td>
                                </tr>
                                <tr>
                                    <td><small>Families</small></td>
                                    <td class="text-end"><small><?php echo $stats['total_families'] ?? 0; ?></small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><small>Family Members</small></td>
                                    <td class="text-end"><small><?php echo $stats['total_members'] ?? 0; ?></small></td>
                                </tr>
                                <tr>
                                    <td><small>Adults/Kids</small></td>
                                    <td class="text-end">
                                        <small><?php echo ($stats['total_adults'] ?? 0) . '/' . ($stats['total_kids'] ?? 0); ?></small>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer p-1">
                        <a href="/admin/users" class="btn btn-link btn-sm w-100">
                            <i class="fas fa-users me-1"></i>Manage Users
                        </a>
                    </div>
                </div>
            </div>

            <!-- Event Management Card -->
            <div class="col-lg-4 col-md-6">
                <div class="card info-card customers-card h-100">
                    <div class="card-header d-flex align-items-center small">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-calendar-event"></i>
                        </div>
                        <div class="ps-3">
                            <h6 class="mb-0">Event Management</h6>
                            <span class="text-success fw-bold">Total: <?php echo $stats['total_events'] ?? 0; ?>
                                events</span>
                        </div>
                    </div>
                    <div class="card-body py-2">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Status</th>
                                    <th class="text-end">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><small>Total Events</small></td>
                                    <td class="text-end"><small><?php echo $stats['total_events'] ?? 0; ?></small></td>
                                </tr>
                                <tr>
                                    <td><small>Upcoming</small></td>
                                    <td class="text-end"><small><?php echo $stats['upcoming_events'] ?? 0; ?></small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><small>Past Events</small></td>
                                    <td class="text-end"><small><?php echo $stats['past_events'] ?? 0; ?></small></td>
                                </tr>
                                <tr>
                                    <td><small>Avg Registrations</small></td>
                                    <td class="text-end"><small>
                                            <?php
                                            $avgRegistrations = 0;
                                            if (isset($GLOBALS['pdo']) && ($stats['total_events'] ?? 0) > 0) {
                                                $stmt = $GLOBALS['pdo']->query("SELECT COUNT(*) as count FROM event_registrations");
                                                $totalRegistrations = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                                $avgRegistrations = round($totalRegistrations / $stats['total_events'], 1);
                                            }
                                            echo $avgRegistrations;
                                            ?>
                                        </small></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer p-1">
                        <a href="/admin/events" class="btn btn-secondary btn-sm w-100">
                            <i class="fas fa-calendar-alt me-1"></i>Manage Events
                        </a>
                    </div>
                </div>
            </div>

            <!-- Payment Management Card -->
            <div class="col-lg-4 col-md-6">
                <div class="card info-card revenue-card h-100">
                    <div class="card-header d-flex align-items-center small">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div class="ps-3">
                            <h6 class="mb-0">Payment Management</h6>
                            <span class="text-success fw-bold">Revenue:
                                $<?php echo number_format($stats['total_revenue'] ?? 0, 0); ?></span>
                        </div>
                    </div>
                    <div class="card-body py-2">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Type</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><small>Total Revenue</small></td>
                                    <td class="text-end">
                                        <small>$<?php echo number_format($stats['total_revenue'] ?? 0, 0); ?></small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><small>Donations</small></td>
                                    <td class="text-end">
                                        <small>$<?php echo number_format($stats['total_donations'] ?? 0, 0); ?></small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><small>Payments</small></td>
                                    <td class="text-end">
                                        <small>$<?php echo number_format($stats['total_payments'] ?? 0, 0); ?></small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><small>This Month</small></td>
                                    <td class="text-end">
                                        <small>$<?php echo number_format($stats['monthly_revenue'] ?? 0, 0); ?></small>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer p-1">
                        <a href="/admin/payments" class="btn btn-secondary btn-sm w-100">
                            <i class="fas fa-dollar-sign me-1"></i>Manage Payments
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3 d-none">
            <!-- Notifications Card -->
            <div class="col-lg-4 col-md-6">
                <div class="card info-card subscribers-card h-100">
                    <div class="card-header d-flex align-items-center small">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-bell"></i>
                        </div>
                        <div class="ps-3">
                            <h6 class="mb-0">Notifications</h6>
                            <span class="text-success fw-bold">Total: <?php echo $stats['total_notifications'] ?? 0; ?>
                                sent</span>
                        </div>
                    </div>
                    <div class="card-body py-2">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Status</th>
                                    <th class="text-end">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><small>Total Sent</small></td>
                                    <td class="text-end">
                                        <small><?php echo $stats['total_notifications'] ?? 0; ?></small></td>
                                </tr>
                                <tr>
                                    <td><small>Pending</small></td>
                                    <td class="text-end">
                                        <small><?php echo $stats['pending_notifications'] ?? 0; ?></small></td>
                                </tr>
                                <tr>
                                    <td><small>This Month</small></td>
                                    <td class="text-end">
                                        <small><?php echo $stats['monthly_notifications'] ?? 0; ?></small></td>
                                </tr>
                                <tr>
                                    <td><small>Email/SMS</small></td>
                                    <td class="text-end">
                                        <small><?php echo ($stats['email_notifications'] ?? 0) . '/' . ($stats['sms_notifications'] ?? 0); ?></small>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer p-1">
                        <a href="/admin/notifications" class="btn btn-secondary btn-sm w-100">
                            <i class="fas fa-bell me-1"></i>Manage Notifications
                        </a>
                    </div>
                </div>
            </div>

            <!-- Requests & Support Card -->
            <div class="col-lg-4 col-md-6">
                <div class="card info-card rewards-card h-100">
                    <div class="card-header d-flex align-items-center small">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-clipboard-list"></i>
                        </div>
                        <div class="ps-3">
                            <h6 class="mb-0">Requests & Support</h6>
                            <span class="text-success fw-bold">Total:
                                <?php echo ($stats['pending_requests'] ?? 0) + ($stats['resolved_requests'] ?? 0); ?>
                                requests</span>
                        </div>
                    </div>
                    <div class="card-body py-2">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Status</th>
                                    <th class="text-end">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><small>Total Requests</small></td>
                                    <td class="text-end">
                                        <small><?php echo ($stats['pending_requests'] ?? 0) + ($stats['resolved_requests'] ?? 0); ?></small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><small>Pending</small></td>
                                    <td class="text-end"><small><?php echo $stats['pending_requests'] ?? 0; ?></small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><small>Resolved</small></td>
                                    <td class="text-end"><small><?php echo $stats['resolved_requests'] ?? 0; ?></small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><small>This Month</small></td>
                                    <td class="text-end"><small><?php echo $stats['monthly_requests'] ?? 0; ?></small>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer p-1">
                        <a href="/admin/requests" class="btn btn-secondary btn-sm w-100">
                            <i class="fas fa-clipboard-list me-1"></i>Manage Requests
                        </a>
                    </div>
                </div>
            </div>

            <!-- Content Management Card -->
            <div class="col-lg-4 col-md-6">
                <div class="card info-card h-100">
                    <div class="card-header d-flex align-items-center small">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <div class="ps-3">
                            <h6 class="mb-0">Content Management</h6>
                            <span class="text-success fw-bold">Total:
                                <?php echo ($stats['total_pages'] ?? 0) + ($stats['total_images'] ?? 0); ?> items</span>
                        </div>
                    </div>
                    <div class="card-body py-2">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Type</th>
                                    <th class="text-end">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><small>Pages</small></td>
                                    <td class="text-end"><small><?php echo $stats['total_pages'] ?? 0; ?></small></td>
                                </tr>
                                <tr>
                                    <td><small>Images</small></td>
                                    <td class="text-end"><small><?php echo $stats['total_images'] ?? 0; ?></small></td>
                                </tr>
                                <tr>
                                    <td><small>Documents</small></td>
                                    <td class="text-end"><small><?php echo $stats['total_documents'] ?? 0; ?></small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><small>This Month</small></td>
                                    <td class="text-end"><small><?php echo $stats['monthly_content'] ?? 0; ?></small>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer p-1">
                        <a href="/admin/content" class="btn btn-secondary btn-sm w-100">
                            <i class="fas fa-edit me-1"></i>Manage Content
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="container">
        <div class="recent-activity card border-0 shadow-sm mt-3">
            <div class="card-header bg-light py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="h6 mb-0"><i class="fas fa-history"></i> Recent Activity</h3>
                    <a href="/admin/activity" class="btn btn-outline-primary btn-sm">View All</a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php if (!empty($recentActivity)): ?>
                        <?php foreach ($recentActivity as $activity): ?>
                            <div class="list-group-item px-3 py-2">
                                <div class="d-flex align-items-center">
                                    <div class="activity-icon bg-<?php echo $activity['type'] === 'user' ? 'success' : ($activity['type'] === 'event' ? 'info' : ($activity['type'] === 'payment' ? 'warning' : 'secondary')); ?> text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                        style="width: 32px; height: 32px;">
                                        <i class="fas fa-<?php echo $activity['icon']; ?> fa-sm"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <p class="mb-0 fw-semibold small"><?php echo htmlspecialchars($activity['title']); ?></p>
                                        <small class="text-muted small"><?php echo htmlspecialchars($activity['meta']); ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="list-group-item px-3 py-2 text-center text-muted">
                            <i class="fas fa-info-circle me-2"></i>No recent activity
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>