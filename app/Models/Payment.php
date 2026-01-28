<?php

namespace App\Models;

/**
 * Payment Model
 * 
 * Manages payment records - Returns stdClass objects for better OOP interface
 */
class Payment extends BaseModel
{
    protected string $table = 'payments';
    protected array $fillable = [
        'user_id',
        'amount',
        'transaction_reference',
        'payment_method',
        'status',
        'gateway_response',
        'created_at',
        'updated_at',
    ];

    /**
     * Find payment by transaction reference - Returns stdClass
     */
    public function findByReference(string $reference): ?object
    {
        return $this->findBy('transaction_reference', $reference);
    }

    /**
     * Create payment record
     */
    public function createPayment(int $userId, float $amount, string $reference, string $paymentMethod): int|false
    {
        return $this->db->insert($this->table, [
            'user_id'                => $userId,
            'amount'                 => $amount,
            'transaction_reference'  => $reference,
            'payment_method'         => $paymentMethod,
            'status'                 => 'pending',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update payment status and gateway response
     */
    public function updatePaymentStatus(int $paymentId, string $status, ?string $gatewayResponse = null): bool
    {
        $data = [
            'status'     => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($gatewayResponse) {
            $data['gateway_response'] = $gatewayResponse;
        }

        return (bool) $this->db->update($this->table, $data, "id = {$paymentId}");
    }

    /**
     * Check if payment reference exists
     */
    public function referenceExists(string $reference): bool
    {
        return $this->findByReference($reference) !== null;
    }

    /**
     * Get user's successful payments - Returns array of stdClass objects
     */
    public function getSuccessfulPayments(int $userId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? AND status = 'success' ORDER BY created_at DESC";
        $results = $this->db->fetchAll($sql, [$userId]);
        return $this->useObjects ? $this->toObjectArray($results) : $results;
    }

    /**
     * Get payment by ID - Returns stdClass
     */
    public function getPayment(int $paymentId): ?object
    {
        return $this->find($paymentId);
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
