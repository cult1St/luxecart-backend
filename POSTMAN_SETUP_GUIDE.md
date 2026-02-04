# Postman Collection Setup Guide

## Overview

This guide explains how to import and use the Frisan E-Commerce API Postman Collection.

## Prerequisites

- **Postman** installed (Download from [postman.com](https://www.postman.com))
- **Frisan API** running locally or on a server
- Valid credentials for testing (admin and user accounts)

## Importing the Collection

### Method 1: Import from File

1. Open Postman
2. Click **File** → **Import**
3. Select **File** tab
4. Click **Upload Files** and select `Frisan_API_Collection.postman_collection.json`
5. Click **Import**

### Method 2: Paste Raw JSON

1. Open Postman
2. Click **File** → **Import**
3. Select **Raw text** tab
4. Copy-paste the entire JSON from the collection file
5. Click **Import**

## Setting Up Environment Variables

The collection uses three main variables that need to be configured:

### 1. **base_url**
   - Default: `http://localhost:8000`
   - Change this if your API is hosted elsewhere

### 2. **user_token**
   - Obtained after logging in as a user
   - Leave empty initially; will be set after first user login

### 3. **admin_token**
   - Obtained after logging in as an admin
   - Leave empty initially; will be set after first admin login

### How to Set Variables

1. Click the **Environment** dropdown at the top right (currently shows "No Environment")
2. Click **Manage Environments** 
3. Click **Create New Environment**
4. Name it "Frisan API"
5. Add these variables:
   | Variable | Initial Value | Type |
   |----------|---------------|------|
   | base_url | http://localhost:8000 | string |
   | user_token | (empty) | string |
   | admin_token | (empty) | string |
6. Click **Save**
7. Select "Frisan API" from the Environment dropdown

## Testing Workflow

### Step 1: Admin Login
1. Navigate to **Admin Authentication** → **Admin Login**
2. Update the request body with valid admin credentials
3. Click **Send**
4. Copy the `api_token` from the response
5. In the **Environment**, paste the token into `admin_token` variable
6. Click **Save**

### Step 2: User Login
1. Navigate to **User Authentication** → **User Login**
2. Update the request body with valid user credentials
3. Click **Send**
4. Copy the `api_token` from the response
5. In the **Environment**, paste the token into `user_token` variable
6. Click **Save**

### Step 3: Test Protected Endpoints

Now you can test protected endpoints:

#### For Admin Endpoints:
- Go to **Admin Dashboard** → **Get Dashboard**
- The `{{admin_token}}` will be automatically included
- Click **Send**

#### For User Endpoints:
- Go to **User Dashboard** → **Get Dashboard**
- The `{{user_token}}` will be automatically included
- Click **Send**

## Collection Structure

### 📁 User Authentication
- User Login
- User Signup
- Verify Email
- Resend Verification Code

### 📁 User Password Recovery
- Forgot Password
- Verify Reset Token
- Reset Password

### 📁 User Dashboard
- Get Dashboard

### 📁 Admin Authentication
- Admin Login
- Get Authenticated Admin
- Admin Logout

### 📁 Admin Password Recovery
- Admin Forgot Password
- Admin Verify Reset Token
- Admin Reset Password

### 📁 Admin Dashboard
- Get Dashboard

### 📁 Admin Notifications
- Get All Notifications
- Get Unread Notifications
- Get Read Notifications
- Mark Notification as Read (change ID)
- Mark All Notifications as Read

### 📁 Admin Products
- List All Products (pagination supported)
- Get Product Details (change ID)
- Create Product
- Update Product (change ID and update fields)
- Delete Product (change ID)
- Get Next Product ID

## Common Parameters to Modify

### Pagination
Many endpoints support pagination:
```
?page=1&per_page=20
```

### Notification Status Filter
```
?status=unread    # or
?status=read
```

### Product IDs
In endpoints like:
- `GET /api/admin/products/{{product_id}}`
- `POST /api/admin/products/update/{{product_id}}`

Replace `{{product_id}}` with the actual product ID in the URL.

## Testing Tips

1. **Auto-refresh tokens**: After login, manually copy the new token to the environment
2. **Check Status Codes**: Verify the response status code (200, 201, 400, 401, etc.)
3. **Read Error Messages**: API returns detailed error messages in the response
4. **Use Tests**: Add Postman tests to validate responses automatically
5. **Monitor Network**: Use Postman's console to see request/response details

## Example Test Sequence

1. **Admin Setup**
   - Admin Login → Copy token to `admin_token`
   - Get Admin Dashboard
   - List Products
   - Create a Product
   - Update the Product
   - Delete the Product

2. **User Setup**
   - User Signup → Check email for verification code
   - Verify Email → Copy token to `user_token`
   - User Login (with another request)
   - Get User Dashboard

3. **Notifications (Admin)**
   - Get All Notifications
   - Get Unread Notifications
   - Mark specific notification as read
   - Mark all as read

## Authentication in Requests

All protected endpoints automatically include the token from the environment variable:

```
Authorization: Bearer {{admin_token}}
```

or

```
Authorization: Bearer {{user_token}}
```

The `{{variable_name}}` syntax is Postman's way of injecting variables.

## Troubleshooting

### 401 Unauthorized
- **Problem**: Token expired or invalid
- **Solution**: Re-login and update the token in environment variables

### 422 Validation Error
- **Problem**: Invalid request body
- **Solution**: Check the request body against the documentation

### 429 Too Many Requests
- **Problem**: Rate limit exceeded
- **Solution**: Wait for the specified time before retrying

### 404 Not Found
- **Problem**: Resource doesn't exist
- **Solution**: Check if the ID/parameter is correct

### 500 Server Error
- **Problem**: Server-side error
- **Solution**: Check server logs and try again

## Additional Resources

- **Full API Documentation**: See `API_DOCUMENTATION.md`
- **API Endpoints Summary**: Check routes.php in the project
- **Request/Response Examples**: Included in each request

## Notes

- All timestamps are in UTC format
- Passwords should be minimum 6 characters
- Email addresses must be valid and unique
- Product IDs must be positive integers
- Tokens may have expiration times - check your implementation

---

**Happy Testing!** 🚀

For issues or questions, refer to the full API documentation or check the server logs.
