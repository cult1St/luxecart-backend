<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Payment;
use Core\Database;
use Exception;
use Throwable;

/**
 * Payment Service
 * 
 * Handles payment verification and recording
 * Integrates with Wallet service for hidden wallet operations
 * Users don't know about wallets - it's a backend-only system
 */
class PaymentService
{
    private PaystackService $paystackService;
    private User $userModel;
    private Payment $paymentModel;
    private Transaction $transactionModel;
    private WalletService $walletService;

    public function __construct(private Database $db)
    {
        $this->paystackService = new PaystackService();
        $this->userModel = new User($db);
        $this->paymentModel = new Payment($db);
        $this->transactionModel = new Transaction($db);
        $this->walletService = new WalletService($db);
    }

    /**
     * Initialize payment with gateway
     * 
     * IMPORTANT: Transaction should be created FIRST, then call this
     * Flow: createTransaction() → initializePayment() → User pays → verifyPayment()
     * 
     * @param int $userId
     * @param float $amount
     * @param string $reference Transaction reference (created beforehand)
     * @param string $paymentMethod Default 'paystack'
     * @return array Contains 'reference' and 'payment_url'
     * @throws Exception
     */
    public function initializePayment(int $userId, float $amount, ?string $reference = null, string $paymentMethod = 'paystack'): array
    {
        $user = $this->userModel->find($userId);
        if (!$user) {
            throw new Exception('User not found');
        }

        if(!$reference){
            $reference = $paymentMethod . '_' . uniqid();
        }

        try {
            switch ($paymentMethod) {
                case 'paystack':
                    $response = $this->paystackService->initializeTransaction([
                        'email'      => $user->email,  // stdClass property access
                        'amount'     => $amount,
                        'reference'  => $reference,
                        'callback_url' => env('PAYSTACK_CALLBACK_URL', 'http://localhost/payment/verify?method=paystack'),
                        'cancel_url' => env('PAYSTACK_CANCEL_URL', 'http://localhost/payment/cancel'),
                    ]);
                    $paymentUrl = $response['data']['authorization_url'] ?? null;
                    break;

                default:
                    throw new Exception('Unsupported payment method: ' . $paymentMethod);
            }

            if (!$paymentUrl) {
                throw new Exception('Failed to generate payment URL from gateway');
            }

            return [
                'reference'   => $reference,
                'payment_url' => $paymentUrl,
            ];

        } catch (Exception $e) {
            throw new Exception('Payment initialization failed: ' . $e->getMessage());
        }
    }

    /**
     * Verify payment with gateway and record in database
     * 
     * WALLET FLOW:
     * 1. Verify payment with gateway
     * 2. Create payment record
     * 3. Process wallet: Credit topup + Debit purchase
     * 4. All transactions recorded in wallet history
     * 
     * User doesn't know about wallet - it's transparent backend operation
     * 
     * @param int $userId
     * @param string $reference Transaction reference
     * @return array ['paymentId' => int, 'walletBalance' => float, 'message' => string]
     * @throws Exception
     */
    public function verifyPayment(int $userId, string $reference): array
    {
        // Check if payment already recorded
        if ($this->paymentModel->referenceExists($reference)) {
            throw new Exception('Payment already recorded for this reference');
        }

        // Get transaction details
        $transaction = $this->transactionModel->findByReference($reference);

        if (!$transaction) {
            throw new Exception('Transaction not found for reference: ' . $reference);
        }

        try {
            $this->db->beginTransaction();

            // Verify with gateway based on payment method
            switch ($transaction->payment_method) {
                case 'paystack':
                    $verifyResponse = $this->paystackService->verifyTransaction($reference);
                    
                    if ($verifyResponse['data']['status'] !== 'success') {
                        throw new Exception(
                            'Payment verification failed: ' . 
                            ($verifyResponse['message'] ?? 'Unknown error')
                        );
                    }
                    break;

                default:
                    throw new Exception('Unsupported payment method: ' . $transaction->payment_method);
            }

            // Create payment record in database
            $paymentId = $this->paymentModel->createPayment(
                $userId,
                $transaction->amount,
                $reference,
                $transaction->payment_method
            );

            if (!$paymentId) {
                throw new Exception('Failed to create payment record');
            }

            // Update payment status to success and store gateway response
            $this->paymentModel->updatePaymentStatus(
                $paymentId,
                'success',
                json_encode($verifyResponse)
            );

            // ========================================================
            // WALLET INTEGRATION: Process wallet after payment success
            // ========================================================
            $walletResult = $this->walletService->processPaymentVerification(
                $userId,
                $transaction->amount,
                $reference,
                $transaction->remark ?? null  // Optional order reference
            );

            $this->db->commit();

            return [
                'paymentId'      => $paymentId,
                'walletBalance'  => $walletResult['wallet_balance'],
                'message'        => 'Payment verified successfully',
            ];

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new Exception('Payment verification failed: ' . $e->getMessage());
        }
    }

    /**
     * Get payment by reference
     * 
     * @param string $reference
     * @return array|null
     */
    public function getPaymentByReference(string $reference): ?array
    {
        return $this->paymentModel->findByReference($reference);
    }

    /**
     * Get payment by ID
     * 
     * @param int $paymentId
     * @return array|null
     */
    public function getPayment(int $paymentId): ?array
    {
        return $this->paymentModel->getPayment($paymentId);
    }

    /**
     * Get user's successful payments
     * 
     * @param int $userId
     * @return array
     */
    public function getUserPayments(int $userId): array
    {
        return $this->paymentModel->getSuccessfulPayments($userId);
    }

    /**
     * Get user's wallet balance (hidden from UI, backend only)
     * 
     * @param int $userId
     * @return float
     */
    public function getWalletBalance(int $userId): float
    {
        return $this->walletService->getBalance($userId);
    }

    /**
     * Get wallet information with statistics
     * 
     * @param int $userId
     * @return object|null Wallet object with balance, total_credited, total_debited
     */
    public function getWalletInfo(int $userId): ?object
    {
        return $this->walletService->getWalletInfo($userId);
    }
}
