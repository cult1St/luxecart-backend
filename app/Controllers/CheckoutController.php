<?php

namespace App\Controllers;

use App\Models\Cart;
use App\Models\ShippingInfo;

class CheckoutController extends BaseController
{
    /**
     * Available shipping methods and their prices
     */
    private array $shippingMethods = [
        'Lagos Mainland' => 4000,
        'Lagos Island'   => 2000,
    ];

    /**
     * Resolve authenticated user's cart
     * 
     */
    protected function resolveUserCart(): array
    {
        $this->requireAuth();

        $userId = $this->getUserId();

        $cartModel = new Cart($this->db);
        $cart = $cartModel->findByUserId($userId);

        if (!$cart) {
            $this->response->error(
                'No active cart found for user',
                [],
                404
            );
            exit;
        }

        return $cart;
    }

    /**
     * Save shipping information (create only)
     */
    public function saveShippingInfo(): void
    {
        try {
            $cart = $this->resolveUserCart();
            $cartId = $cart['id'];

            $shippingModel = new ShippingInfo($this->db);

            // Prevent duplicate shipping info
            if ($shippingModel->getByCart($cartId)) {
                $this->response->error(
                    'Shipping info already exists for this cart',
                    [],
                    409
                );
                return;
            }

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

            $data = [];

            foreach ($requiredFields as $field) {
                $value = $this->request->input($field);

                if ($value === null || $value === '') {
                    $this->response->error(
                        "Field {$field} is required",
                        [],
                        400
                    );
                    return;
                }

                $data[$field] = $value;
            }

            // Optional fields
            $data['company_name'] = $this->request->input('company_name');
            $data['notes']        = $this->request->input('notes');

            // System fields
            $data['cart_id'] = $cartId;
            $data['user_id'] = $this->getUserId();

            $shippingModel->create($data);

            $this->response->success([
                'message'       => 'Shipping info saved',
                'shipping_info' => $data,
            ]);
        } catch (\Throwable $e) {
            $this->response->error(
                'Failed to save shipping info',
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Get shipping information for authenticated user's cart
     */
    public function getShippingInfo(): void
    {
        try {
            $cart = $this->resolveUserCart();

            $shippingModel = new ShippingInfo($this->db);
            $shipping = $shippingModel->getByCart($cart['id']);

            $this->response->success([
                'message'  => $shipping ? 'Shipping info retrieved' : 'No shipping info found',
                'shipping' => $shipping ?: null,
            ]);
        } catch (\Throwable $e) {
            $this->response->error(
                'Failed to fetch shipping info',
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Update existing shipping information (partial updates allowed)
     */
    public function updateShippingInfo(): void
    {
        try {
            $cart = $this->resolveUserCart();
            $cartId = $cart['id'];

            $shippingModel = new ShippingInfo($this->db);

            $existing = $shippingModel->getByCart($cartId);

            if (!$existing) {
                $this->response->error(
                    'Shipping info not found for this cart',
                    [],
                    404
                );
                return;
            }

            $validColumns = $shippingModel->getColumns();
            $updateData   = [];

            foreach ($this->request->all() as $key => $value) {
                if (in_array($key, $validColumns, true)) {
                    $updateData[$key] = $value;
                }
            }

            // Map shipping method → amount
            if (isset($updateData['shipping_method'])) {
                $method = $updateData['shipping_method'];

                if (!isset($this->shippingMethods[$method])) {
                    $this->response->error(
                        'Invalid shipping method',
                        [],
                        400
                    );
                    return;
                }

                $updateData['shipping_amount'] = $this->shippingMethods[$method];
            }

            if (empty($updateData)) {
                $this->response->error(
                    'No valid fields provided to update',
                    [],
                    400
                );
                return;
            }

            $shippingModel->updateByCart($cartId, $updateData);

            $this->response->success([
                'message'  => 'Shipping info updated',
                'shipping' => $updateData,
            ]);
        } catch (\Throwable $e) {
            $this->response->error(
                'Failed to update shipping info',
                ['exception' => $e->getMessage()],
                500
            );
        }
    }
}
