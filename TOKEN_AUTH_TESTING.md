# Token-Based Authentication Testing Guide

## Overview
Your application uses **Bearer Token Authentication**, NOT sessions. This means every protected endpoint requires a valid token in the Authorization header.

---

## How Token Auth Works

1. **Login** → Get `api_token` from response
2. **Store** → Save the token (in frontend, mobile app, etc.)
3. **Request** → Include token in Authorization header for protected endpoints
4. **Validate** → Backend checks token validity, expiry, and IP

---

## Testing with cURL

### 1. Login to Get Token

```bash
curl -X POST http://localhost/frisan/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user_id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "api_token": "abc123def456ghi789jkl012mno345pqr",
    "is_verified": true
  },
  "message": "Login successful",
  "status": 200
}
```

### 2. Test Protected Endpoint (/api/auth/me) - ✅ CORRECT WAY

```bash
curl -X GET http://localhost/frisan/api/auth/me \
  -H "Authorization: Bearer abc123def456ghi789jkl012mno345pqr"
```

**Response (Success):**
```json
{
  "success": true,
  "data": {
    "user_id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "phone": "+1234567890",
    "is_verified": true,
    "is_active": true,
    "created_at": "2026-01-28 10:30:00",
    "updated_at": "2026-01-28 10:30:00"
  },
  "message": "User authenticated",
  "status": 200
}
```

### 3. What Happens Without Token - ❌ WRONG WAY

```bash
# NO Authorization header
curl -X GET http://localhost/frisan/api/auth/me
```

**Response (Error):**
```json
{
  "success": false,
  "message": "Unauthorized",
  "errors": [],
  "status": 401
}
```

This is the error you were seeing because you weren't sending the token!

---

## Testing with Postman

### Setup:
1. **Login request** (POST `http://localhost/frisan/api/auth/login`)
   - Copy the `api_token` value

2. **Protected request** (GET `http://localhost/frisan/api/auth/me`)
   - Go to **Headers** tab
   - Add header: `Authorization: Bearer YOUR_TOKEN_HERE`
   - Send request

### Or Use Postman's Auth Tab:
1. Select **Bearer Token** from the Auth dropdown
2. Paste your token
3. Send the request

---

## Testing with Insomnia

1. Login and copy token
2. Go to protected endpoint
3. Click **Auth** tab
4. Select **Bearer Token**
5. Paste token in the input field

---

## JavaScript/Fetch Example

```javascript
// 1. Login
const loginResponse = await fetch('http://localhost/frisan/api/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: 'user@example.com',
    password: 'password123'
  })
});

const loginData = await loginResponse.json();
const token = loginData.data.api_token;

// 2. Use token for protected endpoint
const meResponse = await fetch('http://localhost/frisan/api/auth/me', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`
  }
});

const userData = await meResponse.json();
console.log(userData);
```

---

## Token Details

- **Duration**: 2 hours (from login time)
- **Format**: Bearer token (32-byte hex string)
- **Stored**: Hashed in database (`api_tokens` table)
- **IP Check**: Optional - validates request IP matches login IP
- **Expiry**: After 2 hours, token becomes invalid

---

## Common Issues

### Issue: "Unauthorized" on protected endpoints
**Solution**: Include Authorization header with valid token
```bash
# ❌ WRONG
curl http://localhost/frisan/api/auth/me

# ✅ CORRECT
curl -H "Authorization: Bearer YOUR_TOKEN" http://localhost/frisan/api/auth/me
```

### Issue: Token expired
**Solution**: Login again to get a new token (valid for 2 hours)

### Issue: Invalid token format
**Solution**: Token must be exactly as returned from login, without modifications

### Issue: Different IP than login
**Solution**: IP check may fail if request IP differs from login IP. Check:
```php
// In AuthService::validateToken()
// IP validation is configured here
```

---

## All Token-Protected Endpoints

These endpoints require the Authorization header:

- `GET /api/auth/me` - Get current user info
- `GET /api/account` - Get account details
- `POST /api/account/update` - Update account
- `GET /api/dashboard` - Dashboard (if protected)
- `POST /api/auth/logout` - Logout

---

## Admin Token Auth

Same process but for admin:

```bash
# Admin login
curl -X POST http://localhost/frisan/api/admin/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "adminpass"
  }'

# Use admin token for protected endpoints
curl -X GET http://localhost/frisan/api/admin/auth/me \
  -H "Authorization: Bearer ADMIN_TOKEN"
```

---

## Summary

Your `/api/auth/me` wasn't returning user data because:
- ✅ **You got the token** from login successfully
- ❌ **You didn't send it** with the `/api/auth/me` request
- ✅ **Code is working correctly** - it returns 401 when no token is provided

Now test it with the token in the Authorization header and it will work!
