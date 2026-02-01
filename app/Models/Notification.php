<?php

namespace App\Models;

use Core\Database;

class Notification extends BaseModel
{
    protected string $table = 'notifications';

    protected array $fillable = [
        'title',
        'description',
        'status',
        'created_by',
        'created_for',
        'read_by',
        'created_at',
        'read_at',
        'updated_at'
    ];

    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    /**
     * Create notification
     */
    public function createNotification(array $data): int
    {
        $data['status'] = $data['status'] ?? 'unread';
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        return $this->create($data);
    }

    /**
     * Mark a single notification as read
     */
    public function markAsRead(int $notificationId, int $readBy): int
    {
        return $this->update($notificationId, [
            'status'     => 'read',
            'read_by'    => $readBy,
            'read_at'    => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Mark all notifications as read (for a role)
     */
    public function markAllAsRead(string $createdFor, int $readBy): int
    {

        return $this->db->update($this->table, [
            'status'     => 'read',
            'read_by'    => $readBy,
            'read_at'    => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], "created_for = {$createdFor} AND status = 'unread'");
    }

    /**
     * Count unread notifications (admin/user)
     */
    public function countUnread(string $createdFor): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM {$this->table}
            WHERE created_for = ?
              AND status = 'unread'
        ";

        $result = $this->db->fetch($sql, [$createdFor]);
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Get all unread notifications (non-paginated)
     */
    public function getUnread(string $createdFor): array
    {
        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE created_for = ?
              AND status = 'unread'
            ORDER BY created_at DESC
        ";

        $results = $this->db->fetchAll($sql, [$createdFor]);
        return $this->useObjects ? $this->toObjectArray($results) : $results;
    }
}
