<?php

namespace App\Controllers;

use App\Models\Family;
use App\Models\User;
use App\Models\FamilyMember;
use App\Services\PhoneService;

class FamilyController
{
    protected $familyService;

    public function __construct()
    {
        $this->familyService = new \App\Services\FamilyService($GLOBALS['pdo'] ?? null);
    }

    public function index($userId)
    {
        $fm = new FamilyMember($GLOBALS['pdo'] ?? null);
        $families = $fm->listByUserId($userId);
        require_once '../src/Views/members/family.php';
    }

    public function create($userId)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $phone = PhoneService::normalizeToE164($_POST['phone'] ?? null);
            $data = [
                'user_id' => $userId,
                'first_name' => $_POST['first_name'] ?? $_POST['name'] ?? null,
                'last_name' => $_POST['last_name'] ?? null,
                'birth_year' => $_POST['birth_year'] ?? null,
                'gender' => $_POST['gender'] ?? null,
                'email' => $_POST['email'] ?? null,
                'phone_e164' => $phone,
                'relationship' => $_POST['relation'] ?? $_POST['relationship'] ?? 'other',
                'relationship_other' => $_POST['relationship_other'] ?? null,
                'occupation' => $_POST['occupation'] ?? null,
                'business_info' => $_POST['business_info'] ?? null,
            ];
            $this->familyService->createFamilyMember($data);
            header("Location: /dashboard.php");
        }
        require_once '../src/Views/members/family.php';
    }

    public function edit($familyId)
    {
        $fm = new FamilyMember($GLOBALS['pdo'] ?? null);
        $family = $fm->findById($familyId);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $phone = PhoneService::normalizeToE164($_POST['phone'] ?? null);
            $data = [
                'first_name' => $_POST['first_name'] ?? $_POST['name'] ?? null,
                'last_name' => $_POST['last_name'] ?? null,
                'birth_year' => $_POST['birth_year'] ?? null,
                'gender' => $_POST['gender'] ?? null,
                'email' => $_POST['email'] ?? null,
                'phone_e164' => $phone,
                'relationship' => $_POST['relation'] ?? $_POST['relationship'] ?? null,
                'relationship_other' => $_POST['relationship_other'] ?? null,
                'occupation' => $_POST['occupation'] ?? null,
                'business_info' => $_POST['business_info'] ?? null,
            ];
            // If this is the main user, set is_main_user and user_id
            if (isset($family['relationship']) && strtolower($family['relationship']) === 'self') {
                $data['is_main_user'] = true;
                $data['user_id'] = $family['user_id'] ?? null;
            }
            $this->familyService->updateFamilyMember($familyId, $data);
            header("Location: /dashboard.php");
        }
        require_once '../src/Views/members/family.php';
    }

    public function delete($familyId)
    {
        $this->familyService->deleteFamilyMember($familyId);
        header("Location: /dashboard.php");
    }

    public function addFamilyMember()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Get raw input
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);

            // Validate JSON payload
            if (is_null($data)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON payload']);
                return;
            }

            // Check for required fields
            if (empty($data['first_name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'First name is required']);
                return;
            }

            if (empty($data['relationship'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Relationship is required']);
                return;
            }

            if (empty($data['user_id']) || !is_numeric($data['user_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'User ID is required and must be numeric']);
                return;
            }

            try {
                // Prepare family member data
                $familyData = [
                    'user_id' => (int)$data['user_id'],
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'] ?? null,
                    'birth_year' => $data['birth_year'] ?? null,
                    'gender' => $data['gender'] ?? null,
                    'email' => $data['email'] ?? null,
                    'phone_e164' => $data['phone_e164'] ?? null,
                    'relationship' => $data['relationship'],
                    'relationship_other' => $data['relationship_other'] ?? null,
                    'occupation' => $data['occupation'] ?? null,
                    'business_info' => $data['business_info'] ?? null,
                    'village' => $data['village'] ?? null,
                    'mosal' => $data['mosal'] ?? null,
                ];

                // Create family member
                $result = $this->familyService->createFamilyMember($familyData);

                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Family member added successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to add family member']);
                }
            } catch (\Exception $e) {
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
            }
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
    }

    public function updateFamilyMember()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Get raw input
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);

            // Validate JSON payload
            if (is_null($data)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON payload']);
                return;
            }

            // Check for required family ID
            if (empty($data['id']) || !is_numeric($data['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Family member ID is required and must be numeric']);
                return;
            }

            $familyId = (int)$data['id'];

            try {
                // Prepare update data (only include fields that are present and not empty)
                $familyData = [];

                if (isset($data['first_name'])) {
                    $familyData['first_name'] = $data['first_name'];
                }
                if (isset($data['last_name'])) {
                    $familyData['last_name'] = $data['last_name'];
                }
                if (isset($data['birth_year'])) {
                    $familyData['birth_year'] = $data['birth_year'] ?: null;
                }
                if (isset($data['gender'])) {
                    $familyData['gender'] = $data['gender'];
                }
                if (isset($data['email'])) {
                    $familyData['email'] = $data['email'];
                }
                if (isset($data['phone_e164'])) {
                    $familyData['phone_e164'] = $data['phone_e164'];
                }
                if (isset($data['relationship'])) {
                    $familyData['relationship'] = $data['relationship'];
                }
                if (isset($data['relationship_other'])) {
                    $familyData['relationship_other'] = $data['relationship_other'];
                }
                if (isset($data['occupation'])) {
                    $familyData['occupation'] = $data['occupation'];
                }
                if (isset($data['business_info'])) {
                    $familyData['business_info'] = $data['business_info'];
                }
                if (isset($data['village'])) {
                    $familyData['village'] = $data['village'];
                }
                if (isset($data['mosal'])) {
                    $familyData['mosal'] = $data['mosal'];
                }

                // Check if there's anything to update
                if (empty($familyData)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'No fields to update']);
                    return;
                }

                // Update family member
                $result = $this->familyService->updateFamilyMember($familyId, $familyData);

                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Family member updated successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to update family member']);
                }
            } catch (\Exception $e) {
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
            }
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
    }

    public function deleteFamilyMember()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Get raw input
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);

            // Validate JSON payload
            if (is_null($data)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON payload']);
                return;
            }

            // Check for required family ID
            if (empty($data['id']) || !is_numeric($data['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Family member ID is required and must be numeric']);
                return;
            }

            $familyId = (int)$data['id'];

            try {
                // Delete family member
                $result = $this->familyService->deleteFamilyMember($familyId);

                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Family member deleted successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to delete family member']);
                }
            } catch (\Exception $e) {
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
            }
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
    }
}