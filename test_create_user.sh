#!/bin/bash

# Test script for Admin User Creation
# This script tests the admin ability to create new users

BASE_URL="http://localhost:8000"
ADMIN_EMAIL="admin@example.com"
ADMIN_PASSWORD="password123"

echo "🧪 Testing Admin User Creation"
echo "================================"

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

# Step 2: Create a new farmer user
echo ""
echo "2. Creating a new farmer user..."
CREATE_RESPONSE=$(curl -s -X POST "$BASE_URL/api/v1/users" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"first_name\": \"John\",
    \"last_name\": \"Farmer\",
    \"username\": \"johnfarmer\",
    \"father_name\": \"Robert Farmer\",
    \"region\": \"North Region\",
    \"email\": \"john.farmer@example.com\",
    \"phone\": \"+1234567890\",
    \"password\": \"password123\",
    \"user_type\": \"farmer\",
    \"two_factor_enabled\": false
  }")

echo "Create User Response: $CREATE_RESPONSE"

# Check if user was created successfully
if echo "$CREATE_RESPONSE" | grep -q "User created successfully"; then
    echo "✅ Farmer user created successfully"
    
    # Extract user ID for further testing
    USER_ID=$(echo $CREATE_RESPONSE | grep -o '"id":[0-9]*' | cut -d':' -f2)
    echo "Created User ID: $USER_ID"
else
    echo "❌ Failed to create farmer user"
    echo "Response: $CREATE_RESPONSE"
fi

# Step 3: Create a new trainer user
echo ""
echo "3. Creating a new trainer user..."
CREATE_TRAINER_RESPONSE=$(curl -s -X POST "$BASE_URL/api/v1/users" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"first_name\": \"Sarah\",
    \"last_name\": \"Trainer\",
    \"username\": \"sarahtrainer\",
    \"father_name\": \"Michael Trainer\",
    \"region\": \"Central Region\",
    \"email\": \"sarah.trainer@example.com\",
    \"phone\": \"+1987654321\",
    \"password\": \"password123\",
    \"user_type\": \"trainer\",
    \"two_factor_enabled\": true
  }")

echo "Create Trainer Response: $CREATE_TRAINER_RESPONSE"

if echo "$CREATE_TRAINER_RESPONSE" | grep -q "User created successfully"; then
    echo "✅ Trainer user created successfully"
else
    echo "❌ Failed to create trainer user"
    echo "Response: $CREATE_TRAINER_RESPONSE"
fi

# Step 4: Create a new admin user
echo ""
echo "4. Creating a new admin user..."
CREATE_ADMIN_RESPONSE=$(curl -s -X POST "$BASE_URL/api/v1/users" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"first_name\": \"Admin\",
    \"last_name\": \"Assistant\",
    \"username\": \"adminassistant\",
    \"father_name\": \"James Admin\",
    \"region\": \"South Region\",
    \"email\": \"admin.assistant@example.com\",
    \"phone\": \"+1122334455\",
    \"password\": \"password123\",
    \"user_type\": \"admin\",
    \"two_factor_enabled\": false
  }")

echo "Create Admin Response: $CREATE_ADMIN_RESPONSE"

if echo "$CREATE_ADMIN_RESPONSE" | grep -q "User created successfully"; then
    echo "✅ Admin user created successfully"
else
    echo "❌ Failed to create admin user"
    echo "Response: $CREATE_ADMIN_RESPONSE"
fi

# Step 5: Test validation - try to create user with existing email
echo ""
echo "5. Testing validation - duplicate email..."
DUPLICATE_RESPONSE=$(curl -s -X POST "$BASE_URL/api/v1/users" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"first_name\": \"Duplicate\",
    \"last_name\": \"User\",
    \"email\": \"$ADMIN_EMAIL\",
    \"password\": \"password123\",
    \"user_type\": \"farmer\"
  }")

echo "Duplicate Email Response: $DUPLICATE_RESPONSE"

if echo "$DUPLICATE_RESPONSE" | grep -q "validation"; then
    echo "✅ Validation working correctly - duplicate email rejected"
else
    echo "❌ Validation failed - duplicate email should be rejected"
fi

# Step 6: Test validation - missing required fields
echo ""
echo "6. Testing validation - missing required fields..."
MISSING_FIELDS_RESPONSE=$(curl -s -X POST "$BASE_URL/api/v1/users" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"first_name\": \"Incomplete\"
  }")

echo "Missing Fields Response: $MISSING_FIELDS_RESPONSE"

if echo "$MISSING_FIELDS_RESPONSE" | grep -q "validation"; then
    echo "✅ Validation working correctly - missing fields rejected"
else
    echo "❌ Validation failed - missing fields should be rejected"
fi

# Step 7: List all users to see created users
echo ""
echo "7. Listing all users..."
LIST_RESPONSE=$(curl -s -X GET "$BASE_URL/api/v1/users" \
  -H "Authorization: Bearer $TOKEN")

echo "List Users Response: $LIST_RESPONSE"

echo ""
echo "🎉 Admin User Creation Test Complete!"
echo "=====================================" 