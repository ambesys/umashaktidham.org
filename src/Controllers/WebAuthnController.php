<?php

namespace App\Controllers;

use App\Services\WebAuthnService;

/**
 * WebAuthnController
 *
 * Handles WebAuthn/Passkey registration and authentication
 */
class WebAuthnController
{
    private $webauthnService;

    public function __construct(WebAuthnService $webauthnService)
    {
        $this->webauthnService = $webauthnService;
    }

    /**
     * Get registration challenge for WebAuthn
     */
    public function getRegistrationChallenge()
    {
        // Require user to be logged in
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            return;
        }

        try {
            $challenge = $this->webauthnService->generateRegistrationChallenge($userId);

            header('Content-Type: application/json');
            echo json_encode($challenge);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Register a WebAuthn credential
     */
    public function registerCredential()
    {
        // Require user to be logged in
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['credential'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid credential data']);
            return;
        }

        try {
            $success = $this->webauthnService->registerCredential($userId, $input['credential']);

            header('Content-Type: application/json');
            echo json_encode(['success' => $success]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Get authentication challenge for WebAuthn
     */
    public function getAuthenticationChallenge()
    {
        // Require user to be logged in
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            return;
        }

        try {
            $challenge = $this->webauthnService->generateAuthenticationChallenge($userId);

            header('Content-Type: application/json');
            echo json_encode($challenge);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Authenticate using WebAuthn credential
     */
    public function authenticate()
    {
        // Require user to be logged in
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['credential'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid credential data']);
            return;
        }

        try {
            $success = $this->webauthnService->authenticate($userId, $input['credential']);

            header('Content-Type: application/json');
            echo json_encode(['success' => $success]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Get user's WebAuthn credentials
     */
    public function getCredentials()
    {
        // Require user to be logged in
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            return;
        }

        try {
            $credentials = $this->webauthnService->getUserCredentials($userId);

            header('Content-Type: application/json');
            echo json_encode(['credentials' => $credentials]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove a WebAuthn credential
     */
    public function removeCredential()
    {
        // Require user to be logged in
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $credentialId = $input['credentialId'] ?? null;

        if (!$credentialId) {
            http_response_code(400);
            echo json_encode(['error' => 'Credential ID required']);
            return;
        }

        try {
            $success = $this->webauthnService->removeCredential($userId, $credentialId);

            header('Content-Type: application/json');
            echo json_encode(['success' => $success]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}