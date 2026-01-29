<?php

namespace App\Models;

use Core\Database;

/**
 * User Model
 *
 * Handles user registration, authentication,
 * profile management, and related queries
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

    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    /* =========================
     * Creation & Authentication
     * ========================= */

    /**
     * Create a new user (with password hashing)
     */
    public function createUser(array $data): int
    {
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        $data['is_active']   = isset($data['is_active']) ? (int) $data['is_active'] : 1;
        $data['is_verified'] = isset($data['is_verified']) ? (int) $data['is_verified'] : 0;
        $data['created_at']  = date('Y-m-d H:i:s');
        $data['updated_at']  = date('Y-m-d H:i:s');

        return $this->create($data);
    }

    /**
     * Verify plain password against hashed password
     */
    public function verifyPassword(string $plain, string $hashed): bool
    {
        return password_verify($plain, $hashed);
    }

    /**
     * Create or fetch user from Google OAuth
     */
    public function createFromGoogle(array $googleData): int
    {
        $existing = $this->findByEmail($googleData['email']);
        if ($existing) {
            return $existing['id'];
        }

        return $this->createUser([
            'name'        => $googleData['name'] ?? '',
            'email'       => $googleData['email'],
            'google_id'   => $googleData['id'],
            'is_verified' => 1,
            'is_active'   => 1,
        ]);
    }

    /* =========================
     * Finders
     * ========================= */

    public function findByEmail(string $email): ?object
    {
        return $this->findBy('email', $email);
    }

    public function findByGoogleId(string $googleId): ?object
    {
        return $this->findBy('google_id', $googleId);
    }

    public function emailExists(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    /* =========================
     * Status & Metadata
     * ========================= */

    public function updateLastLogin(int $userId): int
    {
        return $this->update($userId, [
            'last_login_at' => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    public function markAsVerified(int $userId): int
    {
        return $this->update($userId, [
            'is_verified' => 1,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    /* =========================
     * Queries & Lists
     * ========================= */

    public function getActive(): array
    {
        $sql = "SELECT id, name, email, phone, is_verified, created_at
                FROM {$this->table}
                WHERE is_active = 1
                ORDER BY created_at DESC";

        return $this->db->fetchAll($sql);
    }

    public function getVerified(): array
    {
        $sql = "SELECT id, name, email, phone, created_at
                FROM {$this->table}
                WHERE is_verified = 1 AND is_active = 1
                ORDER BY name ASC";

        return $this->db->fetchAll($sql);
    }

    public function countActive(): int
    {
        $sql = "SELECT COUNT(*) AS count FROM {$this->table} WHERE is_active = 1";
        $result = $this->db->fetch($sql);

        return (int) ($result['count'] ?? 0);
    }

    public function getByVerificationStatus(bool $verified = true): array
    {
        $sql = "SELECT id, name, email, phone, created_at
                FROM {$this->table}
                WHERE is_verified = ? AND is_active = 1
                ORDER BY created_at DESC";

        return $this->db->fetchAll($sql, [(int) $verified]);
    }

    public function search(string $query): array
    {
        $term = "%{$query}%";

        $sql = "SELECT id, name, email, phone, is_active, is_verified, created_at
                FROM {$this->table}
                WHERE name LIKE ? OR email LIKE ?
                ORDER BY name ASC";

        return $this->db->fetchAll($sql, [$term, $term]);
    }

    public function getCreatedBetween(string $startDate, string $endDate): array
    {
        $sql = "SELECT id, name, email, phone, is_active, created_at
                FROM {$this->table}
                WHERE created_at BETWEEN ? AND ?
                ORDER BY created_at DESC";

        return $this->db->fetchAll($sql, [$startDate, $endDate]);
    }

    /* =========================
     * Relationships
     * ========================= */

    public function getUserWithAddresses(int $userId): ?object
    {
        $user = $this->find($userId);
        if (!$user) {
            return null;
        }

        $sql = "SELECT ca.*, c.name AS city_name, s.name AS state_name, co.name AS country_name
                FROM customer_addresses ca
                LEFT JOIN cities c ON ca.city_id = c.id
                LEFT JOIN states s ON ca.state_id = s.id
                LEFT JOIN countries co ON ca.country_id = co.id
                WHERE ca.user_id = ?
                ORDER BY ca.is_default DESC, ca.created_at DESC";

        $user['addresses'] = $this->db->fetchAll($sql, [$userId]);

        return $user;
    }

    public function getDefaultAddress(int $userId): ?array
    {
        $sql = "SELECT ca.*, c.name AS city_name, s.name AS state_name, co.name AS country_name
                FROM customer_addresses ca
                LEFT JOIN cities c ON ca.city_id = c.id
                LEFT JOIN states s ON ca.state_id = s.id
                LEFT JOIN countries co ON ca.country_id = co.id
                WHERE ca.user_id = ? AND ca.is_default = 1
                LIMIT 1";

        return $this->db->fetch($sql, [$userId]);
    }
}
