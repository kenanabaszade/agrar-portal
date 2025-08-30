# 2FA Login Implementation

This document explains the implementation of Two-Factor Authentication (2FA) for the Agrar Portal login system.

## Overview

The 2FA system works as follows:

1. **User Registration**: Users are registered with `two_factor_enabled` defaulting to `false`
2. **Admin Control**: Admins can toggle 2FA for any user through admin settings
3. **Login Flow**: When a user with 2FA enabled tries to login, they must provide an OTP code
4. **OTP Verification**: The system sends OTP codes via email for verification

## Database Schema

The `users` table includes these 2FA-related fields:

```sql
two_factor_enabled BOOLEAN DEFAULT FALSE
otp_code VARCHAR(6) NULL
otp_expires_at TIMESTAMP NULL
```

## API Endpoints

### Authentication Endpoints

#### 1. Login (Modified)
**POST** `/api/v1/auth/login`

**Request:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response (2FA Required):**
```json
{
  "message": "2FA verification required. Please check your email for OTP code.",
  "email": "user@example.com",
  "needs_2fa": true,
  "user_id": 123
}
```

**Response (No 2FA Required):**
```json
{
  "user": { ... },
  "token": "access_token_here"
}
```

#### 2. Verify Login OTP
**POST** `/api/v1/auth/verify-login-otp`

**Request:**
```json
{
  "user_id": 123,
  "otp": "123456"
}
```

**Response:**
```json
{
  "message": "2FA verification successful!",
  "user": { ... },
  "token": "access_token_here"
}
```

#### 3. Resend Login OTP
**POST** `/api/v1/auth/resend-login-otp`

**Request:**
```json
{
  "user_id": 123
}
```

**Response:**
```json
{
  "message": "New 2FA OTP sent to your email."
}
```

### Admin Endpoints

#### 1. Toggle User 2FA
**POST** `/api/v1/users/{user}/toggle-2fa`

**Request:**
```json
{
  "two_factor_enabled": true
}
```

**Response:**
```json
{
  "message": "2FA setting updated successfully",
  "user": { ... }
}
```

#### 2. Update User (Enhanced)
**PATCH** `/api/v1/users/{user}`

**Request:**
```json
{
  "two_factor_enabled": true,
  "first_name": "John",
  "last_name": "Doe"
}
```

## Login Flow

### For Users with 2FA Disabled
1. User submits email/password
2. System validates credentials
3. System returns access token immediately

### For Users with 2FA Enabled
1. User submits email/password
2. System validates credentials
3. System generates OTP and sends via email
4. System returns `needs_2fa: true` with `user_id`
5. User enters OTP code
6. System verifies OTP
7. System returns access token

## Implementation Details

### AuthController Changes

1. **Modified `login()` method**: Now checks `two_factor_enabled` and triggers OTP flow if needed
2. **Added `verifyLoginOtp()` method**: Verifies OTP and completes login
3. **Added `resendLoginOtp()` method**: Resends OTP if needed

### UsersController Changes

1. **Enhanced `update()` method**: Now accepts `two_factor_enabled` field
2. **Added `toggleTwoFactor()` method**: Dedicated endpoint for toggling 2FA

### Routes Added

```php
// Public routes
Route::post('auth/verify-login-otp', [AuthController::class, 'verifyLoginOtp']);
Route::post('auth/resend-login-otp', [AuthController::class, 'resendLoginOtp']);

// Admin routes
Route::post('users/{user}/toggle-2fa', [UsersController::class, 'toggleTwoFactor'])->middleware('role:admin');
```

## Testing

### Test Scripts

1. **`test_2fa_login_flow.sh`**: Tests the complete 2FA login process
2. **`test_admin_2fa_toggle.sh`**: Tests admin 2FA toggle functionality

### Usage

```bash
# Test 2FA login flow
./test_2fa_login_flow.sh

# Test admin 2FA toggle
./test_admin_2fa_toggle.sh
```

## Security Considerations

1. **OTP Expiration**: OTP codes expire after 10 minutes
2. **Rate Limiting**: Consider implementing rate limiting for OTP requests
3. **Token Security**: Access tokens are generated only after successful 2FA verification
4. **Admin Authorization**: Only users with admin role can toggle 2FA settings

## Error Handling

The system handles various error scenarios:

- Invalid OTP codes
- Expired OTP codes
- Missing OTP codes
- Invalid user IDs
- Unauthorized admin access

## Frontend Integration

Frontend applications should:

1. Check for `needs_2fa` flag in login response
2. Show OTP input form when 2FA is required
3. Handle OTP verification flow
4. Provide option to resend OTP
5. Store and use access token after successful verification

## Example Frontend Flow

```javascript
// 1. Login attempt
const loginResponse = await login(email, password);

if (loginResponse.needs_2fa) {
  // 2. Show OTP input
  const otp = await showOtpInput();
  
  // 3. Verify OTP
  const verifyResponse = await verifyLoginOtp(loginResponse.user_id, otp);
  
  // 4. Store token
  storeToken(verifyResponse.token);
} else {
  // Direct login successful
  storeToken(loginResponse.token);
}
```

## Migration Notes

- Existing users have `two_factor_enabled` set to `false` by default
- Admins can enable 2FA for specific users through the admin interface
- The system is backward compatible - users without 2FA can login normally 