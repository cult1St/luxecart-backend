<?php

namespace App\Models;

/**
 * Category Model
 * 
 * Manages product categories
 */
class Category extends BaseModel
{
    protected string $table = 'categories';
    protected array $fillable = [
        'name',
        'slug',
        'description',
        'image_url',
        'is_active',
        'display_order',
        'created_at',
        'updated_at',
    ];

    /**
     * Get all active categories
     */
    public function getActive(): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY display_order ASC";
        return $this->db->fetchAll($sql);
    }

    /**
     * Get category with product count
     */
    public function getWithProductCount(int $id): ?array
    {
        $sql = "SELECT c.*, COUNT(p.id) as product_count 
                FROM {$this->table} c
                LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
                WHERE c.id = ?
                GROUP BY c.id";
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Get by slug
     */
    public function getBySlug(string $slug): ?array
    {
        return $this->findBy('slug', $slug);
    }
}
