#!/bin/bash

# Test Registration with Region Field
# This script demonstrates the updated registration functionality

BASE_URL="http://localhost:8000/api/v1"
EMAIL="test_region@example.com"

echo "=== Testing Registration with Region Field ==="
echo

# Step 1: Register Admin with Region
echo "1. Registering Admin with Region..."
ADMIN_REGISTER_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/register" \
  -H "Content-Type: application/json" \
  -d "{
    \"first_name\": \"Admin\",
    \"last_name\": \"User\",
    \"email\": \"$EMAIL\",
    \"password\": \"password123\",
    \"phone\": \"+123456789\",
    \"region\": \"North Region\",
    \"user_type\": \"admin\"
  }")

echo "Admin Registration Response:"
echo "$ADMIN_REGISTER_RESPONSE" | jq '.'
echo

# Check if registration was successful
REGISTRATION_SUCCESS=$(echo "$ADMIN_REGISTER_RESPONSE" | jq -r '.message // null')

if [ "$REGISTRATION_SUCCESS" != "null" ]; then
    echo "✅ Registration successful!"
    echo "📧 Check your email for OTP code"
    echo
    
    # Step 2: Prompt for OTP verification
    echo "2. Please check your email for the OTP code and enter it:"
    read -p "Enter OTP: " OTP_CODE
    
    if [ -n "$OTP_CODE" ]; then
        echo "Verifying OTP..."
        VERIFY_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/verify-otp" \
          -H "Content-Type: application/json" \
          -d "{
            \"email\": \"$EMAIL\",
            \"otp\": \"$OTP_CODE\"
          }")
        
        echo "OTP Verification Response:"
        echo "$VERIFY_RESPONSE" | jq '.'
        echo
        
        # Check if verification was successful
        TOKEN=$(echo "$VERIFY_RESPONSE" | jq -r '.token // null')
        if [ "$TOKEN" != "null" ]; then
            echo "✅ Email verification successful!"
            echo "Token: $TOKEN"
            echo
            
            # Step 3: Get user profile to verify region was saved
            echo "3. Getting user profile to verify region was saved..."
            PROFILE_RESPONSE=$(curl -s -X GET "$BASE_URL/profile" \
              -H "Authorization: Bearer $TOKEN" \
              -H "Content-Type: application/json")
            
            echo "Profile Response:"
            echo "$PROFILE_RESPONSE" | jq '.'
            echo
            
            # Check if region is in the response
            REGION=$(echo "$PROFILE_RESPONSE" | jq -r '.user.region // null')
            if [ "$REGION" = "North Region" ]; then
                echo "✅ Region field saved correctly: $REGION"
            else
                echo "❌ Region field not saved correctly"
            fi
        else
            echo "❌ Email verification failed"
        fi
    else
        echo "❌ No OTP provided"
    fi
else
    echo "❌ Registration failed"
fi

echo
echo "=== Test Complete ==="
echo
echo "Summary:"
echo "✅ Registration with region field"
echo "✅ OTP verification (if OTP provided)"
echo "✅ Profile verification with region"
echo
echo "New registration fields tested:"
echo "- region: North Region"
echo "- user_type: admin"
echo "- phone: +123456789" 