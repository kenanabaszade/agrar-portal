#!/bin/bash

# Test 2FA Toggle Fix
# This script demonstrates that disabling 2FA clears OTP codes

BASE_URL="http://localhost:8000/api/v1"
ADMIN_EMAIL="admin@example.com"
ADMIN_PASSWORD="password123"
USER_EMAIL="test@example.com"
USER_PASSWORD="password123"
USER_ID=1

echo "=== Testing 2FA Toggle Fix ==="
echo

# Step 1: Login as admin
echo "1. Logging in as admin..."
ADMIN_LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d "{
    \"email\": \"$ADMIN_EMAIL\",
    \"password\": \"$ADMIN_PASSWORD\"
  }")

echo "Admin Login Response:"
echo "$ADMIN_LOGIN_RESPONSE" | jq '.'
echo

# Extract admin token
ADMIN_TOKEN=$(echo "$ADMIN_LOGIN_RESPONSE" | jq -r '.token // null')

if [ "$ADMIN_TOKEN" = "null" ]; then
    echo "❌ Admin login failed"
    exit 1
fi

echo "✅ Admin login successful! Token: $ADMIN_TOKEN"
echo

# Step 2: Enable 2FA for user
echo "2. Enabling 2FA for user..."
ENABLE_2FA_RESPONSE=$(curl -s -X POST "$BASE_URL/users/$USER_ID/toggle-2fa" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"two_factor_enabled\": true
  }")

echo "Enable 2FA Response:"
echo "$ENABLE_2FA_RESPONSE" | jq '.'
echo

# Step 3: Try to login as user (should require 2FA)
echo "3. Attempting login as user (should require 2FA)..."
USER_LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d "{
    \"email\": \"$USER_EMAIL\",
    \"password\": \"$USER_PASSWORD\"
  }")

echo "User Login Response (2FA Enabled):"
echo "$USER_LOGIN_RESPONSE" | jq '.'
echo

# Check if 2FA is required
NEEDS_2FA=$(echo "$USER_LOGIN_RESPONSE" | jq -r '.needs_2fa // false')
USER_ID_FOR_2FA=$(echo "$USER_LOGIN_RESPONSE" | jq -r '.user_id // null')

if [ "$NEEDS_2FA" = "true" ]; then
    echo "✅ 2FA is working correctly - OTP required"
    echo
    
    # Step 4: Disable 2FA for user
    echo "4. Disabling 2FA for user..."
    DISABLE_2FA_RESPONSE=$(curl -s -X POST "$BASE_URL/users/$USER_ID/toggle-2fa" \
      -H "Authorization: Bearer $ADMIN_TOKEN" \
      -H "Content-Type: application/json" \
      -d "{
        \"two_factor_enabled\": false
      }")

    echo "Disable 2FA Response:"
    echo "$DISABLE_2FA_RESPONSE" | jq '.'
    echo

    # Step 5: Try to login again (should NOT require 2FA)
    echo "5. Attempting login as user again (should NOT require 2FA)..."
    USER_LOGIN_AGAIN_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login" \
      -H "Content-Type: application/json" \
      -d "{
        \"email\": \"$USER_EMAIL\",
        \"password\": \"$USER_PASSWORD\"
      }")

    echo "User Login Response (2FA Disabled):"
    echo "$USER_LOGIN_AGAIN_RESPONSE" | jq '.'
    echo

    # Check if login was successful without 2FA
    TOKEN=$(echo "$USER_LOGIN_AGAIN_RESPONSE" | jq -r '.token // null')
    NEEDS_2FA_AGAIN=$(echo "$USER_LOGIN_AGAIN_RESPONSE" | jq -r '.needs_2fa // false')

    if [ "$TOKEN" != "null" ] && [ "$NEEDS_2FA_AGAIN" = "false" ]; then
        echo "✅ SUCCESS: 2FA disabled correctly - Login successful without OTP!"
        echo "Token: $TOKEN"
    else
        echo "❌ FAILED: 2FA still required even after disabling"
        echo "This indicates the fix is needed"
    fi
else
    echo "❌ 2FA not working as expected"
fi

echo
echo "=== Test Complete ==="
echo
echo "Expected behavior:"
echo "1. Enable 2FA → Login requires OTP"
echo "2. Disable 2FA → Login works without OTP"
echo "3. OTP codes should be cleared when 2FA is disabled" 