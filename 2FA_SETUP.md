# 2FA Setup with Gmail OTP

This document explains how to set up Two-Factor Authentication (2FA) using Gmail OTP for the Agrar Portal.

## Features

- 6-digit OTP code sent via email during registration
- 10-minute expiration time for OTP codes
- Email verification required before login
- Resend OTP functionality
- Secure token-based authentication after verification

## API Endpoints

### 1. Register User (Initial 2FA Setup)
```
POST /api/v1/auth/register
```

**Request Body:**
```json
{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john.doe@gmail.com",
    "phone": "+1234567890",
    "password": "password123",
    "user_type": "farmer"
}
```

**Response:**
```json
{
    "message": "Registration successful! Please check your email for OTP verification.",
    "email": "john.doe@gmail.com"
}
```

### 2. Verify OTP
```
POST /api/v1/auth/verify-otp
```

**Request Body:**
```json
{
    "email": "john.doe@gmail.com",
    "otp": "123456"
}
```

**Response:**
```json
{
    "message": "Email verified successfully!",
    "user": {
        "id": 1,
        "first_name": "John",
        "last_name": "Doe",
        "email": "john.doe@gmail.com",
        "email_verified": true,
        "email_verified_at": "2025-08-15T16:30:00.000000Z"
    },
    "token": "1|abc123..."
}
```

### 3. Resend OTP
```
POST /api/v1/auth/resend-otp
```

**Request Body:**
```json
{
    "email": "john.doe@gmail.com"
}
```

**Response:**
```json
{
    "message": "New OTP sent to your email"
}
```

### 4. Login (Only for verified users)
```
POST /api/v1/auth/login
```

**Request Body:**
```json
{
    "email": "john.doe@gmail.com",
    "password": "password123"
}
```

**Response (if not verified):**
```json
{
    "message": "Please verify your email first. Check your inbox for OTP code.",
    "email": "john.doe@gmail.com",
    "needs_verification": true
}
```

### 5. Check 2FA Status
```
GET /api/v1/auth/2fa/status
```

**Headers:** `Authorization: Bearer YOUR_TOKEN`

**Response:**
```json
{
    "two_factor_enabled": true,
    "email_verified": true
}
```

### 6. Enable 2FA (for existing users)
```
POST /api/v1/auth/2fa/enable
```

**Headers:** `Authorization: Bearer YOUR_TOKEN`

**Response:**
```json
{
    "message": "OTP sent to your email. Please verify to enable 2FA."
}
```

### 7. Verify 2FA Activation
```
POST /api/v1/auth/2fa/verify-enable
```

**Headers:** `Authorization: Bearer YOUR_TOKEN`

**Request Body:**
```json
{
    "otp": "123456"
}
```

**Response:**
```json
{
    "message": "2FA enabled successfully!",
    "user": {
        "id": 1,
        "two_factor_enabled": true
    }
}
```

### 8. Disable 2FA
```
POST /api/v1/auth/2fa/disable
```

**Headers:** `Authorization: Bearer YOUR_TOKEN`

**Response:**
```json
{
    "message": "OTP sent to your email. Please verify to disable 2FA."
}
```

### 9. Verify 2FA Deactivation
```
POST /api/v1/auth/2fa/verify-disable
```

**Headers:** `Authorization: Bearer YOUR_TOKEN`

**Request Body:**
```json
{
    "otp": "123456"
}
```

**Response:**
```json
{
    "message": "2FA disabled successfully!",
    "user": {
        "id": 1,
        "two_factor_enabled": false
    }
}
```

## Gmail SMTP Configuration

To use Gmail for sending OTP emails, update your `.env` file with the following settings:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Agrar Portal"
```

### Setting up Gmail App Password

1. Enable 2-Step Verification on your Gmail account
2. Go to Google Account settings
3. Navigate to Security > 2-Step Verification > App passwords
4. Generate a new app password for "Mail"
5. Use this app password in your `.env` file (not your regular Gmail password)

## Database Changes

The following fields have been added to the `users` table:

- `otp_code` (string, nullable) - Stores the 6-digit OTP
- `otp_expires_at` (timestamp, nullable) - OTP expiration time
- `email_verified` (boolean, default: false) - Email verification status

## Security Features

- OTP codes expire after 10 minutes
- OTP codes are cleared after successful verification
- Users cannot login without email verification
- Secure token generation using Laravel Sanctum
- Password hashing using bcrypt

## Testing

For testing purposes, you can use Mailtrap or configure Gmail SMTP as described above.

## Flow Diagram

### Initial Registration Flow
```
User Registration → Send OTP Email → User Enters OTP → Verify Email + Enable 2FA → Login Enabled
     ↓
If OTP Expires → Resend OTP → User Enters New OTP → Verify Email + Enable 2FA → Login Enabled
```

### 2FA Management Flow (for existing users)
```
User Login → Check 2FA Status → Enable/Disable 2FA → Send OTP → Verify OTP → Update 2FA Status
```

## Error Handling

- Invalid OTP: Returns 422 status with error message
- Expired OTP: Returns 400 status with suggestion to resend
- User not found: Returns 404 status
- Already verified: Returns 400 status
- Unverified login attempt: Returns 422 status with verification requirement 