<?php
$pageTitle = 'Manage Events - Admin';
?>

<div class="admin-events-page">
    <div class="container">
        <div class="admin-header">
            <h1><i class="fas fa-calendar-plus"></i> Manage Events</h1>
            <button id="create-event-btn" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create New Event
            </button>
        </div>

        <!-- Events List -->
        <div class="admin-section">
            <h2>Upcoming Events</h2>
            <div id="events-loading" class="loading">
                <p>Loading events...</p>
            </div>
            <div id="events-list" class="events-admin-grid">
                <!-- Events will be loaded here -->
            </div>
        </div>

        <!-- Event Registrations -->
        <div class="admin-section">
            <h2>Recent Registrations</h2>
            <div id="registrations-loading" class="loading">
                <p>Loading registrations...</p>
            </div>
            <div id="registrations-list" class="registrations-table">
                <!-- Registrations will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Create/Edit Event Modal -->
    <div id="event-modal" class="modal" style="display: none;">
        <div class="modal-content large-modal">
            <span class="close-modal">&times;</span>
            <h2 id="modal-title">Create New Event</h2>

            <form id="event-form">
                <input type="hidden" id="event-id" name="id">

                <div class="form-row">
                    <div class="form-group">
                        <label for="event-title">Event Title *</label>
                        <input type="text" id="event-title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="event-location">Location</label>
                        <input type="text" id="event-location" name="location">
                    </div>
                </div>

                <div class="form-group">
                    <label for="event-description">Description</label>
                    <textarea id="event-description" name="description" rows="4"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="event-date">Event Date *</label>
                        <input type="datetime-local" id="event-date" name="event_date" required>
                    </div>
                    <div class="form-group">
                        <label for="registration-deadline">Registration Deadline</label>
                        <input type="datetime-local" id="registration-deadline" name="registration_deadline">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="max-capacity">Maximum Capacity</label>
                        <input type="number" id="max-capacity" name="max_capacity" min="1">
                    </div>
                    <div class="form-group">
                        <!-- Spacer for alignment -->
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Event</button>
                    <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Event Details Modal -->
    <div id="event-details-modal" class="modal" style="display: none;">
        <div class="modal-content large-modal">
            <span class="close-modal">&times;</span>
            <h2 id="details-modal-title">Event Details</h2>

            <div id="event-details-content">
                <!-- Event details will be loaded here -->
            </div>

            <div class="modal-actions">
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
    <div id="registrations-modal" class="modal" style="display: none;">
        <div class="modal-content large-modal">
            <span class="close-modal">&times;</span>
            <h2 id="registrations-modal-title">Event Registrations</h2>

            <div id="event-registrations-content">
                <!-- Registrations will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/admin-events.js"></script>