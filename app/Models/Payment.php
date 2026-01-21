<?php

namespace App\Models;

/**
 * Payment Model
 * 
 * Manages payment records and transactions
 */
class Payment extends BaseModel
{
    protected string $table = 'payments';
    protected array $fillable = [
        'order_id',
        'customer_id',
        'amount',
        'payment_method',
        'transaction_id',
        'status',
        'gateway_response',
        'created_at',
        'updated_at',
    ];

    /**
     * Get payment by transaction ID
     */
    public function getByTransactionId(string $transactionId): ?array
    {
        return $this->findBy('transaction_id', $transactionId);
    }

    /**
     * Get order payments
     */
    public function getOrderPayments(int $orderId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE order_id = ? ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, [$orderId]);
    }

    /**
     * Get successful payments
     */
    public function getSuccessful(int $customerId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE customer_id = ? AND status = 'success' ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, [$customerId]);
    }

    /**
     * Update payment status
     */
    public function updateStatus(int $paymentId, string $status, ?string $response = null): int
    {
        $data = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
        if ($response) {
            $data['gateway_response'] = $response;
        }
        return $this->db->update($this->table, $data, "id = {$paymentId}");
    }

    /**
     * Get total revenue
     */
    public function getTotalRevenue(): float
    {
        $sql = "SELECT SUM(amount) as total FROM {$this->table} WHERE status = 'success'";
        $result = $this->db->fetch($sql);
        return (float)($result['total'] ?? 0);
    }
}
