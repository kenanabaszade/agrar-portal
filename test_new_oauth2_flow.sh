#!/bin/bash

# Test the new OAuth2 flow with web callback
echo "🚀 Testing New OAuth2 Flow with Web Callback"
echo "=============================================="

BASE_URL="http://localhost:8000"
AUTH_TOKEN="34|m1tDPv0d9hmb6S9nWTLJgeMLIeXU4p7JQUp3pv5ea41a8e42"

echo ""
echo "1. Check current Google access status..."
curl -s -X GET "$BASE_URL/api/v1/google/check-access" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json" | jq .

echo ""
echo "2. Get Google OAuth2 authorization URL..."
AUTH_URL=$(curl -s -X GET "$BASE_URL/api/v1/google/auth-url" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json" | jq -r '.auth_url')

echo "Authorization URL: $AUTH_URL"

echo ""
echo "3. Simulate OAuth2 callback with test code..."
# Simulate the callback that would happen after user authorizes
curl -s "http://localhost:8000/oauth2/callback?code=test_authorization_code_123&state=test_state" | head -5

echo ""
echo "4. Retrieve OAuth2 code from session..."
curl -s -X GET "$BASE_URL/api/v1/google/oauth2-code" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json" | jq .

echo ""
echo "5. Test the OAuth2 callback error handling..."
curl -s "http://localhost:8000/oauth2/callback?error=access_denied" | head -5

echo ""
echo "✅ New OAuth2 flow test completed!"
echo ""
echo "📋 Next Steps:"
echo "1. Update Google Cloud Console redirect URI to: http://localhost:8000/oauth2/callback"
echo "2. Add your email as a test user in Google Cloud Console"
echo "3. Visit the authorization URL in your browser"
echo "4. Complete the OAuth2 flow"
echo "5. Use the retrieved code to complete the authentication"
