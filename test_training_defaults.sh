#!/bin/bash

# Test Training Creation with Default Values
echo "🧪 Testing Training Creation with Default Values"
echo "================================================"

BASE_URL="http://localhost:8000"
AUTH_TOKEN="34|m1tDPv0d9hmb6S9nWTLJgeMLIeXU4p7JQUp3pv5ea41a8e42"

echo ""
echo "1. Test minimal training creation (only required fields)..."
curl -s -X POST "$BASE_URL/api/v1/trainings" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Minimal Training Test",
    "trainer_id": 36
  }' | jq .

echo ""
echo "2. Test training with your specified structure..."
curl -s -X POST "$BASE_URL/api/v1/trainings" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Advanced Crop Management",
    "description": "Learn advanced farming techniques for modern agriculture",
    "category": "Crop Management",
    "trainer_id": 36,
    "start_date": "2025-01-01",
    "end_date": "2025-12-31"
  }' | jq .

echo ""
echo "3. Test training with explicit defaults..."
curl -s -X POST "$BASE_URL/api/v1/trainings" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Explicit Defaults Test",
    "trainer_id": 36,
    "is_online": true,
    "has_certificate": false,
    "type": "online",
    "difficulty": "beginner"
  }' | jq .

echo ""
echo "4. Test training with null values..."
curl -s -X POST "$BASE_URL/api/v1/trainings" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Null Values Test",
    "trainer_id": 36,
    "description": null,
    "category": null,
    "type": null,
    "difficulty": null,
    "online_details": null,
    "offline_details": null
  }' | jq .

echo ""
echo "✅ Training defaults test completed!"
echo ""
echo "📋 Expected Results:"
echo "- is_online should default to true"
echo "- has_certificate should default to false"
echo "- All nullable fields should accept null values"
echo "- type and difficulty should be nullable"
