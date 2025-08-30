#!/bin/bash

# Test Admin 2FA Toggle
# This script demonstrates how an admin can toggle 2FA for users

BASE_URL="http://localhost:8000/api/v1"
ADMIN_EMAIL="admin@example.com"
ADMIN_PASSWORD="password123"
USER_ID=1  # Change this to the user ID you want to test with

echo "=== Testing Admin 2FA Toggle ==="
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

# Step 2: Get current user info
echo "2. Getting current user info..."
USER_INFO_RESPONSE=$(curl -s -X GET "$BASE_URL/users/$USER_ID" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json")

echo "User Info Response:"
echo "$USER_INFO_RESPONSE" | jq '.'
echo

# Extract current 2FA status
CURRENT_2FA=$(echo "$USER_INFO_RESPONSE" | jq -r '.two_factor_enabled // false')
echo "Current 2FA status: $CURRENT_2FA"
echo

# Step 3: Toggle 2FA (enable if disabled, disable if enabled)
NEW_2FA_STATUS="true"
if [ "$CURRENT_2FA" = "true" ]; then
    NEW_2FA_STATUS="false"
fi

echo "3. Toggling 2FA to: $NEW_2FA_STATUS"
TOGGLE_RESPONSE=$(curl -s -X POST "$BASE_URL/users/$USER_ID/toggle-2fa" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"two_factor_enabled\": $NEW_2FA_STATUS
  }")

echo "Toggle Response:"
echo "$TOGGLE_RESPONSE" | jq '.'
echo

# Step 4: Verify the change
echo "4. Verifying the change..."
UPDATED_USER_INFO=$(curl -s -X GET "$BASE_URL/users/$USER_ID" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json")

echo "Updated User Info:"
echo "$UPDATED_USER_INFO" | jq '.'
echo

UPDATED_2FA=$(echo "$UPDATED_USER_INFO" | jq -r '.two_factor_enabled // false')
echo "Updated 2FA status: $UPDATED_2FA"

if [ "$UPDATED_2FA" = "$NEW_2FA_STATUS" ]; then
    echo "✅ 2FA toggle successful!"
else
    echo "❌ 2FA toggle failed"
fi

echo
echo "=== Test Complete ===" 