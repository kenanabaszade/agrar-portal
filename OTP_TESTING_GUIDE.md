# 🔐 OTP System Testing Guide

This guide shows you multiple ways to check if the OTP system is working properly.

## 🚀 Quick Test Methods

### **Method 1: Automated Test Script**
```bash
# Run the comprehensive test script
./test_otp_system.sh
```

### **Method 2: Manual Step-by-Step Testing**

#### **Step 1: Register a New User**
```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Test",
    "last_name": "User",
    "email": "test@example.com",
    "password": "password123",
    "user_type": "farmer"
  }'
```

**Expected Response:**
```json
{
  "message": "Registration successful! Please check your email for OTP verification.",
  "email": "test@example.com"
}
```

#### **Step 2: Check Email for OTP**
Check your email inbox for the OTP code. The email will be sent from `test@grindfit.site` with the subject "Your OTP Code for Agrar Portal Registration".

**Email Content:**
```
Hello!

Thank you for registering with Agrar Portal.

Your OTP (One-Time Password) code is:
**123456**

This code will expire in 10 minutes.

If you did not request this code, please ignore this email.

Best regards, Agrar Portal Team
```

#### **Step 3: Verify OTP**
```bash
curl -X POST http://localhost:8000/api/v1/auth/verify-otp \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "otp": "123456"
  }'
```

**Expected Response:**
```json
{
  "message": "Email verified successfully!",
  "user": {
    "email_verified": true,
    "two_factor_enabled": true
  },
  "token": "1|abc123..."
}
```

#### **Step 4: Login After Verification**
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

**Expected Response:**
```json
{
  "user": {
    "email_verified": true,
    "two_factor_enabled": true
  },
  "token": "2|def456..."
}
```

## 🔍 Database Verification

### **Check User in Database**
```bash
php artisan tinker --execute="
\$user = App\Models\User::where('email', 'test@example.com')->first();
if(\$user) {
    echo 'User: ' . \$user->email . PHP_EOL;
    echo 'OTP Code: ' . (\$user->otp_code ?? 'NULL') . PHP_EOL;
    echo 'Email Verified: ' . (\$user->email_verified ? 'Yes' : 'No') . PHP_EOL;
    echo '2FA Enabled: ' . (\$user->two_factor_enabled ? 'Yes' : 'No') . PHP_EOL;
} else {
    echo 'User not found';
}
"
```

## ❌ Error Testing

### **Test Invalid OTP**
```bash
curl -X POST http://localhost:8000/api/v1/auth/verify-otp \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "otp": "000000"
  }'
```

**Expected Response:**
```json
{
  "message": "Invalid OTP code"
}
```

### **Test Login Without Verification**
```bash
# Register a new user but don't verify
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Unverified",
    "last_name": "User",
    "email": "unverified@example.com",
    "password": "password123",
    "user_type": "farmer"
  }'

# Try to login without verification
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "unverified@example.com",
    "password": "password123"
  }'
```

**Expected Response:**
```json
{
  "message": "Please verify your email first. Check your inbox for OTP code.",
  "email": "unverified@example.com",
  "needs_verification": true
}
```

## 📧 Email Testing

### **Test Email Sending**
```bash
php artisan tinker --execute="
try {
    Mail::raw('Test email from Laravel', function(\$message) {
        \$message->to('test@example.com')->subject('Test Email');
    });
    echo 'Email sent successfully';
} catch (Exception \$e) {
    echo 'Email error: ' . \$e->getMessage();
}
"
```

### **Test OTP Notification**
```bash
php artisan tinker --execute="
\$user = App\Models\User::where('email', 'test@example.com')->first();
if(\$user) {
    try {
        \$user->notify(new App\Notifications\OtpNotification('123456'));
        echo 'OTP notification sent successfully';
    } catch (Exception \$e) {
        echo 'OTP notification error: ' . \$e->getMessage();
    }
} else {
    echo 'User not found';
}
"
```

## 🧪 Postman Testing

### **Import the Collection**
1. Open Postman
2. Import `Agrar_Portal_API.postman_collection.json`
3. Update variables in the collection

### **Test Flow in Postman**
1. **Register User** → Get success message
2. **Get OTP Code** → Get OTP from response
3. **Verify OTP** → Get token
4. **Login** → Should work with verified user

## ✅ Success Indicators

### **OTP System is Working If:**

1. ✅ **Registration** returns success message
2. ✅ **Get OTP** returns 6-digit code
3. ✅ **Verify OTP** returns token and verified user
4. ✅ **Login** works after verification
5. ✅ **Invalid OTP** returns error message
6. ✅ **Unverified login** returns verification required message
7. ✅ **Database** shows correct user status

### **Common Issues & Solutions:**

| Issue | Solution |
|-------|----------|
| OTP not received in email | Check spam folder, verify SMTP settings, use resend OTP |
| Invalid OTP error | Check the exact 6-digit code |
| Login fails after verification | Clear browser cache, check token |
| Database shows wrong status | Run migration: `php artisan migrate` |

## 🔧 Troubleshooting Commands

### **Check Routes**
```bash
php artisan route:list --path=api/v1/auth
```

### **Check Email Configuration**
```bash
grep -E "MAIL_" .env
```

### **Check Laravel Logs**
```bash
tail -20 storage/logs/laravel.log
```

### **Clear Cache**
```bash
php artisan config:clear
php artisan cache:clear
```

## 📊 Test Results Summary

When all tests pass, you should see:
- ✅ Registration working
- ✅ OTP generation working
- ✅ OTP verification working
- ✅ Login after verification working
- ✅ Error handling working
- ✅ Database storage working

**Your OTP system is fully functional!** 🎉 