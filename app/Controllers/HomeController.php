<?php

namespace App\Controllers;

/**
 * Home Controller
 * 
 * Handles home page and landing page
 */
class HomeController extends BaseController
{
    /**
     * Show home page
     */
    public function index()
    {
        $productModel = new \App\Models\Product($this->db);
        $categoryModel = new \App\Models\Category($this->db);

        $featuredProducts = $productModel->getActive();
        $categories = $categoryModel->getActive();

        $this->response->view('home', [
            'featuredProducts' => array_slice($featuredProducts, 0, 8),
            'categories' => $categories,
        ]);
    }

    public function testTokenGeneration(){
        return $this->response->success(['HELLO']);
    }
}
