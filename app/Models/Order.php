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
        'tax',
        'shipping',
        'discount',
        'total',
        'currency',
        'created_at',
        'updated_at',
    ];

    /**
     * Get order with items
     */
    public function getWithItems(int $orderId): ?array
    {
        $order = $this->find($orderId);
        if ($order) {
            $order['items'] = $this->getOrderItems($orderId);
        }
        return $order;
    }

    /**
     * Get order items
     */
    public function getOrderItems(int $orderId): array
    {
        $sql = "SELECT oi.*, p.name as product_name, p.image_url
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?";
        return $this->db->fetchAll($sql, [$orderId]);
    }

    /**
     * Get customer orders
     */
    public function getCustomerOrders(int $customerId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, [$customerId]);
    }

    /**
     * Get orders by status
     */
    public function getByStatus(string $status): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE status = ? ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, [$status]);
    }

    /**
     * Update order status
     */
    public function updateStatus(int $orderId, string $status): int
    {
        return $this->db->update($this->table, ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')], "id = {$orderId}");
    }

    /**
     * Get order summary
     */
    public function getSummary(int $orderId): ?array
    {
        $sql = "SELECT id, order_id, user_id, final_amount, status, created_at
                FROM {$this->table}
                WHERE id = ?";
        return $this->db->fetch($sql, [$orderId]);
    }

    /**
     * Count pending orders
     */
    public function countPending(): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE status = 'pending'";
        $result = $this->db->fetch($sql);
        return $result['count'] ?? 0;
    }

    /**
     * get Orders count by users summary
     */
    public function getSummaryByUsers(int $userId): array
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

        return $this->db->fetch($sql, [$userId]);
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
