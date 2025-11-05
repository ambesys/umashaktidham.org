<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Member;
use App\Models\Family;
use App\Services\AuthService;
use App\Services\SessionService;

class DashboardController
{
    protected $authService;
    protected $sessionService;

    public function __construct()
    {
        // Load PDO from project config (this file should populate $pdo)
        $cfg = __DIR__ . '/../../config/database.php';
        if (file_exists($cfg)) {
            require $cfg;
        }

        // Initialize session and auth services with PDO
        $this->sessionService = new SessionService($pdo ?? null);
        $this->authService = new AuthService($pdo ?? null, $this->sessionService);
    }

    public function index()
    {
        // Check authentication via SessionService
        if (!$this->sessionService->isAuthenticated()) {
            header('Location: /login.php');
            exit();
        }

        // Get user details from session service
        $userId = $this->sessionService->getCurrentUserId();
        $user = User::find($userId);
        $members = is_callable([Member::class, 'where']) ? Member::where('user_id', $userId)->get() : [];
        $families = is_callable([Family::class, 'where']) ? Family::where('user_id', $userId)->get() : [];

        // Load the dashboard view
        include_once __DIR__ . '/../Views/dashboard/index.php';
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
            $user = User::find($userId);
            $user->update($_POST);
            header('Location: /dashboard.php');
            exit();
        }

        // Load the edit profile view
        $user = User::find($userId);
        include_once __DIR__ . '/../Views/members/profile.php';
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
        $user = User::find($userId);
        $members = is_callable([Member::class, 'where']) ? Member::where('user_id', $userId)->get() : [];
        $families = is_callable([Family::class, 'where']) ? Family::where('user_id', $userId)->get() : [];

        return [
            'user' => $user,
            'members' => $members,
            'families' => $families,
            'memberCount' => is_array($members) ? count($members) : 0,
            'familyCount' => is_array($families) ? count($families) : 0
        ];
    }
}