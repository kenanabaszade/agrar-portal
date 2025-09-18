# 🧪 Postman OAuth2 Callback Testing Guide

## Overview
This guide shows you how to test the Google OAuth2 callback flow using Postman and the HTML test page.

## Method 1: Using the HTML Test Page (Recommended)

### Step 1: Open the Test Page
1. **Start your Laravel server**: `php artisan serve`
2. **Open the test page**: `http://localhost:8000/oauth2-test.html`
3. **Follow the step-by-step process** on the page

### Step 2: Complete OAuth2 Flow
1. **Click "Get Authorization URL"** - Gets the Google OAuth2 URL
2. **Click "Authorize Google Calendar"** - Opens Google OAuth2 in new window
3. **Complete authorization** - Sign in and grant permissions
4. **Get redirected back** - You'll be redirected to the test page with the code
5. **Click "Test Callback Endpoint"** - Exchanges code for access token
6. **Click "Check Access Status"** - Verifies access was granted
7. **Click "Create Google Meet Meeting"** - Tests meeting creation

## Method 2: Manual Postman Testing

### Step 1: Get Authorization URL
```http
GET http://localhost:8000/api/v1/google/auth-url
Authorization: Bearer 34|m1tDPv0d9hmb6S9nWTLJgeMLIeXU4p7JQUp3pv5ea41a8e42
Accept: application/json
```

**Expected Response:**
```json
{
  "success": true,
  "auth_url": "https://accounts.google.com/o/oauth2/v2/auth?response_type=code&access_type=offline&client_id=...",
  "message": "Please visit this URL to authorize Google Calendar access"
}
```

### Step 2: Authorize Google Calendar
1. **Copy the `auth_url`** from the response
2. **Open the URL in your browser**
3. **Sign in to Google** and authorize Calendar access
4. **You'll be redirected to**: `http://localhost:8000/api/v1/google/callback?code=YOUR_AUTH_CODE`
5. **Copy the `code` parameter** from the URL

### Step 3: Test Callback Endpoint
```http
GET http://localhost:8000/api/v1/google/callback?code=YOUR_AUTH_CODE
Authorization: Bearer 34|m1tDPv0d9hmb6S9nWTLJgeMLIeXU4p7JQUp3pv5ea41a8e42
Accept: application/json
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Google Calendar access granted successfully",
  "user": {
    "id": 36,
    "google_access_token": "ya29.a0...",
    "google_refresh_token": "1//...",
    "google_token_expires_at": "2025-09-07T..."
  }
}
```

### Step 4: Check Access Status
```http
GET http://localhost:8000/api/v1/google/check-access
Authorization: Bearer 34|m1tDPv0d9hmb6S9nWTLJgeMLIeXU4p7JQUp3pv5ea41a8e42
Accept: application/json
```

**Expected Response:**
```json
{
  "success": true,
  "has_access": true,
  "user": {
    "id": 36,
    "google_access_token": "ya29.a0...",
    "google_refresh_token": "1//...",
    "google_token_expires_at": "2025-09-07T..."
  }
}
```

### Step 5: Create Google Meet Meeting
```http
POST http://localhost:8000/api/v1/meetings
Authorization: Bearer 34|m1tDPv0d9hmb6S9nWTLJgeMLIeXU4p7JQUp3pv5ea41a8e42
Content-Type: application/json

{
  "title": "Test Google Meet Meeting",
  "description": "Testing Google Meet creation via Postman",
  "start_time": "2025-09-15 10:00:00",
  "end_time": "2025-09-15 12:00:00",
  "timezone": "UTC",
  "max_attendees": 50,
  "training_id": 5,
  "is_recurring": false,
  "attendees": [
    {
      "email": "test@example.com",
      "name": "Test User"
    }
  ]
}
```

**Expected Response:**
```json
{
  "success": true,
  "event_id": "abc123def456",
  "meet_link": "https://meet.google.com/abc-defg-hij",
  "meeting_id": "abc-defg-hij",
  "meeting_password": null,
  "event_data": {
    "id": "abc123def456",
    "summary": "Test Google Meet Meeting",
    "conferenceData": {
      "entryPoints": [
        {
          "entryPointType": "video",
          "uri": "https://meet.google.com/abc-defg-hij"
        }
      ]
    }
  }
}
```

## Method 3: Using Postman Collection

### Import the Updated Collection
1. **Import** `Agrar_Portal_API.postman_collection.json` into Postman
2. **Set variables**:
   - `base_url`: `http://localhost:8000`
   - `auth_token`: `34|m1tDPv0d9hmb6S9nWTLJgeMLIeXU4p7JQUp3pv5ea41a8e42`

### Test the OAuth2 Flow
1. **Run "Get Google OAuth2 Authorization URL"**
2. **Copy the `auth_url`** from response
3. **Visit the URL** in browser and authorize
4. **Copy the `code`** from callback URL
5. **Set `google_auth_code` variable** in Postman
6. **Run "Handle Google OAuth2 Callback"**
7. **Run "Check Google Calendar Access"**
8. **Run "Create Google Meet Meeting"**

## Troubleshooting

### Common Issues:

1. **redirect_uri_mismatch**
   - **Solution**: Add `http://localhost:8000/api/v1/google/callback` to Google Cloud Console

2. **Invalid authorization code**
   - **Solution**: Make sure you're using the latest code from the callback URL

3. **Access token expired**
   - **Solution**: Re-authorize by going through the OAuth2 flow again

4. **Meeting creation fails**
   - **Solution**: Ensure Google Calendar API and Google Meet API are enabled

### Testing Tips:

1. **Use the HTML test page** for the easiest testing experience
2. **Check browser console** for any JavaScript errors
3. **Verify Google Cloud Console** settings are correct
4. **Test with different Google accounts** to ensure it works for all users
5. **Check Laravel logs** for any server-side errors

## Success Indicators

✅ **OAuth2 Flow Complete**:
- Authorization URL generated successfully
- User can authorize Google Calendar access
- Callback endpoint returns success
- Access status shows `has_access: true`

✅ **Google Meet Integration Working**:
- Meeting creation returns `success: true`
- Valid Google Meet link generated
- Meeting link opens in Google Meet
- Conference data properly set

## Next Steps

After successful testing:
1. **Integrate with your frontend** application
2. **Add error handling** for production use
3. **Implement token refresh** logic
4. **Add user interface** for meeting management
5. **Test with real users** and different Google accounts

---

**Ready to test? Use the HTML test page at `http://localhost:8000/oauth2-test.html` for the easiest experience!** 🚀
