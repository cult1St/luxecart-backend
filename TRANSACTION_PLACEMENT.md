# Transaction Creation - Where & When

## 📋 Quick Answer

**Transactions should be created BEFORE calling `initializePayment()`**

---

## 🔄 The Correct Flow

```
1. User adds items to cart
   ↓
2. User initiates checkout
   ↓
3. CREATE TRANSACTION ← First step
   └─ stores: user_id, amount, reference, payment_method, status='pending'
   ↓
4. INITIALIZE PAYMENT ← Uses the transaction reference
   └─ user gets: payment_url to proceed to payment gateway
   ↓
5. User completes payment on gateway
   ↓
6. VERIFY PAYMENT ← Confirms payment with gateway
   └─ creates payment record, updates transaction status='completed'
   ↓
7. CREATE ORDER ← From verified payment
   └─ creates actual order record
```

---

## ✅ Why Transaction FIRST?

### 1. **Database Integrity**
- Transaction represents the intent to pay
- Payment represents actual money collection
- Separation of concerns

### 2. **Reference Generation**
- Transaction creates the unique reference
- Reference is used for payment gateway
- Gateway needs the reference upfront

### 3. **Tracking Failed Attempts**
- If payment fails, transaction still exists
- Shows what was attempted
- Helps with payment analytics

### 4. **User Experience**
- User sees the reference
- Can inquire about stuck transactions
- Clear trail of what happened

---

## 📝 Code Example

### Controller Implementation

```php
<?php
class CheckoutController extends BaseController
{
    public function initiatePayment()
    {
        try {
            $userId = $this->getUserId();
            $amount = $this->request->post('amount');
            $paymentMethod = $this->request->post('method') ?? 'paystack';

            // ===== STEP 1: CREATE TRANSACTION =====
            $reference = $this->createTransaction($userId, $amount, $paymentMethod);

            // ===== STEP 2: INITIALIZE PAYMENT =====
            $paymentData = $this->paymentService->initializePayment(
                $userId,
                $amount,
                $reference,  // Pass the reference from transaction
                $paymentMethod
            );

            // ===== STEP 3: RETURN TO CLIENT =====
            return $this->response->json([
                'reference'   => $reference,
                'payment_url' => $paymentData['payment_url'],
                'message'     => 'Proceed to payment'
            ]);

        } catch (Exception $e) {
            return $this->response->error($e->getMessage(), [], 400);
        }
    }

    /**
     * Create transaction record
     */
    private function createTransaction(int $userId, float $amount, string $paymentMethod): string
    {
        $reference = $this->generateReference($paymentMethod);

        $transactionId = $this->db->insert('transactions', [
            'user_id'        => $userId,
            'payment_method' => $paymentMethod,
            'amount'         => $amount,
            'reference'      => $reference,
            'status'         => 'pending',
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        if (!$transactionId) {
            throw new Exception('Failed to create transaction');
        }

        return $reference;
    }

    /**
     * Generate unique reference
     */
    private function generateReference(string $method): string
    {
        return strtolower($method) . '_' . bin2hex(random_bytes(10)) . '_' . time();
    }

    /**
     * Verify payment after user returns
     */
    public function verifyPayment()
    {
        try {
            $userId = $this->getUserId();
            $reference = $this->request->post('reference');

            if (!$reference) {
                throw new Exception('Payment reference required');
            }

            // ===== VERIFY PAYMENT =====
            // verifyPayment() will:
            // 1. Call payment gateway to verify
            // 2. Create payment record
            // 3. Update transaction status
            $paymentId = $this->paymentService->verifyPayment($userId, $reference);

            // ===== CREATE ORDER FROM PAYMENT =====
            $orderId = $this->createOrder($userId, $paymentId);

            return $this->response->json([
                'order_id'  => $orderId,
                'payment_id' => $paymentId,
                'message'   => 'Payment verified and order created'
            ]);

        } catch (Exception $e) {
            return $this->response->error($e->getMessage(), [], 400);
        }
    }

    /**
     * Create order from verified payment
     */
    private function createOrder(int $userId, int $paymentId): int
    {
        $payment = $this->paymentService->getPayment($paymentId);
        
        if (!$payment || $payment['status'] !== 'success') {
            throw new Exception('Payment not successful');
        }

        $orderId = $this->db->insert('orders', [
            'user_id'      => $userId,
            'order_id'     => 'ORD-' . bin2hex(random_bytes(5)),
            'status'       => 'processing',
            'total_amount' => $payment['amount'],
            'final_amount' => $payment['amount'],
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        if (!$orderId) {
            throw new Exception('Failed to create order');
        }

        return $orderId;
    }
}
```

---

## 🔍 What Happens in Each Step

### Step 1: Create Transaction
```php
// Insert into transactions table
INSERT INTO transactions (user_id, payment_method, amount, reference, status)
VALUES (4, 'paystack', 5000, 'paystack_abc123_1234567890', 'pending')
```

**Records:**
- User intent to pay
- Amount to pay
- Reference for tracking
- Initial status: pending

---

### Step 2: Initialize Payment
```php
// Call Paystack API
$paymentService->initializePayment($userId, $amount, $reference, 'paystack')
```

**Returns:**
- Authorization URL for user to click
- Uses the transaction reference

**What PaymentService does:**
- Finds user email
- Calls PaystackService with reference
- Returns payment_url

---

### Step 3: User Pays
```
User clicks payment_url
↓
Redirected to Paystack
↓
Enters card details
↓
Completes payment
↓
Redirected back to your app (callback_url with reference)
```

---

### Step 4: Verify Payment
```php
$paymentService->verifyPayment($userId, $reference)
```

**What PaymentService does:**
1. Check transaction exists
2. Call Paystack API to verify
3. Create payment record:
   ```php
   INSERT INTO payments (user_id, amount, transaction_reference, status)
   VALUES (4, 5000, 'paystack_abc123_1234567890', 'success')
   ```
4. Update transaction status to 'completed'

**Returns:**
- Payment ID

---

### Step 5: Create Order
```php
// Only after payment verified
$orderId = $this->createOrder($userId, $paymentId)
```

**Creates:**
- Order record with items
- Links to payment

---

## 📊 Database State at Each Step

### After Step 1 (Create Transaction)
```
transactions table:
id | user_id | reference | amount | status
---|---------|-----------|--------|--------
1  | 4       | paystack_abc123 | 5000 | pending
```

### After Step 4 (Verify Payment)
```
transactions table:
id | user_id | reference | amount | status
---|---------|-----------|--------|----------
1  | 4       | paystack_abc123 | 5000 | completed

payments table:
id | user_id | transaction_reference | amount | status
---|---------|----------------------|--------|--------
1  | 4       | paystack_abc123 | 5000 | success
```

### After Step 5 (Create Order)
```
orders table:
id | user_id | order_id | status
---|---------|----------|----------
1  | 4       | ORD-xyz | processing
```

---

## ⚠️ What NOT to Do

### ❌ Wrong: Transaction after payment
```php
// WRONG!
$paymentData = $paymentService->initializePayment($userId, $amount, $reference);
$reference = $this->createTransaction($userId, $amount, $paymentMethod); // Too late!
```

**Problems:**
- Reference doesn't exist when payment gateway needs it
- Payment gateway gets undefined reference
- Transaction never created if payment fails

---

### ❌ Wrong: No transaction at all
```php
// WRONG!
$paymentData = $paymentService->initializePayment($userId, $amount, $reference);
// No transaction created, no record of intent
```

**Problems:**
- No audit trail
- Can't track what was attempted
- Database inconsistency

---

## ✅ Best Practices

1. **Create Transaction First**
   - Before any payment gateway interaction
   - Generates the reference
   - Records user intent

2. **Use Transaction Reference in Payment**
   - Pass reference to initializePayment()
   - Ensures consistency
   - Gateway knows what transaction this is for

3. **Verify Before Creating Order**
   - Always verify payment succeeded
   - Only create order for successful payments
   - Prevents invalid orders

4. **Handle Failures Gracefully**
   - Transaction exists even if payment fails
   - Shows what was attempted
   - User can retry with same reference

---

## 🎯 Summary

| Step | Component | Action |
|------|-----------|--------|
| 1 | **Controller** | Create transaction record |
| 2 | **Controller** | Call PaymentService::initializePayment() |
| 3 | **PaymentService** | Get payment URL from gateway |
| 4 | **User** | Complete payment on gateway |
| 5 | **Controller** | Call PaymentService::verifyPayment() |
| 6 | **PaymentService** | Verify with gateway, create payment record |
| 7 | **Controller** | Create order from payment |

---

## 📞 Common Scenarios

### Scenario 1: Payment Succeeds
```
Transaction created (pending)
  ↓
Payment initialized
  ↓
User completes payment
  ↓
Payment verified (success)
  ↓
Transaction updated (completed)
  ↓
Order created
```

### Scenario 2: User Cancels
```
Transaction created (pending)
  ↓
Payment initialized
  ↓
User cancels on payment page
  ↓
No payment created
  ↓
Transaction remains (pending)
  ↓
User can try again with same reference
```

### Scenario 3: Payment Fails
```
Transaction created (pending)
  ↓
Payment initialized
  ↓
User completes payment (but it fails)
  ↓
Payment verification fails
  ↓
Payment record created (failed)
  ↓
Transaction remains (pending)
  ↓
User can retry
```

---

## 🚀 Implementation Checklist

- [ ] Transaction table has `reference` column (UNIQUE)
- [ ] Reference is generated before payment init
- [ ] Reference is passed to initializePayment()
- [ ] verifyPayment() checks transaction exists
- [ ] Order only created after payment verified
- [ ] Error handling for each step
- [ ] Transaction status properly updated
- [ ] User sees reference for tracking

---

**Key Takeaway: Create the transaction first, use its reference for payment initialization!**
