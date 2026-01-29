<?php

namespace App\Models;

use Core\Database;

/**
 * PasswordReset Model
 *
 * Manages password reset requests and tokens
 */
class PasswordReset extends BaseModel
{
    protected string $table = 'reset_requests';

    protected array $fillable = [
        'user_id',
        'type',
        'request_link',
        'ip_address',
        'status',
        'expires_at',
        'created_at',
    ];

    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    /**
     * Create a password reset request
     */
    public function createRequest(array $data): int
    {
        return $this->create([
            'user_id' => $data['user_id'],
            'type' => $data['type'] ?? 'user',
            'request_link' => $data['request_link'],
            'ip_address' => $data['ip_address'] ?? null,
            'status' => 'available',
            'expires_at' => $data['expires_at'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Find reset request by link
     */
    public function findByLink(string $resetLink, string $type = 'user'): ?object
    {
        return $this->findBy('request_link', $resetLink, "AND type = '{$type}' AND status = 'available'");
    }

    /**
     * Mark reset request as used
     */
    public function markAsUsed(int $resetId): bool
    {
        return $this->update($resetId, ['status' => 'used']);
    }
}
