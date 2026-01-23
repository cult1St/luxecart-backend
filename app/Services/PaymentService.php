<?php 
namespace App\Services;

use Core\Database;
use Exception;

/**
 * Payment Service
 * 
 * Handles payment processing and related operations
 */
class PaymentService
{
    // Payment related methods would go here
    public function __construct(
        private Database $db
    ){}


    /**
     * Process a payment
     */
    public function processPayment(array $orderData, int $userId, string $paymentMethod): bool
    {
        // Placeholder for payment processing logic
        //check if a payment for this order already exists
        $existingPayment = $this->db->fetch("SELECT * FROM payments WHERE order_id = ?", [$orderData['order_id']]);
        if($existingPayment){
            throw new Exception('A Payment Record for this order already exists');
        }
        //create a payment record in the database
        $paymentId = $this->db->insert('payments', [
            'user_id' => $userId,
            'order_id' => $orderData['order_id'],
            'amount' => $orderData['final_amount'],
            'payment_method' => $paymentMethod,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        //vertify payment with the payment gateway (placeholder)
        $paymentVerified = true; //assume payment is successful for this placeholder

        if($paymentVerified){
            //update payment record status to completed
            $this->db->update('payments', [
                'status' => 'completed',
                'updated_at' => date('Y-m-d H:i:s'),
            ], "id = {$paymentId}");

            //update order status to paid
            $this->db->update('orders', [
                'status' => 'paid',
                'updated_at' => date('Y-m-d H:i:s'),
            ], "id = {$orderData['id']}");

            return true;
        }
        $this->db->update('payments', [
            'status' => 'failed',
            'updated_at' => date('Y-m-d H:i:s'),
        ], "id = {$paymentId}");
        
        return false;
    }
}