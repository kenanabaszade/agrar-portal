# Global Search API - Frontend Üçün Təlimat

## 📍 Endpoint

**URL:** `GET /api/v1/search/global`

**Base URL:** `http://localhost:8000` (development)

**Tam URL:** `http://localhost:8000/api/v1/search/global`

---

## 🔍 Sorğu Atma

### Request Method
**GET** (GET request)

### Query Parametrləri

| Parametr | Tip | Tələb | Təsvir |
|----------|-----|-------|--------|
| `q` | string | **Required** | Axtarış sorğusu. Minimum 2 simvol olmalıdır |
| `lang` | string | Optional | Dil kodu: `az`, `en`, `ru`. Default: `az` |
| `exclude_types` | string | Optional | İstisna ediləcək məzmun tipləri (comma-separated). Həmişə `certificates` göndərin |
| `limit` | number | Optional | Hər tip üçün maksimum nəticə sayı. Default: 10, Maksimum: 20 |

### Nümunə Sorğu

```
GET /api/v1/search/global?q=pomidor&lang=az&exclude_types=certificates&limit=10
```

**Qeyd:** Frontend-də API interceptor avtomatik olaraq `lang` parametrini əlavə edir (localStorage-dan `user_language` və ya `app_language`).

---

## 📥 Response Strukturu

### Uğurlu Response (200 OK)

```json
{
  "data": {
    "video_trainings": [
      {
        "id": 1,
        "title": "Pomidor yetişdirmə texnikaları",
        "description": "Bu kursda pomidor bitkisinin...",
        "category": "Bitki İstehsalı",
        "image": "http://localhost:8000/storage/trainings/banner.jpg",
        "trainer": {
          "id": 5,
          "first_name": "Əli",
          "last_name": "Məmmədov"
        },
        "difficulty": "beginner",
        "duration": 120
      }
    ],
    "online_trainings": [],
    "onsite_trainings": [],
    "webinars": [
      {
        "id": 2,
        "title": "Pomidor xəstəlikləri",
        "description": "Pomidor bitkilərində...",
        "trainer": {
          "name": "Aydın Həsənov",
          "id": 10
        },
        "status": {
          "status": "planned",
          "label": "Gözlənilir"
        }
      }
    ],
    "internship_programs": [],
    "trainers": [],
    "exams": [],
    "articles": [],
    "guides": [],
    "qna": [],
    "results": []
  },
  "meta": {
    "query": "pomidor",
    "total": 2,
    "excluded_types": ["certificates"]
  }
}
```

### Boş Nəticə Response

Əgər heç bir nəticə tapılmasa, hər tip üçün boş array qaytarılır:

```json
{
  "data": {
    "video_trainings": [],
    "online_trainings": [],
    "onsite_trainings": [],
    "webinars": [],
    "internship_programs": [],
    "trainers": [],
    "exams": [],
    "articles": [],
    "guides": [],
    "qna": [],
    "results": []
  },
  "meta": {
    "query": "xyz123",
    "total": 0,
    "excluded_types": ["certificates"]
  }
}
```

### Validation Error (400 Bad Request)

Əgər `q` parametri 2 simvoldan azdırsa:

```json
{
  "message": "The q field must be at least 2 characters.",
  "errors": {
    "q": ["The q field must be at least 2 characters."]
  }
}
```

---

## 🌐 Multilang Sistemi

### Necə İşləyir?

Backend-də multilang field-lər **JSON formatında** saxlanılır:

```json
{
  "az": "Pomidor yetişdirmə texnikaları",
  "en": "Tomato growing techniques",
  "ru": "Техники выращивания помидоров"
}
```

### Frontend-də Nə Görünür?

**Backend-dən gələn response:**

`lang=az` göndərsəniz:
```json
{
  "title": "Pomidor yetişdirmə texnikaları"  // Artıq az dilində string
}
```

`lang=en` göndərsəniz:
```json
{
  "title": "Tomato growing techniques"  // Artıq en dilində string
}
```

**Yəni:**
- Frontend-də əlavə parsing lazım deyil
- Backend artıq seçilmiş dilə görə translate edir
- Sadəcə `lang` parametrini göndərin, backend qalanını edir

### Fallback Sistemi

Əgər seçilmiş dilə tərcümə yoxdursa:
1. **İlk:** Seçilmiş dil (az, en, ru)
2. **İkinci:** Azərbaycan dili (az) - default
3. **Üçüncü:** İngilis dili (en)
4. **Dördüncü:** Rus dili (ru)
5. **Beşinci:** İlk mövcud dil

**Nümunə:**
- `lang=en` göndərdiniz, amma en tərcüməsi yoxdur
- Backend avtomatik olaraq az göstərir
- Frontend heç nə etməyə ehtiyac yoxdur

---

## 📋 Response Field-ləri

### Video Trainings / Online Trainings / Onsite Trainings

```json
{
  "id": 1,
  "title": "string (multilang - artıq translate edilmiş)",
  "description": "string (multilang - artıq translate edilmiş)",
  "category": "string (sadə string, multilang deyil)",
  "image": "string|null (şəkil URL-i)",
  "trainer": {
    "id": 5,
    "first_name": "string (multilang)",
    "last_name": "string (multilang)"
  },
  "difficulty": "beginner|intermediate|advanced",
  "duration": "number|null (dəqiqə ilə)"
}
```

### Webinars

```json
{
  "id": 2,
  "title": "string (multilang)",
  "description": "string (multilang)",
  "trainer": {
    "name": "string (full name)",
    "id": 10
  },
  "status": {
    "status": "scheduled|live|ended|cancelled",
    "label": "string (multilang)"
  }
}
```

### Internship Programs

```json
{
  "id": 3,
  "title": "string (multilang)",
  "description": "string (multilang)",
  "category": "string",
  "company_name": "string|null (multilang)"
}
```

### Trainers

```json
{
  "id": 4,
  "first_name": "string (multilang)",
  "last_name": "string (multilang)",
  "trainer_description": "string|null (multilang)",
  "region": "string|null (multilang)"
}
```

### Exams

```json
{
  "id": 5,
  "title": "string (multilang)",
  "description": "string (multilang)",
  "category": "string"
}
```

### Articles

```json
{
  "id": 6,
  "title": "string (multilang)",
  "short_description": "string (multilang)",
  "category": "string"
}
```

### Guides

```json
{
  "id": 7,
  "title": "string (multilang)",
  "description": "string (multilang)",
  "category": "string"
}
```

### QnA

```json
{
  "id": 8,
  "title": "string (multilang)",
  "body": "string (multilang)",
  "category": "string"
}
```

### Results

```json
{
  "id": 9,
  "course": {
    "title": "string (multilang)",
    "category": "string"
  },
  "score": "number (ball və ya completion percentage)",
  "completed_at": "string|null (ISO 8601 formatında)",
  "type": "training|exam"
}
```

---

## 🔄 İstifadə Nümunəsi

### Vue.js / React

```javascript
// API call
const response = await fetch('/api/v1/search/global?q=pomidor&lang=az&exclude_types=certificates&limit=10');

const data = await response.json();

// data.data.video_trainings - Video trainings array
// data.data.online_trainings - Online trainings array
// data.data.webinars - Webinars array
// ... digər tiplər

// data.meta.query - Axtarış sorğusu
// data.meta.total - Ümumi nəticə sayı
// data.meta.excluded_types - İstisna edilən tiplər
```

### Axios ilə

```javascript
const response = await axios.get('/api/v1/search/global', {
  params: {
    q: 'pomidor',
    lang: 'az',
    exclude_types: 'certificates',
    limit: 10
  }
});

const results = response.data.data;
const meta = response.data.meta;
```

---

## ⚠️ Qeydlər

1. **`q` parametri mütləq lazımdır** və minimum 2 simvol olmalıdır
2. **`lang` parametri optional-dır**, default: `az`
3. **`exclude_types` həmişə `certificates` göndərin** (frontend-də)
4. **`limit` default: 10**, maksimum: 20
5. **Response-də bütün tiplər həmişə mövcuddur** (boş array ola bilər)
6. **Multilang field-lər artıq translate edilmiş string** kimi qaytarılır
7. **Category field-ləri sadə string-dir** (multilang deyil)
8. **Image URL-lər full URL** kimi qaytarılır (path yoxdur)

---

## 🎯 Xülasə

**Endpoint:**
- `GET /api/v1/search/global`

**Parametrlər:**
- `q` (required) - Axtarış sorğusu
- `lang` (optional) - Dil kodu (az, en, ru)
- `exclude_types` (optional) - İstisna edilən tiplər
- `limit` (optional) - Hər tip üçün limit

**Response:**
- `data` - Hər tip üçün array
- `meta` - Query, total, excluded_types

**Multilang:**
- Backend artıq translate edir
- Frontend-də əlavə parsing lazım deyil
- `lang` parametrinə görə avtomatik translate olunur

