<?php

namespace App\Models;

/**
 * Transaction Model
 * 
 * Manages transaction records (user payment intent before actual payment)
 * Returns stdClass objects for better OOP interface
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
     * Find transaction by reference - Returns stdClass
     */
    public function findByReference(string $reference): ?object
    {
        return $this->findBy('reference', $reference);
    }

    /**
     * Check if transaction reference exists
     */
    public function referenceExists(string $reference): bool
    {
        return $this->findByReference($reference) !== null;
    }

    /**
     * Get user's transactions - Returns array of stdClass objects
     */
    public function getUserTransactions(int $userId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC";
        $results = $this->db->fetchAll($sql, [$userId]);
        return $this->useObjects ? $this->toObjectArray($results) : $results;
    }

    /**
     * Get transaction by ID - Returns stdClass
     */
    public function getTransaction(int $transactionId): ?object
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
