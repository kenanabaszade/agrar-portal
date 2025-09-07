#!/bin/bash

# Test script for hybrid media approach (single + separate endpoints)
BASE_URL="http://localhost:8000/api/v1"
AUTH_TOKEN="your_auth_token_here"

echo "=== Testing Hybrid Media Approach ==="
echo "This demonstrates both single endpoint and separate endpoint approaches"
echo ""

# Test 1: Create Training WITHOUT files (using separate endpoint approach)
echo "1. Creating Training WITHOUT files (separate endpoint approach)..."
TRAINING_RESPONSE=$(curl -s -X POST "$BASE_URL/trainings" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Hybrid Approach Training",
    "description": "Testing both single and separate endpoint approaches",
    "category": "Testing",
    "trainer_id": 2,
    "start_date": "2025-01-01",
    "end_date": "2025-12-31",
    "is_online": true,
    "type": "online",
    "online_details": {
      "participant_size": "30",
      "google_meet_link": "https://meet.google.com/test-link"
    },
    "offline_details": {
      "participant_size": "",
      "address": "",
      "coordinates": ""
    }
  }')

echo "$TRAINING_RESPONSE"
echo ""

# Extract training ID (assuming response contains the training)
TRAINING_ID=$(echo "$TRAINING_RESPONSE" | grep -o '"id":[0-9]*' | cut -d':' -f2)
echo "Created Training ID: $TRAINING_ID"
echo ""

# Test 2: Upload media files using SEPARATE endpoint
echo "2. Uploading media files using SEPARATE endpoint..."
echo "   POST /api/v1/trainings/$TRAINING_ID/upload-media"
echo "   (This would be a multipart/form-data request with files)"
echo "   Example: curl -X POST \"$BASE_URL/trainings/$TRAINING_ID/upload-media\" \\"
echo "     -H \"Authorization: Bearer \$AUTH_TOKEN\" \\"
echo "     -F \"banner_image=@banner.jpg\" \\"
echo "     -F \"intro_video=@intro.mp4\" \\"
echo "     -F \"media_files[]=@document1.pdf\" \\"
echo "     -F \"media_files[]=@document2.pdf\""
echo ""

# Test 3: Get media files using separate endpoint
echo "3. Getting media files using SEPARATE endpoint..."
echo "   GET /api/v1/trainings/$TRAINING_ID/media"
curl -X GET "$BASE_URL/trainings/$TRAINING_ID/media" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Accept: application/json"

echo ""
echo ""

# Test 4: Create Training WITH files (using single endpoint approach)
echo "4. Creating Training WITH files (single endpoint approach)..."
echo "   POST /api/v1/trainings (with multipart/form-data)"
echo "   (This would include both training data and files in one request)"
echo "   Example: curl -X POST \"$BASE_URL/trainings\" \\"
echo "     -H \"Authorization: Bearer \$AUTH_TOKEN\" \\"
echo "     -F \"title=Complete Training with Files\" \\"
echo "     -F \"description=Training created with files in one request\" \\"
echo "     -F \"category=Complete\" \\"
echo "     -F \"trainer_id=2\" \\"
echo "     -F \"start_date=2025-01-01\" \\"
echo "     -F \"end_date=2025-12-31\" \\"
echo "     -F \"is_online=true\" \\"
echo "     -F \"type=online\" \\"
echo "     -F \"online_details[participant_size]=40\" \\"
echo "     -F \"online_details[google_meet_link]=https://meet.google.com/complete\" \\"
echo "     -F \"banner_image=@banner.jpg\" \\"
echo "     -F \"intro_video=@intro.mp4\" \\"
echo "     -F \"media_files[]=@doc1.pdf\" \\"
echo "     -F \"media_files[]=@doc2.pdf\""
echo ""

# Test 5: Remove specific media file using separate endpoint
echo "5. Removing specific media file using SEPARATE endpoint..."
echo "   DELETE /api/v1/trainings/$TRAINING_ID/media/{file_path}"
echo "   Example: curl -X DELETE \"$BASE_URL/trainings/$TRAINING_ID/media/trainings/banners/banner_123.jpg\" \\"
echo "     -H \"Authorization: Bearer \$AUTH_TOKEN\""
echo ""

echo "=== Summary of Approaches ==="
echo ""
echo "🔄 SINGLE ENDPOINT APPROACH:"
echo "   ✅ Pros: Atomic operations, simpler frontend, better UX"
echo "   ❌ Cons: Large payloads, timeout risk, less flexible"
echo "   📍 Use Case: Small files, simple uploads, quick training creation"
echo ""
echo "🔄 SEPARATE ENDPOINT APPROACH:"
echo "   ✅ Pros: Better performance, progressive upload, more flexible"
echo "   ❌ Cons: More complex, partial states, multiple requests"
echo "   📍 Use Case: Large files, many files, advanced file management"
echo ""
echo "🔄 HYBRID APPROACH (RECOMMENDED):"
echo "   ✅ Best of both worlds"
echo "   📍 Use single endpoint for simple cases"
echo "   📍 Use separate endpoints for advanced cases"
echo ""
echo "=== Available Endpoints ==="
echo ""
echo "📝 Training CRUD (with optional files):"
echo "   POST   /api/v1/trainings                    - Create training (with/without files)"
echo "   GET    /api/v1/trainings                    - List trainings"
echo "   GET    /api/v1/trainings/{id}               - Get training"
echo "   PATCH  /api/v1/trainings/{id}               - Update training (with/without files)"
echo "   DELETE /api/v1/trainings/{id}               - Delete training"
echo ""
echo "📁 Separate Media Management:"
echo "   POST   /api/v1/trainings/{id}/upload-media  - Upload media files"
echo "   GET    /api/v1/trainings/{id}/media         - Get all media files"
echo "   DELETE /api/v1/trainings/{id}/media/{path}  - Remove specific media file"
echo ""
echo "=== Usage Recommendations ==="
echo ""
echo "🎯 Use SINGLE ENDPOINT when:"
echo "   - Creating training with small files (< 10MB total)"
echo "   - Simple training creation workflow"
echo "   - Want atomic operations"
echo ""
echo "🎯 Use SEPARATE ENDPOINTS when:"
echo "   - Adding files to existing training"
echo "   - Large file uploads (> 10MB)"
echo "   - Progressive file upload"
echo "   - Advanced file management"
echo "   - Want better error handling for file uploads"
echo ""
echo "=== Test Complete ==="
