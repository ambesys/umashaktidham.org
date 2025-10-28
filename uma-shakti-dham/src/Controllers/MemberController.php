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
        $this->memberModel = new Member();
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
        // Fetch user profile details
        $member = $this->memberModel->find($userId);
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