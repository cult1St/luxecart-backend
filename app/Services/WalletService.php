<?php

namespace App\Services;

use App\Models\Wallet;
use Core\Database;
use Exception;
use Throwable;

/**
 * Wallet Service
 * 
 * Handles wallet operations: topups, purchases, and balance management
 * Hidden from user interface - backend only operations
 */
class WalletService
{
    private Wallet $walletModel;

    public function __construct(private Database $db)
    {
        $this->walletModel = new Wallet($db);
    }

    /**
     * Process payment verification wallet flow
     * 
     * After successful payment verification:
     * 1. Create/get wallet for user
     * 2. Credit wallet with verified amount (topup)
     * 3. Debit wallet for purchase
     * 4. All transactions recorded in wallet history
     * 
     * @param int $userId
     * @param float $amount Verified amount from payment gateway
     * @param string $paymentReference Payment reference from gateway
     * @param string|null $orderReference Order/transaction reference
     * @return array ['success' => bool, 'wallet_balance' => float, 'message' => string]
     * @throws Exception
     */
    public function processPaymentVerification(
        int $userId,
        float $amount,
        string $paymentReference,
        ?string $orderReference = null
    ): array
    {
        try {
            $startedTransaction = false;
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $startedTransaction = true;
            }

            // Step 1: Get or create wallet for user
            $wallet = $this->walletModel->getOrCreateWallet($userId);

            // Step 2: Credit wallet (topup) with verified amount
            $this->walletModel->creditWallet(
                $userId,
                $amount,
                'topup',
                $paymentReference,
                'Payment verified and credited to wallet'
            );

            // Step 3: Debit wallet for purchase/transaction
            $this->walletModel->debitWallet(
                $userId,
                $amount,
                'purchase',
                $orderReference ?? $paymentReference,
                'Purchase deducted from wallet'
            );

            // Step 4: Get updated balance
            $updatedWallet = $this->walletModel->getByUserId($userId);
            $newBalance = $updatedWallet ? (float) $updatedWallet->balance : 0;

            if ($startedTransaction && $this->db->inTransaction()) {
                $this->db->commit();
            }

            return [
                'success'         => true,
                'wallet_balance'  => $newBalance,
                'message'         => 'Payment processed successfully. Wallet updated.',
            ];

        } catch (Throwable $e) {
            if (isset($startedTransaction) && $startedTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new Exception('Wallet processing failed: ' . $e->getMessage());
        }
    }

    /**
     * Top up wallet directly (manual credit)
     * 
     * @param int $userId
     * @param float $amount
     * @param string|null $reference Payment/transaction reference
     * @param string|null $description
     * @return array ['success' => bool, 'new_balance' => float]
     * @throws Exception
     */
    public function topupWallet(
        int $userId,
        float $amount,
        ?string $reference = null,
        ?string $description = null
    ): array
    {
        try {
            $this->walletModel->creditWallet(
                $userId,
                $amount,
                'topup',
                $reference,
                $description ?? 'Manual wallet topup'
            );

            $newBalance = $this->walletModel->getBalance($userId);

            return [
                'success'     => true,
                'new_balance' => $newBalance,
            ];
        } catch (Throwable $e) {
            throw new Exception('Wallet topup failed: ' . $e->getMessage());
        }
    }

    /**
     * Process purchase/debit from wallet
     * 
     * @param int $userId
     * @param float $amount
     * @param string|null $reference Order/transaction reference
     * @param string|null $description
     * @return array ['success' => bool, 'new_balance' => float]
     * @throws Exception
     */
    public function purchaseFromWallet(
        int $userId,
        float $amount,
        ?string $reference = null,
        ?string $description = null
    ): array
    {
        try {
            $this->walletModel->debitWallet(
                $userId,
                $amount,
                'purchase',
                $reference,
                $description ?? 'Purchase deducted from wallet'
            );

            $newBalance = $this->walletModel->getBalance($userId);

            return [
                'success'     => true,
                'new_balance' => $newBalance,
            ];
        } catch (Throwable $e) {
            throw new Exception('Purchase processing failed: ' . $e->getMessage());
        }
    }

    /**
     * Refund amount back to wallet
     * 
     * @param int $userId
     * @param float $amount
     * @param string|null $reference Original transaction reference
     * @param string|null $reason Reason for refund
     * @return array ['success' => bool, 'new_balance' => float]
     * @throws Exception
     */
    public function refundToWallet(
        int $userId,
        float $amount,
        ?string $reference = null,
        ?string $reason = null
    ): array
    {
        try {
            $this->walletModel->creditWallet(
                $userId,
                $amount,
                'refund',
                $reference,
                $reason ?? 'Refund credited to wallet'
            );

            $newBalance = $this->walletModel->getBalance($userId);

            return [
                'success'     => true,
                'new_balance' => $newBalance,
            ];
        } catch (Throwable $e) {
            throw new Exception('Refund processing failed: ' . $e->getMessage());
        }
    }

    /**
     * Get user's wallet balance
     * 
     * @param int $userId
     * @return float
     */
    public function getBalance(int $userId): float
    {
        return $this->walletModel->getBalance($userId);
    }

    /**
     * Get wallet with statistics
     * 
     * @param int $userId
     * @return object|null
     */
    public function getWalletInfo(int $userId): ?object
    {
        return $this->walletModel->getWalletWithStats($userId);
    }

    /**
     * Get wallet transaction history
     * 
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getHistory(int $userId, int $limit = 50, int $offset = 0): array
    {
        return $this->walletModel->getWalletHistory($userId, $limit, $offset);
    }

    /**
     * Get wallet transactions by type
     * 
     * @param int $userId
     * @param string $type 'topup', 'purchase', 'refund', etc.
     * @param int $limit
     * @return array
     */
    public function getHistoryByType(int $userId, string $type, int $limit = 50): array
    {
        return $this->walletModel->getWalletHistoryByType($userId, $type, $limit);
    }

    /**
     * Check if user can afford a purchase
     * 
     * @param int $userId
     * @param float $amount
     * @return bool
     */
    public function canAfford(int $userId, float $amount): bool
    {
        $balance = $this->getBalance($userId);
        return $balance >= $amount;
    }

    /**
     * Get wallet statistics
     * 
     * @param int $userId
     * @return array
     */
    public function getStats(int $userId): array
    {
        $wallet = $this->getWalletInfo($userId);

        if (!$wallet) {
            return [
                'balance'         => 0,
                'total_credited'  => 0,
                'total_debited'   => 0,
                'net_balance'     => 0,
            ];
        }

        return [
            'balance'         => (float) $wallet->balance,
            'total_credited'  => (float) $wallet->total_credited,
            'total_debited'   => (float) $wallet->total_debited,
            'net_balance'     => (float) $wallet->balance,
        ];
    }
}
