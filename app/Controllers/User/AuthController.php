<?php

namespace App\Controllers\User;

use App\Controllers\BaseController;
use Helpers\Auth\LoginValidator;
use Helpers\Auth\SignupValidator;
use App\Models\User;
use Exception;

/**
 * Auth Controller
 * 
 * Handles authentication operations from login/signup to logout and forgot password
 */
class AuthController extends BaseController
{

    /**
     * User signup - POST /api/auth/signup
     * 
     * Accepts: email, password, confirm_password, name
     * Returns: User data with user_id and message
     */
    public function signup(): void
    {
        try {
            // Only accept POST requests
            if (!$this->request->isPost()) {
                $this->response->error('Only POST requests are allowed', [], 405);
                return;
            }

            // Get and sanitize input data
            $input = $this->request->all();
            $data = SignupValidator::sanitize($input);

            // Validate input
            $validation = SignupValidator::validate($data);
            if (!$validation['valid']) {
                $this->response->error(
                    'Validation failed',
                    $validation['errors'],
                    422
                );
                return;
            }

            // Check if email already exists

            try {
                $userId = $this->authService->processSignup($data);
            } catch (\Exception $e) {
                $this->response->error(
                    $e->getMessage(),
                    [],
                    412
                );
            }


            // Return success response
            $this->response->success(
                [
                    'user_id' => $userId,
                    'email' => $data['email'],
                    'name' => $data['name'],
                    'message' => 'Signup successful! A verification code has been sent to your email.'
                ],
                'User registered successfully',
                201
            );
        } catch (\Exception $e) {
            $this->log("Signup error: " . $e->getMessage(), 'error');
            $this->response->error(
                'An error occurred during signup',
                [],
                500
            );
        }
    }

    /**
     * Verify email with code - POST /api/auth/verify-email
     * 
     * Accepts: email, code (6-digit)
     * Returns: User data and success message
     */
    public function verifyEmail(): void
    {
        try {
            // Only accept POST requests
            if (!$this->request->isPost()) {
                $this->response->error('Only POST requests are allowed', [], 405);
                return;
            }

            // Get and sanitize input data
            $input = $this->request->all();
            $data = SignupValidator::sanitizeVerification($input);

            // Validate input
            $validation = SignupValidator::validateVerification($data);
            if (!$validation['valid']) {
                $this->response->error(
                    'Validation failed',
                    $validation['errors'],
                    422
                );
                return;
            }

            // Verify the code
            try {
                $user = $this->authService->verifyEmailCode($data['email'], $data['code']);
            } catch (Exception $e) {
                $this->response->error(
                    $e->getMessage(),
                    [],
                    400
                );
            }

            // Return success response
            $this->response->success(
                [
                    'user_id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'is_verified' => true
                ],
                'Email verified successfully! You can now login.',
                200
            );
        } catch (\Exception $e) {
            $this->log("Email verification error: " . $e->getMessage(), 'error');
            $this->response->error(
                'An error occurred during verification',
                [],
                500
            );
        }
    }

    /**
     * Resend verification code - POST /api/auth/resend-code
     * 
     * Accepts: email
     * Returns: Success message
     */
    public function resendCode(): void
    {
        try {
            // Only accept POST requests
            if (!$this->request->isPost()) {
                $this->response->error('Invalid request', [], 405);
                return;
            }

            $email = trim($this->request->post('email', ''));

            // Validate email
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->response->error(
                    'Invalid input',
                    ['email' => 'Invalid email address'],
                    422
                );
                return;
            }

            // Check rate limiting
            if ($this->isRateLimited('resend_' . $email)) {
                $this->response->success(
                    [],
                    'Please wait before requesting another code',
                    400
                );
                return;
            }

            // Check if user exists (SECURITY: don't reveal)
            try {
                $this->authService->sendVerificationCode($email);
            } catch (Exception $e) {
                $this->recordFailedAttempt('resend_'. $email, 900);
                $this->log('' . $e->getMessage(), 'error');
                $this->response->error(
                    $e->getMessage(),
                    [],
                    400
                );
            }
            // Return success response
            $this->response->success(
                ['email' => $email],
                'A new verification code has been sent to your email',
                200
            );
        } catch (\Exception $e) {
            $this->log("Resend code error: " . $e->getMessage(), 'error');
            $this->response->error(
                'An error occurred while resending the code',
                [],
                500
            );
        }
    }

    /**
     * Google OAuth signup/login - POST /api/auth/google
     * 
     * Accepts: name, email, google_id
     * Returns: User data and auth token
     */
    public function googleAuth(): void
    {
        try {
            // Only accept POST requests
            if (!$this->request->isPost()) {
                $this->response->error('Only POST requests are allowed', [], 405);
                return;
            }

            $input = $this->request->all();

            // Validate required fields
            if (empty($input['email']) || empty($input['google_id'])) {
                $this->response->error(
                    'Validation failed',
                    ['google_id' => 'Missing required Google authentication data'],
                    422
                );
                return;
            }

            $userModel = new User($this->db);
            // Check if user exists by Google ID
            $user = $userModel->findByGoogleId($input['google_id']);

            // If not found, check by email
            if (!$user) {
                $user = $userModel->findByEmail($input['email']);
            }

            // If user exists, update Google ID if needed
            if ($user) {
                if (!$user['google_id']) {
                    $userModel->update($user['id'], ['google_id' => $input['google_id']]);
                }
            } else {
                // Create new user from Google data
                $userId = $userModel->createFromGoogle([
                    'name' => $input['name'] ?? '',
                    'email' => $input['email'],
                    'id' => $input['google_id']
                ]);
                $user = $userModel->find($userId);
            }

            // Log activity
            $this->log("Google auth: {$user['email']} (ID: {$user['id']})");

            // Return success response
            $this->response->success(
                [
                    'user_id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'is_verified' => true
                ],
                'Google authentication successful',
                200
            );

        } catch (\Exception $e) {
            $this->log("Google auth error: " . $e->getMessage(), 'error');
            $this->response->error(
                'An error occurred during Google authentication',
                [],
                500
            );
        }
    }

    public function login(): void
    {
        try {
            // Only accept POST requests
            if (!$this->request->isPost()) {
                $this->response->error('Only POST requests are allowed', [], 405);
                return;
            }

            // Check rate limiting - max 5 attempts per 15 minutes
            $ipAddress = $this->request->getIp();
            $rateLimitKey = "login_attempt_{$ipAddress}";
            
            if ($this->isRateLimited($rateLimitKey, 5, 900)) {
                $this->response->error(
                    'Too many login attempts. Please try again in 15 minutes.',
                    [],
                    429
                );
                return;
            }

            // Get and sanitize input data
            $input = $this->request->all();
            $data = LoginValidator::sanitize($input);

            // Validate input
            $validation = LoginValidator::validate($data);
            if (!$validation['valid']) {
                $this->recordFailedAttempt($rateLimitKey, 900);
                $this->response->error(
                    'Validation failed',
                    $validation['errors'],
                    422
                );
                return;
            }

          try{
            $user = $this->authService->processLogin($data['email'], $data['password']);
          } catch (Exception $e) {
              $this->recordFailedAttempt($rateLimitKey, 900);
              $this->response->error(
                  $e->getMessage(),
                  [],
                  400
              );
          }
            // Return success response
            $this->response->success(
                [
                    'user_id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'api_token' => $user['api_token'],
                    'is_verified' => true
                ],
                'Login successful',
                200
            );

        } catch (\Exception $e) {
            $this->log("Login error: " . $e->getMessage(), 'error');
            $this->response->error(
                'An error occurred during login',
                [],
                500
            );
        }
    }

    /**
     * Logout user - POST /api/auth/logout
     * 
     * Clears user session and tokens
     */
    public function logout(): void
    {
        $this->requireAuth();
        try {
            // Only accept POST requests
            if (!$this->request->isPost()) {
                $this->response->error('Only POST requests are allowed', [], 405);
                return;
            }

            try{
                $this->authService->processLogout($this->getUserId());

            }catch (Exception $e) {
                $this->response->error(
                    $e->getMessage(),
                    [],
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
                [],
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
            $this->requireAuth();
            $user = $this->authUser;
            // Return user data
            $this->response->success(
                [
                    'user_id' => $user['id'] ?? null,
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'phone' => $user['phone'] ?? null,
                    'is_verified' => (bool)$user['is_verified'] ?? false,
                    'is_active' => (bool)$user['is_active'] ?? false,
                    'created_at' => $user['created_at'],
                    'updated_at' => $user['updated_at']
                ],
                'User authenticated',
                200
            );

        
    }

    /**
     * Handle forgot password request
     */
    public function forgotPassword()
    {
        if (!$this->request->isPost()) {
            $this->response->error('Invalid request', [], 400);
        }

        $email = $this->request->post('email');
        //validate email
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->response->error('Valid email is required', [], 400);
        }

        $userModel = new User($this->db);
        $user = $userModel->findBy('email', $email);

        if (!$user) {
            // To prevent email enumeration, respond with success even if user not found
            $this->response->success([], 'If that email is registered, a reset link has been sent.');
        }

        $authService = $this->authService;
        try {
            $authService->initiatePasswordReset($email, $user['id'], $this->request->getIp());
        } catch (\Exception $e) {
            $this->response->error($e->getMessage(), [], 500);
        }
        $this->response->success([], 'If that email is registered, a reset link has been sent.');
    }

    /*  
    * Verify reset token
     */
    public function verifyResetToken()
    {
        if (!$this->request->isPost()) {
            $this->response->error('Invalid request', [], 400);
        }

        $token = $this->request->post('token');
        if (!$token) {
            $this->response->error('Token is required', [], 400);
        }

        try {
            $verifytoken = $this->authService->verifyResetToken($token);
        } catch (\Exception $e) {
            $this->response->error($e->getMessage(), [], 400);
        }

        $this->response->success([], 'Token verified successfully');
    }

    /**
     * Resets User Password based on reset token
     * @return void
     */
    public function resetPassword()
    {
        if (!$this->request->isPost()) {
            $this->response->error('Invalid request', [], 400);
        }

        $token = $this->request->post('token');
        $password = $this->request->post('password');
        $confirmPassword = $this->request->post('confirm_password');

        if (!$token || !$password) {
            $this->response->error('Token and Password are required', [], 400);
        }

        //validate password match
        if ($password !== $confirmPassword) {
            $this->response->error('Passwords do not match', [], 400);
        }

        try {
            $this->authService->resetPassword($token, $password);
        } catch (\Exception $e) {
            $this->response->error($e->getMessage(), [], 400);
        }

        $this->response->success([], 'Password reset successfully');
    }
}
