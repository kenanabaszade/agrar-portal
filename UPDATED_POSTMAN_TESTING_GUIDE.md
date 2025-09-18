# Updated Postman Testing Guide - Enhanced Exam System

## 🎯 **Recent Updates Summary**

The Postman collection has been updated to include:
- ✅ **Independent exam support** - Exams without training
- ✅ **Difficulty levels** - Easy/Medium/Hard questions
- ✅ **Enhanced timing system** - Real-time timing validation
- ✅ **Category management** - Full CRUD operations
- ✅ **Improved error handling** - Better debugging

---

## 📋 **New Testing Sections**

### **1. Category Management**
Test the new category system that supports both training categories and independent exam categories.

#### **Test Categories Dropdown:**
```bash
GET /api/v1/categories/dropdown
```
**Expected:** List of active categories for form dropdowns

#### **Test Category CRUD:**
```bash
POST /api/v1/categories
{
  "name": "Ümumi Kənd Təsərrüfatı",
  "description": "General agricultural knowledge",
  "is_active": true,
  "sort_order": 1
}
```

### **2. Independent Exam Creation**
Test creating exams that are not tied to any training.

#### **Test Independent Exam:**
```bash
POST /api/v1/exams
{
  "title": "Ümumi Kənd Təsərrüfatı Bilikləri Sınağı",
  "category": "Ümumi Kənd Təsərrüfatı",
  "passing_score": 75,
  "duration_minutes": 90,
  "questions": [
    {
      "question_text": "Kənd təsərrüfatında hansı əsas sahələr mövcuddur?",
      "question_type": "multiple_choice",
      "difficulty": "easy",
      "points": 5
    }
  ]
}
```

**Key Points:**
- ❌ **No `training_id`** - This makes it independent
- ✅ **`category` required** - Manual category assignment
- ✅ **All question types supported** - With difficulty levels

### **3. Enhanced Exam Timing Testing**
Test the comprehensive timing system.

#### **Test Exam Session Timing:**
```bash
GET /api/v1/exams/4/take
```

**Expected Response:**
```json
{
  "time_info": {
    "time_elapsed_minutes": 15,
    "time_remaining_minutes": 45,
    "time_limit_minutes": 60,
    "time_exceeded": false,
    "started_at": "2024-08-20T10:00:00Z"
  }
}
```

#### **Test Normal Submission:**
```bash
POST /api/v1/exams/4/submit
{
  "answers": [...]
}
```

**Expected for in-time submission:**
```json
{
  "status": "passed",
  "score": 85,
  "time_elapsed_minutes": 45,
  "time_exceeded": false,
  "certificate": {
    "certificate_number": "CERT-12345"
  }
}
```

#### **Test Timeout Scenario:**
- Start exam, wait for duration to exceed
- Submit answers
- **Expected:**
```json
{
  "status": "timeout",
  "score": 75,
  "time_exceeded": true,
  "certificate": null
}
```

### **4. Question Difficulty Testing**
Test questions with different difficulty levels.

#### **Question Examples by Difficulty:**

**Easy Question (2-5 points):**
```json
{
  "question_text": "Bitki becerilmesinin əsas prinsipi nədir?",
  "question_type": "single_choice",
  "difficulty": "easy",
  "points": 3
}
```

**Medium Question (5-10 points):**
```json
{
  "question_text": "Hansı amillər torpaq sağlamlığına təsir edir?",
  "question_type": "multiple_choice", 
  "difficulty": "medium",
  "points": 8
}
```

**Hard Question (10-20 points):**
```json
{
  "question_text": "İqlim dəyişikliyi kənd təsərrüfatına necə təsir edir?",
  "question_type": "text",
  "difficulty": "hard", 
  "points": 15
}
```

---

## 🧪 **Complete Testing Workflow**

### **Step 1: Setup Authentication**
```bash
POST /api/v1/auth/generate-test-token
{
  "email": "admin@example.com",
  "user_type": "admin"
}
```

### **Step 2: Test Category System**
```bash
GET /api/v1/categories/dropdown
POST /api/v1/categories
PUT /api/v1/categories/1
DELETE /api/v1/categories/1
```

### **Step 3: Test Form Data (Enhanced)**
```bash
GET /api/v1/exams/form-data
```
**Verify response includes:**
- Categories for independent exams
- Trainings for training-based exams
- `supports_independent_exams: true`

### **Step 4: Test Both Exam Types**

#### **Training-Based Exam:**
```bash
POST /api/v1/exams
{
  "training_id": 1,
  "title": "Training Exam",
  "questions": [...]
}
```

#### **Independent Exam:**
```bash
POST /api/v1/exams
{
  "category": "Custom Category",
  "title": "Independent Exam", 
  "questions": [...]
}
```

### **Step 5: Test Enhanced Exam Listing**
```bash
GET /api/v1/exams
```
**Verify response includes:**
- `exam_type`: "training_based" or "independent"
- `display_category`: Proper category display
- `training_title`: Only for training-based exams

### **Step 6: Test Complete Student Workflow**
```bash
POST /api/v1/exams/4/register
POST /api/v1/exams/4/start
GET /api/v1/exams/4/take     # Check timing info
POST /api/v1/exams/4/submit  # Submit answers
```

### **Step 7: Test Timing Scenarios**
- **Normal submission** - Within time limit
- **Late submission** - After time expires
- **Session checking** - Real-time timing updates

---

## 📊 **Expected Response Formats**

### **Enhanced Exam Listing:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Training-Based Exam",
      "exam_type": "training_based",
      "display_category": "Bitki Becerilmesi",
      "training_title": "Training Name",
      "training": {
        "title": "Training Name"
      }
    },
    {
      "id": 2,
      "title": "Independent Exam",
      "exam_type": "independent", 
      "display_category": "Ümumi Kənd Təsərrüfatı",
      "training_title": null,
      "training": null
    }
  ]
}
```

### **Enhanced Form Data:**
```json
{
  "categories": [
    {"id": 1, "name": "Bitki Becerilmesi"},
    {"id": 2, "name": "Torpaq Sağlamlığı"}
  ],
  "trainings": [...],
  "trainers": [...],
  "supports_independent_exams": true
}
```

### **Exam Creation Response:**
```json
{
  "message": "Exam created successfully with questions",
  "exam": {
    "id": 1,
    "exam_type": "independent",
    "category": "Custom Category",
    "questions": [
      {
        "difficulty": "medium",
        "points": 8
      }
    ]
  },
  "summary": {
    "total_questions": 3,
    "total_points": 23,
    "question_types": {
      "single_choice": 1,
      "multiple_choice": 1, 
      "text": 1
    }
  }
}
```

### **Timing Information:**
```json
{
  "time_info": {
    "time_elapsed_minutes": 25,
    "time_remaining_minutes": 35,
    "time_limit_minutes": 60,
    "time_exceeded": false,
    "started_at": "2024-08-20T10:00:00Z"
  }
}
```

---

## 🎉 **Updated Collection Features**

### **✅ New Sections Added:**
1. **"Category Management"** - Full CRUD operations
2. **"Exam Timing & Duration Testing"** - Timing system tests
3. **Enhanced exam creation examples** - Both exam types
4. **Difficulty level examples** - All difficulty levels

### **✅ Enhanced Examples:**
- **Azerbaijani language content** - Realistic test data
- **Difficulty levels** - Easy/Medium/Hard examples
- **Independent exams** - No training dependency
- **Enhanced timing** - Real-time timing tests
- **Mixed answer types** - All question types together

### **✅ Updated Descriptions:**
- Clear purpose for each endpoint
- Azerbaijani examples where appropriate
- Enhanced error scenarios
- Timing validation explanations

The Postman collection now fully reflects all the recent enhancements and provides comprehensive testing for the complete exam management system!
