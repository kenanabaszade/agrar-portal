# Postman 2FA Testing Guide

This guide explains how to test the new 2FA (Two-Factor Authentication) functionality using the updated Postman collection.

## 🚀 Quick Start

1. **Import the updated collection**: `Agrar_Portal_API.postman_collection.json`
2. **Set up environment variables** (see below)
3. **Follow the testing scenarios** outlined in this guide

## 📋 Environment Variables

Make sure these variables are set in your Postman environment:

| Variable | Description | Example Value |
|----------|-------------|---------------|
| `base_url` | Your API base URL | `http://localhost:8000` |
| `user_email` | Test user email | `test@example.com` |
| `otp_code` | OTP code from email | `123456` |
| `user_id` | User ID for 2FA operations | `1` |
| `auth_token` | Admin authentication token | `YOUR_ADMIN_TOKEN` |

## 🔐 Testing Scenarios

### Scenario 1: User Login with 2FA Enabled

**Step 1: Login Attempt**
1. Use the **"Login Custom User (Email Verification Required)"** request
2. Set the request body:
   ```json
   {
     "email": "{{user_email}}",
     "password": "password123"
   }
   ```
3. Send the request

**Expected Response (2FA Required):**
```json
{
  "message": "2FA verification required. Please check your email for OTP code.",
  "email": "test@example.com",
  "needs_2fa": true,
  "user_id": 1
}
```

**Step 2: Check Email for OTP**
- Check your email for the OTP code
- Update the `otp_code` variable with the received code

**Step 3: Verify OTP**
1. Use the **"Verify Login OTP (2FA)"** request
2. Set the request body:
   ```json
   {
     "user_id": "{{user_id}}",
     "otp": "{{otp_code}}"
   }
   ```
3. Send the request

**Expected Response:**
```json
{
  "message": "2FA verification successful!",
  "user": { ... },
  "token": "access_token_here"
}
```

### Scenario 2: Resend OTP

If the OTP expires or you need a new one:

1. Use the **"Resend Login OTP (2FA)"** request
2. Set the request body:
   ```json
   {
     "user_id": "{{user_id}}"
   }
   ```
3. Send the request

**Expected Response:**
```json
{
  "message": "New 2FA OTP sent to your email."
}
```

### Scenario 3: Admin Toggle 2FA for User

**Prerequisites:**
- You need an admin account
- Set the `auth_token` variable with admin token

**Step 1: Get Admin Token**
1. Use **"Login Admin"** request
2. Copy the token from the response
3. Update the `auth_token` variable

**Step 2: Enable 2FA for User**
1. Use the **"Toggle User 2FA (Admin)"** request
2. Set the request body:
   ```json
   {
     "two_factor_enabled": true
   }
   ```
3. Send the request

**Expected Response:**
```json
{
  "message": "2FA setting updated successfully",
  "user": {
    "id": 1,
    "two_factor_enabled": true,
    ...
  }
}
```

**Step 3: Disable 2FA for User**
1. Use the same **"Toggle User 2FA (Admin)"** request
2. Set the request body:
   ```json
   {
     "two_factor_enabled": false
   }
   ```
3. Send the request

### Scenario 4: Update User with 2FA Setting

**Step 1: Update User with 2FA**
1. Use the **"Update User"** request
2. Set the request body:
   ```json
   {
     "first_name": "Updated Name",
     "two_factor_enabled": true,
     "user_type": "farmer"
   }
   ```
3. Send the request

## 🔄 Complete Testing Flow

### Flow 1: New User Registration and 2FA Setup

1. **Register User**: Use "Register Farmer (2FA Required)"
2. **Verify Email**: Use "Verify OTP" with email OTP
3. **Admin Enables 2FA**: Use "Toggle User 2FA (Admin)"
4. **Test 2FA Login**: Follow Scenario 1

### Flow 2: Existing User 2FA Testing

1. **Admin Enables 2FA**: Use "Toggle User 2FA (Admin)"
2. **Test Login Flow**: Follow Scenario 1
3. **Test OTP Resend**: Follow Scenario 2
4. **Admin Disables 2FA**: Use "Toggle User 2FA (Admin)"
5. **Test Normal Login**: Should work without 2FA

## 🛠️ Troubleshooting

### Common Issues

**Issue: "User not found"**
- Solution: Check if the `user_id` variable is correct
- Verify the user exists in the database

**Issue: "Invalid OTP code"**
- Solution: Check if the OTP code is correct
- Ensure the OTP hasn't expired (10 minutes)
- Try resending the OTP

**Issue: "2FA is not enabled for this user"**
- Solution: Verify the user has `two_factor_enabled: true`
- Use admin endpoint to enable 2FA

**Issue: "Unauthorized" for admin endpoints**
- Solution: Ensure you're using an admin account
- Check if the `auth_token` is valid and belongs to an admin

### Error Responses

| Error | Meaning | Solution |
|-------|---------|----------|
| `422` | Invalid OTP | Check OTP code |
| `400` | OTP expired | Resend OTP |
| `404` | User not found | Check user ID |
| `401` | Unauthorized | Check admin token |

## 📝 Testing Checklist

- [ ] User registration works
- [ ] Email verification works
- [ ] Admin can enable 2FA for user
- [ ] Login triggers 2FA when enabled
- [ ] OTP verification works
- [ ] OTP resend works
- [ ] Admin can disable 2FA
- [ ] Normal login works when 2FA is disabled
- [ ] Error handling works correctly

## 🎯 Advanced Testing

### Test Edge Cases

1. **Expired OTP**: Wait 10+ minutes and try to verify
2. **Wrong OTP**: Enter incorrect OTP code
3. **Multiple OTP requests**: Send multiple resend requests
4. **Invalid user ID**: Use non-existent user ID
5. **Non-admin access**: Try admin endpoints with non-admin token

### Performance Testing

1. **Concurrent logins**: Test multiple users logging in simultaneously
2. **OTP generation**: Test rapid OTP generation
3. **Token validation**: Test token expiration and renewal

## 📊 Expected Response Codes

| Endpoint | Success | Error Cases |
|----------|---------|-------------|
| Login | `200` (2FA required) / `200` (direct login) | `422` (invalid credentials) |
| Verify Login OTP | `200` | `422` (invalid OTP), `400` (expired) |
| Resend Login OTP | `200` | `400` (user not found) |
| Toggle User 2FA | `200` | `401` (unauthorized), `404` (user not found) |

## 🔒 Security Notes

- OTP codes expire after 10 minutes
- Only admins can toggle 2FA settings
- Access tokens are only generated after successful 2FA verification
- All sensitive operations require proper authentication

## 📞 Support

If you encounter issues:

1. Check the server logs for detailed error messages
2. Verify all environment variables are set correctly
3. Ensure the database is properly seeded with test data
4. Check if the email service is configured correctly for OTP delivery 