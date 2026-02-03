<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Exception;

class CartService
{
    protected $db;
    protected $cartModel;
    protected $cartItemModel;
    protected $productModel;

    private const TOKEN_BYTES = 32;

    public function __construct($db)
    {
        $this->db = $db;
        $this->cartModel = new Cart($db);
        $this->cartItemModel = new CartItem($db);
        $this->productModel = new Product($db);
    }

    /**
     * Resolve cart for current request
     *
     * @param bool $isAuthenticated
     * @param int|null $userId
     * @param string|null $cartToken
     *
     * @return array [
     *   'cart' => array,
     *   'new_token' => string|null
     * ]
     */
    public function resolveCart(
        bool $isAuthenticated,
        ?int $userId,
        ?string $cartToken
    ): array {
        /**
         * AUTHENTICATED USER
         */
        if ($isAuthenticated) {
            $cart = $this->cartModel->findByUserId($userId);

            if ($cart) {
                return [
                    'cart' => $cart,
                    'new_token' => null
                ];
            }

            return [
                'cart' => $this->cartModel->createForUser($userId),
                'new_token' => null
            ];
        }

        /**
         * GUEST USER
         */
        if ($cartToken) {
            $cart = $this->cartModel->findByToken($cartToken);

            if ($cart) {
                return [
                    'cart' => $cart,
                    'new_token' => null
                ];
            }
        }

        /**
         * CREATE NEW GUEST CART
         */
        $token = bin2hex(random_bytes(self::TOKEN_BYTES));
        $cart  = $this->cartModel->createForToken($token);

        return [
            'cart' => $cart,
            'new_token' => $token
        ];
    }

    /**
     * Add or increment item
     * 
     * @throws \InvalidArgumentException if product not found or unavailable
     */
    public function addItem(
        int $cartId,
        int $productId,
        int $quantity
    ): void {
        $product = $this->productModel->findActive($productId);

        if (!$product) {
            throw new \InvalidArgumentException('Product not found or unavailable');
        }

        $this->cartItemModel->addOrIncrement(
            $cartId,
            $productId,
            $quantity,
            $product['price']
        );
    }

    /**
     * Remove item
     */
    public function removeItem(int $cartId, int $productId): bool
    {
        return $this->cartItemModel->remove($cartId, $productId);
    }

    /**
     * Update quantity
     */
    public function updateQuantity(
        int $cartId,
        int $productId,
        int $quantity
    ): bool {
        return $this->cartItemModel->setQuantity(
            $cartId,
            $productId,
            $quantity
        );
    }

    /**
     * Get cart items
     */
    public function getItems(int $cartId): array
    {
        return $this->cartItemModel->getByCart(cartId: $cartId);
    }

    /**
     * Get cart summary
     */
    public function getSummary(int $cartId): array
    {
        return $this->cartModel->getSummary($cartId);
    }

    /**
     * Get cart with full details (items + summary)
     */
    public function getCartWithDetails(int $cartId): array
    {
        return [
            'items' => $this->getItems($cartId),
            'summary' => $this->getSummary($cartId)
        ];
    }

    /**
     * validate if any cart item is not available
     */
    public function validateCartAvailability(int $cartId): void
    {
        $cartItems = $this->cartItemModel
            ->getItemsForAvailabilityCheck($cartId);

        foreach ($cartItems as $item) {
            // Lock product row
            $product = $this->productModel
                ->lockForUpdate((int) $item->product_id);

            if (!$product) {
                throw new Exception('Product does not exist');
            }

            $availableQuantity =
                (int) $product->stock_quantity
                - (int) $product->reserved_quantity;

            if ($availableQuantity < (int) $item->quantity) {
                throw new Exception(
                    "{$product->name} is no longer available in the requested quantity"
                );
            }

            // Reserve stock (still inside same DB transaction)
            $this->productModel->reserveStock(
                (int) $product->id,
                (int) $item->quantity
            );
        }
    }

    public function releaseCartReservation(int $cartId): void
    {
        $cartItems = $this->cartItemModel
            ->getItemsForAvailabilityCheck($cartId);

        foreach ($cartItems as $item) {
            $this->productModel->releaseReservedStock(
                (int) $item->product_id,
                (int) $item->quantity
            );
        }
    }
}
