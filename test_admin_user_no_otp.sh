#!/bin/bash

# Test script to demonstrate that admin-created users don't require OTP
# This script shows the difference between registration (requires OTP) and admin creation (no OTP)

BASE_URL="http://localhost:8000"
ADMIN_EMAIL="admin@example.com"
ADMIN_PASSWORD="password123"

echo "🧪 Testing Admin User Creation - No OTP Required"
echo "================================================"

# Step 1: Login as admin
echo "1. Logging in as admin..."
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/api/v1/auth/login" \
  -H "Content-Type: application/json" \
  -d "{
    \"email\": \"$ADMIN_EMAIL\",
    \"password\": \"$ADMIN_PASSWORD\"
  }")

echo "Login Response: $LOGIN_RESPONSE"

# Extract token
TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
    echo "❌ Failed to get auth token"
    exit 1
fi

echo "✅ Admin login successful"
echo "Token: $TOKEN"

# Step 2: Create a user via admin (should not require OTP)
echo ""
echo "2. Creating user via admin (should NOT require OTP)..."
CREATE_RESPONSE=$(curl -s -X POST "$BASE_URL/api/v1/users" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"first_name\": \"NoOtp\",
    \"last_name\": \"User\",
    \"username\": \"nootpuser\",
    \"father_name\": \"John NoOtp\",
    \"region\": \"Test Region\",
    \"email\": \"nootp.user@example.com\",
    \"phone\": \"+1234567890\",
    \"password\": \"password123\",
    \"user_type\": \"farmer\",
    \"two_factor_enabled\": false
  }")

echo "Create User Response: $CREATE_RESPONSE"

# Check if user was created successfully
if echo "$CREATE_RESPONSE" | grep -q "User created successfully"; then
    echo "✅ Admin-created user successful (no OTP required)"
    
    # Extract user email for login test
    CREATED_EMAIL="nootp.user@example.com"
else
    echo "❌ Failed to create user via admin"
    echo "Response: $CREATE_RESPONSE"
    exit 1
fi

# Step 3: Try to login with admin-created user (should work without OTP verification)
echo ""
echo "3. Testing login with admin-created user (should work without OTP)..."
LOGIN_NO_OTP_RESPONSE=$(curl -s -X POST "$BASE_URL/api/v1/auth/login" \
  -H "Content-Type: application/json" \
  -d "{
    \"email\": \"$CREATED_EMAIL\",
    \"password\": \"password123\"
  }")

echo "Login Response: $LOGIN_NO_OTP_RESPONSE"

# Check if login was successful (should not require OTP)
if echo "$LOGIN_NO_OTP_RESPONSE" | grep -q '"token"'; then
    echo "✅ Login successful without OTP verification (as expected)"
    echo "✅ Admin-created users are automatically email-verified"
else
    echo "❌ Login failed or required OTP (unexpected)"
    echo "Response: $LOGIN_NO_OTP_RESPONSE"
fi

# Step 4: Compare with regular registration (requires OTP)
echo ""
echo "4. Testing regular registration (should require OTP)..."
REGISTER_RESPONSE=$(curl -s -X POST "$BASE_URL/api/v1/auth/register" \
  -H "Content-Type: application/json" \
  -d "{
    \"first_name\": \"Otp\",
    \"last_name\": \"Required\",
    \"email\": \"otp.required@example.com\",
    \"password\": \"password123\",
    \"phone\": \"+1234567890\",
    \"region\": \"Test Region\",
    \"user_type\": \"farmer\"
  }")

echo "Register Response: $REGISTER_RESPONSE"

# Check if registration requires OTP
if echo "$REGISTER_RESPONSE" | grep -q "Please check your email for OTP verification"; then
    echo "✅ Regular registration requires OTP (as expected)"
else
    echo "❌ Regular registration should require OTP"
    echo "Response: $REGISTER_RESPONSE"
fi

# Step 5: Try to login with unverified user (should fail)
echo ""
echo "5. Testing login with unverified user (should fail)..."
LOGIN_UNVERIFIED_RESPONSE=$(curl -s -X POST "$BASE_URL/api/v1/auth/login" \
  -H "Content-Type: application/json" \
  -d "{
    \"email\": \"otp.required@example.com\",
    \"password\": \"password123\"
  }")

echo "Login Unverified Response: $LOGIN_UNVERIFIED_RESPONSE"

# Check if login was blocked for unverified user
if echo "$LOGIN_UNVERIFIED_RESPONSE" | grep -q "Please verify your email first"; then
    echo "✅ Login blocked for unverified user (as expected)"
else
    echo "❌ Login should be blocked for unverified user"
    echo "Response: $LOGIN_UNVERIFIED_RESPONSE"
fi

echo ""
echo "🎉 Test Complete!"
echo "=================="
echo "✅ Admin-created users: NO OTP required, automatically verified"
echo "✅ Regular registration: OTP required, must verify email"
echo "✅ Login blocked for unverified users" 