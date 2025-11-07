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
    private $pdo;

    public function __construct(OAuthService $oauthService, SessionService $sessionService = null)
    {
        $this->oauthService = $oauthService;
        $this->sessionService = $sessionService;
        
        // Initialize PDO for role lookup
        $cfg = __DIR__ . '/../config/database.php';
        if (file_exists($cfg)) {
            require $cfg; // expects $pdo
            if (isset($pdo) && $pdo instanceof \PDO) {
                $this->pdo = $pdo;
            }
        }
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

            // Set 'user' session key to match password-based login
            $userSession = [
                'id' => $user['id'],
                'name' => $user['name'] ?? '',
                'email' => $user['email'] ?? '',
                'first_name' => $user['first_name'] ?? '',
                'last_name' => $user['last_name'] ?? '',
            ];
            if ($this->sessionService) {
                // Get role name from role_id
                $roleName = $this->getRoleName($user['role_id'] ?? null);
                $this->sessionService->setAuthenticatedUser($user['id'], $user['role_id'] ?? null, $roleName);
                $this->sessionService->setSessionData('user', $userSession);
                $this->sessionService->setSessionData('auth_type', $provider); // Store auth type for logout
                LoggerService::info("Session created successfully via SessionService");
            } else {
                if (session_status() !== PHP_SESSION_ACTIVE) session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $this->getRoleName($user['role_id'] ?? null); // Store role name
                $_SESSION['user'] = $userSession;
                $_SESSION['auth_type'] = $provider; // Store auth type for logout
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

    /**
     * Get role name by role ID
     */
    private function getRoleName(?int $roleId): string
    {

        LoggerService::debug("roleId", $roleId);
        if (!$roleId) {
            return 'user'; // Default role
        }
        
        if (!$this->pdo) {
            return 'user'; // Fallback if no PDO
        }
        
        $stmt = $this->pdo->prepare("SELECT name FROM roles WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $roleId, \PDO::PARAM_INT);
        $stmt->execute();
        $role = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $role['name'] ?? 'user';
    }
}