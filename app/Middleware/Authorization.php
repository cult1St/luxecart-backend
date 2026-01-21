<?php

namespace App\Middleware;

use Core\Request;
use Core\Response;

/**
 * Authorization Middleware
 * 
 * Verifies user permissions
 */
class Authorization
{
    protected Request $request;
    protected Response $response;
    protected array $requiredRoles = [];

    public function __construct(Request $request, Response $response, array $roles = [])
    {
        $this->request = $request;
        $this->response = $response;
        $this->requiredRoles = $roles;
    }

    /**
     * Handle middleware
     */
    public function handle(): bool
    {
        if (empty($this->requiredRoles)) {
            return true;
        }

        $userRole = $_SESSION['role'] ?? null;

        if (!$userRole || !in_array($userRole, $this->requiredRoles)) {
            $this->response->error('Unauthorized', [], 403);
            return false;
        }

        return true;
    }
}
