<?php

namespace App\Services;

use App\Models\Product;
use Helpers\Paginator;
use Helpers\Utility;
use InvalidArgumentException;

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
        $paginatedProducts = $this->productModel->paginate($page, $perPage);
        return [
            "products" => $paginatedProducts["data"],
            "meta" => $paginatedProducts['meta']
        ];
    }

    /**
     * Get single active product by ID
     * 
     * @throws \InvalidArgumentException if product not found or not active
     */
    public function getActiveProduct(int $id): ?object
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

    /**
     * Get admin paginated products
     * @return mixed
     */
    public function getAdminPaginatedProducts(int $page = 1, int $perPage = self::DEFAULT_PER_PAGE): array
    {
        return [
            'products' => $this->productModel->paginate($page, $perPage)
        ];
    }

    /**
     * Create a new product
     */
    public function createProduct(array $data): ?object
    {
        $utility = new Utility($this->db);
        $productName = $data['product_name'] ?? '';
        if (empty($productName)) {
            throw new InvalidArgumentException("Product name is required");
        }
        //generate slug for product
        $slug = $utility->generateSlug($productName);
        //check if slug already exists
        $existSlug = $this->productModel->findBy("slug", $slug);
        if ($existSlug) {
            $slug = $slug . "_" . time();
        }
        $request = [
            "name" => $productName,
            "slug" => $slug,
            "description" => $data['product_description'] ?? '',
            "price" => $data['product_price'] ?? 0,
            "stock_quantity" => $data['stock_quantity'] ?? 0,
            "images" => json_encode($data['product_images'] ?? []) ?? [],
        ];
        $create = $this->productModel->create($request);
        if ($create) {
            $product = $this->productModel->find($create);
            return $product;
        }
        return null;
    }

    public function updateProduct(int $id, array $data): ?object
    {
        $utility = new Utility($this->db);
        $product = $this->productModel->find($id);
        if (!$product) {
            throw new InvalidArgumentException('Product Not Found');
        }
        //check for deleted images
        $images = json_decode($product->images ?? []) ?? [];
        if(!empty($data['deleted_images'])){
            $images = array_filter($images, function($img) use ($data){
                return !in_array($img, $data['deleted_images']);
            });
        }
        $updateData = [
            "name" => $data['product_name'] ?? $product->name,
            "description" => $data['product_description'] ?? $product->description,
            'price'=> $data['product_price'] ?? $product->price,
            'stock_quantity' => $data['stock_quantity'] ?? $product->stock_quantity,
            'images' => json_encode(array_merge($images, $data['product_images'] ?? [])) ?? [],
        ];
        if($product->name !== ($data['product_name'] ?? $product->name)){
            //generate new slug
            $slug = $utility->generateSlug($data['product_name']);
            //check if slug already exists
            $existSlug = $this->productModel->findBy("slug", $slug);
            if ($existSlug && $existSlug['id'] != $id) {
                $slug = $slug . "_" . time();
            }
            $updateData['slug'] = $slug;
        }
        $this->productModel->update($id, $updateData);
        return $this->productModel->find($id);
    }

    public function deleteProduct($id): bool
    {
        $product = $this->productModel->find($id);
        if (!$product) {
            throw new InvalidArgumentException('Product Not Found');
        }
        return $this->productModel->delete($id);
    }

    public function getProduct(int $id): ?object
    {
        $product = $this->productModel->find($id);
        if (!$product) {
            throw new InvalidArgumentException('Product Not Found');
        }
        return $product;
    }

    public function getNextProductId(): int
    {
        $sql = "SELECT id FROM products ORDER BY id DESC";
        $result = $this->db->fetch($sql);
        return (int)$result["id"] + 1;
    }
}
