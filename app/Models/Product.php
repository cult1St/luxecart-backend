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
}
