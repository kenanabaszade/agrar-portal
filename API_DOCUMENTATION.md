# Agrar Portal API Documentation

## Overview

Agrar Portal is an Agricultural Training & Certification Platform that enables farmers to learn sustainable farming practices, trainers to create courses, and admins to manage the platform.

**Base URL**: `http://localhost:8000/api/v1`

## Authentication

All protected endpoints require a Bearer token in the Authorization header:
```
Authorization: Bearer YOUR_TOKEN_HERE
```

### User Types
- **Admin**: Full platform control
- **Trainer**: Create/manage trainings and exams
- **Farmer**: Enroll in courses, take exams, participate in forum

---

## Authentication Endpoints

### Register User (2FA Required)
```http
POST /auth/register
```

**Request Body:**
```json
{
  "first_name": "John",
  "last_name": "Farmer",
  "email": "john@example.com",
  "password": "password123",
  "phone": "+123456789",
  "user_type": "farmer"
}
```

**Response (201):**
```json
{
  "message": "Registration successful! Please check your email for OTP verification.",
  "email": "john@example.com"
}
```

**Note:** After registration, a 6-digit OTP code will be sent to the user's email. The user must verify this OTP before they can login.

### Verify OTP
```http
POST /auth/verify-otp
```

**Request Body:**
```json
{
  "email": "john@example.com",
  "otp": "123456"
}
```

**Response (200):**
```json
{
  "message": "Email verified successfully!",
  "user": {
    "id": 1,
    "first_name": "John",
    "last_name": "Farmer",
    "email": "john@example.com",
    "email_verified": true,
    "email_verified_at": "2025-08-15T16:30:00.000000Z",
    "user_type": "farmer"
  },
  "token": "1|abc123..."
}
```

### Resend OTP
```http
POST /auth/resend-otp
```

**Request Body:**
```json
{
  "email": "john@example.com"
}
```

**Response (200):**
```json
{
  "message": "New OTP sent to your email"
}
```

### Login User (Email Verification Required)
```http
POST /auth/login
```

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response (200) - Success:**
```json
{
  "user": {
    "id": 1,
    "first_name": "John",
    "last_name": "Farmer",
    "email": "john@example.com",
    "email_verified": true,
    "user_type": "farmer"
  },
  "token": "1|abc123..."
}
```

**Response (422) - Email Not Verified:**
```json
{
  "message": "Please verify your email first. Check your inbox for OTP code.",
  "email": "john@example.com",
  "needs_verification": true
}
```

**Note:** Users must verify their email with OTP before they can login to the system.

### Logout User
```http
POST /auth/logout
```

**Headers:** `Authorization: Bearer YOUR_TOKEN`

**Response (200):**
```json
{
  "message": "Logged out"
}
```

---

## Password Reset Endpoints (2FA Protected)

### Request Password Reset
```http
POST /auth/forgot-password
```

**Request Body:**
```json
{
  "email": "john@example.com"
}
```

**Response (200):**
```json
{
  "message": "Password reset OTP sent to your email. Please check your inbox.",
  "email": "john@example.com"
}
```

**Note:** This endpoint generates a reset token and sends a 6-digit OTP to the user's email for 2FA verification.

### Verify Password Reset OTP
```http
POST /auth/verify-password-reset-otp
```

**Request Body:**
```json
{
  "email": "john@example.com",
  "otp": "123456"
}
```

**Response (200):**
```json
{
  "message": "OTP verified successfully. You can now reset your password.",
  "token": "64-character-reset-token",
  "email": "john@example.com"
}
```

**Note:** After OTP verification, the system returns a reset token that must be used to reset the password.

### Reset Password
```http
POST /auth/reset-password
```

**Request Body:**
```json
{
  "email": "john@example.com",
  "token": "64-character-reset-token",
  "password": "newPassword123",
  "password_confirmation": "newPassword123"
}
```

**Response (200):**
```json
{
  "message": "Password reset successfully. You can now login with your new password."
}
```

**Note:** The reset token expires after 24 hours and can only be used once.

### Resend Password Reset OTP
```http
POST /auth/resend-password-reset-otp
```

**Request Body:**
```json
{
  "email": "john@example.com"
}
```

**Response (200):**
```json
{
  "message": "New password reset OTP sent to your email."
}
```

**Note:** This endpoint can be used if the original OTP has expired (10 minutes).

---

## 2FA Management Endpoints

### Check 2FA Status
```http
GET /auth/2fa/status
```

**Headers:** `Authorization: Bearer YOUR_TOKEN`

**Response (200):**
```json
{
  "two_factor_enabled": true,
  "email_verified": true
}
```

### Enable 2FA
```http
POST /auth/2fa/enable
```

**Headers:** `Authorization: Bearer YOUR_TOKEN`

**Response (200):**
```json
{
  "message": "OTP sent to your email. Please verify to enable 2FA."
}
```

### Verify 2FA Activation
```http
POST /auth/2fa/verify-enable
```

**Headers:** `Authorization: Bearer YOUR_TOKEN`

**Request Body:**
```json
{
  "otp": "123456"
}
```

**Response (200):**
```json
{
  "message": "2FA enabled successfully!",
  "user": {
    "id": 1,
    "two_factor_enabled": true
  }
}
```

### Disable 2FA
```http
POST /auth/2fa/disable
```

**Headers:** `Authorization: Bearer YOUR_TOKEN`

**Response (200):**
```json
{
  "message": "OTP sent to your email. Please verify to disable 2FA."
}
```

### Verify 2FA Deactivation
```http
POST /auth/2fa/verify-disable
```

**Headers:** `Authorization: Bearer YOUR_TOKEN`

**Request Body:**
```json
{
  "otp": "123456"
}
```

**Response (200):**
```json
{
  "message": "2FA disabled successfully!",
  "user": {
    "id": 1,
    "two_factor_enabled": false
  }
}
```

---

## Training Management

### List Trainings
```http
GET /trainings
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Intro to Sustainable Farming",
      "description": "Basics of sustainable practices",
      "category": "Sustainability",
      "trainer_id": 2,
      "start_date": "2025-01-01",
      "end_date": "2025-12-31",
      "is_online": true,
      "modules": [
        {
          "id": 1,
          "title": "Soil Health",
          "lessons": [...]
        }
      ]
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 1,
    "last_page": 1
  }
}
```

### Create Training (Admin/Trainer only)
```http
POST /trainings
```

**Request Body:**
```json
{
  "title": "Advanced Crop Management",
  "description": "Learn advanced farming techniques",
  "category": "Crop Management",
  "trainer_id": 2,
  "start_date": "2025-01-01",
  "end_date": "2025-12-31",
  "is_online": true
}
```

### Get Training Details
```http
GET /trainings/{id}
```

### Update Training (Admin/Trainer only)
```http
PUT /trainings/{id}
```

### Delete Training (Admin/Trainer only)
```http
DELETE /trainings/{id}
```

---

## Training Registration

### Register for Training
```http
POST /trainings/{training_id}/register
```

**Response (201):**
```json
{
  "id": 1,
  "user_id": 1,
  "training_id": 1,
  "registration_date": "2025-08-11T10:00:00Z",
  "status": "approved"
}
```

---

## Training Module Management (Admin/Trainer only)

### List Modules for Training
```http
GET /trainings/{training_id}/modules
```

### Create Module
```http
POST /trainings/{training_id}/modules
```

**Request Body:**
```json
{
  "title": "Soil Health Fundamentals",
  "sequence": 1
}
```

### Update Module
```http
PUT /trainings/{training_id}/modules/{module_id}
```

### Delete Module
```http
DELETE /trainings/{training_id}/modules/{module_id}
```

---

## Training Lesson Management (Admin/Trainer only)

### List Lessons for Module
```http
GET /modules/{module_id}/lessons
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "module_id": 1,
      "title": "Introduction to Soil Types",
      "lesson_type": "mixed",
      "duration_minutes": 45,
      "description": "Learn about different soil types and their characteristics",
      "status": "published",
      "is_required": true,
      "sequence": 1,
      "media_files": [
        {
          "type": "video",
          "url": "https://example.com/video.mp4",
          "title": "Soil Types Video",
          "description": "Comprehensive overview of soil types"
        }
      ]
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 1
  }
}
```

### Create Lesson
```http
POST /modules/{module_id}/lessons
```

**Request Body:**
```json
{
  "title": "Understanding Soil pH",
  "lesson_type": "mixed",
  "duration_minutes": 30,
  "content": "Soil pH is a measure of soil acidity...",
  "description": "Learn about soil pH and its importance in farming",
  "video_url": "https://example.com/ph-video.mp4",
  "media_files": [
    {
      "type": "image",
      "url": "https://example.com/ph-chart.jpg",
      "title": "pH Scale Chart",
      "description": "Visual representation of pH scale"
    },
    {
      "type": "document",
      "url": "https://example.com/ph-guide.pdf",
      "title": "pH Testing Guide",
      "description": "Step-by-step guide for testing soil pH"
    }
  ],
  "status": "published",
  "is_required": true,
  "min_completion_time": 1800,
  "metadata": {
    "difficulty": "intermediate",
    "tags": ["soil", "ph", "testing"]
  }
}
```

**Lesson Types:**
- `text`: Text-only content
- `video`: Video content
- `audio`: Audio content
- `image`: Image content
- `mixed`: Combination of multiple media types

**Media File Types:**
- `image`: Images (JPG, PNG, GIF, etc.)
- `video`: Videos (MP4, AVI, MOV, etc.)
- `audio`: Audio files (MP3, WAV, etc.)
- `document`: Documents (PDF, DOC, etc.)

### Get Lesson Details
```http
GET /lessons/{lesson_id}
```

**Response:**
```json
{
  "lesson": {
    "id": 1,
    "module_id": 1,
    "title": "Understanding Soil pH",
    "lesson_type": "mixed",
    "duration_minutes": 30,
    "content": "Soil pH is a measure of soil acidity...",
    "description": "Learn about soil pH and its importance",
    "video_url": "https://example.com/ph-video.mp4",
    "status": "published",
    "is_required": true,
    "sequence": 1,
    "media_files": [...],
    "metadata": {...}
  },
  "content": {
    "text": "Soil pH is a measure of soil acidity...",
    "description": "Learn about soil pH and its importance",
    "media": [...],
    "video_url": "https://example.com/ph-video.mp4"
  },
  "duration": "30m"
}
```

### Update Lesson
```http
PUT /lessons/{lesson_id}
```

### Delete Lesson
```http
DELETE /lessons/{lesson_id}
```

### Upload Media to Lesson
```http
POST /lessons/{lesson_id}/upload-media
```

**Request Body (multipart/form-data):**
```
file: [binary file]
type: image
title: Soil Sample Image
description: Close-up view of soil sample
```

**Response:**
```json
{
  "message": "Media uploaded successfully",
  "media_file": {
    "type": "image",
    "url": "/storage/lessons/1/image.jpg",
    "filename": "soil-sample.jpg",
    "size": 1024000,
    "mime_type": "image/jpeg",
    "title": "Soil Sample Image",
    "description": "Close-up view of soil sample"
  }
}
```

### Remove Media from Lesson
```http
DELETE /lessons/{lesson_id}/remove-media
```

**Request Body:**
```json
{
  "media_index": 0
}
```

### Reorder Lessons
```http
POST /modules/{module_id}/reorder-lessons
```

**Request Body:**
```json
{
  "lesson_order": [3, 1, 2, 4]
}
```

---

## Lesson Progress Tracking (Students)

### Get Lesson Progress
```http
GET /lessons/{lesson_id}/progress
```

**Response:**
```json
{
  "lesson_id": 1,
  "progress": {
    "id": 1,
    "user_id": 1,
    "training_id": 1,
    "module_id": 1,
    "lesson_id": 1,
    "status": "completed",
    "completed_at": "2025-08-11T10:30:00Z",
    "time_spent": 1800,
    "notes": "Very informative lesson"
  },
  "is_completed": true
}
```

### Mark Lesson as Completed
```http
POST /lessons/{lesson_id}/complete
```

**Request Body:**
```json
{
  "time_spent": 1800,
  "notes": "Learned a lot about soil pH testing"
}
```

**Response:**
```json
{
  "message": "Lesson marked as completed",
  "progress": {
    "id": 1,
    "user_id": 1,
    "training_id": 1,
    "module_id": 1,
    "lesson_id": 1,
    "status": "completed",
    "completed_at": "2025-08-11T10:30:00Z",
    "time_spent": 1800,
    "notes": "Learned a lot about soil pH testing"
  }
}
```

---

## Progress Tracking

### List User Progress
```http
GET /progress
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "training_id": 1,
      "module_id": 1,
      "lesson_id": 1,
      "status": "completed",
      "last_accessed": "2025-08-11T10:00:00Z",
      "completed_at": "2025-08-11T10:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 50,
    "total": 1
  }
}
```

### Update Progress
```http
POST /progress
```

**Request Body:**
```json
{
  "training_id": 1,
  "module_id": 1,
  "lesson_id": 1,
  "status": "completed",
  "completed_at": "2025-08-11T10:30:00Z"
}
```

---

## Exam Management

### List Exams
```http
GET /exams
```

### Create Exam (Admin/Trainer only)
```http
POST /exams
```

**Request Body:**
```json
{
  "training_id": 1,
  "title": "Final Assessment",
  "description": "Comprehensive exam",
  "passing_score": 70,
  "duration_minutes": 60,
  "start_date": "2025-01-01",
  "end_date": "2025-12-31"
}
```

### Get Exam Details
```http
GET /exams/{id}
```

### Update Exam (Admin/Trainer only)
```http
PUT /exams/{id}
```

### Delete Exam (Admin/Trainer only)
```http
DELETE /exams/{id}
```

---

## Enhanced Question Features

### Question Types with Media Support:
- **Single Choice**: One correct answer with optional media
- **Multiple Choice**: Multiple correct answers with optional media
- **Text**: Free text answer with optional media prompts

### Media Types Supported:
- **Images**: JPG, PNG, GIF, etc. for visual questions
- **Videos**: MP4, AVI, MOV, etc. for dynamic content
- **Audio**: MP3, WAV, etc. for listening comprehension
- **Documents**: PDF, DOC, etc. for reference materials

### Enhanced Scoring:
- **Points per Question**: Each question can have different point values
- **Points per Choice**: Individual choices can award points
- **Required vs Optional**: Questions can be marked as required or optional
- **Explanations**: Detailed explanations for correct answers

### Security Features:
- **Exam Taking Mode**: Hides correct answers and explanations during exam
- **Review Mode**: Shows all information after exam completion
- **Media Validation**: Ensures uploaded files are valid and secure

---

## Exam Question Management (Admin/Trainer only)

### Add Single Choice Question
```http
POST /exams/{exam_id}/questions
```

**Request Body:**
```json
{
  "question_text": "What is composting?",
  "question_type": "single_choice",
  "question_media": [
    {
      "type": "image",
      "url": "https://example.com/compost-pile.jpg",
      "title": "Compost Pile",
      "description": "A healthy compost pile"
    }
  ],
  "explanation": "Composting is the natural process of recycling organic matter into nutrient-rich soil.",
  "points": 2,
  "is_required": true,
  "sequence": 1,
  "choices": [
    {
      "choice_text": "A way to recycle organic matter",
      "choice_media": [
        {
          "type": "image",
          "url": "https://example.com/recycling.jpg",
          "title": "Recycling Process"
        }
      ],
      "is_correct": true,
      "explanation": "This is the correct answer because composting breaks down organic materials.",
      "points": 2
    },
    {
      "choice_text": "A type of soil erosion",
      "is_correct": false,
      "explanation": "Soil erosion is the loss of soil, not the creation of soil.",
      "points": 0
    },
    {
      "choice_text": "A chemical fertilizer",
      "is_correct": false,
      "explanation": "Chemical fertilizers are synthetic, not natural like compost.",
      "points": 0
    }
  ],
  "metadata": {
    "difficulty": "easy",
    "tags": ["composting", "organic", "soil"]
  }
}
```

### Add Multiple Choice Question with Media
```http
POST /exams/{exam_id}/questions
```

**Request Body:**
```json
{
  "question_text": "Which of the following are benefits of sustainable farming?",
  "question_type": "multiple_choice",
  "question_media": [
    {
      "type": "video",
      "url": "https://example.com/sustainable-farming.mp4",
      "title": "Sustainable Farming Overview",
      "description": "Video explaining sustainable farming practices"
    }
  ],
  "explanation": "Sustainable farming provides multiple environmental and economic benefits.",
  "points": 3,
  "sequence": 2,
  "choices": [
    {
      "choice_text": "Reduced soil erosion",
      "choice_media": [
        {
          "type": "image",
          "url": "https://example.com/soil-conservation.jpg",
          "title": "Soil Conservation"
        }
      ],
      "is_correct": true,
      "points": 1
    },
    {
      "choice_text": "Lower water consumption",
      "is_correct": true,
      "points": 1
    },
    {
      "choice_text": "Increased biodiversity",
      "is_correct": true,
      "points": 1
    },
    {
      "choice_text": "Higher chemical usage",
      "is_correct": false,
      "points": 0
    }
  ]
}
```

### Add Text Question with Audio
```http
POST /exams/{exam_id}/questions
```

**Request Body:**
```json
{
  "question_text": "Listen to the audio and describe the soil type you hear about.",
  "question_type": "text",
  "question_media": [
    {
      "type": "audio",
      "url": "https://example.com/soil-description.mp3",
      "title": "Soil Type Audio",
      "description": "Audio description of soil characteristics"
    }
  ],
  "explanation": "The audio describes clay soil, which is characterized by fine particles and high water retention.",
  "points": 5,
  "sequence": 3
}
```

### Upload Media to Question
```http
POST /exams/{exam_id}/upload-question-media
```

**Request Body (multipart/form-data):**
```
file: [binary file]
type: image
target_type: question
question_id: 1
title: Soil Sample Image
description: Close-up view of soil sample
```

**Response:**
```json
{
  "message": "Question media uploaded successfully",
  "media_file": {
    "type": "image",
    "url": "/storage/exams/1/questions/1/image.jpg",
    "filename": "soil-sample.jpg",
    "size": 1024000,
    "mime_type": "image/jpeg",
    "title": "Soil Sample Image",
    "description": "Close-up view of soil sample"
  }
}
```

### Upload Media to Choice
```http
POST /exams/{exam_id}/upload-question-media
```

**Request Body (multipart/form-data):**
```
file: [binary file]
type: image
target_type: choice
question_id: 1
choice_id: 2
title: Choice Image
description: Visual representation of this choice
```

### Get Exam for Taking (Students)
```http
GET /exams/{exam_id}/take
```

**Response:**
```json
{
  "exam": {
    "id": 1,
    "title": "Sustainable Farming Final",
    "description": "Comprehensive exam on sustainable practices",
    "duration_minutes": 60,
    "passing_score": 70
  },
  "questions": [
    {
      "id": 1,
      "question_text": "What is composting?",
      "question_media": [
        {
          "type": "image",
          "url": "https://example.com/compost-pile.jpg",
          "title": "Compost Pile"
        }
      ],
      "question_type": "single_choice",
      "points": 2,
      "sequence": 1,
      "choices": [
        {
          "id": 1,
          "choice_text": "A way to recycle organic matter",
          "choice_media": [
            {
              "type": "image",
              "url": "https://example.com/recycling.jpg",
              "title": "Recycling Process"
            }
          ]
        },
        {
          "id": 2,
          "choice_text": "A type of soil erosion"
        }
      ]
    }
  ],
  "total_questions": 10,
  "total_points": 25
}
```

### Submit Exam with Enhanced Answers
```http
POST /exams/{exam_id}/submit
```

**Request Body:**
```json
{
  "answers": [
    {
      "question_id": 1,
      "choice_id": 1
    },
    {
      "question_id": 2,
      "choice_ids": [1, 2, 3]
    },
    {
      "question_id": 3,
      "answer_text": "The audio described clay soil with fine particles and high water retention capacity."
    }
  ]
}
```

**Response:**
```json
{
  "id": 1,
  "user_id": 1,
  "exam_id": 1,
  "status": "passed",
  "score": 85,
  "finished_at": "2025-08-11T10:30:00Z",
  "certificate": {
    "id": 1,
    "certificate_number": "CERT-2025-001",
    "issue_date": "2025-08-11"
  }
}
```

### Get Exam with Questions (Admin/Trainer)
```http
GET /exams/{exam_id}/questions
```

**Response:**
```json
{
  "exam": {
    "id": 1,
    "title": "Sustainable Farming Final",
    "description": "Comprehensive exam on sustainable practices"
  },
  "questions": [
    {
      "id": 1,
      "question_text": "What is composting?",
      "question_media": [...],
      "explanation": "Composting is the natural process of recycling organic matter...",
      "question_type": "single_choice",
      "points": 2,
      "is_required": true,
      "sequence": 1,
      "choices": [
        {
          "id": 1,
          "choice_text": "A way to recycle organic matter",
          "choice_media": [...],
          "is_correct": true,
          "explanation": "This is the correct answer because...",
          "points": 2
        }
      ]
    }
  ],
  "total_questions": 10,
  "total_points": 25,
  "has_media": true
}
```

---

## Certificates

### List Certificates
```http
GET /certificates
```

### Get Certificate Details
```http
GET /certificates/{id}
```

---

## Forum

### List Questions
```http
GET /forum/questions
```

### Post Question
```http
POST /forum/questions
```

**Request Body:**
```json
{
  "title": "Best time to plant tomatoes?",
  "body": "When is the optimal planting season for tomatoes in this region?"
}
```

### Answer Question
```http
POST /forum/questions/{question_id}/answers
```

**Request Body:**
```json
{
  "body": "Spring is the best time to plant tomatoes, typically after the last frost."
}
```

---

## Notifications

### List Notifications
```http
GET /notifications
```

### Mark Notification as Read
```http
POST /notifications/{notification_id}/read
```

---

## Payments

### List Payments
```http
GET /payments
```

### Create Payment
```http
POST /payments
```

**Request Body:**
```json
{
  "amount": 50.00,
  "currency": "USD",
  "payment_method": "card",
  "status": "pending",
  "related_exam_registration_id": 1
}
```

### Payment Webhook
```http
POST /payments/webhook
```

**Request Body:**
```json
{
  "payment_id": 1,
  "status": "paid"
}
```

---

## User Management (Admin only)

### List Users
```http
GET /users
```

### Get User Details
```http
GET /users/{id}
```

### Update User
```http
PATCH /users/{id}
```

**Request Body:**
```json
{
  "first_name": "Updated Name",
  "user_type": "trainer",
  "is_active": true
}
```

---

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "message": "Forbidden: requires role admin"
}
```

### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

### 404 Not Found
```json
{
  "message": "No query results for model [App\\Models\\Training] 1"
}
```

---

## Business Logic Flow

### 1. Training Creation (Admin/Trainer)
1. Create training with modules and lessons
2. Create exam with questions and choices
3. Set passing score and duration

### 2. Student Learning (Farmer)
1. Register for training
2. Progress through lessons (track progress)
3. Register for exam
4. Take exam and submit answers
5. Receive certificate if passed

### 3. Community Engagement
1. Ask questions in forum
2. Answer other users' questions
3. Receive notifications

---

## Testing Examples

### Complete Learning Flow

```bash
# 1. Register as farmer
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "John",
    "last_name": "Farmer",
    "email": "john@example.com",
    "password": "password123",
    "user_type": "farmer"
  }'

# 2. Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'

# 3. Register for training (use token from login)
curl -X POST http://localhost:8000/api/v1/trainings/1/register \
  -H "Authorization: Bearer YOUR_TOKEN"

# 4. Update progress
curl -X POST http://localhost:8000/api/v1/progress \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "training_id": 1,
    "module_id": 1,
    "lesson_id": 1,
    "status": "completed"
  }'

# 5. Register for exam
curl -X POST http://localhost:8000/api/v1/exams/1/register \
  -H "Authorization: Bearer YOUR_TOKEN"

# 6. Start exam
curl -X POST http://localhost:8000/api/v1/exams/1/start \
  -H "Authorization: Bearer YOUR_TOKEN"

# 7. Submit exam
curl -X POST http://localhost:8000/api/v1/exams/1/submit \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "answers": [
      {
        "question_id": 1,
        "choice_id": 1
      }
    ]
  }'
```

### Admin Operations

```bash
# Login as admin
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password123"
  }'

# Create training
curl -X POST http://localhost:8000/api/v1/trainings \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Advanced Techniques",
    "description": "Advanced farming methods",
    "category": "Advanced",
    "trainer_id": 2,
    "is_online": true
  }'

# List all users
curl -X GET http://localhost:8000/api/v1/users \
  -H "Authorization: Bearer ADMIN_TOKEN"
```

---

## Response Format

All paginated responses follow this format:
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 100,
    "last_page": 5,
    "from": 1,
    "to": 20,
    "prev_page_url": null,
    "next_page_url": "http://localhost:8000/api/v1/users?page=2"
  }
}
```

---

## Setup Instructions

1. **Install dependencies:**
   ```bash
   composer install
   ```

2. **Configure environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Setup database:**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

4. **Start server:**
   ```bash
   php artisan serve
   ```

5. **Test with seeded data:**
   - Admin: `admin@example.com` / `password123`
   - Trainer: `trainer@example.com` / `password123`
   - Farmer: `farmer@example.com` / `password123`

---

## Rate Limiting

- API endpoints are rate limited
- Authentication endpoints: 5 requests per minute
- Other endpoints: 60 requests per minute

---

## Support

For API support, contact the development team or refer to the application logs at `storage/logs/laravel.log`.
