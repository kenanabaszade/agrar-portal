#!/bin/bash

echo "🧪 Testing Google Meet OAuth2 Flow"
echo "=================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

BASE_URL="http://localhost:8000/api/v1"
AUTH_TOKEN="34|m1tDPv0d9hmb6S9nWTLJgeMLIeXU4p7JQUp3pv5ea41a8e42"

echo -e "\n${BLUE}Step 1: Check Google Calendar access${NC}"
curl -s -X GET "$BASE_URL/google/check-access" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json" | jq .

echo -e "\n${BLUE}Step 2: Get Google OAuth2 authorization URL${NC}"
curl -s -X GET "$BASE_URL/google/auth-url" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json" | jq .

echo -e "\n${BLUE}Step 3: Test meeting creation (should fail without Google auth)${NC}"
curl -s -X POST "$BASE_URL/meetings" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test OAuth2 Google Meet",
    "description": "Testing Google Meet with OAuth2 flow",
    "start_time": "2025-09-15 10:00:00",
    "end_time": "2025-09-15 12:00:00",
    "timezone": "UTC",
    "max_attendees": 50,
    "training_id": 5,
    "is_recurring": false,
    "attendees": [
      {
        "email": "kenanabaszadeh@gmail.com",
        "name": "John Farmer"
      }
    ]
  }' | jq .

echo -e "\n${GREEN}✅ Test completed!${NC}"
echo -e "${YELLOW}To complete the setup:${NC}"
echo -e "1. Visit the auth_url from Step 2"
echo -e "2. Authorize Google Calendar access"
echo -e "3. Copy the code from the callback URL"
echo -e "4. Use the code to complete the OAuth2 flow"