#!/bin/bash

# Test Google Meet Integration with HTML Pages
echo "🧪 Testing Google Meet Integration with HTML Pages"
echo "=================================================="

BASE_URL="http://localhost:8000"
AUTH_TOKEN="34|m1tDPv0d9hmb6S9nWTLJgeMLIeXU4p7JQUp3pv5ea41a8e42"

echo ""
echo "📋 Step 1: Test Google OAuth2 Authorization URL"
echo "-----------------------------------------------"
echo "Getting Google OAuth2 authorization URL..."
AUTH_RESPONSE=$(curl -s -X GET "$BASE_URL/api/v1/google/auth-url" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json")

echo "Response:"
echo "$AUTH_RESPONSE" | jq .

# Extract the auth URL
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
    echo "2. Complete the Google OAuth2 flow"
    echo "3. You'll be redirected to: $BASE_URL/oauth2/callback"
    echo "4. Check the success/error pages we created"
    echo ""
    echo "📋 Step 3: Check OAuth2 Code"
    echo "---------------------------"
    echo "After completing OAuth2, check if we received the code..."
    sleep 2
    
    CODE_RESPONSE=$(curl -s -X GET "$BASE_URL/api/v1/google/oauth2-code" \
      -H "Authorization: Bearer $AUTH_TOKEN" \
      -H "Content-Type: application/json")
    
    echo "OAuth2 Code Response:"
    echo "$CODE_RESPONSE" | jq .
    
    echo ""
    echo "📋 Step 4: Test Google Meet Link Generation"
    echo "------------------------------------------"
    echo "Testing Google Meet link generation..."
    
    MEET_RESPONSE=$(curl -s -X POST "$BASE_URL/api/v1/google/meet-link" \
      -H "Authorization: Bearer $AUTH_TOKEN" \
      -H "Content-Type: application/json" \
      -d '{
        "title": "Test Google Meet Session",
        "description": "Testing Google Meet integration",
        "start_time": "2025-01-15T10:00:00Z",
        "end_time": "2025-01-15T11:00:00Z"
      }')
    
    echo "Google Meet Response:"
    echo "$MEET_RESPONSE" | jq .
    
else
    echo "❌ Failed to get authorization URL"
    echo "Response: $AUTH_RESPONSE"
fi

echo ""
echo "📋 Step 5: Test HTML Pages Directly"
echo "----------------------------------"
echo "You can also test the HTML pages directly:"
echo ""
echo "1. Success Page: $BASE_URL/oauth2/callback?code=test_code"
echo "2. Error Page: $BASE_URL/oauth2/callback?error=access_denied"
echo ""
echo "📋 Step 6: Test Training with Google Meet"
echo "----------------------------------------"
echo "Creating a training with Google Meet integration..."

TRAINING_RESPONSE=$(curl -s -X POST "$BASE_URL/api/v1/trainings" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Google Meet Training Test",
    "description": "Testing Google Meet integration with training",
    "category": "Google Meet Test",
    "trainer_id": 36,
    "start_date": "2025-01-15",
    "end_date": "2025-01-15",
    "is_online": true,
    "type": "online",
    "online_details": {
      "participant_size": "50",
      "google_meet_link": "https://meet.google.com/test-link"
    }
  }')

echo "Training Creation Response:"
echo "$TRAINING_RESPONSE" | jq .

echo ""
echo "✅ Google Meet Integration Test Completed!"
echo ""
echo "📝 Next Steps:"
echo "1. Complete the OAuth2 flow in your browser"
echo "2. Check the HTML success/error pages"
echo "3. Test Google Meet link generation"
echo "4. Verify training creation with Google Meet links"
