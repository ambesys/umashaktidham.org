<?php

namespace App\Middleware;

use App\Services\SessionService;

class UserAuthMiddleware
{
    protected $sessionService;

    public function __construct()
    {
        // Use the global PDO when available so session handling is consistent
        $pdo = $GLOBALS['pdo'] ?? null;
        $this->sessionService = new SessionService($pdo);
    }

    public function handle($request, $next)
    {
        if (!$this->sessionService->isAuthenticated()) {
            header('Location: /login');
            exit();
        }

        return $next($request);
    }
}