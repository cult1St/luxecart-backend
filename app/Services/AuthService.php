<?php

namespace App\Services;

use App\Models\ApiToken;
use App\Models\User;
use Core\Database;

class AuthService
{
    public function __construct(
        private Database $db
    ) {}

    public function generateToken(int $userId, int $expiryHours = 2): string
    {
        $plainToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $plainToken);

        $apiTokenModel = new ApiToken($this->db);

        // Optional: delete existing tokens (single-session policy)
        $apiTokenModel->deleteUserTokens($userId);

        $apiTokenModel->createToken([
            'user_id' => $userId,
            'token' => $hashedToken,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date(
                'Y-m-d H:i:s',
                strtotime("+{$expiryHours} hours")
            ),
        ]);

        return $plainToken; // sent ONCE to client
    }

    public function validateToken(string $plainToken): ?array
    {
        $hashedToken = hash('sha256', $plainToken);

        $apiTokenModel = new ApiToken($this->db);
        $tokenData = $apiTokenModel->getByToken($hashedToken);

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

        return $tokenData;
    }

    public function initiatePasswordReset(string $email, int $userId, ?string $ipAddress = null): bool
    {
        // Generate a password reset token valid for 1 hour
        $resetToken = bin2hex(random_bytes(32));

        // Store the reset request in the database
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        //create reset link
        $resetLink = "https://localhost:3000/reset-password?token={$resetToken}";


        $resetTokenRequest = $this->db->insert('reset_requests', [
            'user_id' => $userId,
            'request_link' => $resetLink,
            'ip_address'=> $ipAddress,
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$resetTokenRequest) {
            throw new \Exception("Could not create reset request");
        }

        try {
            // Send reset email 
            $userModel = new User($this->db);
            $userDetails = $userModel->find($userId);
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

    public function verifyResetToken(string $resetToken): bool
    {
        $resetLink = "https://localhost:3000/reset-password?token={$resetToken}";
       
        $sql = "SELECT * FROM reset_requests WHERE request_link = ?  AND status = 'available'";
        $request = $this->db->fetch($sql, [$resetLink]);
        

        if (!$request) {
            throw new \Exception("Invalid reset token");
        }

        //if it is available check expiry date
        if(strtotime($request['expires_at']) <= time()){
            throw new \Exception("Reset token has expired");
        }
        return true;
    }

    public function resetPassword(string $resetToken, string $newPassword): bool
    {
        $resetLink = "https://localhost:3000/reset-password?token={$resetToken}";

        $sql = "SELECT * FROM reset_requests WHERE request_link = ? AND status = 'available'";
        $request = $this->db->fetch($sql, [$resetLink]);

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
}
