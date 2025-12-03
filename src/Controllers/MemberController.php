<?php

namespace App\Controllers;

use App\Models\Member;
use App\Services\AuthService;

class MemberController
{
    protected $memberModel;
    protected $authService;

    public function __construct()
    {
        // Prefer PDO-based model with explicit PDO when available
        try {
            $this->memberModel = new Member($GLOBALS['pdo'] ?? null);
        } catch (\Throwable $e) {
            // Fallback: instantiate without PDO
            $this->memberModel = new Member();
        }
        $this->authService = new AuthService();
    }

    public function register()
    {
        // Handle user registration logic
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $result = $this->memberModel->create($data);
            if ($result) {
                // Redirect to success page or dashboard
                header('Location: /dashboard.php');
                exit();
            } else {
                // Handle registration error
                $error = "Registration failed. Please try again.";
            }
        }
        include '../src/Views/auth/register.php';
    }

    public function profile($userId)
    {
        // Fetch user profile details using safe PDO query if needed
        $member = null;
        if (method_exists($this->memberModel, 'find')) {
            $member = $this->memberModel->find($userId);
        } else {
            // Fallback to direct query
            if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof \PDO) {
                $stmt = $GLOBALS['pdo']->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(\PDO::FETCH_ASSOC);

                // Fetch 'self' family member record if any
                $stmt = $GLOBALS['pdo']->prepare("SELECT * FROM family_members WHERE user_id = ? AND relationship = 'self' LIMIT 1");
                $stmt->execute([$userId]);
                $self = $stmt->fetch(\PDO::FETCH_ASSOC);

                // Merge user + self fields into a single array expected by the view
                $member = array_merge($user ?: [], $self ?: []);
            }
        }

        if (function_exists('render_view')) {
            render_view('src/Views/members/profile.php', ['member' => $member]);
            return;
        }
        include '../src/Views/members/profile.php';
    }

    public function updateProfile($userId)
    {
        // Handle profile update logic
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $result = $this->memberModel->update($userId, $data);
            if ($result) {
                // Redirect to profile page with success message
                header('Location: /members/profile.php?id=' . $userId);
                exit();
            } else {
                // Handle update error
                $error = "Profile update failed. Please try again.";
            }
        }
        $this->profile($userId);
    }

    public function manageFamily($userId)
    {
        // Handle family details management
        include '../src/Views/members/family.php';
    }
}