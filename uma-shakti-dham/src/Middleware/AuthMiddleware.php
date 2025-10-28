<?php

namespace App\Middleware;

use App\Services\AuthService;

class AuthMiddleware
{
    protected $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function handle($request, $next)
    {
        if (!$this->authService->isAuthenticated()) {
            header('Location: /login.php');
            exit();
        }

        return $next($request);
    }
}