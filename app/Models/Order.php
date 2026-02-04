<?php

namespace App\Models;

/**
 * Order Model
 *
 * Manages customer orders
 */
class Order extends BaseModel
{
    protected string $table = 'orders';

    protected array $fillable = [
        'order_number',
        'user_id',
        'transaction_reference',
        'status',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'final_amount',
        'delivered_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Get order with items
     */
    public function getWithItems(int $orderId): ?object
    {
        $order = $this->find($orderId);

        if (!$order) {
            return null;
        }

        $items = $this->getOrderItems($orderId);

        if ($this->useObjects) {
            $order->items = $items;
        } else {
            $order['items'] = $items;
        }

        return $order;
    }

    /**
     * Get order items
     */
    public function getOrderItems(int $orderId): array
    {
        $sql = "
            SELECT oi.*, p.name AS product_name, p.images
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ";

        $results = $this->db->fetchAll($sql, [$orderId]);

        return $this->useObjects
            ? $this->toObjectArray($results)
            : $results;
    }

    /**
     * Get customer orders
     */
    public function getCustomerOrders(int $customerId): array
    {
        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE user_id = ?
            ORDER BY created_at DESC
        ";

        $results = $this->db->fetchAll($sql, [$customerId]);

        return $this->useObjects
            ? $this->toObjectArray($results)
            : $results;
    }

    /**
     * Get orders by status
     */
    public function getByStatus(string $status): array
    {
        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE status = ?
            ORDER BY created_at DESC
        ";

        $results = $this->db->fetchAll($sql, [$status]);

        return $this->useObjects
            ? $this->toObjectArray($results)
            : $results;
    }

    /**
     * Update order status
     */
    public function updateStatus(int $orderId, string $status): int
    {
        return $this->update($orderId, [
            'status'     => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get order summary
     */
    public function getSummary(int $orderId): ?object
    {
        $sql = "
            SELECT id, order_id, user_id, final_amount, status, created_at
            FROM {$this->table}
            WHERE id = ?
            LIMIT 1
        ";

        $result = $this->db->fetch($sql, [$orderId]);

        return $result
            ? ($this->useObjects ? $this->toObject($result) : $result)
            : null;
    }

    /**
     * Count pending orders
     */
    public function countPending(): int
    {
        $sql = "SELECT COUNT(*) AS count FROM {$this->table} WHERE status = 'pending'";
        $result = $this->db->fetch($sql);

        return (int) ($result['count'] ?? 0);
    }

    /**
     * Orders summary by user
     */
    public function getSummaryByUsers(int $userId): object|array
    {
        $sql = "
            SELECT
                COUNT(*) AS total_orders,
                SUM(final_amount) AS total_spent,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_orders,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_orders
            FROM {$this->table}
            WHERE user_id = ?
        ";

        $result = $this->db->fetch($sql, [$userId]);

        return $this->useObjects
            ? $this->toObject($result)
            : $result;
    }

    /**
     * Get recent orders count (last 7 days)
     */
    public function getRecentOrdersCount(): int
    {
        $sql = "
            SELECT COUNT(*) AS count
            FROM {$this->table}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ";

        $result = $this->db->fetch($sql);

        return (int) ($result['count'] ?? 0);
    }

    /**
     * Get total sales amount
     */
    public function getTotalSalesAmount(): float
    {
        $sql = "
            SELECT SUM(final_amount) AS total_sales
            FROM {$this->table}
            WHERE status = 'completed'
        ";

        $result = $this->db->fetch($sql);

        return (float) ($result['total_sales'] ?? 0);
    }

    /**
     * Get latest orders
     */
    public function getLatestOrders(int $limit = 10): array
    {
        $sql = "
            SELECT *
            FROM {$this->table}
            ORDER BY created_at DESC
            LIMIT ?
        ";

        $results = $this->db->fetchAll($sql, [$limit]);

        return $this->useObjects
            ? $this->toObjectArray($results)
            : $results;
    }

    public function findByTransactionReference(string $reference): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE transaction_reference = ? LIMIT 1",
            [$reference]
        ) ?: null;
    }

    public function create(array $data): int
    {
        return $this->db->insert($this->table, $data);
    }

    /**
     * Find order by order number
     */
    public function findByOrderNumber(string $orderNumber): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE order_number = ? LIMIT 1",
            [$orderNumber]
        ) ?: null;
    }

    public function findByUser(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC",
            [$userId]
        );
    }

    

}
