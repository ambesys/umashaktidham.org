<?php

namespace App\Controllers;

use App\Services\UserService;
use App\Services\NotificationService;
use App\Services\DashboardService;

class UserController
{
    protected $userService;
    protected $dashboardService;

    public function __construct()
    {
        // Pass PDO instance to UserService
        $pdo = $GLOBALS['pdo'] ?? null;
        $this->userService = new UserService($pdo);
        
        // Initialize DashboardService for stats calculations
        try {
            $this->dashboardService = new DashboardService($pdo);
        } catch (\Throwable $e) {
            error_log('UserController: DashboardService initialization failed: ' . $e->getMessage());
            $this->dashboardService = null;
        }
    }

    /**
     * Dashboard wrapper - delegate to DashboardController
     * 
     * Enhanced to provide DashboardService for stats computation
     */
    public function dashboard()
    {
        $dc = new \App\Controllers\DashboardController();
        return $dc->index();
    }

    /**
     * Render profile for current user
     */
    public function profile()
    {
        $userId = $_SESSION['user_id'] ?? null;
        $mc = new \App\Controllers\MemberController();
        return $mc->profile($userId);
    }

    /**
     * Get user-specific statistics for personal dashboard
     * 
     * Returns profile completeness, family size, events registered, donations made, etc.
     * Useful for user dashboard rendering.
     * 
     * @param int|null $userId Optional user ID; defaults to current session user
     * @return array User statistics
     */
    public function getUserStats($userId = null)
    {
        $userId = $userId ?? ($_SESSION['user_id'] ?? null);
        
        if (!$userId) {
            return [
                'user' => null,
                'family_size' => 0,
                'events_registered' => 0,
                'donations_count' => 0,
                'total_donated' => 0,
                'profile_completeness' => 0,
            ];
        }

        // Get stats from DashboardService if available
        if ($this->dashboardService) {
            $stats = $this->dashboardService->getUserStats($userId);
            // Add profile completeness calculation
            $stats['profile_completeness'] = $this->calculateProfileCompleteness($stats['user'] ?? []);
            return $stats;
        }

        // Fallback to local implementation if service unavailable
        return [
            'user' => null,
            'family_size' => 0,
            'events_registered' => 0,
            'donations_count' => 0,
            'total_donated' => 0,
            'profile_completeness' => 0,
        ];
    }

    /**
     * Calculate profile completeness percentage
     * 
     * Based on filled fields: email, phone, city, avatar, bio, etc.
     * 
     * @param array $user User data
     * @return int Percentage from 0-100
     */
    private function calculateProfileCompleteness($user = [])
    {
        if (empty($user)) {
            return 0;
        }

        $fields = ['email', 'username', 'phone_e164', 'city', 'avatar', 'bio'];
        $filled = 0;

        foreach ($fields as $field) {
            if (!empty($user[$field])) {
                $filled++;
            }
        }

        return intval(($filled / count($fields)) * 100);
    }

    /**
     * Update profile (handles both JSON and form data)
     */
    public function updateProfile($userId = null)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        // Get current user ID from session
        $currentUserId = $_SESSION['user_id'] ?? null;
        if (!$currentUserId) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        // Parse request data (JSON or form-encoded)
        $data = [];
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $isJson = false;
        
        if (strpos($contentType, 'application/json') !== false) {
            // Handle JSON
            $isJson = true;
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);
            if (is_null($data)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON payload']);
                return;
            }
        } else {
            // Handle form-encoded
            $data = $_POST;
        }

        // Extract user ID to update (default to current user)
        $userIdToUpdate = $data['id'] ?? $currentUserId;
        
        // Validate that user can only update their own profile (unless admin)
        $role = $_SESSION['user_role'] ?? 1;
        if ($userIdToUpdate != $currentUserId && $role < 2) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }

        // Update profile: Check if relationship is 'self' (user's own profile record)
        // If so, update/insert in family_members with relationship='self'
        // Otherwise update basic user info
        
        try {
            $pdo = $GLOBALS['pdo'] ?? null;
            $relationship = $data['relationship'] ?? null;
            $result = false;
            
            if ($relationship === 'self') {
                // Update user's own profile in family_members as 'self' relationship
                $fmModel = new \App\Models\FamilyMember($pdo);
                
                // First check if a 'self' record exists for this user
                $selfRecord = $pdo->prepare("SELECT id FROM family_members WHERE user_id = ? AND relationship = 'self' LIMIT 1");
                $selfRecord->execute([$userIdToUpdate]);
                $existingRecord = $selfRecord->fetch();
                
                if ($existingRecord) {
                    // Update existing record
                    $recordId = $existingRecord['id'];
                    $result = $fmModel->update($recordId, $data);
                } else {
                    // Insert new self record - but this shouldn't normally happen
                    // For now just return error
                    if ($isJson) {
                        http_response_code(400);
                        echo json_encode(['error' => 'No self profile record found. Please contact support.']);
                    } else {
                        $_SESSION['error'] = "No self profile record found. Please contact support.";
                        header('Location: /members/profile.php?id=' . $userIdToUpdate);
                    }
                    return;
                }
            } else {
                // Update basic user info in users table
                $userModel = new \App\Models\User($pdo);
                
                // Prepare update data for users table
                $userData = [
                    'first_name' => $data['first_name'] ?? null,
                    'last_name' => $data['last_name'] ?? null,
                    'email' => $data['email'] ?? null,
                    'phone_e164' => $data['phone_e164'] ?? $data['phone'] ?? null,
                    'name' => ($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')
                ];
                
                $result = $userModel->update($userIdToUpdate, $userData);
                
                // Also update user's 'self' record in family_members if it exists
                if ($result) {
                    $fmModel = new \App\Models\FamilyMember($pdo);
                    $selfRecord = $pdo->prepare("SELECT id FROM family_members WHERE user_id = ? AND relationship = 'self' LIMIT 1");
                    $selfRecord->execute([$userIdToUpdate]);
                    $existingRecord = $selfRecord->fetch();
                    
                    if ($existingRecord) {
                        // Update the family_members 'self' record with full profile data
                        $fmModel->update($existingRecord['id'], $data);
                    }
                }
            }
            
            if ($result) {
                if ($isJson) {
                    // Return JSON response for AJAX requests
                    http_response_code(200);
                    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
                } else {
                    // Redirect for form submissions
                    header('Location: /members/profile.php?id=' . $userIdToUpdate);
                    exit();
                }
            } else {
                if ($isJson) {
                    // Return JSON error for AJAX requests
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Profile update failed. Please try again.']);
                } else {
                    // Show error for form submissions
                    $_SESSION['error'] = "Profile update failed. Please try again.";
                    header('Location: /members/profile.php?id=' . $userIdToUpdate);
                    exit();
                }
            }
        } catch (\Exception $e) {
            if ($isJson) {
                http_response_code(500);
                echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
            } else {
                $_SESSION['error'] = "Error: " . $e->getMessage();
                header('Location: /members/profile.php?id=' . $userIdToUpdate);
            }
        }
    }

    /**
     * Show family list for current user
     */
    public function family()
    {
        $userId = $_SESSION['user_id'] ?? null;
        $fc = new \App\Controllers\FamilyController();
        return $fc->index($userId);
    }

    public function addFamilyMember()
    {
        $fc = new \App\Controllers\FamilyController();
        return $fc->addFamilyMember();
    }

    public function updateFamilyMember()
    {
        $fc = new \App\Controllers\FamilyController();
        return $fc->updateFamilyMember();
    }

    public function deleteFamilyMember()
    {
        $fc = new \App\Controllers\FamilyController();
        return $fc->deleteFamilyMember();
    }

    public function members()
    {
        // Render members index view via MemberController or view
        $mc = new \App\Controllers\MemberController();
        return $mc->manageFamily($_SESSION['user_id'] ?? null);
    }

    public function viewMember()
    {
        $mc = new \App\Controllers\MemberController();
        return $mc->profile($_GET['id'] ?? ($_SESSION['user_id'] ?? null));
    }

    /**
     * Show change password form
     */
    public function showChangePasswordForm()
    {
        // Render using layout helper if available
        if (function_exists('render_view')) {
            render_view('src/Views/user/change-password.php');
            return;
        }
        include 'src/Views/user/change-password.php';
    }

    /**
     * Handle change password POST
     */
    public function handleChangePassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /user/change-password');
            exit;
        }

        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (empty($current) || empty($new) || empty($confirm)) {
            $error = 'All fields are required.';
            render_view('src/Views/user/change-password.php', ['error' => $error]);
            return;
        }

        if ($new !== $confirm) {
            $error = 'New password and confirmation do not match.';
            render_view('src/Views/user/change-password.php', ['error' => $error]);
            return;
        }

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            header('Location: /login');
            exit;
        }

        $auth = new \App\Services\AuthService($GLOBALS['pdo'] ?? null, null);
        try {
            $auth->changePassword((int)$userId, $current, $new);
            // Optionally, log user out to force re-login
            // $auth->logout();
            // Send password changed notification (best-effort)
            try {
                // Load user details for the email
                require_once __DIR__ . '/../../config/database.php';
                $userModel = new \App\Models\User($pdo ?? null);
                $user = $userModel->find($userId);

                // Instantiate NotificationService (provider chosen by env)
                $notif = new \App\Services\NotificationService();
                $notif->sendPasswordChanged(is_array($user) ? $user : ['id' => $userId, 'email' => $_SESSION['user_email'] ?? null]);
            } catch (\Throwable $e) {
                error_log('UserController: password-change notification failed: ' . $e->getMessage());
            }
            render_view('src/Views/user/change-password.php', ['success' => 'Password changed successfully.']);
            return;
        } catch (\Exception $e) {
            render_view('src/Views/user/change-password.php', ['error' => $e->getMessage()]);
            return;
        }
    }

    public function updateMember()
    {
        $mc = new \App\Controllers\MemberController();
        return $mc->updateProfile($_POST['id'] ?? ($_SESSION['user_id'] ?? null));
    }

    public function updateUser()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Get raw input
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);

            // Validate JSON payload
            if (is_null($data)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON payload. Received: ' . substr($rawInput, 0, 100)]);
                return;
            }

            // Check for required fields
            if (empty($data['id']) || !is_numeric($data['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'User ID is required and must be numeric.']);
                return;
            }

            // Rename relation to relationship if present
            if (isset($data['relation'])) {
                $data['relationship'] = $data['relation'];
                unset($data['relation']);
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
