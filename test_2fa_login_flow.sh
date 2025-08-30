#!/bin/bash

# Test 2FA Login Flow
# This script demonstrates the complete 2FA login process

BASE_URL="http://localhost:8000/api/v1"
EMAIL="test@example.com"
PASSWORD="password123"

echo "=== Testing 2FA Login Flow ==="
echo

# Step 1: Login attempt (should trigger 2FA if enabled)
echo "1. Attempting login..."
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d "{
    \"email\": \"$EMAIL\",
    \"password\": \"$PASSWORD\"
  }")

echo "Login Response:"
echo "$LOGIN_RESPONSE" | jq '.'
echo

# Check if 2FA is required
NEEDS_2FA=$(echo "$LOGIN_RESPONSE" | jq -r '.needs_2fa // false')
USER_ID=$(echo "$LOGIN_RESPONSE" | jq -r '.user_id // null')

if [ "$NEEDS_2FA" = "true" ] && [ "$USER_ID" != "null" ]; then
    echo "2FA is required for this user (ID: $USER_ID)"
    echo
    
    # Step 2: Resend OTP if needed
    echo "2. Resending 2FA OTP..."
    RESEND_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/resend-login-otp" \
      -H "Content-Type: application/json" \
      -d "{
        \"user_id\": $USER_ID
      }")
    
    echo "Resend Response:"
    echo "$RESEND_RESPONSE" | jq '.'
    echo
    
    # Step 3: Verify OTP (you would get this from email)
    echo "3. Please check your email for the OTP code and enter it:"
    read -p "Enter OTP: " OTP_CODE
    
    echo "Verifying OTP..."
    VERIFY_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/verify-login-otp" \
      -H "Content-Type: application/json" \
      -d "{
        \"user_id\": $USER_ID,
        \"otp\": \"$OTP_CODE\"
      }")
    
    echo "Verify Response:"
    echo "$VERIFY_RESPONSE" | jq '.'
    echo
    
    # Extract token if successful
    TOKEN=$(echo "$VERIFY_RESPONSE" | jq -r '.token // null')
    if [ "$TOKEN" != "null" ]; then
        echo "✅ Login successful! Token: $TOKEN"
    else
        echo "❌ Login failed"
    fi
else
    echo "No 2FA required or login failed"
    TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.token // null')
    if [ "$TOKEN" != "null" ]; then
        echo "✅ Login successful! Token: $TOKEN"
    else
        echo "❌ Login failed"
    fi
fi

echo
echo "=== Test Complete ===" 