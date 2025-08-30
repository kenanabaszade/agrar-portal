# Profile Management Guide

This guide explains the new profile management functionality that allows users to update their credentials with security features.

## 🚀 Overview

The profile management system includes:

1. **Basic Profile Updates**: Update name, username, father's name, region, phone number, user type
2. **Password Change**: Change password with current password verification
3. **Email Change**: Change email with OTP verification
4. **Security Features**: OTP verification for sensitive changes

## 📋 Database Changes

### New Fields Added to Users Table
- `username` (unique, nullable)
- `father_name` (nullable)
- `region` (nullable)

### New Table: email_change_requests
- `user_id` (foreign key)
- `new_email`
- `otp_code`
- `otp_expires_at`

## 🔐 API Endpoints

### Profile Management Endpoints

#### 1. Get My Profile
**GET** `/api/v1/profile`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "user": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "username": "johndoe",
    "father_name": "John Senior",
    "region": "North Region",
    "email": "john@example.com",
    "phone": "+1234567890",
    "user_type": "farmer",
    "two_factor_enabled": false,
    ...
  }
}
```

#### 2. Update Profile
**PATCH** `/api/v1/profile`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "first_name": "Updated First Name",
  "last_name": "Updated Last Name",
  "username": "updated_username",
  "father_name": "Father's Name",
  "region": "North Region",
  "phone": "+1234567890",
  "user_type": "farmer"
}
```

**Response:**
```json
{
  "message": "Profile updated successfully",
  "user": { ... }
}
```

#### 3. Change Password
**POST** `/api/v1/profile/change-password`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "current_password": "oldPassword123",
  "new_password": "newPassword123",
  "new_password_confirmation": "newPassword123"
}
```

**Response:**
```json
{
  "message": "Password changed successfully"
}
```

#### 4. Request Email Change
**POST** `/api/v1/profile/request-email-change`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "new_email": "newemail@example.com",
  "password": "currentPassword123"
}
```

**Response:**
```json
{
  "message": "OTP sent to new email address. Please verify to complete email change.",
  "new_email": "newemail@example.com"
}
```

#### 5. Verify Email Change OTP
**POST** `/api/v1/profile/verify-email-change`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "otp": "123456"
}
```

**Response:**
```json
{
  "message": "Email changed successfully",
  "user": { ... }
}
```

#### 6. Resend Email Change OTP
**POST** `/api/v1/profile/resend-email-change-otp`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "New OTP sent to new email address."
}
```

#### 7. Cancel Email Change
**POST** `/api/v1/profile/cancel-email-change`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "Email change request cancelled."
}
```

## 🔄 Testing Scenarios

### Scenario 1: Basic Profile Update

1. **Get Current Profile**
   - Use "Get My Profile" request
   - Verify current data

2. **Update Profile**
   - Use "Update Profile" request
   - Update name, username, father's name, region, phone, user type
   - Verify changes are saved

### Scenario 2: Password Change

1. **Change Password**
   - Use "Change Password" request
   - Provide current password and new password
   - Verify password is changed

2. **Test New Password**
   - Try logging in with new password
   - Verify old password no longer works

### Scenario 3: Email Change with OTP

1. **Request Email Change**
   - Use "Request Email Change" request
   - Provide new email and current password
   - Check for OTP in new email

2. **Verify Email Change**
   - Use "Verify Email Change OTP" request
   - Provide OTP from email
   - Verify email is updated

3. **Test New Email**
   - Try logging in with new email
   - Verify old email no longer works

### Scenario 4: Email Change Edge Cases

1. **Resend OTP**
   - Use "Resend Email Change OTP" request
   - Check for new OTP in email

2. **Cancel Email Change**
   - Use "Cancel Email Change" request
   - Verify email change is cancelled

3. **Expired OTP**
   - Wait 10+ minutes
   - Try to verify expired OTP
   - Should get error message

## 🛠️ Postman Testing

### Environment Variables
- `auth_token`: User authentication token
- `otp_code`: OTP code from email

### Testing Flow

1. **Login and Get Token**
   ```bash
   POST /api/v1/auth/login
   # Copy token to auth_token variable
   ```

2. **Test Profile Updates**
   ```bash
   GET /api/v1/profile
   PATCH /api/v1/profile
   ```

3. **Test Password Change**
   ```bash
   POST /api/v1/profile/change-password
   ```

4. **Test Email Change**
   ```bash
   POST /api/v1/profile/request-email-change
   # Check email for OTP
   POST /api/v1/profile/verify-email-change
   ```

## 🔒 Security Features

### Password Change Security
- Requires current password verification
- New password must be confirmed
- Minimum 8 characters required

### Email Change Security
- Requires current password verification
- OTP sent to new email address
- OTP expires after 10 minutes
- Only one email change request per user at a time

### Validation Rules
- Username must be unique
- Email must be unique
- Phone number format validation
- Password confirmation required
- User type must be one of: farmer, trainer, admin

## 📊 Error Handling

### Common Error Responses

| Error Code | Meaning | Solution |
|------------|---------|----------|
| `422` | Validation error | Check request data |
| `401` | Unauthorized | Check authentication token |
| `400` | Bad request | Check request format |
| `404` | Not found | Check endpoint URL |

### Validation Errors
- Invalid email format
- Username already taken
- Email already taken
- Password too short
- Password confirmation mismatch
- Current password incorrect
- Invalid user type

## 🎯 Best Practices

### For Frontend Implementation

1. **Profile Update**
   - Show current values in form
   - Validate data before submission
   - Show success/error messages

2. **Password Change**
   - Require current password
   - Show password strength indicator
   - Confirm new password

3. **Email Change**
   - Show current email
   - Require password verification
   - Show OTP input form
   - Provide resend option
   - Allow cancellation

### For Backend Testing

1. **Test All Validation Rules**
2. **Test OTP Expiration**
3. **Test Concurrent Requests**
4. **Test Error Scenarios**
5. **Test Security Features**

## 📝 Testing Checklist

- [ ] Get profile works
- [ ] Update profile works
- [ ] Username uniqueness validation
- [ ] Region field updates correctly
- [ ] User type changes work correctly
- [ ] Password change works
- [ ] Current password verification
- [ ] Email change request works
- [ ] OTP generation and sending
- [ ] Email change verification works
- [ ] OTP expiration handling
- [ ] Resend OTP works
- [ ] Cancel email change works
- [ ] Error handling works
- [ ] Security features work

## 🔧 Troubleshooting

### Common Issues

**Issue: "Username already taken"**
- Solution: Choose a different username

**Issue: "Email already taken"**
- Solution: Use a different email address

**Issue: "Current password incorrect"**
- Solution: Verify current password

**Issue: "OTP expired"**
- Solution: Request new OTP

**Issue: "Email change already in progress"**
- Solution: Wait for current request to expire or cancel it

### Debug Tips

1. Check server logs for detailed errors
2. Verify authentication token is valid
3. Check email configuration for OTP delivery
4. Verify database migrations are run
5. Test with Postman collection first 