<?php

namespace App\Controllers;

use App\Services\CartService;
use Core\Database;
use Core\Request;
use Core\Response;
use Helpers\ErrorResponse;
use InvalidArgumentException;
use Throwable;

class CartController extends BaseController
{
    protected CartService $cartService;

    public function __construct(Database $db, Request $request, Response $response)
    {
        parent::__construct($db, $request, $response);
        $this->cartService = new CartService($this->db);
    }

    /**
     * Resolve cart via service
     */
    protected function resolveCart(): array
    {
        return $this->cartService->resolveCart(
            $this->isAuthenticated(),
            $this->isAuthenticated() ? $this->getUserId() : null,
            $this->request->cookie('cart_token')
        );
    }

    /**
     * Get current cart
     */
    public function index(): void
    {
        try {
            $result = $this->resolveCart();
            $cart   = $result['cart'];

            $cartId = is_array($cart) ? $cart['id'] : $cart->id;
            $details = $this->cartService->getCartWithDetails($cartId);
            $discount = (float) ($cart->discount_amount ?? 0);

            $response = [
                'cart_id' => $cartId,
                'items'   => $details['items'],
                'summary' => [
                    'subtotal' => $details['summary']['subtotal'],
                    'discount' => $discount,
                    'total'    => max($details['summary']['subtotal'] - $discount, 0)
                ]
            ];

            if ($result['new_token']) {
                $response['cart_token'] = $result['new_token'];
            }

            $this->response->success($response);
        } catch (Throwable $e) {
            $this->response->error(
                'Failed to fetch cart',
                500,
                ['exception' => ErrorResponse::formatResponse($e)]
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
                $this->response->error('Product ID is required', 400);
                return;
            }

            $result = $this->resolveCart();
            $cart   = $result['cart'];
            $cartId = is_array($cart) ? $cart['id'] : $cart->id;

            $this->cartService->addItem(
                $cartId,
                $productId,
                $quantity
            );

            $response = [
                'message' => 'Item added to cart'
            ];

            if ($result['new_token']) {
                $response['cart_token'] = $result['new_token'];
            }

            $this->response->success($response);
        } catch (InvalidArgumentException $e) {
            $this->response->error($e->getMessage(), 404);
        } catch (Throwable $e) {
            $this->response->error(
                'Failed to add item to cart',
                500,
                ['exception' => ErrorResponse::formatResponse($e)]
            );
        }
    }

    /**
     * Remove item
     */
    public function remove(): void
    {
        try {
            $productId = (int) $this->request->input('product_id');

            if (!$productId) {
                $this->response->error('Product ID is required', 400);
                return;
            }

            $result = $this->resolveCart();
            $cart   = $result['cart'];

            $deleted = $this->cartService->removeItem($cart['id'], $productId);

            if (!$deleted) {
                $this->response->error('Item not found in cart', 404);
                return;
            }

            $response = ['message' => 'Item removed'];

            if ($result['new_token']) {
                $response['cart_token'] = $result['new_token'];
            }

            $this->response->success($response);
        } catch (Throwable $e) {
            $this->response->error(
                'Failed to remove item from cart',
                500,
                ['exception' => ErrorResponse::formatResponse($e)]
            );
        }
    }

    /**
     * Update quantity
     */
    public function updateQuantity(): void
    {
        try {
            $productId = (int) $this->request->input('product_id');
            $quantity  = (int) $this->request->input('quantity');

            if (!$productId) {
                $this->response->error('Product ID is required', 400);
                return;
            }

            if ($quantity < 0) {
                $this->response->error('Quantity cannot be less than 0', 400);
                return;
            }

            $result = $this->resolveCart();
            $cart   = $result['cart'];

            if ($quantity === 0) {
                $deleted = $this->cartService->removeItem($cart['id'], $productId);

                if (!$deleted) {
                    $this->response->error('Item not found in cart', 404);
                    return;
                }
            } else {
                $updated = $this->cartService->updateQuantity(
                    $cart['id'],
                    $productId,
                    $quantity
                );

                if (!$updated) {
                    $this->response->error('Item not found in cart', 404);
                    return;
                }
            }

            $summary = $this->cartService->getSummary($cart['id']);

            $response = [
                'message' => 'Cart updated',
                'cart'    => $summary
            ];

            if ($result['new_token']) {
                $response['cart_token'] = $result['new_token'];
            }

            $this->response->success($response);
        } catch (Throwable $e) {
            $this->response->error(
                'Failed to update cart',
                500,
                ['exception' => ErrorResponse::formatResponse($e)]
            );
        }
    }
}
