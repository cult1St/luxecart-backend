# Payment Service Refactoring - Implementation Checklist

## Files & Implementation Status

### ✅ Core Files Created

- [x] `app/Contracts/PaymentGatewayInterface.php` - Abstraction for payment gateways
- [x] `app/Services/PaymentGateways/PaystackGateway.php` - Paystack adapter
- [x] `app/Services/PaymentGateways/PaymentGatewayFactory.php` - Gateway factory
- [x] `app/Repositories/PaymentRepository.php` - Data persistence layer
- [x] `app/Services/OrderService.php` - Transaction & order creation
- [x] `app/Controllers/PaymentController.php` - Example usage

### ✅ Files Refactored

- [x] `app/Services/PaymentService.php` - Thin orchestration layer

### ✅ Documentation Created

- [x] `PAYMENT_SERVICE_REFACTORING.md` - Architecture & best practices
- [x] `REFACTORING_SUMMARY.md` - Before/after comparison
- [x] `MIGRATION_GUIDE.md` - Step-by-step migration
- [x] `VISUAL_COMPARISON.md` - Visual diagrams & examples
- [x] `IMPLEMENTATION_CHECKLIST.md` - This file

---

## Pre-Implementation Steps

### Local Testing
- [ ] Copy all new files to your local project
- [ ] Review file structure matches expected paths
- [ ] Check file permissions are correct
- [ ] Verify no file conflicts

### Backup
- [ ] Backup current `app/Services/PaymentService.php`
- [ ] Backup database schema
- [ ] Commit current code to git
- [ ] Create feature branch

### Dependencies
- [ ] Ensure Composer autoload works with new namespaces
- [ ] Run `composer dump-autoload` if needed
- [ ] Verify no missing dependencies

---

## Step 1: Directory Structure Setup

```
app/
├── Contracts/
│   └── PaymentGatewayInterface.php
├── Services/
│   ├── PaymentService.php (refactored)
│   ├── OrderService.php (new)
│   ├── PaystackService.php (existing)
│   └── PaymentGateways/
│       ├── PaystackGateway.php
│       ├── PaymentGatewayFactory.php
│       └── ExampleGateways.php
├── Repositories/
│   └── PaymentRepository.php
└── Controllers/
    └── PaymentController.php
```

Checklist:
- [ ] Create `app/Contracts/` directory
- [ ] Create `app/Services/PaymentGateways/` directory
- [ ] Create `app/Repositories/` directory
- [ ] All files in correct locations
- [ ] File names match exactly

---

## Step 2: Composer Autoload Configuration

### Update `composer.json`

If using PSR-4 autoloading, ensure these namespaces are configured:

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "App\\Contracts\\": "app/Contracts/",
            "App\\Services\\": "app/Services/",
            "App\\Services\\PaymentGateways\\": "app/Services/PaymentGateways/",
            "App\\Repositories\\": "app/Repositories/",
            "App\\Controllers\\": "app/Controllers/",
            "Core\\": "core/"
        }
    }
}
```

Checklist:
- [ ] Namespaces exist in composer.json
- [ ] Run `composer dump-autoload`
- [ ] No autoload errors

---

## Step 3: Environment Configuration

### Verify `.env` File

Ensure payment gateway configurations exist:

```env
PAYSTACK_SECRET_KEY=sk_test_xxxxx
PAYSTACK_PUBLIC_KEY=pk_test_xxxxx
PAYSTACK_CALLBACK_URL=http://localhost/payment/verify
PAYSTACK_CANCEL_URL=http://localhost/payment/cancel
```

Checklist:
- [ ] `PAYSTACK_SECRET_KEY` configured
- [ ] `PAYSTACK_PUBLIC_KEY` configured
- [ ] `PAYSTACK_CALLBACK_URL` configured (can use `env()` function)
- [ ] `PAYSTACK_CANCEL_URL` configured

---

## Step 4: Bootstrap/Initialization Setup

### Create or Update Bootstrap File

Add gateway registration to `bootstrap.php` or your app initialization:

```php
<?php
// bootstrap.php

// ... existing code ...

// Payment Gateway Factory Setup
use App\Services\PaymentGateways\PaymentGatewayFactory;
use App\Services\PaymentGateways\PaystackGateway;

// Register available payment gateways
PaymentGatewayFactory::register('paystack', PaystackGateway::class);

// Optional: Register other gateways when ready
// PaymentGatewayFactory::register('stripe', StripeGateway::class);
// PaymentGatewayFactory::register('flutterwave', FlutterwaveGateway::class);

// ... rest of bootstrap code ...
```

Checklist:
- [ ] Import PaymentGatewayFactory
- [ ] Import PaystackGateway
- [ ] Register at least 'paystack' gateway
- [ ] Placed before PaymentService usage
- [ ] No syntax errors

---

## Step 5: Database Schema Verification

### Verify Required Tables Exist

Ensure your database has these tables:

#### `transactions` table
```sql
CREATE TABLE IF NOT EXISTS transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reference VARCHAR(255) NOT NULL UNIQUE,
    status VARCHAR(50) DEFAULT 'pending',
    remark TEXT,
    cart LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

#### `payments` table
```sql
CREATE TABLE IF NOT EXISTS payments (
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

#### `orders` table
```sql
CREATE TABLE IF NOT EXISTS orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    order_id VARCHAR(50) NOT NULL UNIQUE,
    status ENUM('pending','paid','processing','shipped','delivered','cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    shipping_amount DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    final_amount DECIMAL(10,2) NOT NULL,
    delivered_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

Checklist:
- [ ] `transactions` table exists and has correct columns
- [ ] `payments` table exists and has correct columns (note: `transaction_reference` not `transcation_reference`)
- [ ] `orders` table exists and has correct columns
- [ ] All foreign keys properly configured
- [ ] Indexes on frequently queried columns

---

## Step 6: Routes Configuration

### Update `routes.php`

Add payment routes:

```php
<?php
// routes.php

// Payment Routes
Route::post('/payment/initialize', 'PaymentController@initiate');
Route::post('/payment/verify', 'PaymentController@verify');
Route::get('/payment/status/:reference', 'PaymentController@status');
Route::post('/payment/webhook', 'PaymentController@webhook');  // Public endpoint
```

Checklist:
- [ ] Payment routes added
- [ ] Routes match PaymentController methods
- [ ] Webhook route is public (no auth required)
- [ ] Verify routes added to router

---

## Step 7: Model Updates

### Update Payment Model (if needed)

The existing `app/Models/Payment.php` should work, but verify:

```php
<?php
class Payment extends BaseModel
{
    protected string $table = 'payments';
    protected array $fillable = [
        'user_id',
        'amount',
        'transaction_reference',  // NOT 'transcation_reference'
        'payment_method',
        'status',
        'gateway_response',
    ];
}
```

Checklist:
- [ ] `transaction_reference` column name correct
- [ ] Fillable array includes all needed fields
- [ ] No typos in column names

---

## Step 8: Controller Implementation

### Option A: Use Provided PaymentController

The provided `PaymentController.php` has everything you need:
- [ ] Copy `PaymentController.php` to `app/Controllers/`
- [ ] Review the implementation
- [ ] Test endpoints

### Option B: Update Existing Controller

If you have existing payment handling:

```php
<?php
class YourPaymentController extends BaseController
{
    private PaymentService $paymentService;
    private OrderService $orderService;

    public function __construct(Database $db, Request $request, Response $response)
    {
        parent::__construct($db, $request, $response);
        $this->paymentService = new PaymentService($db);
        $this->orderService = new OrderService($db);
    }

    public function initiate()
    {
        try {
            $userId = $this->getUserId();
            $amount = (float)$this->request->post('amount');
            $method = $this->request->post('method') ?? 'paystack';

            // Create transaction (OrderService)
            $reference = $this->orderService->createTransaction($userId, $amount, $method);

            // Initialize payment (PaymentService)
            $paymentData = $this->paymentService->initializePayment($userId, $amount, $method);

            return $this->response->json([
                'reference' => $reference,
                'payment_url' => $paymentData['payment_url'],
            ]);
        } catch (Exception $e) {
            return $this->response->error($e->getMessage(), [], 400);
        }
    }

    public function verify()
    {
        try {
            $userId = $this->getUserId();
            $reference = $this->request->post('reference');

            // Verify payment (PaymentService)
            $paymentId = $this->paymentService->verifyPayment($userId, $reference);

            // Create order (OrderService)
            $orderId = $this->orderService->createFromPayment($paymentId, $userId);

            return $this->response->json([
                'order_id' => $orderId,
                'message' => 'Payment verified',
            ]);
        } catch (Exception $e) {
            return $this->response->error($e->getMessage(), [], 400);
        }
    }
}
```

Checklist:
- [ ] OrderService instantiated
- [ ] PaymentService instantiated
- [ ] Initiate calls both services
- [ ] Verify calls both services
- [ ] Error handling with try-catch

---

## Step 9: Testing - Unit Tests

### Create Basic Tests

Create `tests/PaymentServiceTest.php`:

```php
<?php
namespace Tests;

use App\Services\PaymentService;
use App\Services\OrderService;
use App\Repositories\PaymentRepository;
use Core\Database;
use PHPUnit\Framework\TestCase;

class PaymentServiceTest extends TestCase
{
    private PaymentService $paymentService;
    private Database $db;

    public function setUp(): void
    {
        // Mock database
        $this->db = $this->createMock(Database::class);
        $this->paymentService = new PaymentService($this->db);
    }

    public function test_initialize_payment_success()
    {
        // Arrange
        $userId = 1;
        $amount = 5000;

        // Act
        $result = $this->paymentService->initializePayment($userId, $amount, 'paystack');

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('payment_url', $result);
        $this->assertArrayHasKey('reference', $result);
    }

    public function test_initialize_payment_invalid_user()
    {
        // Arrange
        $userId = 9999;
        $amount = 5000;

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->paymentService->initializePayment($userId, $amount, 'paystack');
    }

    public function test_verify_payment_success()
    {
        // Add similar tests for verifyPayment
    }
}
```

Checklist:
- [ ] Create test file
- [ ] Write unit tests for key methods
- [ ] Mock database and dependencies
- [ ] Tests pass locally

---

## Step 10: Testing - Integration Tests

### Manual Testing Checklist

#### Test 1: Payment Initialization
```
1. Call: POST /payment/initialize
2. Body: { "amount": 5000, "method": "paystack" }
3. Expected: 200 OK with payment_url
4. Verify: Transaction created in database
```
- [ ] Endpoint returns 200
- [ ] Response has payment_url
- [ ] Transaction record created
- [ ] Reference generated

#### Test 2: Payment Verification (Sandbox)
```
1. Initialize payment (get reference)
2. Go to payment_url
3. Complete test payment in Paystack
4. Call: POST /payment/verify
5. Body: { "reference": "xxx" }
6. Expected: 200 OK with order_id
```
- [ ] Payment verified successfully
- [ ] Payment record created
- [ ] Order record created
- [ ] Correct status values

#### Test 3: Payment Failure Handling
```
1. Initialize payment
2. Cancel on Paystack
3. Verify payment
4. Expected: Exception with error message
```
- [ ] Error thrown correctly
- [ ] Transaction marked failed
- [ ] Clear error message

#### Test 4: Webhook Handling (if implemented)
```
1. Send webhook payload from Paystack
2. Verify webhook signature
3. Expected: 200 OK acknowledged
```
- [ ] Webhook signature validated
- [ ] Response acknowledged
- [ ] Payment status updated

Checklist:
- [ ] Test initialization flow
- [ ] Test successful payment verification
- [ ] Test failed payment handling
- [ ] Test with sandbox credentials
- [ ] Monitor database changes
- [ ] Check logs for errors

---

## Step 11: Error Handling

### Common Issues & Solutions

#### Issue 1: "Class not found: PaymentGatewayFactory"
```
Solution:
1. Run: composer dump-autoload
2. Check: Namespace in PaymentGatewayFactory.php matches app structure
3. Verify: Bootstrap file imports the class
```
- [ ] Ran composer dump-autoload
- [ ] Namespace correct
- [ ] Bootstrap imports class

#### Issue 2: "Unsupported payment method: paystack"
```
Solution:
1. Verify: PaymentGatewayFactory::register() called in bootstrap
2. Check: Gateway class exists at specified path
3. Ensure: bootstrap.php is loaded before PaymentService usage
```
- [ ] Bootstrap file loaded
- [ ] Gateway registered
- [ ] PaymentGatewayFactory imported

#### Issue 3: "Transaction record not found"
```
Solution:
1. Verify: OrderService::createTransaction() called before verify
2. Check: Transaction actually inserted in database
3. Ensure: Reference matches between transaction and verify calls
```
- [ ] createTransaction called first
- [ ] Transaction appears in database
- [ ] Reference matches exactly

#### Issue 4: Column name typo errors
```
Solution:
1. Check: payments table has 'transaction_reference' not 'transcation_reference'
2. Verify: PaymentRepository uses correct column name
3. Database migration if needed
```
- [ ] Column name correct in database
- [ ] Column name correct in repository
- [ ] Old typo removed

Checklist:
- [ ] All known issues addressed
- [ ] Error messages clear
- [ ] Proper exception handling

---

## Step 12: Performance & Monitoring

### Add Logging

```php
// In PaymentController
$this->log('Payment initialized: reference=' . $reference, 'info');
$this->log('Payment verified: paymentId=' . $paymentId, 'info');
$this->log('Payment failed: reason=' . $reason, 'warning');
```

Checklist:
- [ ] Add logging for key operations
- [ ] Monitor payment flow in logs
- [ ] Track errors and exceptions
- [ ] Review logs for patterns

### Database Optimization

```sql
-- Add indexes for common queries
ALTER TABLE payments ADD INDEX idx_user_reference (user_id, transaction_reference);
ALTER TABLE transactions ADD INDEX idx_reference (reference);
ALTER TABLE orders ADD INDEX idx_user_status (user_id, status);
```

Checklist:
- [ ] Indexes added to payments table
- [ ] Indexes added to transactions table
- [ ] Query performance acceptable

---

## Step 13: Security Checklist

- [ ] All user inputs validated in controller
- [ ] SQL injection prevention (use parameterized queries)
- [ ] CSRF protection enabled on payment routes
- [ ] HTTPS only for payment endpoints
- [ ] Payment data never logged in plaintext
- [ ] Webhook signature validation implemented
- [ ] Rate limiting on payment endpoints
- [ ] Sensitive environment variables not exposed

---

## Step 14: Deployment

### Pre-Deployment
- [ ] All tests pass locally
- [ ] Code review completed
- [ ] Database schema updated
- [ ] Environment variables configured
- [ ] Backup of production code taken
- [ ] Rollback plan documented

### Deployment Steps
1. [ ] Deploy to staging environment
2. [ ] Run full test suite on staging
3. [ ] Test with sandbox payment gateway
4. [ ] Verify logging and monitoring
5. [ ] Deploy to production
6. [ ] Monitor payment operations
7. [ ] Check error logs

### Post-Deployment
- [ ] Monitor error rates
- [ ] Check payment success rates
- [ ] Review customer feedback
- [ ] Monitor database performance
- [ ] Keep backup available for 24+ hours

---

## Step 15: Documentation Updates

- [ ] Update API documentation with new endpoints
- [ ] Update developer guide
- [ ] Update deployment guide
- [ ] Document payment flow for team
- [ ] Create troubleshooting guide
- [ ] Update changelog

---

## Final Checklist

### Files & Code
- [ ] All new files created
- [ ] PaymentService refactored
- [ ] No syntax errors
- [ ] Proper namespaces
- [ ] Imports correct

### Configuration
- [ ] Composer autoload updated
- [ ] Bootstrap file configured
- [ ] Environment variables set
- [ ] Routes added
- [ ] Database schema ready

### Testing
- [ ] Unit tests created
- [ ] Unit tests pass
- [ ] Integration tests pass
- [ ] Manual testing completed
- [ ] Error scenarios tested

### Production Ready
- [ ] Security checks passed
- [ ] Performance acceptable
- [ ] Logging configured
- [ ] Monitoring active
- [ ] Documentation updated
- [ ] Team trained

---

## Rollback Procedure

If issues occur in production:

```bash
# Revert to old PaymentService
git revert <commit-hash>

# Restore database (if needed)
mysql -u root -p database_name < backup.sql

# Clear any caches
php artisan cache:clear  # or equivalent

# Notify team
```

- [ ] Backup location documented
- [ ] Rollback tested on staging
- [ ] Team aware of procedure
- [ ] Rollback time estimated

---

## Sign-Off

- [ ] Development complete
- [ ] Testing complete
- [ ] Code review complete
- [ ] Deployment approved
- [ ] Documentation complete
- [ ] Team trained

**Ready for production deployment!**
