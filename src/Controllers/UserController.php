<?php

namespace App\Controllers;

use App\Services\UserService;

class UserController
{
    protected $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    public function updateUser()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);

            // Validate JSON payload
            if (is_null($data)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON payload.']);
                return;
            }

            // Check for required fields
            if (empty($data['id']) || !is_numeric($data['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'User ID is required and must be numeric.']);
                return;
            }

            try {
                $result = $this->userService->updateUser($data);

                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to update user.']);
                }
            } catch (\Exception $e) {
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
            }
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed.']);
        }
    }
}
