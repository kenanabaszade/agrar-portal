# Exam Management API Documentation

This document describes the enhanced exam management APIs for the admin dashboard based on the UI mockups provided.

## Overview

The exam management system provides comprehensive CRUD operations for exams with admin-only access, advanced filtering, search capabilities, and detailed statistics.

## Authentication & Authorization

All exam management endpoints require:
- Valid authentication token (`Authorization: Bearer <token>`)
- Admin role (`user_type: 'admin'` or role with name 'admin')

## API Endpoints

### 1. Get Exam Statistics
**GET** `/api/v1/exams/stats`

Returns dashboard statistics for exams.

**Response:**
```json
{
  "total_exams": 127,
  "active_exams": 45,
  "upcoming_exams": 12,
  "total_registrations": 1847,
  "completed_exams": 1203,
  "average_score": 76.4,
  "completion_rate": 65.2
}
```

### 2. Get Form Data
**GET** `/api/v1/exams/form-data`

Returns data needed for exam creation/editing forms.

**Response:**
```json
{
  "categories": [
    "Bitki Becerilmesi",
    "Torpaq Sağlamlığı",
    "Zərərverici Mübarizə"
  ],
  "trainings": [
    {
      "id": 1,
      "title": "Bitki Becerilmesi Əsasları",
      "category": "Bitki Becerilmesi",
      "trainer": {
        "id": 5,
        "first_name": "Dr. Sabina",
        "last_name": "Həsənova"
      }
    }
  ],
  "trainers": [
    {
      "id": 5,
      "first_name": "Dr. Sabina",
      "last_name": "Həsənova",
      "email": "sabina@example.com"
    }
  ]
}
```

### 3. List Exams with Filtering
**GET** `/api/v1/exams`

Advanced exam listing with pagination, search, and filtering.

**Query Parameters:**
- `search` (string): Search in title, description, training title, or category
- `category` (string): Filter by training category
- `training_id` (integer): Filter by specific training
- `status` (string): Filter by status (`upcoming`, `active`, `ended`)
- `sort_by` (string): Sort field (`title`, `created_at`, `start_date`, `end_date`, `passing_score`)
- `sort_order` (string): Sort direction (`asc`, `desc`)
- `per_page` (integer): Items per page (max 100, default 15)
- `page` (integer): Page number

**Example Request:**
```
GET /api/v1/exams?search=bitki&category=Bitki%20Becerilmesi&status=active&sort_by=title&sort_order=asc&per_page=20
```

**Response:**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "title": "Bitki Becerilmesi Əsasları - Sınaq",
      "description": "Bitki becerilmesi haqqında ətraflı məlumat və nə qiymətləndirəcəyini yazın...",
      "training_id": 1,
      "passing_score": 70,
      "duration_minutes": 60,
      "start_date": "2024-08-10",
      "end_date": "2024-08-15",
      "status": "active",
      "completion_rate": 78.5,
      "pass_rate": 89.2,
      "created_at": "2024-08-09T10:30:00Z",
      "updated_at": "2024-08-09T10:30:00Z",
      "training": {
        "id": 1,
        "title": "Bitki Becerilmesi Əsasları",
        "category": "Bitki Becerilmesi",
        "trainer": {
          "id": 5,
          "first_name": "Dr. Sabina",
          "last_name": "Həsənova"
        }
      },
      "questions_count": 25,
      "registrations_count": 156
    }
  ],
  "first_page_url": "http://example.com/api/v1/exams?page=1",
  "from": 1,
  "last_page": 7,
  "last_page_url": "http://example.com/api/v1/exams?page=7",
  "links": [...],
  "next_page_url": "http://example.com/api/v1/exams?page=2",
  "path": "http://example.com/api/v1/exams",
  "per_page": 15,
  "prev_page_url": null,
  "to": 15,
  "total": 127
}
```

### 4. Create Exam
**POST** `/api/v1/exams`

Creates a new exam.

**Request Body:**
```json
{
  "training_id": 1,
  "title": "Yeni İmtahan",
  "description": "İmtahan haqqında ətraflı məlumat və nə qiymətləndirəcəyini yazın...",
  "passing_score": 70,
  "duration_minutes": 60,
  "start_date": "2024-08-20",
  "end_date": "2024-08-25"
}
```

**Validation Rules:**
- `training_id`: Required, must exist in trainings table
- `title`: Required, string, max 255 characters
- `description`: Optional, string
- `passing_score`: Required, integer, 0-100
- `duration_minutes`: Required, integer, 1-480 (max 8 hours)
- `start_date`: Optional, date, must be today or future
- `end_date`: Optional, date, must be >= start_date

**Response:**
```json
{
  "message": "Exam created successfully",
  "exam": {
    "id": 128,
    "title": "Yeni İmtahan",
    "description": "İmtahan haqqında ətraflı məlumat...",
    "training_id": 1,
    "passing_score": 70,
    "duration_minutes": 60,
    "start_date": "2024-08-20",
    "end_date": "2024-08-25",
    "created_at": "2024-08-09T14:30:00Z",
    "updated_at": "2024-08-09T14:30:00Z",
    "training": {
      "id": 1,
      "title": "Bitki Becerilmesi Əsasları",
      "trainer": {
        "id": 5,
        "first_name": "Dr. Sabina",
        "last_name": "Həsənova"
      }
    },
    "questions": []
  }
}
```

### 5. Show Exam Details
**GET** `/api/v1/exams/{id}`

Returns detailed exam information with statistics.

**Response:**
```json
{
  "id": 1,
  "title": "Bitki Becerilmesi Əsasları - Sınaq",
  "description": "Bitki becerilmesi haqqında ətraflı məlumat...",
  "training_id": 1,
  "passing_score": 70,
  "duration_minutes": 60,
  "start_date": "2024-08-10",
  "end_date": "2024-08-15",
  "created_at": "2024-08-09T10:30:00Z",
  "updated_at": "2024-08-09T10:30:00Z",
  "training": {
    "id": 1,
    "title": "Bitki Becerilmesi Əsasları",
    "category": "Bitki Becerilmesi",
    "trainer": {
      "id": 5,
      "first_name": "Dr. Sabina",
      "last_name": "Həsənova"
    }
  },
  "questions": [
    {
      "id": 1,
      "question_text": "Bitki becerilmesinin əsas prinsipi nədir?",
      "question_type": "single_choice",
      "points": 4,
      "sequence": 1,
      "choices": [
        {
          "id": 1,
          "choice_text": "Variant 1",
          "is_correct": true
        },
        {
          "id": 2,
          "choice_text": "Variant 2",
          "is_correct": false
        }
      ]
    }
  ],
  "registrations": [
    {
      "id": 1,
      "status": "passed",
      "score": 85,
      "created_at": "2024-08-10T09:00:00Z",
      "user": {
        "id": 10,
        "first_name": "Kenan",
        "last_name": "Abaszade",
        "email": "kenan@example.com"
      }
    }
  ],
  "stats": {
    "total_registrations": 156,
    "completed_registrations": 98,
    "passed_registrations": 87,
    "completion_rate": 62.8,
    "pass_rate": 88.8,
    "average_score": 76.4,
    "total_questions": 25
  }
}
```

### 6. Update Exam
**PUT/PATCH** `/api/v1/exams/{id}`

Updates an existing exam.

**Request Body:** (all fields optional for PATCH)
```json
{
  "training_id": 2,
  "title": "Yenilənmiş İmtahan Başlığı",
  "description": "Yenilənmiş təsvir...",
  "passing_score": 75,
  "duration_minutes": 90,
  "start_date": "2024-08-25",
  "end_date": "2024-08-30"
}
```

**Response:**
```json
{
  "message": "Exam updated successfully",
  "exam": {
    "id": 1,
    "title": "Yenilənmiş İmtahan Başlığı",
    // ... updated exam data
  }
}
```

### 7. Delete Exam
**DELETE** `/api/v1/exams/{id}`

Deletes an exam. Fails if exam has registrations.

**Response (Success):**
```json
{
  "message": "Exam deleted successfully"
}
```

**Response (Error - Has Registrations):**
```json
{
  "message": "Cannot delete exam with existing registrations",
  "registrations_count": 156
}
```

## Question Management

The existing question management endpoints remain available for admin/trainer roles:

- **POST** `/api/v1/exams/{exam}/questions` - Add question
- **PUT** `/api/v1/exams/{exam}/questions/{question}` - Update question  
- **DELETE** `/api/v1/exams/{exam}/questions/{question}` - Delete question
- **GET** `/api/v1/exams/{exam}/questions` - Get exam questions

## Error Responses

All endpoints return consistent error responses:

**Validation Error (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "title": ["The title field is required."],
    "passing_score": ["The passing score must be between 0 and 100."]
  }
}
```

**Forbidden (403):**
```json
{
  "message": "Forbidden"
}
```

**Not Found (404):**
```json
{
  "message": "No query results for model [App\\Models\\Exam] 999"
}
```

## Usage Examples

### Frontend Integration

```javascript
// Get exam statistics for dashboard
const stats = await fetch('/api/v1/exams/stats', {
  headers: { 'Authorization': `Bearer ${token}` }
}).then(r => r.json());

// Search and filter exams
const exams = await fetch('/api/v1/exams?' + new URLSearchParams({
  search: 'bitki',
  category: 'Bitki Becerilmesi',
  status: 'active',
  sort_by: 'title',
  per_page: 20
}), {
  headers: { 'Authorization': `Bearer ${token}` }
}).then(r => r.json());

// Create new exam
const newExam = await fetch('/api/v1/exams', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    training_id: 1,
    title: 'Yeni İmtahan',
    description: 'İmtahan təsviri...',
    passing_score: 70,
    duration_minutes: 60
  })
}).then(r => r.json());
```

## Notes

- All endpoints require admin authentication
- Audit logs are automatically created for create/update/delete operations
- Exam deletion is prevented if there are existing registrations
- Statistics are calculated in real-time
- Pagination follows Laravel's standard format
- All dates are in ISO 8601 format
- File uploads for question media use separate endpoints
