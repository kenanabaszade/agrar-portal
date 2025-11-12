# Training, Module və Lesson Multilang API Qaydası

Frontend developer üçün Training, Module və Lesson-ların yaradılması və yenilənməsi üçün multilang API istifadə qaydası.

## 🌍 Dəstəklənən Dillər

Sistem 3 dillə işləyir:
- **az** (Azərbaycan dili) - Default dil, mütləq lazımdır
- **en** (İngilis dili) - İstəyə görə
- **ru** (Rus dili) - İstəyə görə

## 📋 Multilang Field-lər

### Training
- `title` - Multilang (az mütləq lazımdır)
- `description` - Multilang (optional)

### Module
- `title` - Multilang (az mütləq lazımdır)

### Lesson
- `title` - Multilang (az mütləq lazımdır)
- `content` - Multilang (optional)
- `description` - Multilang (optional)

---

## 🎯 Request Formatları

Sistem 3 formatı qəbul edir:

### Format 1: Object Formatı (Tövsiyə Olunan)
```json
{
  "title": {
    "az": "Azərbaycan başlığı",
    "en": "English title",
    "ru": "Русское название"
  }
}
```

### Format 2: Ayrı-ayrı Field-lər
```json
{
  "title_az": "Azərbaycan başlığı",
  "title_en": "English title",
  "title_ru": "Русское название"
}
```

### Format 3: Sadə String (Yalnız Azərbaycan dili)
```json
{
  "title": "Azərbaycan başlığı"
}
```

**Qeyd:** Format 2 və Format 3 avtomatik olaraq Format 1-ə çevrilir.

---

## 📤 Training API

### 1. Training Yaratmaq

**POST** `/api/v1/trainings`

**Request Body (Object Formatı - Tövsiyə olunan):**
```json
{
  "title": {
    "az": "Kənd təsərrüfatı əsasları",
    "en": "Agriculture Basics",
    "ru": "Основы сельского хозяйства"
  },
  "description": {
    "az": "Bu təlimdə əsas kənd təsərrüfatı prinsipləri öyrədilir",
    "en": "This training teaches basic agriculture principles",
    "ru": "Это обучение учит основным принципам сельского хозяйства"
  },
  "category": "Kənd təsərrüfatı",
  "trainer_id": 1,
  "is_online": true,
  "start_date": "2025-01-01",
  "end_date": "2025-12-31"
}
```

**Request Body (Ayrı-ayrı Field-lər):**
```json
{
  "title_az": "Kənd təsərrüfatı əsasları",
  "title_en": "Agriculture Basics",
  "title_ru": "Основы сельского хозяйства",
  "description_az": "Bu təlimdə...",
  "description_en": "In this training...",
  "description_ru": "В этом обучении...",
  "category": "Kənd təsərrüfatı",
  "trainer_id": 1,
  "is_online": true
}
```

**Response (201 Created):**
```json
{
  "id": 1,
  "title": {
    "az": "Kənd təsərrüfatı əsasları",
    "en": "Agriculture Basics",
    "ru": "Основы сельского хозяйства"
  },
  "description": {
    "az": "Bu təlimdə əsas kənd təsərrüfatı prinsipləri öyrədilir",
    "en": "This training teaches basic agriculture principles",
    "ru": "Это обучение учит основным принципам сельского хозяйства"
  },
  "category": "Kənd təsərrüfatı",
  "trainer_id": 1,
  "is_online": true,
  "created_at": "2025-01-01T12:00:00.000000Z",
  "updated_at": "2025-01-01T12:00:00.000000Z"
}
```

### 2. Training Məlumatını Almaq

**GET** `/api/v1/trainings/{id}`

**Response (200 OK):**
```json
{
  "id": 1,
  "title": {
    "az": "Kənd təsərrüfatı əsasları",
    "en": "Agriculture Basics",
    "ru": "Основы сельского хозяйства"
  },
  "description": {
    "az": "Bu təlimdə...",
    "en": "In this training...",
    "ru": "В этом обучении..."
  },
  "category": "Kənd təsərrüfatı",
  "trainer_id": 1,
  "is_online": true
}
```

**Müəyyən dildə almaq üçün:**
**GET** `/api/v1/trainings/{id}?lang=en`

**Response:**
```json
{
  "id": 1,
  "title": "Agriculture Basics",
  "description": "In this training...",
  "category": "Kənd təsərrüfatı",
  "trainer_id": 1,
  "is_online": true
}
```

### 3. Training Yeniləmək

**PATCH** `/api/v1/trainings/{id}`

**Request Body (Yalnız yenilənəcək field-lər):**
```json
{
  "title": {
    "az": "Yeni başlıq",
    "en": "New Title",
    "ru": "Новое название"
  }
}
```

**Response (200 OK):**
```json
{
  "id": 1,
  "title": {
    "az": "Yeni başlıq",
    "en": "New Title",
    "ru": "Новое название"
  },
  "description": {
    "az": "Bu təlimdə...",
    "en": "In this training...",
    "ru": "В этом обучении..."
  }
}
```

---

## 📦 Module API

### 1. Module Yaratmaq

**POST** `/api/v1/trainings/{training_id}/modules`

**Request Body (Object Formatı - Tövsiyə olunan):**
```json
{
  "title": {
    "az": "Modul 1: Giriş",
    "en": "Module 1: Introduction",
    "ru": "Модуль 1: Введение"
  },
  "sequence": 1
}
```

**Request Body (Ayrı-ayrı Field-lər):**
```json
{
  "title_az": "Modul 1: Giriş",
  "title_en": "Module 1: Introduction",
  "title_ru": "Модуль 1: Введение",
  "sequence": 1
}
```

**Response (201 Created):**
```json
{
  "id": 1,
  "training_id": 1,
  "title": {
    "az": "Modul 1: Giriş",
    "en": "Module 1: Introduction",
    "ru": "Модуль 1: Введение"
  },
  "sequence": 1,
  "created_at": "2025-01-01T12:00:00.000000Z",
  "updated_at": "2025-01-01T12:00:00.000000Z",
  "lessons": []
}
```

### 2. Module Məlumatını Almaq

**GET** `/api/v1/trainings/{training_id}/modules/{module_id}`

**Response (200 OK):**
```json
{
  "id": 1,
  "training_id": 1,
  "title": {
    "az": "Modul 1: Giriş",
    "en": "Module 1: Introduction",
    "ru": "Модуль 1: Введение"
  },
  "sequence": 1,
  "lessons": [
    {
      "id": 1,
      "title": {
        "az": "Dərs 1",
        "en": "Lesson 1",
        "ru": "Урок 1"
      }
    }
  ]
}
```

**Müəyyən dildə almaq üçün:**
**GET** `/api/v1/trainings/{training_id}/modules/{module_id}?lang=en`

**Response:**
```json
{
  "id": 1,
  "training_id": 1,
  "title": "Module 1: Introduction",
  "sequence": 1,
  "lessons": [
    {
      "id": 1,
      "title": "Lesson 1"
    }
  ]
}
```

### 3. Module Yeniləmək

**PATCH** `/api/v1/trainings/{training_id}/modules/{module_id}`

**Request Body:**
```json
{
  "title": {
    "az": "Yenilənmiş Modul Başlığı",
    "en": "Updated Module Title",
    "ru": "Обновленное название модуля"
  },
  "sequence": 2
}
```

**Response (200 OK):**
```json
{
  "id": 1,
  "training_id": 1,
  "title": {
    "az": "Yenilənmiş Modul Başlığı",
    "en": "Updated Module Title",
    "ru": "Обновленное название модуля"
  },
  "sequence": 2
}
```

---

## 📚 Lesson API

### 1. Lesson Yaratmaq

**POST** `/api/v1/trainings/{training_id}/modules/{module_id}/lessons`

**Request Body (Object Formatı - Tövsiyə olunan):**
```json
{
  "title": {
    "az": "Dərs 1: Kənd təsərrüfatına giriş",
    "en": "Lesson 1: Introduction to Agriculture",
    "ru": "Урок 1: Введение в сельское хозяйство"
  },
  "content": {
    "az": "Bu dərsdə kənd təsərrüfatının əsasları öyrədilir...",
    "en": "This lesson teaches the basics of agriculture...",
    "ru": "На этом уроке изучаются основы сельского хозяйства..."
  },
  "description": {
    "az": "Dərsin qısa təsviri",
    "en": "Short lesson description",
    "ru": "Краткое описание урока"
  },
  "lesson_type": "text",
  "duration_minutes": 30,
  "sequence": 1,
  "status": "published",
  "is_required": true
}
```

**Request Body (Ayrı-ayrı Field-lər):**
```json
{
  "title_az": "Dərs 1: Kənd təsərrüfatına giriş",
  "title_en": "Lesson 1: Introduction to Agriculture",
  "title_ru": "Урок 1: Введение в сельское хозяйство",
  "content_az": "Bu dərsdə...",
  "content_en": "In this lesson...",
  "content_ru": "На этом уроке...",
  "description_az": "Dərsin qısa təsviri",
  "description_en": "Short lesson description",
  "description_ru": "Краткое описание урока",
  "lesson_type": "text",
  "duration_minutes": 30,
  "sequence": 1,
  "status": "published"
}
```

**Request Body (Qarışıq Format - title object, content ayrı field-lər):**
```json
{
  "title": {
    "az": "Dərs 1",
    "en": "Lesson 1",
    "ru": "Урок 1"
  },
  "content_az": "Azərbaycan məzmunu",
  "content_en": "English content",
  "content_ru": "Русское содержание",
  "lesson_type": "text"
}
```

**Response (201 Created):**
```json
{
  "id": 1,
  "module_id": 1,
  "title": {
    "az": "Dərs 1: Kənd təsərrüfatına giriş",
    "en": "Lesson 1: Introduction to Agriculture",
    "ru": "Урок 1: Введение в сельское хозяйство"
  },
  "content": {
    "az": "Bu dərsdə kənd təsərrüfatının əsasları öyrədilir...",
    "en": "This lesson teaches the basics of agriculture...",
    "ru": "На этом уроке изучаются основы сельского хозяйства..."
  },
  "description": {
    "az": "Dərsin qısa təsviri",
    "en": "Short lesson description",
    "ru": "Краткое описание урока"
  },
  "lesson_type": "text",
  "duration_minutes": 30,
  "sequence": 1,
  "status": "published",
  "is_required": true,
  "created_at": "2025-01-01T12:00:00.000000Z",
  "updated_at": "2025-01-01T12:00:00.000000Z",
  "module": {
    "id": 1,
    "title": {
      "az": "Modul 1: Giriş",
      "en": "Module 1: Introduction",
      "ru": "Модуль 1: Введение"
    }
  }
}
```

### 2. Lesson Məlumatını Almaq

**GET** `/api/v1/trainings/{training_id}/modules/{module_id}/lessons/{lesson_id}`

**Response (200 OK):**
```json
{
  "lesson": {
    "id": 1,
    "module_id": 1,
    "title": {
      "az": "Dərs 1: Kənd təsərrüfatına giriş",
      "en": "Lesson 1: Introduction to Agriculture",
      "ru": "Урок 1: Введение в сельское хозяйство"
    },
    "content": {
      "az": "Bu dərsdə kənd təsərrüfatının əsasları öyrədilir...",
      "en": "This lesson teaches the basics of agriculture...",
      "ru": "На этом уроке изучаются основы сельского хозяйства..."
    },
    "description": {
      "az": "Dərsin qısa təsviri",
      "en": "Short lesson description",
      "ru": "Краткое описание урока"
    },
    "lesson_type": "text",
    "duration_minutes": 30
  },
  "content": {
    "text": {
      "az": "Bu dərsdə...",
      "en": "In this lesson...",
      "ru": "На этом уроке..."
    },
    "description": {
      "az": "Dərsin qısa təsviri",
      "en": "Short lesson description",
      "ru": "Краткое описание урока"
    }
  },
  "duration": "30m"
}
```

**Müəyyən dildə almaq üçün:**
**GET** `/api/v1/trainings/{training_id}/modules/{module_id}/lessons/{lesson_id}?lang=en`

**Response:**
```json
{
  "lesson": {
    "id": 1,
    "module_id": 1,
    "title": "Lesson 1: Introduction to Agriculture",
    "content": "This lesson teaches the basics of agriculture...",
    "description": "Short lesson description",
    "lesson_type": "text",
    "duration_minutes": 30
  }
}
```

### 3. Lesson Yeniləmək

**PATCH** `/api/v1/trainings/{training_id}/modules/{module_id}/lessons/{lesson_id}`

**Request Body (Yalnız yenilənəcək field-lər):**
```json
{
  "title": {
    "az": "Yenilənmiş Dərs Başlığı",
    "en": "Updated Lesson Title",
    "ru": "Обновленное название урока"
  },
  "content": {
    "az": "Yenilənmiş məzmun...",
    "en": "Updated content...",
    "ru": "Обновленное содержание..."
  }
}
```

**Response (200 OK):**
```json
{
  "id": 1,
  "module_id": 1,
  "title": {
    "az": "Yenilənmiş Dərs Başlığı",
    "en": "Updated Lesson Title",
    "ru": "Обновленное название урока"
  },
  "content": {
    "az": "Yenilənmiş məzmun...",
    "en": "Updated content...",
    "ru": "Обновленное содержание..."
  }
}
```

---

## 🔄 Tam Training Yaratma Nümunəsi (Training + Module + Lesson)

### Addım 1: Training Yaratmaq

**POST** `/api/v1/trainings`

```json
{
  "title": {
    "az": "Kənd təsərrüfatı əsasları",
    "en": "Agriculture Basics",
    "ru": "Основы сельского хозяйства"
  },
  "description": {
    "az": "Bu təlimdə...",
    "en": "In this training...",
    "ru": "В этом обучении..."
  },
  "trainer_id": 1,
  "is_online": true
}
```

**Response:** `{ "id": 1, ... }`

### Addım 2: Module Yaratmaq

**POST** `/api/v1/trainings/1/modules`

```json
{
  "title": {
    "az": "Modul 1: Giriş",
    "en": "Module 1: Introduction",
    "ru": "Модуль 1: Введение"
  },
  "sequence": 1
}
```

**Response:** `{ "id": 1, ... }`

### Addım 3: Lesson Yaratmaq

**POST** `/api/v1/trainings/1/modules/1/lessons`

```json
{
  "title": {
    "az": "Dərs 1: Kənd təsərrüfatına giriş",
    "en": "Lesson 1: Introduction to Agriculture",
    "ru": "Урок 1: Введение в сельское хозяйство"
  },
  "content": {
    "az": "Bu dərsdə...",
    "en": "In this lesson...",
    "ru": "На этом уроке..."
  },
  "description": {
    "az": "Dərsin qısa təsviri",
    "en": "Short lesson description",
    "ru": "Краткое описание урока"
  },
  "lesson_type": "text",
  "duration_minutes": 30,
  "sequence": 1,
  "status": "published"
}
```

---

## 📖 Training + Modules + Lessons Birlikdə Almaq

**GET** `/api/v1/trainings/{id}?include_modules=true`

**Response:**
```json
{
  "id": 1,
  "title": {
    "az": "Kənd təsərrüfatı əsasları",
    "en": "Agriculture Basics",
    "ru": "Основы сельского хозяйства"
  },
  "description": {
    "az": "Bu təlimdə...",
    "en": "In this training...",
    "ru": "В этом обучении..."
  },
  "modules": [
    {
      "id": 1,
      "title": {
        "az": "Modul 1: Giriş",
        "en": "Module 1: Introduction",
        "ru": "Модуль 1: Введение"
      },
      "sequence": 1,
      "lessons": [
        {
          "id": 1,
          "title": {
            "az": "Dərs 1",
            "en": "Lesson 1",
            "ru": "Урок 1"
          },
          "content": {
            "az": "Məzmun...",
            "en": "Content...",
            "ru": "Содержание..."
          },
          "description": {
            "az": "Təsvir...",
            "en": "Description...",
            "ru": "Описание..."
          },
          "sequence": 1
        }
      ]
    }
  ]
}
```

**Müəyyən dildə almaq:**
**GET** `/api/v1/trainings/{id}?include_modules=true&lang=en`

**Response:**
```json
{
  "id": 1,
  "title": "Agriculture Basics",
  "description": "In this training...",
  "modules": [
    {
      "id": 1,
      "title": "Module 1: Introduction",
      "lessons": [
        {
          "id": 1,
          "title": "Lesson 1",
          "content": "Content...",
          "description": "Description...",
          "sequence": 1
        }
      ]
    }
  ]
}
```

---

## ⚠️ Vacib Qaydalar

### 1. **Azərbaycan dili mütləq lazımdır**
Hər multilang field üçün `az` (Azərbaycan dili) mütləq olmalıdır. `title` field-i üçün bu xüsusilə vacibdir.

❌ **Səhv:**
```json
{
  "title": {
    "en": "English Title",
    "ru": "Русское название"
  }
}
```

✅ **Düzgün:**
```json
{
  "title": {
    "az": "Azərbaycan başlığı",
    "en": "English Title",
    "ru": "Русское название"
  }
}
```

### 2. **Dil Kodları**
Yalnız `az`, `en`, `ru` dəstəklənir. Başqa dil kodları qəbul olunmur.

### 3. **Format Qarışığı**
Fərqli field-lər üçün fərqli formatlar istifadə edə bilərsiniz:

```json
{
  "title": {
    "az": "Başlıq",
    "en": "Title",
    "ru": "Название"
  },
  "description_az": "Təsvir",
  "description_en": "Description",
  "description_ru": "Описание"
}
```

### 4. **Boş Dəyərlər**
Boş string-lər (`""`) avtomatik olaraq silinir. Yalnız doldurulmuş dillər qalır.

### 5. **Response Formatı**
- `lang` parametri olmadan → Bütün dillər object formatında qaytarılır
- `lang` parametri ilə → Yalnız həmin dil string formatında qaytarılır

---

## 🎯 Praktik Nümunələr

### Nümunə 1: Sadə Training (Yalnız Azərbaycan dili)

**POST** `/api/v1/trainings`

```json
{
  "title": "Kənd təsərrüfatı əsasları",
  "trainer_id": 1,
  "is_online": true
}
```

### Nümunə 2: Çoxdilli Training (Az + En)

**POST** `/api/v1/trainings`

```json
{
  "title": {
    "az": "Kənd təsərrüfatı əsasları",
    "en": "Agriculture Basics"
  },
  "description": {
    "az": "Bu təlimdə...",
    "en": "In this training..."
  },
  "trainer_id": 1,
  "is_online": true
}
```

### Nümunə 3: Tam Çoxdilli (Az + En + Ru)

**POST** `/api/v1/trainings`

```json
{
  "title": {
    "az": "Kənd təsərrüfatı əsasları",
    "en": "Agriculture Basics",
    "ru": "Основы сельского хозяйства"
  },
  "description": {
    "az": "Bu təlimdə...",
    "en": "In this training...",
    "ru": "В этом обучении..."
  },
  "trainer_id": 1,
  "is_online": true
}
```

---

## 📝 Xülasə

1. **3 format dəstəklənir:** Object, ayrı-ayrı field-lər, sadə string
2. **Azərbaycan dili mütləq lazımdır** multilang field-lər üçün
3. **Yalnız `az`, `en`, `ru`** dəstəklənir
4. **`lang` parametri** ilə müəyyən dildə məlumat ala bilərsiniz
5. **Field-lər:** Training (title, description), Module (title), Lesson (title, content, description)

Bu qaydalara əsasən frontend-də form-ları doldurarkən istifadə edin! 🚀

