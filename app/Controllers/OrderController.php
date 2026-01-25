<?php

namespace App\Controllers;

use App\Models\Order;
use Throwable;

class OrderController extends BaseController
{
    public function history()
    {
        try {
            // TEMP until auth exists
            $userId = 1;

            if (!$userId) {
                $this->response->error('User not identified', [], 400);
            }

            $orderModel = new Order($this->db);

            $orders = $this->getOrderHistoryData($orderModel, $userId);

            $this->response->success([
                'user' => [
                    'id' => $userId,
                    'name' => 'Ayomide',
                ],
                'orders' => $orders,
            ]);

        } catch (Throwable $e) {
            // Log later if logger exists
            $this->response->error(
                'Failed to fetch order history',
                [
                    'exception' => $e->getMessage()
                ],
                500
            );
        }
    }

    private function getOrderHistoryData(Order $orderModel, int $userId): array
    {
        $sql = "
            SELECT 
                order_id,
                final_amount,
                status,
                created_at
            FROM orders
            WHERE user_id = ?
            ORDER BY created_at DESC
        ";

        $rows = $this->db->fetchAll($sql, [$userId]);

        // Empty array is valid (no orders yet)
        if (!$rows) {
            return [];
        }

        return array_map(function ($row) {
            return [
                'order_id' => (int) $row['order_id'],
                'amount' => (float) $row['final_amount'],
                'status' => $row['status'],
                'date' => date('M d, Y', strtotime($row['created_at'])),
            ];
        }, $rows);
    }
}
