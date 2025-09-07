#!/bin/bash

# Test Complete OAuth2 Flow
echo "🧪 Testing Complete OAuth2 Flow"
echo "==============================="

BASE_URL="http://localhost:8000"
AUTH_TOKEN="34|m1tDPv0d9hmb6S9nWTLJgeMLIeXU4p7JQUp3pv5ea41a8e42"

echo ""
echo "📋 Step 1: Get OAuth2 Authorization URL"
echo "--------------------------------------"
AUTH_RESPONSE=$(curl -s -X GET "$BASE_URL/api/v1/google/auth-url" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json")

echo "Response:"
echo "$AUTH_RESPONSE" | jq .

AUTH_URL=$(echo "$AUTH_RESPONSE" | jq -r '.auth_url // empty')

if [ -n "$AUTH_URL" ] && [ "$AUTH_URL" != "null" ]; then
    echo ""
    echo "✅ Authorization URL received successfully!"
    echo "🔗 Auth URL: $AUTH_URL"
    echo ""
    echo "📋 Step 2: Manual OAuth2 Flow"
    echo "-----------------------------"
    echo "1. Open this URL in your browser:"
    echo "   $AUTH_URL"
    echo ""
    echo "2. Complete the Google authorization"
    echo "3. You should be redirected to: $BASE_URL/oauth2/callback"
    echo "4. The page should show 'Authorization Successful!'"
    echo ""
    echo "📋 Step 3: Check OAuth2 Status"
    echo "-----------------------------"
    echo "After completing the OAuth2 flow, run this command to check status:"
    echo ""
    echo "curl -X GET \"$BASE_URL/api/v1/google/check-access\" \\"
    echo "  -H \"Authorization: Bearer $AUTH_TOKEN\" \\"
    echo "  -H \"Content-Type: application/json\" | jq ."
    echo ""
    echo "📋 Step 4: Test Google Meet Creation"
    echo "-----------------------------------"
    echo "Once OAuth2 is complete, you can create Google Meet meetings:"
    echo ""
    echo "curl -X POST \"$BASE_URL/api/v1/trainings\" \\"
    echo "  -H \"Authorization: Bearer $AUTH_TOKEN\" \\"
    echo "  -H \"Content-Type: application/json\" \\"
    echo "  -d '{"
    echo "    \"title\": \"Test Google Meet Training\","
    echo "    \"description\": \"Testing Google Meet integration\","
    echo "    \"trainer_id\": 2,"
    echo "    \"start_date\": \"2025-01-15\","
    echo "    \"end_date\": \"2025-01-15\","
    echo "    \"is_online\": true,"
    echo "    \"online_details\": {"
    echo "      \"google_meet_link\": \"https://meet.google.com/test-meeting\""
    echo "    }"
    echo "  }' | jq ."
else
    echo "❌ Failed to get authorization URL"
    echo "Response: $AUTH_RESPONSE"
fi

echo ""
echo "🔗 Alternative: Use the HTML Test Page"
echo "-------------------------------------"
echo "You can also use the interactive test page:"
echo "$BASE_URL/google-meet-test"
