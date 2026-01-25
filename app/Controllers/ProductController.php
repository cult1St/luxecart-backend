<?php

namespace App\Controllers;

use App\Models\Product;
use Helpers\Paginator;

class ProductController extends BaseController
{
    protected Product $productModel;

    public function __construct($db, $request, $response)
    {
        parent::__construct($db, $request, $response);
        $this->productModel = new Product($this->db);
    }

    /**
     * Get all products with pagination
     */
    public function index(): void
    {
        $page = (int)$this->request->input('page', 1);
        $perPage = (int)$this->request->input('per_page', 15);

        try {
            $products = $this->productModel->getActive();

            $paginator = new Paginator($products, $page, $perPage);

            $this->response->success([
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
            ]);
        } catch (\Throwable $e) {
            $this->response->error('Failed to fetch products', ['exception' => $e->getMessage()], 500);
        }
    }

    /**
     * Get single active product
     */
    public function show($id): void
    {
        try {
            $id = (int)$id;
            $product = $this->productModel->find($id);

            if (!$product) {
                $this->response->error('Product not found', [], 404);
            }

            if ((int)$product['is_active'] !== 1) {
                $this->response->error('Product not available', [], 404);
            }

            $this->response->success(['product' => $product]);
        } catch (\Throwable $e) {
            $this->response->error('Failed to fetch product', ['exception' => $e->getMessage()], 500);
        }
    }

    /**
     * Get related products
     */
    public function related(): void
    {
        try {
            $products = $this->productModel->getRandomActive(4);

            $this->response->success([
                'products' => $products,
            ]);
        } catch (\Throwable $e) {
            $this->response->error('Failed to fetch related products', ['exception' => $e->getMessage()], 500);
        }
    }
}
