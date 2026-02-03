<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\ShippingInfo;

class CheckoutService
{
    protected $db;
    protected $cartModel;
    protected $shippingModel;

    /**
     * Available shipping methods and their prices
     */
    private const SHIPPING_METHODS = [
        'Lagos Mainland' => 4000,
        'Lagos Island'   => 2000,
    ];

    public function __construct($db)
    {
        $this->db = $db;
        $this->cartModel = new Cart($db);
        $this->shippingModel = new ShippingInfo($db);
    }

    /**
     * Get user's active cart
     * 
     * @throws \InvalidArgumentException if cart not found
     */
    public function getUserCart(int $userId): ?object
    {
        $cart = $this->cartModel->findByUserId($userId);

        if (!$cart) {
            throw new \InvalidArgumentException('No active cart found for user');
        }

        return $cart;
    }

    /**
     * Create shipping information
     * 
     * @throws \InvalidArgumentException if shipping info already exists
     */
    public function createShippingInfo(int $cartId, int $userId, array $data): array
    {
        // Check for existing shipping info
        if ($this->shippingModel->getByCart($cartId)) {
            throw new \InvalidArgumentException('Shipping info already exists for this cart');
        }

        // Validate required fields
        $requiredFields = [
            'first_name',
            'last_name',
            'address',
            'country_id',
            'state_id',
            'city_id',
            'zip_code',
            'email',
            'phone_number',
        ];

        $shippingData = [];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new \InvalidArgumentException("Field {$field} is required");
            }

            $shippingData[$field] = $data[$field];
        }

        // Optional fields
        $shippingData['company_name'] = $data['company_name'] ?? null;
        $shippingData['notes']        = $data['notes'] ?? null;

        // System fields
        $shippingData['cart_id'] = $cartId;
        $shippingData['user_id'] = $userId;

        $this->shippingModel->create($shippingData);

        return $shippingData;
    }

    /**
     * Get shipping information by cart ID
     */
    public function getShippingInfo(int $cartId): ?array
    {
        return $this->shippingModel->getByCart($cartId);
    }

    /**
     * Update shipping information
     * 
     * @throws \InvalidArgumentException if shipping info not found or invalid data
     */
    public function updateShippingInfo(int $cartId, array $data): array
    {
        $existing = $this->shippingModel->getByCart($cartId);

        if (!$existing) {
            throw new \InvalidArgumentException('Shipping info not found for this cart');
        }

        $validColumns = $this->shippingModel->getColumns();
        $updateData   = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $validColumns, true)) {
                $updateData[$key] = $value;
            }
        }

        // Map shipping method → amount
        if (isset($updateData['shipping_method'])) {
            $method = $updateData['shipping_method'];

            if (!isset(self::SHIPPING_METHODS[$method])) {
                throw new \InvalidArgumentException('Invalid shipping method');
            }

            $updateData['shipping_amount'] = self::SHIPPING_METHODS[$method];
        }

        if (empty($updateData)) {
            throw new \InvalidArgumentException('No valid fields provided to update');
        }

        $this->shippingModel->updateByCart($cartId, $updateData);

        return $updateData;
    }

    /**
     * Get available shipping methods
     */
    public function getShippingMethods(): array
    {
        return self::SHIPPING_METHODS;
    }
}