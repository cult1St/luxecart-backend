<?php

namespace App\Controllers;

use Core\Database;
use Core\Request;
use Core\Response;
use App\Services\AuthService;
use Exception;

/**
 * Base Controller
 * 
 * Parent class for all controllers
 */
abstract class BaseController
{
    protected Database $db;
    protected Request $request;
    protected Response $response;
    protected AuthService $authService;

    // Store authenticated user info
    protected ?array $authUser = null;

    public function __construct(
        Database $db,
        Request $request,
        Response $response
    ) {
        $this->db = $db;
        $this->request = $request;
        $this->response = $response;
        $this->authService = new AuthService($db);

        // Attempt to authenticate on controller instantiation
        $this->authenticateFromHeader();
    }

    /**
     * Authenticate user from Authorization header
     */
    protected function authenticateFromHeader(): void
    {
        $authHeader = $this->request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            $this->authUser = null;
            return;
        }

        $token = substr($authHeader, 7); // remove 'Bearer '

        try {
            $userData = $this->authService->validateToken($token);
            $this->authUser = $userData;
        } catch (Exception) {
            $this->authUser = null;
        }
    }

    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated(): bool
    {
        return $this->authUser !== null;
    }

    /**
     * Get authenticated user ID
     */
    protected function getUserId(): ?int
    {
        return $this->authUser['user_id'] ?? null;
    }

    /**
     * Require authentication, return 401 if not
     */
    protected function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            $this->response->error('Unauthorized', [], 401);
        }
    }

    /**
     * Check if user is admin
     */
    protected function isAdmin(): bool
    {
        return $this->authUser['is_admin'] ?? false;
    }

    /**
     * Require admin access
     */
    protected function requireAdmin(): void
    {
        $this->requireAuth();

        if (!$this->isAdmin()) {
            $this->response->error('Forbidden', [], 403);
        }
    }

    /**
     * Log activity
     */
    protected function log(string $message, string $level = 'info'): void
    {
        $logFile = BASE_PATH . '/storage/logs/' . date('Y-m-d') . '.log';
        $logMessage = '[' . date('H:i:s') . '] [' . strtoupper($level) . '] ' . $message . PHP_EOL;
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}
