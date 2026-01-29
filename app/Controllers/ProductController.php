<?php

namespace App\Controllers;

use App\Services\ProductService;

class ProductController extends BaseController
{
    protected ProductService $productService;

    public function __construct($db, $request, $response)
    {
        parent::__construct($db, $request, $response);
        $this->productService = new ProductService($this->db);
    }

    /**
     * Get all products with pagination
     */
    public function index(): void
    {
        try {
            $page = (int)$this->request->input('page', 1);
            $perPage = (int)$this->request->input('per_page', 15);

            $result = $this->productService->getPaginatedProducts($page, $perPage);

            $this->response->success($result);
        } catch (\Throwable $e) {
            $this->response->error(
                'Failed to fetch products',
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Get single active product
     */
    public function show($id): void
    {
        try {
            $id = (int)$id;
            $product = $this->productService->getActiveProduct($id);

            $this->response->success(['product' => $product]);
        } catch (\InvalidArgumentException $e) {
            $this->response->error($e->getMessage(), [], 404);
        } catch (\Throwable $e) {
            $this->response->error(
                'Failed to fetch product',
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Get related products
     */
    public function related(): void
    {
        try {
            $products = $this->productService->getRelatedProducts();

            $this->response->success([
                'products' => $products,
            ]);
        } catch (\Throwable $e) {
            $this->response->error(
                'Failed to fetch related products',
                ['exception' => $e->getMessage()],
                500
            );
        }
    }
}