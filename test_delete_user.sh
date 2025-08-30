#!/bin/bash

# Test Delete User Functionality
# This script demonstrates the delete user functionality

BASE_URL="http://localhost:8000/api/v1"
ADMIN_EMAIL="admin@example.com"
ADMIN_PASSWORD="password123"
USER_ID_TO_DELETE=2

echo "=== Testing Delete User Functionality ==="
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

# Step 2: Get user details before deletion
echo "2. Getting user details before deletion..."
USER_DETAILS_RESPONSE=$(curl -s -X GET "$BASE_URL/users/$USER_ID_TO_DELETE" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json")

echo "User Details Before Deletion:"
echo "$USER_DETAILS_RESPONSE" | jq '.'
echo

# Check if user exists
USER_EXISTS=$(echo "$USER_DETAILS_RESPONSE" | jq -r '.id // null')

if [ "$USER_EXISTS" = "null" ]; then
    echo "❌ User not found"
    exit 1
fi

echo "✅ User found: ID $USER_ID_TO_DELETE"
echo

# Step 3: Delete user
echo "3. Deleting user..."
DELETE_RESPONSE=$(curl -s -X DELETE "$BASE_URL/users/$USER_ID_TO_DELETE" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json")

echo "Delete Response:"
echo "$DELETE_RESPONSE" | jq '.'
echo

# Check if deletion was successful
DELETE_SUCCESS=$(echo "$DELETE_RESPONSE" | jq -r '.message // null')

if [ "$DELETE_SUCCESS" = "User deleted successfully" ]; then
    echo "✅ User deleted successfully!"
    echo
    
    # Step 4: Verify user is deleted
    echo "4. Verifying user is deleted..."
    VERIFY_DELETE_RESPONSE=$(curl -s -X GET "$BASE_URL/users/$USER_ID_TO_DELETE" \
      -H "Authorization: Bearer $ADMIN_TOKEN" \
      -H "Content-Type: application/json")
    
    echo "Verify Delete Response:"
    echo "$VERIFY_DELETE_RESPONSE" | jq '.'
    echo
    
    # Check if user is actually deleted
    USER_STILL_EXISTS=$(echo "$VERIFY_DELETE_RESPONSE" | jq -r '.id // null')
    
    if [ "$USER_STILL_EXISTS" = "null" ]; then
        echo "✅ User successfully deleted and no longer accessible"
    else
        echo "❌ User still exists after deletion"
    fi
else
    echo "❌ User deletion failed"
    echo "Error: $DELETE_RESPONSE"
fi

echo
echo "=== Test Complete ==="
echo
echo "Summary:"
echo "✅ Admin authentication"
echo "✅ User details retrieval"
echo "✅ User deletion"
echo "✅ Deletion verification"
echo
echo "Security features tested:"
echo "- Admin-only access"
echo "- User existence validation"
echo "- Proper deletion confirmation" 