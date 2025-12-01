<?php

namespace App\Middleware;

use App\Services\SessionService;

class AdminAuthMiddleware
{
    protected $sessionService;

    public function __construct()
    {
        $this->sessionService = new SessionService();
    }

    public function handle($request, $next)
    {
        if (!$this->sessionService->isAuthenticated()) {
            header('Location: /login');
            exit();
        }

        $userRole = $this->sessionService->getCurrentUserRole();
        $userRoleName = $this->sessionService->getSessionDataByKey('user_role');

        // Allow admin (role_id = 2) or moderator (role_id = 3)
        if ($userRole !== 2 && $userRole !== 3 && $userRoleName !== 'admin' && $userRoleName !== 'moderator') {
            header('Location: /user/dashboard');
            exit();
        }

        return $next($request);
    }
}