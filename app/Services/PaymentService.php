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
 * Uses Payment model for data operations
 */
class PaymentService
{
    private PaystackService $paystackService;
    private User $userModel;
    private Payment $paymentModel;

    private Transaction $transactionModel;

    public function __construct(private Database $db)
    {
        $this->paystackService = new PaystackService();
        $this->userModel = new User($db);
        $this->paymentModel = new Payment($db);
        $this->transactionModel = new Transaction($db);

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
                        'email'      => $user['email'],
                        'amount'     => $amount,
                        'reference'  => $reference,
                        'callback_url' => env('PAYSTACK_CALLBACK_URL'),
                        'cancel_url' => env('PAYSTACK_CANCEL_URL'),
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
     * @param int $userId
     * @param string $reference Transaction reference
     * @return int Payment ID on success
     * @throws Exception
     */
    public function verifyPayment(int $userId, string $reference): int
    {
        // Check if payment already recorded
        if ($this->paymentModel->referenceExists($reference)) {
            throw new Exception('Payment already recorded for this reference');
        }

        // Get transaction details
        $transaction = $this->db->fetch(
            'SELECT * FROM transactions WHERE reference = ?',
            [$reference]
        );

        if (!$transaction) {
            throw new Exception('Transaction not found for reference: ' . $reference);
        }

        try {
            $this->db->beginTransaction();

            // Verify with gateway based on payment method
            switch ($transaction['payment_method']) {
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
                    throw new Exception('Unsupported payment method: ' . $transaction['payment_method']);
            }

            // Create payment record in database
            $paymentId = $this->paymentModel->createPayment(
                $userId,
                $transaction['amount'],
                $reference,
                $transaction['payment_method']
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

            // Update transaction status
            $this->db->update(
                'transactions',
                [
                    'status'     => 'completed',
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
                "id = {$transaction['id']}"
            );

            $this->db->commit();
            return $paymentId;

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
}
