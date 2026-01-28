# Migration Guide - Payment Service Refactoring

## Quick Start (5 minutes)

### 1. **Update Your bootstrap.php or initialization file**

```php
// Register payment gateways (in bootstrap.php or app initialization)
use App\Services\PaymentGateways\PaymentGatewayFactory;

// Register Paystack (default)
PaymentGatewayFactory::register('paystack', \App\Services\PaymentGateways\PaystackGateway::class);

// Optional: Register other gateways when ready
// PaymentGatewayFactory::register('stripe', StripeGateway::class);
// PaymentGatewayFactory::register('flutterwave', FlutterwaveGateway::class);
```

### 2. **Create OrderService instance in your controller**

```php
$orderService = new \App\Services\OrderService($db);
$paymentService = new \App\Services\PaymentService($db);
```

### 3. **Update payment initialization flow**

**Old way:**
```php
$paymentService->initializePayment($userId, 'paystack', $amount);
// Problem: Transaction created inside, mixed concerns
```

**New way:**
```php
// Step 1: Create transaction (OrderService)
$reference = $orderService->createTransaction($userId, $amount, 'paystack');

// Step 2: Initialize payment (PaymentService)
$paymentData = $paymentService->initializePayment($userId, $amount, 'paystack');

// Return payment URL to client
return [
    'reference' => $reference,
    'payment_url' => $paymentData['payment_url']
];
```

### 4. **Update payment verification flow**

**Old way:**
```php
$paymentId = $paymentService->verifyPayment($userId, $reference);
// Problem: Returns bool|int inconsistently
```

**New way:**
```php
// Step 1: Verify with gateway (PaymentService)
$paymentId = $paymentService->verifyPayment($userId, $reference);

// Step 2: Create order (OrderService)
$orderId = $orderService->createFromPayment($paymentId, $userId);

// Step 3: Send confirmation
$mailService->sendOrderConfirmation($userId, $orderId);
```

---

## File Changes Checklist

### ✅ New Files Created
- [ ] `app/Contracts/PaymentGatewayInterface.php`
- [ ] `app/Services/PaymentGateways/PaystackGateway.php`
- [ ] `app/Services/PaymentGateways/PaymentGatewayFactory.php`
- [ ] `app/Repositories/PaymentRepository.php`
- [ ] `app/Services/OrderService.php`
- [ ] `app/Controllers/PaymentController.php` (example)
- [ ] `app/Services/PaymentGateways/ExampleGateways.php` (reference)

### ✏️ Files Modified
- [ ] `app/Services/PaymentService.php` (refactored)

### 📚 Documentation
- [ ] `PAYMENT_SERVICE_REFACTORING.md`
- [ ] `REFACTORING_SUMMARY.md`

---

## Breaking Changes

### **1. Method Signature Changed**
```php
// OLD
public function initializePayment(int $userId, string $paymentMethod, float $amount)

// NEW
public function initializePayment(int $userId, float $amount, string $paymentMethod = 'paystack')
```
**Action**: Update all calls to use new parameter order

### **2. Return Type Changed**
```php
// OLD
public function verifyPayment(): bool|int  // Inconsistent

// NEW
public function verifyPayment(): int  // Always returns payment ID
```
**Action**: Update error handling - now throws Exception instead of returning false

### **3. Exceptions Instead of Return Values**
```php
// OLD
if ($paymentId = $paymentService->verifyPayment(...)) {
    // Success
} else {
    // Failed
}

// NEW
try {
    $paymentId = $paymentService->verifyPayment(...);
    // Success
} catch (Exception $e) {
    // Failed
}
```
**Action**: Wrap calls in try-catch blocks

### **4. Transaction Creation Removed**
```php
// OLD (was inside initializePayment)
$paymentService->initializePayment($userId, 'paystack', $amount);
// Internally created transaction

// NEW (must create separately)
$reference = $orderService->createTransaction($userId, $amount, 'paystack');
$paymentData = $paymentService->initializePayment($userId, $amount, 'paystack');
```
**Action**: Call OrderService::createTransaction() before initializePayment()

---

## Step-by-Step Migration

### **Phase 1: Preparation**

1. Review the new files in `app/Contracts/`, `app/Services/PaymentGateways/`, `app/Repositories/`
2. Understand the architecture (read PAYMENT_SERVICE_REFACTORING.md)
3. Create OrderService in `app/Services/`

### **Phase 2: Update Bootstrap**

```php
// bootstrap.php
require_once APP_PATH . '/Services/PaymentGateways/PaymentGatewayFactory.php';
require_once APP_PATH . '/Services/PaymentGateways/PaystackGateway.php';

// Register gateways
PaymentGatewayFactory::register('paystack', PaystackGateway::class);
```

### **Phase 3: Update Controllers**

Find all places using PaymentService:

```bash
# Search for PaymentService usage
grep -r "paymentService" app/Controllers/
```

Update to new pattern:
```php
// OLD
$paymentId = $this->paymentService->initializePayment($userId, 'paystack', $amount);

// NEW
$reference = $this->orderService->createTransaction($userId, $amount, 'paystack');
$paymentData = $this->paymentService->initializePayment($userId, $amount, 'paystack');
```

### **Phase 4: Update Views/Routes**

Update payment endpoints:
```php
// routes.php
Route::post('/payment/initialize', 'PaymentController@initiate');
Route::post('/payment/verify', 'PaymentController@verify');
Route::get('/payment/status/:reference', 'PaymentController@status');
Route::post('/payment/webhook', 'PaymentController@webhook');
```

### **Phase 5: Testing**

```php
// Test initialization
$reference = $orderService->createTransaction(4, 5000, 'paystack');
$result = $paymentService->initializePayment(4, 5000, 'paystack');
var_dump($result);  // Should have 'payment_url' and 'reference'

// Test verification
try {
    $paymentId = $paymentService->verifyPayment(4, $reference);
    echo "Payment ID: $paymentId\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

### **Phase 6: Deployment**

1. Backup current PaymentService
2. Deploy new files
3. Test with sandbox payment gateway
4. Monitor logs for errors
5. Gradually roll out to production

---

## Common Errors & Solutions

### **Error: "Unsupported payment method"**
```
Cause: Gateway not registered
Solution: Add registration in bootstrap.php
```

### **Error: "Transaction record not found"**
```
Cause: Calling verifyPayment() before createTransaction()
Solution: Always call createTransaction() first
```

### **Error: "Payment already recorded for this reference"**
```
Cause: Calling verifyPayment() twice with same reference
Solution: Check if payment exists before verifying
```

### **Error: "Class not found PaymentGatewayInterface"**
```
Cause: Interface not auto-loaded
Solution: Ensure composer autoload is updated
```

---

## Testing Checklist

### **Unit Tests**
- [ ] PaymentService::initializePayment()
- [ ] PaymentService::verifyPayment()
- [ ] PaymentService::failPayment()
- [ ] PaymentRepository::create()
- [ ] PaymentRepository::findByReference()
- [ ] PaymentGatewayFactory::make()

### **Integration Tests**
- [ ] Full payment flow (init → verify → order)
- [ ] Failed payment handling
- [ ] Webhook processing
- [ ] Multiple payment methods

### **Manual Tests**
- [ ] Payment initialization with sandbox
- [ ] Return from payment gateway
- [ ] Payment verification
- [ ] Order creation
- [ ] Email confirmation

---

## Rollback Plan (If Needed)

If issues occur:

```bash
# Revert to old PaymentService
git checkout HEAD~1 app/Services/PaymentService.php

# Keep new files but disable in routes
# Comment out payment routes temporarily
```

---

## Support for New Gateways

To add Stripe:

1. Create `app/Services/PaymentGateways/StripeGateway.php`
2. Implement PaymentGatewayInterface
3. Register: `PaymentGatewayFactory::register('stripe', StripeGateway::class)`
4. Use: `$paymentService->initializePayment($userId, $amount, 'stripe')`

See `ExampleGateways.php` for reference implementations.

---

## Performance Considerations

### **Database Queries**
- PaymentService: 2-3 queries per operation (optimized)
- PaymentRepository: Single responsibility - easy to optimize
- Consider caching: Gateway status checks, user payment history

### **Gateway API Calls**
- Paystack API is fast (typically < 500ms)
- Consider retries for failed gateway calls
- Implement request timeout handling

---

## Monitoring & Logging

Add to your logging:

```php
// Log successful payment
$this->log("Payment verified: Reference=$reference, PaymentID=$paymentId", 'info');

// Log failed payment
$this->log("Payment failed: Reference=$reference, Reason=$reason", 'warning');

// Log gateway errors
$this->log("Gateway error: {$e->getMessage()}", 'error');
```

---

## Questions?

Refer to:
- `PAYMENT_SERVICE_REFACTORING.md` - Architecture & principles
- `REFACTORING_SUMMARY.md` - Before/after comparison
- `PaymentController.php` - Usage examples
- `ExampleGateways.php` - Adding new gateways

---

## Summary

The migration is straightforward:

1. ✅ Copy new files
2. ✅ Register gateways in bootstrap
3. ✅ Update controller calls
4. ✅ Use try-catch for exceptions
5. ✅ Create transactions before payments
6. ✅ Test thoroughly

**Estimated time: 1-2 hours for small projects, varies by project size**
