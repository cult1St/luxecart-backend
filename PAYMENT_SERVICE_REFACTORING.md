# Payment Service Refactoring - Architecture & Best Practices

## Overview
The PaymentService has been refactored to follow **SOLID** and **DRY** principles with a clean, thin architecture.

---

## Architecture Changes

### 1. **Separation of Concerns**
```
PaymentService (Thin orchestrator)
    ↓
PaymentRepository (Data persistence)
PaymentGatewayFactory (Gateway selection)
PaymentGatewayInterface (Abstraction)
```

### 2. **Removed Responsibilities**
- ❌ Transaction creation (move to OrderService)
- ❌ Direct database operations (delegated to PaymentRepository)
- ❌ Gateway-specific logic (abstracted via PaymentGatewayInterface)
- ❌ Hardcoded payment methods (factory pattern)

### 3. **New Structure**

#### **PaymentGatewayInterface** (Contracts)
Defines contract for all payment gateways
- `initializeTransaction(array $data): array`
- `verifyTransaction(string $reference): array`
- `getName(): string`

**Benefit**: Add Stripe, Flutterwave, etc. without modifying PaymentService

#### **PaystackGateway** (Adapter Pattern)
Implements PaymentGatewayInterface for Paystack
- Wraps PaystackService
- Provides consistent interface

**Benefit**: Loose coupling to payment gateways

#### **PaymentGatewayFactory** (Factory Pattern)
Creates gateway instances based on method
```php
$gateway = PaymentGatewayFactory::make('paystack');
$gateway = PaymentGatewayFactory::make('stripe');
```

**Benefit**: Centralized gateway management, easy to extend

#### **PaymentRepository** (Repository Pattern)
Handles all payment data operations
- `create(array $data): int`
- `findByReference(string $reference): ?array`
- `updateStatus(int $paymentId, string $status, ?string $response): bool`
- `referenceExists(string $reference): bool`

**Benefit**: Single source of truth for payment data access

#### **PaymentService** (Orchestrator - NOW THIN)
Coordinates payment workflow
- Calls gateway → initiates payment
- Calls gateway → verifies payment
- Uses repository → persists payment record
- **Does NOT create transactions** (move to OrderService)

**Benefit**: Clear, focused, testable

---

## SOLID Principles Applied

### **S - Single Responsibility**
- PaymentService: Orchestrate payment workflow only
- PaymentRepository: Data persistence only
- PaymentGatewayInterface: Define contract only
- PaystackGateway: Paystack integration only

### **O - Open/Closed**
- Service open for extension (new gateways)
- Closed for modification (add via factory)
```php
PaymentGatewayFactory::register('stripe', StripeGateway::class);
```

### **L - Liskov Substitution**
- Any gateway implementing PaymentGatewayInterface is substitutable
```php
$gateway = PaymentGatewayFactory::make($method); // Any gateway works
```

### **I - Interface Segregation**
- PaymentGatewayInterface minimal and focused
- Clients depend only on needed methods

### **D - Dependency Inversion**
- PaymentService depends on abstractions (PaymentGatewayInterface)
- Not concrete implementations (PaystackService)

---

## DRY Principles Applied

### **No Code Duplication**
- ❌ No repeated database queries
- ❌ No duplicate payment validation
- ❌ No repeated gateway calls

### **Centralized Logic**
- Reference generation in one place
- Payment status updates centralized
- Gateway response handling unified

---

## Recommended Implementation Scenarios

### **Scenario 1: Initialize Payment (with Transaction)**

**Controller:**
```php
public function initiatePayment(Request $request)
{
    try {
        $userId = $this->getUserId();
        $amount = $request->post('amount');

        // Create transaction record (OrderService responsibility)
        $reference = bin2hex(random_bytes(10));
        $transactionId = $this->orderService->createTransaction([
            'user_id' => $userId,
            'amount' => $amount,
            'reference' => $reference,
            'payment_method' => 'paystack'
        ]);

        // Initialize payment
        $paymentData = $this->paymentService->initializePayment(
            $userId,
            $amount,
            'paystack'
        );

        return $this->response->json([
            'transaction_id' => $transactionId,
            'payment_url' => $paymentData['payment_url'],
            'reference' => $paymentData['reference']
        ]);

    } catch (Exception $e) {
        return $this->response->error($e->getMessage(), [], 400);
    }
}
```

### **Scenario 2: Verify Payment & Create Order**

**Controller:**
```php
public function verifyPayment(Request $request)
{
    try {
        $userId = $this->getUserId();
        $reference = $request->get('reference');

        // Verify payment with gateway
        $paymentId = $this->paymentService->verifyPayment($userId, $reference);

        // Create order from verified payment (OrderService)
        $orderId = $this->orderService->createFromPayment($paymentId, $userId);

        // Send confirmation email
        $this->mailService->sendOrderConfirmation($userId, $orderId);

        return $this->response->json([
            'payment_id' => $paymentId,
            'order_id' => $orderId,
            'message' => 'Payment verified and order created'
        ]);

    } catch (Exception $e) {
        return $this->response->error($e->getMessage(), [], 400);
    }
}
```

### **Scenario 3: Handle Payment Webhook (Paystack Callback)**

**Controller:**
```php
public function handlePaystackWebhook(Request $request)
{
    try {
        $payload = json_decode(file_get_contents('php://input'), true);

        // Validate webhook signature
        if (!$this->validateWebhookSignature($payload)) {
            return $this->response->error('Invalid signature', [], 403);
        }

        $reference = $payload['data']['reference'];
        $status = $payload['data']['status'];

        // Mark as failed if webhook says failed
        if ($status !== 'success') {
            $this->paymentService->failPayment(
                0, // Will create new record
                $reference,
                'Payment failed via webhook'
            );
            return $this->response->json(['status' => 'received']);
        }

        // Payment verified - handle as normal
        // (user will also verify via callback URL)

        return $this->response->json(['status' => 'received']);

    } catch (Exception $e) {
        \error_log('Webhook error: ' . $e->getMessage());
        return $this->response->error('Webhook processing failed', [], 500);
    }
}
```

### **Scenario 4: Add New Payment Gateway (Stripe)**

**1. Create Gateway Adapter:**
```php
// app/Services/PaymentGateways/StripeGateway.php
class StripeGateway implements PaymentGatewayInterface
{
    private StripeService $stripeService;

    public function __construct()
    {
        $this->stripeService = new StripeService();
    }

    public function initializeTransaction(array $data): array
    {
        // Stripe implementation
    }

    public function verifyTransaction(string $reference): array
    {
        // Stripe verification
    }

    public function getName(): string
    {
        return 'stripe';
    }
}
```

**2. Register in Factory:**
```php
// In bootstrap or config
PaymentGatewayFactory::register('stripe', StripeGateway::class);
```

**3. Use in Controller (NO CHANGES NEEDED):**
```php
$paymentData = $this->paymentService->initializePayment(
    $userId,
    $amount,
    'stripe'  // Switch from 'paystack' to 'stripe'
);
```

---

## Method Signatures

### **PaymentService::initializePayment()**
```php
public function initializePayment(
    int $userId,
    float $amount,
    string $paymentMethod = 'paystack'
): array
```
Returns: `['reference' => string, 'payment_url' => string]`

### **PaymentService::verifyPayment()**
```php
public function verifyPayment(
    int $userId,
    string $reference
): int  // Returns payment ID
```

### **PaymentService::failPayment()**
```php
public function failPayment(
    int $userId,
    string $reference,
    string $reason = 'Unknown'
): bool
```

### **PaymentService::getPaymentByReference()**
```php
public function getPaymentByReference(
    string $reference
): ?array
```

---

## Database Schema Requirements

### **transactions table** (Created by OrderService)
```sql
CREATE TABLE transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reference VARCHAR(255) NOT NULL UNIQUE,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### **payments table** (Managed by PaymentService)
```sql
CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    transaction_reference VARCHAR(255) NOT NULL UNIQUE,
    payment_method VARCHAR(50) NOT NULL,
    status ENUM('pending','success','failed','cancelled') DEFAULT 'pending',
    gateway_response LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

---

## Error Handling Best Practices

### **Expected Exceptions**
- `Exception` - General payment errors
- `InvalidArgumentException` - Invalid gateway method
- `RuntimeException` - Gateway configuration issues

### **Usage:**
```php
try {
    $paymentId = $this->paymentService->verifyPayment($userId, $reference);
} catch (Exception $e) {
    // Log error
    $this->log('Payment verification failed: ' . $e->getMessage());
    
    // Return user-friendly response
    return $this->response->error(
        'Payment verification failed. Please try again.',
        [],
        400
    );
}
```

---

## Testing Strategy

### **Unit Tests - PaymentService**
```php
public function testInitializePaymentSuccess()
public function testInitializePaymentWithInvalidUser()
public function testVerifyPaymentSuccess()
public function testVerifyPaymentAlreadyRecorded()
```

### **Unit Tests - PaymentRepository**
```php
public function testCreatePayment()
public function testFindByReference()
public function testUpdateStatus()
public function testReferenceExists()
```

### **Unit Tests - PaymentGatewayFactory**
```php
public function testMakePaystack()
public function testMakeStripe()
public function testMakeUnsupportedGateway()
public function testRegisterNewGateway()
```

---

## Next Steps

1. **Create OrderService** to handle transaction creation
2. **Implement WebhookValidator** for payment callbacks
3. **Add PaymentGateway tests** for each gateway
4. **Create PaymentController** with routes
5. **Add Stripe/Flutterwave** adapters
6. **Implement caching** for gateway status checks
7. **Add audit logging** for payment operations

---

## File Structure
```
app/
├── Contracts/
│   └── PaymentGatewayInterface.php
├── Services/
│   ├── PaymentService.php (refactored)
│   ├── PaystackService.php (unchanged)
│   └── PaymentGateways/
│       ├── PaystackGateway.php
│       └── PaymentGatewayFactory.php
├── Repositories/
│   └── PaymentRepository.php
└── Models/
    └── Payment.php (existing)
```

---

## Summary

✅ **Thin Service** - PaymentService is now lean and focused
✅ **DRY** - No duplication, centralized logic
✅ **SOLID** - Each class has single responsibility
✅ **Extensible** - Add gateways without modifying existing code
✅ **Testable** - Clear interfaces and dependencies
✅ **Maintainable** - Separation of concerns
