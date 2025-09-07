#!/bin/bash

# Test OAuth2 Configuration
echo "🔧 Testing OAuth2 Configuration"
echo "================================"

BASE_URL="http://localhost:8000"
AUTH_TOKEN="34|m1tDPv0d9hmb6S9nWTLJgeMLIeXU4p7JQUp3pv5ea41a8e42"

echo ""
echo "📋 Current Configuration:"
echo "------------------------"
echo "Base URL: $BASE_URL"
echo "Expected Redirect URI: $BASE_URL/oauth2/callback"

echo ""
echo "🔍 Step 1: Check .env configuration..."
if grep -q "GOOGLE_REDIRECT_URI" .env; then
    REDIRECT_URI=$(grep "GOOGLE_REDIRECT_URI" .env | cut -d'=' -f2)
    echo "✅ Found GOOGLE_REDIRECT_URI in .env: $REDIRECT_URI"
    
    if [ "$REDIRECT_URI" = "$BASE_URL/oauth2/callback" ]; then
        echo "✅ Redirect URI matches expected value"
    else
        echo "❌ Redirect URI mismatch!"
        echo "   Expected: $BASE_URL/oauth2/callback"
        echo "   Found:    $REDIRECT_URI"
        echo ""
        echo "🔧 To fix this, update your .env file:"
        echo "   GOOGLE_REDIRECT_URI=$BASE_URL/oauth2/callback"
    fi
else
    echo "❌ GOOGLE_REDIRECT_URI not found in .env file"
    echo ""
    echo "🔧 Add this to your .env file:"
    echo "   GOOGLE_REDIRECT_URI=$BASE_URL/oauth2/callback"
fi

echo ""
echo "🔍 Step 2: Test OAuth2 authorization URL..."
AUTH_RESPONSE=$(curl -s -X GET "$BASE_URL/api/v1/google/auth-url" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json")

if echo "$AUTH_RESPONSE" | jq -e '.auth_url' > /dev/null 2>&1; then
    AUTH_URL=$(echo "$AUTH_RESPONSE" | jq -r '.auth_url')
    echo "✅ Successfully got authorization URL"
    echo "🔗 Auth URL: $AUTH_URL"
    
    # Extract redirect_uri from the auth URL
    REDIRECT_FROM_URL=$(echo "$AUTH_URL" | grep -o 'redirect_uri=[^&]*' | cut -d'=' -f2 | sed 's/%2F/\//g' | sed 's/%3A/:/g')
    if [ -n "$REDIRECT_FROM_URL" ]; then
        echo "🔍 Redirect URI in auth URL: $REDIRECT_FROM_URL"
        
        if [ "$REDIRECT_FROM_URL" = "$BASE_URL/oauth2/callback" ]; then
            echo "✅ Redirect URI in auth URL matches expected value"
        else
            echo "❌ Redirect URI in auth URL doesn't match!"
            echo "   Expected: $BASE_URL/oauth2/callback"
            echo "   Found:    $REDIRECT_FROM_URL"
        fi
    fi
else
    echo "❌ Failed to get authorization URL"
    echo "Response: $AUTH_RESPONSE"
fi

echo ""
echo "🔍 Step 3: Test callback route..."
CALLBACK_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/oauth2/callback")
if [ "$CALLBACK_RESPONSE" = "200" ] || [ "$CALLBACK_RESPONSE" = "400" ]; then
    echo "✅ Callback route is accessible (HTTP $CALLBACK_RESPONSE)"
else
    echo "❌ Callback route not accessible (HTTP $CALLBACK_RESPONSE)"
fi

echo ""
echo "📋 Summary:"
echo "----------"
echo "1. Check your Google Cloud Console OAuth2 credentials"
echo "2. Make sure the redirect URI is exactly: $BASE_URL/oauth2/callback"
echo "3. Wait 1-2 minutes after making changes in Google Console"
echo "4. Test the OAuth2 flow using: http://localhost:8000/google-meet-test"

echo ""
echo "🔗 Useful Links:"
echo "---------------"
echo "• Google Cloud Console: https://console.cloud.google.com/apis/credentials"
echo "• Test Page: $BASE_URL/google-meet-test"
echo "• OAuth2 Callback: $BASE_URL/oauth2/callback"
