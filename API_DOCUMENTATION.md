# Frisan E-Commerce API - Complete Documentation

Comprehensive API documentation for all Frisan E-Commerce platform endpoints. All error responses return HTTP 400 with formatted error messages.

**Base URL:** `/api`

---

## Table of Contents

1. [Public Product Endpoints](#public-product-endpoints)
2. [Shopping Cart Endpoints](#shopping-cart-endpoints)
3. [Checkout & Shipping Endpoints](#checkout--shipping-endpoints)
4. [Order Endpoints](#order-endpoints)
5. [User Authentication Endpoints](#user-authentication-endpoints)
6. [User Account Endpoints](#user-account-endpoints)
7. [User Dashboard Endpoint](#user-dashboard-endpoint)
8. [User Payment Endpoints](#user-payment-endpoints)
9. [Admin Authentication Endpoints](#admin-authentication-endpoints)
10. [Admin Dashboard Endpoint](#admin-dashboard-endpoint)
11. [Admin Notifications Endpoints](#admin-notifications-endpoints)
12. [Admin Products Endpoints](#admin-products-endpoints)
13. [Response Format & Status Codes](#response-format--status-codes)

---

## Public Product Endpoints

### Get All Products

**Endpoint:** `GET /api/products`

**Description:** Retrieve paginated list of all active products available in the store.

**Authentication:** Not required

**Query Parameters:**
- `page` (integer, optional): Page number for pagination (default: 1)
- `per_page` (integer, optional): Products per page (default: 15)

**Request Example:**
```
GET /api/products?page=1&per_page=15
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "products": [
      {
        "id": 1,
        "name": "Wireless Mouse",
        "price": 4500,
        "description": "Ergonomic wireless mouse",
        "category": "Electronics",
        "image_url": "/images/mouse.jpg",
        "in_stock": true,
        "quantity": 50
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 45,
      "total_pages": 3
    }
  },
  "message": "Products retrieved successfully"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 400 | Failed to fetch products | Invalid parameters or database error |

---

### Get Product Details

**Endpoint:** `GET /api/products/{id}`

**Description:** Retrieve detailed information about a specific product.

**Authentication:** Not required

**URL Parameters:**
- `id` (integer, required): Product ID

**Request Example:**
```
GET /api/products/1
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "product": {
      "id": 1,
      "name": "Wireless Mouse",
      "price": 4500,
      "cost_price": 2000,
      "description": "Ergonomic wireless mouse with 2.4GHz connectivity",
      "category_id": 5,
      "category": "Electronics",
      "quantity": 50,
      "sku": "MOUSE-001",
      "image_url": "/images/mouse.jpg",
      "images": [
        {
          "id": 1,
          "url": "/images/mouse-1.jpg",
          "alt": "Front view"
        }
      ],
      "is_active": true,
      "created_at": "2026-01-15 10:30:00",
      "updated_at": "2026-02-04 14:22:00"
    }
  },
  "message": "Product retrieved successfully"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 404 | Product not found | Product doesn't exist or is inactive |
| 400 | Failed to fetch product | Database error or invalid ID |

---

### Get Related Products

**Endpoint:** `GET /api/products/related`

**Description:** Retrieve products related to the currently viewed product or category.

**Authentication:** Not required

**Query Parameters:**
- `category_id` (integer, optional): Filter by category
- `limit` (integer, optional): Maximum number of products (default: 10)

**Request Example:**
```
GET /api/products/related?category_id=5&limit=10
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "products": [
      {
        "id": 2,
        "name": "USB-C Cable",
        "price": 2500,
        "description": "High-speed USB-C charging cable",
        "image_url": "/images/cable.jpg"
      }
    ]
  },
  "message": "Related products retrieved successfully"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 400 | Failed to fetch related products | Database error or invalid parameters |

---

## Shopping Cart Endpoints

### Get Cart

**Endpoint:** `GET /api/cart`

**Description:** Retrieve current user's cart or anonymous cart based on cart token.

**Authentication:** Optional (works for both authenticated and anonymous users)

**Request Headers:**
- Cookie: `cart_token` (for anonymous users)

**Request Example:**
```
GET /api/cart
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "cart_id": 10,
    "items": [
      {
        "id": 1,
        "product_id": 1,
        "product_name": "Wireless Mouse",
        "quantity": 2,
        "price": 4500,
        "subtotal": 9000
      }
    ],
    "summary": {
      "subtotal": 9000,
      "discount": 500,
      "total": 8500
    },
    "cart_token": "token_if_new"
  },
  "message": "Cart retrieved successfully"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 400 | Failed to fetch cart | Database error |

---

### Add Item to Cart

**Endpoint:** `POST /api/cart/add`

**Description:** Add a product to the user's cart with specified quantity.

**Authentication:** Optional

**Request Body:**
- `product_id` (integer, required): Product ID to add
- `quantity` (integer, required): Quantity to add (minimum 1)

**Request Example:**
```json
{
  "product_id": 1,
  "quantity": 2
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "message": "Item added to cart",
    "cart_token": "token_if_new"
  },
  "message": "Item successfully added to cart"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 400 | Product ID is required | Missing product_id parameter |
| 400 | Product not found | Product doesn't exist |
| 400 | Insufficient stock | Requested quantity exceeds available stock |

---

### Remove Item from Cart

**Endpoint:** `PUT /api/cart/remove`

**Description:** Remove a specific item from the cart.

**Authentication:** Optional

**Request Body:**
- `product_id` (integer, required): Product ID to remove
- OR `cart_item_id` (integer, required): Cart item ID to remove

**Request Example:**
```json
{
  "product_id": 1
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "message": "Item removed from cart"
  },
  "message": "Item successfully removed"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 400 | Product ID or cart item ID is required | Missing required parameters |
| 400 | Item not found in cart | Item doesn't exist in cart |

---

### Update Cart Item Quantity

**Endpoint:** `PUT /api/cart/update_quantity`

**Description:** Update the quantity of an item in the cart.

**Authentication:** Optional

**Request Body:**
- `product_id` (integer, required): Product ID
- `quantity` (integer, required): New quantity (minimum 1)

**Request Example:**
```json
{
  "product_id": 1,
  "quantity": 5
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "message": "Cart item quantity updated",
    "new_quantity": 5,
    "new_total": 22500
  },
  "message": "Quantity updated successfully"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 400 | Invalid quantity | Quantity must be at least 1 |
| 400 | Product not found in cart | Item doesn't exist in cart |
| 400 | Insufficient stock | Quantity exceeds available stock |

---

## Checkout & Shipping Endpoints

### Save Shipping Information

**Endpoint:** `POST /api/checkout/shipping`

**Description:** Save shipping address and information for checkout. Creates new shipping record if doesn't exist.

**Authentication:** Required

**Request Body:**
- `first_name` (string, required): Customer first name
- `last_name` (string, required): Customer last name
- `email` (string, required): Delivery email
- `phone` (string, required): Phone number
- `address` (string, required): Street address
- `city` (string, required): City
- `state` (string, required): State/Province
- `postal_code` (string, required): Postal/Zip code
- `country` (string, required): Country

**Request Example:**
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone": "1234567890",
  "address": "123 Main St",
  "city": "Lagos",
  "state": "Lagos",
  "postal_code": "100001",
  "country": "Nigeria"
}
```

**Success Response (201):**
```json
{
  "success": true,
  "data": {
    "message": "Shipping info saved",
    "shipping_info": {
      "id": 5,
      "cart_id": 10,
      "first_name": "John",
      "last_name": "Doe",
      "email": "john@example.com",
      "phone": "1234567890",
      "address": "123 Main St",
      "city": "Lagos",
      "state": "Lagos",
      "postal_code": "100001",
      "country": "Nigeria",
      "created_at": "2026-02-04 15:30:00"
    }
  },
  "message": "Shipping info saved successfully"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 401 | Unauthorized | User not authenticated |
| 404 | Cart not found | User's cart doesn't exist |
| 409 | Shipping information already exists | Shipping info already saved for this cart |
| 400 | Validation failed | Missing or invalid required fields |

---

### Get Shipping Information

**Endpoint:** `GET /api/checkout/shipping`

**Description:** Retrieve saved shipping information for the user's cart.

**Authentication:** Required

**Request Example:**
```
GET /api/checkout/shipping
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "message": "Shipping info retrieved",
    "shipping": {
      "id": 5,
      "cart_id": 10,
      "first_name": "John",
      "last_name": "Doe",
      "email": "john@example.com",
      "phone": "1234567890",
      "address": "123 Main St",
      "city": "Lagos",
      "state": "Lagos",
      "postal_code": "100001",
      "country": "Nigeria"
    }
  },
  "message": "Shipping info retrieved successfully"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 401 | Unauthorized | User not authenticated |
| 404 | No shipping info found | Shipping information not yet saved |

---

### Update Shipping Information

**Endpoint:** `PUT /api/checkout/shipping`

**Description:** Update existing shipping information (partial updates allowed).

**Authentication:** Required

**Request Body:** (All fields optional, only include fields to update)
- `first_name` (string): Customer first name
- `last_name` (string): Customer last name
- `email` (string): Email address
- `phone` (string): Phone number
- `address` (string): Street address
- `city` (string): City
- `state` (string): State/Province
- `postal_code` (string): Postal code
- `country` (string): Country

**Request Example:**
```json
{
  "phone": "9876543210",
  "city": "Abuja"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "message": "Shipping info updated",
    "shipping_info": {
      "id": 5,
      "cart_id": 10,
      "first_name": "John",
      "last_name": "Doe",
      "email": "john@example.com",
      "phone": "9876543210",
      "address": "123 Main St",
      "city": "Abuja",
      "state": "Lagos",
      "postal_code": "100001",
      "country": "Nigeria"
    }
  },
  "message": "Shipping info updated successfully"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 401 | Unauthorized | User not authenticated |
| 404 | Cart not found | Cart doesn't exist |
| 400 | Validation failed | Invalid field values |

---

## Order Endpoints

### Get Order History

**Endpoint:** `GET /api/orders/order-history`

**Description:** Retrieve order history for authenticated user with summary information.

**Authentication:** Required

**Query Parameters:**
- `page` (integer, optional): Page number (default: 1)
- `per_page` (integer, optional): Orders per page (default: 20)
- `status` (string, optional): Filter by status (pending, completed, cancelled, etc.)

**Request Example:**
```
GET /api/orders/order-history?page=1&status=completed
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "message": "Order history retrieved",
    "orders": [
      {
        "id": 45,
        "order_number": "ORD-001045",
        "status": "completed",
        "total_amount": 15000,
        "items_count": 3,
        "created_at": "2026-02-03 10:30:00",
        "updated_at": "2026-02-04 14:22:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 10,
      "total_pages": 1
    }
  },
  "message": "Order history retrieved successfully"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 401 | Unauthorized | User not authenticated |
| 400 | Failed to fetch order history | Database error |

---

## User Authentication Endpoints

### User Signup

**Endpoint:** `POST /api/auth/signup`

**Description:** Register a new user account. Sends email verification code.

**Authentication:** Not required

**Request Body:**
- `email` (string, required): Email address (must be unique)
- `password` (string, required): Password (minimum 6 characters)
- `confirm_password` (string, required): Password confirmation
- `name` (string, required): Full name

**Request Example:**
```json
{
  "email": "user@example.com",
  "password": "securePassword123",
  "confirm_password": "securePassword123",
  "name": "John Doe"
}
```

**Success Response (201):**
```json
{
  "success": true,
  "data": {
    "user_id": 1,
    "email": "user@example.com",
    "name": "John Doe",
    "message": "Registration successful. Please verify your email."
  },
  "message": "User registered successfully"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 400 | Validation failed | Missing required fields or validation errors |
| 400 | Email already exists | Email is already registered |
| 400 | Passwords do not match | Password and confirm_password don't match |

---

### User Login

**Endpoint:** `POST /api/auth/login`

**Description:** Authenticate user with email and password. Returns API token.

**Authentication:** Not required

**Request Body:**
- `email` (string, required): User's email address
- `password` (string, required): User's password

**Request Example:**
```json
{
  "email": "user@example.com",
  "password": "securePassword123"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "user_id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "api_token": "token_string_here",
    "is_verified": true
  },
  "message": "Login successful"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 400 | Validation failed | Missing email or password |
| 400 | Invalid email or password | Credentials don't match any account |
| 400 | Account not verified | User hasn't verified email yet |
| 429 | Account blocked due to too many login attempts | Too many failed attempts (max 5 per 15 min) |

---

### User Logout

**Endpoint:** `POST /api/auth/logout`

**Description:** Log out user and invalidate their session/token.

**Authentication:** Required

**Request Example:**
```
POST /api/auth/logout
Authorization: Bearer <token>
```

**Success Response (200):**
```json
{
  "success": true,
  "data": [],
  "message": "Logged out successfully"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 401 | Unauthorized | User not authenticated |

---

### Get Authenticated User

**Endpoint:** `GET /api/auth/me`

**Description:** Retrieve currently authenticated user's information.

**Authentication:** Required

**Request Example:**
```
GET /api/auth/me
Authorization: Bearer <token>
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "user_id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "phone": "1234567890",
    "is_verified": true,
    "is_active": true,
    "created_at": "2026-01-15 10:30:00",
    "updated_at": "2026-02-04 14:22:00"
  },
  "message": "User authenticated"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 401 | Unauthorized | Token invalid or expired |

---

### Verify Email

**Endpoint:** `POST /api/auth/verify-email`

**Description:** Verify user email using 6-digit code sent to email.

**Authentication:** Not required

**Request Body:**
- `email` (string, required): User's email address
- `code` (string, required): 6-digit verification code

**Request Example:**
```json
{
  "email": "user@example.com",
  "code": "123456"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "user_id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "is_verified": true
  },
  "message": "Email verified successfully! You can now login."
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 400 | Validation failed | Missing email or code |
| 400 | Invalid or expired verification code | Code doesn't match or expired |

---

### Resend Verification Code

**Endpoint:** `POST /api/auth/resend-code`

**Description:** Resend email verification code to user's email.

**Authentication:** Not required

**Request Body:**
- `email` (string, required): User's email address

**Request Example:**
```json
{
  "email": "user@example.com"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "email": "user@example.com"
  },
  "message": "A new verification code has been sent to your email"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 400 | Invalid email | Email format is invalid |
| 400 | User not found or resend rate limit exceeded | Email not registered or resend attempted too soon |

---

### Google OAuth Authentication

**Endpoint:** `POST /api/auth/google`

**Description:** Authenticate or register user via Google OAuth.

**Authentication:** Not required

**Request Body:**
- `email` (string, required): Google account email
- `google_id` (string, required): Google account ID
- `name` (string, optional): User's name from Google

**Request Example:**
```json
{
  "email": "user@gmail.com",
  "google_id": "1234567890",
  "name": "John Doe"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "user_id": 1,
    "name": "John Doe",
    "email": "user@gmail.com",
    "is_verified": true
  },
  "message": "Google authentication successful"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 400 | Missing required Google authentication data | Email or google_id missing |
| 400 | Google authentication failed | Invalid Google credentials |

---

### Forgot Password

**Endpoint:** `POST /api/auth/forgot-password`

**Description:** Initiate password reset process. Sends reset token via email.

**Authentication:** Not required

**Request Body:**
- `email` (string, required): User's email address

**Request Example:**
```json
{
  "email": "user@example.com"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": [],
  "message": "If that email is registered, a reset link has been sent."
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 400 | Invalid email format | Email validation failed |

**Note:** Response is generic for security (email enumeration prevention).

---

### Verify Reset Token

**Endpoint:** `POST /api/auth/verify-reset-token`

**Description:** Verify password reset token validity.

**Authentication:** Not required

**Request Body:**
- `token` (string, required): Password reset token from email

**Request Example:**
```json
{
  "token": "reset_token_here"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": [],
  "message": "Token verified successfully"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 400 | Token is required | Token parameter missing |
| 400 | Invalid or expired token | Token doesn't exist or expired |

---

### Reset Password

**Endpoint:** `POST /api/auth/reset-password`

**Description:** Reset user password using valid reset token.

**Authentication:** Not required

**Request Body:**
- `token` (string, required): Password reset token
- `password` (string, required): New password (minimum 6 characters)
- `confirm_password` (string, required): Password confirmation

**Request Example:**
```json
{
  "token": "reset_token_here",
  "password": "newPassword123",
  "confirm_password": "newPassword123"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": [],
  "message": "Password reset successfully"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 400 | Token is required | Token parameter missing |
| 400 | Validation failed | Password validation errors |
| 400 | Invalid or expired token | Token doesn't exist or expired |

---

## User Account Endpoints

### Get Account Information

**Endpoint:** `GET /api/auth/account`

**Description:** Retrieve user account profile information including address.

**Authentication:** Required

**Request Example:**
```
GET /api/auth/account
Authorization: Bearer <token>
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "user_id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "phone": "1234567890",
    "is_verified": true,
    "is_active": true,
    "address": {
      "id": 1,
      "street": "123 Main St",
      "city": "Lagos",
      "state": "Lagos",
      "postal_code": "100001",
      "country": "Nigeria"
    },
    "created_at": "2026-01-15 10:30:00",
    "updated_at": "2026-02-04 14:22:00"
  },
  "message": "Account information retrieved successfully"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 401 | Unauthorized | User not authenticated |
| 404 | User not found | User account doesn't exist |

---

### Update Account Information

**Endpoint:** `POST /api/auth/account/update`

**Description:** Update user account profile information (partial updates allowed).

**Authentication:** Required

**Request Body:** (All fields optional)
- `name` (string): Full name
- `phone` (string): Phone number
- `email` (string): Email address
- `address` (object): Address object with fields:
  - `street` (string)
  - `city` (string)
  - `state` (string)
  - `postal_code` (string)
  - `country` (string)

**Request Example:**
```json
{
  "name": "Jane Doe",
  "phone": "9876543210",
  "address": {
    "street": "456 Oak Ave",
    "city": "Abuja",
    "state": "FCT",
    "postal_code": "900001",
    "country": "Nigeria"
  }
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "user_id": 1,
    "name": "Jane Doe",
    "email": "user@example.com",
    "phone": "9876543210",
    "address": {
      "street": "456 Oak Ave",
      "city": "Abuja",
      "state": "FCT",
      "postal_code": "900001",
      "country": "Nigeria"
    }
  },
  "message": "Account updated successfully"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 401 | Unauthorized | User not authenticated |
| 400 | Validation failed | Invalid field values |

---

## User Dashboard Endpoint

### Get User Dashboard

**Endpoint:** `GET /api/dashboard`

**Description:** Retrieve user dashboard with profile and order statistics.

**Authentication:** Required

**Request Example:**
```
GET /api/dashboard
Authorization: Bearer <token>
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com",
      "phone": "1234567890",
      "is_verified": true
    },
    "orders_summary": {
      "total_orders": 5,
      "completed_orders": 4,
      "pending_orders": 1,
      "cancelled_orders": 0,
      "total_spent": 45000
    }
  },
  "message": "Dashboard stats retrieved successfully"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 401 | Unauthorized | User not authenticated |
| 404 | User not found | User account doesn't exist |

---

## User Payment Endpoints

### Proceed to Payment

**Endpoint:** `POST /api/proceed-to-payment`

**Description:** Initialize payment process. Validates cart and creates transaction record.

**Authentication:** Required

**Request Body:** (typically empty, uses user's cart)

**Request Example:**
```
POST /api/proceed-to-payment
Authorization: Bearer <token>
Content-Type: application/json
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "transaction_reference": "TXN-2026-0001",
    "amount": 45000,
    "payment_url": "https://checkout.paystack.com/...",
    "authorization_url": "https://checkout.paystack.com/..."
  },
  "message": "Payment initialized successfully"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 401 | Unauthorized | User not authenticated |
| 400 | Cart not found | User's cart doesn't exist |
| 400 | Invalid cart amount | Cart total is zero or negative |
| 400 | Cart validation failed | Products unavailable or out of stock |

---

### Verify Payment

**Endpoint:** `POST /api/verify-payment/{reference}`

**Description:** Verify payment transaction and create order if successful.

**Authentication:** Required

**URL Parameters:**
- `reference` (string, optional): Payment reference from Paystack

**Query Parameters:**
- `reference` (string, optional): Payment reference as query parameter

**Request Example:**
```
POST /api/verify-payment/TXN-2026-0001
Authorization: Bearer <token>
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "order_id": 45,
    "order_number": "ORD-001045",
    "status": "completed",
    "transaction_reference": "TXN-2026-0001",
    "amount": 45000,
    "message": "Payment verified and order created"
  },
  "message": "Payment verified successfully"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 401 | Unauthorized | User not authenticated |
| 400 | Transaction reference is required | Reference parameter missing |
| 400 | Transaction not found | Reference doesn't match any transaction |
| 400 | Payment verification failed | Payment wasn't successful |

---

## Admin Authentication Endpoints

### Admin Login

**Endpoint:** `POST /api/admin/auth/login`

**Description:** Authenticate admin user with email and password.

**Authentication:** Not required

**Request Body:**
- `email` (string, required): Admin email address
- `password` (string, required): Admin password

**Request Example:**
```json
{
  "email": "admin@example.com",
  "password": "adminPassword123"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "admin_id": 1,
    "name": "Admin User",
    "email": "admin@example.com",
    "api_token": "admin_token_here"
  },
  "message": "Login successful"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 400 | Validation failed | Missing email or password |
| 400 | Invalid credentials | Email/password don't match |
| 429 | Account blocked due to too many login attempts | Too many failed attempts (max 5 per 15 min) |

---

### Get Authenticated Admin

**Endpoint:** `GET /api/admin/auth/me`

**Description:** Retrieve currently authenticated admin's information.

**Authentication:** Required

**Request Example:**
```
GET /api/admin/auth/me
Authorization: Bearer <admin_token>
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "admin_id": 1,
    "name": "Admin User",
    "email": "admin@example.com",
    "created_at": "2026-01-15 10:30:00",
    "updated_at": "2026-02-04 14:22:00"
  },
  "message": "Admin authenticated"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 401 | Unauthorized | Admin not authenticated or token invalid |

---

### Admin Logout

**Endpoint:** `POST /api/admin/auth/logout`

**Description:** Log out admin and invalidate their session.

**Authentication:** Required

**Request Example:**
```
POST /api/admin/auth/logout
Authorization: Bearer <admin_token>
```

**Success Response (200):**
```json
{
  "success": true,
  "data": [],
  "message": "Logout successful"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 401 | Unauthorized | Admin not authenticated |

---

### Admin Forgot Password

**Endpoint:** `POST /api/admin/auth/forgot-password`

**Description:** Initiate admin password reset.

**Authentication:** Not required

**Request Body:**
- `email` (string, required): Admin email address

**Request Example:**
```json
{
  "email": "admin@example.com"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": [],
  "message": "If that email is registered, a reset link has been sent."
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 400 | Invalid email | Email validation failed |

---

### Admin Verify Reset Token

**Endpoint:** `POST /api/admin/auth/verify-reset-token`

**Description:** Verify admin password reset token.

**Authentication:** Not required

**Request Body:**
- `token` (string, required): Reset token

**Request Example:**
```json
{
  "token": "reset_token_here"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": [],
  "message": "Token verified successfully"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 400 | Token is required | Token parameter missing |
| 400 | Invalid or expired token | Token invalid or expired |

---

### Admin Reset Password

**Endpoint:** `POST /api/admin/auth/reset-password`

**Description:** Reset admin password with valid token.

**Authentication:** Not required

**Request Body:**
- `token` (string, required): Reset token
- `password` (string, required): New password (minimum 6 characters)
- `confirm_password` (string, required): Password confirmation

**Request Example:**
```json
{
  "token": "reset_token_here",
  "password": "newPassword123",
  "confirm_password": "newPassword123"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": [],
  "message": "Password reset successfully"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 400 | Token is required | Token parameter missing |
| 400 | Validation failed | Password validation errors |
| 400 | Invalid or expired token | Token invalid or expired |

---

## Admin Dashboard Endpoint

### Get Admin Dashboard

**Endpoint:** `GET /api/admin/dashboard`

**Description:** Retrieve admin dashboard with business statistics.

**Authentication:** Required

**Request Example:**
```
GET /api/admin/dashboard
Authorization: Bearer <admin_token>
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "admin": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@example.com",
      "phone": "1234567890",
      "is_verified": true
    },
    "orders_summary": {
      "recent_orders_count": 12,
      "total_revenue": 450000,
      "total_users": 156,
      "recent_orders": [
        {
          "id": 45,
          "order_number": "ORD-001045",
          "total_amount": 15000,
          "status": "completed",
          "created_at": "2026-02-04 14:30:00"
        }
      ]
    }
  },
  "message": "Dashboard stats retrieved successfully"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 401 | Unauthorized | Admin not authenticated |
| 404 | Admin not found | Admin account doesn't exist |

---

## Admin Notifications Endpoints

### Get All Notifications

**Endpoint:** `GET /api/admin/notifications`

**Description:** Retrieve paginated and grouped notifications.

**Authentication:** Required

**Query Parameters:**
- `page` (integer, optional): Page number (default: 1)
- `per_page` (integer, optional): Notifications per page (default: 20)
- `status` (string, optional): Filter by 'unread' or 'read'

**Request Example:**
```
GET /api/admin/notifications?page=1&per_page=20
Authorization: Bearer <admin_token>
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "notifications": [
      {
        "id": 1,
        "type": "order_created",
        "message": "New order #ORD-001045 created",
        "read_at": null,
        "created_at": "2026-02-04 14:30:00",
        "data": {
          "order_id": 45
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 45,
      "total_pages": 3
    }
  },
  "message": "Notifications fetched successfully"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 401 | Unauthorized | Admin not authenticated |

---

### Get Unread Notifications

**Endpoint:** `GET /api/admin/notifications/unread`

**Description:** Retrieve only unread notifications.

**Authentication:** Required

**Request Example:**
```
GET /api/admin/notifications/unread
Authorization: Bearer <admin_token>
```

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "type": "order_created",
      "message": "New order #ORD-001045 created",
      "read_at": null,
      "created_at": "2026-02-04 14:30:00"
    }
  ],
  "message": "Unread notifications fetched successfully"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 401 | Unauthorized | Admin not authenticated |

---

### Get Read Notifications

**Endpoint:** `GET /api/admin/notifications/read`

**Description:** Retrieve only read notifications.

**Authentication:** Required

**Request Example:**
```
GET /api/admin/notifications/read
Authorization: Bearer <admin_token>
```

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 2,
      "type": "payment_received",
      "message": "Payment received for order #ORD-001044",
      "read_at": "2026-02-04 12:00:00",
      "created_at": "2026-02-04 12:15:00"
    }
  ],
  "message": "Read notifications fetched successfully"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 401 | Unauthorized | Admin not authenticated |

---

### Mark Notification as Read

**Endpoint:** `POST /api/admin/notifications/mark-as-read/{id}`

**Description:** Mark a single notification as read.

**Authentication:** Required

**URL Parameters:**
- `id` (integer, required): Notification ID

**Request Example:**
```
POST /api/admin/notifications/mark-as-read/1
Authorization: Bearer <admin_token>
```

**Success Response (200):**
```json
{
  "success": true,
  "data": null,
  "message": "Notification marked as read"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 401 | Unauthorized | Admin not authenticated |
| 404 | Notification not found | Notification doesn't exist |

---

### Mark All Notifications as Read

**Endpoint:** `POST /api/admin/notifications/mark-all-as-read`

**Description:** Mark all notifications as read.

**Authentication:** Required

**Request Example:**
```
POST /api/admin/notifications/mark-all-as-read
Authorization: Bearer <admin_token>
```

**Success Response (200):**
```json
{
  "success": true,
  "data": null,
  "message": "All notifications marked as read"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 401 | Unauthorized | Admin not authenticated |

---

## Admin Products Endpoints

### List All Products

**Endpoint:** `GET /api/admin/products`

**Description:** Retrieve paginated list of all products with admin details.

**Authentication:** Required

**Query Parameters:**
- `page` (integer, optional): Page number (default: 1)
- `per_page` (integer, optional): Products per page (default: 10)

**Request Example:**
```
GET /api/admin/products?page=1&per_page=10
Authorization: Bearer <admin_token>
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "products": [
      {
        "id": 1,
        "product_code": "PROD-001",
        "name": "Wireless Mouse",
        "description": "Ergonomic wireless mouse",
        "price": 4500,
        "cost_price": 2000,
        "quantity": 50,
        "sku": "MOUSE-001",
        "status": "active",
        "created_at": "2026-01-15 10:30:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 10,
      "total": 45,
      "total_pages": 5
    }
  },
  "message": "Products fetched successfully"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 401 | Unauthorized | Admin not authenticated |

---

### Get Product Details

**Endpoint:** `GET /api/admin/products/{id}`

**Description:** Retrieve detailed information about a specific product.

**Authentication:** Required

**URL Parameters:**
- `id` (integer, required): Product ID

**Request Example:**
```
GET /api/admin/products/1
Authorization: Bearer <admin_token>
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "product_code": "PROD-001",
    "name": "Wireless Mouse",
    "description": "Ergonomic wireless mouse with 2.4GHz connectivity",
    "category_id": 5,
    "category": "Electronics",
    "price": 4500,
    "cost_price": 2000,
    "quantity": 50,
    "sku": "MOUSE-001",
    "image_url": "/images/mouse.jpg",
    "status": "active",
    "created_at": "2026-01-15 10:30:00",
    "updated_at": "2026-02-04 14:22:00"
  },
  "message": "Product fetched successfully"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 401 | Unauthorized | Admin not authenticated |
| 404 | Product not found | Product doesn't exist |

---

### Get Next Product ID

**Endpoint:** `GET /api/admin/products/next-id`

**Description:** Retrieve the next available product ID for creation.

**Authentication:** Required

**Request Example:**
```
GET /api/admin/products/next-id
Authorization: Bearer <admin_token>
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "next_product_id": 47
  },
  "message": "Next Product ID fetched successfully"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 401 | Unauthorized | Admin not authenticated |

---

### Create Product

**Endpoint:** `POST /api/admin/products/store`

**Description:** Create a new product in the system.

**Authentication:** Required

**Request Body:**
- `name` (string, required): Product name (max 255 characters)
- `description` (string, optional): Product description
- `price` (number, required): Selling price (must be positive)
- `cost_price` (number, optional): Cost price
- `category_id` (integer, required): Category ID
- `quantity` (integer, required): Stock quantity
- `sku` (string, optional): Stock keeping unit (must be unique)
- `image_url` (string, optional): Product image URL
- `status` (string, optional): 'active' or 'inactive' (default: 'active')

**Request Example:**
```json
{
  "name": "Mechanical Keyboard",
  "description": "RGB mechanical gaming keyboard",
  "price": 12000,
  "cost_price": 5000,
  "category_id": 5,
  "quantity": 30,
  "sku": "KEYBOARD-001",
  "image_url": "/images/keyboard.jpg",
  "status": "active"
}
```

**Success Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 46,
    "product_code": "PROD-046",
    "name": "Mechanical Keyboard",
    "description": "RGB mechanical gaming keyboard",
    "price": 12000,
    "cost_price": 5000,
    "category_id": 5,
    "quantity": 30,
    "sku": "KEYBOARD-001",
    "image_url": "/images/keyboard.jpg",
    "status": "active",
    "created_at": "2026-02-04 15:45:00"
  },
  "message": "Product Created Successfully"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 401 | Unauthorized | Admin not authenticated |
| 400 | Required fields missing | Validation failed, missing required fields |
| 400 | Validation failed | Invalid field values or format |
| 400 | SKU already exists | SKU is not unique |

---

### Update Product

**Endpoint:** `POST /api/admin/products/update/{id}`

**Description:** Update an existing product (partial updates allowed).

**Authentication:** Required

**URL Parameters:**
- `id` (integer, required): Product ID

**Request Body:** (All fields optional, only include fields to update)
- `name` (string): Product name
- `description` (string): Product description
- `price` (number): Selling price
- `cost_price` (number): Cost price
- `category_id` (integer): Category ID
- `quantity` (integer): Stock quantity
- `sku` (string): Stock keeping unit
- `image_url` (string): Product image URL
- `status` (string): 'active' or 'inactive'

**Request Example:**
```json
{
  "price": 13500,
  "quantity": 25,
  "status": "active"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 46,
    "product_code": "PROD-046",
    "name": "Mechanical Keyboard",
    "price": 13500,
    "quantity": 25,
    "status": "active",
    "updated_at": "2026-02-04 16:30:00"
  },
  "message": "Product Updated Successfully"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 401 | Unauthorized | Admin not authenticated |
| 404 | Product not found | Product doesn't exist |
| 400 | Validation failed | Invalid field values |

---

### Delete Product

**Endpoint:** `POST /api/admin/products/delete/{id}`

**Description:** Delete a product from the system.

**Authentication:** Required

**URL Parameters:**
- `id` (integer, required): Product ID

**Request Example:**
```
POST /api/admin/products/delete/46
Authorization: Bearer <admin_token>
```

**Success Response (200):**
```json
{
  "success": true,
  "data": null,
  "message": "Product deleted successfully"
}
```

**Error Responses:**

| Status | Error Message | Cause |
|--------|---------------|-------|
| 401 | Unauthorized | Admin not authenticated |
| 404 | Product not found | Product doesn't exist |

---

## Response Format & Status Codes

### Standard Success Response
```json
{
  "success": true,
  "data": {},
  "message": "Operation successful"
}
```

### Standard Error Response
```json
{
  "success": false,
  "message": "Error description",
  "statusCode": 400,
  "errors": {
    "field_name": "Field error message"
  }
}
```

### HTTP Status Codes

| Code | Meaning | Usage |
|------|---------|-------|
| 200 | OK | Successful GET/POST request |
| 201 | Created | Successful resource creation |
| 400 | Bad Request | Invalid request, validation error, or business logic error |
| 401 | Unauthorized | Authentication required or invalid token |
| 404 | Not Found | Resource doesn't exist |
| 409 | Conflict | Resource conflict (e.g., duplicate email) |
| 429 | Too Many Requests | Rate limit exceeded (login attempts) |

### Authentication

Include API token in request header for protected endpoints:

```
Authorization: Bearer <api_token>
```

### Rate Limiting

- **Login endpoints**: Max 5 attempts per IP per 15 minutes
- After exceeding limit: Account temporarily blocked

### Notes

- All timestamps are in UTC timezone
- Passwords minimum 6 characters
- Email addresses must be valid and unique
- All error responses return formatted error messages
- No 500 status codes returned (errors formatted as 400)

---

*Last Updated: February 4, 2026*  
*API Version: 1.0*  
*Total Endpoints: 48*
