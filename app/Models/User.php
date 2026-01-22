<?php

namespace App\Models;

/**
 * User Model
 * 
 * Manages user data and operations
 */
class User extends BaseModel
{
    protected string $table = 'users';
    protected array $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'phone',
        'is_active',
        'is_verified',
        'last_login_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Get user by email
     */
    public function getByEmail(string $email): ?array
    {
        return $this->findBy('email', $email);
    }

    /**
     * Get user by google ID
     */
    public function getByGoogleId(string $googleId): ?array
    {
        return $this->findBy('google_id', $googleId);
    }

    /**
     * Get all active users
     */
    public function getActive(): array
    {
        $sql = "SELECT id, name, email, phone, is_verified, created_at FROM {$this->table} WHERE is_active = 1 ORDER BY created_at DESC";
        return $this->db->fetchAll($sql);
    }

    /**
     * Get all verified users
     */
    public function getVerified(): array
    {
        $sql = "SELECT id, name, email, phone, created_at FROM {$this->table} WHERE is_verified = 1 AND is_active = 1 ORDER BY name ASC";
        return $this->db->fetchAll($sql);
    }

    /**
     * Get user with addresses
     */
    public function getUserWithAddresses(int $userId): ?array
    {
        $user = $this->find($userId);
        if (!$user) {
            return null;
        }

        $sql = "SELECT ca.*, c.name as city_name, s.name as state_name, co.name as country_name
                FROM customer_addresses ca
                LEFT JOIN cities c ON ca.city_id = c.id
                LEFT JOIN states s ON ca.state_id = s.id
                LEFT JOIN countries co ON ca.country_id = co.id
                WHERE ca.user_id = ?
                ORDER BY ca.is_default DESC, ca.created_at DESC";
        
        $addresses = $this->db->fetchAll($sql, [$userId]);
        $user['addresses'] = $addresses;

        return $user;
    }

    /**
     * Get user's default address
     */
    public function getDefaultAddress(int $userId): ?array
    {
        $sql = "SELECT ca.*, c.name as city_name, s.name as state_name, co.name as country_name
                FROM customer_addresses ca
                LEFT JOIN cities c ON ca.city_id = c.id
                LEFT JOIN states s ON ca.state_id = s.id
                LEFT JOIN countries co ON ca.country_id = co.id
                WHERE ca.user_id = ? AND ca.is_default = 1
                LIMIT 1";
        
        return $this->db->fetch($sql, [$userId]);
    }

    /**
     * Search users by name or email
     */
    public function search(string $query): array
    {
        $sql = "SELECT id, name, email, phone, is_active, is_verified, created_at 
                FROM {$this->table}
                WHERE (name LIKE ? OR email LIKE ?)
                ORDER BY name ASC";
        $searchTerm = "%{$query}%";
        return $this->db->fetchAll($sql, [$searchTerm, $searchTerm]);
    }

    /**
     * Get users created in date range
     */
    public function getCreatedBetween(string $startDate, string $endDate): array
    {
        $sql = "SELECT id, name, email, phone, is_active, created_at
                FROM {$this->table}
                WHERE created_at BETWEEN ? AND ?
                ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, [$startDate, $endDate]);
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(int $userId): int
    {
        return $this->update($userId, [
            'last_login_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Count active users
     */
    public function countActive(): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE is_active = 1";
        $result = $this->db->fetch($sql);
        return $result['count'] ?? 0;
    }

    /**
     * Get users by verification status
     */
    public function getByVerificationStatus(bool $verified = true): array
    {
        $status = $verified ? 1 : 0;
        $sql = "SELECT id, name, email, phone, created_at FROM {$this->table} WHERE is_verified = ? AND is_active = 1 ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, [$status]);
    }

   
}
