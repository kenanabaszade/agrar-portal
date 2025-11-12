# User Results API - Backend Sistemində User Nəticələri Necə Qaytarılır

## 📊 **Əsas Endpoint-lər**

### 1. **Comprehensive User Results**
**Endpoint:** `GET /api/v1/my/results?period=today|week|year|all`

**Controller:** `ProgressController::myResults()`

**Qaytarır:**
```json
{
  "period": "all",
  "key_metrics": {
    "completed_courses": 5,          // Tamamlanan kurslar
    "ongoing_courses": 3,            // Davam edən kurslar
    "certificates_earned": 4,        // Qazanılan sertifikatlar
    "webinar_participation": 12      // Vebinar iştirakı
  },
  "learning_progress": {
    "overall_progress": 65.5,        // Ümumi tərəqqi faizi
    "weekly_goal": {
      "hours": 5,
      "progress": 75.0               // Həftəlik məqsəd faizi
    },
    "monthly_goal": {
      "hours": 20,
      "progress": 60.0               // Aylıq məqsəd faizi
    },
    "total_hours": 12.5,             // Ümumi öyrənmə saatı
    "daily_streak": 7                // Günlük streak (ardıcıl günlər)
  },
  "performance_analytics": {
    "average_score": 82.5,           // Orta imtahan balı
    "learning_effectiveness": 85.0,  // Öyrənmə effektivliyi (keçid faizi)
    "knowledge_retention_rate": 90.2, // Bilik saxlama dərəcəsi
    "improvement_message": "Yaxşı nəticə. Təkmilləşdirmə üçün daha çox öyrənin."
  },
  "latest_achievements": [
    {
      "title": "Əla Nəticə",
      "description": "95+ bal topladınız",
      "icon": "star",
      "date": "2025-01-15",
      "category": "performance"
    }
  ],
  "course_progress": [
    {
      "id": 1,
      "title": "Training Title",
      "description": "Training Description",
      "progress_percentage": 65.5,
      "completed_lessons": 13,
      "total_lessons": 20,
      "last_activity": "2025-01-20 14:30",
      "status": "approved",
      "type": "offline"
    }
  ]
}
```

**Qeydlər:**
- `period` parametri: `today`, `week`, `year`, `all` (default: `all`)
- Completed courses: TrainingRegistration status='completed' VƏ YA Certificate mövcuddur
- Ongoing courses: TrainingRegistration status='approved' VƏ training completed deyil
- Video trainings: Progress varsa amma certificate yoxdursa ongoing sayılır

---

### 2. **User Statistics (Dashboard)**
**Endpoint:** `GET /api/v1/user-statistics`

**Controller:** `DashboardController::userStatistics()`

**Qaytarır:**
```json
{
  "completed_courses": {
    "count": 5,
    "this_month_change": 2,          // Bu ay tamamlanan
    "goal_percentage": 83.3          // Məqsədə çatma faizi
  },
  "ongoing_courses": {
    "count": 3,
    "average_progress": 45.5         // Orta tərəqqi faizi
  },
  "certificates_earned": {
    "count": 4,
    "new_certificates": true,        // Yeni sertifikat var
    "completion_percentage": 80.0
  },
  "total_learning_hours": {
    "hours": 12.5,
    "this_week_change": 3.2,         // Bu həftə öyrənilən saat
    "goal_percentage": 62.5          // 50 saat məqsədinə görə
  }
}
```

---

### 3. **User Progress List**
**Endpoint:** `GET /api/v1/progress`

**Controller:** `ProgressController::index()`

**Qaytarır:**
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
      "last_accessed": "2025-01-20T14:30:00Z",
      "completed_at": "2025-01-20T15:00:00Z",
      "time_spent": 1800,            // Saniyə ilə
      "notes": "Çox faydalı dərs idi"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 50,
    "total": 1
  }
}
```

---

### 4. **Training Detailed (User Progress ilə)**
**Endpoint:** `GET /api/v1/trainings/{id}/detailed?lang=az`

**Controller:** `TrainingController::detailed()`

**Authenticated user üçün qaytarır:**
```json
{
  "id": 1,
  "title": "Training Title",
  "description": "Training Description",
  // ... training məlumatları
  "user_registration": {
    "is_registered": true,
    "status": "approved",
    "registration_date": "2025-01-10",
    "certificate_id": 5,
    "can_complete": true
  },
  "user_progress": {
    "last_accessed_lesson": {...},
    "next_lesson": {...},
    "is_completed": false,
    "completion_date": null,
    "certificate_id": null,
    "progress_summary": {
      "completed_lessons": 13,
      "total_lessons": 20,
      "progress_percentage": 65.5
    }
  }
}
```

---

### 5. **Training Completion Status**
**Endpoint:** `GET /api/v1/trainings/{training}/completion-status`

**Controller:** `TrainingController::getTrainingCompletionStatus()`

**Qaytarır:**
```json
{
  "training_id": 1,
  "training_title": "Training Title",
  "is_completed": false,
  "can_complete": true,
  "registration_status": "approved",
  "certificate_id": null,
  "progress": {
    "completed_required_lessons": 8,
    "total_required_lessons": 10,
    "completed_all_lessons": 13,
    "total_lessons": 20,
    "completion_percentage": 65.0
  }
}
```

---

### 6. **Exam Results (User History)**
**Endpoint:** `GET /api/v1/exams/{exam}/result`

**Controller:** `ExamController::getUserExamResult()`

**Qaytarır:**
```json
{
  "exam": {
    "id": 1,
    "title": "Exam Title",
    "passing_score": 70
  },
  "user_results": [
    {
      "registration_id": 1,
      "status": "passed",
      "score": 85.5,
      "started_at": "2025-01-20T10:00:00Z",
      "finished_at": "2025-01-20T10:45:00Z",
      "attempt_number": 1,
      "passed": true,
      "certificate_id": 5
    }
  ],
  "summary": {
    "total_attempts": 3,
    "passed_attempts": 2,
    "failed_attempts": 1,
    "average_score": 78.3,
    "best_score": 85.5,
    "latest_score": 82.0
  }
}
```

---

## 🔍 **Data Source-ları**

### Training Progress
- **Table:** `user_training_progress`
- **Fields:** `user_id`, `training_id`, `module_id`, `lesson_id`, `status`, `completed_at`, `time_spent`
- **Status values:** `not_started`, `in_progress`, `completed`

### Training Completion
- **Table:** `training_registrations`
- **Completion criteria:**
  - Status = `completed` VƏ YA
  - Certificate mövcuddur (video trainings üçün)

### Exam Results
- **Table:** `exam_registrations`
- **Fields:** `user_id`, `exam_id`, `status`, `score`, `started_at`, `finished_at`
- **Status values:** `registered`, `in_progress`, `passed`, `failed`, `completed`

### Certificates
- **Table:** `certificates`
- **Fields:** `user_id`, `related_training_id`, `related_exam_id`, `status`, `certificate_number`
- **Filter:** Yalnız `status='active'` və `expiry_date >= today` (və ya null)

---

## 📈 **Hesablamalar**

### Overall Progress
```
(Completed Lessons / Total Lessons) * 100
```

### Average Progress (Ongoing Courses)
```
Əvvəlcə hər training üçün: (Completed Lessons / Total Lessons) * 100
Sonra bütün trainings üçün orta: Sum(progress) / Count(trainings)
```

### Learning Hours
```
SUM(time_spent in seconds) / 3600 = Hours
```

### Daily Streak
```
Son 30 gün ərzində ardıcıl günlərin sayı (updated_at tarixə görə)
```

### Knowledge Retention Rate
```
İlk yarıdakı orta bal / Son yarıdakı orta bal * 100
```

### Learning Effectiveness
```
(Passed Exams / Total Exams) * 100
```

---

## 🔐 **User Filtering**

Bütün endpoint-lərdə:
- User yalnız öz məlumatlarını görür
- `auth()->user()->id` və ya `$request->user()->id` istifadə olunur
- ProgressController metodlarında user check var:
  ```php
  if ($progress->user_id !== auth()->id()) {
      return response()->json(['message' => 'Unauthorized'], 403);
  }
  ```

---

## 📝 **Qeydlər**

1. **Video Trainings:**
   - Registration tələb olunmur
   - Progress varsa training "ongoing" sayılır
   - Certificate varsa "completed" sayılır

2. **Non-Video Trainings:**
   - Registration tələb olunur (status='approved')
   - Registration status='completed' olmalıdır
   - Certificate registration-a bağlıdır

3. **Time Spent:**
   - Saniyə ilə saxlanılır
   - Response-da saat və ya dəqiqəyə çevrilir

4. **Period Filter:**
   - `today`: Bu günün start-dan indiyə qədər
   - `week`: Bu həftənin start-dan indiyə qədər
   - `year`: Bu ilin start-dan indiyə qədər
   - `all`: Bütün məlumatlar (date filter yoxdur)


