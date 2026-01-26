<?php

namespace App\Models;

use Core\Database;

/**
 * Email Verification Model
 * 
 * Manages email verification codes and their expiration
 */
class EmailVerification extends BaseModel
{
    protected string $table = 'email_verifications';
    protected array $fillable = [
        'user_id',
        'email',
        'code',
        'is_verified',
        'expires_at',
        'created_at',
        'updated_at'
    ];

    // Code validity in minutes
    const CODE_EXPIRY_MINUTES = 15;

    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    /**
     * Create verification code for user
     * 
     * @param int|null $userId User ID (nullable for email without account)
     * @param string $email Email address
     * @return int Verification record ID
     */
    public function createVerification(?int $userId, string $email): int
    {
        // Generate 6-digit code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Calculate expiration time
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::CODE_EXPIRY_MINUTES . ' minutes'));

        // Invalidate any existing codes for this email
        $this->db->query(
            "UPDATE {$this->table} SET is_verified = 2 WHERE email = ? AND is_verified = 0",
            [$email]
        );

        // Create new verification record
        return $this->create([
            'user_id' => $userId,
            'email' => $email,
            'code' => $code,
            'is_verified' => 0, // 0 = pending, 1 = verified, 2 = expired/invalidated
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Verify code for email
     * 
     * @param string $email Email address
     * @param string $code Verification code
     * @return array|null Verification record if code is valid
     */
    public function verifyCode(string $email, string $code): ?array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE email = ? 
                AND code = ? 
                AND is_verified = 0 
                AND expires_at > NOW()
                ORDER BY created_at DESC
                LIMIT 1";

        return $this->db->fetch($sql, [$email, $code]);
    }

    /**
     * Mark verification as completed
     * 
     * @param int $verificationId Verification record ID
     * @return int Number of rows affected
     */
    public function markAsVerified(int $verificationId): int
    {
        return $this->update($verificationId, [
            'is_verified' => 1,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get latest verification code for email
     * 
     * @param string $email Email address
     * @return array|null Latest verification record
     */
    public function getLatestVerification(string $email): ?array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE email = ? 
                AND is_verified = 0
                ORDER BY created_at DESC
                LIMIT 1";

        return $this->db->fetch($sql, [$email]);
    }

    /**
     * Check if verification code is still valid
     * 
     * @param int $verificationId Verification record ID
     * @return bool True if code is still valid
     */
    public function isCodeValid(int $verificationId): bool
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE id = ? 
                AND is_verified = 0 
                AND expires_at > NOW()";

        return $this->db->fetch($sql, [$verificationId]) !== null;
    }

    /**
     * Clean up expired verifications
     * Optionally called periodically to keep database clean
     * 
     * @return int Number of records deleted
     */
    public function cleanupExpiredVerifications(): int
    {
        return $this->db->delete($this->table, "expires_at < NOW() AND is_verified = 0");
    }
}
