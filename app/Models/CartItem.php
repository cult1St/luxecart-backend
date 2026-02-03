<?php

namespace App\Models;

class CartItem extends BaseModel
{
    protected string $table = 'cart_items';

    /**
     * Get all items for a cart
     */
    public function getByCart(int $cartId): array
    {
        $results = $this->db->fetchAll(
            "SELECT
                ci.product_id,
                p.name AS product_name,
                p.price,
                ci.quantity,
                (ci.quantity * p.price) AS subtotal
             FROM cart_items ci
             JOIN products p ON p.id = ci.product_id
             WHERE ci.cart_id = ?",
            [$cartId]
        );
        return $this->useObjects ? $this->toObjectArray($results) : $results;
    }

    /**
     * Add item to cart or increment quantity
     */
    public function addOrIncrement(int $cartId, int $productId, int $quantity, float $price): void
    {
        $existing = $this->db->fetch(
            "SELECT id, quantity FROM {$this->table}
             WHERE cart_id = ? AND product_id = ?",
            [$cartId, $productId]
        );

        if ($existing) {
            $this->db->update(
                $this->table,
                ['quantity' => $existing['quantity'] + $quantity],
                "id = {$existing['id']}"
            );
            return;
        }

        $this->db->insert($this->table, [
            'cart_id' => $cartId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'price' => $price,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Remove item from cart
     *
     * Returns true if deleted, false if not found
     */
    public function remove(int $cartId, int $productId): bool
    {
        $existing = $this->db->fetch(
            "SELECT id FROM {$this->table} WHERE cart_id = ? AND product_id = ?",
            [$cartId, $productId]
        );

        if (!$existing) {
            return false;
        }

        $this->db->delete($this->table, "id = {$existing['id']}");
        return true;
    }

    public function setQuantity(int $cartId, int $productId, int $quantity): int
    {
        return $this->db->update(
            $this->table,
            ['quantity' => $quantity],
            "cart_id = {$cartId} AND product_id = {$productId}"
        );
    }

    public function getItemsForAvailabilityCheck(int $cartId): array
    {
        $results = $this->db->fetchAll(
            "SELECT product_id, quantity
         FROM {$this->table}
         WHERE cart_id = ?",
            [$cartId]
        );
        return $this->useObjects ? $this->toObjectArray($results) : $results;
    }
}
