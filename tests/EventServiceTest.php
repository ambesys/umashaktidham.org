<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Services\EventService;
use PDO;

class EventServiceTest extends TestCase
{
    private $pdo;
    private $eventService;

    protected function setUp(): void
    {
        // Create in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');

        // Create tables
        $this->pdo->exec("
            CREATE TABLE events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                description TEXT,
                event_date DATE NOT NULL,
                location TEXT,
                max_capacity INTEGER,
                registration_deadline DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                role_id INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->pdo->exec("
            CREATE TABLE family_members (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                first_name TEXT,
                last_name TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ");

        $this->pdo->exec("
            CREATE TABLE event_tickets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                event_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                price DECIMAL(10,2) DEFAULT 0,
                is_active BOOLEAN DEFAULT 1,
                FOREIGN KEY (event_id) REFERENCES events(id)
            )
        ");

        $this->pdo->exec("
            CREATE TABLE coupons (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                event_id INTEGER NOT NULL,
                code TEXT UNIQUE NOT NULL,
                discount_amount DECIMAL(10,2) DEFAULT 0,
                is_active BOOLEAN DEFAULT 1,
                expires_at DATETIME,
                usage_limit INTEGER,
                times_used INTEGER DEFAULT 0,
                one_per_user BOOLEAN DEFAULT 0,
                FOREIGN KEY (event_id) REFERENCES events(id)
            )
        ");

        $this->pdo->exec("
            CREATE TABLE event_registrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                event_id INTEGER NOT NULL,
                event_ticket_id INTEGER,
                guest_count INTEGER DEFAULT 0,
                total_amount DECIMAL(10,2) DEFAULT 0,
                discount_amount DECIMAL(10,2) DEFAULT 0,
                final_amount DECIMAL(10,2) DEFAULT 0,
                registration_date DATE DEFAULT CURRENT_DATE,
                checked_in BOOLEAN DEFAULT 0,
                checkin_time DATETIME,
                checked_in_by INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (event_id) REFERENCES events(id),
                FOREIGN KEY (event_ticket_id) REFERENCES event_tickets(id)
            )
        ");

        $this->eventService = new EventService($this->pdo);

        // Insert test data
        $this->setupTestData();
    }

    private function setupTestData()
    {
        // Insert test user
        $this->pdo->exec("INSERT INTO users (name, email, password_hash) VALUES ('Test User', 'test@example.com', 'hash')");
        $this->pdo->exec("INSERT INTO family_members (user_id, first_name, last_name) VALUES (1, 'John', 'Doe')");

        // Insert test event with future date
        $this->pdo->exec("INSERT INTO events (title, description, event_date, location, max_capacity, registration_deadline)
                         VALUES ('Test Event', 'A test event', '2025-12-25', 'Test Location', 100, '2025-12-20')");

        // Insert test ticket
        $this->pdo->exec("INSERT INTO event_tickets (event_id, name, price) VALUES (1, 'General Admission', 25.00)");

        // Insert test coupon
        $this->pdo->exec("INSERT INTO coupons (event_id, code, discount_amount, is_active) VALUES (1, 'SAVE10', 10.00, 1)");
    }

    public function testGetAllEvents()
    {
        $events = $this->eventService->getAllEvents();

        $this->assertIsArray($events);
        $this->assertCount(1, $events);
        $this->assertEquals('Test Event', $events[0]['title']);
        $this->assertArrayHasKey('registration_count', $events[0]);
    }

    public function testGetEventById()
    {
        $event = $this->eventService->getEventById(1);

        $this->assertIsArray($event);
        $this->assertEquals('Test Event', $event['title']);
        $this->assertEquals('A test event', $event['description']);
    }

    public function testGetEventByIdNotFound()
    {
        $event = $this->eventService->getEventById(999);

        $this->assertNull($event);
    }

    public function testCreateEvent()
    {
        $eventData = [
            'title' => 'New Event',
            'description' => 'New event description',
            'event_date' => '2024-12-31',
            'location' => 'New Location',
            'max_capacity' => 50,
            'registration_deadline' => '2024-12-25'
        ];

        $eventId = $this->eventService->createEvent($eventData);

        $this->assertIsInt($eventId);
        $this->assertGreaterThan(1, $eventId);

        // Verify event was created
        $event = $this->eventService->getEventById($eventId);
        $this->assertEquals('New Event', $event['title']);
    }

    public function testRegisterForEvent()
    {
        $registrationData = [
            'ticket_id' => 1,
            'guest_count' => 2,
            'total_amount' => 75.00
        ];

        $result = $this->eventService->registerForEvent(1, 1, $registrationData);

        $this->assertTrue($result);

        // Verify registration was created
        $registrations = $this->eventService->getUserRegistrations(1);
        $this->assertCount(1, $registrations);
        $this->assertEquals(2, $registrations[0]['guest_count']);
        $this->assertEquals(75.00, $registrations[0]['total_amount']);
    }

    public function testRegisterForEventWithCoupon()
    {
        $registrationData = [
            'ticket_id' => 1,
            'coupon_id' => 1,
            'guest_count' => 1,
            'total_amount' => 25.00
        ];

        $result = $this->eventService->registerForEvent(1, 1, $registrationData);

        $this->assertTrue($result);

        // Verify discount was applied
        $registrations = $this->eventService->getUserRegistrations(1);
        $this->assertEquals(10.00, $registrations[0]['discount_amount']);
        $this->assertEquals(15.00, $registrations[0]['final_amount']);
    }

    public function testRegisterForEventAlreadyRegistered()
    {
        // First registration
        $registrationData = [
            'ticket_id' => 1,
            'guest_count' => 0,
            'total_amount' => 25.00
        ];
        $this->eventService->registerForEvent(1, 1, $registrationData);

        // Try to register again
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User is already registered for this event');

        $this->eventService->registerForEvent(1, 1, $registrationData);
    }

    public function testRegisterForEventPastDeadline()
    {
        // Update event deadline to past
        $this->pdo->exec("UPDATE events SET registration_deadline = '2020-01-01' WHERE id = 1");

        $registrationData = [
            'ticket_id' => 1,
            'guest_count' => 0,
            'total_amount' => 25.00
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Registration deadline has passed');

        $this->eventService->registerForEvent(1, 1, $registrationData);
    }

    public function testRegisterForEventAtCapacity()
    {
        // Update event to have capacity of 1
        $this->pdo->exec("UPDATE events SET max_capacity = 1 WHERE id = 1");

        // Register first person
        $registrationData = [
            'ticket_id' => 1,
            'guest_count' => 0,
            'total_amount' => 25.00
        ];
        $this->eventService->registerForEvent(1, 1, $registrationData);

        // Create another user
        $this->pdo->exec("INSERT INTO users (name, email, password_hash) VALUES ('Test User 2', 'test2@example.com', 'hash')");
        $this->pdo->exec("INSERT INTO family_members (user_id, first_name, last_name) VALUES (2, 'Jane', 'Smith')");

        // Try to register second person
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Event is at full capacity');

        $this->eventService->registerForEvent(2, 1, $registrationData);
    }

    public function testIsUserRegistered()
    {
        $this->assertFalse($this->eventService->isUserRegistered(1, 1));

        // Register user
        $registrationData = [
            'ticket_id' => 1,
            'guest_count' => 0,
            'total_amount' => 25.00
        ];
        $this->eventService->registerForEvent(1, 1, $registrationData);

        $this->assertTrue($this->eventService->isUserRegistered(1, 1));
        $this->assertFalse($this->eventService->isUserRegistered(2, 1));
    }

    public function testGetUserRegistrations()
    {
        // Register for event
        $registrationData = [
            'ticket_id' => 1,
            'guest_count' => 1,
            'total_amount' => 25.00
        ];
        $this->eventService->registerForEvent(1, 1, $registrationData);

        $registrations = $this->eventService->getUserRegistrations(1);

        $this->assertIsArray($registrations);
        $this->assertCount(1, $registrations);
        $this->assertEquals('Test Event', $registrations[0]['title']);
        $this->assertEquals('General Admission', $registrations[0]['ticket_name']);
        $this->assertEquals(25.00, $registrations[0]['ticket_price']);
    }

    public function testGetEventTickets()
    {
        $tickets = $this->eventService->getEventTickets(1);

        $this->assertIsArray($tickets);
        $this->assertCount(1, $tickets);
        $this->assertEquals('General Admission', $tickets[0]['name']);
        $this->assertEquals(25.00, $tickets[0]['price']);
    }

    public function testGetEventCoupons()
    {
        $coupons = $this->eventService->getEventCoupons(1);

        $this->assertIsArray($coupons);
        $this->assertCount(1, $coupons);
        $this->assertEquals('SAVE10', $coupons[0]['code']);
        $this->assertEquals(10.00, $coupons[0]['discount_amount']);
    }

    public function testCheckInAttendee()
    {
        // Register for event
        $registrationData = [
            'ticket_id' => 1,
            'guest_count' => 0,
            'total_amount' => 25.00
        ];
        $this->eventService->registerForEvent(1, 1, $registrationData);

        // Get registration ID
        $registrations = $this->eventService->getUserRegistrations(1);
        $registrationId = $registrations[0]['id'];

        // Check in attendee
        $result = $this->eventService->checkInAttendee($registrationId, 1);

        $this->assertTrue($result);

        // Verify check-in
        $stmt = $this->pdo->prepare("SELECT checked_in, checked_in_by FROM event_registrations WHERE id = ?");
        $stmt->execute([$registrationId]);
        $registration = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals(1, $registration['checked_in']);
        $this->assertEquals(1, $registration['checked_in_by']);
    }

    public function testCheckInAttendeeAlreadyCheckedIn()
    {
        // Register and check in
        $registrationData = [
            'ticket_id' => 1,
            'guest_count' => 0,
            'total_amount' => 25.00
        ];
        $this->eventService->registerForEvent(1, 1, $registrationData);

        $registrations = $this->eventService->getUserRegistrations(1);
        $registrationId = $registrations[0]['id'];

        $this->eventService->checkInAttendee($registrationId, 1);

        // Try to check in again
        $result = $this->eventService->checkInAttendee($registrationId, 1);

        $this->assertFalse($result); // Should return false for already checked in
    }
}