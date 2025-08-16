# Postman Collection Setup for Agrar Portal API

This guide explains how to use the updated Postman collection with 2FA (Two-Factor Authentication) features.

## 📋 Prerequisites

1. **Postman** installed on your machine
2. **Laravel server** running on `http://localhost:8000`
3. **Gmail SMTP** configured in your `.env` file

## 🚀 Quick Start

### 1. Import the Collection

1. Open Postman
2. Click "Import" button
3. Select the `Agrar_Portal_API.postman_collection.json` file
4. The collection will be imported with all 2FA endpoints

### 2. Configure Environment Variables

The collection includes these variables:

| Variable | Default Value | Description |
|----------|---------------|-------------|
| `base_url` | `http://localhost:8000` | Your Laravel API base URL |
| `auth_token` | `YOUR_AUTH_TOKEN_HERE` | Authentication token (auto-filled after login) |
| `user_email` | `test@example.com` | Email for testing (update with your email) |
| `otp_code` | `123456` | OTP code placeholder (update with actual OTP) |
| `admin_email` | `admin@example.com` | Pre-seeded admin email |
| `trainer_email` | `trainer@example.com` | Pre-seeded trainer email |
| `farmer_email` | `farmer@example.com` | Pre-seeded farmer email |
| `default_password` | `password123` | Default password for all seeded users |

### 3. Update Variables

1. Click on the collection name
2. Go to "Variables" tab
3. Update `user_email` with your actual email address
4. Keep `otp_code` as placeholder (you'll update it when you receive OTP)

## 🔐 2FA Testing Workflow

### Option 1: Use Pre-seeded Users (No 2FA Required)

The database comes with pre-seeded users that are already verified:

1. **Login Admin** - Use `admin@example.com` / `password123`
2. **Login Trainer** - Use `trainer@example.com` / `password123`
3. **Login Farmer** - Use `farmer@example.com` / `password123`

These users can login directly without 2FA verification.

### Option 2: Register New Users (2FA Required)

1. Use **"Register Admin/Trainer/Farmer (2FA Required)"** requests
2. Update the email in the request body to your email
3. Send the request
4. **Get OTP Code** - Use **"Get OTP Code (Development)"** request to retrieve the OTP directly
5. **Alternative**: Check your email for the 6-digit OTP code (if email is working)

### Step 2: Verify OTP (Only for New Registrations)

1. **Get OTP Code** - Use **"Get OTP Code (Development)"** request to get the OTP
2. Copy the 6-digit OTP from the response
3. Update the `otp_code` variable in the collection
4. Use **"Verify OTP"** request
5. This will return an authentication token

### Step 3: Set Authentication Token

1. Copy the token from the login/verify OTP response
2. Update the `auth_token` variable in the collection
3. Now you can use all authenticated endpoints

### Step 4: Test 2FA Management

1. **Check 2FA Status** - See current 2FA status
2. **Enable 2FA** - Enable 2FA (if not already enabled)
3. **Disable 2FA** - Disable 2FA with OTP verification
4. **Re-enable 2FA** - Re-enable 2FA with OTP verification

## 📧 Email Configuration

Make sure your `.env` file has Gmail SMTP configured:

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

## 🔄 Testing Flow

### Pre-seeded Users Flow:
```
1. Login Admin/Trainer/Farmer → Get auth token
2. Set auth token → Access protected endpoints
```

### New User Registration Flow:
```
1. Register User → Get OTP email
2. Verify OTP → Get auth token
3. Set auth token → Access protected endpoints
```

### 2FA Management Flow:
```
1. Login → Get auth token
2. Check 2FA Status → See current status
3. Enable/Disable 2FA → Get OTP email
4. Verify OTP → Update 2FA status
```

## 📝 Request Examples

### Pre-seeded User Login
```json
{
  "email": "admin@example.com",
  "password": "password123"
}
```

### Register New User
```json
{
  "first_name": "John",
  "last_name": "Farmer",
  "email": "{{user_email}}",
  "password": "password123",
  "phone": "+123456789",
  "user_type": "farmer"
}
```

### Verify OTP
```json
{
  "email": "{{user_email}}",
  "otp": "{{otp_code}}"
}
```

### Enable 2FA
```json
{
  "otp": "{{otp_code}}"
}
```

## ⚠️ Important Notes

1. **OTP Expiration**: OTP codes expire after 10 minutes
2. **Email Verification**: Users must verify email before login
3. **Token Management**: Always update `auth_token` after login/verification
4. **Gmail App Password**: Use Gmail app password, not regular password

## 🧪 Testing Tips

1. **Use Real Email**: Use a real email address to receive OTP codes
2. **Check Spam**: OTP emails might go to spam folder
3. **Token Refresh**: Update auth token after each login
4. **Error Handling**: Check response messages for detailed error information

## 🔧 Troubleshooting

### Common Issues:

1. **"Invalid credentials"** - Check email/password
2. **"Please verify your email first"** - Complete OTP verification
3. **"OTP has expired"** - Request new OTP
4. **"Invalid OTP code"** - Double-check the 6-digit code

### Email Issues:

1. **Email not received** - Use **"Get OTP Code (Development)"** endpoint instead
2. **Gmail app password issues** - Generate new app password in Google Account settings
3. **Check spam folder** - OTP emails might be filtered as spam
4. **SMTP configuration** - Verify Gmail SMTP settings in `.env`

### Solutions:

1. **Email not received** - Use the development OTP endpoint for testing
2. **Token expired** - Re-login and update auth_token
3. **OTP issues** - Use "Resend OTP" endpoint or "Get OTP Code" endpoint

## 👥 Pre-seeded Users

The database comes with these pre-seeded users for testing:

| User Type | Email | Password | Description |
|-----------|-------|----------|-------------|
| Admin | `admin@example.com` | `password123` | Full platform control |
| Trainer | `trainer@example.com` | `password123` | Create/manage trainings |
| Farmer | `farmer@example.com` | `password123` | Enroll in courses, take exams |

These users are already verified and can login directly without 2FA.

## 📚 Additional Resources

- [2FA Setup Guide](./2FA_SETUP.md)
- [API Documentation](./API_DOCUMENTATION.md)
- [Laravel Mail Configuration](https://laravel.com/docs/mail) 