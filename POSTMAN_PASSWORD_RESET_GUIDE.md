# Postman Password Reset Testing Guide

## Overview
This guide explains how to test the password reset functionality with 2FA authentication using the updated Postman collection.

## Prerequisites
1. Laravel server running on `http://localhost:8000`
2. Updated Postman collection imported
3. A test user account in the database

## Environment Variables Setup

### Required Variables
Make sure these variables are set in your Postman environment:

| Variable | Description | Example Value |
|----------|-------------|---------------|
| `base_url` | Your Laravel server URL | `http://localhost:8000` |
| `user_email` | Email of the user to test | `farmer@example.com` |
| `otp_code` | OTP code from email/database | `123456` |
| `reset_token` | Reset token from OTP verification | `abc123...` |

## Testing Flow

### Step 1: Request Password Reset
**Endpoint:** `POST {{base_url}}/api/v1/auth/forgot-password`

**Request Body:**
```json
{
  "email": "{{user_email}}"
}
```

**Expected Response:**
```json
{
  "message": "Password reset OTP sent to your email. Please check your inbox.",
  "email": "farmer@example.com"
}
```

**What happens:**
- System generates a secure reset token
- Sends 6-digit OTP to user's email
- Stores reset token and OTP in database

### Step 2: Get OTP from Database
Since emails might not be configured in development, get the OTP directly from the database:

```bash
php artisan tinker --execute="echo App\Models\User::where('email', 'farmer@example.com')->first()->otp_code;"
```

### Step 3: Verify Password Reset OTP
**Endpoint:** `POST {{base_url}}/api/v1/auth/verify-password-reset-otp`

**Request Body:**
```json
{
  "email": "{{user_email}}",
  "otp": "{{otp_code}}"
}
```

**Expected Response:**
```json
{
  "message": "OTP verified successfully. You can now reset your password.",
  "reset_token": "abc123def456ghi789jkl012mno345pqr678stu901vwx234yz5678901234567890",
  "email": "farmer@example.com"
}
```

**Important:** Copy the `reset_token` from the response and update your Postman variable.

### Step 4: Reset Password with Token
**Endpoint:** `POST {{base_url}}/api/v1/auth/reset-password`

**Request Body:**
```json
{
  "email": "{{user_email}}",
  "token": "{{reset_token}}",
  "password": "newPassword123",
  "password_confirmation": "newPassword123"
}
```

**Expected Response:**
```json
{
  "message": "Password reset successfully. You can now login with your new password.",
  "email": "farmer@example.com"
}
```

### Step 5: Verify Password Reset
**Endpoint:** `POST {{base_url}}/api/v1/auth/login`

**Request Body:**
```json
{
  "email": "{{user_email}}",
  "password": "newPassword123"
}
```

**Expected Response:**
```json
{
  "message": "Login successful",
  "user": {
    "id": 1,
    "first_name": "Frank",
    "last_name": "Farmer",
    "email": "farmer@example.com",
    "user_type": "farmer"
  },
  "token": "your_auth_token_here"
}
```

## Optional: Resend Password Reset OTP
If the OTP expires or you need a new one:

**Endpoint:** `POST {{base_url}}/api/v1/auth/resend-password-reset-otp`

**Request Body:**
```json
{
  "email": "{{user_email}}"
}
```

## Postman Collection Structure

The updated collection includes these new endpoints in the **Authentication** folder:

1. **Request Password Reset (2FA)** - Initiates the password reset process
2. **Verify Password Reset OTP** - Verifies the OTP and returns reset token
3. **Reset Password with Token** - Changes password using the reset token
4. **Resend Password Reset OTP** - Sends a new OTP if needed

## Testing Tips

### 1. Use Environment Variables
- Set `user_email` to an existing user in your database
- Update `otp_code` after each OTP generation
- Update `reset_token` after OTP verification

### 2. Check Database for OTP
```bash
# Get OTP for a specific user
php artisan tinker --execute="echo App\Models\User::where('email', 'farmer@example.com')->first()->otp_code;"

# Check OTP expiration
php artisan tinker --execute="echo App\Models\User::where('email', 'farmer@example.com')->first()->otp_expires_at;"
```

### 3. Monitor Logs
```bash
# Watch Laravel logs for debugging
tail -f storage/logs/laravel.log
```

### 4. Test Error Scenarios
- Invalid email address
- Expired OTP
- Invalid reset token
- Password confirmation mismatch
- Weak password

## Security Features

✅ **2FA Protection** - OTP required for password reset
✅ **Secure Tokens** - 64-character random tokens
✅ **Time Limits** - 24-hour token expiration, 10-minute OTP expiration
✅ **One-time Use** - Tokens and OTPs are invalidated after use
✅ **Email Verification** - Only registered emails can request reset

## Troubleshooting

### Common Issues

1. **"User not found" error**
   - Ensure the email exists in the database
   - Check for typos in the email address

2. **"Invalid OTP" error**
   - OTP might have expired (10 minutes)
   - Get fresh OTP from database
   - Use the resend endpoint

3. **"Invalid token" error**
   - Token might have expired (24 hours)
   - Start the process again from step 1
   - Ensure you're using the correct token

4. **"Password confirmation does not match"**
   - Ensure both password fields are identical
   - Check for extra spaces or characters

### Debug Commands

```bash
# Check user status
php artisan tinker --execute="echo App\Models\User::where('email', 'farmer@example.com')->first()->toJson();"

# Clear expired tokens
php artisan tinker --execute="DB::table('password_reset_tokens')->where('created_at', '<', now()->subDay())->delete();"

# Check password reset tokens
php artisan tinker --execute="echo DB::table('password_reset_tokens')->get()->toJson();"
```

## Complete Test Sequence

1. **Setup:** Ensure server is running and user exists
2. **Request Reset:** Call forgot-password endpoint
3. **Get OTP:** Retrieve OTP from database
4. **Verify OTP:** Call verify-password-reset-otp endpoint
5. **Update Token:** Copy reset_token to Postman variable
6. **Reset Password:** Call reset-password endpoint
7. **Verify Login:** Test login with new password
8. **Cleanup:** Optional - reset password back to original

This completes the password reset testing flow with 2FA authentication! 