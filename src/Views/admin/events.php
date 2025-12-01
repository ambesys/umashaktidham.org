<?php
$pageTitle = 'Manage Events - Admin';
?>

<div class="page-heading">
    <div class="container">
        <h1><i class="fas fa-calendar-alt text-muted me-2"></i> Event Management</h1>
        <p>Create, manage, and monitor community events.</p>
    </div>
</div>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-end align-items-center mb-4">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createEventModal">
            <i class="fas fa-plus me-2"></i>Create New Event
        </button>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="avatar mx-auto mb-3" style="width: 50px; height: 50px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-calendar-check fa-lg text-muted"></i>
                    </div>
                    <h4 class="mb-1"><?php echo $stats['total_events'] ?? 0; ?></h4>
                    <small class="text-muted">Total Events</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="avatar mx-auto mb-3" style="width: 50px; height: 50px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-users fa-lg text-muted"></i>
                    </div>
                    <h4 class="mb-1"><?php echo $stats['total_registrations'] ?? 0; ?></h4>
                    <small class="text-muted">Total Registrations</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="avatar mx-auto mb-3" style="width: 50px; height: 50px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-clock fa-lg text-muted"></i>
                    </div>
                    <h4 class="mb-1"><?php echo $stats['upcoming_events'] ?? 0; ?></h4>
                    <small class="text-muted">Upcoming Events</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="avatar mx-auto mb-3" style="width: 50px; height: 50px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-dollar-sign fa-lg text-muted"></i>
                    </div>
                    <h4 class="mb-1">$<?php echo number_format($stats['total_revenue'] ?? 0, 0); ?></h4>
                    <small class="text-muted">Total Revenue</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content with Tabs -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0">
            <ul class="nav nav-tabs card-header-tabs" id="eventTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="all-events-tab" data-bs-toggle="tab" data-bs-target="#all-events" type="button" role="tab">
                        <i class="fas fa-list me-2"></i>All Events
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="upcoming-events-tab" data-bs-toggle="tab" data-bs-target="#upcoming-events" type="button" role="tab">
                        <i class="fas fa-clock me-2"></i>Upcoming
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="past-events-tab" data-bs-toggle="tab" data-bs-target="#past-events" type="button" role="tab">
                        <i class="fas fa-history me-2"></i>Past Events
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="registrations-tab" data-bs-toggle="tab" data-bs-target="#registrations" type="button" role="tab">
                        <i class="fas fa-users me-2"></i>Registrations
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body">
            <div class="tab-content" id="eventTabsContent">
                <!-- All Events Tab -->
                <div class="tab-pane fade show active" id="all-events" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">All Events</h5>
                        <div class="d-flex gap-2">
                            <input type="text" class="form-control form-control-sm" id="searchEvents" placeholder="Search events..." style="width: 250px;">
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-filter me-1"></i>Status
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="filterEvents('all')">All Events</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="filterEvents('upcoming')">Upcoming</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="filterEvents('past')">Past Events</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="filterEvents('draft')">Draft</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover" id="eventsTable">
                            <thead class="table-light">
                                <tr>
                                    <th><input type="checkbox" class="form-check-input" id="selectAllEvents"></th>
                                    <th>Event</th>
                                    <th>Date & Time</th>
                                    <th>Location</th>
                                    <th>Capacity</th>
                                    <th>Registrations</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($events) && is_array($events)): ?>
                                    <?php foreach ($events as $event): ?>
                                        <tr data-event-id="<?php echo $event['id']; ?>">
                                            <td><input type="checkbox" class="form-check-input event-checkbox" value="<?php echo $event['id']; ?>"></td>
                                            <td>
                                                <div>
                                                    <div class="fw-semibold"><?php echo htmlspecialchars($event['title']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars(substr($event['description'] ?? '', 0, 50)); ?>...</small>
                                                </div>
                                            </td>
                                            <td>
                                                <div><?php echo date('M j, Y', strtotime($event['event_date'])); ?></div>
                                                <small class="text-muted"><?php echo date('g:i A', strtotime($event['event_date'])); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($event['location'] ?? 'TBD'); ?></td>
                                            <td>
                                                <?php
                                                $capacity = $event['max_capacity'] ?? 0;
                                                $registrations = $event['registration_count'] ?? 0;
                                                if ($capacity > 0) {
                                                    $percentage = ($registrations / $capacity) * 100;
                                                    echo "<div class='text-center'>{$registrations}/{$capacity}</div>";
                                                    echo "<div class='progress mt-1' style='height: 4px;'>";
                                                    echo "<div class='progress-bar bg-" . ($percentage > 80 ? 'danger' : ($percentage > 60 ? 'warning' : 'success')) . "' style='width: {$percentage}%'></div>";
                                                    echo "</div>";
                                                } else {
                                                    echo 'Unlimited';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo $event['registration_count'] ?? 0; ?></td>
                                            <td>
                                                <?php
                                                $eventDate = strtotime($event['event_date']);
                                                $now = time();
                                                if ($eventDate > $now) {
                                                    echo '<span class="badge bg-success">Upcoming</span>';
                                                } else {
                                                    echo '<span class="badge bg-secondary">Past</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" title="Edit" onclick="editEvent(<?php echo $event['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-info" title="View Details" onclick="viewEventDetails(<?php echo $event['id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-success" title="Registrations" onclick="viewRegistrations(<?php echo $event['id']; ?>)">
                                                        <i class="fas fa-users"></i>
                                                    </button>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                            <i class="fas fa-ellipsis-h"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li><a class="dropdown-item" href="#" onclick="duplicateEvent(<?php echo $event['id']; ?>)"><i class="fas fa-copy me-2"></i>Duplicate</a></li>
                                                            <li><a class="dropdown-item" href="#" onclick="exportRegistrations(<?php echo $event['id']; ?>)"><i class="fas fa-download me-2"></i>Export List</a></li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li><a class="dropdown-item text-danger" href="#" onclick="deleteEvent(<?php echo $event['id']; ?>)"><i class="fas fa-trash me-2"></i>Delete Event</a></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="fas fa-calendar-times fa-3x mb-3"></i>
                                                <h5>No events found</h5>
                                                <p>Create your first event to get started</p>
                                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createEventModal">
                                                    <i class="fas fa-plus me-2"></i>Create Event
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Upcoming Events Tab -->
                <div class="tab-pane fade" id="upcoming-events" role="tabpanel">
                    <div class="alert alert-info">
                        <i class="fas fa-clock me-2"></i>Upcoming events will be displayed here.
                    </div>
                </div>

                <!-- Past Events Tab -->
                <div class="tab-pane fade" id="past-events" role="tabpanel">
                    <div class="alert alert-secondary">
                        <i class="fas fa-history me-2"></i>Past events will be displayed here.
                    </div>
                </div>

                <!-- Registrations Tab -->
                <div class="tab-pane fade" id="registrations" role="tabpanel">
                    <div class="alert alert-success">
                        <i class="fas fa-users me-2"></i>Event registrations will be displayed here.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Event Modal -->
<div class="modal fade" id="createEventModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-calendar-plus me-2"></i>Create New Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="eventForm">
                    <input type="hidden" id="eventId" name="id">

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="eventTitle" class="form-label fw-semibold">Event Title *</label>
                            <input type="text" class="form-control" id="eventTitle" name="title" required>
                        </div>

                        <div class="col-md-6">
                            <label for="eventDate" class="form-label fw-semibold">Event Date & Time *</label>
                            <input type="datetime-local" class="form-control" id="eventDate" name="event_date" required>
                        </div>

                        <div class="col-md-6">
                            <label for="eventLocation" class="form-label fw-semibold">Location</label>
                            <input type="text" class="form-control" id="eventLocation" name="location">
                        </div>

                        <div class="col-md-6">
                            <label for="maxCapacity" class="form-label fw-semibold">Maximum Capacity</label>
                            <input type="number" class="form-control" id="maxCapacity" name="max_capacity" min="1" placeholder="Unlimited if empty">
                        </div>

                        <div class="col-md-6">
                            <label for="registrationDeadline" class="form-label fw-semibold">Registration Deadline</label>
                            <input type="datetime-local" class="form-control" id="registrationDeadline" name="registration_deadline">
                        </div>

                        <div class="col-12">
                            <label for="eventDescription" class="form-label fw-semibold">Description</label>
                            <textarea class="form-control" id="eventDescription" name="description" rows="4"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" form="eventForm">Save Event</button>
            </div>
        </div>
    </div>
</div>

<!-- Event Details Modal -->
<div class="modal fade" id="eventDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-calendar-alt me-2"></i>Event Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="eventDetailsContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchEvents')?.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#eventsTable tbody tr');

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Bulk selection
document.getElementById('selectAllEvents')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.event-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
});

function editEvent(eventId) {
    // Load event data and show modal
    fetch(`/admin/event/${eventId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('eventId').value = data.id;
            document.getElementById('eventTitle').value = data.title;
            document.getElementById('eventDate').value = data.event_date;
            document.getElementById('eventLocation').value = data.location;
            document.getElementById('maxCapacity').value = data.max_capacity;
            document.getElementById('registrationDeadline').value = data.registration_deadline;
            document.getElementById('eventDescription').value = data.description;

            document.querySelector('#createEventModal .modal-title').textContent = 'Edit Event';
            new bootstrap.Modal(document.getElementById('createEventModal')).show();
        });
}

function viewEventDetails(eventId) {
    // Load event details
    fetch(`/admin/event-details/${eventId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('eventDetailsContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('eventDetailsModal')).show();
        });
}

function viewRegistrations(eventId) {
    // Switch to registrations tab and filter
    document.getElementById('registrations-tab').click();
    // Implement filtering logic
}

function deleteEvent(eventId) {
    if (confirm('Are you sure you want to delete this event? This action cannot be undone.')) {
        fetch(`/admin/delete-event/${eventId}`, { method: 'DELETE' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error deleting event');
                }
            });
    }
}

function duplicateEvent(eventId) {
    if (confirm('Create a copy of this event?')) {
        fetch(`/admin/duplicate-event/${eventId}`, { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error duplicating event');
                }
            });
    }
}

function filterEvents(filter) {
    // Implement filtering logic
    alert(`Filtering by: ${filter}`);
}

// Form submission
document.getElementById('eventForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const eventId = formData.get('id');

    fetch(eventId ? `/admin/update-event/${eventId}` : '/admin/create-event', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('createEventModal')).hide();
            location.reload();
        } else {
            alert('Error saving event');
        }
    });
});
</script>
                <button id="edit-event-btn" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit Event
                </button>
                <button id="view-registrations-btn" class="btn btn-secondary">
                    <i class="fas fa-users"></i> View Registrations
                </button>
                <button class="btn btn-secondary close-modal">Close</button>
            </div>
        </div>
    </div>

    <!-- Registrations Modal -->
    <div id="registrations-modal" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="registrations-modal-title">Event Registrations</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="event-registrations-content">
                        <!-- Registrations will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/admin-events.js"></script>