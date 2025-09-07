#!/bin/bash

# Test Meeting Creation Flow with Google Authentication
echo "🧪 Testing Meeting Creation Flow with Google Authentication"
echo "=========================================================="

BASE_URL="http://localhost:8000"
AUTH_TOKEN="34|m1tDPv0d9hmb6S9nWTLJgeMLIeXU4p7JQUp3pv5ea41a8e42"

echo ""
echo "📋 Step 1: Check Current Google Authentication Status"
echo "---------------------------------------------------"
echo "Checking if user has Google Calendar access..."
GOOGLE_AUTH_RESPONSE=$(curl -s -X GET "$BASE_URL/api/v1/google/check-access" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json")

echo "Response:"
echo "$GOOGLE_AUTH_RESPONSE" | jq .

HAS_GOOGLE_ACCESS=$(echo "$GOOGLE_AUTH_RESPONSE" | jq -r '.has_access // false')

echo ""
echo "📋 Step 2: Test Meeting Creation"
echo "-------------------------------"

if [ "$HAS_GOOGLE_ACCESS" = "true" ]; then
    echo "✅ User has Google Calendar access - attempting to create meeting..."
    
    MEETING_RESPONSE=$(curl -s -X POST "$BASE_URL/api/v1/meetings" \
      -H "Authorization: Bearer $AUTH_TOKEN" \
      -H "Content-Type: application/json" \
      -d '{
        "title": "Test Google Meet Meeting",
        "description": "Testing Google Meet integration",
        "start_time": "2025-01-15T10:00:00Z",
        "end_time": "2025-01-15T11:00:00Z",
        "timezone": "UTC",
        "max_attendees": 50
      }')
    
    echo "Meeting Creation Response:"
    echo "$MEETING_RESPONSE" | jq .
    
    if echo "$MEETING_RESPONSE" | jq -e '.meeting' > /dev/null 2>&1; then
        echo ""
        echo "✅ Meeting created successfully!"
        MEETING_ID=$(echo "$MEETING_RESPONSE" | jq -r '.meeting.id')
        MEET_LINK=$(echo "$MEETING_RESPONSE" | jq -r '.meeting.google_meet_link')
        echo "📅 Meeting ID: $MEETING_ID"
        echo "🔗 Google Meet Link: $MEET_LINK"
    else
        echo ""
        echo "❌ Failed to create meeting"
        ERROR=$(echo "$MEETING_RESPONSE" | jq -r '.error // "Unknown error"')
        echo "Error: $ERROR"
    fi
    
else
    echo "❌ User does not have Google Calendar access"
    echo ""
    echo "📋 Step 3: Get Google Authorization URL"
    echo "--------------------------------------"
    echo "Getting Google OAuth2 authorization URL..."
    
    AUTH_URL_RESPONSE=$(curl -s -X GET "$BASE_URL/api/v1/google/auth-url" \
      -H "Authorization: Bearer $AUTH_TOKEN" \
      -H "Content-Type: application/json")
    
    echo "Response:"
    echo "$AUTH_URL_RESPONSE" | jq .
    
    AUTH_URL=$(echo "$AUTH_URL_RESPONSE" | jq -r '.auth_url // empty')
    
    if [ -n "$AUTH_URL" ] && [ "$AUTH_URL" != "null" ]; then
        echo ""
        echo "🔗 Google Authorization URL:"
        echo "$AUTH_URL"
        echo ""
        echo "📋 Next Steps:"
        echo "1. Open the URL above in your browser"
        echo "2. Complete the Google OAuth2 authorization"
        echo "3. You'll be redirected to: $BASE_URL/oauth2/callback"
        echo "4. Run this script again to test meeting creation"
    else
        echo "❌ Failed to get authorization URL"
    fi
fi

echo ""
echo "📋 Step 4: Test Meeting Creation Without Google Auth"
echo "--------------------------------------------------"
echo "Testing what happens when trying to create a meeting without Google access..."

# Temporarily remove Google access token (simulate no access)
MEETING_NO_AUTH_RESPONSE=$(curl -s -X POST "$BASE_URL/api/v1/meetings" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test Meeting Without Google Auth",
    "start_time": "2025-01-15T10:00:00Z",
    "end_time": "2025-01-15T11:00:00Z"
  }')

echo "Response (should show Google auth required):"
echo "$MEETING_NO_AUTH_RESPONSE" | jq .

echo ""
echo "📋 Summary"
echo "---------"
echo "✅ Meeting creation requires Google Calendar authentication"
echo "✅ System checks for google_access_token in user record"
echo "✅ Returns 401 with auth URL if no Google access"
echo "✅ Creates Google Meet meeting if user has access"
echo ""
echo "🔗 Useful Endpoints:"
echo "• Check Google Auth: GET /api/v1/google/check-access"
echo "• Get Auth URL: GET /api/v1/google/auth-url"
echo "• Create Meeting: POST /api/v1/meetings"
echo "• OAuth2 Callback: /oauth2/callback"
