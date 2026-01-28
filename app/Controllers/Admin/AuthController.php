<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use Helpers\Auth\LoginValidator;
use Exception;

/**
 * Admin Authentication Controller
 * Handles admin login, logout, and admin management.
 */
class AuthController extends BaseController
{

    /**
     * Handle admin login request
     */

    public function login()
    {
        if (!$this->request->isPost()) {
            return $this->response->error("Invalid request method", 405);
        }

        $ipAddress = $this->request->getIp();
        $rateLimitKey = "login_attempt_{$ipAddress}";

        if ($this->isRateLimited($rateLimitKey, 5, 900)) {
            $this->response->error(
                'Too many login attempts. Please try again in 15 minutes.',
                429
            );
            return;
        }

        $input = $this->request->all();
        //validate input
        $validate = LoginValidator::validate($input);
        if (!$validate['valid']) {
            $this->response->error(
                'Validation failed',
                422,
                $validate['errors']
            );
            return;
        }
        $data = LoginValidator::sanitize($input);

         // Process login

        try{
            $admin = $this->authService->processLogin($data['email'], $data['password'], 'admin');
          } catch (Exception $e) {
              $this->recordFailedAttempt($rateLimitKey, 900);
              $this->response->error(
                  $e->getMessage(),
                  400
              );
          }
            // Return success response
            $this->response->success(
                [
                    'admin_id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'api_token' => $admin->api_token
                ],
                'Login successful',
                200
            );
    }

    /**
     * Logout user - POST /api/auth/logout
     * 
     * Clears user session and tokens
     */
    public function logout(): void
    {
        $this->requireAdmin();
        try {
            // Only accept POST requests
            if (!$this->request->isPost()) {
                $this->response->error('Only POST requests are allowed', 405);
                return;
            }

            try{
                $this->authService->processLogout($this->getUserId('admin'));

            }catch (Exception $e) {
                $this->response->error(
                    $e->getMessage(),
                    400
                );
            }

            $this->response->success(
                [],
                'Logged out successfully',
                200
            );

        } catch (Exception $e) {
            $this->log("Logout error: " . $e->getMessage(), 'error');
            $this->response->error(
                'An error occurred during logout',
                500
            );
        }
    }

    /**
     * Get authenticated user - GET /api/auth/me
     * 
     * Returns: Current user data or error if not authenticated
     */
    public function me(): void
    {
            // Check if user is authenticated
            $this->requireAdmin();
            $admin = $this->authUser;
            // Return user data
            $this->response->success(
                [
                    'admin_id' => $admin->id ?? null,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'created_at' => $admin->created_at,
                    'updated_at' => $admin->updated_at
                ],
                'Admin authenticated',
                200
            );

        
    }


    
}
