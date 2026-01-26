<?php

namespace App\Models;

use Core\Database;

/**
 * User Model
 * 
 * Handles user registration, authentication, and password management
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
        'created_at',
        'updated_at'
    ];

    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    /**
     * Create a new user with password hashing
     * 
     * @param array $data User data
     * @return int User ID
     */
    public function createUser(array $data): int
    {
        // Hash the password if provided
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        // Set default values
        $data['is_active'] = isset($data['is_active']) ? (int)(bool)$data['is_active'] : 1;
        $data['is_verified'] = isset($data['is_verified']) ? (int)(bool)$data['is_verified'] : 0;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        return $this->create($data);
    }

    /**
     * Find user by email
     * 
     * @param string $email User email
     * @return array|null User data
     */
    public function findByEmail(string $email): ?array
    {
        return $this->findBy('email', $email);
    }

    /**
     * Find user by Google ID
     * 
     * @param string $googleId Google OAuth ID
     * @return array|null User data
     */
    public function findByGoogleId(string $googleId): ?array
    {
        return $this->findBy('google_id', $googleId);
    }

    /**
     * Verify password against hashed password
     * 
     * @param string $plainPassword Plain text password
     * @param string $hashedPassword Hashed password from database
     * @return bool True if password matches
     */
    public function verifyPassword(string $plainPassword, string $hashedPassword): bool
    {
        return password_verify($plainPassword, $hashedPassword);
    }

    /**
     * Mark user as verified
     * 
     * @param int $userId User ID
     * @return int Number of rows affected
     */
    public function markAsVerified(int $userId): int
    {
        return $this->update($userId, [
            'is_verified' => 1,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Check if email exists
     * 
     * @param string $email User email
     * @return bool True if email exists
     */
    public function emailExists(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    /**
     * Create user with Google OAuth
     * 
     * @param array $googleData Google user data
     * @return int User ID
     */
    public function createFromGoogle(array $googleData): int
    {
        // Check if user already exists by email
        $existingUser = $this->findByEmail($googleData['email']);
        if ($existingUser) {
            return $existingUser['id'];
        }

        // Create new user from Google data
        return $this->createUser([
            'name' => $googleData['name'] ?? '',
            'email' => $googleData['email'],
            'google_id' => $googleData['id'],
            'is_verified' => true, // Auto-verify Google OAuth users
            'is_active' => true
        ]);
    }
}
