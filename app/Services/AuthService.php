<?php

namespace App\Services;

use Helpers\Mailer;
use App\Models\ApiToken;
use App\Models\EmailVerification;
use App\Models\User;
use Core\Database;
use Exception;


/**
 * Auth Service
 * 
 * Handles authentication related operations like token generation, validation, password reset etc.
 */
class AuthService
{
    private $mailer;
    /**
     * Constructor, Sets up the AuthService with a database connection
     */
    public function __construct(
        private Database $db
    ) {
        $this->mailer = new Mailer();
    }
    /**
     * Process the signup process for a new user
     */
    public function processSignup(array $userData): int
    {
        //check for existing email
        $userModel = new User($this->db);
        if ($userModel->emailExists($userData['email'])) {
            throw new \Exception("Email already exists");
        }

        try {
            $this->db->beginTransaction();
            //create user

            $userId = $userModel->createUser($userData);

            if (empty($userId)) {
                throw new \Exception("Could not create user");
            }

            // Send verification email
            $emailSent = $this->sendVerificationCode($userData['email']);
            if (!$emailSent) {
                //log error
                error_logger('Error', 'Failed to send verification email to ' . $userData['email']);
                throw new \Exception("Could not send verification email");
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }


        return $userId;
    }

    /**
     * Send verification code to user's email
     */
    public function sendVerificationCode(string $email): bool
    {
        $userModel = new User($this->db);
        $user = $userModel->findByEmail($email);

        if (!$user) {
            throw new Exception("User not found");
        }

        $emailVerificationModel = new EmailVerification($this->db);
        $code = $emailVerificationModel->createVerification($user->id, $email);

        if (empty($code)) {
            throw new Exception("Could not create verification code");
        }

        // Send verification email
        $emailSent = $this->mailer->sendVerificationCode(
            $email,
            $user->name,
            $code
        );

        if (!$emailSent) {
            throw new Exception("Could not send verification email");
        }

        return true;
    }

    /**
     * Verify user's email with the provided code
     */
    public function verifyEmailCode(string $email, string $code): ?object
    {
        $userModel = new User($this->db);
        $emailVerificationModel = new EmailVerification($this->db);
        $verification = $emailVerificationModel->verifyCode($email, $code);
        
        //removed expiry check from db query, now check here

        if (!$verification || strtotime($verification['expires_at']) <= time()) {
            throw new Exception("Invalid verification code");
        }


        // Mark verification as completed
        $emailVerificationModel->markAsVerified($verification['id']);

        // Mark user as verified
        $userModel->markAsVerified($verification['user_id']);

        //send welcome email
        $user = $userModel->find($verification['user_id']);
        $this->mailer->sendWelcomeEmail($user->email, $user->name);

        return $user;
    }

    /**
     * Generate API token for a user
     */
    public function generateToken(int $userId, int $expiryHours = 2, string $type = "user"): string
    {
        $plainToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $plainToken);

        $apiTokenModel = new ApiToken($this->db);

        // Optional: delete existing tokens (single-session policy)
        $apiTokenModel->deleteUserTokens($userId, $type);

        $apiTokenModel->createToken([
            'user_id' => $userId,
            'token' => $hashedToken,
            'type' => $type,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date(
                'Y-m-d H:i:s',
                strtotime("+{$expiryHours} hours")
            ),
        ], $type);

        return $plainToken; // sent ONCE to client
    }

    /**
     * Validate API token
     */
    public function validateToken(string $plainToken, string $type = 'user'): ?object
    {
        $hashedToken = hash('sha256', $plainToken);

        $apiTokenModel = new ApiToken($this->db);
        $tokenData = $apiTokenModel->getByToken($hashedToken, $type);

        if (!$tokenData) {
            return null;
        }

        if (strtotime($tokenData->expires_at) <= time()) {
            return null;
        }

        // Optional IP check
        if ($tokenData->ip_address !== null) {
            if ($tokenData->ip_address !== ($_SERVER['REMOTE_ADDR'] ?? null)) {
                return null;
            }
        }
        
        // Get user data through model
        if ($type === 'admin') {
            $adminModel = new \App\Models\Admin($this->db);
            $user = $adminModel->find($tokenData->user_id);
        } else {
            $userModel = new User($this->db);
            $user = $userModel->find($tokenData->user_id);
        }

        return $user;
    }

    /**
     * Initiate password reset process
     */
    public function initiatePasswordReset(string $email, int $userId, ?string $ipAddress = null, string $type = "user"): bool
    {
        // Generate a password reset token valid for 10 minutes
        $resetToken = bin2hex(random_bytes(32));
        $resetLink = "https://localhost:3000/reset-password?token={$resetToken}";
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        // Store reset request through model
        $passwordResetModel = new \App\Models\PasswordReset($this->db);
        $resetRequestId = $passwordResetModel->createRequest([
            'user_id' => $userId,
            'type' => $type,
            'request_link' => $resetLink,
            'ip_address' => $ipAddress,
            'expires_at' => $expiresAt,
        ]);

        if (!$resetRequestId) {
            throw new \Exception("Could not create reset request");
        }

        try {
            // Get user data through model
            if ($type === 'user') {
                $userModel = new User($this->db);
                $userDetails = $userModel->find($userId);
            } else {
                $adminModel = new \App\Models\Admin($this->db);
                $userDetails = $adminModel->find($userId);
            }
            
            $sendMail = MailService::send($userDetails->email, 'Password Reset', "Click here to reset your password: {$resetLink}");
        } catch (\Exception $e) {
            error_log("Failed to send password reset email to {$email}: " . $e->getMessage());
            throw new \Exception("Could not send reset email");
        }

        return $sendMail;
    }

    /**
     * Verify reset token
     */
    public function verifyResetToken(string $resetToken, string $type = "user"): bool
    {
        $resetLink = "https://localhost:3000/reset-password?token={$resetToken}";

        $passwordResetModel = new \App\Models\PasswordReset($this->db);
        $request = $passwordResetModel->findByLink($resetLink, $type);

        if (!$request) {
            throw new \Exception("Invalid reset token");
        }

        // Check expiry date
        if (strtotime($request->expires_at) <= time()) {
            throw new \Exception("Reset token has expired");
        }
        
        return true;
    }

    /**
     * Reset user password
     */
    public function resetPassword(string $resetToken, string $newPassword, string $type = "user"): bool
    {
        $resetLink = "https://localhost:3000/reset-password?token={$resetToken}";

        $passwordResetModel = new \App\Models\PasswordReset($this->db);
        $request = $passwordResetModel->findByLink($resetLink, $type);

        if (!$request || strtotime($request->expires_at) <= time()) {
            throw new \Exception("Invalid or expired reset token");
        }

        $userId = $request->user_id;

        // Update user password through model
        $userModel = new User($this->db);
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $updateResult = $userModel->update($userId, [
            'password' => $hashedPassword,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$updateResult) {
            throw new \Exception("Could not update password");
        }

        // Mark reset request as used
        $passwordResetModel->markAsUsed($request->id);

        return true;
    }

    /**
     * Process user login
     */
    public function processLogin(string $email, string $password, string $type = "user"): ?object
    {
        // Get user through model
        if ($type === 'admin') {
            $adminModel = new \App\Models\Admin($this->db);
            $user = $adminModel->findByEmail($email);
        } else {
            $userModel = new User($this->db);
            $user = $userModel->findByEmail($email);
        }
        
        if (!$user || !password_verify($password, $user->password)) {
            throw new Exception("Invalid email or password");
        }

        // Check if user is verified
        if ($type === 'user' && !$user->is_verified) {
            throw new Exception("Email not verified");
        }
        
        // Update last login through model
        if ($type === 'admin') {
            $adminModel->update($user->id, ["last_login_at" => date('Y-m-d H:i:s')]);
        } else {
            $userModel->update($user->id, ["last_login_at" => date('Y-m-d H:i:s')]);
        }
        
        // Generate token
        $token = $this->generateToken($user->id, 2, $type);
        $user->api_token = $token;
        return $user;
    }

    /**
     * Process user logout
     */
    public function processLogout(int $userId, string $type = "user"): bool
    {
        // Verify user exists through model
        if ($type === 'admin') {
            $adminModel = new \App\Models\Admin($this->db);
            $user = $adminModel->find($userId);
        } else {
            $userModel = new User($this->db);
            $user = $userModel->find($userId);
        }
        
        if (!$user) {
            throw new Exception("Invalid user");
        }
        
        // Delete user tokens
        $apiTokenModel = new ApiToken($this->db);
        return $apiTokenModel->deleteUserTokens($userId, $type) > 0;
    }
}
