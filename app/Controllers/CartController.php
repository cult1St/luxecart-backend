<?php

namespace App\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;

class CartController extends BaseController
{
    /**
     * Resolve cart based on auth state
     * - Authenticated: resolve by user_id
     * - Guest: resolve by cart_token cookie
     */
    protected function resolveCart(): array
    {
        $cartModel = new Cart($this->db);

        /**
         * AUTHENTICATED USER
         */
        if ($this->isAuthenticated()) {
            $userId = $this->getUserId();

            $cart = $cartModel->findByUserId($userId);

            if ($cart) {
                return $cart;
            }

            // Create cart for user if none exists
            return $cartModel->createForUser($userId);
        }

        /**
         * GUEST USER (COOKIE TOKEN)
         */
        $token = $this->request->cookie('cart_token');

        if ($token) {
            $cart = $cartModel->findByToken($token);

            if ($cart) {
                return $cart;
            }
        }

        // No valid cart → create new guest cart
        $token = bin2hex(random_bytes(32));

        $cart = $cartModel->createForToken($token);

        // Persist token in cookie
        $this->response->cookie(
            'cart_token',
            $token,
            time() + (60 * 60 * 24 * 30) // 30 days
        );

        return $cart;
    }

    /**
     * Get current cart
     */
    public function index(): void
    {
        try {
            $cart = $this->resolveCart();

            $cartItemModel = new CartItem($this->db);
            $items = $cartItemModel->getByCart($cart['id']);

            $cartModel = new Cart($this->db);
            $summary = $cartModel->getSummary($cart['id']);

            $discount = (float) ($cart['discount_amount'] ?? 0);

            $this->response->success([
                'cart_id' => $cart['id'],
                'items'   => $items,
                'summary' => [
                    'subtotal' => $summary['subtotal'],
                    'discount' => $discount,
                    'total'    => max($summary['subtotal'] - $discount, 0)
                ]
            ]);
        } catch (\Throwable $e) {
            $this->response->error(
                'Failed to fetch cart',
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Add item to cart
     */
    public function add(): void
    {
        try {
            $productId = (int) $this->request->input('product_id');
            $quantity  = (int) $this->request->input('quantity', 1);

            if (!$productId) {
                $this->response->error('Product ID is required', [], 400);
                return;
            }

            $productModel = new Product($this->db);
            $product = $productModel->findActive($productId);

            if (!$product) {
                $this->response->error('Product not found or unavailable', [], 404);
                return;
            }

            $cart = $this->resolveCart();

            $cartItemModel = new CartItem($this->db);
            $cartItemModel->addOrIncrement(
                $cart['id'],
                $productId,
                $quantity,
                $product['price']
            );

            $this->response->success([
                'message'      => 'Item added to cart',
                'product_name' => $product['name'],
                'quantity'     => $quantity,
                'price'        => $product['price']
            ]);
        } catch (\Throwable $e) {
            $this->response->error(
                'Failed to add item to cart',
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Remove item from cart
     */
    public function remove(): void
    {
        try {
            $productId = (int) $this->request->input('product_id');

            if (!$productId) {
                $this->response->error('Product ID is required', [], 400);
                return;
            }

            $productModel = new Product($this->db);
            $product = $productModel->findActive($productId);

            if (!$product) {
                $this->response->error('Product not found or unavailable', [], 404);
                return;
            }

            $cart = $this->resolveCart();

            $cartItemModel = new CartItem($this->db);
            $deleted = $cartItemModel->remove($cart['id'], $productId);

            if (!$deleted) {
                $this->response->error('Item not found in cart', [], 404);
                return;
            }

            $this->response->success([
                'message'    => 'Item removed from cart',
                'product_id' => $productId
            ]);
        } catch (\Throwable $e) {
            $this->response->error(
                'Failed to remove item from cart',
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Update cart item quantity
     */
    public function updateQuantity(): void
    {
        try {
            $productId   = (int) $this->request->input('product_id');
            $rawQuantity = $this->request->input('quantity');

            if (!$productId) {
                $this->response->error('Product ID is required', [], 400);
                return;
            }

            if ($rawQuantity === null) {
                $this->response->error('Quantity is required', [], 400);
                return;
            }

            $quantity = (int) $rawQuantity;

            if ($quantity < 0) {
                $this->response->error('Quantity cannot be less than 0', [], 400);
                return;
            }

            $productModel = new Product($this->db);
            $product = $productModel->findActive($productId);

            if (!$product) {
                $this->response->error('Product not found or unavailable', [], 404);
                return;
            }

            $cart = $this->resolveCart();

            $cartItemModel = new CartItem($this->db);

            if ($quantity === 0) {
                $deleted = $cartItemModel->remove($cart['id'], $productId);

                if (!$deleted) {
                    $this->response->error('Product not found in cart', [], 404);
                    return;
                }
            } else {
                $affected = $cartItemModel->setQuantity(
                    $cart['id'],
                    $productId,
                    $quantity
                );

                if ($affected === 0) {
                    $this->response->error('Product not found in cart', [], 404);
                    return;
                }
            }

            $cartModel = new Cart($this->db);
            $summary = $cartModel->getSummary($cart['id']);

            $this->response->success([
                'message' => 'Cart updated',
                'cart'    => $summary
            ]);
        } catch (\Throwable $e) {
            $this->response->error(
                'Failed to update cart',
                ['exception' => $e->getMessage()],
                500
            );
        }
    }
}
