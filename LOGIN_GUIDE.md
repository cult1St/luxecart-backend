# Login Endpoint Guide

## Quick Summary

Users who are **already verified** can now log in with email and password.

---

## 📍 API Endpoint

**POST** `/api/auth/login`

---

## 📤 Request Body

```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

### Required Fields
- `email` - User's email address (must be valid email format)
- `password` - User's password (minimum 6 characters)

---

## ✅ Success Response (200)

```json
{
  "success": true,
  "data": {
    "user_id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "is_verified": true
  },
  "message": "Login successful"
}
```

---

## ❌ Error Responses

### Invalid Credentials (401)
```json
{
  "success": false,
  "message": "Invalid credentials provided",
  "data": [],
  "code": 401
}
```

### Email Not Verified (401)
```json
{
  "success": false,
  "message": "Email not verified. Please verify your email before logging in.",
  "data": [],
  "code": 401
}
```

### Too Many Attempts (429)
```json
{
  "success": false,
  "message": "Too many login attempts. Please try again in 15 minutes.",
  "data": [],
  "code": 429
}
```

### Validation Error (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "data": {
    "email": "Email format is invalid",
    "password": "Password is required"
  },
  "code": 422
}
```

---

## 🔒 Security Features

| Feature | Details |
|---------|---------|
| **Rate Limiting** | Max 5 attempts per 15 minutes per IP |
| **Password Hashing** | Checked using bcrypt verify |
| **Email Verification** | User MUST be verified before login |
| **Generic Errors** | Doesn't reveal if email exists or password wrong |
| **Input Validation** | Email format, password length checked |

---

## 🧪 Test with Postman

### Setup
1. Open Postman
2. Create new request
3. Set method to **POST**
4. Set URL to: `http://localhost/frisan/api/auth/login`

### Test Case 1: Successful Login
**Headers:**
```
Content-Type: application/json
```

**Body (raw JSON):**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Expected:** Status 200 with user data

### Test Case 2: Wrong Password
**Body (raw JSON):**
```json
{
  "email": "john@example.com",
  "password": "wrongpassword"
}
```

**Expected:** Status 401 with "Invalid credentials provided"

### Test Case 3: Invalid Email Format
**Body (raw JSON):**
```json
{
  "email": "not-an-email",
  "password": "password123"
}
```

**Expected:** Status 422 with validation error

### Test Case 4: Rate Limiting
Make 6+ login requests rapidly (5 failures)

**Expected:** 6th request returns Status 429 "Too many login attempts"

---

## 📝 Flow Diagram

```
User submits (email, password)
       ↓
Check rate limiting
       ↓
Validate input (format, length)
       ↓
Find user by email
       ↓
Verify password (bcrypt)
       ↓
Check if verified
       ↓
Return success or error
```

---

## 🎯 Status Codes

| Code | Meaning |
|------|---------|
| 200 | Login successful |
| 401 | Invalid credentials or not verified |
| 422 | Validation failed |
| 429 | Rate limited (too many attempts) |
| 500 | Server error |

---

## 💡 Key Points

✓ Users MUST verify email first (use signup → verify-email flow first)
✓ Password is checked securely with bcrypt
✓ Error messages are intentionally generic for security
✓ Rate limiting prevents brute force attacks
✓ IP address is detected (including Cloudflare)

---

## 🔄 Complete User Flow

```
1. User signs up (/api/auth/signup)
   ↓
2. Email verification code sent
   ↓
3. User verifies email (/api/auth/verify-email)
   ↓
4. User can now login (/api/auth/login)  ← YOU ARE HERE
   ↓
5. Return user data to frontend
   ↓
6. Frontend redirects to dashboard
```

---

## ⚙️ How Login Works (Behind the Scenes)

1. **Sanitize Input:** Email lowercased, whitespace trimmed
2. **Validate Input:** Email format & password length checked
3. **Rate Limit Check:** Verify user hasn't exceeded attempt limit
4. **Find User:** Query database for user by email
5. **Verify Password:** Use bcrypt to safely compare passwords
6. **Check Verification:** Ensure email is marked as verified
7. **Log Activity:** Record successful login to logs
8. **Return Response:** Send user data back to frontend

---

## 🚀 Ready to Test!

Login system is complete and secure. Test all 4 cases above in Postman!

**Remember:** Users must complete signup → email verification → login flow.
