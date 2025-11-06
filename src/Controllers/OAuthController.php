<?php

namespace App\Controllers;

use App\Services\OAuthService;
use App\Services\SessionService;
use App\Services\LoggerService;

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
            if (function_exists('getLogger')) {
                $logger = getLogger();
                $logger->error("OAuth redirect error for $provider", [
                    'error' => $e->getMessage(),
                    'provider' => $provider,
                    'trace' => $e->getTraceAsString(),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
            }
            header('Location: /login?error=oauth_config');
            exit;
        }
    }

    /**
     * Handle OAuth callback
     */
    public function callback(string $provider)
    {
        LoggerService::info("Starting OAuth callback for $provider");

        $code = $_GET['code'] ?? null;
        $error = $_GET['error'] ?? null;

        LoggerService::debug("Code: " . ($code ? 'present' : 'missing'));
        LoggerService::debug("Error: " . ($error ?: 'none'));

        if ($error) {
            LoggerService::error("OAuth provider error: $error");
            header('Location: /login?error=oauth_' . $error);
            exit;
        }

        if (!$code) {
            LoggerService::error("No authorization code provided");
            header('Location: /login?error=oauth_no_code');
            exit;
        }

        try {
            LoggerService::info("Attempting to handle OAuth callback...");
            $user = $this->oauthService->handleCallback($provider, $code);
            LoggerService::info("OAuth callback successful, user ID: " . ($user['id'] ?? 'unknown'));

            if ($this->sessionService) {
                $this->sessionService->setAuthenticatedUser($user['id'], $user['role_id'] ?? null);
                LoggerService::info("Session created successfully via SessionService");
            } else {
                if (session_status() !== PHP_SESSION_ACTIVE) session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role_id'] ?? null;
                LoggerService::info("Fallback session created - user_id: " . $_SESSION['user_id'] . ", session_id: " . session_id());
            }

            LoggerService::debug("Session status after login: " . session_status());
            LoggerService::debug("Session ID: " . session_id());
            LoggerService::debug("Session save path: " . session_save_path());
            LoggerService::debug("All session data: " . print_r($_SESSION, true));

            LoggerService::info("Redirecting to dashboard...");
            header('Location: /dashboard');
            exit;

        } catch (\Exception $e) {
            LoggerService::error("Exception caught: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'provider' => $provider,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ]);

            $errorMessage = 'oauth_callback';
            if (strpos($e->getMessage(), 'redirect_uri_mismatch') !== false) {
                $errorMessage = 'oauth_redirect_mismatch';
            } elseif (strpos($e->getMessage(), 'invalid_client') !== false) {
                $errorMessage = 'oauth_invalid_client';
            } elseif (strpos($e->getMessage(), 'access_denied') !== false) {
                $errorMessage = 'oauth_access_denied';
            }

            LoggerService::info("Redirecting to login with error: $errorMessage");
            header('Location: /login?error=' . $errorMessage);
            exit;
        }
    }
}