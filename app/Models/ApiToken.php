<?php

namespace App\Models;

/**
 * ApiToken Model
 * 
 * Manages API tokens
 */
class ApiToken extends BaseModel
{
    protected string $table = 'api_tokens';
    protected array $fillable = [
        'user_id',
        'token',
        'ip_address',
        'created_at',
        'expires_at',
    ];
    protected $expireAfter = 7200; // 2 hours in seconds

    /**
     * Create token for user
     */
    public function createToken(array $data, string $type = 'user'): int
    {
        return $this->create([
            'user_id' => $data['user_id'],
            'token' => $data['token'],
            'type' => $type,
            'ip_address' => $data['ip_address'] ?? null,
            'created_at' => $data['created_at'] ?? date('Y-m-d H:i:s'),
            'expires_at' => $data['expires_at'] ?? date('Y-m-d H:i:s', time() + $this->expireAfter),
        ]);
    }

    /**
     * Get token by token string
     */
    public function getByToken(string $token, string $type = 'user'): ?array
    {
        try {
            $result = $this->db->fetch("SELECT * FROM {$this->table} WHERE token = ? AND type = ?", [$token, $type]);
            return $result;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get user's active token
     */
    public function getUserToken(int $userId, string $type = 'user'): ?array
    {
        $tokens = $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE user_id = ? AND type = ? ORDER BY created_at DESC",
            [$userId, $type]
        );

        if (empty($tokens)) {
            return null;
        }

        // Return first non-expired token
        foreach ($tokens as $token) {
            if (strtotime($token['expires_at']) > time()) {
                return $token;
            }
        }

        return null;
    }

    /**
     * Delete expired tokens for user
     */
    public function deleteExpiredTokens(int $userId, string $type = 'user'): int
    {
        return $this->db->delete($this->table, "user_id = {$userId} AND type = '{$type}' AND expires_at < NOW()");
    }

    /**
     * Delete all tokens for user
     */
    public function deleteUserTokens(int $userId, string $type = "user"): int
    {
        return $this->db->delete($this->table, "user_id = {$userId} AND type = '{$type}'");
    }
}
