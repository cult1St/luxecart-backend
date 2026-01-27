<?php

namespace App\Models;

class Cart extends BaseModel
{
    protected string $table = 'carts';

    /**
     * Get or create cart using cookie token
     */
    public function resolveCart($request, $response): array
    {
        $token = $request->cookie('cart_token');

        if ($token) {
            $cart = $this->findByToken($token);
            if ($cart) {
                return $cart;
            }
        }

        // create new cart
        $token = bin2hex(random_bytes(32));

       $cartId = $this->db->insert($this->table, [
            'token' => $token,
            'created_at' => date('Y-m-d H:i:s')
        ]);


        $response->cookie(
            'cart_token',
            $token,
            time() + (60 * 60 * 24 * 30) // 30 days
        );

        return [
            'id' => $cartId,
            'token' => $token
        ];
    }

    public function findByToken(string $token): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE token = ? LIMIT 1",
            [$token]
        ) ?: null;
    }

    /**
     * Cart summary (for modal & UI)
     */
    public function getSummary(int $cartId): array
    {
        $row = $this->db->fetch(
            "SELECT 
                COUNT(ci.id) AS items_count,
                COALESCE(SUM(ci.quantity * p.price), 0) AS subtotal
             FROM cart_items ci
             JOIN products p ON p.id = ci.product_id
             WHERE ci.cart_id = ?",
            [$cartId]
        );

        return [
            'items_count' => (int) $row['items_count'],
            'subtotal' => (float) $row['subtotal']
        ];
    }
}
