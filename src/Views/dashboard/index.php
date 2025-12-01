<?php
// Ensure $dashboardData exists and provide safe defaults for keys used in this view.
$dashboardData = (is_array($dashboardData ?? null)) ? $dashboardData : [];
$dashboardData['user'] = is_array($dashboardData['user'] ?? null) ? $dashboardData['user'] : [];
$dashboardData['family'] = is_array($dashboardData['family'] ?? null) ? $dashboardData['family'] : [];
$dashboardData['events'] = is_array($dashboardData['events'] ?? null) ? $dashboardData['events'] : [];
$dashboardData['tickets'] = is_array($dashboardData['tickets'] ?? null) ? $dashboardData['tickets'] : [];

// --- Profile completion calculation ---
// Define the fields to check for 'self' and for the first family member.
$selfFields = [
    'relationship', // relation
    'name',
    'birth_year', // age
    'village',
    'mosal',
    'occupation',
    // contact details: email and phone - treat as one grouped field but check both
    'contact'
];

$familyFields = [
    'relationship',
    'name',
    'birth_year',
    'village',
    'mosal',
    'occupation'
];

$filled = 0;
$total = count($selfFields) + count($familyFields);

// Evaluate self fields
$self = $dashboardData['user'];
// Normalize name
$selfName = trim((string)($self['name'] ?? ($self['first_name'] . ' ' . $self['last_name'] ?? '')));
foreach ($selfFields as $f) {
    if ($f === 'contact') {
        $hasEmail = !empty($self['email']);
        $hasPhone = !empty($self['phone_e164']) || !empty($self['phone']);
        if ($hasEmail || $hasPhone) {
            $filled++;
        }
        continue;
    }

    if ($f === 'name') {
        if ($selfName !== '') $filled++;
        continue;
    }

    if (!empty($self[$f])) $filled++;
}

// Evaluate first family member (non-self). We treat the first entry in family list as the primary family member
$firstFamily = null;
if (!empty($dashboardData['family']) && is_array($dashboardData['family'])) {
    $firstFamily = $dashboardData['family'][0];
}

if (is_array($firstFamily)) {
    $famName = trim((string)($firstFamily['first_name'] ?? $firstFamily['name'] ?? ''));
    foreach ($familyFields as $f) {
        if ($f === 'name') {
            if ($famName !== '') $filled++;
            continue;
        }
        if (!empty($firstFamily[$f]) || !empty($firstFamily[($f === 'birth_year' ? 'birth_year' : $f)])) $filled++;
    }
}

$percent = (int)round(($filled / max(1, $total)) * 100);

// Build missing items list for banner
$missing = [];
// self missing
foreach ($selfFields as $f) {
    if ($f === 'contact') {
        $hasEmail = !empty($self['email']);
        $hasPhone = !empty($self['phone_e164']) || !empty($self['phone']);
        if (!($hasEmail || $hasPhone)) $missing[] = 'Contact details (email or phone)';
        continue;
    }
    if ($f === 'name') {
        if ($selfName === '') $missing[] = 'Name';
        continue;
    }
    if (empty($self[$f])) $missing[] = ucwords(str_replace('_', ' ', $f));
}

// family missing (if no family exists, encourage adding one)
if (!is_array($firstFamily)) {
    $missing[] = 'Add at least one family member';
} else {
    foreach ($familyFields as $f) {
        if ($f === 'name') {
            if (trim((string)($firstFamily['first_name'] ?? $firstFamily['name'] ?? '')) === '') $missing[] = 'Family member name';
            continue;
        }
        if (empty($firstFamily[$f])) $missing[] = 'Family ' . ucwords(str_replace('_', ' ', $f));
    }
}

?>

<div class="user-dashboard bg-light py-4">
    <div class="page-heading">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h1 class="mb-0"><i class="fas fa-user-circle text-muted me-3"></i> Welcome, <?= htmlspecialchars($dashboardData['user']['name'] ?? 'User') ?>!</h1>
                    <p class="mb-0">Manage your profile, family members, and stay updated with community events.</p>
                </div>

                <!-- Profile completion donut / progress -->
                <div class="d-flex align-items-center gap-3">
                    <div class="text-end me-2">
                        <div id="profilePercentText" class="h5 mb-0 text-white"><?= $percent ?>%</div>
                        <small class="text-white">Profile complete</small>
                    </div>
                    <svg id="profileDonut" width="64" height="64" viewBox="0 0 42 42" class="rounded-circle">
                        <defs>
                            <linearGradient id="g1" x1="0%" x2="100%">
                                <stop offset="0%" stop-color="#4caf50"/>
                                <stop offset="100%" stop-color="#2196f3"/>
                            </linearGradient>
                        </defs>
                        <circle cx="21" cy="21" r="15.5" fill="none" stroke="#e9ecef" stroke-width="5"></circle>
                        <circle id="donutProgress" cx="21" cy="21" r="15.5" fill="none" stroke="url(#g1)" stroke-width="5" stroke-linecap="round" transform="rotate(-90 21 21)" stroke-dasharray="0 100"></circle>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <?php if ($percent < 100): ?>
            <div class="alert alert-warning border shadow-sm">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <strong>Complete your profile</strong>
                        <p class="mb-0 text-muted">Your profile is <?= $percent ?>% complete. Completing your profile helps the community know you better.</p>
                        <?php if (!empty($missing)): ?>
                            <!-- <ul class="mb-0 mt-2">
                                <?php foreach (array_slice($missing,0,5) as $m): ?>
                                    <li class="small text-muted"><?= htmlspecialchars($m) ?></li>
                                <?php endforeach; ?>
                                <?php if (count($missing) > 5): ?>
                                    <li class="small text-muted">and <?= count($missing) - 5 ?> more...</li>
                                <?php endif; ?>
                            </ul> -->
                        <?php endif; ?>
                    </div>
                    <div class="text-end">
                        <button class="btn btn-outline-primary btn-sm me-2" data-action="edit-profile">Edit Profile</button>
                        <button class="btn btn-primary btn-sm" data-action="add-family">Add Family Member</button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php
        // Check if user is new (created within last 7 days)
        if (isset($dashboardData['user']['created_at'])) {
            $createdAt = strtotime($dashboardData['user']['created_at']);
            $sevenDaysAgo = strtotime('-7 days');
            if ($createdAt >= $sevenDaysAgo) {
                $daysAgo = ceil((time() - $createdAt) / 86400);
                $daysLeft = 7 - $daysAgo;
        ?>
        <!-- Welcome Banner for New Users -->
        <div class="alert alert-light border shadow-sm">
            <div class="d-flex align-items-center">
                <i class="fas fa-star fa-2x text-muted me-3"></i>
                <div>
                    <h5 class="alert-heading mb-1 text-dark">Welcome to Umashakti Dham! 🎉</h5>
                    <p class="mb-0 text-muted">Thank you for joining our community! Get started by completing your profile and adding family members.</p>
                </div>
            </div>
        </div>
        <?php
            }
        }
        ?>

        <div class="row">
            <!-- Family Section -->
            <div class="col-lg-8 mb-4">
                <div class="card border shadow-sm">
                    <div class="card-header bg-light border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-dark"><i class="fas fa-users text-muted me-2"></i> Your Family</h5>
                            <button id="addFamilyButton" class="btn btn-outline-secondary btn-sm" data-action="add-family">
                                <i class="fas fa-user-plus"></i> Add Member
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <!-- Success/Error Banners for self update -->
                        <!-- <div id="selfSuccessBanner" class="alert alert-success alert-dismissible fade show mb-0 mx-3 mt-3 " role="alert"> -->


                        <!-- Family List as Table -->
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Relation</th>
                                        <th>Name</th>
                                        <th>Age</th>
                                        <th>Village</th>
                                        <th>Mosal</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="familyList">
                                    <!-- Display user as 'Self' -->
                                    <tr data-user-profile
                                        data-user-id="<?= htmlspecialchars($dashboardData['user']['id'] ?? '') ?>"
                                        data-first-name="<?= htmlspecialchars($dashboardData['user']['first_name'] ?? '') ?>"
                                        data-last-name="<?= htmlspecialchars($dashboardData['user']['last_name'] ?? '') ?>"
                                        data-email="<?= htmlspecialchars($dashboardData['user']['email'] ?? '') ?>"
                                        data-phone="<?= htmlspecialchars($dashboardData['user']['phone_e164'] ?? '') ?>"
                                        data-birth-year="<?= htmlspecialchars($dashboardData['user']['birth_year'] ?? '') ?>"
                                        data-gender="<?= htmlspecialchars($dashboardData['user']['gender'] ?? '') ?>"
                                        data-occupation="<?= htmlspecialchars($dashboardData['user']['occupation'] ?? '') ?>"
                                        data-mosal="<?= htmlspecialchars($dashboardData['user']['mosal'] ?? '') ?>"
                                        data-village="<?= htmlspecialchars($dashboardData['user']['village'] ?? '') ?>"
                                        data-business-info="<?= htmlspecialchars($dashboardData['user']['business_info'] ?? '') ?>"
                                        data-street-address="<?= htmlspecialchars($dashboardData['user']['street_address'] ?? '') ?>"
                                        data-city="<?= htmlspecialchars($dashboardData['user']['city'] ?? '') ?>"
                                        data-state="<?= htmlspecialchars($dashboardData['user']['state'] ?? '') ?>"
                                        data-zip-code="<?= htmlspecialchars($dashboardData['user']['zip_code'] ?? '') ?>"
                                        data-country="<?= htmlspecialchars($dashboardData['user']['country'] ?? 'USA') ?>">
                                        <td><strong><?= htmlspecialchars($dashboardData['user']['relationship'] ?? 'Self') ?></strong></td>
                                        <td><?= htmlspecialchars($dashboardData['user']['name'] ?? 'User') ?></td>
                                        <td>
                                            <?php
                                            $birthYear = $dashboardData['user']['birth_year'] ?? null;
                                            if ($birthYear) {
                                                $age = date('Y') - $birthYear;
                                                echo $age;
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td><?= htmlspecialchars($dashboardData['user']['village'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($dashboardData['user']['mosal'] ?? '-') ?></td>
                                        <td>
                                            <button class="btn btn-edit btn-sm" data-action="edit-profile">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Family Members -->
                                    <?php if (!empty($dashboardData['family']) && is_array($dashboardData['family'])): ?>
                                        <?php foreach ($dashboardData['family'] as $member): ?>
                                            <?php include __DIR__ . '/../partials/family_row.php'; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Add Family Member Form -->
                        <!-- Replaced by modal - no inline form needed -->
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Events Section -->
                <div class="card border-0 bg-white shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-success bg-opacity-10 rounded-circle p-2 me-3">
                                <i class="fas fa-calendar-alt text-success"></i>
                            </div>
                            <h6 class="mb-0 text-dark">Upcoming Events</h6>
                        </div>
                        <?php if (!empty($dashboardData['events']) && is_array($dashboardData['events'])): ?>
                            <?php foreach ($dashboardData['events'] as $event): ?>
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <div>
                                        <h6 class="mb-1 small fw-bold"><?= htmlspecialchars($event['name']) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($event['date']) ?></small>
                                    </div>
                                    <button class="btn btn-outline-secondary btn-sm">Register</button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-times fa-2x text-muted mb-3"></i>
                                <p class="text-muted mb-0">No upcoming events</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tickets Section -->
                <div class="card border-0 bg-white shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-warning bg-opacity-10 rounded-circle p-2 me-3">
                                <i class="fas fa-ticket-alt text-warning"></i>
                            </div>
                            <h6 class="mb-0 text-dark">Your Tickets</h6>
                        </div>
                        <?php if (!empty($dashboardData['tickets']) && is_array($dashboardData['tickets'])): ?>
                            <?php foreach ($dashboardData['tickets'] as $ticket): ?>
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <div>
                                        <h6 class="mb-1 small fw-bold"><?= htmlspecialchars($ticket['event_name']) ?></h6>
                                        <small class="text-muted">Ticket #<?= htmlspecialchars($ticket['id']) ?></small>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-outline-secondary btn-sm">View</button>
                                        <button class="btn btn-outline-secondary btn-sm">Check-in</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-ticket-alt fa-2x text-muted mb-3"></i>
                                <p class="text-muted mb-0">No tickets available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reusable Modal Forms (included at bottom) -->
<?php include __DIR__ . '/../partials/modal-forms.php'; ?>

<!-- Dashboard-specific scripts (must load after Bootstrap) -->
<script src="/assets/js/dashboard.js"></script>
<script src="/assets/js/modal-forms.js"></script>

<!-- Profile completion script -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    try {
        var pct = <?= json_encode($percent) ?>;
        var donut = document.getElementById('donutProgress');
        var percentText = document.getElementById('profilePercentText');
        if (donut && percentText) {
            var radius = 15.5;
            var circumference = 2 * Math.PI * radius;
            var filled = Math.max(0, Math.min(100, pct));
            var dash = (filled / 100) * circumference;
            // set dasharray to show filled portion
            donut.setAttribute('stroke-dasharray', dash + ' ' + (circumference - dash));
            // animate (simple)
            donut.style.transition = 'stroke-dasharray 800ms ease';
            percentText.textContent = filled + '%';
        }
    } catch (e) {
        console && console.warn && console.warn('Profile completion script error', e);
    }
});
</script>