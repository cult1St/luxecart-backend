<?php

namespace App\Models;

/**
 * Customer Model
 * 
 * Manages customer accounts and information
 */
class Customer extends BaseModel
{
    protected string $table = 'customers';
    protected array $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'is_active',
        'is_verified',
        'email_verified_at',
        'last_login_at',
        'created_at',
        'updated_at',
    ];
    protected array $hidden = ['password'];

    /**
     * Get by email
     */
    public function getByEmail(string $email): ?array
    {
        return $this->findBy('email', $email);
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $plainPassword, string $hashedPassword): bool
    {
        return password_verify($plainPassword, $hashedPassword);
    }

    /**
     * Hash password
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Get customer addresses
     */
    public function getAddresses(int $customerId): array
    {
        $sql = "SELECT * FROM customer_addresses WHERE customer_id = ? ORDER BY is_default DESC";
        return $this->db->fetchAll($sql, [$customerId]);
    }

    /**
     * Get order history
     */
    public function getOrders(int $customerId): array
    {
        $sql = "SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, [$customerId]);
    }

    /**
     * Update last login
     */
    public function updateLastLogin(int $customerId): int
    {
        return $this->db->update('customers', ['last_login_at' => date('Y-m-d H:i:s')], "id = {$customerId}");
    }

    /**
     * Mark email as verified
     */
    public function verifyEmail(int $customerId): int
    {
        return $this->db->update('customers', 
            ['is_verified' => 1, 'email_verified_at' => date('Y-m-d H:i:s')], 
            "id = {$customerId}"
        );
    }

    /**
     * Get customer address
     * 
     * @param int $userId User ID
     * @return array|null Address data
     */
    public function getAddress(int $userId): ?array
    {
        $sql = "SELECT * FROM customer_addresses WHERE user_id = ? LIMIT 1";
        return $this->db->fetch($sql, [$userId]);
    }

    /**
     * Update or create customer address
     * 
     * @param int $userId User ID
     * @param array $data Address data
     * @return int Address ID
     */
    public function updateAddress(int $userId, array $data): int
    {
        // Check if address exists
        $existing = $this->getAddress($userId);

        if ($existing) {
            // Update existing address
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->update('customer_addresses', $data, "user_id = {$userId}");
            return $existing['id'];
        } else {
            // Create new address
            $data['user_id'] = $userId;
            $data['type'] = 'shipping';
            $data['is_default'] = 1;
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            return $this->db->insert('customer_addresses', $data);
        }
    }
}
