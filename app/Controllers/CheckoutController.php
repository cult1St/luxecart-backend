<?php

namespace App\Controllers;

use App\Services\CheckoutService;
use Core\Database;
use Core\Request;
use Core\Response;
use InvalidArgumentException;
use Throwable;

class CheckoutController extends BaseController
{
    protected CheckoutService $checkoutService;

    public function __construct(Database $db, Request $request, Response $response)
    {
        parent::__construct($db, $request, $response);
        $this->checkoutService = new CheckoutService($this->db);
    }

    /**
     * Resolve authenticated user's cart
     */
    protected function resolveUserCart(): object
    {
        $this->requireAuth();

        try {
            return $this->checkoutService->getUserCart($this->getUserId());
        } catch (InvalidArgumentException $e) {
            $this->response->error($e->getMessage(), 404);
            exit;
        }
    }

    /**
     * Save shipping information (create only)
     */
    public function saveShippingInfo(): void
    {
        try {
            $cart = $this->resolveUserCart();

            $shippingData = $this->checkoutService->createShippingInfo(
                $cart->id,
                $this->getUserId(),
                $this->request->all()
            );

            $this->response->success([
                'message'       => 'Shipping info saved',
                'shipping_info' => $shippingData,
            ]);
        } catch (InvalidArgumentException $e) {
            $statusCode = str_contains($e->getMessage(), 'already exists') ? 409 : 400;
            $this->response->error($e->getMessage(), $statusCode);
        } catch (Throwable $e) {
            $this->response->error(
                'Failed to save shipping info',
                500,
                ['exception' => $e->getMessage()]
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

            $shipping = $this->checkoutService->getShippingInfo($cart->id);

            $this->response->success([
                'message'  => $shipping ? 'Shipping info retrieved' : 'No shipping info found',
                'shipping' => $shipping ?: null,
            ]);
        } catch (Throwable $e) {
            $this->response->error(
                'Failed to fetch shipping info',
                500,
                ['exception' => $e->getMessage()]
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

            $updateData = $this->checkoutService->updateShippingInfo(
                $cart->id,
                $this->request->all()
            );

            $this->response->success([
                'message'  => 'Shipping info updated',
                'shipping' => $updateData,
            ]);
        } catch (InvalidArgumentException $e) {
            $statusCode = str_contains($e->getMessage(), 'not found') ? 404 : 400;
            $this->response->error($e->getMessage(), $statusCode);
        } catch (Throwable $e) {
            $this->response->error(
                'Failed to update shipping info',
                500,
                ['exception' => $e->getMessage()]
            );
        }
    }
}