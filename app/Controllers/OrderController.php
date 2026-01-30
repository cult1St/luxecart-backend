<?php

namespace App\Controllers;

use App\Services\OrderService;

class OrderController extends BaseController
{
    private OrderService $orderService;

    public function __construct(...$args)
    {
        parent::__construct(...$args);
        $this->orderService = new OrderService($this->db);
    }

    /**
     * GET /orders/history
     */
    public function history(): void
    {
        try {
            $this->requireAuth();
            $userId = $this->getUserId();

            $orders = $this->orderService->getOrderHistorySummary($userId);

            $this->response->success([
                'message' => 'Order history retrieved',
                'orders'  => $orders
            ]);
        } catch (\Throwable $e) {
            $this->response->error(
                'Failed to fetch order history',
                ['exception' => $e->getMessage()],
                500
            );
        }
    }
}
