# 🔐 Google Authentication Flow for Meeting Creation

## 📋 Overview
When creating meetings via `/api/v1/meetings` POST, the system checks if the user has Google Calendar access before allowing meeting creation.

## 🔄 Complete Flow

### Step 1: User Authentication
```bash
# User must be authenticated with Sanctum token
curl -X POST "http://localhost:8000/api/v1/meetings" \
  -H "Authorization: Bearer YOUR_SANCTUM_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test Meeting",
    "start_time": "2025-01-15T10:00:00Z",
    "end_time": "2025-01-15T11:00:00Z"
  }'
```

### Step 2: Google Authentication Check
The system checks if the user has `google_access_token` in the database:

```php
$user = $request->user();
if (!$user->google_access_token) {
    return response()->json([
        'error' => 'Google Calendar access required',
        'message' => 'Please authorize Google Calendar access first',
        'auth_url' => url('/api/v1/google/auth-url')
    ], 401);
}
```

### Step 3: Possible Responses

#### ✅ **If User Has Google Access:**
```json
{
  "message": "Meeting created successfully",
  "meeting": {
    "id": 1,
    "title": "Test Meeting",
    "google_meet_link": "https://meet.google.com/abc-defg-hij",
    "start_time": "2025-01-15T10:00:00Z",
    "end_time": "2025-01-15T11:00:00Z"
  }
}
```

#### ❌ **If User Doesn't Have Google Access:**
```json
{
  "error": "Google Calendar access required",
  "message": "Please authorize Google Calendar access first",
  "auth_url": "http://localhost:8000/api/v1/google/auth-url"
}
```

## 🧪 Testing the Flow

### Test 1: Check Current Google Auth Status
```bash
curl -X GET "http://localhost:8000/api/v1/google/check-access" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

### Test 2: Try Creating Meeting Without Google Auth
```bash
curl -X POST "http://localhost:8000/api/v1/meetings" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test Meeting",
    "start_time": "2025-01-15T10:00:00Z",
    "end_time": "2025-01-15T11:00:00Z"
  }'
```

### Test 3: Complete Google OAuth2 Flow
1. Get auth URL: `GET /api/v1/google/auth-url`
2. Complete OAuth2: Visit the auth URL
3. Check status: `GET /api/v1/google/check-access`
4. Create meeting: `POST /api/v1/meetings`

## 🔧 Database Schema

The `users` table has these Google-related fields:
- `google_access_token` - JSON string containing access token
- `google_refresh_token` - Refresh token for long-term access
- `google_token_expires_at` - Token expiration timestamp

## 📝 Meeting Creation Process

1. **Validate Request** - Check required fields
2. **Check Google Auth** - Verify user has Google access token
3. **Set Access Token** - Configure GoogleCalendarService with user's token
4. **Create Google Meeting** - Call Google Calendar API
5. **Store in Database** - Save meeting details locally
6. **Return Response** - Send meeting data to client

## 🚨 Error Handling

### Common Error Scenarios:
- **401 Unauthorized**: User not authenticated
- **401 Google Access Required**: User authenticated but no Google token
- **400 Bad Request**: Invalid meeting data
- **400 Google API Error**: Failed to create Google Meet meeting
- **500 Server Error**: Database or system error

## 🔄 Token Management

### Token Storage:
- Tokens are stored in `users.google_access_token` as JSON
- Includes access_token, refresh_token, expires_in, etc.

### Token Usage:
- Set in GoogleCalendarService: `$service->setAccessToken($user->google_access_token)`
- Used for all Google Calendar API calls

### Token Refresh:
- Google tokens expire after 1 hour
- Refresh tokens are used to get new access tokens
- Handled automatically by Google API client

## 🎯 Best Practices

1. **Always Check Auth First**: Verify Google access before API calls
2. **Handle Token Expiry**: Implement token refresh logic
3. **Error Messages**: Provide clear guidance for auth issues
4. **Fallback Options**: Consider non-Google meeting creation
5. **Logging**: Log authentication failures for debugging
