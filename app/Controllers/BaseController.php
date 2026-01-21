<?php

namespace App\Controllers;

use Core\Database;
use Core\Request;
use Core\Response;

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

    public function __construct(Database $db, Request $request, Response $response)
    {
        $this->db = $db;
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Get authenticated user ID
     */
    protected function getUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Check authentication and redirect
     */
    protected function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            $this->response->redirect('/login');
        }
    }

    /**
     * Check admin status
     */
    protected function isAdmin(): bool
    {
        return $_SESSION['is_admin'] ?? false;
    }

    /**
     * Require admin access
     */
    protected function requireAdmin(): void
    {
        $this->requireAuth();
        if (!$this->isAdmin()) {
            $this->response->error('Unauthorized', [], 403);
        }
    }

    /**
     * Validate CSRF token
     */
    protected function validateCsrf(): bool
    {
        $token = $this->request->input('csrf_token');
        return hash_equals($_SESSION['csrf_token'] ?? '', $token ?? '');
    }

    /**
     * Generate CSRF token
     */
    protected function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
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
