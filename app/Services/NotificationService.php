<?php

namespace App\Services;

use App\Models\Notification;
use Core\Database;

class NotificationService
{
    protected Notification $notification;

    // default audience for now
    protected string $audience = 'admin';

    public function __construct(Database $db)
    {
        $this->notification = new Notification($db);
    }

    /**
     * Create system notification (admin-facing)
     */
    public function notify(
        string $title,
        string $description,
        int $createdBy = 0,
        string $createdFor = 'admin'
    ): int {
        return $this->notification->createNotification([
            'title'       => $title,
            'description' => $description,
            'created_by'  => $createdBy,
            'created_for' => $createdFor,
        ]);
    }

    /**
     * Paginated notifications (admin)
     */
    public function getPaginated(
        int $page = 1,
        int $perPage = 20,
        ?string $status = null
    ): array {
        $where = ['created_for' => $this->audience];

        if ($status) {
            $where['status'] = $status;
        }

        return $this->notification->paginate(
            $page,
            $perPage,
            [
                'where' => $where,
                'orderBy' => 'created_at',
                'direction' => 'DESC'
            ]
        );
    }

    /**
     * Paginated + grouped notifications (by title)
     */
    public function getGroupedPaginated(
        int $page = 1,
        int $perPage = 20,
        ?string $status = null
    ): array {
        $paginated = $this->getPaginated($page, $perPage, $status);

        $grouped = [];

        foreach ($paginated['data'] as $notification) {
            $grouped[$notification->title][] = $notification;
        }

        return [
            'data' => $grouped,
            'meta' => $paginated['meta']
        ];
    }

    /**
     * Get unread notifications (admin)
     */
    public function getUnread(): array
    {
        return $this->notification->getUnread($this->audience);
    }

    /**
     * Count unread notifications
     */
    public function unreadCount(): int
    {
        return $this->notification->countUnread($this->audience);
    }

    /**
     * Mark a single notification as read
     */
    public function markAsRead(int $notificationId, int $adminId): void
    {
        $this->notification->markAsRead($notificationId, $adminId);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(int $adminId): void
    {
        $this->notification->markAllAsRead($this->audience, $adminId);
    }
}
