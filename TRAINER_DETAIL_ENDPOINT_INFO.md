# Trainer Detalları - GET Endpoint

## 🔗 Endpoint URL

```
GET /api/v1/trainers/{id}
```

**Status:** ✅ Public (Authentication lazım deyil)

**Parametr:** `{id}` - Trainer-in ID-si (integer)

---

## 📤 Response Strukturu

```json
{
  "id": 5,
  "first_name": "Əli",
  "last_name": "Məmmədov",
  "email": "ali@example.com",
  "profile_photo_url": "https://api.aqrar.az/storage/profile_photos/photo.jpg",
  "trainer_category": {
    "az": "Kənd təsərrüfatı",
    "en": "Agriculture"
  },
  "trainer_category_string": "Kənd təsərrüfatı",
  "trainer_description": {
    "az": "15 ildir kənd təsərrüfatı sahəsində çalışır. Buğda və taxıl bitkilərinin becərilməsi üzrə mütəxəssisdir.",
    "en": "Working in agriculture for 15 years. Specialist in wheat and cereal crop cultivation."
  },
  "experience_years": 3,
  "experience_months": 5,
  "experience_formatted": "3 il 5 ay",
  "specializations": [
    {
      "az": "Buğda becərməsi",
      "en": "Wheat cultivation"
    },
    {
      "az": "Taxıl bitkiləri",
      "en": "Cereal crops"
    },
    {
      "az": "Torpaq idarəetməsi",
      "en": "Soil management"
    }
  ],
  "specializations_strings": [
    "Buğda becərməsi",
    "Taxıl bitkiləri",
    "Torpaq idarəetməsi"
  ],
  "specializations_string": "Buğda becərməsi, Taxıl bitkiləri, Torpaq idarəetməsi",
  "qualifications": [
    {
      "az": "Kənd Təsərrüfatı Bakalavr",
      "en": "BSc Agriculture"
    },
    {
      "az": "Aqronomiya Magistr",
      "en": "MSc Agronomy"
    }
  ],
  "qualifications_strings": [
    "Kənd Təsərrüfatı Bakalavr",
    "Aqronomiya Magistr"
  ],
  "qualifications_string": "Kənd Təsərrüfatı Bakalavr, Aqronomiya Magistr",
  "created_at": "2023-01-15T10:00:00.000000Z",
  "trainings": [
    {
      "id": 10,
      "title": {
        "az": "Kənd təsərrüfatı əsasları",
        "en": "Agriculture Basics"
      },
      "description": {
        "az": "Bu training-də kənd təsərrüfatının əsasları öyrədilir...",
        "en": "This training covers the basics of agriculture..."
      },
      "category": "Kənd təsərrüfatı",
      "start_date": "2025-01-15",
      "end_date": "2025-01-20",
      "difficulty": "beginner",
      "type": "online",
      "status": "published",
      "registrations_count": 45,
      "media_counts": {
        "videos": 5,
        "documents": 3,
        "images": 2,
        "audio": 0,
        "total": 10
      }
    },
    {
      "id": 11,
      "title": {
        "az": "Buğda becərmə texnikaları",
        "en": "Wheat Cultivation Techniques"
      },
      "description": {
        "az": "Buğda becərmə üzrə təkmil biliklər...",
        "en": "Advanced knowledge in wheat cultivation..."
      },
      "category": "Kənd təsərrüfatı",
      "start_date": "2025-02-01",
      "end_date": "2025-02-10",
      "difficulty": "intermediate",
      "type": "offline",
      "status": "published",
      "registrations_count": 32,
      "media_counts": {
        "videos": 8,
        "documents": 5,
        "images": 4,
        "audio": 1,
        "total": 18
      }
    }
  ],
  "trainings_count": 2
}
```

---

## 📝 Response Field-lərinin İzahı

### Trainer Məlumatları:

| Field | Tip | Təsvir |
|-------|-----|--------|
| `id` | integer | Trainer-in unikal ID-si |
| `first_name` | string | Trainer-in adı |
| `last_name` | string | Trainer-in soyadı |
| `email` | string | Trainer-in email ünvanı |
| `profile_photo_url` | string \| null | Trainer-in şəkilinin tam URL-i |
| `trainer_category` | object \| null | Multilang kateqoriya: `{az: string, en: string}` |
| `trainer_category_string` | string \| null | Cari dilə görə kateqoriya string-i |
| `trainer_description` | object \| null | Multilang təsvir: `{az: string, en: string}` |
| `experience_years` | integer | İllərlə təcrübə (default: 0) |
| `experience_months` | integer | Aylarla təcrübə (0-11, default: 0) |
| `experience_formatted` | string \| null | Formatlaşdırılmış təcrübə: `"3 il 5 ay"`, `"3 il"`, `"5 ay"` və ya `null` |

### İxtisaslaşma Sahələri:

| Field | Tip | Təsvir |
|-------|-----|--------|
| `specializations` | array | Multilang array: `[{az: string, en: string}, ...]` |
| `specializations_strings` | array | Cari dilə görə ixtisaslaşma sahələri string array-i |
| `specializations_string` | string \| null | Vergüllə ayrılmış ixtisaslaşma sahələri mətni |

### Kvalifikasiyalar:

| Field | Tip | Təsvir |
|-------|-----|--------|
| `qualifications` | array | Multilang array: `[{az: string, en: string}, ...]` |
| `qualifications_strings` | array | Cari dilə görə kvalifikasiyalar string array-i |
| `qualifications_string` | string \| null | Vergüllə ayrılmış kvalifikasiyalar mətni |

### Əlavə Məlumatlar:

| Field | Tip | Təsvir |
|-------|-----|--------|
| `created_at` | string | Trainer-in yaradılma tarixi (ISO 8601 formatında) |
| `trainings` | array | Bu trainer-in **published** training-ləri (array of training objects) |
| `trainings_count` | integer | Published training-lərin sayı |

---

## 🎓 Training Object Strukturu (trainings array-də)

Hər bir training object-i aşağıdakı field-lərə malikdir:

| Field | Tip | Təsvir |
|-------|-----|--------|
| `id` | integer | Training-in ID-si |
| `title` | object | Multilang başlıq: `{az: string, en: string}` |
| `description` | object | Multilang təsvir: `{az: string, en: string}` |
| `category` | string | Training kateqoriyası |
| `start_date` | string \| null | Başlama tarixi (Y-m-d formatında) |
| `end_date` | string \| null | Bitmə tarixi (Y-m-d formatında) |
| `difficulty` | string | Çətinlik səviyyəsi: `beginner`, `intermediate`, `advanced` |
| `type` | string | Training tipi: `online`, `offline` |
| `status` | string | Training statusu: `published` |
| `registrations_count` | integer | Qeydiyyat sayı |
| `media_counts` | object | Media fayl sayları: `{videos: int, documents: int, images: int, audio: int, total: int}` |

---

## 🔍 Nümunə Request-lər

### 1. Trainer detalları (ID ilə):
```
GET /api/v1/trainers/5
```

### 2. Nümunə Response (minimal):
```json
{
  "id": 5,
  "first_name": "Əli",
  "last_name": "Məmmədov",
  "email": "ali@example.com",
  "profile_photo_url": null,
  "trainer_category": null,
  "trainer_category_string": null,
  "trainer_description": null,
  "experience_years": 0,
  "experience_months": 0,
  "experience_formatted": null,
  "specializations": [],
  "specializations_strings": [],
  "specializations_string": null,
  "qualifications": [],
  "qualifications_strings": [],
  "qualifications_string": null,
  "created_at": "2023-01-15T10:00:00.000000Z",
  "trainings": [],
  "trainings_count": 0
}
```

---

## ❌ Error Response-lər

### 404 Not Found (Trainer tapılmadı):
```json
{
  "message": "No query results for model [App\\Models\\User] {id}"
}
```

### 422 Validation Error (Yanlış ID formatı):
```json
{
  "message": "Invalid trainer ID"
}
```

---

## 💻 JavaScript/React Nümunəsi

```javascript
// Fetch trainer details
async function getTrainerDetail(trainerId) {
  const response = await fetch(`/api/v1/trainers/${trainerId}`);
  
  if (!response.ok) {
    if (response.status === 404) {
      throw new Error('Trainer tapılmadı');
    }
    if (response.status === 422) {
      throw new Error('Yanlış trainer ID formatı');
    }
    throw new Error('Trainer məlumatları yüklənə bilmədi');
  }
  
  return response.json();
}

// İstifadə:
try {
  const trainer = await getTrainerDetail(5);
  
  console.log('Trainer adı:', trainer.first_name, trainer.last_name);
  console.log('Email:', trainer.email);
  console.log('Kateqoriya:', trainer.trainer_category_string);
  console.log('Təcrübə:', trainer.experience_formatted);
  console.log('İxtisaslaşma:', trainer.specializations_string);
  console.log('Kvalifikasiyalar:', trainer.qualifications_string);
  console.log('Training sayı:', trainer.trainings_count);
  
  // Training-ləri göstər
  trainer.trainings.forEach(training => {
    console.log(`- ${training.title.az || training.title.en}`);
    console.log(`  Qeydiyyat: ${training.registrations_count}`);
    console.log(`  Media: ${training.media_counts.total} fayl`);
  });
} catch (error) {
  console.error('Xəta:', error.message);
}
```

---

## ⚠️ Əhəmiyyətli Qeydlər

1. **Public Endpoint:** Token lazım deyil, hər kəs istifadə edə bilər
2. **Published Training-lər:** Yalnız `status: 'published'` olan training-lər qaytarılır
3. **Training Sıralaması:** Training-lər `created_at` sütunu üzrə **azalan** sıra ilə (ən yenisi əvvəl)
4. **Multilang Field-lər:** 
   - Həmişə `trainer_category_string`, `specializations_strings`, `qualifications_strings` field-lərindən istifadə edin - daha asandır
   - Training-lərdə `title` və `description` multilang object-lərdir
5. **Media Counts:** 
   - Training-in öz media faylları + lesson-lərin media faylları sayılır
   - `video_url` və `pdf_url` də lesson media-larına daxil edilir
6. **Null Dəyərlər:** 
   - `profile_photo_url`, `trainer_category`, `trainer_description` null ola bilər
   - `specializations`, `qualifications` boş array ola bilər
   - Default dəyərlər göstərin
7. **Experience Format:** 
   - `experience_formatted` həmişə istifadə edin: `"3 il 5 ay"`, `"3 il"`, `"5 ay"` və ya `null`
8. **Created_at:** ISO 8601 formatında qaytarılır

---

## 🎯 Əsas Çıxışlar

- ✅ **Public endpoint** - Authentication lazım deyil
- ✅ **Tam trainer məlumatları** - Bütün field-lər daxil olmaqla
- ✅ **Published training-lər** - Trainer-in bütün published training-ləri
- ✅ **Training detalları** - Hər training üçün tam məlumat (media counts, registrations və s.)
- ✅ **Multilang dəstək** - Azərbaycan və İngilis dilləri
- ✅ **Formatlaşdırılmış təcrübə** - `experience_formatted` field-i
- ✅ **Vergüllə ayrılmış string-lər** - `specializations_string` və `qualifications_string` rahatlıq üçün

---

## 📊 Response Məlumat Strukturu

```
Trainer Detail Response
├── Trainer Məlumatları
│   ├── id, first_name, last_name, email
│   ├── profile_photo_url
│   ├── trainer_category (multilang)
│   ├── trainer_description (multilang)
│   └── experience (years, months, formatted)
├── İxtisaslaşma Sahələri
│   ├── specializations (multilang array)
│   ├── specializations_strings (string array)
│   └── specializations_string (comma-separated)
├── Kvalifikasiyalar
│   ├── qualifications (multilang array)
│   ├── qualifications_strings (string array)
│   └── qualifications_string (comma-separated)
├── Əlavə Məlumatlar
│   ├── created_at
│   └── trainings_count
└── Training-lər Array
    └── Her Training Object
        ├── id, title, description (multilang)
        ├── category, start_date, end_date
        ├── difficulty, type, status
        ├── registrations_count
        └── media_counts (videos, documents, images, audio, total)
```

