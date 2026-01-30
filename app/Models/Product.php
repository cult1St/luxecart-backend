<?php

namespace App\Models;

class Product extends BaseModel
{
    protected string $table = 'products';
    protected array $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'stock_quantity',
        'sold',
        'images',
        'is_active',
        'created_at',
        'updated_at',
    ];

    /**
     * Get all active products
     */
    public function getActive(): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY created_at DESC";
        return $this->db->fetchAll($sql);
    }

    /**
     * Get random active products, limit at most $limit
     */
    public function getRandomActive(int $limit = 4): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY RAND() LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Find active product by ID
     */
    public function findActive(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? AND is_active = 1";
        $result = $this->db->fetch($sql, [$id]);

        return $result === false ? null : $result;
    }

    /**
 * Decrement stock quantity and increment sold count
 * Returns true on success, false if insufficient stock
 */
public function decrementStock(int $productId, int $quantity): bool
{
    // Check current stock
    $product = $this->find($productId);
    
    if (!$product || $product['stock_quantity'] < $quantity) {
        return false;
    }

    $sql = "UPDATE {$this->table} 
            SET stock_quantity = stock_quantity - ?, 
                sold = sold + ?
            WHERE id = ? AND stock_quantity >= ?";
    
    $statement = $this->db->query($sql, [
        $quantity,
        $quantity,
        $productId,
        $quantity
    ]);

    return $statement->rowCount() > 0;
}
}
