<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Member;
use App\Models\Family;
use App\Services\AuthService;

class DashboardController
{
    protected $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function index()
    {
        // Check if user is authenticated
        if (!$this->authService->isAuthenticated()) {
            header('Location: /login.php');
            exit();
        }

        // Get user details
        $userId = $_SESSION['user_id'];
        $user = User::find($userId);
        $members = Member::where('user_id', $userId)->get();
        $families = Family::where('user_id', $userId)->get();

        // Load the dashboard view
        include_once __DIR__ . '/../Views/dashboard/index.php';
    }

    public function editProfile()
    {
        // Check if user is authenticated
        if (!$this->authService->isAuthenticated()) {
            header('Location: /login.php');
            exit();
        }

        $userId = $_SESSION['user_id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Update user details
            $user = User::find($userId);
            $user->update($_POST);
            header('Location: /dashboard.php');
            exit();
        }

        // Load the edit profile view
        $user = User::find($userId);
        include_once __DIR__ . '/../Views/members/profile.php';
    }

    public function manageFamily()
    {
        // Check if user is authenticated
        if (!$this->authService->isAuthenticated()) {
            header('Location: /login.php');
            exit();
        }

        $userId = $_SESSION['user_id'];
        $families = Family::where('user_id', $userId)->get();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Handle family details submission
            $family = new Family();
            $family->user_id = $userId;
            $family->fill($_POST);
            $family->save();
            header('Location: /dashboard.php');
            exit();
        }

        // Load the family management view
        include_once __DIR__ . '/../Views/members/family.php';
    }
}