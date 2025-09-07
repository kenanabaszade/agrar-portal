#!/bin/bash

# Google Authentication Test Only
# This script tests just the Google authentication flow

BASE_URL="http://localhost:8000"

echo "🔐 Google Authentication Test"
echo "============================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Get auth token
read -p "Enter your auth token: " AUTH_TOKEN

if [ -z "$AUTH_TOKEN" ]; then
    print_error "Auth token is required"
    exit 1
fi

# Step 1: Check current authentication status
print_status "Step 1: Check Google Authentication Status"
echo "GET $BASE_URL/api/v1/google/check-access"

RESPONSE=$(curl -s -X GET "$BASE_URL/api/v1/google/check-access" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json")

echo "Response:"
echo "$RESPONSE" | jq '.'
echo ""

# Parse response
HAS_ACCESS=$(echo $RESPONSE | jq -r '.has_access // false')
CAN_CREATE_MEETINGS=$(echo $RESPONSE | jq -r '.can_create_meetings // false')
TOKEN_VALID=$(echo $RESPONSE | jq -r '.token_valid // false')

echo "Status Summary:"
echo "- Has Access: $HAS_ACCESS"
echo "- Token Valid: $TOKEN_VALID"
echo "- Can Create Meetings: $CAN_CREATE_MEETINGS"
echo ""

if [ "$CAN_CREATE_MEETINGS" = "true" ]; then
    print_success "✅ User is fully authenticated and can create Google Meet meetings!"
    exit 0
fi

# Step 2: Get Google Auth URL
print_status "Step 2: Get Google Authentication URL"
echo "GET $BASE_URL/api/v1/google/auth-url"

AUTH_RESPONSE=$(curl -s -X GET "$BASE_URL/api/v1/google/auth-url" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json")

echo "Response:"
echo "$AUTH_RESPONSE" | jq '.'
echo ""

GOOGLE_AUTH_URL=$(echo $AUTH_RESPONSE | jq -r '.auth_url // empty')

if [ -n "$GOOGLE_AUTH_URL" ]; then
    print_success "✅ Google Auth URL obtained!"
    echo ""
    echo "🔗 Please visit this URL to authorize Google Calendar access:"
    echo "$GOOGLE_AUTH_URL"
    echo ""
    echo "After authorization, run this script again to verify the authentication."
else
    print_error "❌ Failed to get Google Auth URL"
    exit 1
fi
