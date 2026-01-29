<?php

namespace App\Controllers;

use Core\Database;
use Core\Request;
use Core\Response;
use App\Services\AuthService;
use Throwable;

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
    protected ?object $authUser = null;

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
        } catch (Throwable) {
            $this->authUser = null;
        }
    }

    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated($type = 'user'): bool
    {
        return $this->authUser !== null;
    }

    /**
     * Get authenticated user ID
     */
    protected function getUserId($type = 'user'): ?int
    {
        return $type === 'admin' ? ($this->authUser->admin_id ?? null) : ($this->authUser->user_id ?? null);
    }

    /**
     * Require authentication, return 401 if not
     */
    protected function requireAuth($type = 'user'): void
    {
        if (!$this->isAuthenticated($type)) {
            $this->response->error('Unauthorized', 401);
        }
    }

    /**
     * Check if user is admin
     */
    protected function isAdmin(): bool
    {
        return $this->authUser->role ?? false;
    }

    /**
     * Require admin access
     */
    protected function requireAdmin(): void
    {
        $this->requireAuth('admin');

        if (!$this->isAdmin()) {
            $this->response->error('Forbidden', 403);
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

    /**
     * Check if action is rate limited (simple in-memory check)
     */
    protected function isRateLimited(string $key, int $maxAttempts = 5, int $windowSeconds = 900): bool
    {
        $cacheFile = BASE_PATH . '/storage/logs/.rate_limit_' . md5($key);
        
        if (file_exists($cacheFile)) {
            $data = unserialize(file_get_contents($cacheFile));
            if (time() - $data['timestamp'] > $windowSeconds) {
                // Window expired, reset
                unlink($cacheFile);
                return false;
            }
            // Check if max attempts exceeded
            return $data['attempts'] >= $maxAttempts;
        }
        
        return false;
    }

    /**
     * Record a failed attempt for rate limiting
     */
    protected function recordFailedAttempt(string $key, int $windowSeconds = 900): void
    {
        $cacheFile = BASE_PATH . '/storage/logs/.rate_limit_' . md5($key);
        
        if (file_exists($cacheFile)) {
            $data = unserialize(file_get_contents($cacheFile));
            if (time() - $data['timestamp'] < $windowSeconds) {
                $data['attempts']++;
            } else {
                $data = ['attempts' => 1, 'timestamp' => time()];
            }
        } else {
            $data = ['attempts' => 1, 'timestamp' => time()];
        }
        
        file_put_contents($cacheFile, serialize($data));
    }}