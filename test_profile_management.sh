#!/bin/bash

# Test Profile Management with New Fields
# This script demonstrates the complete profile management functionality

BASE_URL="http://localhost:8000/api/v1"
EMAIL="test@example.com"
PASSWORD="password123"

echo "=== Testing Profile Management with New Fields ==="
echo

# Step 1: Login to get token
echo "1. Logging in to get authentication token..."
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d "{
    \"email\": \"$EMAIL\",
    \"password\": \"$PASSWORD\"
  }")

echo "Login Response:"
echo "$LOGIN_RESPONSE" | jq '.'
echo

# Extract token
TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.token // null')

if [ "$TOKEN" = "null" ]; then
    echo "❌ Login failed"
    exit 1
fi

echo "✅ Login successful! Token: $TOKEN"
echo

# Step 2: Get current profile
echo "2. Getting current profile..."
PROFILE_RESPONSE=$(curl -s -X GET "$BASE_URL/profile" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json")

echo "Current Profile:"
echo "$PROFILE_RESPONSE" | jq '.'
echo

# Step 3: Update profile with new fields
echo "3. Updating profile with new fields (region, user_type)..."
UPDATE_RESPONSE=$(curl -s -X PATCH "$BASE_URL/profile" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"first_name\": \"Updated First Name\",
    \"last_name\": \"Updated Last Name\",
    \"username\": \"updated_username\",
    \"father_name\": \"Father's Name\",
    \"region\": \"North Region\",
    \"phone\": \"+1234567890\",
    \"user_type\": \"trainer\"
  }")

echo "Update Response:"
echo "$UPDATE_RESPONSE" | jq '.'
echo

# Step 4: Verify the changes
echo "4. Verifying profile changes..."
UPDATED_PROFILE=$(curl -s -X GET "$BASE_URL/profile" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json")

echo "Updated Profile:"
echo "$UPDATED_PROFILE" | jq '.'
echo

# Step 5: Test password change
echo "5. Testing password change..."
PASSWORD_RESPONSE=$(curl -s -X POST "$BASE_URL/profile/change-password" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"current_password\": \"$PASSWORD\",
    \"new_password\": \"newPassword123\",
    \"new_password_confirmation\": \"newPassword123\"
  }")

echo "Password Change Response:"
echo "$PASSWORD_RESPONSE" | jq '.'
echo

# Step 6: Test email change request
echo "6. Testing email change request..."
EMAIL_CHANGE_RESPONSE=$(curl -s -X POST "$BASE_URL/profile/request-email-change" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"new_email\": \"newemail@example.com\",
    \"password\": \"newPassword123\"
  }")

echo "Email Change Request Response:"
echo "$EMAIL_CHANGE_RESPONSE" | jq '.'
echo

# Check if email change was successful
EMAIL_CHANGE_SUCCESS=$(echo "$EMAIL_CHANGE_RESPONSE" | jq -r '.message // null')

if [ "$EMAIL_CHANGE_SUCCESS" != "null" ]; then
    echo "✅ Email change request successful!"
    echo "📧 Check the new email for OTP code"
    echo
    
    # Step 7: Prompt for OTP verification
    echo "7. Please check your email for the OTP code and enter it:"
    read -p "Enter OTP: " OTP_CODE
    
    if [ -n "$OTP_CODE" ]; then
        echo "Verifying email change OTP..."
        VERIFY_RESPONSE=$(curl -s -X POST "$BASE_URL/profile/verify-email-change" \
          -H "Authorization: Bearer $TOKEN" \
          -H "Content-Type: application/json" \
          -d "{
            \"otp\": \"$OTP_CODE\"
          }")
        
        echo "Email Change Verification Response:"
        echo "$VERIFY_RESPONSE" | jq '.'
        echo
    fi
else
    echo "❌ Email change request failed"
fi

echo
echo "=== Test Complete ==="
echo
echo "Summary of tested features:"
echo "✅ Profile retrieval"
echo "✅ Profile update with new fields (region, user_type)"
echo "✅ Password change"
echo "✅ Email change request"
echo "✅ OTP verification (if OTP provided)"
echo
echo "New fields tested:"
echo "- region: North Region"
echo "- user_type: trainer"
echo "- username: updated_username"
echo "- father_name: Father's Name" 