<?php

namespace App\Services;

use PDO;

/**
 * EventService
 *
 * Handles event management, registration, and ticketing.
 * Manages events, event_registrations, event_tickets, and coupons tables.
 */
class EventService
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get all events
     */
    public function getAllEvents(): array
    {
        $stmt = $this->pdo->query(
            "SELECT e.*, COUNT(er.id) as registration_count
             FROM events e
             LEFT JOIN event_registrations er ON e.id = er.event_id
             GROUP BY e.id
             ORDER BY e.event_date ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get event by ID
     */
    public function getEventById(int $eventId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT e.*, COUNT(er.id) as registration_count
             FROM events e
             LEFT JOIN event_registrations er ON e.id = er.event_id
             WHERE e.id = :id
             GROUP BY e.id"
        );
        $stmt->bindParam(':id', $eventId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Create new event
     */
    public function createEvent(array $data): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO events (title, description, event_date, location, max_capacity, registration_deadline, created_at)
             VALUES (:title, :description, :event_date, :location, :max_capacity, :registration_deadline, CURRENT_TIMESTAMP)"
        );

        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':event_date', $data['event_date']);
        $stmt->bindParam(':location', $data['location']);
        $stmt->bindParam(':max_capacity', $data['max_capacity'], PDO::PARAM_INT);
        $stmt->bindParam(':registration_deadline', $data['registration_deadline']);

        $stmt->execute();
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Update event
     */
    public function updateEvent(int $eventId, array $data): bool
    {
        $fields = [];
        $params = [':id' => $eventId];

        if (isset($data['title'])) { $fields[] = 'title = :title'; $params[':title'] = $data['title']; }
        if (isset($data['description'])) { $fields[] = 'description = :description'; $params[':description'] = $data['description']; }
        if (isset($data['event_date'])) { $fields[] = 'event_date = :event_date'; $params[':event_date'] = $data['event_date']; }
        if (isset($data['location'])) { $fields[] = 'location = :location'; $params[':location'] = $data['location']; }
        if (isset($data['max_capacity'])) { $fields[] = 'max_capacity = :max_capacity'; $params[':max_capacity'] = $data['max_capacity']; }
        if (isset($data['registration_deadline'])) { $fields[] = 'registration_deadline = :registration_deadline'; $params[':registration_deadline'] = $data['registration_deadline']; }

        if (empty($fields)) return false;

        $sql = 'UPDATE events SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        return $stmt->execute();
    }

    /**
     * Register for event
     */
    public function registerForEvent(int $userId, int $eventId, array $registrationData): bool
    {
        // Check if event exists and has capacity
        $event = $this->getEventById($eventId);
        if (!$event) {
            throw new \RuntimeException('Event not found');
        }

        // Check registration deadline
        if ($event['registration_deadline'] && strtotime($event['registration_deadline']) < time()) {
            throw new \RuntimeException('Registration deadline has passed');
        }

        // Check capacity
        if ($event['max_capacity'] && $event['registration_count'] >= $event['max_capacity']) {
            throw new \RuntimeException('Event is at full capacity');
        }

        // Check if user is already registered
        if ($this->isUserRegistered($userId, $eventId)) {
            throw new \RuntimeException('User is already registered for this event');
        }

        // Process ticket selection
        $ticketId = $registrationData['ticket_id'] ?? null;
        if ($ticketId) {
            $this->validateTicket($ticketId, $eventId);
        }

        // Process coupon if provided
        $couponId = $registrationData['coupon_id'] ?? null;
        $discount = 0;
        if ($couponId) {
            $discount = $this->validateAndApplyCoupon($couponId, $eventId, $userId);
        }

        // Calculate total guests
        $guests = $registrationData['guest_count'] ?? 0;
        $totalAttendees = 1 + $guests; // user + guests

        // Check capacity with guests
        if ($event['max_capacity'] && ($event['registration_count'] + $totalAttendees) > $event['max_capacity']) {
            throw new \RuntimeException('Not enough capacity for all attendees');
        }

        // Create registration
        $stmt = $this->pdo->prepare(
            "INSERT INTO event_registrations (user_id, event_id, event_ticket_id, guest_count, total_amount, discount_amount, final_amount, registration_date, created_at)
             VALUES (:user_id, :event_id, :ticket_id, :guest_count, :total_amount, :discount_amount, :final_amount, CURRENT_DATE, CURRENT_TIMESTAMP)"
        );

        $totalAmount = $registrationData['total_amount'] ?? 0;
        $finalAmount = $totalAmount - $discount;

        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
        $stmt->bindParam(':ticket_id', $ticketId, PDO::PARAM_INT);
        $stmt->bindParam(':guest_count', $guests, PDO::PARAM_INT);
        $stmt->bindParam(':total_amount', $totalAmount);
        $stmt->bindParam(':discount_amount', $discount);
        $stmt->bindParam(':final_amount', $finalAmount);

        return $stmt->execute();
    }

    /**
     * Check if user is registered for event
     */
    public function isUserRegistered(int $userId, int $eventId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM event_registrations WHERE user_id = :user_id AND event_id = :event_id"
        );
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Get user's registrations
     */
    public function getUserRegistrations(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT er.*, e.title, e.event_date, e.location, et.name as ticket_name, et.price as ticket_price
             FROM event_registrations er
             JOIN events e ON er.event_id = e.id
             LEFT JOIN event_tickets et ON er.event_ticket_id = et.id
             WHERE er.user_id = :user_id
             ORDER BY e.event_date DESC"
        );
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get event registrations
     */
    public function getEventRegistrations(int $eventId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT er.*, u.name, u.email, fm.first_name, fm.last_name, et.name as ticket_name
             FROM event_registrations er
             JOIN users u ON er.user_id = u.id
             LEFT JOIN family_members fm ON u.id = fm.user_id
             LEFT JOIN event_tickets et ON er.event_ticket_id = et.id
             WHERE er.event_id = :event_id
             ORDER BY er.created_at DESC"
        );
        $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Validate ticket
     */
    private function validateTicket(int $ticketId, int $eventId): void
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM event_tickets WHERE id = :id AND event_id = :event_id AND is_active = 1"
        );
        $stmt->bindParam(':id', $ticketId, PDO::PARAM_INT);
        $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
        $stmt->execute();

        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$ticket) {
            throw new \RuntimeException('Invalid ticket selection');
        }
    }

    /**
     * Validate and apply coupon
     */
    private function validateAndApplyCoupon(int $couponId, int $eventId, int $userId): float
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM coupons WHERE id = :id AND event_id = :event_id AND is_active = 1
             AND (expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP)
             AND (usage_limit IS NULL OR times_used < usage_limit)"
        );
        $stmt->bindParam(':id', $couponId, PDO::PARAM_INT);
        $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
        $stmt->execute();

        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$coupon) {
            throw new \RuntimeException('Invalid or expired coupon');
        }

        // Check if user already used this coupon
        if ($coupon['one_per_user']) {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM event_registrations
                 WHERE user_id = :user_id AND event_id = :event_id AND coupon_id = :coupon_id"
            );
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
            $stmt->bindParam(':coupon_id', $couponId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->fetchColumn() > 0) {
                throw new \RuntimeException('Coupon already used by this user');
            }
        }

        return (float)$coupon['discount_amount'];
    }

    /**
     * Check in attendee
     */
    public function checkInAttendee(int $registrationId, int $checkedBy): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE event_registrations
             SET checked_in = 1, checkin_time = CURRENT_TIMESTAMP, checked_in_by = :checked_by
             WHERE id = :id AND checked_in = 0"
        );
        $stmt->bindParam(':id', $registrationId, PDO::PARAM_INT);
        $stmt->bindParam(':checked_by', $checkedBy, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Get event tickets
     */
    public function getEventTickets(int $eventId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM event_tickets WHERE event_id = :event_id AND is_active = 1");
        $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get available coupons for event
     */
    public function getEventCoupons(int $eventId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM coupons WHERE event_id = :event_id AND is_active = 1
             AND (expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP)
             AND (usage_limit IS NULL OR times_used < usage_limit)"
        );
        $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}