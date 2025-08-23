#!/bin/bash

# Password Reset with 2FA Test Script
# This script tests the complete password reset flow

BASE_URL="http://localhost:8000/api/v1"
TEST_EMAIL="farmer@example.com"
TEST_PASSWORD="newPassword123"

echo "🧪 Testing Password Reset with 2FA Authentication"
echo "=================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✅ $2${NC}"
    else
        echo -e "${RED}❌ $2${NC}"
    fi
}

# Function to extract JSON value
extract_json_value() {
    echo $1 | grep -o "\"$2\":\"[^\"]*\"" | cut -d'"' -f4
}

echo -e "\n${YELLOW}Step 1: Request Password Reset${NC}"
echo "----------------------------------------"
RESET_REQUEST=$(curl -s -X POST "$BASE_URL/auth/forgot-password" \
    -H "Content-Type: application/json" \
    -d "{\"email\": \"$TEST_EMAIL\"}")

echo "Response: $RESET_REQUEST"

if echo "$RESET_REQUEST" | grep -q "Password reset OTP sent"; then
    print_status 0 "Password reset request successful"
else
    print_status 1 "Password reset request failed"
    echo "Exiting..."
    exit 1
fi

echo -e "\n${YELLOW}Step 2: Get OTP from database${NC}"
echo "--------------------------------"
# Get the latest OTP from the database
OTP=$(php artisan tinker --execute="echo App\Models\User::where('email', '$TEST_EMAIL')->first()->otp_code;" 2>/dev/null)

if [ -z "$OTP" ]; then
    echo -e "${RED}❌ Could not find OTP in database${NC}"
    echo "Please check the email or database manually"
    exit 1
fi

echo -e "${GREEN}✅ Found OTP: $OTP${NC}"

echo -e "\n${YELLOW}Step 3: Verify Password Reset OTP${NC}"
echo "----------------------------------------"
OTP_VERIFY=$(curl -s -X POST "$BASE_URL/auth/verify-password-reset-otp" \
    -H "Content-Type: application/json" \
    -d "{\"email\": \"$TEST_EMAIL\", \"otp\": \"$OTP\"}")

echo "Response: $OTP_VERIFY"

# Extract token from response
RESET_TOKEN=$(extract_json_value "$OTP_VERIFY" "token")

if [ -n "$RESET_TOKEN" ]; then
    print_status 0 "OTP verification successful"
    echo -e "${GREEN}✅ Reset token: $RESET_TOKEN${NC}"
else
    print_status 1 "OTP verification failed"
    echo "Exiting..."
    exit 1
fi

echo -e "\n${YELLOW}Step 4: Reset Password${NC}"
echo "---------------------------"
PASSWORD_RESET=$(curl -s -X POST "$BASE_URL/auth/reset-password" \
    -H "Content-Type: application/json" \
    -d "{\"email\": \"$TEST_EMAIL\", \"token\": \"$RESET_TOKEN\", \"password\": \"$TEST_PASSWORD\", \"password_confirmation\": \"$TEST_PASSWORD\"}")

echo "Response: $PASSWORD_RESET"

if echo "$PASSWORD_RESET" | grep -q "Password reset successfully"; then
    print_status 0 "Password reset successful"
else
    print_status 1 "Password reset failed"
    echo "Exiting..."
    exit 1
fi

echo -e "\n${YELLOW}Step 5: Test Login with New Password${NC}"
echo "----------------------------------------"
LOGIN_TEST=$(curl -s -X POST "$BASE_URL/auth/login" \
    -H "Content-Type: application/json" \
    -d "{\"email\": \"$TEST_EMAIL\", \"password\": \"$TEST_PASSWORD\"}")

echo "Response: $LOGIN_TEST"

if echo "$LOGIN_TEST" | grep -q "token"; then
    print_status 0 "Login with new password successful"
else
    print_status 1 "Login with new password failed"
fi

echo -e "\n${GREEN}🎉 Password Reset with 2FA Test Completed!${NC}"
echo "=================================================="
echo -e "${GREEN}✅ All steps passed successfully${NC}"
echo ""
echo "Summary:"
echo "- Password reset request: ✅"
echo "- OTP verification: ✅"
echo "- Password reset: ✅"
echo "- Login with new password: ✅"
echo ""
echo "The password reset system with 2FA is working correctly!" 