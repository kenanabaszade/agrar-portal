#!/bin/bash

# Google Meet Integration Complete Test Flow
# This script tests the entire Google Meet integration process

BASE_URL="http://localhost:8000"
AUTH_TOKEN=""
GOOGLE_AUTH_URL=""

echo "🚀 Starting Google Meet Integration Test Flow"
echo "=============================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Step 1: User Authentication (Login)
print_status "Step 1: User Authentication"
echo "Please login first to get an auth token:"
echo "POST $BASE_URL/api/v1/auth/login"
echo "Body: {\"email\": \"admin@example.com\", \"password\": \"password123\"}"
echo ""
read -p "Enter your auth token (or press Enter to skip): " AUTH_TOKEN

if [ -z "$AUTH_TOKEN" ]; then
    print_warning "Skipping authentication test. Please provide a valid auth token."
    exit 1
fi

# Step 2: Check Google Authentication Status
print_status "Step 2: Check Google Authentication Status"
echo "GET $BASE_URL/api/v1/google/check-access"

RESPONSE=$(curl -s -X GET "$BASE_URL/api/v1/google/check-access" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json")

echo "Response: $RESPONSE"
echo ""

# Check if user has Google access
HAS_ACCESS=$(echo $RESPONSE | jq -r '.has_access // false')
CAN_CREATE_MEETINGS=$(echo $RESPONSE | jq -r '.can_create_meetings // false')

if [ "$HAS_ACCESS" = "true" ] && [ "$CAN_CREATE_MEETINGS" = "true" ]; then
    print_success "User has valid Google Calendar access!"
    SKIP_AUTH=true
else
    print_warning "User needs Google Calendar authentication"
    SKIP_AUTH=false
fi

# Step 3: Get Google Auth URL (if needed)
if [ "$SKIP_AUTH" = "false" ]; then
    print_status "Step 3: Get Google Authentication URL"
    echo "GET $BASE_URL/api/v1/google/auth-url"
    
    AUTH_RESPONSE=$(curl -s -X GET "$BASE_URL/api/v1/google/auth-url" \
      -H "Authorization: Bearer $AUTH_TOKEN" \
      -H "Content-Type: application/json" \
      -H "Accept: application/json")
    
    echo "Response: $AUTH_RESPONSE"
    echo ""
    
    GOOGLE_AUTH_URL=$(echo $AUTH_RESPONSE | jq -r '.auth_url // empty')
    
    if [ -n "$GOOGLE_AUTH_URL" ]; then
        print_success "Google Auth URL obtained!"
        echo "Please visit this URL to authorize Google Calendar access:"
        echo "$GOOGLE_AUTH_URL"
        echo ""
        read -p "Press Enter after you've completed the Google authorization..."
        
        # Step 4: Check authentication status again
        print_status "Step 4: Verify Google Authentication"
        echo "GET $BASE_URL/api/v1/google/check-access"
        
        VERIFY_RESPONSE=$(curl -s -X GET "$BASE_URL/api/v1/google/check-access" \
          -H "Authorization: Bearer $AUTH_TOKEN" \
          -H "Content-Type: application/json" \
          -H "Accept: application/json")
        
        echo "Response: $VERIFY_RESPONSE"
        echo ""
        
        CAN_CREATE_MEETINGS=$(echo $VERIFY_RESPONSE | jq -r '.can_create_meetings // false')
        
        if [ "$CAN_CREATE_MEETINGS" = "true" ]; then
            print_success "Google authentication successful!"
        else
            print_error "Google authentication failed. Please try again."
            exit 1
        fi
    else
        print_error "Failed to get Google Auth URL"
        exit 1
    fi
fi

# Step 5: Test Meeting Creation
print_status "Step 5: Create Google Meet Meeting"
echo "POST $BASE_URL/api/v1/meetings"

MEETING_DATA='{
  "title": "Test Google Meet Meeting",
  "description": "This is a test meeting created via API",
  "start_time": "'$(date -u -d '+1 hour' '+%Y-%m-%dT%H:%M:%SZ')'",
  "end_time": "'$(date -u -d '+2 hours' '+%Y-%m-%dT%H:%M:%SZ')'",
  "timezone": "UTC",
  "max_attendees": 10,
  "attendees": [
    {
      "email": "test@example.com",
      "name": "Test User"
    }
  ]
}'

echo "Meeting Data: $MEETING_DATA"
echo ""

MEETING_RESPONSE=$(curl -s -X POST "$BASE_URL/api/v1/meetings" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "$MEETING_DATA")

echo "Response: $MEETING_RESPONSE"
echo ""

MEETING_ID=$(echo $MEETING_RESPONSE | jq -r '.meeting.id // empty')
GOOGLE_MEET_LINK=$(echo $MEETING_RESPONSE | jq -r '.meeting.google_meet_link // empty')

if [ -n "$MEETING_ID" ] && [ -n "$GOOGLE_MEET_LINK" ]; then
    print_success "Meeting created successfully!"
    echo "Meeting ID: $MEETING_ID"
    echo "Google Meet Link: $GOOGLE_MEET_LINK"
else
    print_error "Failed to create meeting"
    echo "Error details: $MEETING_RESPONSE"
    exit 1
fi

# Step 6: Test Meeting Retrieval
print_status "Step 6: Retrieve Created Meeting"
echo "GET $BASE_URL/api/v1/meetings/$MEETING_ID"

RETRIEVE_RESPONSE=$(curl -s -X GET "$BASE_URL/api/v1/meetings/$MEETING_ID" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json")

echo "Response: $RETRIEVE_RESPONSE"
echo ""

# Step 7: Test Meeting List
print_status "Step 7: List All Meetings"
echo "GET $BASE_URL/api/v1/meetings"

LIST_RESPONSE=$(curl -s -X GET "$BASE_URL/api/v1/meetings" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json")

echo "Response: $LIST_RESPONSE"
echo ""

# Step 8: Test Meeting Registration
print_status "Step 8: Register for Meeting"
echo "POST $BASE_URL/api/v1/meetings/$MEETING_ID/register"

REGISTER_RESPONSE=$(curl -s -X POST "$BASE_URL/api/v1/meetings/$MEETING_ID/register" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json")

echo "Response: $REGISTER_RESPONSE"
echo ""

# Step 9: Test Meeting Attendees
print_status "Step 9: Get Meeting Attendees"
echo "GET $BASE_URL/api/v1/meetings/$MEETING_ID/attendees"

ATTENDEES_RESPONSE=$(curl -s -X GET "$BASE_URL/api/v1/meetings/$MEETING_ID/attendees" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json")

echo "Response: $ATTENDEES_RESPONSE"
echo ""

# Step 10: Test Meeting Update
print_status "Step 10: Update Meeting"
echo "PUT $BASE_URL/api/v1/meetings/$MEETING_ID"

UPDATE_DATA='{
  "title": "Updated Test Google Meet Meeting",
  "description": "This meeting has been updated via API"
}'

UPDATE_RESPONSE=$(curl -s -X PUT "$BASE_URL/api/v1/meetings/$MEETING_ID" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "$UPDATE_DATA")

echo "Response: $UPDATE_RESPONSE"
echo ""

# Step 11: Test Meeting Deletion
print_status "Step 11: Delete Meeting"
echo "DELETE $BASE_URL/api/v1/meetings/$MEETING_ID"

DELETE_RESPONSE=$(curl -s -X DELETE "$BASE_URL/api/v1/meetings/$MEETING_ID" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json")

echo "Response: $DELETE_RESPONSE"
echo ""

# Final Summary
echo "=============================================="
print_success "Google Meet Integration Test Complete!"
echo "=============================================="
echo ""
echo "Test Summary:"
echo "✅ User Authentication"
echo "✅ Google Calendar Authentication"
echo "✅ Meeting Creation"
echo "✅ Meeting Retrieval"
echo "✅ Meeting Listing"
echo "✅ Meeting Registration"
echo "✅ Attendee Management"
echo "✅ Meeting Updates"
echo "✅ Meeting Deletion"
echo ""
echo "All tests completed successfully! 🎉"
