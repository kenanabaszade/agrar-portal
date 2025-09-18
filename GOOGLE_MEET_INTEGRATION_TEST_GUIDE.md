# 🧪 Google Meet Integration Test Guide

## 📋 Overview
This guide will help you test the Google Meet integration using the HTML pages we created. The integration includes OAuth2 authentication, Google Meet link generation, and training creation with Google Meet links.

## 🚀 Quick Start

### 1. Access the Test Page
Open your browser and navigate to:
```
http://localhost:8000/google-meet-test
```

This page provides an interactive interface to test all Google Meet integration features.

### 2. Test the OAuth2 Flow
1. Click "Get Authorization URL" to get the Google OAuth2 URL
2. Click "Start OAuth2 Flow" to open Google's authorization page
3. Complete the OAuth2 flow in the new window
4. You'll be redirected to our success/error pages
5. Click "Check OAuth2 Status" to verify the authorization

## 🔧 Manual Testing Steps

### Step 1: Test OAuth2 Authorization URL
```bash
curl -X GET "http://localhost:8000/api/v1/google/auth-url" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

**Expected Response:**
```json
{
  "success": true,
  "auth_url": "https://accounts.google.com/o/oauth2/v2/auth?...",
  "message": "Please visit this URL to authorize Google Calendar access"
}
```

### Step 2: Complete OAuth2 Flow
1. Open the `auth_url` in your browser
2. Sign in with your Google account
3. Grant permissions for Google Calendar access
4. You'll be redirected to: `http://localhost:8000/oauth2/callback`

### Step 3: Check OAuth2 Status
```bash
curl -X GET "http://localhost:8000/api/v1/google/oauth2-code" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

**Expected Response (after OAuth2 completion):**
```json
{
  "success": true,
  "code": "4/0AX4XfWh...",
  "message": "OAuth2 authorization successful"
}
```

### Step 4: Test Google Meet Link Generation
```bash
curl -X POST "http://localhost:8000/api/v1/google/meet-link" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test Google Meet Session",
    "description": "Testing Google Meet integration",
    "start_time": "2025-01-15T10:00:00Z",
    "end_time": "2025-01-15T11:00:00Z"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "meet_link": "https://meet.google.com/abc-defg-hij",
  "event_id": "event_123456789",
  "message": "Google Meet link created successfully"
}
```

### Step 5: Test Training with Google Meet
```bash
curl -X POST "http://localhost:8000/api/v1/trainings" \
  -H "Authorization: Bearer YOUR_TOKEN" \
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
  }'
```

## 🎯 HTML Pages Testing

### Success Page
Test the OAuth2 success page:
```
http://localhost:8000/oauth2/callback?code=test_code
```

### Error Page
Test the OAuth2 error page:
```
http://localhost:8000/oauth2/callback?error=access_denied
```

## 🔍 Troubleshooting

### Common Issues

1. **OAuth2 Authorization Failed**
   - Check if Google OAuth2 credentials are properly configured
   - Verify the redirect URI matches: `http://localhost:8000/oauth2/callback`
   - Ensure the Google Calendar API is enabled

2. **Google Meet Link Generation Failed**
   - Complete the OAuth2 flow first
   - Check if the user has Google Calendar access
   - Verify the access token is valid

3. **Training Creation Failed**
   - Ensure the trainer_id exists in the users table
   - Check if all required fields are provided
   - Verify the authentication token is valid

### Debug Commands

Check Google access status:
```bash
curl -X GET "http://localhost:8000/api/v1/google/check-access" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

Revoke Google access:
```bash
curl -X POST "http://localhost:8000/api/v1/google/revoke-access" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 📊 Test Results

### ✅ Successful Test Results
- [ ] OAuth2 authorization URL generated
- [ ] OAuth2 flow completed successfully
- [ ] OAuth2 code received and stored
- [ ] Google Meet link generated
- [ ] Training created with Google Meet link
- [ ] HTML success/error pages working

### 🔧 Configuration Checklist
- [ ] Google OAuth2 credentials configured
- [ ] Google Calendar API enabled
- [ ] Redirect URI configured: `http://localhost:8000/oauth2/callback`
- [ ] Laravel application running on `http://localhost:8000`
- [ ] Database migrations run successfully

## 🎉 Next Steps

After successful testing:
1. Integrate Google Meet links into your training creation flow
2. Add Google Meet link generation to the training creation API
3. Implement automatic Google Calendar event creation
4. Add Google Meet link management features

## 📝 Notes

- The OAuth2 flow requires user interaction in a browser
- Google Meet links are generated through Google Calendar API
- The integration supports both online and offline training types
- All Google Meet links are stored in the `online_details` JSON field

## 🆘 Support

If you encounter issues:
1. Check the Laravel logs: `storage/logs/laravel.log`
2. Verify Google API quotas and limits
3. Ensure all environment variables are set correctly
4. Test with a fresh OAuth2 authorization if needed
