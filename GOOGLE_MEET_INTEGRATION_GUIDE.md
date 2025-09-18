# Google Meet Integration Guide

## 🎯 Overview
This guide explains how to set up and use the Google Meet integration in your Agrar Portal. The system allows trainers and admins to create Google Meet meetings directly from the platform, and users can register for these meetings.

## 🔧 Setup Instructions

### 1. Google Cloud Console Setup

#### Step 1: Create a Google Cloud Project
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the Google Calendar API

#### Step 2: Create Credentials
1. Go to "Credentials" in the Google Cloud Console
2. Click "Create Credentials" → "OAuth 2.0 Client IDs"
3. Set application type to "Web application"
4. Add authorized redirect URIs:
   - `http://localhost:8000/auth/google/callback` (for development)
   - `https://yourdomain.com/auth/google/callback` (for production)
5. Download the JSON credentials file

#### Step 3: Service Account (Alternative)
For server-to-server authentication:
1. Go to "Credentials" → "Create Credentials" → "Service Account"
2. Create a service account and download the JSON key file
3. Place the file in `storage/app/google-credentials.json`

### 2. Environment Configuration

Add these variables to your `.env` file:

```env
# Google Calendar API Configuration
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
GOOGLE_CALENDAR_ID=primary
GOOGLE_CREDENTIALS_PATH=storage/app/google-credentials.json
```

### 3. Database Setup

The migrations have been run automatically. The system creates two tables:
- `meetings` - Stores meeting information and Google Calendar event IDs
- `meeting_registrations` - Tracks user registrations for meetings

## 🚀 API Endpoints

### Meeting Management (Admin/Trainer Only)

#### Create Meeting
```http
POST /api/v1/meetings
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Agricultural Training Session",
  "description": "Learn about modern farming techniques",
  "start_time": "2025-01-15 10:00:00",
  "end_time": "2025-01-15 12:00:00",
  "timezone": "UTC",
  "max_attendees": 50,
  "training_id": 1,
  "is_recurring": false,
  "attendees": [
    {
      "email": "farmer@example.com",
      "name": "John Farmer"
    }
  ]
}
```

#### List Meetings
```http
GET /api/v1/meetings?status=upcoming
Authorization: Bearer {token}
```

#### Get Meeting Details
```http
GET /api/v1/meetings/{id}
Authorization: Bearer {token}
```

#### Update Meeting
```http
PATCH /api/v1/meetings/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Updated Meeting Title",
  "max_attendees": 100
}
```

#### Delete Meeting
```http
DELETE /api/v1/meetings/{id}
Authorization: Bearer {token}
```

### Meeting Registration (All Users)

#### Register for Meeting
```http
POST /api/v1/meetings/{id}/register
Authorization: Bearer {token}
```

#### Cancel Registration
```http
DELETE /api/v1/meetings/{id}/cancel-registration
Authorization: Bearer {token}
```

#### Get My Registrations
```http
GET /api/v1/my-meetings
Authorization: Bearer {token}
```

#### Get Meeting Attendees (Admin/Trainer)
```http
GET /api/v1/meetings/{id}/attendees
Authorization: Bearer {token}
```

## 📊 Response Examples

### Meeting Creation Response
```json
{
  "message": "Meeting created successfully",
  "meeting": {
    "id": 1,
    "title": "Agricultural Training Session",
    "description": "Learn about modern farming techniques",
    "google_event_id": "abc123def456",
    "google_meet_link": "https://meet.google.com/abc-defg-hij",
    "meeting_id": "abc-defg-hij",
    "start_time": "2025-01-15T10:00:00.000000Z",
    "end_time": "2025-01-15T12:00:00.000000Z",
    "timezone": "UTC",
    "max_attendees": 50,
    "status": "scheduled",
    "created_by": 2,
    "training_id": 1,
    "duration": "2h 0m",
    "attendee_count": 0,
    "creator": {
      "id": 2,
      "name": "Trainer Name",
      "email": "trainer@example.com"
    },
    "training": {
      "id": 1,
      "title": "Advanced Crop Management"
    }
  }
}
```

### Meeting List Response
```json
{
  "data": [
    {
      "id": 1,
      "title": "Agricultural Training Session",
      "start_time": "2025-01-15T10:00:00.000000Z",
      "end_time": "2025-01-15T12:00:00.000000Z",
      "status": "scheduled",
      "google_meet_link": "https://meet.google.com/abc-defg-hij",
      "max_attendees": 50,
      "attendee_count": 5,
      "is_live": false,
      "is_upcoming": true,
      "has_available_spots": true
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/v1/meetings?page=1",
    "last": "http://localhost:8000/api/v1/meetings?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "per_page": 15,
    "to": 1,
    "total": 1
  }
}
```

## 🔄 Meeting Status Flow

1. **Scheduled** - Meeting is created and waiting to start
2. **Live** - Meeting is currently happening
3. **Ended** - Meeting has finished
4. **Cancelled** - Meeting was cancelled

## 📱 Frontend Integration

### Dashboard Features
- **Admin/Trainer Dashboard**:
  - Create new meetings
  - View all meetings (upcoming, live, past)
  - Manage meeting details
  - View attendee lists
  - Cancel meetings

- **User Dashboard**:
  - Browse available meetings
  - Register for meetings
  - View registered meetings
  - Join meetings via Google Meet links
  - Cancel registrations

### Meeting Cards
```javascript
// Example meeting card component
const MeetingCard = ({ meeting }) => (
  <div className="meeting-card">
    <h3>{meeting.title}</h3>
    <p>{meeting.description}</p>
    <div className="meeting-details">
      <span>📅 {formatDate(meeting.start_time)}</span>
      <span>⏱️ {meeting.duration}</span>
      <span>👥 {meeting.attendee_count}/{meeting.max_attendees}</span>
    </div>
    {meeting.is_upcoming && (
      <button onClick={() => registerForMeeting(meeting.id)}>
        Register
      </button>
    )}
    {meeting.is_live && (
      <a href={meeting.google_meet_link} target="_blank">
        Join Meeting
      </a>
    )}
  </div>
);
```

## 🔐 Security Features

- **Role-based Access**: Only admins and trainers can create/manage meetings
- **User Registration**: All authenticated users can register for meetings
- **Capacity Limits**: Meetings have maximum attendee limits
- **Time Validation**: Meetings can only be created for future dates
- **Google Calendar Sync**: All changes are synchronized with Google Calendar

## 🚨 Error Handling

### Common Error Responses

#### Meeting Full
```json
{
  "error": "Meeting is full, no available spots"
}
```

#### Already Registered
```json
{
  "error": "You are already registered for this meeting"
}
```

#### Google API Error
```json
{
  "error": "Failed to create Google Meet meeting",
  "details": "Invalid credentials"
}
```

## 📈 Analytics & Reporting

### Meeting Statistics
- Total meetings created
- Average attendance rate
- Most popular meeting times
- User engagement metrics

### Export Features
- Meeting attendee lists (CSV/Excel)
- Meeting reports with attendance data
- Training session analytics

## 🔧 Troubleshooting

### Common Issues

1. **Google API Authentication Failed**
   - Check credentials in `.env` file
   - Verify Google Cloud Console setup
   - Ensure Calendar API is enabled

2. **Meeting Creation Fails**
   - Check internet connection
   - Verify Google Calendar permissions
   - Check meeting time is in the future

3. **Users Can't Register**
   - Verify user authentication
   - Check meeting capacity
   - Ensure meeting is not cancelled

### Debug Mode
Enable debug logging in `.env`:
```env
LOG_LEVEL=debug
```

## 🎉 Ready to Use!

Your Google Meet integration is now ready! Trainers and admins can create meetings, and users can register and join them seamlessly through your Agrar Portal platform.

### Next Steps
1. Set up Google Cloud Console credentials
2. Configure environment variables
3. Test meeting creation and registration
4. Integrate with your frontend dashboard
5. Train your team on the new features

Happy meeting! 🎬✨
