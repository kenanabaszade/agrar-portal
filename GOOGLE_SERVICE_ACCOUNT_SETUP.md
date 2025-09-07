# 🔧 Google Service Account Setup Guide

## 🎯 Overview
This guide shows you how to set up a Google Service Account for your Agrar Portal, so that all meetings are created from a single account without requiring individual user authentication.

## 📋 Step-by-Step Setup

### Step 1: Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Name it "Agrar Portal Service Account"

### Step 2: Enable Google Calendar API

1. In Google Cloud Console, go to "APIs & Services" → "Library"
2. Search for "Google Calendar API"
3. Click on it and press "Enable"

### Step 3: Create Service Account

1. Go to "APIs & Services" → "Credentials"
2. Click "Create Credentials" → "Service Account"
3. Fill in the details:
   - **Service account name**: `agrar-portal-meetings`
   - **Service account ID**: `agrar-portal-meetings` (auto-generated)
   - **Description**: `Service account for creating Google Meet meetings in Agrar Portal`
4. Click "Create and Continue"
5. Skip the "Grant access" step (click "Continue")
6. Click "Done"

### Step 4: Generate Service Account Key

1. In the Credentials page, find your service account
2. Click on the service account email
3. Go to "Keys" tab
4. Click "Add Key" → "Create new key"
5. Choose "JSON" format
6. Click "Create"
7. The JSON file will download automatically

### Step 5: Configure Your Application

1. **Rename the downloaded file** to `google-credentials.json`
2. **Move it to your Laravel project**: `storage/app/google-credentials.json`
3. **Update your `.env` file**:

```env
# Google Service Account Configuration
GOOGLE_CREDENTIALS_PATH=storage/app/google-credentials.json
GOOGLE_SERVICE_ACCOUNT_EMAIL=agrar-portal-meetings@your-project-id.iam.gserviceaccount.com
GOOGLE_CALENDAR_ID=primary

# Optional: Keep these for fallback (not needed for service account)
GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_client_secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

### Step 6: Set Up Calendar Sharing (Important!)

Since the service account creates meetings, you need to share a calendar with it:

#### Option A: Use Service Account's Own Calendar
1. The service account has its own calendar
2. Meetings will be created there
3. You can share this calendar with users

#### Option B: Share Your Calendar with Service Account
1. Go to [Google Calendar](https://calendar.google.com/)
2. Create a new calendar called "Agrar Portal Meetings"
3. Go to calendar settings → "Share with specific people"
4. Add the service account email: `agrar-portal-meetings@your-project-id.iam.gserviceaccount.com`
5. Give it "Make changes to events" permission
6. Update your `.env`:

```env
GOOGLE_CALENDAR_ID=your-calendar-id@group.calendar.google.com
```

### Step 7: Test the Setup

Run this command to test:

```bash
php artisan tinker
```

Then in tinker:

```php
use App\Services\GoogleCalendarService;

$service = new GoogleCalendarService();
$result = $service->createMeeting([
    'title' => 'Test Meeting',
    'description' => 'Testing service account',
    'start_time' => '2025-01-15 10:00:00',
    'end_time' => '2025-01-15 11:00:00',
    'timezone' => 'UTC'
]);

dd($result);
```

## 🎉 Benefits of Service Account Approach

### ✅ **Advantages:**
- **No user authentication required** - Users don't need to sign in to Google
- **Centralized management** - All meetings created from one account
- **Simplified user experience** - Just create meetings directly
- **Better security** - Service account credentials stored securely
- **No token expiration** - Service accounts don't expire like user tokens

### 🔒 **Security:**
- Service account credentials are stored in `storage/app/` (not in git)
- Only admins/trainers can create meetings
- Users only register for meetings, don't create them

## 📱 **Usage Examples**

### Create a Meeting (Admin/Trainer)
```http
POST /api/v1/meetings
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json

{
  "title": "Agricultural Training Session",
  "description": "Learn modern farming techniques",
  "start_time": "2025-01-15 10:00:00",
  "end_time": "2025-01-15 12:00:00",
  "max_attendees": 50
}
```

### Register for Meeting (Any User)
```http
POST /api/v1/meetings/1/register
Authorization: Bearer USER_TOKEN
```

## 🚨 **Troubleshooting**

### Common Issues:

1. **"Service account not found"**
   - Check the service account email in `.env`
   - Verify the JSON file is in the correct location

2. **"Calendar not found"**
   - Check the `GOOGLE_CALENDAR_ID` in `.env`
   - Ensure the calendar is shared with the service account

3. **"Permission denied"**
   - Verify the service account has access to the calendar
   - Check that the Calendar API is enabled

### Debug Commands:

```bash
# Check if credentials file exists
ls -la storage/app/google-credentials.json

# Check environment variables
php artisan tinker --execute="echo config('services.google.service_account_email');"

# Test Google Calendar connection
php artisan tinker --execute="
use App\Services\GoogleCalendarService;
\$service = new GoogleCalendarService();
echo 'Service initialized successfully';
"
```

## 🎯 **Ready to Use!**

Once set up, your Agrar Portal will:
- ✅ Create Google Meet meetings automatically
- ✅ No user Google authentication required
- ✅ All meetings managed from one service account
- ✅ Users can register and join meetings seamlessly

**Your Google Meet integration is now ready for production use!** 🎬✨
