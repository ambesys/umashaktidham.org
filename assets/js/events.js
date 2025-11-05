/**
 * Events JavaScript
 * Handles event listing, registration modal, and API interactions
 */

class EventsManager {
    constructor() {
        this.events = [];
        this.currentEvent = null;
        this.selectedTicket = null;
        this.couponDiscount = 0;
        this.init();
    }

    init() {
        this.loadEvents();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Modal close buttons
        document.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', () => this.closeModal());
        });

        // Click outside modal to close
        document.getElementById('registration-modal').addEventListener('click', (e) => {
            if (e.target.id === 'registration-modal') {
                this.closeModal();
            }
        });

        // Registration form
        document.getElementById('registration-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitRegistration();
        });

        // Ticket selection
        document.getElementById('ticket-select').addEventListener('change', (e) => {
            this.selectTicket(e.target.value);
        });

        // Guest count change
        document.getElementById('guest-count').addEventListener('input', (e) => {
            this.updateGuestCount(e.target.value);
        });

        // Apply coupon
        document.getElementById('apply-coupon').addEventListener('click', () => {
            this.applyCoupon();
        });
    }

    async loadEvents() {
        try {
            this.showLoading();

            const response = await fetch('/api/events');
            const data = await response.json();

            if (data.success) {
                this.events = data.events;
                this.renderEvents();
            } else {
                this.showError(data.error || 'Failed to load events');
            }
        } catch (error) {
            this.showError('Network error while loading events');
            console.error('Events loading error:', error);
        } finally {
            this.hideLoading();
        }
    }

    renderEvents() {
        const container = document.getElementById('events-list');

        if (this.events.length === 0) {
            container.innerHTML = '<p class="no-events">No upcoming events found.</p>';
            return;
        }

        container.innerHTML = this.events.map(event => `
            <div class="event-card">
                <div class="event-header">
                    <h3>${this.escapeHtml(event.title)}</h3>
                    <div class="event-date">
                        <i class="fas fa-calendar"></i>
                        ${this.formatDate(event.event_date)}
                    </div>
                </div>

                <div class="event-details">
                    <p class="event-description">${this.escapeHtml(event.description || 'No description available')}</p>

                    ${event.location ? `
                        <div class="event-location">
                            <i class="fas fa-map-marker-alt"></i>
                            ${this.escapeHtml(event.location)}
                        </div>
                    ` : ''}

                    <div class="event-capacity">
                        <i class="fas fa-users"></i>
                        ${event.registration_count || 0} registered
                        ${event.max_capacity ? ` / ${event.max_capacity} capacity` : ''}
                    </div>

                    ${event.registration_deadline ? `
                        <div class="event-deadline">
                            <i class="fas fa-clock"></i>
                            Registration deadline: ${this.formatDate(event.registration_deadline)}
                        </div>
                    ` : ''}
                </div>

                <div class="event-actions">
                    <button class="btn-primary register-btn" data-event-id="${event.id}">
                        Register Now
                    </button>
                    <button class="btn-secondary view-details-btn" data-event-id="${event.id}">
                        View Details
                    </button>
                </div>
            </div>
        `).join('');

        // Add event listeners to buttons
        container.querySelectorAll('.register-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const eventId = e.target.dataset.eventId;
                this.openRegistrationModal(eventId);
            });
        });

        container.querySelectorAll('.view-details-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const eventId = e.target.dataset.eventId;
                this.viewEventDetails(eventId);
            });
        });
    }

    async openRegistrationModal(eventId) {
        const event = this.events.find(e => e.id == eventId);
        if (!event) return;

        this.currentEvent = event;

        // Check if user is authenticated
        const isAuthenticated = await this.checkAuthentication();
        if (!isAuthenticated) {
            alert('Please log in to register for events.');
            window.location.href = '/auth/login';
            return;
        }

        // Check if already registered
        const isRegistered = await this.checkRegistrationStatus(eventId);
        if (isRegistered) {
            alert('You are already registered for this event.');
            return;
        }

        // Set modal title
        document.getElementById('modal-event-title').textContent = `Register for ${event.title}`;

        // Load event details including tickets and coupons
        await this.loadEventDetails(eventId);

        // Reset form
        this.resetRegistrationForm();

        // Show modal
        document.getElementById('registration-modal').style.display = 'block';
    }

    async loadEventDetails(eventId) {
        try {
            const response = await fetch(`/api/events/${eventId}`);
            const data = await response.json();

            if (data.success) {
                this.currentEvent = data.event;

                // Populate ticket options
                const ticketSelect = document.getElementById('ticket-select');
                ticketSelect.innerHTML = '<option value="">Choose a ticket type...</option>';

                if (data.event.tickets && data.event.tickets.length > 0) {
                    data.event.tickets.forEach(ticket => {
                        const option = document.createElement('option');
                        option.value = ticket.id;
                        option.textContent = `${ticket.name} - $${ticket.price}`;
                        option.dataset.price = ticket.price;
                        ticketSelect.appendChild(option);
                    });
                } else {
                    ticketSelect.innerHTML = '<option value="">No tickets available</option>';
                }
            }
        } catch (error) {
            console.error('Error loading event details:', error);
        }
    }

    selectTicket(ticketId) {
        if (!ticketId) {
            this.selectedTicket = null;
            this.updatePricing();
            return;
        }

        const ticketOption = document.querySelector(`#ticket-select option[value="${ticketId}"]`);
        if (ticketOption) {
            this.selectedTicket = {
                id: ticketId,
                price: parseFloat(ticketOption.dataset.price) || 0
            };
            this.updatePricing();
        }
    }

    updateGuestCount(count) {
        this.updatePricing();
    }

    updatePricing() {
        const guestCount = parseInt(document.getElementById('guest-count').value) || 0;
        const ticketPrice = this.selectedTicket ? this.selectedTicket.price : 0;
        const guestsPrice = guestCount * ticketPrice;
        const subtotal = ticketPrice + guestsPrice;
        const total = subtotal - this.couponDiscount;

        // Update display
        document.getElementById('ticket-price').textContent = `$${ticketPrice.toFixed(2)}`;
        document.getElementById('guest-count-display').textContent = guestCount;
        document.getElementById('guests-price').textContent = `$${guestsPrice.toFixed(2)}`;
        document.getElementById('total-price').textContent = `$${total.toFixed(2)}`;

        // Show/hide discount
        const discountItem = document.getElementById('discount-item');
        const discountAmount = document.getElementById('discount-amount');

        if (this.couponDiscount > 0) {
            discountItem.style.display = 'flex';
            discountAmount.textContent = `-$${this.couponDiscount.toFixed(2)}`;
        } else {
            discountItem.style.display = 'none';
        }
    }

    async applyCoupon() {
        const couponCode = document.getElementById('coupon-code').value.trim();
        if (!couponCode) {
            alert('Please enter a coupon code.');
            return;
        }

        try {
            // For now, we'll validate against available coupons
            // In a real implementation, this might be done server-side
            const availableCoupons = this.currentEvent.coupons || [];
            const coupon = availableCoupons.find(c => c.code.toLowerCase() === couponCode.toLowerCase());

            if (coupon && coupon.is_active) {
                this.couponDiscount = parseFloat(coupon.discount_amount) || 0;
                this.updatePricing();
                alert(`Coupon applied! $${this.couponDiscount.toFixed(2)} discount.`);
            } else {
                alert('Invalid or expired coupon code.');
                this.couponDiscount = 0;
                this.updatePricing();
            }
        } catch (error) {
            console.error('Error applying coupon:', error);
            alert('Error applying coupon. Please try again.');
        }
    }

    async submitRegistration() {
        if (!this.currentEvent || !this.selectedTicket) {
            alert('Please select a ticket type.');
            return;
        }

        const formData = new FormData(document.getElementById('registration-form'));
        const guestCount = parseInt(formData.get('guest_count')) || 0;
        const ticketPrice = this.selectedTicket.price;
        const totalAmount = (1 + guestCount) * ticketPrice;

        const registrationData = {
            ticket_id: this.selectedTicket.id,
            guest_count: guestCount,
            coupon_id: null, // Would be set if coupon validation was successful
            total_amount: totalAmount
        };

        try {
            const response = await fetch(`/api/events/${this.currentEvent.id}/register`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(registrationData)
            });

            const data = await response.json();

            if (data.success) {
                alert('Registration successful!');
                this.closeModal();
                // Reload events to update registration counts
                this.loadEvents();
            } else {
                alert(data.error || 'Registration failed. Please try again.');
            }
        } catch (error) {
            console.error('Registration error:', error);
            alert('Network error. Please try again.');
        }
    }

    async checkAuthentication() {
        // This would typically check a session or token
        // For now, we'll assume authentication status
        return true; // Placeholder
    }

    async checkRegistrationStatus(eventId) {
        try {
            const response = await fetch('/api/events/my-registrations');
            const data = await response.json();

            if (data.success) {
                return data.registrations.some(reg => reg.event_id == eventId);
            }
        } catch (error) {
            console.error('Error checking registration status:', error);
        }
        return false;
    }

    viewEventDetails(eventId) {
        // For now, just show an alert. Could open a detailed modal or navigate to detail page
        const event = this.events.find(e => e.id == eventId);
        if (event) {
            alert(`${event.title}\n\n${event.description}\n\nDate: ${this.formatDate(event.event_date)}\nLocation: ${event.location || 'TBD'}`);
        }
    }

    resetRegistrationForm() {
        document.getElementById('registration-form').reset();
        this.selectedTicket = null;
        this.couponDiscount = 0;
        this.updatePricing();
    }

    closeModal() {
        document.getElementById('registration-modal').style.display = 'none';
        this.resetRegistrationForm();
    }

    showLoading() {
        document.getElementById('events-loading').style.display = 'block';
    }

    hideLoading() {
        document.getElementById('events-loading').style.display = 'none';
    }

    showError(message) {
        const errorDiv = document.getElementById('events-error');
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new EventsManager();
});