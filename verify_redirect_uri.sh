#!/bin/bash

echo "🔍 Verifying Redirect URI Configuration"
echo "======================================"

echo ""
echo "📋 Current Laravel Configuration:"
echo "--------------------------------"
echo "Expected Redirect URI: http://localhost:8000/oauth2/callback"

echo ""
echo "📋 OAuth2 Request Details:"
echo "-------------------------"
AUTH_RESPONSE=$(curl -s -X GET "http://localhost:8000/api/v1/google/auth-url" \
  -H "Authorization: Bearer 34|m1tDPv0d9hmb6S9nWTLJgeMLIeXU4p7JQUp3pv5ea41a8e42" \
  -H "Content-Type: application/json")

if echo "$AUTH_RESPONSE" | jq -e '.auth_url' > /dev/null 2>&1; then
    AUTH_URL=$(echo "$AUTH_RESPONSE" | jq -r '.auth_url')
    REDIRECT_URI=$(echo "$AUTH_URL" | grep -o 'redirect_uri=[^&]*' | cut -d'=' -f2 | sed 's/%2F/\//g' | sed 's/%3A/:/g')
    
    echo "✅ OAuth2 URL generated successfully"
    echo "🔗 Redirect URI in request: $REDIRECT_URI"
    
    if [ "$REDIRECT_URI" = "http://localhost:8000/oauth2/callback" ]; then
        echo "✅ Redirect URI matches expected value"
        echo ""
        echo "📋 Google Cloud Console Configuration Required:"
        echo "---------------------------------------------"
        echo "In your Google Cloud Console OAuth2 credentials,"
        echo "make sure you have EXACTLY this redirect URI:"
        echo ""
        echo "   http://localhost:8000/oauth2/callback"
        echo ""
        echo "🔗 Google Cloud Console:"
        echo "https://console.cloud.google.com/apis/credentials"
        echo ""
        echo "📋 Steps to Fix:"
        echo "1. Go to Google Cloud Console"
        echo "2. Find your OAuth2 Client ID: 393586105717-a4jcrfo2sfrie6ujaa8lak2kop29ga54.apps.googleusercontent.com"
        echo "3. Click Edit (pencil icon)"
        echo "4. Add redirect URI: http://localhost:8000/oauth2/callback"
        echo "5. Save and wait 1-2 minutes"
    else
        echo "❌ Redirect URI mismatch!"
        echo "   Expected: http://localhost:8000/oauth2/callback"
        echo "   Found:    $REDIRECT_URI"
    fi
else
    echo "❌ Failed to get OAuth2 URL"
    echo "Response: $AUTH_RESPONSE"
fi
