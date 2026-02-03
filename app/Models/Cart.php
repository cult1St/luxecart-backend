<?php

namespace App\Models;

class Cart extends BaseModel
{
    protected string $table = 'carts';

    /**
     * Find cart by user ID
     */
    public function findByUserId(int $userId): ?object
    {
        return $this->findBy('user_id', $userId);
    }

    /**
     * Find cart by token
     */
    public function findByToken(string $token): ?object
    {
        return $this->findBy('token', $token);
    }

    /**
     * Create cart for authenticated user
     */
    public function createForUser(int $userId): array
    {
        $cartId = $this->db->insert($this->table, [
            'user_id'    => $userId,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return [
            'id'      => $cartId,
            'user_id' => $userId
        ];
    }

    /**
     * Create cart for guest using token
     */
    public function createForToken(string $token): array
    {
        $cartId = $this->db->insert($this->table, [
            'token'      => $token,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return [
            'id'    => $cartId,
            'token' => $token
        ];
    }

    /**
     * Cart summary (for UI)
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
            'subtotal'    => (float) $row['subtotal']
        ];
    }

 public function isLocked(int $cartId): bool
    {
        $row = $this->db->fetch(
            "SELECT is_locked FROM carts WHERE id = ? LIMIT 1",
            [$cartId]
        );

        return (bool) ($row['is_locked'] ?? false);
    }

    public function lock(int $cartId): void
    {
        $this->db->update(
            'carts',
            ['is_locked' => 1],
            'id = ' . (int) $cartId
        );
    }

    public function unlock(int $cartId): void
    {
        $this->db->update(
            'carts',
            ['is_locked' => 0],
            'id = ' . (int) $cartId
        );
    }

    public function clearItems(int $cartId): void
    {
        $this->db->delete(
            'cart_items',
            'cart_id = ' . (int) $cartId
        );
    }
}
