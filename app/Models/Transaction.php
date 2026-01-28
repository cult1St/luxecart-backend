<?php

namespace App\Models;

/**
 * Transaction Model
 * 
 * Manages transaction records (user payment intent before actual payment)
 */
class Transaction extends BaseModel
{
    protected string $table = 'transactions';
    protected array $fillable = [
        'user_id',
        'amount',
        'reference',
        'payment_method',
        'remark',
        'created_at',
        'updated_at',
    ];

    /**
     * Create transaction record
     */
    public function createTransaction(int $userId, float $amount, string $reference, string $paymentMethod, string $remark): int|false
    {
        return $this->db->insert($this->table, [
            'user_id'        => $userId,
            'amount'         => $amount,
            'reference'      => $reference,
            'payment_method' => $paymentMethod,
            'remark'         => $remark,
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Find transaction by reference
     */
    public function findByReference(string $reference): ?array
    {
        return $this->findBy('reference', $reference);
    }

    /**
     * Check if transaction reference exists
     */
    public function referenceExists(string $reference): bool
    {
        return (bool) $this->findByReference($reference);
    }

   

    /**
     * Get user's transactions
     */
    public function getUserTransactions(int $userId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, [$userId]);
    }

    /**
     * Get transaction by ID
     */
    public function getTransaction(int $transactionId): ?array
    {
        return $this->find($transactionId);
    }

    /**
     * Get total transaction amount for user
     */
    public function getUserTotalAmount(int $userId): float
    {
        $sql = "SELECT SUM(amount) as total FROM {$this->table} WHERE user_id = ? AND status = 'completed'";
        $result = $this->db->fetch($sql, [$userId]);
        return (float)($result['total'] ?? 0);
    }
}
