<?php

namespace App\Controllers;

use App\Services\EventService;
use App\Services\SessionService;

/**
 * EventController
 *
 * Handles event management and registration endpoints.
 */
class EventController
{
    private $eventService;
    private $sessionService;

    public function __construct(EventService $eventService, SessionService $sessionService)
    {
        $this->eventService = $eventService;
        $this->sessionService = $sessionService;
    }

    /**
     * List all events
     */
    public function index()
    {
        try {
            $events = $this->eventService->getAllEvents();
            $this->jsonResponse(['success' => true, 'events' => $events]);
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get event details
     */
    public function show($id)
    {
        try {
            $eventId = (int)$id;
            $event = $this->eventService->getEventById($eventId);

            if (!$event) {
                $this->jsonResponse(['success' => false, 'error' => 'Event not found'], 404);
                return;
            }

            // Get tickets and coupons for the event
            $tickets = $this->eventService->getEventTickets($eventId);
            $coupons = $this->eventService->getEventCoupons($eventId);

            $event['tickets'] = $tickets;
            $event['coupons'] = $coupons;

            $this->jsonResponse(['success' => true, 'event' => $event]);
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create new event (admin only)
     */
    public function create()
    {
        try {
            // Check if user is admin
            if (!$this->sessionService->isAuthenticated() || $this->sessionService->getCurrentUserRole() < 2) {
                $this->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 403);
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                $this->jsonResponse(['success' => false, 'error' => 'Invalid JSON data'], 400);
                return;
            }

            // Validate required fields
            $requiredFields = ['title', 'description', 'event_date', 'location'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $this->jsonResponse(['success' => false, 'error' => "Missing required field: $field"], 400);
                    return;
                }
            }

            $eventId = $this->eventService->createEvent($data);
            $this->jsonResponse(['success' => true, 'event_id' => $eventId], 201);
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update event (admin only)
     */
    public function update($id)
    {
        try {
            // Check if user is admin
            if (!$this->sessionService->isAuthenticated() || $this->sessionService->getCurrentUserRole() < 2) {
                $this->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 403);
                return;
            }

            $eventId = (int)$id;
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                $this->jsonResponse(['success' => false, 'error' => 'Invalid JSON data'], 400);
                return;
            }

            $success = $this->eventService->updateEvent($eventId, $data);

            if ($success) {
                $this->jsonResponse(['success' => true, 'message' => 'Event updated successfully']);
            } else {
                $this->jsonResponse(['success' => false, 'error' => 'No changes made'], 400);
            }
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Register for event
     */
    public function register($id)
    {
        try {
            if (!$this->sessionService->isAuthenticated()) {
                $this->jsonResponse(['success' => false, 'error' => 'Authentication required'], 401);
                return;
            }

            $eventId = (int)$id;
            $userId = $this->sessionService->getCurrentUserId();

            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                $this->jsonResponse(['success' => false, 'error' => 'Invalid JSON data'], 400);
                return;
            }

            // Set default values
            $registrationData = [
                'ticket_id' => $data['ticket_id'] ?? null,
                'coupon_id' => $data['coupon_id'] ?? null,
                'guest_count' => $data['guest_count'] ?? 0,
                'total_amount' => $data['total_amount'] ?? 0
            ];

            $success = $this->eventService->registerForEvent($userId, $eventId, $registrationData);

            if ($success) {
                $this->jsonResponse(['success' => true, 'message' => 'Registration successful']);
            } else {
                $this->jsonResponse(['success' => false, 'error' => 'Registration failed'], 500);
            }
        } catch (\RuntimeException $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get user's registrations
     */
    public function myRegistrations()
    {
        try {
            if (!$this->sessionService->isAuthenticated()) {
                $this->jsonResponse(['success' => false, 'error' => 'Authentication required'], 401);
                return;
            }

            $userId = $this->sessionService->getCurrentUserId();
            $registrations = $this->eventService->getUserRegistrations($userId);

            $this->jsonResponse(['success' => true, 'registrations' => $registrations]);
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get event registrations (admin only)
     */
    public function getRegistrations($id)
    {
        try {
            // Check if user is admin
            if (!$this->sessionService->isAuthenticated() || $this->sessionService->getCurrentUserRole() < 2) {
                $this->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 403);
                return;
            }

            $eventId = (int)$id;
            $registrations = $this->eventService->getEventRegistrations($eventId);

            $this->jsonResponse(['success' => true, 'registrations' => $registrations]);
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Check in attendee (admin only)
     */
    public function checkIn($registrationId)
    {
        try {
            // Check if user is admin
            if (!$this->sessionService->isAuthenticated() || $this->sessionService->getCurrentUserRole() < 2) {
                $this->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 403);
                return;
            }

            $checkedBy = $this->sessionService->getCurrentUserId();
            $success = $this->eventService->checkInAttendee((int)$registrationId, $checkedBy);

            if ($success) {
                $this->jsonResponse(['success' => true, 'message' => 'Check-in successful']);
            } else {
                $this->jsonResponse(['success' => false, 'error' => 'Check-in failed or already checked in'], 400);
            }
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper method to send JSON responses
     */
    private function jsonResponse(array $data, int $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}