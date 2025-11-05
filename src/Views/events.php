<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - Uma Shakti Dham</title>
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
    <?php include __DIR__ . '/layouts/header.php'; ?>

    <main class="container">
        <div class="events-section">
            <h1><i class="fas fa-calendar-alt"></i> Upcoming Events</h1>

            <div id="events-loading" class="loading">
                <p>Loading events...</p>
            </div>

            <div id="events-error" class="error-message" style="display: none;"></div>

            <div id="events-list" class="events-grid">
                <!-- Events will be loaded here -->
            </div>
        </div>
    </main>

    <!-- Event Registration Modal -->
    <div id="registration-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2 id="modal-event-title">Register for Event</h2>

            <form id="registration-form">
                <input type="hidden" id="registration-event-id" name="event_id">

                <div class="form-group">
                    <label for="ticket-select">Select Ticket:</label>
                    <select id="ticket-select" name="ticket_id" required>
                        <option value="">Choose a ticket type...</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="guest-count">Number of Guests:</label>
                    <input type="number" id="guest-count" name="guest_count" min="0" max="10" value="0">
                    <small>Note: You + guests cannot exceed event capacity</small>
                </div>

                <div class="form-group">
                    <label for="coupon-code">Coupon Code (optional):</label>
                    <input type="text" id="coupon-code" name="coupon_code" placeholder="Enter coupon code">
                    <button type="button" id="apply-coupon" class="btn-secondary">Apply</button>
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
                    <div class="pricing-item" id="discount-item" style="display: none;">
                        <span>Discount:</span>
                        <span id="discount-amount">-$0.00</span>
                    </div>
                    <div class="pricing-total">
                        <strong>Total: <span id="total-price">$0.00</span></strong>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">Register</button>
                    <button type="button" class="btn-secondary close-modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/layouts/footer.php'; ?>

    <script src="/assets/js/events.js"></script>
</body>
</html>
