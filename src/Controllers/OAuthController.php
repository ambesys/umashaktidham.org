<?php

namespace App\Controllers;

use App\Services\OAuthService;
use App\Services\SessionService;

/**
 * OAuthController
 *
 * Handles OAuth authentication flows (Google, Facebook)
 */
class OAuthController
{
    private $oauthService;
    private $sessionService;

    public function __construct(OAuthService $oauthService, SessionService $sessionService = null)
    {
        $this->oauthService = $oauthService;
        $this->sessionService = $sessionService;
    }

    /**
     * Redirect to OAuth provider for authentication
     */
    public function redirect(string $provider)
    {
        try {
            $url = $this->oauthService->getAuthorizationUrl($provider);

            // Redirect to provider
            header('Location: ' . $url);
            exit;

        } catch (\Exception $e) {
            // Handle error - redirect to login with error
            header('Location: /login?error=oauth_config');
            exit;
        }
    }

    /**
     * Handle OAuth callback
     */
    public function callback(string $provider)
    {
        $code = $_GET['code'] ?? null;
        $error = $_GET['error'] ?? null;

        if ($error) {
            header('Location: /login?error=oauth_' . $error);
            exit;
        }

        if (!$code) {
            header('Location: /login?error=oauth_no_code');
            exit;
        }

        try {
            $user = $this->oauthService->handleCallback($provider, $code);

            // Start session and log user in using SessionService
            if ($this->sessionService) {
                $this->sessionService->setAuthenticatedUser($user['id'], $user['role_id'] ?? null);
            } else {
                // Fallback to direct session handling
                if (session_status() !== PHP_SESSION_ACTIVE) session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role_id'] ?? null;
            }

            // Redirect to dashboard
            header('Location: /dashboard');
            exit;

        } catch (\Exception $e) {
            error_log('OAuth callback error: ' . $e->getMessage());
            header('Location: /login?error=oauth_callback');
            exit;
        }
    }
}