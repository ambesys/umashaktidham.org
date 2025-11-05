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
        
        // Initialize models with database connection
        $userModel = new User($this->pdo ?? null);
        $memberModel = new Member($this->pdo ?? null);
        $familyModel = new Family($this->pdo ?? null);
        
        $user = $userModel->find($userId);
    $members = method_exists($memberModel, 'getAll') ? $memberModel->getAll($userId) : []; // fetch members for current user
        $families = method_exists($familyModel, 'getFamilyByUserId') ? $familyModel->getFamilyByUserId($userId) : [];

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
            $userModel = new User($this->pdo ?? null);
            $user = $userModel->find($userId);
            $userModel->update($userId, $_POST);
            header('Location: /dashboard.php');
            exit();
        }

        // Load the edit profile view
        $userModel = new User($this->pdo ?? null);
        $user = $userModel->find($userId);
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
        
        // Initialize models with database connection
        $userModel = new User($this->pdo ?? null);
        $memberModel = new Member($this->pdo ?? null);
        $familyModel = new Family($this->pdo ?? null);
        
        $user = $userModel->find($userId);
    $members = method_exists($memberModel, 'getAll') ? $memberModel->getAll($userId) : [];
        $families = method_exists($familyModel, 'getFamilyByUserId') ? $familyModel->getFamilyByUserId($userId) : [];

        return [
            'user' => $user,
            'members' => $members,
            'families' => $families,
            'memberCount' => is_array($members) ? count($members) : 0,
            'familyCount' => is_array($families) ? count($families) : 0
        ];
    }
}