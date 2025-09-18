# Development Token Generation Guide

This guide explains how to generate authentication tokens for testing when the mail server is unavailable.

## Overview

I've added development-only endpoints that bypass email verification and OTP requirements, allowing you to generate tokens and test APIs without needing email access.

## ⚠️ Security Notice

These endpoints are **ONLY available in development/testing environments**. They will return a 403 error in production for security reasons.

## New Development Endpoints

### 1. Generate Test Token
**POST** `/api/v1/auth/generate-test-token`

The easiest way to get a token for testing. This endpoint:
- Bypasses all email verification
- Automatically enables 2FA for the user
- Generates a ready-to-use token
- Can optionally set/change user type

### 2. Development OTP Bypass
**POST** `/api/v1/auth/verify-otp-dev`

Accepts any 6-digit code as valid for OTP verification.

### 3. Development Login OTP Bypass
**POST** `/api/v1/auth/verify-login-otp-dev`

Accepts any 6-digit code as valid for login OTP verification.

## Quick Start Guide

### Method 1: Generate Test Token (Recommended)

This is the fastest way to get a working admin token:

1. **Open Postman** and go to: `Development/Testing Authentication > Generate Admin Test Token`

2. **Request Body**:
   ```json
   {
     "email": "admin@example.com",
     "user_type": "admin"
   }
   ```

3. **Send the request**. You'll get a response like:
   ```json
   {
     "message": "Test token generated successfully",
     "user": {
       "id": 1,
       "first_name": "Admin",
       "last_name": "User",
       "email": "admin@example.com",
       "user_type": "admin",
       "two_factor_enabled": true,
       "email_verified": true
     },
     "token": "1|abcdef123456...",
     "token_type": "Bearer"
   }
   ```

4. **Copy the token** and update your `auth_token` environment variable in Postman

5. **Test the token** by calling any admin endpoint like:
   ```
   GET /api/v1/exams/stats
   ```

### Method 2: Using Existing Users

If you already have users in your database but can't verify them via email:

1. **Use the register endpoint** to create a user (if needed)
2. **Use the development OTP bypass** with any 6-digit code like `123456`
3. **Get your token** from the response

## Postman Collection Usage

### Available Test Token Generators

1. **Generate Admin Test Token**
   - Creates/updates user as admin
   - Perfect for testing exam management APIs

2. **Generate Trainer Test Token**
   - Creates/updates user as trainer
   - Good for testing training/exam creation

3. **Generate Farmer Test Token**
   - Creates/updates user as farmer/student
   - For testing student functionality

### Pre-configured Examples

The Postman collection includes ready-to-use examples:

- **Admin Token**: Uses `{{admin_email}}` variable
- **Trainer Token**: Uses `{{trainer_email}}` variable  
- **Farmer Token**: Uses `{{farmer_email}}` variable

## Environment Variables Setup

Make sure these variables are set in your Postman environment:

```
base_url = http://localhost:8000
admin_email = admin@example.com
trainer_email = trainer@example.com
farmer_email = farmer@example.com
auth_token = (will be set after generating token)
```

## Step-by-Step Testing Workflow

### 1. Generate Admin Token
```bash
POST /api/v1/auth/generate-test-token
{
  "email": "admin@example.com",
  "user_type": "admin"
}
```

### 2. Update Environment Variable
Copy the token from response and set `auth_token` variable.

### 3. Test Admin APIs
Now you can test all admin endpoints:
```bash
GET /api/v1/exams/stats
GET /api/v1/exams/form-data
GET /api/v1/exams
POST /api/v1/exams
```

## API Responses

### Successful Token Generation
```json
{
  "message": "Test token generated successfully",
  "user": {
    "id": 1,
    "first_name": "Admin",
    "last_name": "User", 
    "email": "admin@example.com",
    "user_type": "admin",
    "two_factor_enabled": true,
    "email_verified": true
  },
  "token": "1|abcdef123456789...",
  "token_type": "Bearer",
  "note": "This is a test token for development purposes only"
}
```

### Production Environment (Expected Error)
```json
{
  "message": "Test token generation is not available in production"
}
```

### User Not Found
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The selected email is invalid."]
  }
}
```

## Creating Test Users

If you need to create test users first, you can use the existing register endpoints:

### Create Admin User
```json
POST /api/v1/auth/register
{
  "first_name": "Admin",
  "last_name": "User",
  "email": "admin@example.com",
  "password": "password123",
  "phone": "+123456789",
  "region": "Test Region",
  "user_type": "admin"
}
```

### Create Trainer User
```json
POST /api/v1/auth/register
{
  "first_name": "Trainer",
  "last_name": "User",
  "email": "trainer@example.com", 
  "password": "password123",
  "phone": "+123456789",
  "region": "Test Region",
  "user_type": "trainer"
}
```

Then use the development token generator to get tokens for these users.

## Troubleshooting

### "Test token generation is not available in production"
**Solution**: Make sure your Laravel environment is set to development:
- Check `.env` file: `APP_ENV=local` or `APP_ENV=development`
- Not `APP_ENV=production`

### "The selected email is invalid"
**Solution**: The user doesn't exist in the database. Either:
1. Create the user first using the register endpoint
2. Use an existing user's email address

### "403 Forbidden" on API calls
**Solution**: 
1. Make sure you copied the full token including the prefix (e.g., `1|abc123...`)
2. Ensure the `Authorization` header is set as `Bearer {{auth_token}}`
3. Verify the user has the correct role (admin for exam management)

### Token doesn't work
**Solution**:
1. Check that the token was copied correctly
2. Ensure no extra spaces or characters
3. Try generating a new token
4. Verify the user type matches the required permissions

## Testing Different User Types

### Admin Testing
```json
{
  "email": "admin@example.com",
  "user_type": "admin"
}
```
- Can access all exam management endpoints
- Can manage users, trainings, etc.

### Trainer Testing  
```json
{
  "email": "trainer@example.com", 
  "user_type": "trainer"
}
```
- Can manage trainings and questions
- Limited admin access

### Farmer/Student Testing
```json
{
  "email": "farmer@example.com",
  "user_type": "farmer"
}
```
- Can take exams and trainings
- No admin access

## Security Notes

1. **Development Only**: These endpoints automatically check the environment and refuse to work in production
2. **No Email Required**: Bypasses all email verification for testing convenience  
3. **Auto-Setup**: Automatically enables 2FA and verifies email for generated tokens
4. **User Type Control**: Can create or modify users with specific roles for testing

## Next Steps

After generating your admin token:

1. **Test Dashboard APIs**:
   - `GET /api/v1/exams/stats`
   - `GET /api/v1/exams/form-data`

2. **Test Exam Management**:
   - `GET /api/v1/exams` (with filtering)
   - `POST /api/v1/exams` (create exam)
   - `PUT /api/v1/exams/{id}` (update exam)

3. **Test Complete Workflow**:
   - Follow the exam management testing guide
   - Create, read, update, delete exams
   - Test all filtering and search functionality

This setup allows you to fully test your admin dashboard APIs without needing email server access!
