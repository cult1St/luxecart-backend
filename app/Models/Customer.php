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
}
