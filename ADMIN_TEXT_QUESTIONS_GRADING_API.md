# Admin Açıq Sualları Qiymətləndirmə API Sənədləşməsi

## 📋 Ümumi Məlumat

Admin üçün açıq sualları (text questions) qiymətləndirmək üçün iki endpoint mövcuddur:
1. **List Endpoint** - Bütün gözləyən imtahanları siyahı halında göstərir
2. **Detailed Endpoint** - Xüsusi bir imtahanın tam detallarını göstərir

---

## 🔗 Endpoint-lər

### 1. Gözləyən İmtahanların Siyahısı

**Endpoint:** `GET /api/v1/admin/exams/pending-reviews`

**Açıqlama:** Bütün gözləyən (pending_review) imtahanları göstərir.

**Response Nümunəsi:**
```json
{
  "message": "Pending review exams retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "user": {
          "id": 5,
          "first_name": "Umud",
          "last_name": "Abbasli",
          "email": "umud@example.com"
        },
        "exam": {
          "id": 10,
          "title": "Bitki ekini imtahani"
        },
        "training": {
          "id": 3,
          "title": "Bitki ekini"
        },
        "trainer": {
          "id": 2,
          "first_name": "Vusal",
          "last_name": "Eyvazov",
          "full_name": "Vusal Eyvazov"
        },
        "correct_answers_count": 7,
        "total_questions": 10,
        "correct_answers_text": "7/10",
        "current_score": 70,
        "passing_score": 80,
        "text_questions_count": 2,
        "started_at": "2024-11-05 10:00:00",
        "finished_at": "2024-11-05 11:00:00",
        "created_at": "2024-11-05 10:00:00"
      }
    ],
    "per_page": 20,
    "total": 15
  }
}
```

**Response Sahələri:**
- `id` - Registration ID
- `user` - İstifadəçi məlumatları (id, first_name, last_name, email)
- `exam` - İmtahan məlumatları (id, title)
- `training` - Training məlumatları (id, title) - nullable
- `trainer` - Trainer məlumatları (id, first_name, last_name, full_name) - nullable
- `correct_answers_count` - Düzgün cavabların sayı (yalnız avtomatik qiymətləndirilən)
- `total_questions` - Ümumi sualların sayı
- `correct_answers_text` - "7/10" formatında
- `current_score` - Hazırkı bal (avtomatik qiymətləndirilən sualların balı)
- `passing_score` - Minimum keçid balı
- `text_questions_count` - Açıq sualların sayı
- `started_at` - İmtahanın başlama vaxtı
- `finished_at` - İmtahanın bitmə vaxtı

---

### 2. Detallı İmtahan Məlumatları

**Endpoint:** `GET /api/v1/admin/exams/{registrationId}/for-grading`

**Açıqlama:** Xüsusi bir imtahanın tam detallarını və açıq sualları göstərir.

**Response Nümunəsi:**
```json
{
  "message": "Exam data retrieved successfully",
  "data": {
    "registration_id": 1,
    "user": {
      "id": 5,
      "first_name": "Umud",
      "last_name": "Abbasli",
      "email": "umud@example.com",
      "full_name": "Umud Abbasli"
    },
    "exam": {
      "id": 10,
      "title": "Bitki ekini imtahani"
    },
    "training": {
      "id": 3,
      "title": "Bitki ekini"
    },
    "trainer": {
      "id": 2,
      "first_name": "Vusal",
      "last_name": "Eyvazov",
      "full_name": "Vusal Eyvazov"
    },
    "correct_answers_count": 7,
    "total_questions": 10,
    "correct_answers_text": "7/10",
    "current_score": 70,
    "passing_score": 80,
    "text_questions_count": 2,
    "started_at": "2024-11-05 10:00:00",
    "finished_at": "2024-11-05 11:00:00",
    "attempt_number": 1,
    "text_questions": [
      {
        "id": 123,
        "question_id": 45,
        "question_text": {
          "az": "Bitkileri ekerken nəyə diqqət etmək lazımdır?",
          "en": "What should be considered when planting?",
          "ru": "На что нужно обратить внимание при посадке?"
        },
        "answer_text": "Bitkileri ekerken torpağın keyfiyyətinə, suyun mövcudluğuna və işığa diqqət etmək lazımdır.",
        "answered_at": "2024-11-05 11:00:00",
        "points": 5
      },
      {
        "id": 124,
        "question_id": 46,
        "question_text": {
          "az": "Kompostun əsas məqsədi nədir?",
          "en": "What is the main purpose of compost?",
          "ru": "Какова основная цель компоста?"
        },
        "answer_text": "Kompost üzvi maddələri qida maddələrinə çevirmək üçün istifadə olunur.",
        "answered_at": "2024-11-05 11:00:00",
        "points": 5
      }
    ]
  }
}
```

**Response Sahələri:**
- Yuxarıdakı bütün sahələr (list endpoint-dəki kimi)
- `text_questions` - Açıq sualların siyahısı:
  - `id` - ExamUserAnswer ID
  - `question_id` - Question ID
  - `question_text` - Sual mətni (multilang)
  - `answer_text` - İstifadəçinin cavabı
  - `answered_at` - Cavab verilmə vaxtı
  - `points` - Sualın balı

---

### 3. Açıq Sualları Qiymətləndir

**Endpoint:** `POST /api/v1/admin/exams/{registrationId}/grade-text-questions`

**Açıqlama:** Admin açıq sualları qiymətləndirir. Sistem final balı hesablayır və istifadəçiyə bildiriş göndərir.

**Request Body:**
```json
{
  "grades": [
    {
      "answer_id": 123,
      "is_correct": true,
      "feedback": {
        "az": "Yaxşı cavab, lakin daha ətraflı ola bilərdi. Bitkileri ekerken flan flan seylere diqqət etməlisiniz.",
        "en": "Good answer, but could be more detailed. When planting, you should pay attention to such and such things.",
        "ru": "Хороший ответ, но можно было бы более подробно. При посадке следует обращать внимание на такие-то вещи."
      }
    },
    {
      "answer_id": 124,
      "is_correct": false,
      "feedback": {
        "az": "Bu cavab düzgün deyil. Kompostun əsas məqsədi...",
        "en": "This answer is not correct. The main purpose of compost is...",
        "ru": "Этот ответ неверен. Основная цель компоста..."
      }
    }
  ],
  "admin_notes": "Ümumi qeydlər burada..."
}
```

**Request Validation:**
- `grades` - Array, required
  - `grades.*.answer_id` - Integer, required
  - `grades.*.is_correct` - Boolean, required
  - `grades.*.feedback` - Object, nullable
    - `grades.*.feedback.az` - String, nullable
    - `grades.*.feedback.en` - String, nullable
    - `grades.*.feedback.ru` - String, nullable
- `admin_notes` - String, nullable

**Response Nümunəsi:**
```json
{
  "message": "Text questions graded successfully",
  "data": {
    "registration_id": 1,
    "total_correct": 8,
    "total_questions": 10,
    "correct_answers_text": "8/10",
    "final_score": 80,
    "passing_score": 80,
    "status": "passed",
    "passed": true
  }
}
```

**Response Sahələri:**
- `registration_id` - Registration ID
- `total_correct` - Ümumi düzgün cavabların sayı (avtomatik + açıq suallar)
- `total_questions` - Ümumi sualların sayı
- `correct_answers_text` - "8/10" formatında
- `final_score` - Final bal (%)
- `passing_score` - Minimum keçid balı
- `status` - Final status ("passed" və ya "failed")
- `passed` - Boolean, keçid olub-olmadığı

**Sistemin İşləməsi:**

1. **Qiymətləndirmə:**
   - Admin hər bir açıq sual üçün `is_correct` (true/false) qeyd edir
   - İstəyə görə multilang feedback yaza bilər

2. **Bal Hesablanması:**
   - Avtomatik qiymətləndirilən sualların düzgün sayı: 7
   - Açıq sualların düzgün sayı: 1 (admin düzgün deyib)
   - Ümumi düzgün: 7 + 1 = 8
   - Final bal: (8 / 10) × 100 = 80%

3. **Status Yenilənməsi:**
   - Əgər final bal >= passing_score → `status = 'passed'`
   - Əgər final bal < passing_score → `status = 'failed'`

4. **Sertifikat:**
   - Əgər keçid olsa və training-in `has_certificate = true` olsa:
     - PDF sertifikat yaradılır
     - QR kod yaradılır
     - Database-ə yazılır

5. **Email Bildirişi:**
   - Keçid olsa → `ExamPassedMail` göndərilir (sertifikat ilə)
   - Kəsil olsa → `ExamFailedMail` göndərilir

---

## 📝 Multilang Feedback

Admin feedback-i multilang formatda yaza bilər:

```json
{
  "feedback": {
    "az": "Azərbaycan dilində izah",
    "en": "Explanation in English",
    "ru": "Объяснение на русском"
  }
}
```

**Qeydlər:**
- Bütün dillər optional-dır
- Boş olan dillər nəzərə alınmır
- Əgər bütün dillər boşdursa, feedback `null` olur

---

## 🔍 Nümunə İş Axını

### 1. Admin gözləyən imtahanları görür:
```bash
GET /api/v1/admin/exams/pending-reviews
```

Response:
- User: Umud Abbasli
- İmtahan: Bitki ekini imtahani
- Training: Bitki ekini
- Trainer: Vusal Eyvazov
- Düzgün cavablar: 7/10
- Keçid balı: 70%
- Minimum keçid: 80%
- Açıq suallar: 2

### 2. Admin detallı məlumatları görür:
```bash
GET /api/v1/admin/exams/1/for-grading
```

Response:
- Yuxarıdakı bütün məlumatlar
- Açıq sualların siyahısı (sual mətni + user cavabı)

### 3. Admin qiymətləndirir:
```bash
POST /api/v1/admin/exams/1/grade-text-questions
{
  "grades": [
    {
      "answer_id": 123,
      "is_correct": true,
      "feedback": {
        "az": "Yaxşı cavab!"
      }
    },
    {
      "answer_id": 124,
      "is_correct": false,
      "feedback": {
        "az": "Bu cavab düzgün deyil."
      }
    }
  ]
}
```

Response:
- Final bal: 80%
- Status: passed
- İstifadəçiyə email göndərildi
- Sertifikat yaradıldı

---

## ✅ Sistem Hazırdır!

Bütün endpoint-lər hazırdır və multilang feedback dəstəklənir.

