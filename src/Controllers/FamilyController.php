<?php

namespace App\Controllers;

use App\Models\FamilyMember;
use App\Services\LoggerService;
use App\Services\PhoneService;
use App\Services\SessionService;

class FamilyController
{
    protected $familyService;
    protected $sessionService;

    public function __construct()
    {
        $this->familyService = new \App\Services\FamilyService($GLOBALS['pdo'] ?? null);
        $this->sessionService = new SessionService($GLOBALS['pdo'] ?? null);
    }

    public function index($userId)
    {
        $fm = new FamilyMember($GLOBALS['pdo'] ?? null);
        $families = $fm->listByUserId($userId);

        if (function_exists('render_view')) {
            render_view('src/Views/members/family.php', ['families' => $families]);
        } else {
            $view = __DIR__ . '/../Views/members/family.php';
            if (file_exists($view)) {
                include_once $view;
            } else {
                throw new \RuntimeException("View not found: $view");
            }
        }
    }

    public function create($userId)
    {
        // Require authentication for creating family members via form
        if (!$this->sessionService->isAuthenticated()) {
            header('Location: /login.php');
            return;
        }

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
            return;
        }

        if (function_exists('render_view')) {
            render_view('src/Views/members/family.php', ['families' => null, 'userId' => $userId]);
        } else {
            $view = __DIR__ . '/../Views/members/family.php';
            if (file_exists($view)) {
                include_once $view;
            } else {
                throw new \RuntimeException("View not found: $view");
            }
        }
    }

    public function edit($familyId)
    {
        // Require authentication for editing
        if (!$this->sessionService->isAuthenticated()) {
            header('Location: /login.php');
            return;
        }

        $fm = new FamilyMember($GLOBALS['pdo'] ?? null);
        $family = $fm->findById($familyId);
        if (!$family) {
            header('Location: /dashboard.php');
            return;
        }

        // Authorize: owner or elevated role (>=2)
        $currentUserId = $this->sessionService->getCurrentUserId();
        $currentRole = $this->sessionService->getCurrentUserRole() ?? 1;
        if ($family['user_id'] != $currentUserId && (int)$currentRole < 2) {
            header('Location: /dashboard.php');
            return;
        }

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

            if (isset($family['relationship']) && strtolower($family['relationship']) === 'self') {
                $data['is_main_user'] = true;
                $data['user_id'] = $family['user_id'] ?? null;
            }

            $this->familyService->updateFamilyMember($familyId, $data);
            header("Location: /dashboard.php");
            return;
        }

        if (function_exists('render_view')) {
            render_view('src/Views/members/family.php', ['family' => $family]);
        } else {
            $view = __DIR__ . '/../Views/members/family.php';
            if (file_exists($view)) {
                include_once $view;
            } else {
                throw new \RuntimeException("View not found: $view");
            }
        }
    }

    public function delete($familyId)
    {
        // Require authentication
        if (!$this->sessionService->isAuthenticated()) {
            header('Location: /login.php');
            return;
        }

        // Authorize: owner or elevated role
        $currentUserId = $this->sessionService->getCurrentUserId();
        $currentRole = $this->sessionService->getCurrentUserRole() ?? 1;

        $fmModel = new FamilyMember($GLOBALS['pdo'] ?? null);
        $existing = $fmModel->findById($familyId);
        if ($existing && ($existing['user_id'] == $currentUserId || (int)$currentRole >= 2)) {
            $this->familyService->deleteFamilyMember($familyId);
        }

        header("Location: /dashboard.php");
    }

    /*****************************************************************
     * JSON API endpoints used by public/assets/js/dashboard.js
     *****************************************************************/

    public function addFamilyMember()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        // Require authentication
        if (!$this->sessionService->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }

        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        if (is_null($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON payload']);
            return;
        }

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

        $currentUserId = $this->sessionService->getCurrentUserId();
        $currentRole = $this->sessionService->getCurrentUserRole() ?? 1;

        // Resolve target user: admins (role>=2) may specify user_id to act for others
        if (!empty($data['user_id']) && is_numeric($data['user_id'])) {
            if ((int)$data['user_id'] !== (int)$currentUserId && (int)$currentRole < 2) {
                http_response_code(403);
                echo json_encode(['error' => 'Forbidden']);
                return;
            }
            $targetUserId = (int)$data['user_id'];
        } else {
            $targetUserId = (int)$currentUserId;
        }

        try {
            $familyData = [
                'user_id' => $targetUserId,
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
    }

    public function updateFamilyMember()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        if (!$this->sessionService->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }

        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        if (is_null($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON payload']);
            return;
        }
        if (empty($data['id']) || !is_numeric($data['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Family member ID is required and must be numeric']);
            return;
        }

        $familyId = (int)$data['id'];
        $currentUserId = $this->sessionService->getCurrentUserId();
        $currentRole = $this->sessionService->getCurrentUserRole() ?? 1;

        // Authorize: owner or elevated role
        $fmModel = new FamilyMember($GLOBALS['pdo'] ?? null);
        $existing = $fmModel->findById($familyId);
        if (!$existing) {
            http_response_code(404);
            echo json_encode(['error' => 'Family member not found']);
            return;
        }
        if ($existing['user_id'] != $currentUserId && (int)$currentRole < 2) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }

        try {
            $familyData = [];
            $allowed = ['first_name','last_name','birth_year','gender','email','phone_e164','relationship','relationship_other','occupation','business_info','village','mosal'];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $data)) {
                    $familyData[$f] = $data[$f];
                }
            }

            if (empty($familyData)) {
                http_response_code(400);
                echo json_encode(['error' => 'No fields to update']);
                return;
            }

            $result = $this->familyService->updateFamilyMember($familyId, $familyData);
            if ($result === false) {
                http_response_code(500);
                LoggerService::error('FamilyController::updateFamilyMember - Update failed for family ID: ' . $familyId);
                echo json_encode(['error' => 'Failed to update family member']);
                return;
            }
            // If nothing changed, rows_affected will be 0
            $rowsAffected = intval($result);
            if ($rowsAffected === 0) {
                // No changes made
                echo json_encode(['success' => true, 'rows_affected' => 0, 'message' => 'No changes made']);
            } else {
                echo json_encode(['success' => true, 'rows_affected' => $rowsAffected, 'message' => 'Family member updated successfully']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function deleteFamilyMember()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        if (!$this->sessionService->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }

        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        if (is_null($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON payload']);
            return;
        }
        if (empty($data['id']) || !is_numeric($data['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Family member ID is required and must be numeric']);
            return;
        }

        $familyId = (int)$data['id'];
        $currentUserId = $this->sessionService->getCurrentUserId();
        $currentRole = $this->sessionService->getCurrentUserRole() ?? 1;

        try {
            $fmModel = new FamilyMember($GLOBALS['pdo'] ?? null);
            $existing = $fmModel->findById($familyId);
            if (!$existing) {
                http_response_code(404);
                echo json_encode(['error' => 'Family member not found']);
                return;
            }
            if ($existing['user_id'] != $currentUserId && (int)$currentRole < 2) {
                http_response_code(403);
                echo json_encode(['error' => 'Forbidden']);
                return;
            }

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
    }
}