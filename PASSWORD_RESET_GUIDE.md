# Password Reset with 2FA Authentication Guide

## Overview

The password reset system implements a secure two-factor authentication (2FA) process using email OTP verification. This ensures that only users with access to their registered email can reset their passwords.

## Flow Diagram

```
1. User requests password reset
   ↓
2. System generates reset token + sends OTP email
   ↓
3. User enters OTP from email
   ↓
4. System verifies OTP and returns reset token
   ↓
5. User enters new password with reset token
   ↓
6. Password is updated and user can login
```

## API Endpoints

### 1. Request Password Reset
**POST** `/api/v1/auth/forgot-password`

**Request Body:**
```json
{
    "email": "user@example.com"
}
```

**Response:**
```json
{
    "message": "Password reset OTP sent to your email. Please check your inbox.",
    "email": "user@example.com"
}
```

**What happens:**
- Generates a 64-character reset token
- Stores token in `password_reset_tokens` table
- Generates 6-digit OTP
- Sends OTP to user's email
- OTP expires in 10 minutes

### 2. Verify Password Reset OTP
**POST** `/api/v1/auth/verify-password-reset-otp`

**Request Body:**
```json
{
    "email": "user@example.com",
    "otp": "123456"
}
```

**Response:**
```json
{
    "message": "OTP verified successfully. You can now reset your password.",
    "token": "64-character-reset-token",
    "email": "user@example.com"
}
```

**What happens:**
- Verifies the OTP code
- Checks OTP expiration (10 minutes)
- Returns the reset token for password reset
- Clears the OTP after successful verification

### 3. Reset Password
**POST** `/api/v1/auth/reset-password`

**Request Body:**
```json
{
    "email": "user@example.com",
    "token": "64-character-reset-token",
    "password": "newPassword123",
    "password_confirmation": "newPassword123"
}
```

**Response:**
```json
{
    "message": "Password reset successfully. You can now login with your new password."
}
```

**What happens:**
- Verifies the reset token
- Checks token expiration (24 hours)
- Updates user's password
- Deletes the used reset token
- User can now login with new password

### 4. Resend Password Reset OTP
**POST** `/api/v1/auth/resend-password-reset-otp`

**Request Body:**
```json
{
    "email": "user@example.com"
}
```

**Response:**
```json
{
    "message": "New password reset OTP sent to your email."
}
```

**What happens:**
- Checks if reset token exists
- Generates new OTP
- Sends new OTP to user's email
- OTP expires in 10 minutes

## Frontend Integration

### Step 1: Forgot Password Form
```javascript
// User enters email and clicks "Reset Password"
const requestReset = async (email) => {
    const response = await fetch('/api/v1/auth/forgot-password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email })
    });
    
    if (response.ok) {
        // Show OTP input form
        showOtpForm(email);
    }
};
```

### Step 2: OTP Verification Form
```javascript
// User enters OTP from email
const verifyOtp = async (email, otp) => {
    const response = await fetch('/api/v1/auth/verify-password-reset-otp', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, otp })
    });
    
    if (response.ok) {
        const data = await response.json();
        // Store token and show password reset form
        showPasswordResetForm(email, data.token);
    }
};
```

### Step 3: Password Reset Form
```javascript
// User enters new password
const resetPassword = async (email, token, password, passwordConfirmation) => {
    const response = await fetch('/api/v1/auth/reset-password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            email, 
            token, 
            password, 
            password_confirmation: passwordConfirmation 
        })
    });
    
    if (response.ok) {
        // Show success message and redirect to login
        showSuccessMessage('Password reset successfully!');
        redirectToLogin();
    }
};
```

## URL Structure for Frontend

The frontend should handle these URLs:

1. **Forgot Password Page:** `/forgot-password`
2. **OTP Verification Page:** `/reset-password/verify-otp?email=user@example.com`
3. **Password Reset Page:** `/reset-password?email=user@example.com&token=64-char-token`

## Security Features

1. **2FA Protection:** OTP verification required before password reset
2. **Token Expiration:** Reset tokens expire after 24 hours
3. **OTP Expiration:** OTP codes expire after 10 minutes
4. **One-time Use:** Reset tokens are deleted after use
5. **Email Verification:** Only verified email addresses can reset passwords
6. **Password Confirmation:** New password must be confirmed

## Error Handling

### Common Error Responses:

**Invalid Email:**
```json
{
    "message": "If a user with that email address exists, we will send a password reset link."
}
```

**Invalid OTP:**
```json
{
    "message": "Invalid OTP code"
}
```

**Expired OTP:**
```json
{
    "message": "OTP has expired. Please request a new one."
}
```

**Invalid Token:**
```json
{
    "message": "Invalid or expired reset token"
}
```

**Expired Token:**
```json
{
    "message": "Reset token has expired. Please request a new one."
}
```

## Database Tables

### password_reset_tokens
- `email` (primary key)
- `token` (64-character string)
- `created_at` (timestamp)

### users (existing fields used)
- `otp_code` (6-digit string)
- `otp_expires_at` (timestamp)

## Testing

You can test the password reset flow using the existing test script:

```bash
./test_otp_system.sh
```

Or manually test each endpoint using Postman or curl commands.

## Example Frontend Flow

1. User visits `/forgot-password`
2. Enters email and clicks "Send Reset Link"
3. System sends OTP to email
4. User is redirected to `/reset-password/verify-otp?email=user@example.com`
5. User enters OTP from email
6. System verifies OTP and redirects to `/reset-password?email=user@example.com&token=64-char-token`
7. User enters new password and confirmation
8. System updates password and redirects to login page
9. User can login with new password 