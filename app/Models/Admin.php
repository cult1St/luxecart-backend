<?php

namespace App\Models;

use Core\Database;

/**
 * Admin Model
 *
 * Handles admin user records and authentication
 */
class Admin extends BaseModel
{
    protected string $table = 'admin_users';

    protected array $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_active',
        'is_verified',
        'last_login_at',
        'created_at',
        'updated_at',
    ];

    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    /**
     * Find admin by email
     */
    public function findByEmail(string $email): ?object
    {
        return $this->findBy('email', $email);
    }

    /**
     * Check if admin email exists
     */
    public function emailExists(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    /**
     * Mark admin as verified
     */
    public function markAsVerified(int $adminId): bool
    {
        return $this->update($adminId, [
            'is_verified' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
