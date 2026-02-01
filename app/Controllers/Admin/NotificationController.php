<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\NotificationService;
use Core\Database;
use Core\Request;
use Core\Response;
use Helpers\ErrorResponse;
use Throwable;

class NotificationController extends BaseController
{
    private NotificationService $notificationService;

    public function __construct(
        Database $db,
        Request $request,
        Response $response
    ) {
        parent::__construct($db, $request, $response);
        $this->notificationService = new NotificationService($db);
    }

    /**
     * Get paginated & grouped notifications (admin)
     */
    public function index()
    {
        $this->requireAdmin();

        $perPage = (int) $this->request->get('per_page', 20);
        $page    = (int) $this->request->get('page', 1);
        $status  = $this->request->get('status'); // optional: unread/read

        try {
            $notifications = $this->notificationService
                ->getGroupedPaginated($page, $perPage, $status);
        } catch (Throwable $e) {
            return $this->response->error(
                ErrorResponse::formatResponse($e),
                500
            );
        }

        return $this->response->success(
            $notifications,
            'Notifications fetched successfully'
        );
    }

    /**
     * Get unread notifications (admin)
     */
    public function unread()
    {
        $this->requireAdmin();

        try {
            $notifications = $this->notificationService->getUnread();
        } catch (Throwable $e) {
            return $this->response->error(
                ErrorResponse::formatResponse($e),
                500
            );
        }

        return $this->response->success(
            $notifications,
            'Unread notifications fetched successfully'
        );
    }

    /**
     * Get unread notifications count (admin)
     */
    public function unreadCount()
    {
        $this->requireAdmin();

        try {
            $count = $this->notificationService->unreadCount();
        } catch (Throwable $e) {
            return $this->response->error(
                ErrorResponse::formatResponse($e),
                500
            );
        }

        return $this->response->success(
            ['count' => $count],
            'Unread notification count fetched successfully'
        );
    }

    /**
     * Mark a single notification as read
     */
    public function markAsRead(int $id)
    {
        $this->requireAdmin();

        try {
            $this->notificationService->markAsRead($id, $this->getUserId());
        } catch (Throwable $e) {
            return $this->response->error(
                ErrorResponse::formatResponse($e),
                500
            );
        }

        return $this->response->success(
            null,
            'Notification marked as read'
        );
    }

    /**
     * Mark all notifications as read (admin)
     */
    public function markAllAsRead()
    {
        $this->requireAdmin();

        try {
            $this->notificationService->markAllAsRead($this->getUserId());
        } catch (Throwable $e) {
            return $this->response->error(
                ErrorResponse::formatResponse($e),
                500
            );
        }

        return $this->response->success(
            null,
            'All notifications marked as read'
        );
    }
}
