<?php

namespace App\Models;

class OrderItem extends BaseModel
{
    protected string $table = 'order_items';
    protected array $fillable = [
        'order_id',          
        'product_id',              
        'quantity', 
        'price',               
        'subtotal'
    ];

    public function insertMany(array $items): void
    {
        foreach ($items as $item) {
            $this->db->insert($this->table, $item);
        }
    }

    /**
     * Count total products in an order
     */
    public function getProductsCount(int $orderId): int
    {
        $row = $this->db->fetch(
            "SELECT COALESCE(SUM(quantity), 0) AS products_count FROM order_items WHERE order_id = ?",
            [$orderId]
        );

        return (int) ($row['products_count'] ?? 0);
    }
}
