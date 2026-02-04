<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ShippingInfo;
use App\Models\Product;
use Core\Database;
use Exception;
use Throwable;

class OrderService
{
    private Order $orderModel;
    private OrderItem $orderItemModel;
    private Cart $cartModel;
    private CartItem $cartItemModel;
    private ShippingInfo $shippingModel;
    private Product $productModel;

    public function __construct(
        private Database $db
    ) {
        $this->orderModel = new Order($this->db);
        $this->orderItemModel = new OrderItem($this->db);
        $this->cartModel = new Cart($this->db);
        $this->cartItemModel = new CartItem($this->db);
        $this->shippingModel = new ShippingInfo($this->db);
        $this->productModel = new Product($this->db);
    }

    /**
     * Create order AFTER successful payment
     */
    public function createOrder(
        int $userId,
        string $transactionReference
    ): object {
        $startedTransaction = false;
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $startedTransaction = true;
        }

        try {
            /** 1. Prevent duplicate orders */
            if ($this->orderModel->findByTransactionReference($transactionReference)) {
                throw new Exception('Order already exists for this transaction');
            }

            /** 2. Get cart */
            $cart = $this->cartModel->findByUserId($userId);

            if (!$cart) {
                throw new Exception('Cart not found');
            }

            /** 3. Get cart items */
            $items = $this->cartItemModel->getByCart($cart->id);

            if (empty($items)) {
                throw new Exception('Cart is empty');
            }

            /** 4. Get shipping info */
            $shipping = $this->shippingModel->getByCart($cart->id);

            if (!$shipping) {
                throw new Exception('Shipping info missing');
            }

            /** 5. Calculate totals */
            $subtotal = 0;

            foreach ($items as $item) {
                $subtotal += $item->quantity * $item->price;
            }

            $discount       = (float) ($cart->discount_amount ?? 0);
            $shippingAmount = (float) ($shipping->shipping_amount ?? 0);

            $finalAmount = max(
                $subtotal + $shippingAmount - $discount,
                0
            );

            /** 6. Generate order number */
            $orderNumber = $this->generateOrderNumber();

            /** 7. Create order */
            $orderId = $this->orderModel->create([
                'order_number'          => $orderNumber,
                'user_id'               => $userId,
                'transaction_reference' => $transactionReference,
                'status'                => 'paid',
                'subtotal'              => $subtotal,
                'tax_amount'                   => 0,
                'shipping_amount'              => $shippingAmount,
                'discount_amount'              => $discount,
                'final_amount'                 => $finalAmount,
            ]);

            /** 8. Create order items */
            $orderItems = [];

            foreach ($items as $item) {
                $orderItems[] = [
                    'order_id'   => $orderId,
                    'product_id' => $item->product_id,
                    'quantity'   => $item->quantity,
                    'price'      => $item->price,
                    'subtotal'   => $item->quantity * $item->price,
                ];
            }

            $this->orderItemModel->insertMany($orderItems);

            /** 9. Finalize inventory (convert reservation → sold) */
            foreach ($items as $item) {
                $this->productModel->finalizeStock(
                    (int) $item->product_id,
                    (int) $item->quantity
                );
            }

            /** 10. Clear and unlock cart */
            $this->cartModel->clearItems($cart->id);
            $this->cartModel->unlock($cart->id);

            if ($startedTransaction && $this->db->inTransaction()) {
                $this->db->commit();
            }

            $data = [
                'order_id'     => $orderId,
                'order_number' => $orderNumber,
                'reference'    => $transactionReference,
                'final_amount' => $finalAmount,
                'status'       => 'paid',
            ];
            return (object) $data;
        } catch (Throwable $e) {
            if ($startedTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }


    /**
     * Get summarized order history for a user
     */
    public function getOrderHistorySummary(int $userId): array
    {

        // Fetch orders
        $orders = $this->orderModel->findByUser($userId);

        // Map each order to summary format
        foreach ($orders as &$order) {
            $productsCount =  $this->orderItemModel->getProductsCount($order['id']);

            $order = [
                'order_id'      => $order['id'],
                'order_number'  => $order['order_number'] ?? null,
                'status'        => $order['status'],
                'created_at'    => $order['created_at'],
                'total_amount'  => (float) ($order['final_amount'] ?? $order['total'] ?? 0),
                'products_count' => $productsCount
            ];
        }

        return $orders;
    }

    /**
     * Generate random 8-digit order number
     */
    private function generateOrderNumber(): string
    {
        $orderModel = new Order($this->db);
        $maxAttempts = 10;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $number = (string) random_int(10000000, 99999999);

            if (!$orderModel->findByOrderNumber($number)) {
                return $number;
            }
        }

        // Fallback: use timestamp-based
        return date('Ymd') . random_int(1000, 9999);
    }
}
