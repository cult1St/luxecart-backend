# Frisan API - Quick Reference Guide

## 🚀 Base URL
```
http://localhost:8000/api
```

---

## 👤 USER ENDPOINTS

### Authentication
```
POST /auth/login
POST /auth/signup
POST /auth/verify-email
POST /auth/resend-code
```

### Password Recovery
```
POST /auth/forgot-password
POST /auth/verify-reset-token
POST /auth/reset-password
```

### Dashboard
```
GET /dashboard [AUTH REQUIRED]
```

---

## 🔐 ADMIN ENDPOINTS

### Authentication
```
POST /admin/auth/login
GET /admin/auth/me [AUTH REQUIRED]
POST /admin/auth/logout [AUTH REQUIRED]
```

### Password Recovery
```
POST /admin/auth/forgot-password
POST /admin/auth/verify-reset-token
POST /admin/auth/reset-password
```

### Dashboard
```
GET /admin/dashboard [AUTH REQUIRED]
```

### Notifications
```
GET /admin/notifications [AUTH REQUIRED]
GET /admin/notifications/unread [AUTH REQUIRED]
GET /admin/notifications/read [AUTH REQUIRED]
POST /admin/notifications/mark-as-read/{id} [AUTH REQUIRED]
POST /admin/notifications/mark-all-as-read [AUTH REQUIRED]
```

### Products
```
GET /admin/products [AUTH REQUIRED]
GET /admin/products/{id} [AUTH REQUIRED]
GET /admin/products/next-id [AUTH REQUIRED]
POST /admin/products/store [AUTH REQUIRED]
POST /admin/products/update/{id} [AUTH REQUIRED]
POST /admin/products/delete/{id} [AUTH REQUIRED]
```

---

## 📝 COMMON REQUEST HEADERS

### For Protected Endpoints
```
Authorization: Bearer <token>
Content-Type: application/json
```

### Example cURL Commands

**User Login:**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password123"}'
```

**Admin Dashboard:**
```bash
curl -X GET http://localhost:8000/api/admin/dashboard \
  -H "Authorization: Bearer <admin_token>"
```

**Create Product:**
```bash
curl -X POST http://localhost:8000/api/admin/products/store \
  -H "Authorization: Bearer <admin_token>" \
  -H "Content-Type: application/json" \
  -d '{
    "name":"Product Name",
    "price":5000,
    "category_id":1,
    "quantity":10
  }'
```

---

## ✅ HTTP METHODS

| Method | Usage |
|--------|-------|
| GET | Retrieve data |
| POST | Create/Update/Delete data |

---

## 📊 RESPONSE CODES

| Code | Status |
|------|--------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Rate Limited |
| 500 | Server Error |

---

## 🔑 AUTHENTICATION FLOW

### User Login Flow
```
1. POST /auth/signup → Verify Email
2. POST /auth/verify-email (with code) → Ready to Login
3. POST /auth/login → Get API Token
4. Use token in Authorization header
```

### Forgot Password Flow
```
1. POST /auth/forgot-password
2. Check email for token
3. POST /auth/verify-reset-token
4. POST /auth/reset-password
```

---

## 📋 REQUIRED FIELDS BY ENDPOINT

### User Login
```json
{
  "email": "string",
  "password": "string"
}
```

### User Signup
```json
{
  "email": "string",
  "password": "string (min 6 chars)",
  "confirm_password": "string",
  "name": "string"
}
```

### Create Product
```json
{
  "name": "string",
  "price": "number",
  "category_id": "integer",
  "quantity": "integer"
}
```

### Forgot Password
```json
{
  "email": "string"
}
```

### Reset Password
```json
{
  "token": "string",
  "password": "string (min 6 chars)",
  "confirm_password": "string"
}
```

---

## 🔍 QUERY PARAMETERS

### Pagination
```
?page=1&per_page=20
```

### Notification Filter
```
?status=unread  (or read)
```

---

## 💡 QUICK TIPS

1. **Store tokens securely** after login
2. **Always include Content-Type: application/json** for POST requests
3. **Check response status codes** for operation success
4. **Rate limiting**: 5 login attempts per 15 minutes
5. **Token expiration**: Check your implementation
6. **Error messages**: Read detailed error responses
7. **Email format**: Use valid email addresses
8. **Passwords**: Minimum 6 characters

---

## 🛠️ POSTMAN VARIABLES

```
{{base_url}}      → Your API URL
{{user_token}}    → User authentication token
{{admin_token}}   → Admin authentication token
```

Set these in Postman's Environment settings.

---

## 📱 JAVASCRIPT/FETCH EXAMPLES

### User Login
```javascript
const response = await fetch('http://localhost:8000/api/auth/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    email: 'user@example.com',
    password: 'password123'
  })
});
const data = await response.json();
console.log(data.data.api_token); // Store this token
```

### Protected Request
```javascript
const response = await fetch('http://localhost:8000/api/dashboard', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});
const data = await response.json();
```

### Create Product
```javascript
const response = await fetch('http://localhost:8000/api/admin/products/store', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${adminToken}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    name: 'New Product',
    price: 5000,
    category_id: 1,
    quantity: 10
  })
});
const data = await response.json();
```

---

## 🐍 PYTHON REQUESTS EXAMPLES

### User Login
```python
import requests

response = requests.post('http://localhost:8000/api/auth/login', json={
    'email': 'user@example.com',
    'password': 'password123'
})
data = response.json()
token = data['data']['api_token']
```

### Protected Request
```python
headers = {
    'Authorization': f'Bearer {token}',
    'Content-Type': 'application/json'
}
response = requests.get('http://localhost:8000/api/dashboard', headers=headers)
data = response.json()
```

---

## ⚡ COMMON ERRORS & SOLUTIONS

| Error | Cause | Solution |
|-------|-------|----------|
| 401 Unauthorized | Invalid/expired token | Re-login and get new token |
| 422 Validation Error | Missing/invalid fields | Check required fields |
| 429 Too Many Requests | Rate limited | Wait before retrying |
| 404 Not Found | Wrong endpoint/ID | Verify endpoint and IDs |
| 500 Server Error | Server issue | Check server logs |

---

## 📞 SUPPORT

For detailed information:
- Full docs: See `API_DOCUMENTATION.md`
- Setup guide: See `POSTMAN_SETUP_GUIDE.md`
- Summary: See `DOCUMENTATION_SUMMARY.md`

---

**Version:** 1.0  
**Last Updated:** February 4, 2026

Keep this handy while developing! 🚀
