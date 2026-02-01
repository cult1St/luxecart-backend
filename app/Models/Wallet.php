<?php

namespace App\Models;

/**
 * Wallet Model
 * 
 * Manages user wallets and wallet transactions (topups, debits, purchases)
 * Keeps track of all wallet history for auditing and reconciliation
 */
class Wallet extends BaseModel
{
    protected string $table = 'wallets';
    protected array $fillable = [
        'user_id',
        'balance',
        'created_at',
        'updated_at',
    ];

    /**
     * Create or get user's wallet
     * Returns the wallet object or creates a new one if it doesn't exist
     * 
     * @param int $userId
     * @return object stdClass wallet object
     */
    public function getOrCreateWallet(int $userId): object
    {
        $wallet = $this->findBy('user_id', $userId);
        
        if ($wallet) {
            return $wallet;
        }

        // Create new wallet with zero balance
        $this->db->insert($this->table, [
            'user_id'    => $userId,
            'balance'    => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Return the newly created wallet
        return $this->findBy('user_id', $userId);
    }

    /**
     * Check if user has a wallet
     * 
     * @param int $userId
     * @return bool
     */
    public function hasWallet(int $userId): bool
    {
        return $this->findBy('user_id', $userId) !== null;
    }

    /**
     * Get wallet by user ID
     * 
     * @param int $userId
     * @return object|null
     */
    public function getByUserId(int $userId): ?object
    {
        return $this->findBy('user_id', $userId);
    }

    /**
     * Credit wallet with amount (add to balance)
     * Also records the transaction in wallet history
     * 
     * @param int $userId
     * @param float $amount
     * @param string $transactionType (e.g., 'topup', 'refund')
     * @param string|null $reference Payment/transaction reference
     * @param string|null $description Additional description
     * @return bool
     * @throws Exception
     */
    public function creditWallet(int $userId, float $amount, string $transactionType = 'topup', ?string $reference = null, ?string $description = null): bool
    {
        // Get or create wallet
        $wallet = $this->getOrCreateWallet($userId);
        
        // Update balance
        $newBalance = $wallet->balance + $amount;
        $updated = (bool) $this->db->update(
            $this->table,
            [
                'balance'    => $newBalance,
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            "id = {$wallet->id}"
        );

        if ($updated) {
            // Record transaction in history
            $this->recordWalletHistory(
                $wallet->id,
                $userId,
                'credit',
                $amount,
                $transactionType,
                $reference,
                $description
            );
        }

        return $updated;
    }

    /**
     * Debit wallet with amount (subtract from balance)
     * Also records the transaction in wallet history
     * 
     * @param int $userId
     * @param float $amount
     * @param string $transactionType (e.g., 'purchase', 'withdrawal')
     * @param string|null $reference Transaction reference
     * @param string|null $description Additional description
     * @return bool
     * @throws Exception
     */
    public function debitWallet(int $userId, float $amount, string $transactionType = 'purchase', ?string $reference = null, ?string $description = null): bool
    {
        $wallet = $this->getByUserId($userId);
        
        if (!$wallet) {
            throw new \Exception('Wallet not found for user: ' . $userId);
        }

        // Check sufficient balance
        if ($wallet->balance < $amount) {
            throw new \Exception('Insufficient wallet balance');
        }

        // Update balance
        $newBalance = $wallet->balance - $amount;
        $updated = (bool) $this->db->update(
            $this->table,
            [
                'balance'    => $newBalance,
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            "id = {$wallet->id}"
        );

        if ($updated) {
            // Record transaction in history
            $this->recordWalletHistory(
                $wallet->id,
                $userId,
                'debit',
                $amount,
                $transactionType,
                $reference,
                $description
            );
        }

        return $updated;
    }

    /**
     * Get wallet balance for user
     * 
     * @param int $userId
     * @return float
     */
    public function getBalance(int $userId): float
    {
        $wallet = $this->getByUserId($userId);
        return $wallet ? (float) $wallet->balance : 0;
    }

    /**
     * Get wallet history for user (all transactions)
     * 
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array Array of stdClass objects
     */
    public function getWalletHistory(int $userId, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT * FROM wallet_history 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";
        
        $results = $this->db->fetchAll($sql, [$userId, $limit, $offset]);
        return $this->useObjects ? $this->toObjectArray($results) : $results;
    }

    /**
     * Get wallet transactions by type
     * 
     * @param int $userId
     * @param string $type 'topup', 'purchase', 'refund', etc.
     * @param int $limit
     * @return array
     */
    public function getWalletHistoryByType(int $userId, string $type, int $limit = 50): array
    {
        $sql = "SELECT * FROM wallet_history 
                WHERE user_id = ? AND transaction_type = ? 
                ORDER BY created_at DESC 
                LIMIT ?";
        
        $results = $this->db->fetchAll($sql, [$userId, $type, $limit]);
        return $this->useObjects ? $this->toObjectArray($results) : $results;
    }

    /**
     * Get total amount credited to wallet
     * 
     * @param int $userId
     * @return float
     */
    public function getTotalCredited(int $userId): float
    {
        $sql = "SELECT SUM(amount) as total FROM wallet_history 
                WHERE user_id = ? AND action = 'credit'";
        
        $result = $this->db->fetch($sql, [$userId]);
        return (float) ($result['total'] ?? 0);
    }

    /**
     * Get total amount debited from wallet
     * 
     * @param int $userId
     * @return float
     */
    public function getTotalDebited(int $userId): float
    {
        $sql = "SELECT SUM(amount) as total FROM wallet_history 
                WHERE user_id = ? AND action = 'debit'";
        
        $result = $this->db->fetch($sql, [$userId]);
        return (float) ($result['total'] ?? 0);
    }

    /**
     * Record wallet transaction in history
     * PRIVATE: Called internally by credit/debit methods
     * 
     * @param int $walletId
     * @param int $userId
     * @param string $action 'credit' or 'debit'
     * @param float $amount
     * @param string $transactionType (e.g., 'topup', 'purchase')
     * @param string|null $reference
     * @param string|null $description
     * @return bool
     */
    private function recordWalletHistory(
        int $walletId,
        int $userId,
        string $action,
        float $amount,
        string $transactionType,
        ?string $reference = null,
        ?string $description = null
    ): bool
    {
        return (bool) $this->db->insert('wallet_history', [
            'wallet_id'       => $walletId,
            'user_id'         => $userId,
            'action'          => $action,
            'amount'          => $amount,
            'transaction_type' => $transactionType,
            'reference'       => $reference,
            'description'     => $description,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get wallet with history summary
     * 
     * @param int $userId
     * @return object|null
     */
    public function getWalletWithStats(int $userId): ?object
    {
        $wallet = $this->getByUserId($userId);
        
        if (!$wallet) {
            return null;
        }

        // Add stats to wallet object
        $wallet->total_credited = $this->getTotalCredited($userId);
        $wallet->total_debited = $this->getTotalDebited($userId);
        
        return $wallet;
    }
}
