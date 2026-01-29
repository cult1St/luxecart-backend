## Hidden Wallet System - Implementation Guide

### Overview
The wallet system is a **backend-only** implementation. Users are not aware they have wallets - all wallet operations happen transparently after successful payment verification. This approach is more scalable and provides better transaction tracking.

### Architecture

```
Payment Flow:
User pays → Payment Gateway → Verify Payment → Wallet Operations (hidden) → Success
                                                ↓
                                    1. Credit wallet (topup)
                                    2. Debit wallet (purchase)
                                    3. Record history
```

### Key Components

#### 1. **Wallet Model** (`app/Models/Wallet.php`)
- **Purpose**: Direct wallet database operations
- **Key Methods**:
  - `getOrCreateWallet($userId)` - Gets existing wallet or creates new one
  - `creditWallet($userId, $amount, $type, $ref, $desc)` - Add funds to wallet
  - `debitWallet($userId, $amount, $type, $ref, $desc)` - Subtract funds from wallet
  - `getBalance($userId)` - Get current balance
  - `getWalletHistory($userId, $limit, $offset)` - Get transaction history
  - `getWalletHistoryByType($userId, $type, $limit)` - Get transactions by type

#### 2. **Wallet Service** (`app/Services/WalletService.php`)
- **Purpose**: Business logic for wallet operations
- **Key Methods**:
  - `processPaymentVerification($userId, $amount, $ref, $orderRef)` - Main payment processing
    - Creates wallet if needed
    - Credits amount (topup)
    - Debits amount (purchase)
    - Returns new balance
  - `topupWallet($userId, $amount, $ref, $desc)` - Manual topup
  - `purchaseFromWallet($userId, $amount, $ref, $desc)` - Debit for purchase
  - `refundToWallet($userId, $amount, $ref, $reason)` - Refund processing
  - `canAfford($userId, $amount)` - Check if user can afford purchase
  - `getStats($userId)` - Get wallet statistics

#### 3. **Updated Payment Service** (`app/Services/PaymentService.php`)
- **Integration Point**: `verifyPayment()` method
- **New Flow**:
  1. Verify with payment gateway
  2. Create payment record
  3. **Call `WalletService::processPaymentVerification()`**
  4. Return wallet balance along with payment ID
- **New Methods**:
  - `getWalletBalance($userId)` - Get user's wallet balance
  - `getWalletInfo($userId)` - Get wallet with statistics

#### 4. **Database Tables**

**wallets** table:
```sql
CREATE TABLE wallets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    balance DECIMAL(15,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id)
);
```

**wallet_history** table:
```sql
CREATE TABLE wallet_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wallet_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    action ENUM('credit','debit') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    transaction_type VARCHAR(50) NOT NULL,
    reference VARCHAR(255),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id),
    INDEX (wallet_id),
    INDEX (transaction_type),
    INDEX (created_at)
);
```

### Wallet Flow After Payment Success

#### Step 1: Payment Verification
```php
// In your controller or wherever payment is verified
$paymentService = new PaymentService($db);
$result = $paymentService->verifyPayment($userId, $paymentReference);

// Returns:
// [
//     'paymentId' => 123,
//     'walletBalance' => 50000.00,
//     'message' => 'Payment verified successfully'
// ]
```

#### Step 2: Wallet Processing (Automatic)
The wallet processing happens **inside** `PaymentService::verifyPayment()`:

```
Payment Verified with Amount: 50,000
↓
Step 1: Create/Get Wallet for User
↓
Step 2: CREDIT Wallet (Topup)
    Amount: +50,000
    Type: 'topup'
    Reference: payment_reference
    History Record: "Payment verified and credited to wallet"
↓
Step 3: DEBIT Wallet (Purchase)
    Amount: -50,000
    Type: 'purchase'
    Reference: order_reference (or same as payment)
    History Record: "Purchase deducted from wallet"
↓
Final Balance: 0 (if user had 0 before)
```

### Transaction Types

**Credit (Topup) Types:**
- `topup` - Direct wallet funding
- `refund` - Refund from failed purchase
- `bonus` - Admin bonus/reward

**Debit (Spending) Types:**
- `purchase` - Product/service purchase
- `withdrawal` - Wallet withdrawal to bank
- `fee` - System fee or commission

### Example Usage

#### Get Wallet Info
```php
$paymentService = new PaymentService($db);

// Get just balance
$balance = $paymentService->getWalletBalance($userId);
echo "Balance: " . $balance;

// Get full wallet info with stats
$wallet = $paymentService->getWalletInfo($userId);
echo "Current: " . $wallet->balance;
echo "Total Credited: " . $wallet->total_credited;
echo "Total Debited: " . $wallet->total_debited;
```

#### Manual Wallet Operations (If needed)
```php
$walletService = new WalletService($db);

// Top up wallet manually
$result = $walletService->topupWallet(
    $userId,
    5000,
    'manual_topup_ref_123',
    'Manual topup for testing'
);

// Process refund
$walletService->refundToWallet(
    $userId,
    5000,
    'payment_ref_123',
    'Refund for cancelled order'
);

// Check if user can afford something
$canAfford = $walletService->canAfford($userId, 10000);
if ($canAfford) {
    // Proceed with purchase
    $walletService->purchaseFromWallet(
        $userId,
        10000,
        'order_123',
        'Purchase of product X'
    );
}
```

#### Get Wallet History
```php
$walletService = new WalletService($db);

// Get all transactions
$history = $walletService->getHistory($userId, 50, 0);

// Get only topup transactions
$topups = $walletService->getHistoryByType($userId, 'topup', 50);

// Get only purchases
$purchases = $walletService->getHistoryByType($userId, 'purchase', 50);

foreach ($history as $transaction) {
    echo $transaction->action . ": " . $transaction->amount;
    echo " Type: " . $transaction->transaction_type;
    echo " Date: " . $transaction->created_at;
}
```

### Benefits of This Approach

1. **Scalability**: All transactions recorded in wallet history
2. **Transparency**: Complete audit trail of all operations
3. **Flexibility**: Easy to add new transaction types or rules
4. **Security**: Transaction integrity with begin/commit/rollback
5. **Hidden**: Users don't see wallet complexity, just balances
6. **Reconciliation**: Easy to track money flow and reconcile accounts
7. **Analytics**: Rich history for reporting and analysis
8. **Atomic Operations**: All-or-nothing transactions ensure consistency

### Database Setup

Run the migration to create the wallet tables:

```bash
php database/migrate.php
```

Or manually execute the schema from `database/schema.php` which now includes wallet tables.

### Important Notes

⚠️ **Critical Points:**
1. Wallets are created automatically on first payment
2. Users never interact with wallets directly
3. All wallet operations are transactional (atomic)
4. History cannot be deleted (audit trail)
5. Each user has only one wallet (unique constraint on user_id)
6. Balance is always in decimal format (supports cents)

### Error Handling

```php
try {
    $result = $paymentService->verifyPayment($userId, $reference);
} catch (Exception $e) {
    // Payment verification or wallet processing failed
    // Transaction is automatically rolled back
    error_log("Payment verification failed: " . $e->getMessage());
    // Inform user about failure
}
```

### Future Enhancements

- Wallet transfer between users
- Scheduled wallet operations
- Wallet limits/caps
- Wallet freeze/unfreeze
- Wallet export reports
- Webhook notifications for wallet events
