<?php

namespace App\Middleware;

use Core\Request;
use Core\Response;
use Core\Database;
use App\Services\AuthService;
use Throwable;

/**
 * Authentication Middleware
 * 
 * Verifies user authentication via Bearer token in Authorization header
 */
class Authentication
{
    protected Request $request;
    protected Response $response;
    protected AuthService $authService;
    protected ?array $authUser = null;

    public function __construct(Request $request, Response $response, Database $db)
    {
        $this->request = $request;
        $this->response = $response;
        $this->authService = new AuthService($db);
    }

    /**
     * Handle middleware
     */
    public function handle(): bool
    {
        if (!$this->isAuthenticated()) {
            $this->response->error('Unauthorized', [], 401);
            return false;
        }
        return true;
    }

    /**
     * Check if user is authenticated via Bearer token
     */
    protected function isAuthenticated($type = 'user'): bool
    {
        $authHeader = $this->request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            $this->authUser = null;
            return false;
        }

        $token = substr($authHeader, 7); // remove 'Bearer '

        try {
            $userData = $this->authService->validateToken($token, $type);
            $this->authUser = $userData;
            return true;
        } catch (Throwable) {
            $this->authUser = null;
            return false;
        }
    }

    /**
     * Get authenticated user data
     */
    public function getAuthUser(): ?array
    {
        return $this->authUser;
    }
}
