<?php

namespace App\Middleware;

use Core\Request;
use Core\Response;

/**
 * Authentication Middleware
 * 
 * Verifies user authentication
 */
class Authentication
{
    protected Request $request;
    protected Response $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Handle middleware
     */
    public function handle(): bool
    {
        if (!$this->isAuthenticated()) {
            $this->response->redirect(route('/login'));
            return false;
        }
        return true;
    }

    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}
