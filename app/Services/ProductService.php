<?php

namespace App\Services;

use App\Models\Product;
use Helpers\Paginator;

class ProductService
{
    protected $db;
    protected $productModel;

    private const DEFAULT_PER_PAGE = 15;
    private const DEFAULT_RELATED_COUNT = 4;

    public function __construct($db)
    {
        $this->db = $db;
        $this->productModel = new Product($db);
    }

    /**
     * Get paginated active products
     */
    public function getPaginatedProducts(int $page = 1, int $perPage = self::DEFAULT_PER_PAGE): array
    {
        $products = $this->productModel->getActive();

        $paginator = new Paginator($products, $page, $perPage);

        return [
            'products' => $paginator->getItems(),
            'pagination' => [
                'current_page' => $paginator->getCurrentPage(),
                'per_page' => $perPage,
                'total_pages' => $paginator->getTotalPages(),
                'total_items' => $paginator->getTotal(),
                'has_next' => $paginator->hasNextPage(),
                'has_previous' => $paginator->hasPreviousPage(),
                'next_page' => $paginator->getNextPage(),
                'previous_page' => $paginator->getPreviousPage(),
            ]
        ];
    }

    /**
     * Get single active product by ID
     * 
     * @throws \InvalidArgumentException if product not found or not active
     */
    public function getActiveProduct(int $id): array
    {
        $product = $this->productModel->find($id);

        if (!$product) {
            throw new \InvalidArgumentException('Product not found');
        }

        if ((int)$product['is_active'] !== 1) {
            throw new \InvalidArgumentException('Product not available');
        }

        return $product;
    }

    /**
     * Get random related products
     */
    public function getRelatedProducts(int $count = self::DEFAULT_RELATED_COUNT): array
    {
        return $this->productModel->getRandomActive($count);
    }
}