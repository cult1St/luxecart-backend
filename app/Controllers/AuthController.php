<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\EmailVerification;
use App\Helpers\Mailer;
use App\Helpers\SignupValidator;
use App\Helpers\LoginValidator;
use Core\Database;
use Core\Request;
use Core\Response;

/**
 * Auth Controller
 * 
 * Handles user authentication, signup, and email verification
 */
class AuthController extends BaseController
{
    protected User $userModel;
    protected EmailVerification $emailVerificationModel;
    protected Mailer $mailer;

    public function __construct(Database $db, Request $request, Response $response)
    {
        parent::__construct($db, $request, $response);
        $this->userModel = new User($db);
        $this->emailVerificationModel = new EmailVerification($db);
        $this->mailer = new Mailer();
    }

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
            // SECURITY: Don't reveal if email exists (prevents email enumeration)
            if ($this->userModel->emailExists($data['email'])) {
                $this->response->success(
                    ['user_id' => 0],
                    'If this email exists, you will receive a verification code.',
                    201
                );
                return;
            }

            // Create user (not verified yet)
            $userId = $this->userModel->createUser([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'is_verified' => false,
                'is_active' => true
            ]);

            // Create verification code
            $verificationId = $this->emailVerificationModel->createVerification($userId, $data['email']);

            // Get the verification code to send via email
            $verification = $this->emailVerificationModel->find($verificationId);

            // Send verification email
            $emailSent = $this->mailer->sendVerificationCode(
                $data['email'],
                $data['name'],
                $verification['code']
            );

            if (!$emailSent) {
                $this->log("Failed to send verification email to {$data['email']}", 'warning');
            }

            // Log activity
            $this->log("New user registered: {$data['email']} (ID: $userId)");

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
            $verification = $this->emailVerificationModel->verifyCode($data['email'], $data['code']);

            if (!$verification) {
                $this->log("Failed verification attempt for {$data['email']}", 'warning');
                $this->response->error(
                    'Verification failed',
                    ['code' => 'Invalid code'],
                    400
                );
                return;
            }

            // Mark verification as completed
            $this->emailVerificationModel->markAsVerified($verification['id']);

            // Mark user as verified
            $this->userModel->markAsVerified($verification['user_id']);

            // Get user data
            $user = $this->userModel->find($verification['user_id']);

            // Send welcome email
            $this->mailer->sendWelcomeEmail($user['email'], $user['name']);

            // Log activity
            $this->log("User verified: {$user['email']} (ID: {$user['id']})");

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
                    'If registered, you will receive a code shortly.',
                    200
                );
                return;
            }

            // Check if user exists (SECURITY: don't reveal)
            $user = $this->userModel->findByEmail($email);
            if (!$user) {
                $this->response->success(
                    [],
                    'If registered, you will receive a code shortly.',
                    200
                );
                return;
            }

            // Check if already verified (SECURITY: don't reveal)
            if ($user['is_verified']) {
                $this->response->success(
                    [],
                    'If registered, you will receive a code shortly.',
                    200
                );
                return;
            }

            // Create new verification code
            $verificationId = $this->emailVerificationModel->createVerification($user['id'], $email);
            $verification = $this->emailVerificationModel->find($verificationId);

            // Send verification email
            $emailSent = $this->mailer->sendVerificationCode(
                $email,
                $user['name'],
                $verification['code']
            );

            if (!$emailSent) {
                $this->log("Failed to resend verification email to {$email}", 'warning');
            }

            // Log activity
            $this->log("Resent verification code to {$email}");

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

            // Check if user exists by Google ID
            $user = $this->userModel->findByGoogleId($input['google_id']);

            // If not found, check by email
            if (!$user) {
                $user = $this->userModel->findByEmail($input['email']);
            }

            // If user exists, update Google ID if needed
            if ($user) {
                if (!$user['google_id']) {
                    $this->userModel->update($user['id'], ['google_id' => $input['google_id']]);
                }
            } else {
                // Create new user from Google data
                $userId = $this->userModel->createFromGoogle([
                    'name' => $input['name'] ?? '',
                    'email' => $input['email'],
                    'id' => $input['google_id']
                ]);
                $user = $this->userModel->find($userId);
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

    /**
     * User login - POST /api/auth/login
     * 
     * Accepts: email, password
     * Returns: User data if credentials valid
     */
    public function login(): void
    {
        try {
            // Only accept POST requests
            if (!$this->request->isPost()) {
                $this->response->error('Only POST requests are allowed', [], 405);
                return;
            }

            // Check rate limiting - max 5 attempts per 15 minutes
            $ipAddress = $this->getClientIp();
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

            // Find user by email
            $user = $this->userModel->findByEmail($data['email']);

            // User not found or invalid password - don't reveal which
            if (!$user || !$this->userModel->verifyPassword($data['password'], $user['password_hash'])) {
                $this->recordFailedAttempt($rateLimitKey, 900);
                $this->response->error(
                    'Invalid credentials provided',
                    [],
                    401
                );
                return;
            }

            // Check if user is verified
            if (!$user['is_verified']) {
                $this->recordFailedAttempt($rateLimitKey, 900);
                $this->response->error(
                    'Email not verified. Please verify your email before logging in.',
                    [],
                    401
                );
                return;
            }

            // Log activity
            $this->log("User login: {$user['email']} (ID: {$user['id']})");

            // Return success response
            $this->response->success(
                [
                    'user_id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
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
     * Get client IP address
     * 
     * @return string
     */
    protected function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP']; // Cloudflare
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}
