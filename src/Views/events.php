
<div class="page-heading">
    <div class="container">
        <h1><i class="fas fa-calendar-alt"></i> Upcoming Events</h1>
        <p>Browse upcoming events, cultural programs, and register for activities.</p>
    </div>
</div>

    <main class="container">
        <div class="events-section">
            
            <div id="events-loading" class="loading">
                <p>Loading events...</p>
            </div>

            <div id="events-error" class="error-message d-none"></div>

            <div id="events-list" class="events-grid">
                <!-- Events will be loaded here -->
            </div>
        </div>
    </main>

    <!-- Event Registration Modal -->
    <div id="registration-modal" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-event-title">Register for Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="registration-form">
                        <input type="hidden" id="registration-event-id" name="event_id">

                        <div class="form-group mb-3">
                            <label for="ticket-select" class="form-label">Select Ticket:</label>
                            <select id="ticket-select" name="ticket_id" class="form-select" required>
                                <option value="">Choose a ticket type...</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="guest-count" class="form-label">Number of Guests:</label>
                            <input type="number" id="guest-count" name="guest_count" min="0" max="10" value="0" class="form-control">
                            <small class="form-text text-muted">Note: You + guests cannot exceed event capacity</small>
                        </div>

                        <div class="form-group mb-3">
                            <label for="coupon-code" class="form-label">Coupon Code (optional):</label>
                            <div class="input-group">
                                <input type="text" id="coupon-code" name="coupon_code" placeholder="Enter coupon code" class="form-control">
                                <button type="button" id="apply-coupon" class="btn btn-outline-secondary">Apply</button>
                            </div>
                        </div>

                        <div id="pricing-summary" class="pricing-summary">
                            <h3>Pricing Summary</h3>
                            <div class="pricing-item">
                                <span>Ticket Price:</span>
                                <span id="ticket-price">$0.00</span>
                            </div>
                            <div class="pricing-item">
                                <span>Guests (<span id="guest-count-display">0</span>):</span>
                                <span id="guests-price">$0.00</span>
                            </div>
                            <div class="pricing-item d-none" id="discount-item">
                                <span>Discount:</span>
                                <span id="discount-amount">-$0.00</span>
                            </div>
                            <div class="pricing-total">
                                <strong>Total: <span id="total-price">$0.00</span></strong>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="registration-form" class="btn btn-primary">Register</button>
                </div>
            </div>
        </div>
    </div>
