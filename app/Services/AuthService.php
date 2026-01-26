<?php

namespace App\Services;

use App\Helpers\Mailer;
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
        $code = $emailVerificationModel->createVerification($user['id'], $email);

        if (empty($code)) {
            throw new Exception("Could not create verification code");
        }

        // Send verification email
        $emailSent = $this->mailer->sendVerificationCode(
            $email,
            $user['name'],
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
    public function verifyEmailCode(string $email, string $code): array
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
        $this->mailer->sendWelcomeEmail($user['email'], $user['name']);

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
    public function validateToken(string $plainToken, string $type = 'user'): ?array
    {
        $hashedToken = hash('sha256', $plainToken);

        $apiTokenModel = new ApiToken($this->db);
        $tokenData = $apiTokenModel->getByToken($hashedToken, $type);

        if (!$tokenData) {
            return null;
        }

        if (strtotime($tokenData['expires_at']) <= time()) {
            return null;
        }

        // Optional IP check
        if ($tokenData['ip_address'] !== null) {
            if ($tokenData['ip_address'] !== ($_SERVER['REMOTE_ADDR'] ?? null)) {
                return null;
            }
        }
        //now get user data
        if ($type === 'admin') {
            $user = $this->db->fetch("SELECT * FROM admin_users WHERE id = ?", [$tokenData['user_id']]);
        } else {
            $user = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$tokenData['user_id']]);
        }

        return $user;
    }

    /**
     * Initiate password reset process
     */
    public function initiatePasswordReset(string $email, int $userId, ?string $ipAddress = null, string $type = "user"): bool
    {
        // Generate a password reset token valid for 1 hour
        $resetToken = bin2hex(random_bytes(32));

        // Store the reset request in the database
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        //create reset link
        $resetLink = "https://localhost:3000/reset-password?token={$resetToken}";


        $resetTokenRequest = $this->db->insert('reset_requests', [
            'user_id' => $userId,
            'type' => $type,
            'request_link' => $resetLink,
            'ip_address' => $ipAddress,
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$resetTokenRequest) {
            throw new \Exception("Could not create reset request");
        }

        try {
            // Send reset email 
            if ($type === 'user') {
                $userModel = new User($this->db);
                $userDetails = $userModel->find($userId);
            } else {
                $admin = $this->db->fetch("SELECT * FROM admin_users WHERE id = ?", [$userId]);
                $userDetails = $admin;
            }
            $sendMail = MailService::send($userDetails['email'], 'Password Reset', "Click here to reset your password: {$resetLink}");
        } catch (\Exception $e) {
            //log error
            error_log("Failed to send password reset email to {$email}: " . $e->getMessage());

            //throw exception
            $sendMail = false;
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

        $sql = "SELECT * FROM reset_requests WHERE request_link = ?  AND status = 'available' AND type = ?";
        $request = $this->db->fetch($sql, [$resetLink, $type]);


        if (!$request) {
            throw new \Exception("Invalid reset token");
        }

        //if it is available check expiry date
        if (strtotime($request['expires_at']) <= time()) {
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

        $sql = "SELECT * FROM reset_requests WHERE request_link = ? AND status = 'available' AND type = ?";
        $request = $this->db->fetch($sql, [$resetLink, $type]);

        if (!$request || strtotime($request['expires_at']) <= time()) {
            throw new \Exception("Invalid or expired reset token");
        }

        $userId = $request['user_id'];

        // Update user password
        $userModel = new User($this->db);
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $updateResult = $userModel->update($userId, [
            'password' => $hashedPassword,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$updateResult) {
            throw new \Exception("Could not update password");
        }

        // Invalidate the reset request
        $this->db->update('reset_requests', ['status' => 'used'], "id = {$request['id']}");

        return true;
    }

    /**
     * Process user login
     */
    public function processLogin(string $email, string $password, string $type = "user"): ?array
    {
        $user = $this->db->fetch(
            "SELECT * FROM " . ($type === 'admin' ? 'admin_users' : 'users') . " WHERE email = ?",
            [$email]
        );
        if (!$user || !password_verify($password, $user['password'])) {
            throw new Exception("Invalid email or password");
        }

        //check if user is verified
        if ($type === 'user' && !$user['is_verified']) {
            throw new Exception("Email not verified");
        }
        //update last login
        $this->db->update($type === 'admin' ? 'admin_users' : 'users', ["last_login_at" => date('Y-m-d H:i:s')], "id = {$user['id']}");
        //generate token
        
        $token = $this->generateToken($user['id'], 2, $type);
        $user['api_token'] = $token;
        return $user;
    }

    /**
     * Process user logout
     */
    public function processLogout(int $userId, string $type = "user"): bool
    {
        //first check if user is valid
        $user = $this->db->fetch(
            "SELECT id FROM " . ($type === 'admin' ? 'admin_users' : 'users') . " WHERE id = ?",
            [$userId]
        );
        if (!$user) {
            throw new Exception("Invalid user");
        }
        $apiTokenModel = new ApiToken($this->db);
        return $apiTokenModel->deleteUserTokens($userId, $type) > 0;
    }
}
