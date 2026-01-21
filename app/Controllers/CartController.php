<?php

namespace App\Controllers;

/**
 * Cart Controller
 * 
 * Handles shopping cart operations
 */
class CartController extends BaseController
{
    /**
     * Show cart
     */
    public function index()
    {
        $cart = $_SESSION['cart'] ?? [];
        $productModel = new \App\Models\Product($this->db);

        $items = [];
        $total = 0;

        foreach ($cart as $productId => $quantity) {
            if ($product = $productModel->find($productId)) {
                $items[] = array_merge($product, ['cart_quantity' => $quantity]);
                $total += $product['price'] * $quantity;
            }
        }

        $this->response->view('cart.index', [
            'items' => $items,
            'total' => $total,
        ]);
    }

    /**
     * Add to cart
     */
    public function add()
    {
        if (!$this->request->isPost()) {
            $this->response->error('Invalid request', [], 400);
        }

        $productId = (int)$this->request->input('product_id');
        $quantity = (int)$this->request->input('quantity', 1);

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] += $quantity;
        } else {
            $_SESSION['cart'][$productId] = $quantity;
        }

        $this->response->success(['cart_count' => count($_SESSION['cart'])], 'Product added to cart');
    }

    /**
     * Update cart item
     */
    public function update()
    {
        if (!$this->request->isPost()) {
            $this->response->error('Invalid request', [], 400);
        }

        $productId = (int)$this->request->input('product_id');
        $quantity = (int)$this->request->input('quantity');

        if ($quantity <= 0) {
            unset($_SESSION['cart'][$productId]);
        } else {
            $_SESSION['cart'][$productId] = $quantity;
        }

        $this->response->success();
    }

    /**
     * Remove from cart
     */
    public function remove()
    {
        if (!$this->request->isPost()) {
            $this->response->error('Invalid request', [], 400);
        }

        $productId = (int)$this->request->input('product_id');
        unset($_SESSION['cart'][$productId]);

        $this->response->success(['cart_count' => count($_SESSION['cart'] ?? [])]);
    }

    /**
     * Clear cart
     */
    public function clear()
    {
        if (!$this->request->isPost()) {
            $this->response->error('Invalid request', [], 400);
        }

        $_SESSION['cart'] = [];
        $this->response->success();
    }
}
