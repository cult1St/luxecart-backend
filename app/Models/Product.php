<?php

namespace App\Models;

/**
 * Product Model
 * 
 * Manages product data and operations
 */
class Product extends BaseModel
{
    protected string $table = 'products';
    protected array $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'category_id',
        'price',
        'cost_price',
        'stock_quantity',
        'sku',
        'image_url',
        'is_active',
        'created_at',
        'updated_at',
    ];

    /**
     * Get product with category
     */
    public function getWithCategory(int $id): ?array
    {
        $sql = "SELECT p.*, c.name as category_name 
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id = ?";
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Get all active products
     */
    public function getActive(): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY created_at DESC";
        return $this->db->fetchAll($sql);
    }

    /**
     * Get products by category
     */
    public function getByCategory(int $categoryId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE category_id = ? AND is_active = 1 ORDER BY name ASC";
        return $this->db->fetchAll($sql, [$categoryId]);
    }

    /**
     * Get low stock products
     */
    public function getLowStock(int $threshold = 10): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE stock_quantity <= ? ORDER BY stock_quantity ASC";
        return $this->db->fetchAll($sql, [$threshold]);
    }

    /**
     * Search products
     */
    public function search(string $query): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE (name LIKE ? OR description LIKE ? OR sku = ?) 
                AND is_active = 1
                ORDER BY name ASC";
        $searchTerm = "%{$query}%";
        return $this->db->fetchAll($sql, [$searchTerm, $searchTerm, $query]);
    }

    /**
     * Get product reviews
     */
    public function getReviews(int $productId): array
    {
        $sql = "SELECT r.*, u.name as customer_name 
                FROM reviews r
                LEFT JOIN customers u ON r.customer_id = u.id
                WHERE r.product_id = ? AND r.is_approved = 1
                ORDER BY r.created_at DESC";
        return $this->db->fetchAll($sql, [$productId]);
    }

    /**
     * Get average rating
     */
    public function getAverageRating(int $productId): float
    {
        $sql = "SELECT AVG(rating) as average FROM reviews WHERE product_id = ? AND is_approved = 1";
        $result = $this->db->fetch($sql, [$productId]);
        return $result['average'] ?? 0;
    }

    /**
     * Update stock
     */
    public function updateStock(int $productId, int $quantity): int
    {
        $sql = "UPDATE {$this->table} SET stock_quantity = stock_quantity + ? WHERE id = ?";
        return $this->db->query($sql, [$quantity, $productId])->rowCount();
    }
}
