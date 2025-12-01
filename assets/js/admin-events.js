/**
 * Admin Events JavaScript
 * Handles admin event management, creation, editing, and registration viewing
 */

class AdminEventsManager {
    constructor() {
        this.events = [];
        this.currentEvent = null;
        this.init();
    }

    init() {
        this.loadEvents();
        this.loadRecentRegistrations();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Create event button
        document.getElementById('create-event-btn').addEventListener('click', () => {
            this.openCreateEventModal();
        });

        // Event form submission
        document.getElementById('event-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveEvent();
        });

        // Edit event button
        document.getElementById('edit-event-btn').addEventListener('click', () => {
            this.openEditEventModal();
        });

        // View registrations button
        document.getElementById('view-registrations-btn').addEventListener('click', () => {
            this.openRegistrationsModal();
        });
    }

    async loadEvents() {
        try {
            this.showLoading('events-loading');

            const response = await fetch('/api/events');
            const data = await response.json();

            if (data.success) {
                this.events = data.events;
                this.renderEvents();
            } else {
                this.showError('Failed to load events', 'events-list');
            }
        } catch (error) {
            this.showError('Network error while loading events', 'events-list');
            console.error('Events loading error:', error);
        } finally {
            this.hideLoading('events-loading');
        }
    }

    renderEvents() {
        const container = document.getElementById('events-list');

        if (this.events.length === 0) {
            container.innerHTML = '<p class="no-data">No events found. Create your first event!</p>';
            return;
        }

        container.innerHTML = this.events.map(event => `
            <div class="event-admin-card">
                <div class="event-admin-header">
                    <h3>${this.escapeHtml(event.title)}</h3>
                    <div class="event-admin-status">
                        <span class="status-badge ${this.getStatusClass(event)}">${this.getStatusText(event)}</span>
                    </div>
                </div>

                <div class="event-admin-details">
                    <div class="detail-row">
                        <i class="fas fa-calendar"></i>
                        <span>${this.formatDate(event.event_date)}</span>
                    </div>

                    ${event.location ? `
                        <div class="detail-row">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>${this.escapeHtml(event.location)}</span>
                        </div>
                    ` : ''}

                    <div class="detail-row">
                        <i class="fas fa-users"></i>
                        <span>${event.registration_count || 0} registered${event.max_capacity ? ` / ${event.max_capacity}` : ''}</span>
                    </div>

                    ${event.registration_deadline ? `
                        <div class="detail-row">
                            <i class="fas fa-clock"></i>
                            <span>Deadline: ${this.formatDate(event.registration_deadline)}</span>
                        </div>
                    ` : ''}
                </div>

                <div class="event-admin-actions">
                    <button class="btn btn-sm btn-primary view-details-btn" data-event-id="${event.id}">
                        <i class="fas fa-eye"></i> Details
                    </button>
                    <button class="btn btn-sm btn-secondary edit-event-btn" data-event-id="${event.id}">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-sm btn-danger delete-event-btn" data-event-id="${event.id}">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        `).join('');

        // Add event listeners
        container.querySelectorAll('.view-details-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const eventId = e.target.closest('button').dataset.eventId;
                this.viewEventDetails(eventId);
            });
        });

        container.querySelectorAll('.edit-event-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const eventId = e.target.closest('button').dataset.eventId;
                this.editEvent(eventId);
            });
        });

        container.querySelectorAll('.delete-event-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const eventId = e.target.closest('button').dataset.eventId;
                this.deleteEvent(eventId);
            });
        });
    }

    async loadRecentRegistrations() {
        try {
            this.showLoading('registrations-loading');

            // Load all events and their registrations
            const allRegistrations = [];

            for (const event of this.events.slice(0, 5)) { // Limit to first 5 events
                try {
                    const response = await fetch(`/api/events/${event.id}/registrations`);
                    const data = await response.json();

                    if (data.success && data.registrations.length > 0) {
                        data.registrations.forEach(reg => {
                            allRegistrations.push({
                                ...reg,
                                event_title: event.title
                            });
                        });
                    }
                } catch (error) {
                    console.error(`Error loading registrations for event ${event.id}:`, error);
                }
            }

            // Sort by creation date and take most recent
            allRegistrations.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
            const recentRegistrations = allRegistrations.slice(0, 10);

            this.renderRecentRegistrations(recentRegistrations);
        } catch (error) {
            this.showError('Network error while loading registrations', 'registrations-list');
            console.error('Registrations loading error:', error);
        } finally {
            this.hideLoading('registrations-loading');
        }
    }

    renderRecentRegistrations(registrations) {
        const container = document.getElementById('registrations-list');

        if (registrations.length === 0) {
            container.innerHTML = '<p class="no-data">No recent registrations found.</p>';
            return;
        }

        container.innerHTML = `
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Event</th>
                        <th>Guests</th>
                        <th>Registered</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${registrations.map(reg => `
                        <tr>
                            <td>${this.escapeHtml(reg.name || 'N/A')}</td>
                            <td>${this.escapeHtml(reg.event_title)}</td>
                            <td>${reg.guest_count || 0}</td>
                            <td>${this.formatDate(reg.registration_date)}</td>
                            <td>
                                <span class="status-badge ${reg.checked_in ? 'status-checked-in' : 'status-pending'}">
                                    ${reg.checked_in ? 'Checked In' : 'Pending'}
                                </span>
                            </td>
                            <td>
                                ${!reg.checked_in ? `
                                    <button class="btn btn-xs btn-success check-in-btn" data-registration-id="${reg.id}">
                                        <i class="fas fa-check"></i> Check In
                                    </button>
                                ` : ''}
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;

        // Add check-in event listeners
        container.querySelectorAll('.check-in-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const registrationId = e.target.closest('button').dataset.registrationId;
                this.checkInAttendee(registrationId);
            });
        });
    }

    openCreateEventModal() {
        document.getElementById('modal-title').textContent = 'Create New Event';
        document.getElementById('event-form').reset();
        document.getElementById('event-id').value = '';
        const modal = new bootstrap.Modal(document.getElementById('createEventModal'));
        modal.show();
    }

    async viewEventDetails(eventId) {
        const event = this.events.find(e => e.id == eventId);
        if (!event) return;

        this.currentEvent = event;

        const detailsContent = document.getElementById('event-details-content');
        detailsContent.innerHTML = `
            <div class="event-details-grid">
                <div class="detail-section">
                    <h3>Event Information</h3>
                    <div class="detail-item">
                        <strong>Title:</strong> ${this.escapeHtml(event.title)}
                    </div>
                    <div class="detail-item">
                        <strong>Description:</strong> ${this.escapeHtml(event.description || 'No description')}
                    </div>
                    <div class="detail-item">
                        <strong>Date:</strong> ${this.formatDate(event.event_date)}
                    </div>
                    <div class="detail-item">
                        <strong>Location:</strong> ${this.escapeHtml(event.location || 'TBD')}
                    </div>
                    <div class="detail-item">
                        <strong>Capacity:</strong> ${event.max_capacity || 'Unlimited'}
                    </div>
                    <div class="detail-item">
                        <strong>Registered:</strong> ${event.registration_count || 0}
                    </div>
                    ${event.registration_deadline ? `
                        <div class="detail-item">
                            <strong>Registration Deadline:</strong> ${this.formatDate(event.registration_deadline)}
                        </div>
                    ` : ''}
                </div>
            </div>
        `;

        document.getElementById('details-modal-title').textContent = `Details: ${event.title}`;
        const modal = new bootstrap.Modal(document.getElementById('eventDetailsModal'));
        modal.show();
    }

    async editEvent(eventId) {
        const event = this.events.find(e => e.id == eventId);
        if (!event) return;

        // Populate form
        document.getElementById('event-id').value = event.id;
        document.getElementById('event-title').value = event.title;
        document.getElementById('event-description').value = event.description || '';
        document.getElementById('event-location').value = event.location || '';
        document.getElementById('event-date').value = this.formatDateTimeForInput(event.event_date);
        document.getElementById('registration-deadline').value = event.registration_deadline ?
            this.formatDateTimeForInput(event.registration_deadline) : '';
        document.getElementById('max-capacity').value = event.max_capacity || '';

        document.getElementById('modal-title').textContent = 'Edit Event';
        const modal = new bootstrap.Modal(document.getElementById('createEventModal'));
        modal.show();
    }

    async saveEvent() {
        const formData = new FormData(document.getElementById('event-form'));
        const eventData = {
            title: formData.get('title'),
            description: formData.get('description'),
            event_date: formData.get('event_date'),
            location: formData.get('location'),
            max_capacity: formData.get('max_capacity') ? parseInt(formData.get('max_capacity')) : null,
            registration_deadline: formData.get('registration_deadline') || null
        };

        const eventId = formData.get('id');

        try {
            const url = eventId ? `/api/events/${eventId}` : '/api/events';
            const method = eventId ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(eventData)
            });

            const data = await response.json();

            if (data.success) {
                alert(`Event ${eventId ? 'updated' : 'created'} successfully!`);
                // Hide Bootstrap modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('createEventModal'));
                if (modal) modal.hide();
                this.loadEvents();
            } else {
                alert(data.error || 'Failed to save event');
            }
        } catch (error) {
            console.error('Save event error:', error);
            alert('Network error. Please try again.');
        }
    }

    async deleteEvent(eventId) {
        if (!confirm('Are you sure you want to delete this event? This action cannot be undone.')) {
            return;
        }

        try {
            // Note: We'd need to add a DELETE endpoint for this
            alert('Delete functionality not yet implemented');
        } catch (error) {
            console.error('Delete event error:', error);
            alert('Error deleting event');
        }
    }

    async openRegistrationsModal() {
        if (!this.currentEvent) return;

        try {
            const response = await fetch(`/api/events/${this.currentEvent.id}/registrations`);
            const data = await response.json();

            if (data.success) {
                this.renderRegistrationsModal(data.registrations);
                document.getElementById('registrations-modal-title').textContent =
                    `Registrations: ${this.currentEvent.title}`;
                const modal = new bootstrap.Modal(document.getElementById('registrations-modal'));
                modal.show();
            } else {
                alert('Failed to load registrations');
            }
        } catch (error) {
            console.error('Load registrations error:', error);
            alert('Network error loading registrations');
        }
    }

    renderRegistrationsModal(registrations) {
        const container = document.getElementById('event-registrations-content');

        if (registrations.length === 0) {
            container.innerHTML = '<p class="no-data">No registrations found for this event.</p>';
            return;
        }

        container.innerHTML = `
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Guests</th>
                        <th>Total Amount</th>
                        <th>Registered</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${registrations.map(reg => `
                        <tr>
                            <td>${this.escapeHtml(reg.name || 'N/A')}</td>
                            <td>${this.escapeHtml(reg.email || 'N/A')}</td>
                            <td>${reg.guest_count || 0}</td>
                            <td>$${parseFloat(reg.total_amount || 0).toFixed(2)}</td>
                            <td>${this.formatDate(reg.registration_date)}</td>
                            <td>
                                <span class="status-badge ${reg.checked_in ? 'status-checked-in' : 'status-pending'}">
                                    ${reg.checked_in ? 'Checked In' : 'Pending'}
                                </span>
                            </td>
                            <td>
                                ${!reg.checked_in ? `
                                    <button class="btn btn-xs btn-success check-in-btn" data-registration-id="${reg.id}">
                                        <i class="fas fa-check"></i> Check In
                                    </button>
                                ` : '<span class="text-muted">Checked In</span>'}
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;

        // Add check-in event listeners
        container.querySelectorAll('.check-in-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const registrationId = e.target.closest('button').dataset.registrationId;
                this.checkInAttendee(registrationId);
            });
        });
    }

    async checkInAttendee(registrationId) {
        try {
            const response = await fetch(`/api/events/registrations/${registrationId}/checkin`, {
                method: 'POST'
            });

            const data = await response.json();

            if (data.success) {
                alert('Attendee checked in successfully!');
                // Refresh data
                this.loadEvents();
                this.loadRecentRegistrations();
                // Refresh modal if open
                const modalElement = document.getElementById('registrations-modal');
                if (modalElement && modalElement.classList.contains('show')) {
                    this.openRegistrationsModal();
                }
            } else {
                alert(data.error || 'Failed to check in attendee');
            }
        } catch (error) {
            console.error('Check-in error:', error);
            alert('Network error during check-in');
        }
    }

    showLoading(elementId) {
        document.getElementById(elementId).style.display = 'block';
    }

    hideLoading(elementId) {
        document.getElementById(elementId).style.display = 'none';
    }

    showError(message, containerId) {
        const container = document.getElementById(containerId);
        container.innerHTML = `<div class="error-message">${message}</div>`;
    }

    getStatusClass(event) {
        const eventDate = new Date(event.event_date);
        const now = new Date();

        if (eventDate < now) {
            return 'status-past';
        } else if (event.registration_deadline && new Date(event.registration_deadline) < now) {
            return 'status-closed';
        } else {
            return 'status-active';
        }
    }

    getStatusText(event) {
        const eventDate = new Date(event.event_date);
        const now = new Date();

        if (eventDate < now) {
            return 'Past Event';
        } else if (event.registration_deadline && new Date(event.registration_deadline) < now) {
            return 'Registration Closed';
        } else {
            return 'Active';
        }
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    formatDateTimeForInput(dateString) {
        const date = new Date(dateString);
        return date.toISOString().slice(0, 16);
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new AdminEventsManager();
});