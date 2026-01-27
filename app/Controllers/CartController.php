<?php

namespace App\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;

class CartController extends BaseController
{

    /**
     * Get current cart
     */
    public function index(): void
    {
        try {
            // Resolve cart (guest via cookie)
            $cartModel = new Cart($this->db);
            $cart = $cartModel->resolveCart($this->request, $this->response);

           
            $cartItemModel = new CartItem($this->db);
            $items = $cartItemModel->getByCart($cart['id']);

            
            $summary = $cartModel->getSummary($cart['id']);

            $this->response->success([
                'cart_id' => $cart['id'],
                'items' => $items,
                'summary' => [
                    'subtotal' => $summary['subtotal'],
                    'discount' => (float) $cart['discount_amount'],
                    'total' => max(
                        $summary['subtotal'] - (float) $cart['discount_amount'],
                        0
                    )
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

            //  Resolve cart
            $cartModel = new Cart($this->db);
            $cart = $cartModel->resolveCart($this->request, $this->response);


            $cartItemModel = new CartItem($this->db);
            $cartItemModel->addOrIncrement(
                $cart['id'],
                $productId,
                $quantity,
                $product['price']
            );


            $this->response->success([
                'message' => 'Item added to cart',
                'product_name' => $product['name'],
                'quantity' => $quantity,
                'price' => $product['price']
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
     * Remove an item from cart
     */
    public function remove(): void
    {
        try {
            $cartId    = null;
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

            // Resolve cart
            $cartModel = new Cart($this->db);
            $cart = $cartModel->resolveCart($this->request, $this->response);
            $cartId = $cart['id'];

        
            $cartItemModel = new CartItem($this->db);
            $deleted = $cartItemModel->remove($cartId, $productId);

            if (!$deleted) {
                $this->response->error('Item not found in cart', [], 404);
                return;
            }

            $this->response->success([
                'message' => 'Item removed from cart',
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
     * update quantity of item in cart
     */
    public function updateQuantity(): void
    {
        try {
            $productId = (int) $this->request->input('product_id');
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

            // Resolve cart (guest)
            $cartModel = new Cart($this->db);
            $cart = $cartModel->resolveCart($this->request, $this->response);

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

            $summary = $cartModel->getSummary($cart['id']);

            $this->response->success([
                'message' => 'Cart updated',
                'cart' => $summary,
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
