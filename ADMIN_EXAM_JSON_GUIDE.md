# Admin Panel - İmtahan İdarəetməsi JSON Nümunələri

Bu sənəd frontend developer üçün imtahan yaratma, redaktə etmə və görüntüləmə üçün lazım olan JSON formatlarını izah edir.

---

## 📋 1. İmtahan Yaratma (Create Exam)

### Endpoint
```
POST /api/v1/exams
```

### Request Body (Sadə - Yalnız Azərbaycan dili)

```json
{
  "training_id": 1,
  "title": "Bitki Becerilmesi İmtahanı",
  "description": "Bu imtahan bitki becerilmesi mövzusunda bilikləri yoxlayır",
  "passing_score": 70,
  "duration_minutes": 60,
  "start_date": "2025-01-20",
  "end_date": "2025-02-20",
  "max_attempts": 3,
  "exam_question_count": 10,
  "shuffle_questions": true,
  "shuffle_choices": true,
  "show_result_immediately": true,
  "show_correct_answers": true,
  "show_explanations": true,
  "auto_submit": false,
  "questions": [
    {
      "question_text": "Kompost nədir?",
      "question_type": "single_choice",
      "difficulty": "medium",
      "points": 5,
      "sequence": 1,
      "explanation": "Kompost üzvi tullantıların təbii parçalanması prosesidir",
      "is_required": true,
      "choices": [
        {
          "choice_text": "Üzvi tullantıların parçalanması",
          "is_correct": true,
          "explanation": "Düzgün cavab - kompost təbii prosesdir"
        },
        {
          "choice_text": "Kimyəvi gübrə",
          "is_correct": false
        },
        {
          "choice_text": "Mineral maddə",
          "is_correct": false
        }
      ]
    },
    {
      "question_text": "Torpaq sağlamlığına hansı amillər təsir edir?",
      "question_type": "multiple_choice",
      "difficulty": "hard",
      "points": 10,
      "sequence": 2,
      "is_required": true,
      "choices": [
        {
          "choice_text": "pH səviyyəsi",
          "is_correct": true,
          "explanation": "pH səviyyəsi torpaq sağlamlığının əsas göstəricisidir"
        },
        {
          "choice_text": "Üzvi maddə miqdarı",
          "is_correct": true
        },
        {
          "choice_text": "Suvarma tezliyi",
          "is_correct": false
        },
        {
          "choice_text": "Yağış miqdarı",
          "is_correct": false
        }
      ]
    },
    {
      "question_text": "Kompost təbii gübrədir.",
      "question_type": "true_false",
      "difficulty": "easy",
      "points": 3,
      "sequence": 3,
      "is_required": true,
      "choices": [
        {
          "choice_text": "Doğru",
          "is_correct": true,
          "explanation": "Bəli, kompost təbii üzvi gübrədir"
        },
        {
          "choice_text": "Yanlış",
          "is_correct": false
        }
      ]
    },
    {
      "question_text": "Kompost hazırlanmasının mərhələlərini izah edin.",
      "question_type": "text",
      "difficulty": "hard",
      "points": 15,
      "sequence": 4,
      "explanation": "Kompost hazırlamaq üçün üzvi tullantıları bir yerə yığmaq, nəmlik səviyyəsini idarə etmək və düzənli qarışdırmaq lazımdır",
      "is_required": true
    }
  ]
}
```

### Request Body (Çoxdilli - Multilang)

```json
{
  "training_id": 1,
  "title": {
    "az": "Bitki Becerilmesi İmtahanı",
    "en": "Plant Cultivation Exam",
    "ru": "Экзамен по выращиванию растений"
  },
  "description": {
    "az": "Bu imtahan bitki becerilmesi mövzusunda bilikləri yoxlayır",
    "en": "This exam tests knowledge about plant cultivation",
    "ru": "Этот экзамен проверяет знания по выращиванию растений"
  },
  "passing_score": 70,
  "duration_minutes": 60,
  "start_date": "2025-01-20",
  "end_date": "2025-02-20",
  "max_attempts": 3,
  "exam_question_count": 10,
  "shuffle_questions": true,
  "shuffle_choices": true,
  "show_result_immediately": true,
  "show_correct_answers": true,
  "show_explanations": true,
  "auto_submit": false,
  "questions": [
    {
      "question_text": {
        "az": "Kompost nədir?",
        "en": "What is compost?",
        "ru": "Что такое компост?"
      },
      "question_type": "single_choice",
      "difficulty": "medium",
      "points": 5,
      "sequence": 1,
      "explanation": {
        "az": "Kompost üzvi tullantıların təbii parçalanması prosesidir",
        "en": "Compost is the natural decomposition process of organic waste",
        "ru": "Компост - это естественный процесс разложения органических отходов"
      },
      "is_required": true,
      "choices": [
        {
          "choice_text": {
            "az": "Üzvi tullantıların parçalanması",
            "en": "Decomposition of organic waste",
            "ru": "Разложение органических отходов"
          },
          "is_correct": true,
          "explanation": {
            "az": "Düzgün cavab - kompost təbii prosesdir",
            "en": "Correct answer - compost is a natural process",
            "ru": "Правильный ответ - компост это естественный процесс"
          }
        },
        {
          "choice_text": {
            "az": "Kimyəvi gübrə",
            "en": "Chemical fertilizer",
            "ru": "Химическое удобрение"
          },
          "is_correct": false
        }
      ]
    },
    {
      "question_text": {
        "az": "Kompost hazırlanmasının mərhələlərini izah edin.",
        "en": "Explain the stages of compost preparation.",
        "ru": "Объясните этапы приготовления компоста."
      },
      "question_type": "text",
      "difficulty": "hard",
      "points": 15,
      "sequence": 4,
      "explanation": {
        "az": "Kompost hazırlamaq üçün üzvi tullantıları bir yerə yığmaq, nəmlik səviyyəsini idarə etmək və düzənli qarışdırmaq lazımdır",
        "en": "To prepare compost, you need to collect organic waste, manage moisture levels, and mix regularly",
        "ru": "Для приготовления компоста необходимо собрать органические отходы, контролировать уровень влажности и регулярно перемешивать"
      },
      "is_required": true
    }
  ]
}
```

### Response (Uğurlu - 201 Created)

```json
{
  "id": 1,
  "training_id": 1,
  "title": "Bitki Becerilmesi İmtahanı",
  "description": "Bu imtahan bitki becerilmesi mövzusunda bilikləri yoxlayır",
  "passing_score": 70,
  "duration_minutes": 60,
  "start_date": "2025-01-20",
  "end_date": "2025-02-20",
  "max_attempts": 3,
  "exam_question_count": 10,
  "shuffle_questions": true,
  "shuffle_choices": true,
  "show_result_immediately": true,
  "show_correct_answers": true,
  "show_explanations": true,
  "auto_submit": false,
  "status": "published",
  "created_at": "2025-01-15T10:30:00.000000Z",
  "updated_at": "2025-01-15T10:30:00.000000Z",
  "questions": [
    {
      "id": 1,
      "exam_id": 1,
      "question_text": "Kompost nədir?",
      "question_type": "single_choice",
      "difficulty": "medium",
      "points": 5,
      "sequence": 1,
      "explanation": "Kompost üzvi tullantıların təbii parçalanması prosesidir",
      "is_required": true,
      "choices": [
        {
          "id": 1,
          "question_id": 1,
          "choice_text": "Üzvi tullantıların parçalanması",
          "is_correct": true,
          "explanation": "Düzgün cavab - kompost təbii prosesdir"
        },
        {
          "id": 2,
          "question_id": 1,
          "choice_text": "Kimyəvi gübrə",
          "is_correct": false
        }
      ]
    }
  ]
}
```

---

## ✏️ 2. İmtahan Redaktə Etmə (Update Exam)

### Endpoint
```
PUT /api/v1/exams/{id}
```

### Request Body (Yalnız Dəyişdiriləcək Sahələr)

**Nümunə 1: Yalnız Əsas Məlumatları Yeniləmək**

```json
{
  "title": "Yenilənmiş İmtahan Başlığı",
  "description": "Yenilənmiş təsvir",
  "passing_score": 75,
  "duration_minutes": 90,
  "max_attempts": 5
}
```

**Nümunə 2: Sualları Yeniləmək (Mövcud sualları dəyişdirmək və yeni əlavə etmək)**

```json
{
  "questions": [
    {
      "id": 1,
      "question_text": "Yenilənmiş sual mətni",
      "question_type": "single_choice",
      "points": 10,
      "choices": [
        {
          "id": 1,
          "choice_text": "Yenilənmiş variant",
          "is_correct": true
        }
      ]
    },
    {
      "question_text": "Yeni sual",
      "question_type": "multiple_choice",
      "points": 5,
      "choices": [
        {
          "choice_text": "Yeni variant 1",
          "is_correct": true
        },
        {
          "choice_text": "Yeni variant 2",
          "is_correct": false
        }
      ]
    }
  ]
}
```

**Qeyd:** 
- Əgər sualda `id` varsa → mövcud sual yenilənir
- Əgər sualda `id` yoxdursa → yeni sual yaradılır
- Request-də göndərilməyən mövcud suallar silinmir (yalnız yenilənənlər dəyişir)

### Response (Uğurlu - 200 OK)

```json
{
  "id": 1,
  "title": "Yenilənmiş İmtahan Başlığı",
  "description": "Yenilənmiş təsvir",
  "passing_score": 75,
  "duration_minutes": 90,
  "updated_at": "2025-01-15T11:00:00.000000Z",
  "questions": [
    {
      "id": 1,
      "question_text": "Yenilənmiş sual mətni",
      "question_type": "single_choice",
      "points": 10
    }
  ]
}
```

---

## 👁️ 3. İmtahan Görüntüləmə (Get Exam)

### Endpoint
```
GET /api/v1/exams/{id}
```

### Response (Sadə - Default dil: az)

```json
{
  "id": 1,
  "training_id": 1,
  "training": {
    "id": 1,
    "title": "Bitki Becerilmesi Təlimi",
    "category": "Bitki Becerilmesi"
  },
  "title": "Bitki Becerilmesi İmtahanı",
  "description": "Bu imtahan bitki becerilmesi mövzusunda bilikləri yoxlayır",
  "passing_score": 70,
  "duration_minutes": 60,
  "start_date": "2025-01-20",
  "end_date": "2025-02-20",
  "max_attempts": 3,
  "exam_question_count": 10,
  "shuffle_questions": true,
  "shuffle_choices": true,
  "show_result_immediately": true,
  "show_correct_answers": true,
  "show_explanations": true,
  "auto_submit": false,
  "status": "published",
  "created_at": "2025-01-15T10:30:00.000000Z",
  "updated_at": "2025-01-15T10:30:00.000000Z",
  "questions": [
    {
      "id": 1,
      "exam_id": 1,
      "question_text": "Kompost nədir?",
      "question_type": "single_choice",
      "difficulty": "medium",
      "points": 5,
      "sequence": 1,
      "explanation": "Kompost üzvi tullantıların təbii parçalanması prosesidir",
      "is_required": true,
      "question_media": null,
      "metadata": null,
      "created_at": "2025-01-15T10:30:00.000000Z",
      "updated_at": "2025-01-15T10:30:00.000000Z",
      "choices": [
        {
          "id": 1,
          "question_id": 1,
          "choice_text": "Üzvi tullantıların parçalanması",
          "is_correct": true,
          "points": 0,
          "explanation": "Düzgün cavab - kompost təbii prosesdir",
          "choice_media": null,
          "metadata": null,
          "created_at": "2025-01-15T10:30:00.000000Z",
          "updated_at": "2025-01-15T10:30:00.000000Z"
        },
        {
          "id": 2,
          "question_id": 1,
          "choice_text": "Kimyəvi gübrə",
          "is_correct": false,
          "points": 0,
          "explanation": null,
          "choice_media": null,
          "metadata": null,
          "created_at": "2025-01-15T10:30:00.000000Z",
          "updated_at": "2025-01-15T10:30:00.000000Z"
        }
      ]
    },
    {
      "id": 2,
      "exam_id": 1,
      "question_text": "Torpaq sağlamlığına hansı amillər təsir edir?",
      "question_type": "multiple_choice",
      "difficulty": "hard",
      "points": 10,
      "sequence": 2,
      "explanation": null,
      "is_required": true,
      "choices": [
        {
          "id": 3,
          "question_id": 2,
          "choice_text": "pH səviyyəsi",
          "is_correct": true,
          "explanation": "pH səviyyəsi torpaq sağlamlığının əsas göstəricisidir"
        },
        {
          "id": 4,
          "question_id": 2,
          "choice_text": "Üzvi maddə miqdarı",
          "is_correct": true
        },
        {
          "id": 5,
          "question_id": 2,
          "choice_text": "Suvarma tezliyi",
          "is_correct": false
        }
      ]
    },
    {
      "id": 3,
      "exam_id": 1,
      "question_text": "Kompost təbii gübrədir.",
      "question_type": "true_false",
      "difficulty": "easy",
      "points": 3,
      "sequence": 3,
      "is_required": true,
      "choices": [
        {
          "id": 6,
          "question_id": 3,
          "choice_text": "Doğru",
          "is_correct": true,
          "explanation": "Bəli, kompost təbii üzvi gübrədir"
        },
        {
          "id": 7,
          "question_id": 3,
          "choice_text": "Yanlış",
          "is_correct": false
        }
      ]
    },
    {
      "id": 4,
      "exam_id": 1,
      "question_text": "Kompost hazırlanmasının mərhələlərini izah edin.",
      "question_type": "text",
      "difficulty": "hard",
      "points": 15,
      "sequence": 4,
      "explanation": "Kompost hazırlamaq üçün üzvi tullantıları bir yerə yığmaq, nəmlik səviyyəsini idarə etmək və düzənli qarışdırmaq lazımdır",
      "is_required": true,
      "choices": []
    }
  ],
  "statistics": {
    "total_registrations": 25,
    "total_completed": 18,
    "total_passed": 15,
    "total_failed": 3,
    "average_score": 78.5
  }
}
```

### Response (Çoxdilli - ?lang=en parametri ilə)

```json
{
  "id": 1,
  "title": "Plant Cultivation Exam",
  "description": "This exam tests knowledge about plant cultivation",
  "questions": [
    {
      "id": 1,
      "question_text": "What is compost?",
      "explanation": "Compost is the natural decomposition process of organic waste",
      "choices": [
        {
          "id": 1,
          "choice_text": "Decomposition of organic waste",
          "explanation": "Correct answer - compost is a natural process"
        }
      ]
    }
  ]
}
```

---

## 🔄 4. İmtahan Siyahısı (List Exams)

### Endpoint
```
GET /api/v1/exams?page=1&per_page=20&search=&status=&category=
```

### Response

```json
{
  "data": [
    {
      "id": 1,
      "title": "Bitki Becerilmesi İmtahanı",
      "description": "Bu imtahan bitki becerilmesi mövzusunda bilikləri yoxlayır",
      "category": "Bitki Becerilmesi",
      "passing_score": 70,
      "duration_minutes": 60,
      "start_date": "2025-01-20",
      "end_date": "2025-02-20",
      "status": "published",
      "total_questions": 10,
      "total_registrations": 25,
      "created_at": "2025-01-15T10:30:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 1,
    "last_page": 1,
    "from": 1,
    "to": 1
  }
}
```

---

## 📝 5. Form Data (Dropdown-lar üçün)

### Endpoint
```
GET /api/v1/exams/form-data
```

### Response

```json
{
  "categories": [
    {
      "id": 1,
      "name": "Bitki Becerilmesi"
    },
    {
      "id": 2,
      "name": "Torpaq Sağlamlığı"
    }
  ],
  "trainings": [
    {
      "id": 1,
      "title": "Bitki Becerilmesi Əsasları",
      "category": "Bitki Becerilmesi",
      "trainer_id": 2,
      "trainer_name": "Təlimçi Adı"
    }
  ],
  "trainers": [
    {
      "id": 2,
      "first_name": "Təlimçi",
      "last_name": "Adı",
      "is_current_user": false
    }
  ],
  "current_user": {
    "id": 1,
    "user_type": "admin",
    "first_name": "Admin",
    "last_name": "User"
  },
  "supports_independent_exams": true
}
```

---

## 📌 Əsas Qeydlər

### 1. Çoxdilli Sistem
- **Yalnız Azərbaycan dili:** Sadə string göndərin → `"title": "Başlıq"`
- **Çoxdilli:** JSON obyekt göndərin → `"title": {"az": "Başlıq", "en": "Title", "ru": "Заголовок"}`
- **Default dil:** `az` (Azərbaycan dili)
- **Response-da:** Həmişə cari dilin tərcüməsi göstərilir (request-də `?lang=en` varsa English)

### 2. Sual Tipləri
- **single_choice** → Radio button, 1 düzgün cavab
- **multiple_choice** → Checkbox, bir neçə düzgün cavab ola bilər
- **true_false** → Radio button, Doğru/Yanlış
- **text** → Textarea, variant yoxdur

### 3. Çətinlik Səviyyələri
- `easy` - Asan
- `medium` - Orta
- `hard` - Çətin

### 4. Validasiya
- `passing_score`: 0-100 arası
- `duration_minutes`: 1-480 arası (1 dəqiqə - 8 saat)
- `max_attempts`: 1-10 arası
- `exam_question_count`: Mütləq olmalıdır və ümumi sual sayından çox ola bilməz
- Choice tipli suallarda ən azı 1 düzgün cavab olmalıdır

### 5. Müstəqil İmtahan (Training-dən asılı olmayan)
- `training_id`: `null` göndərin
- `category`: Mütləq göndərin (string)
- Məsələn: `{"training_id": null, "category": "Ümumi Bilik"}`

### 6. Media Fayllar (İstəyə bağlı)
```json
{
  "question_media": [
    {
      "type": "image",
      "url": "https://example.com/image.jpg",
      "title": "Şəkil başlığı",
      "description": "Şəkil təsviri"
    }
  ],
  "choices": [
    {
      "choice_media": [
        {
          "type": "image",
          "url": "https://example.com/choice-image.jpg"
        }
      ]
    }
  ]
}
```

Media tipləri: `image`, `video`, `audio`, `document`

---

## ⚠️ Xəta Cavabları

### 400 Bad Request - Validasiya Xətası
```json
{
  "message": "Validation failed",
  "errors": {
    "title": ["The title field is required."],
    "passing_score": ["The passing score must be between 0 and 100."],
    "questions.0.choices": ["At least one choice must be marked as correct."]
  }
}
```

### 404 Not Found
```json
{
  "message": "Exam not found"
}
```

### 422 Unprocessable Entity
```json
{
  "message": "Validation failed",
  "errors": {
    "exam_question_count": ["İmtahanda göstəriləcək sual sayı (15) ümumi sual sayından (10) çox ola bilməz"]
  }
}
```

---

## 🎯 Nümunə İş Axını (Workflow)

### İmtahan Yaratma:
1. **Form Data alın** → `GET /api/v1/exams/form-data` (dropdown-lar üçün)
2. **Formu doldurun** → Sualları əlavə edin
3. **Göndərin** → `POST /api/v1/exams` (tam JSON ilə)
4. **Response alın** → Yaranmış imtahan məlumatları

### İmtahan Redaktəsi:
1. **İmtahanı alın** → `GET /api/v1/exams/{id}`
2. **Dəyişikliklər edin** → Yalnız dəyişdiriləcək sahələr
3. **Göndərin** → `PUT /api/v1/exams/{id}`
4. **Response alın** → Yenilənmiş məlumatlar

### İmtahan Görüntüləmə:
1. **Siyahı alın** → `GET /api/v1/exams` (filtrlərlə)
2. **Detallı görüntülə** → `GET /api/v1/exams/{id}` (tam məlumatlar)

