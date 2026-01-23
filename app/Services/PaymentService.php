<?php

namespace App\Services;

use Core\Database;
use Exception;
use Throwable;


/**
 * Payment Service. Handle all payment processes from initialization to verification
 */
class PaymentService
{
    private PaystackService $paystackService;

    public function __construct(private Database $db)
    {
        $this->paystackService = new PaystackService();
    }

    /**
     * Initialize a payment
     */
    public function initializePayment(int $userId, string $paymentMethod = 'paystack'): array
    {
        $user = $this->db->fetch(
            "SELECT id, email FROM users WHERE id = ?",
            [$userId]
        );

        if (!$user) {
            throw new Exception('Invalid user');
        }

        try {
            $this->db->beginTransaction();

            $cart = $this->db->fetch(
                "SELECT * FROM carts WHERE user_id = ? FOR UPDATE",
                [$userId]
            );

            if (!$cart) {
                throw new Exception('No cart found');
            }

            $cartItems = $this->db->fetchAll(
                "SELECT product_id, price, quantity FROM cart_items WHERE cart_id = ?",
                [$cart['id']]
            );

            if (empty($cartItems)) {
                throw new Exception('Cart is empty');
            }

            $totalAmount = 0;
            foreach ($cartItems as $item) {
                $totalAmount += ((float) $item['price']) * ((int) $item['quantity']);
            }

            if ($totalAmount <= 0) {
                throw new Exception('Invalid cart amount');
            }

            $reference = $paymentMethod . '_' . bin2hex(random_bytes(10));

            $transactionId = $this->db->insert('transactions', [
                'user_id'        => $userId,
                'payment_method'=> $paymentMethod,
                'amount'         => $totalAmount,
                'reference'      => $reference,
                'cart_snapshot'  => json_encode($cartItems),
                'status'         => 'initialized',
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);

            if (!$transactionId) {
                throw new Exception('Failed to create transaction');
            }

            switch ($paymentMethod) {
                case 'paystack':
                    $response = $this->paystackService->initializeTransaction([
                        'email'        => $user['email'],
                        'amount'       => $totalAmount,
                        'reference'    => $reference,
                        'callback_url' => env('PAYSTACK_CALLBACK_URL'),
                        'cancel_url'   => env('PAYSTACK_CANCEL_URL'),
                    ]);
                    $paymentUrl = $response['data']['authorization_url'] ?? null;
                    break;

                default:
                    throw new Exception('Unsupported payment method');
            }

            if (!$paymentUrl) {
                throw new Exception('Failed to generate payment URL');
            }

            $this->db->commit();

            return [
                'reference'   => $reference,
                'payment_url'=> $paymentUrl,
            ];

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new Exception('Payment initialization failed: ' . $e->getMessage());
        }
    }

    /**
     * Verify payment
     */
    public function verifyPayment(int $userId, string $reference): bool|int{
        //check for previous payment with this reference, if there is, throw an exception
        $existingPayment = $this->db->fetch("SELECT * FROM payments WHERE transaction_reference = ?", [$reference]);
        if ($existingPayment) {
            throw new Exception('A Payment Record for this order already exists');
        }
        $transaction = $this->db->fetch('SELECT * FROM transactions WHERE reference = ?', [$reference]);
        if (!$transaction) {
            throw new Exception('Invalid Transaction Reference');
        }
        $paymentId = $this->db->insert('payments', [
            'user_id' => $userId,
            'amount' => $transaction['amount'],
            'transcation_reference' => $reference,
            'payment_method' => $transaction['payment_method'],
            'status' => 'pending',
        ]);

        if (!$paymentId) {
            throw new Exception('Unable to create payment Record');
        }

        try{

        $this->db->beginTransaction();
        //verify payment with payment gateway
        $isVerified = false;
        switch($transaction['payment_method']){
            case 'paystack':
                $verifyResponse = $this->paystackService->verifyTransaction($reference);
                if ($verifyResponse['data']['status'] !== 'success') {
                    throw new Exception('Payment verification failed: ' . ($verifyResponse['message'] ?? 'Unknown error'));
                }
                $isVerified = true;
                break;
            default:
                throw new Exception('Payment Method Not Supported');
        }
        if (!$isVerified) {
            throw new Exception('Unable to verify payment');
        }
        //update payment record status to success
        $this->db->update('payments', [
            "status" => "success",
            "gateway_response" => json_encode($verifyResponse)
        ], "id = {$paymentId}");
        $this->db->commit();
        return $paymentId;

        }catch (Throwable $e) {
            $this->db->update('payments', [
                "status" => "failed",
                "gateway_response" => $e->getMessage()
            ], "id = {$paymentId}");
        }
        return false;
       
    }
}
