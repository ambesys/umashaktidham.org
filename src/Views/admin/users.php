<div class="admin-dashboard mt-4">

    <div class="page-heading">
        <div class="container">
            <h1><i class="fas fa-users text-muted me-2"></i> User Management</h1>
            <p>Manage community members and their profile information.</p>
        </div>
    </div>

<div class="container py-4">
    <div class="d-flex justify-content-end align-items-center mb-4">
        <button onclick="openAddUserForm()" class="btn btn-primary">
            <i class="fas fa-user-plus me-2"></i>Add New User
        </button>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <?php
        $cards = [
            ['label' => 'Total Users', 'value' => $stats['total_users'] ?? 0],
            ['label' => 'Family Members', 'value' => $stats['total_members'] ?? 0],
            ['label' => 'Adults (11-59)', 'value' => $stats['total_adults'] ?? 0],
            ['label' => 'Kids (≤10)', 'value' => $stats['total_kids'] ?? 0],
            ['label' => 'Seniors (≥60)', 'value' => $stats['total_seniors'] ?? 0],
            ['label' => 'Total Sponsors', 'value' => $stats['total_sponsors'] ?? 0],
        ];
        foreach ($cards as $c) { ?>
            <div class="col-md-2">
                <div class="stats-card shadow-md text-center">
                    <div class="stats-card-body">
                        <div class="stats-card-label"><?= $c['label']; ?></div>
                        <div class="h4 mb-0 fw-bold"><?= $c['value']; ?></div>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>

    <!-- User Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>All Users (<?= count($users ?? []); ?>)</h5>
                <div class="d-flex gap-2">
                    <input type="text" class="form-control form-control-sm" id="searchAllUsers" placeholder="Search users..." style="width:250px;">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-filter me-1"></i>Filter
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="filterUsers('all')">All Users</a></li>
                            <li><a class="dropdown-item" href="#" onclick="filterUsers('active')">Active</a></li>
                            <li><a class="dropdown-item" href="#" onclick="filterUsers('inactive')">Inactive</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="filterUsers('admin')">Administrators</a></li>
                        </ul>
                    </div>
                    <button class="btn btn-outline-secondary btn-sm" onclick="filterUsers('all')"><i class="fas fa-sync-alt me-1"></i>Reset</button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover" id="allUsersTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width:32px;"><input type="checkbox" class="form-check-input" id="selectAll"></th>
                            <th style="width:60px;">ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Cell#</th>
                            <th style="width:90px;">Family Size</th>
                            <th style="width:120px;">Join Date</th>
                            <th style="width:110px; text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users) && is_array($users)) {
                            foreach ($users as $user) { ?>
                                <tr data-user-id="<?= $user['id']; ?>" data-status="<?= ($user['status'] ?? 'active'); ?>" data-role="<?= ($user['role_name'] ?? 'user'); ?>">
                                    <td><input type="checkbox" class="form-check-input user-checkbox" value="<?= $user['id']; ?>"></td>
                                    <td><?= $user['id']; ?></td>
                                    <td><?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></td>
                                    <td><?= htmlspecialchars($user['email'] ?? ''); ?></td>
                                    <td><?= htmlspecialchars($user['phone_e164'] ?? ''); ?></td>
                                    <td><?= $user['family_size'] ?? 1; ?></td>
                                    <td><?= date('M j, Y', strtotime($user['created_at'] ?? 'now')); ?></td>
                                    <td style="display: flex; gap: 0.25rem;">
                                        <button class="btn btn-sm btn-outline-primary" onclick="openEditUserForm(<?= $user['id']; ?>)" title="Edit User">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-info" onclick="toggleFamily(<?= $user['id']; ?>)" title="View Family">
                                            <i class="fas fa-users"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteUser(<?= $user['id']; ?>)" title="Delete User">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <!-- Family expandable row -->
                                <tr id="familyRow-<?= $user['id']; ?>" class="family-row" style="display:none;">
                                    <td colspan="10">
                                        <div class="p-3 bg-light border rounded">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="mb-0"><i class="fas fa-users me-2 text-secondary"></i>Family Members</h6>
                                                <button class="btn btn-outline-secondary btn-sm" onclick="openAddFamilyMemberForm(<?= $user['id']; ?>)">
                                                    <i class="fas fa-user-plus"></i> Add Member
                                                </button>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-hover mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Relation</th>
                                                            <th>Name</th>
                                                            <th>Age</th>
                                                            <th>Gender</th>
                                                            <th>Phone</th>
                                                            <th>Email</th>
                                                            <th>Village</th>
                                                            <th>Mosal</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php if (!empty($user['family_members'])): ?>
                                                            <?php foreach ($user['family_members'] as $member): ?>
                                                                <tr>
                                                                    <td><?= htmlspecialchars($member['relationship'] ?? '-') ?></td>
                                                                    <td><?= htmlspecialchars(($member['first_name'] ?? $member['name'] ?? '') . ' ' . ($member['last_name'] ?? '')) ?></td>
                                                                    <td><?php $birthYear = $member['birth_year'] ?? null; echo ($birthYear ? (date('Y') - $birthYear) : '-'); ?></td>
                                                                    <td><?= htmlspecialchars($member['gender'] ?? '-') ?></td>
                                                                    <td><?= htmlspecialchars($member['phone_e164'] ?? '-') ?></td>
                                                                    <td><?= htmlspecialchars($member['email'] ?? '-') ?></td>
                                                                    <td><?= htmlspecialchars($member['village'] ?? '-') ?></td>
                                                                    <td><?= htmlspecialchars($member['mosal'] ?? '-') ?></td>
                                                                     <td>
                                                                        <button class="btn btn-edit btn-sm" onclick="openEditFamilyMemberForm(<?= $member['id']; ?>, <?= $user['id']; ?>)"><i class="fas fa-edit"></i></button>
                                                                        <button class="btn btn-delete btn-sm ms-1" onclick="deleteFamilyMember(<?= $member['id']; ?>)"><i class="fas fa-trash"></i></button>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <tr><td colspan="11" class="text-muted text-center">No family members found.</td></tr>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <!-- Add Family Member Form -->
                                            <div id="addFamilyForm-<?= $user['id']; ?>" class="p-3 bg-light border rounded mt-3 d-none">
                                                <h6 class="mb-3"><i class="fas fa-user-plus"></i> Add Family Member</h6>
                                                <form onsubmit="addFamilyMemberSubmit(event, <?= $user['id']; ?>)">
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold">First Name *</label>
                                                            <input type="text" name="first_name" class="form-control" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold">Last Name</label>
                                                            <input type="text" name="last_name" class="form-control">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold">Birth Year</label>
                                                            <input type="text" name="birth_year" class="form-control" placeholder="YYYY" pattern="\d{4}" inputmode="numeric" maxlength="4">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold">Gender</label>
                                                            <select name="gender" class="form-select">
                                                                <option value="male">Male</option>
                                                                <option value="female">Female</option>
                                                                <option value="other">Other</option>
                                                                <option value="prefer_not_say">Prefer not</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold">Phone</label>
                                                            <input type="text" name="phone" class="form-control">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold">Email</label>
                                                            <input type="email" name="email" class="form-control">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold">Village (Vatan)</label>
                                                            <input type="text" name="village" class="form-control">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold">Mosal</label>
                                                            <input type="text" name="mosal" class="form-control">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold">Relationship *</label>
                                                            <select name="relationship" class="form-select" required>
                                                                <option value="">Select...</option>
                                                                <option value="spouse">Spouse</option>
                                                                <option value="child">Child</option>
                                                                <option value="mother">Mother</option>
                                                                <option value="father">Father</option>
                                                                <option value="sibling">Sibling</option>
                                                                <option value="brother">Brother</option>
                                                                <option value="sister">Sister</option>
                                                                <option value="father-in-law">Father-in-law</option>
                                                                <option value="mother-in-law">Mother-in-law</option>
                                                                <option value="other">Other</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <button type="submit" class="btn btn-primary mt-3">Add Member</button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php }
                        } ?>
                    </tbody>
                </table>
            </div>

            <!-- Bulk actions -->
            <div class="d-flex justify-content-between align-items-center mt-3" id="bulkActions" style="display:none;">
                <span id="selectedCount" class="text-muted">0 users selected</span>
                <div class="btn-group">
                    <button class="btn btn-outline-primary btn-sm" onclick="bulkAction('activate')"><i class="fas fa-user-check me-1"></i>Activate</button>
                    <button class="btn btn-outline-warning btn-sm" onclick="bulkAction('deactivate')"><i class="fas fa-user-times me-1"></i>Deactivate</button>
                    <button class="btn btn-outline-danger btn-sm" onclick="bulkAction('delete')"><i class="fas fa-trash me-1"></i>Delete</button>
                </div>
            </div>
        </div>


    </div><!-- tab-content -->
</div><!-- card-body -->
</div><!-- card -->


<!-- User Details Modal -->


<script>
// ========== SEARCH ==========
['All','Active','Inactive','Admin'].forEach(tab => {
    const input = document.getElementById(`search${tab}Users`);
    if (!input) return;
    input.addEventListener('input', () => {
        const term = input.value.toLowerCase();
        const rows = document.querySelectorAll(`#${tab.toLowerCase()}UsersTable tbody tr`);
        rows.forEach(r => r.style.display = r.textContent.toLowerCase().includes(term) ? '' : 'none');
    });
});

// ========== BULK SELECT ==========
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = this.checked);
    updateBulkActions();
});
document.addEventListener('change', e => { if (e.target.classList.contains('user-checkbox')) updateBulkActions(); });
function updateBulkActions() {
    const selected = document.querySelectorAll('.user-checkbox:checked').length;
    document.getElementById('bulkActions').style.display = selected ? 'flex' : 'none';
    document.getElementById('selectedCount').textContent = `${selected} user${selected>1?'s':''} selected`;
}

// ========== FILTERS ==========
function filterUsers(filter) {
    const rows = document.querySelectorAll('#allUsersTable tbody tr');
    rows.forEach(row => {
        if (!row.dataset.userId) return;
        const status = row.dataset.status, role = row.dataset.role;
        let show = true;
        switch (filter) {
            case 'active': show = (status === 'active'); break;
            case 'inactive': show = (status === 'inactive'); break;
            case 'admin': show = (role === 'admin'); break;
            case 'all': default: show = true;
        }
        row.style.display = show ? '' : 'none';
        const fam = document.getElementById(`familyRow-${row.dataset.userId}`);
        if (fam) fam.style.display = 'none';
    });
}

// ========== FAMILY VIEW ==========
async function toggleFamily(userId) {
    const row = document.getElementById(`familyRow-${userId}`);
    if (row.style.display === "none") {
        row.style.display = "";
    } else {
        row.style.display = "none";
    }
}

    function addFamilyMember(userId) {
        // Reveal the inline add form for the user row (exists in the markup)
        const form = document.getElementById(`addFamilyForm-${userId}`);
        if (form) {
            form.style.display = 'block';
            // hide the show button if present
            const btn = form.closest('.p-3')?.querySelector('button');
            if (btn) btn.style.display = 'none';
            return;
        }
        // fallback to opening a modal form (if available)
        fetch(`/get-family-member-form?action=add`)
            .then(res => res.text())
            .then(html => {
                document.getElementById('userDetailsContent').innerHTML = html;
                new bootstrap.Modal(document.getElementById('userDetailsModal')).show();
            });
    }
    
    // Submit handler for inline Add Family Member form in admin users page
    function addFamilyMemberSubmit(event, userId) {
        event.preventDefault();
        const form = event.target;
        const data = {};
        new FormData(form).forEach((value, key) => {
            data[key] = value;
        });
        // Ensure the payload specifies user_id to add member for selected user
        data.user_id = userId;

        fetch('/add-family-member', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(json => {
            if (json && json.success) {
                // Reload to show updated family members. Could be optimized to update DOM instead.
                location.reload();
            } else {
                alert('Failed to add family member: ' + (json.error || json.message || 'Unknown error'));
            }
        })
        .catch(err => {
            console.error('Add member failed', err);
            alert('Error adding family member');
        });
    }
    function editFamilyMember(memberId) {
        // Fetch unified member form for edit and show as modal
        fetch(`/get-family-member-form?action=edit&id=${memberId}`)
            .then(res => res.text())
            .then(html => {
                document.getElementById('userDetailsContent').innerHTML = html;
                new bootstrap.Modal(document.getElementById('userDetailsModal')).show();
            });
    }
    function deleteFamilyMember(memberId) {
        if (!confirm("Delete this family member?")) return;
        fetch('/delete-family-member', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: memberId })
        })
        .then(res => res.json())
        .then(json => {
            if (json && json.success) {
                location.reload();
            } else {
                alert('Failed to delete family member: ' + (json.error || json.message || 'Unknown error'));
            }
        })
        .catch(err => {
            console.error('Delete family member failed', err);
            alert('Error deleting family member');
        });
    }

// ========== BULK ACTIONS ==========
function bulkAction(action) {
    const ids = Array.from(document.querySelectorAll('.user-checkbox:checked')).map(cb => cb.value);
    if (!ids.length) return alert('Please select users first');
    if (confirm(`Are you sure you want to ${action} ${ids.length} user(s)?`)) {
        alert(`${action} action on users: ${ids.join(', ')}`); // Replace with real AJAX
    }
}

// ========== VIEW USER DETAILS ==========
function viewUserDetails(id) {
    fetch(`/admin/user-details/${id}`)
        .then(r => r.text())
        .then(html => {
            document.getElementById('userDetailsContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('userDetailsModal')).show();
        });
}

    // Remove user via admin route (GET). JS will reload the page on success.
    function deleteUser(id) {
        if (!confirm('Delete this user? This action is irreversible.')) return;
        fetch(`/admin/user/delete/${id}`, { method: 'GET' })
        .then(res => {
            // Attempt to force a reload to let server-side redirect complete
            location.reload();
        })
        .catch(err => {
            console.error('Delete user failed', err);
            alert('Failed to delete user');
        });
    }
</script>

<!-- Admin User Management Modal -->
<div class="modal fade" id="formModal" tabindex="-1" aria-labelledby="formModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="formModalLabel">User Form</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="formModalBody">
                <!-- Form will be loaded here dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="formModalSaveBtn">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Load admin-users.js for modal handling -->
<script src="/assets/js/admin-users.js"></script>
