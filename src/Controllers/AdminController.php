<?php
namespace App\Controllers;

use App\Models\User;
use App\Models\Moderator;
use App\Services\LoggerService;
use App\Services\DashboardService;
use PDO;



class AdminController
{
    private $dashboardService;

    public function __construct()
    {
        // Initialize DashboardService for stats calculations
        try {
            $this->dashboardService = new DashboardService($GLOBALS['pdo'] ?? null);
        } catch (\Throwable $e) {
            error_log('AdminController: DashboardService initialization failed: ' . $e->getMessage());
            $this->dashboardService = null;
        }
    }

    /**
     * Get all users with family size and family members attached
     * 
     * Delegates to DashboardService for consistent data aggregation
     */
    public function getUsersWithFamilyData()
    {
        if ($this->dashboardService) {
            return $this->dashboardService->getUsersWithFamilyData();
        }
        
        // Fallback: implement locally if service unavailable
        $users = $this->getUsers();
        foreach ($users as &$user) {
            $stmt = $GLOBALS['pdo']->prepare("SELECT id, first_name, last_name, birth_year, relationship, village, mosal, gender, phone_e164, email, occupation, business_info FROM family_members WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $familyMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $user['family_members'] = $familyMembers ?? [];
            $user['family_size'] = count($familyMembers);
        }
        return $users;
    }

    /**
     * Get active users (logged in within last 30 days)
     * 
     * Delegates to DashboardService for consistent data aggregation
     */
    public function getActiveUsers()
    {
        if ($this->dashboardService) {
            return $this->dashboardService->getActiveUsers();
        }
        
        // Fallback: implement locally if service unavailable
    $dateFunction = "DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $stmt = $GLOBALS['pdo']->prepare("
            SELECT u.*, r.name as role_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            JOIN sessions s ON u.id = s.user_id
            WHERE s.last_activity > $dateFunction
            GROUP BY u.id
            ORDER BY u.created_at DESC
        ");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($users as &$user) {
            $stmt = $GLOBALS['pdo']->prepare("SELECT COUNT(*) as count FROM family_members WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $familyCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            $user['family_size'] = 1 + $familyCount;
            $stmt = $GLOBALS['pdo']->prepare("SELECT id, first_name, last_name, birth_year FROM family_members WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $user['family_members'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $users;
    }
    public function index()
    {
        // This method is called directly from routes, data is passed via App.php logic
        // The view will be rendered by the Layout system with the data
        return;
    }

    public function listUsers()
    {
        // This method is called directly from routes, data is passed via App.php logic
        // The view will be rendered by the Layout system with the data
        return;
    }

    public function createUser()
    {
        // Handle user creation logic
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user = new User();
            $user->name = $_POST['name'];
            $user->email = $_POST['email'];
            $user->password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $user->save();
            header('Location: /admin/users');
        }
        require_once '../src/Views/admin/create_user.php';
    }

    public function editUser($id)
    {
        // Handle user editing logic
        $user = User::find($id);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user->name = $_POST['name'];
            $user->email = $_POST['email'];
            $user->save();
            header('Location: /admin/users');
        }
        require_once '../src/Views/admin/edit_user.php';
    }

    public function deleteUser($id = null)
    {
        // Support POST body or optional param
        if (is_null($id)) {
            // Try to read from POST or query param
            $id = $_POST['user_id'] ?? $_GET['user_id'] ?? null;
        }
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID required']);
            return;
        }

        // Handle user deletion logic
        $user = User::find($id);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }
        $user->delete();

        // If this is an AJAX/JSON request, return JSON; otherwise redirect
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xhr = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        if ($xhr || strpos($accept, 'application/json') !== false) {
            echo json_encode(['success' => true, 'message' => 'User deleted']);
            return;
        }

        header('Location: /admin/users');
    }

    public function promoteUser()
    {
        // Handle user role promotion
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_POST['user_id'] ?? null;
            $roleId = $_POST['role_id'] ?? null;

            if (!$userId || !$roleId) {
                header('Location: /admin/users?error=invalid_data');
                exit;
            }

            // Verify the role exists
            $stmt = $GLOBALS['pdo']->prepare("SELECT id, name FROM roles WHERE id = ?");
            $stmt->execute([$roleId]);
            $role = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$role) {
                header('Location: /admin/users?error=invalid_role');
                exit;
            }

            // Update user role
            $stmt = $GLOBALS['pdo']->prepare("UPDATE users SET role_id = ? WHERE id = ?");
            $stmt->execute([$roleId, $userId]);

            // Log the promotion
            if (function_exists('getLogger')) {
                $logger = getLogger();
                $logger->info("User role promoted", [
                    'user_id' => $userId,
                    'new_role_id' => $roleId,
                    'new_role_name' => $role['name'],
                    'admin_user_id' => $_SESSION['user_id'] ?? 'unknown'
                ]);
            }

            header('Location: /admin/users?success=role_updated');
            exit;
        }

        // If not POST, redirect back
        header('Location: /admin/users');
        exit;
    }

    public function listModerators()
    {
        // Retrieve and display a list of moderators
        $moderators = Moderator::all();
        require_once '../src/Views/admin/moderators.php';
    }

    public function createModerator()
    {
        // Handle moderator creation logic
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $moderator = new Moderator();
            $moderator->name = $_POST['name'];
            $moderator->email = $_POST['email'];
            $moderator->password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $moderator->save();
            header('Location: /admin/moderators');
        }
        require_once '../src/Views/admin/create_moderator.php';
    }

    public function editModerator($id)
    {
        // Handle moderator editing logic
        $moderator = Moderator::find($id);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $moderator->name = $_POST['name'];
            $moderator->email = $_POST['email'];
            $moderator->save();
            header('Location: /admin/moderators');
        }
        require_once '../src/Views/admin/edit_moderator.php';
    }

    public function deleteModerator($id)
    {
        // Handle moderator deletion logic
        $moderator = Moderator::find($id);
        $moderator->delete();
        header('Location: /admin/moderators');
    }

    public function getDashboardStats()
    {
        // Delegate to DashboardService for consistent stats calculation
        if ($this->dashboardService) {
            return $this->dashboardService->getDashboardStats();
        }
        
        // Fallback: return empty stats if service unavailable
        error_log('AdminController: DashboardService unavailable, returning minimal stats');
        return [
            'total_users' => 0,
            'active_users' => 0,
            'total_events' => 0,
            'total_donations' => 0,
            'total_members' => 0,
        ];
    }

    public function getRecentActivity()
    {
        // Delegate to DashboardService for consistent activity data
        if ($this->dashboardService) {
            return $this->dashboardService->getRecentActivity();
        }
        
        // Fallback: return empty activities if service unavailable
        error_log('AdminController: DashboardService unavailable, returning empty activities');
        return [];
    }

    public function getUsers()
    {
        // Delegate to DashboardService for consistent user data retrieval
        if ($this->dashboardService) {
            return $this->dashboardService->getUsers();
        }
        
        // Fallback: query directly if service unavailable
        $stmt = $GLOBALS['pdo']->query("
            SELECT u.*, r.name as role_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            ORDER BY u.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get member form for admin to add/edit users and family members
     * Supports: ?type=user&user_id=X or ?type=member&id=X&user_id=Y
     */
    public function getAdminMemberForm()
    {
        $type = $_GET['type'] ?? 'user';
        $userId = $_GET['user_id'] ?? null;
        $memberId = $_GET['id'] ?? null;

        $member = [];
        $isMainUser = ($type === 'user');
        $isEditMode = !empty($memberId) || !empty($userId);

        if ($type === 'user' && $userId) {
            // Edit existing user
            $userModel = new User($GLOBALS['pdo'] ?? null);
            $userData = $userModel->find($userId);

            if (!$userData) {
                http_response_code(404);
                exit('User not found');
            }

            // Merge user fields + family member fields
            $member = [
                'id' => $userData['id'],
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
            ];
        } elseif ($type === 'user' && !$userId) {
            // Add new user
            $isEditMode = false;
            $member = [];
        } elseif ($type === 'member' && $memberId) {
            // Edit existing family member
            $memberModel = new \App\Models\Member($GLOBALS['pdo'] ?? null);
            $member = $memberModel->getById($memberId);

            if (!$member) {
                http_response_code(404);
                exit('Member not found');
            }
        } elseif ($type === 'member' && $userId) {
            // Add new family member for specific user
            $member = ['user_id' => $userId];
            $isEditMode = false;
        }

        // Set header for plain HTML
        header('Content-Type: text/html; charset=utf-8');

        // Render the unified form template
        include __DIR__ . '/../Views/forms/member-form.php';
    }

    /**
     * Save member (user or family member) - admin version with override permissions
     */
    public function saveAdminMember()
    {
        $memberId = $_POST['member_id'] ?? null;
        $userId = $_POST['user_id'] ?? null;
        $isMainUser = ($_POST['is_main_user'] ?? '0') === '1';

        // Normalize member_id
        if (empty($memberId) || $memberId === "0") {
            $memberId = null;
        } else {
            $memberId = (int)$memberId;
        }

        try {
            if ($isMainUser && $userId) {
                // Update existing user
                $userModel = new User($GLOBALS['pdo'] ?? null);
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

                $userModel->update($userId, $userUpdateData);

                // Update or create 'self' family member record
                $familyMemberModel = new \App\Models\Member($GLOBALS['pdo'] ?? null);
                
                // Check if self record exists
                $stmt = $GLOBALS['pdo']->prepare("SELECT id FROM family_members WHERE user_id = ? AND relationship = 'self' LIMIT 1");
                $stmt->execute([$userId]);
                $existingSelf = $stmt->fetch(PDO::FETCH_ASSOC);

                $memberUpdateData = [
                    'first_name' => $_POST['first_name'] ?? '',
                    'last_name' => $_POST['last_name'] ?? '',
                    'birth_year' => !empty($_POST['birth_year']) ? $_POST['birth_year'] : null,
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
                    $familyMemberModel->update($existingSelf['id'], $memberUpdateData);
                } else {
                    $memberUpdateData['user_id'] = $userId;
                    $familyMemberModel->create($memberUpdateData);
                }

                echo json_encode(['success' => true, 'message' => 'User updated']);

            } elseif ($isMainUser && !$userId) {
                // Create new user + self family member
                $userModel = new User($GLOBALS['pdo'] ?? null);
                
                // Create user account
                $newUserId = $userModel->create([
                    'first_name' => $_POST['first_name'] ?? '',
                    'last_name' => $_POST['last_name'] ?? '',
                    'email' => $_POST['email'] ?? '',
                    'password' => password_hash('temp' . rand(1000, 9999), PASSWORD_DEFAULT), // Temp password
                    'phone_e164' => $_POST['phone_e164'] ?? '',
                    'street_address' => $_POST['street_address'] ?? '',
                    'city' => $_POST['city'] ?? '',
                    'state' => $_POST['state'] ?? '',
                    'zip_code' => $_POST['zip_code'] ?? '',
                    'country' => $_POST['country'] ?? 'USA',
                    'role_id' => 2 // Default member role
                ]);

                // Create self family member
                $familyMemberModel = new \App\Models\Member($GLOBALS['pdo'] ?? null);
                $familyMemberModel->create([
                    'user_id' => $newUserId,
                    'first_name' => $_POST['first_name'] ?? '',
                    'last_name' => $_POST['last_name'] ?? '',
                    'birth_year' => !empty($_POST['birth_year']) ? $_POST['birth_year'] : null,
                    'gender' => $_POST['gender'] ?? null,
                    'email' => $_POST['email'] ?? '',
                    'phone_e164' => $_POST['phone_e164'] ?? '',
                    'relationship' => 'self',
                    'occupation' => $_POST['occupation'] ?? '',
                    'business_info' => $_POST['business_info'] ?? '',
                    'village' => $_POST['village'] ?? '',
                    'mosal' => $_POST['mosal'] ?? ''
                ]);

                echo json_encode(['success' => true, 'message' => 'User created']);

            } elseif ($memberId) {
                // Edit existing family member
                $familyMemberModel = new \App\Models\Member($GLOBALS['pdo'] ?? null);
                
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

                $familyMemberModel->update($memberId, $memberData);
                echo json_encode(['success' => true, 'message' => 'Member updated']);

            } else {
                // Add new family member
                $familyMemberModel = new \App\Models\Member($GLOBALS['pdo'] ?? null);

                $memberData = [
                    'user_id' => $userId,
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

                $familyMemberModel->create($memberData);
                echo json_encode(['success' => true, 'message' => 'Member added']);
            }

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}