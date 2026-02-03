<?php

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\Cart;
use App\Models\Transaction;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\PaymentService;
use Core\Database;
use Core\Request;
use Core\Response;
use Exception;
use Helpers\ErrorResponse;
use Helpers\Utility;
use Throwable;

class PaymentController extends BaseController
{
    private PaymentService $paymentService;
    private Transaction $transactionModel;
    private CartService $cartService;
    private OrderService $orderService;
    private Cart $cartModel;
    private Utility $utility;

    public function __construct(Database $db, Request $request, Response $response)
    {
        parent::__construct($db, $request, $response);

        $this->paymentService   = new PaymentService($this->db);
        $this->cartService      = new CartService($this->db);
        $this->orderService = new OrderService($this->db);
        $this->transactionModel = new Transaction($this->db);
        $this->cartModel        = new Cart($this->db);
        $this->utility          = new Utility($this->db);
    }

    public function index()
    {
        $this->requireAuth();

        $userId = $this->getUserId();

        try {
            $this->db->beginTransaction();

            // Fetch user cart
            $cart = $this->cartModel->findByUserId($userId);
            if (!$cart) {
                throw new Exception('Cart not found');
            }

            // Get cart summary
            $cartSummary = $this->cartModel->getSummary($cart->id);
            $totalAmount = (float) $cartSummary['subtotal'];

            if ($totalAmount <= 0) {
                throw new Exception('Invalid cart amount');
            }

            //validate cart availability and lock 
            $this->cartService->validateCartAvailability($cart->id);


            // Generate transaction reference
            $reference = $this->utility->generateReference();

            // Create transaction record
            $this->transactionModel->createTransaction(
                $userId,
                $totalAmount,
                $reference,
                'paystack',
                'Transaction payment for order processing'
            );

            // Initialize payment
            $paymentResponse = $this->paymentService->initializePayment(
                $userId,
                $totalAmount,
                $reference
            );

            if (!$paymentResponse) {
                throw new Exception('Payment initialization failed');
            }

            $this->db->commit();

            return $this->response->success(
                $paymentResponse,
                'Payment initiated successfully'
            );
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return $this->response->error(
                ErrorResponse::formatResponse($e)
            );
        }
    }

    public function verify(?string $reference = null)
    {
        $this->requireAuth();

        if (empty($reference)) {
            $reference = $this->request->get('reference');
            if (empty($reference)) {
                return $this->response->error('Missing reference parameter');
            }
        }

        $userId = $this->getUserId();

        $this->db->beginTransaction();
        try {


            // Verify payment (throws if invalid or already processed)
            $payment = $this->paymentService
                ->verifyPayment($userId, $reference);

            // Fetch cart
            $cart = $this->cartModel->findByUserId($userId);
            if (!$cart) {
                throw new Exception('Cart not found');
            }

            ///handle the order service 
            $order = $this->orderService->createOrder($userId, $reference);

            if ($this->db->inTransaction()) {
                $this->db->commit();
            }

            return $this->response->success(
                $order,
                'Payment verified and Order Processed successfully'
            );
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }


            // release reservation
            try {
                $cart = $this->cartModel->findByUserId($userId);
                if ($cart) {
                    $this->cartService
                        ->releaseCartReservation($cart->id);
                }
            } catch (Throwable $ignored) {
                // log but do not mask original error
                $this->log($ignored->getMessage(), 'error');
            }

            return $this->response->error(
                ErrorResponse::formatResponse($e),
                500,
                [
                    'debug' => $e->getMessage(),
                ]
            );
        }
    }
}
