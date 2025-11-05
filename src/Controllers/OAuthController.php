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
        // Debug: Write to a temporary file to see what's happening
        $debugFile = __DIR__ . '/../../logs/oauth_debug.log';
        $debugDir = dirname($debugFile);
        if (!is_dir($debugDir)) {
            mkdir($debugDir, 0755, true);
        }
        file_put_contents($debugFile, date('Y-m-d H:i:s') . " - Starting OAuth callback for $provider\n", FILE_APPEND);
        
        $code = $_GET['code'] ?? null;
        $error = $_GET['error'] ?? null;

        file_put_contents($debugFile, "Code: " . ($code ? 'present' : 'missing') . "\n", FILE_APPEND);
        file_put_contents($debugFile, "Error: " . ($error ?: 'none') . "\n", FILE_APPEND);

        if ($error) {
            file_put_contents($debugFile, "OAuth provider error: $error\n", FILE_APPEND);
            header('Location: /login?error=oauth_' . $error);
            exit;
        }

        if (!$code) {
            file_put_contents($debugFile, "No authorization code provided\n", FILE_APPEND);
            header('Location: /login?error=oauth_no_code');
            exit;
        }

        try {
            file_put_contents($debugFile, "Attempting to handle OAuth callback...\n", FILE_APPEND);
            $user = $this->oauthService->handleCallback($provider, $code);
            file_put_contents($debugFile, "OAuth callback successful, user ID: " . ($user['id'] ?? 'unknown') . "\n", FILE_APPEND);

            // Start session and log user in using SessionService
            if ($this->sessionService) {
                $this->sessionService->setAuthenticatedUser($user['id'], $user['role_id'] ?? null);
                file_put_contents($debugFile, "Session created successfully via SessionService\n", FILE_APPEND);
            } else {
                // Fallback to direct session handling
                if (session_status() !== PHP_SESSION_ACTIVE) session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role_id'] ?? null;
                file_put_contents($debugFile, "Fallback session created - user_id: " . $_SESSION['user_id'] . ", session_id: " . session_id() . "\n", FILE_APPEND);
            }

            // Additional session debugging
            file_put_contents($debugFile, "Session status after login: " . session_status() . "\n", FILE_APPEND);
            file_put_contents($debugFile, "Session ID: " . session_id() . "\n", FILE_APPEND);
            file_put_contents($debugFile, "Session save path: " . session_save_path() . "\n", FILE_APPEND);
            file_put_contents($debugFile, "All session data: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

            file_put_contents($debugFile, "Redirecting to dashboard...\n", FILE_APPEND);
            // Redirect to dashboard
            header('Location: /dashboard');
            exit;

        } catch (\Exception $e) {
            file_put_contents($debugFile, "Exception caught: " . $e->getMessage() . "\n", FILE_APPEND);
            file_put_contents($debugFile, "Stack trace: " . $e->getTraceAsString() . "\n", FILE_APPEND);
            
            // Log the detailed error using LoggerService
            if (function_exists('getLogger')) {
                $logger = getLogger();
                $logger->error("OAuth callback error for $provider", [
                    'error' => $e->getMessage(),
                    'provider' => $provider,
                    'trace' => $e->getTraceAsString(),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
                ]);
            } else {
                error_log('OAuth callback error: ' . $e->getMessage());
            }
            
            // Provide user-friendly error message based on exception type
            $errorMessage = 'oauth_callback';
            if (strpos($e->getMessage(), 'redirect_uri_mismatch') !== false) {
                $errorMessage = 'oauth_redirect_mismatch';
            } elseif (strpos($e->getMessage(), 'invalid_client') !== false) {
                $errorMessage = 'oauth_invalid_client';
            } elseif (strpos($e->getMessage(), 'access_denied') !== false) {
                $errorMessage = 'oauth_access_denied';
            }
            
            file_put_contents($debugFile, "Redirecting to login with error: $errorMessage\n", FILE_APPEND);
            header('Location: /login?error=' . $errorMessage);
            exit;
        }
    }
}