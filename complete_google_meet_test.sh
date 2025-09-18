#!/bin/bash

echo "🚀 Complete Google Meet OAuth2 Integration Test"
echo "=============================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

BASE_URL="http://localhost:8000/api/v1"
AUTH_TOKEN="34|m1tDPv0d9hmb6S9nWTLJgeMLIeXU4p7JQUp3pv5ea41a8e42"

echo -e "\n${BLUE}Step 1: Check current Google Calendar access${NC}"
ACCESS_CHECK=$(curl -s -X GET "$BASE_URL/google/check-access" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json")

echo "$ACCESS_CHECK" | jq .

HAS_ACCESS=$(echo "$ACCESS_CHECK" | jq -r '.has_access')

if [ "$HAS_ACCESS" = "true" ]; then
    echo -e "${GREEN}✅ User already has Google Calendar access!${NC}"
else
    echo -e "${YELLOW}⚠️  User needs to authorize Google Calendar access${NC}"
    
    echo -e "\n${BLUE}Step 2: Get OAuth2 authorization URL${NC}"
    AUTH_RESPONSE=$(curl -s -X GET "$BASE_URL/google/auth-url" \
      -H "Authorization: Bearer $AUTH_TOKEN" \
      -H "Content-Type: application/json")
    
    echo "$AUTH_RESPONSE" | jq .
    
    AUTH_URL=$(echo "$AUTH_RESPONSE" | jq -r '.auth_url')
    
    echo -e "\n${YELLOW}🔗 Please visit this URL to authorize Google Calendar access:${NC}"
    echo -e "${BLUE}$AUTH_URL${NC}"
    
    echo -e "\n${YELLOW}📋 After authorization, you'll be redirected to:${NC}"
    echo -e "${BLUE}http://localhost:8000/api/v1/google/callback?code=...${NC}"
    
    echo -e "\n${YELLOW}⏳ Please complete the OAuth2 flow in your browser, then press Enter to continue...${NC}"
    read -p "Press Enter after completing OAuth2 authorization..."
    
    echo -e "\n${BLUE}Step 3: Check Google Calendar access again${NC}"
    ACCESS_CHECK_AFTER=$(curl -s -X GET "$BASE_URL/google/check-access" \
      -H "Authorization: Bearer $AUTH_TOKEN" \
      -H "Content-Type: application/json")
    
    echo "$ACCESS_CHECK_AFTER" | jq .
    
    HAS_ACCESS_AFTER=$(echo "$ACCESS_CHECK_AFTER" | jq -r '.has_access')
    
    if [ "$HAS_ACCESS_AFTER" = "true" ]; then
        echo -e "${GREEN}✅ Google Calendar access authorized successfully!${NC}"
    else
        echo -e "${RED}❌ Google Calendar access still not authorized${NC}"
        echo -e "${YELLOW}Please check the OAuth2 flow and try again${NC}"
        exit 1
    fi
fi

echo -e "\n${BLUE}Step 4: Create a Google Meet meeting${NC}"
MEETING_RESPONSE=$(curl -s -X POST "$BASE_URL/meetings" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test OAuth2 Google Meet Meeting",
    "description": "Testing Google Meet integration with OAuth2 authentication",
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
  }')

echo "$MEETING_RESPONSE" | jq .

SUCCESS=$(echo "$MEETING_RESPONSE" | jq -r '.success // false')

if [ "$SUCCESS" = "true" ]; then
    echo -e "${GREEN}✅ Google Meet meeting created successfully!${NC}"
    
    MEET_LINK=$(echo "$MEETING_RESPONSE" | jq -r '.meet_link // "N/A"')
    MEETING_ID=$(echo "$MEETING_RESPONSE" | jq -r '.meeting_id // "N/A"')
    EVENT_ID=$(echo "$MEETING_RESPONSE" | jq -r '.event_id // "N/A"')
    
    echo -e "\n${GREEN}📋 Meeting Details:${NC}"
    echo -e "${BLUE}Event ID: $EVENT_ID${NC}"
    echo -e "${BLUE}Meeting ID: $MEETING_ID${NC}"
    echo -e "${BLUE}Google Meet Link: $MEET_LINK${NC}"
    
    if [ "$MEET_LINK" != "N/A" ] && [ "$MEET_LINK" != "null" ]; then
        echo -e "\n${YELLOW}🔗 Google Meet Link:${NC}"
        echo -e "${GREEN}$MEET_LINK${NC}"
        
        echo -e "\n${YELLOW}📝 You can test this link by:${NC}"
        echo -e "1. Opening the link in your browser"
        echo -e "2. Verifying it opens Google Meet"
        echo -e "3. Checking if the meeting room is accessible"
        
        echo -e "\n${BLUE}Step 5: Test meeting retrieval${NC}"
        RETRIEVE_RESPONSE=$(curl -s -X GET "$BASE_URL/meetings/$EVENT_ID" \
          -H "Authorization: Bearer $AUTH_TOKEN" \
          -H "Content-Type: application/json")
        
        echo "$RETRIEVE_RESPONSE" | jq .
        
    else
        echo -e "${RED}❌ No Google Meet link generated${NC}"
    fi
    
else
    echo -e "${RED}❌ Failed to create Google Meet meeting${NC}"
    ERROR=$(echo "$MEETING_RESPONSE" | jq -r '.error // "Unknown error"')
    echo -e "${RED}Error: $ERROR${NC}"
fi

echo -e "\n${GREEN}🎉 Google Meet OAuth2 Integration Test Complete!${NC}"
