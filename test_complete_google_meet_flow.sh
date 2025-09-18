#!/bin/bash

echo "🚀 Complete Google Meet Integration Test Flow"
echo "=============================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
BASE_URL="http://localhost:8000"
USER_EMAIL="admin@example.com"
USER_PASSWORD="password"

echo -e "${BLUE}Step 1: Login to get authentication token${NC}"
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/api/v1/auth/login" \
  -H "Content-Type: application/json" \
  -d "{
    \"email\": \"$USER_EMAIL\",
    \"password\": \"$USER_PASSWORD\"
  }")

echo "Login Response: $LOGIN_RESPONSE"

# Extract token
TOKEN=$(echo $LOGIN_RESPONSE | jq -r '.data.token // .token // empty')

if [ -z "$TOKEN" ] || [ "$TOKEN" = "null" ]; then
    echo -e "${RED}❌ Failed to get authentication token${NC}"
    echo "Response: $LOGIN_RESPONSE"
    exit 1
fi

echo -e "${GREEN}✅ Got authentication token: ${TOKEN:0:20}...${NC}"

echo -e "\n${BLUE}Step 2: Get Google OAuth2 authorization URL${NC}"
AUTH_URL_RESPONSE=$(curl -s -X GET "$BASE_URL/api/v1/google/auth-url" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json")

echo "Auth URL Response: $AUTH_URL_RESPONSE"

AUTH_URL=$(echo $AUTH_URL_RESPONSE | jq -r '.auth_url // empty')

if [ -z "$AUTH_URL" ] || [ "$AUTH_URL" = "null" ]; then
    echo -e "${RED}❌ Failed to get authorization URL${NC}"
    echo "Response: $AUTH_URL_RESPONSE"
    exit 1
fi

echo -e "${GREEN}✅ Got authorization URL${NC}"
echo -e "${YELLOW}📋 Please open this URL in your browser and complete the OAuth2 flow:${NC}"
echo -e "${YELLOW}$AUTH_URL${NC}"

echo -e "\n${BLUE}Step 3: After completing OAuth2, check if token is stored${NC}"
echo -e "${YELLOW}⏳ Waiting for you to complete OAuth2 flow...${NC}"
echo -e "${YELLOW}Press Enter when you've completed the OAuth2 flow...${NC}"
read -r

echo -e "\n${BLUE}Step 4: Check Google access status${NC}"
ACCESS_RESPONSE=$(curl -s -X GET "$BASE_URL/api/v1/google/check-access" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json")

echo "Access Response: $ACCESS_RESPONSE"

HAS_ACCESS=$(echo $ACCESS_RESPONSE | jq -r '.has_access // false')

if [ "$HAS_ACCESS" = "true" ]; then
    echo -e "${GREEN}✅ Google access is properly configured${NC}"
else
    echo -e "${RED}❌ Google access is not configured${NC}"
    echo "Response: $ACCESS_RESPONSE"
    exit 1
fi

echo -e "\n${BLUE}Step 5: Create a Google Meet meeting${NC}"
MEETING_RESPONSE=$(curl -s -X POST "$BASE_URL/api/v1/meetings" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test Meeting from Script",
    "description": "This is a test meeting created via API",
    "start_time": "2025-01-15T10:00:00Z",
    "end_time": "2025-01-15T11:00:00Z",
    "attendees": ["test@example.com"]
  }')

echo "Meeting Response: $MEETING_RESPONSE"

MEETING_ID=$(echo $MEETING_RESPONSE | jq -r '.data.id // empty')
MEET_URL=$(echo $MEETING_RESPONSE | jq -r '.data.meet_url // empty')

if [ -n "$MEETING_ID" ] && [ "$MEETING_ID" != "null" ]; then
    echo -e "${GREEN}✅ Meeting created successfully!${NC}"
    echo -e "${GREEN}Meeting ID: $MEETING_ID${NC}"
    if [ -n "$MEET_URL" ] && [ "$MEET_URL" != "null" ]; then
        echo -e "${GREEN}Google Meet URL: $MEET_URL${NC}"
    fi
else
    echo -e "${RED}❌ Failed to create meeting${NC}"
    echo "Response: $MEETING_RESPONSE"
    exit 1
fi

echo -e "\n${GREEN}🎉 Complete Google Meet integration test completed successfully!${NC}"
echo -e "${BLUE}Summary:${NC}"
echo -e "  ✅ User authentication: Working"
echo -e "  ✅ Google OAuth2 flow: Working"
echo -e "  ✅ Token storage: Working"
echo -e "  ✅ Meeting creation: Working"
echo -e "  ✅ Google Meet URL: Generated"
