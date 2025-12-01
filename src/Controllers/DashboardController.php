<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Member;
use App\Models\Family;
use App\Services\AuthService;
use App\Services\SessionService;
use PDO;

class DashboardController
{
    protected $authService;
    protected $sessionService;
    protected $pdo;
    protected $userModel;
    protected $familyModel;

    public function __construct()
    {
        // Prefer an existing global PDO (e.g. in tests). Otherwise include database config.
        if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
            $this->pdo = $GLOBALS['pdo'];
        } else {
            // Include database configuration which should set $pdo
            require_once __DIR__ . '/../../config/database.php';
            $this->pdo = $pdo ?? null;
        }

        // Initialize SessionService with PDO (nullable)
        $this->sessionService = new SessionService($this->pdo ?? null);

        // Initialize model instances for reuse in controller methods
        try {
            $this->userModel = new User($this->pdo ?? null);
        } catch (\Throwable $e) {
            $this->userModel = null;
        }

        try {
            $this->familyModel = new \App\Models\FamilyMember($this->pdo ?? null);
        } catch (\Throwable $e) {
            $this->familyModel = null;
        }
    }

    public function index()
    {
        // Check authentication via SessionService
        if (!$this->sessionService->isAuthenticated()) {
            header('Location: /login.php');
            exit();
        }

        // Get fresh dashboard data (includes user and family members)
        $dashboardData = $this->getDashboardData();

        // Load the dashboard view using the global view helper so header/footer layout is applied
        if (function_exists('render_view')) {
            // render_view extracts array keys into variables, so pass the whole payload
            // under the name 'dashboardData' so the view can access $dashboardData as before.
            render_view('src/Views/dashboard/index.php', ['dashboardData' => $dashboardData]);
        } else {
            // When including directly, $dashboardData is already available in scope
            include_once __DIR__ . '/../Views/dashboard/index.php';
        }
    }

    public function editProfile()
    {
        // Check authentication via SessionService
        if (!$this->sessionService->isAuthenticated()) {
            header('Location: /login.php');
            exit();
        }

        $userId = $this->sessionService->getCurrentUserId();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Update user details
            $userModel = new User($this->pdo ?? null);
            $user = $userModel->find($userId);
            $userModel->update($userId, $_POST);
            header('Location: /dashboard.php');
            exit();
        }

        // Load the edit profile view (pass $user to the view when using render_view)
        $userModel = new User($this->pdo ?? null);
        $user = $userModel->find($userId);
        if (function_exists('render_view')) {
            render_view('src/Views/members/profile.php', ['user' => $user]);
        } else {
            include_once __DIR__ . '/../Views/members/profile.php';
        }
    }

    public function getDashboardData()
    {
        // Check authentication via SessionService
        if (!$this->sessionService->isAuthenticated()) {
            header('Location: /login');
            exit();
        }

        // Get user details from session
        $userId = $this->sessionService->getCurrentUserId();

        
    // Use controller-scoped models (initialized in constructor)
    $userModel = $this->userModel ?: new User($this->pdo ?? null);
    $familyMemberModel = $this->familyModel ?: new \App\Models\FamilyMember($this->pdo ?? null);

    // Get user with self family record (User::find may include fm_* fields)
    $user = $userModel->find($userId);

    // Defensive: if User::find returned null (shouldn't happen for an authenticated session),
    // ensure we have an array so later indexing does not emit warnings in views.
    if (!is_array($user)) {
        $user = ['id' => $userId, 'email' => '', 'first_name' => '', 'last_name' => ''];
    }

    // Normalize user shape so the view/JS can rely on consistent keys.
    $normalizedUser = [
        'id' => $user['id'] ?? $userId,
        'email' => $user['email'] ?? '',
        // Prefer explicit user first/last name, otherwise fallback to fm_ prefixed family fields or email
        'first_name' => $user['first_name'] ?? $user['fm_first_name'] ?? '',
        'last_name' => $user['last_name'] ?? $user['fm_last_name'] ?? '',
        'birth_year' => $user['birth_year'] ?? $user['fm_birth_year'] ?? null,
        'gender' => $user['gender'] ?? $user['fm_gender'] ?? null,
        'phone_e164' => $user['phone_e164'] ?? $user['fm_phone_e164'] ?? ($user['phone'] ?? null),
        'occupation' => $user['occupation'] ?? $user['fm_occupation'] ?? null,
        'business_info' => $user['business_info'] ?? $user['fm_business_info'] ?? null,
        'village' => $user['village'] ?? $user['fm_village'] ?? null,
        'mosal' => $user['mosal'] ?? $user['fm_mosal'] ?? null,
        // Address fields (NEW)
        'street_address' => $user['street_address'] ?? null,
        'city' => $user['city'] ?? null,
        'state' => $user['state'] ?? null,
        'zip_code' => $user['zip_code'] ?? null,
        'country' => $user['country'] ?? 'USA',
        // Relationship for the primary record should be 'self' (lowercase) to match backend expectations
        'relationship' => $user['relationship'] ?? ($user['fm_relationship'] ?? 'self'),
        'created_at' => $user['created_at'] ?? null,
    ];

    // Compute display name if not already set
    if (!empty($user['name'])) {
        $normalizedUser['name'] = $user['name'];
    } else {
        $nFirst = trim($normalizedUser['first_name'] ?? '');
        $nLast = trim($normalizedUser['last_name'] ?? '');
        $full = trim($nFirst . ' ' . $nLast);
        $normalizedUser['name'] = $full !== '' ? $full : ($normalizedUser['email'] ?: 'User');
    }

    // Log normalized user and raw user for diagnostics
    \App\Services\LoggerService::info("Dashboard data fetch - user_id: $userId", ['raw_user' => $user, 'user' => $normalizedUser]);

    // Get all OTHER family members (excluding self) directly from model
    $family = $familyMemberModel->listByUserId($userId, true);

    \App\Services\LoggerService::info("Dashboard data fetch - family_count: " . count($family), ['family_preview' => array_slice($family, 0, 5)]);

    return [
        'user' => $normalizedUser,
        'family' => $family, // Only non-self members
        'events' => [], // Placeholder for events
        'tickets' => [], // Placeholder for tickets
        'familyCount' => is_array($family) ? count($family) : 0
    ];
    }

    /**
     * GET /get-user-form
     * Returns pre-filled user profile form HTML (no wrapper, just form)
     */
    public function getUserForm()
    {
        // Check authentication
        if (!$this->sessionService->isAuthenticated()) {
            http_response_code(401);
            exit('Unauthorized');
        }

        $userId = $this->sessionService->getCurrentUserId();
        $userModel = new User($this->pdo ?? null);
        $user = $userModel->find($userId);

        if (!$user) {
            http_response_code(404);
            exit('User not found');
        }

        // Set header for plain HTML
        header('Content-Type: text/html; charset=utf-8');

        // Return form HTML (no layout wrapper)
        include __DIR__ . '/../Views/forms/user-profile-form.php';
    }

    /**
     * GET /get-family-member-form
     * Returns pre-filled family member form HTML (no wrapper, just form)
     * Query params: action (add|edit), id (member_id for edit)
     */
    public function getFamilyMemberForm()
    {
        // Check authentication
        if (!$this->sessionService->isAuthenticated()) {
            http_response_code(401);
            exit('Unauthorized');
        }

        $action = $_GET['action'] ?? 'add';
        $memberId = $_GET['id'] ?? null;

        $member = [];

        if ($action === 'edit' && $memberId) {
            $familyMemberModel = new \App\Models\FamilyMember($this->pdo ?? null);
            $member = $familyMemberModel->findById($memberId);

            if (!$member) {
                http_response_code(404);
                exit('Member not found');
            }
        }

        // Set header for plain HTML
        header('Content-Type: text/html; charset=utf-8');

        // Return form HTML (no layout wrapper)
        include __DIR__ . '/../Views/forms/family-member-form.php';
    }

    /**
     * GET /get-member-form
     * Unified endpoint for getting form (add/edit main user or family member)
     * 
     * Query params:
     * - type: 'user' (main user edit), 'member' (family member)
     * - id: member_id or user_id to edit (null = add new)
     * - action: 'add' or 'edit' (convenience, derived from id)
     */
    public function getMemberForm()
    {
        // Check authentication
        if (!$this->sessionService->isAuthenticated()) {
            http_response_code(401);
            exit('Unauthorized');
        }

        $currentUserId = $this->sessionService->getCurrentUserId();
        $type = $_GET['type'] ?? 'member'; // 'user' or 'member'
        $id = $_GET['id'] ?? null;
        $isEditMode = !empty($id);

        $member = [];
        $isMainUser = false;
        $memberId = null;
        $userId = $currentUserId;

        if ($type === 'user') {
            // Edit main user profile (get 'self' family member record)
            $isMainUser = true;
            $memberId = null; // User record, not family member
            
            $userModel = new User($this->pdo ?? null);
            $userData = $userModel->find($currentUserId);

            if (!$userData) {
                http_response_code(404);
                exit('User not found');
            }

            // Merge user fields + family member fields (with fm_ prefix from User::find)
            $member = [
                'first_name' => $userData['first_name'] ?? '',
                'last_name' => $userData['last_name'] ?? '',
                'email' => $userData['email'] ?? '',
                'birth_year' => $userData['fm_birth_year'] ?? null,
                'gender' => $userData['fm_gender'] ?? null,
                'phone_e164' => $userData['phone_e164'] ?? $userData['fm_phone_e164'] ?? null,
                'relationship' => $userData['fm_relationship'] ?? 'self',
                'occupation' => $userData['fm_occupation'] ?? null,
                'business_info' => $userData['fm_business_info'] ?? null,
                'village' => $userData['fm_village'] ?? null,
                'mosal' => $userData['fm_mosal'] ?? null,
                'street_address' => $userData['street_address'] ?? null,
                'city' => $userData['city'] ?? null,
                'state' => $userData['state'] ?? null,
                'zip_code' => $userData['zip_code'] ?? null,
                'country' => $userData['country'] ?? 'USA',
                'address_line_2' => $userData['address_line_2'] ?? null,
            ];

        } elseif ($type === 'member') {
            // Edit or add family member
            if ($isEditMode) {
                $familyMemberModel = new Member($this->pdo ?? null);
                $member = $familyMemberModel->getById($id);

                if (!$member) {
                    http_response_code(404);
                    exit('Member not found');
                }

                $memberId = $member['id'];
                $userId = $member['user_id'];
            } else {
                // Adding new - userId should be current user
                $memberId = null;
            }
        }

        // Set header for plain HTML
        header('Content-Type: text/html; charset=utf-8');

        // Render the unified form template
        include __DIR__ . '/../Views/forms/member-form.php';
    }

    /**
     * POST /save-member
     * Unified endpoint for saving member data (add/edit/update main user)
     * 
     * POST fields:
     * - member_id: null for add, numeric for edit
     * - user_id: user_id that owns this member
     * - is_main_user: '1' if updating main user profile + their 'self' family record
     * - first_name, last_name, email, phone_e164, birth_year, gender, etc.
     * - street_address, city, state, zip_code, country (only for is_main_user=1)
     */
    public function saveMember()
    {
        // Check authentication
        if (!$this->sessionService->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $currentUserId = $this->sessionService->getCurrentUserId();
        $memberId = $_POST['member_id'] ?? null;
        $userId = $_POST['user_id'] ?? null;
        $isMainUser = ($_POST['is_main_user'] ?? '0') === '1';

        // DEBUG: Log incoming POST data
        error_log('=== SAVE MEMBER DEBUG ===');
        error_log('Raw POST member_id: ' . var_export($memberId, true));
        error_log('Raw POST user_id: ' . var_export($userId, true));
        error_log('Raw POST is_main_user: ' . var_export($_POST['is_main_user'] ?? '0', true));
        error_log('Raw POST first_name: ' . var_export($_POST['first_name'] ?? '', true));
        error_log('Raw POST street_address: ' . var_export($_POST['street_address'] ?? 'NOT SET', true));
        error_log('Raw POST city: ' . var_export($_POST['city'] ?? 'NOT SET', true));
        error_log('Raw POST state: ' . var_export($_POST['state'] ?? 'NOT SET', true));

        // Normalize member_id: empty string or "0" should be treated as null (add new)
        if (empty($memberId) || $memberId === "0") {
            $memberId = null;
        } else {
            $memberId = (int)$memberId;
        }

        error_log('Normalized member_id: ' . var_export($memberId, true));
        error_log('isMainUser: ' . var_export($isMainUser, true));
        
        // Validate ownership
        if ((int)$userId !== (int)$currentUserId && !$isMainUser) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            exit;
        }

        try {
            if ($isMainUser) {
                error_log('Branch: UPDATE MAIN USER PROFILE');
                // Update main user profile (users table) + their 'self' family member record
                $userModel = new User($this->pdo ?? null);

                // Build user update data - only include fields that have values
                $userUpdateData = [];
                if (!empty($_POST['first_name'])) $userUpdateData['first_name'] = $_POST['first_name'];
                if (!empty($_POST['last_name'])) $userUpdateData['last_name'] = $_POST['last_name'];
                if (!empty($_POST['email'])) $userUpdateData['email'] = $_POST['email'];
                if (isset($_POST['phone_e164'])) $userUpdateData['phone_e164'] = $_POST['phone_e164'];
                if (isset($_POST['street_address'])) $userUpdateData['street_address'] = $_POST['street_address'];
                if (isset($_POST['city'])) $userUpdateData['city'] = $_POST['city'];
                if (isset($_POST['state'])) $userUpdateData['state'] = $_POST['state'];
                if (isset($_POST['zip_code'])) $userUpdateData['zip_code'] = $_POST['zip_code'];
                if (isset($_POST['country'])) $userUpdateData['country'] = $_POST['country'];

                $userModel->update($currentUserId, $userUpdateData);

                // Update or create 'self' family member record
                $familyMemberModel = new Member($this->pdo ?? null);
                $existingSelf = $this->getOrCreateSelfMemberRecord($currentUserId);

                // Build family member update data - use ?? '' to ensure fields are updated even if empty
                // This is needed because the member form includes these fields and we want to sync them
                $memberUpdateData = [
                    'first_name' => $_POST['first_name'] ?? '',
                    'last_name' => $_POST['last_name'] ?? '',
                    'birth_year' => $_POST['birth_year'] ?? null,
                    'gender' => $_POST['gender'] ?? null,
                    'email' => $_POST['email'] ?? '',
                    'phone_e164' => $_POST['phone_e164'] ?? '',
                    'relationship' => 'self',
                    'occupation' => $_POST['occupation'] ?? '',
                    'business_info' => $_POST['business_info'] ?? '',
                    'village' => $_POST['village'] ?? '',
                    'mosal' => $_POST['mosal'] ?? ''
                ];

                if ($existingSelf) {
                    $updateCount = $familyMemberModel->update($existingSelf['id'], $memberUpdateData);
                    error_log("Self family member updated: id={$existingSelf['id']}, rows_affected={$updateCount}");
                } else {
                    $memberUpdateData['user_id'] = $currentUserId;
                    $created = $familyMemberModel->create($memberUpdateData);
                    error_log('Self family member create result: ' . var_export($created, true));
                }

                $rows = isset($updateCount) ? (int)$updateCount : 0;
                $message = $rows === 0 ? 'No changes made' : 'Profile updated';
                echo json_encode([
                    'success' => true, 
                    'message' => $message,
                    'rows_affected' => $rows,
                    'member' => [
                        'id' => $currentUserId,
                        'first_name' => $_POST['first_name'] ?? '',
                        'last_name' => $_POST['last_name'] ?? '',
                        'email' => $_POST['email'] ?? '',
                        'phone_e164' => $_POST['phone_e164'] ?? '',
                        'birth_year' => $_POST['birth_year'] ?? null,
                        'gender' => $_POST['gender'] ?? null,
                        'occupation' => $_POST['occupation'] ?? '',
                        'business_info' => $_POST['business_info'] ?? '',
                        'village' => $_POST['village'] ?? '',
                        'mosal' => $_POST['mosal'] ?? '',
                        'relationship' => 'self',
                        'is_main_user' => true
                    ]
                ]);

            } elseif ($memberId) {
                // Edit existing family member
                $familyMemberModel = new Member($this->pdo ?? null);
                $existing = $familyMemberModel->getById($memberId);

                if (!$existing || $existing['user_id'] != $currentUserId) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Forbidden']);
                    exit;
                }

                $memberData = [
                    'first_name' => $_POST['first_name'] ?? '',
                    'last_name' => $_POST['last_name'] ?? '',
                    'birth_year' => !empty($_POST['birth_year']) ? $_POST['birth_year'] : null,
                    'gender' => !empty($_POST['gender']) ? $_POST['gender'] : null,
                    'email' => $_POST['email'] ?? '',
                    'phone_e164' => $_POST['phone_e164'] ?? '',
                    'relationship' => $_POST['relationship'] ?? '',
                    'relationship_other' => $_POST['relationship_other'] ?? '',
                    'occupation' => $_POST['occupation'] ?? '',
                    'business_info' => $_POST['business_info'] ?? '',
                    'village' => $_POST['village'] ?? '',
                    'mosal' => $_POST['mosal'] ?? ''
                ];

                // DEBUG: Log before update
                error_log("Attempting to update member id={$memberId} for user={$currentUserId}");
                $updateCount = $familyMemberModel->update($memberId, $memberData);
                error_log('Update rows affected: ' . var_export($updateCount, true));
                // DEBUG: Fetch post-update record
                $after = $familyMemberModel->getById($memberId);
                error_log('Record after update: ' . var_export($after, true));

                $rows = (int)$updateCount;
                $message = $rows === 0 ? 'No changes made' : 'Member updated';
                echo json_encode([
                    'success' => true, 
                    'message' => $message,
                    'rows_affected' => $rows,
                    'member' => array_merge(['id' => $memberId], $memberData)
                ]);

            } else {
                // Add new family member
                $familyMemberModel = new Member($this->pdo ?? null);

                $memberData = [
                    'user_id' => $currentUserId,
                    'first_name' => $_POST['first_name'] ?? '',
                    'last_name' => $_POST['last_name'] ?? '',
                    'birth_year' => $_POST['birth_year'] ?? null,
                    'gender' => !empty($_POST['gender']) ? $_POST['gender'] : null,
                    'email' => $_POST['email'] ?? '',
                    'phone_e164' => $_POST['phone_e164'] ?? '',
                    'relationship' => $_POST['relationship'] ?? '',
                    'occupation' => $_POST['occupation'] ?? '',
                    'business_info' => $_POST['business_info'] ?? '',
                    'village' => $_POST['village'] ?? '',
                    'mosal' => $_POST['mosal'] ?? '',
                ];

                $familyMemberModel->create($memberData);
                echo json_encode(['success' => true, 'message' => 'Member added']);
            }

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }

        exit;
    }

    /**
     * Helper: Get or create 'self' family member record for a user
     * Returns the family member record or null
     */
    private function getOrCreateSelfMemberRecord($userId)
    {
        $familyMemberModel = new Member($this->pdo ?? null);
        
        // Try to find existing 'self' record
        $sql = "SELECT * FROM family_members WHERE user_id = :user_id AND LOWER(COALESCE(relationship, '')) = 'self' LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        return $existing ?: null;
    }
}