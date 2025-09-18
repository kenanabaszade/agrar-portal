#!/bin/bash

# Test script for new training fields
BASE_URL="http://localhost:8000/api/v1"
AUTH_TOKEN="your_auth_token_here"

echo "=== Testing New Training Fields ==="
echo ""

# Test 1: Create Online Training
echo "1. Creating Online Training..."
curl -X POST "$BASE_URL/trainings" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Advanced Crop Management Online",
    "description": "Learn advanced farming techniques online",
    "category": "Crop Management",
    "trainer_id": 2,
    "start_date": "2025-01-01",
    "end_date": "2025-12-31",
    "is_online": true,
    "type": "online",
    "online_details": {
      "participant_size": "50",
      "google_meet_link": "https://meet.google.com/abc-defg-hij"
    },
    "offline_details": {
      "participant_size": "",
      "address": "",
      "coordinates": ""
    }
  }'

echo ""
echo ""

# Test 2: Create Offline Training
echo "2. Creating Offline Training..."
curl -X POST "$BASE_URL/trainings" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Field Training - Soil Analysis",
    "description": "Hands-on training in soil analysis techniques",
    "category": "Field Work",
    "trainer_id": 2,
    "start_date": "2025-02-01",
    "end_date": "2025-02-05",
    "is_online": false,
    "type": "offline",
    "online_details": {
      "participant_size": "",
      "google_meet_link": ""
    },
    "offline_details": {
      "participant_size": "25",
      "address": "Agricultural Research Center, Farm Road 123, Green Valley",
      "coordinates": "40.7128,-74.0060"
    }
  }'

echo ""
echo ""

# Test 3: Update Training to change from online to offline
echo "3. Updating Training from Online to Offline..."
curl -X PATCH "$BASE_URL/trainings/1" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "offline",
    "is_online": false,
    "offline_details": {
      "participant_size": "30",
      "address": "New Training Center, Main Street 456",
      "coordinates": "41.8781,-87.6298"
    },
    "online_details": {
      "participant_size": "",
      "google_meet_link": ""
    }
  }'

echo ""
echo ""

# Test 4: Get Training with new fields
echo "4. Getting Training with new fields..."
curl -X GET "$BASE_URL/trainings/1" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Accept: application/json"

echo ""
echo ""

echo "=== Test Complete ==="
echo ""
echo "New Training Fields:"
echo "- type: 'online' or 'offline'"
echo "- online_details: { participant_size, google_meet_link }"
echo "- offline_details: { participant_size, address, coordinates }"
echo "- media_files: unified media storage with type categorization"
echo ""
echo "Usage Notes:"
echo "1. Replace 'your_auth_token_here' with a valid admin/trainer token"
echo "2. The 'type' field determines if training is online or offline"
echo "3. Fill the appropriate details object based on the type"
echo "4. Both online_details and offline_details are always present but only one is used"
