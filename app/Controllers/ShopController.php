<?php

namespace App\Controllers;

/**
 * Shop Controller
 * 
 * Handles product listing and filtering
 */
class ShopController extends BaseController
{
    /**
     * Show shop products
     */
    public function index()
    {
        $productModel = new \App\Models\Product($this->db);
        $categoryModel = new \App\Models\Category($this->db);

        $category = $this->request->input('category');
        $search = $this->request->input('search');
        $page = (int)$this->request->input('page', 1);

        if ($search) {
            $products = $productModel->search($search);
        } elseif ($category) {
            $cat = $categoryModel->findBy('slug', $category);
            $products = $cat ? $productModel->getByCategory($cat->id) : [];
        } else {
            $products = $productModel->getActive();
        }

        $paginator = new \Helpers\Paginator($products, $page);

        $this->response->view('shop.index', [
            'products' => $paginator->getItems(),
            'paginator' => $paginator,
            'categories' => $categoryModel->getActive(),
        ]);
    }
}
