# Trainer Sistemi - Frontend Developer Üçün Təlimat

## Ümumi Məlumat

Trainer sistemi user-lərin trainer kimi işləməsini təmin edir. Hər trainer öz kateqoriyası, təcrübəsi, ixtisaslaşma sahələri və kvalifikasiyaları ilə təqdim olunur.

---

## 📡 API Endpoint-ləri

### 1. Trainer-lərin Siyahısı (Public - Authentication yoxdur)

**GET** `/api/v1/trainers`

Bu endpoint bütün trainer-lərin siyahısını qaytarır. Public-dir, yəni token tələb etmir.

#### Query Parameters:

| Parametr | Tip | Tələb olunur | Təsvir |
|----------|-----|--------------|--------|
| `search` | string | Yox | Ad, soyad, kateqoriya və ya ixtisaslaşma sahələrində axtarış |
| `trainer_category` | string | Yox | Trainer kateqoriyasına görə filtr |
| `sort_by` | string | Yox | Sıralama: `first_name`, `last_name`, `trainer_category`, `created_at` (default: `first_name`) |
| `sort_order` | string | Yox | `asc` və ya `desc` (default: `asc`) |
| `per_page` | integer | Yox | Səhifə başına trainer sayı (max: 100, default: 15) |
| `page` | integer | Yox | Səhifə nömrəsi (default: 1) |

#### Response Strukturu:

```json
{
  "data": [
    {
      "id": 1,
      "first_name": "Əli",
      "last_name": "Məmmədov",
      "profile_photo_url": "https://api.aqrar.az/storage/profile_photos/photo.jpg",
      "trainer_category": {
        "az": "Kənd təsərrüfatı",
        "en": "Agriculture"
      },
      "trainer_category_string": "Kənd təsərrüfatı",
      "specializations": [
        {
          "az": "Buğda becərməsi",
          "en": "Wheat cultivation"
        },
        {
          "az": "Taxıl bitkiləri",
          "en": "Cereal crops"
        }
      ],
      "specializations_strings": [
        "Buğda becərməsi",
        "Taxıl bitkiləri"
      ],
      "experience_years": 3,
      "experience_months": 5,
      "experience_formatted": "3 il 5 ay",
      "trainings_count": 12
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 67,
    "from": 1,
    "to": 15
  },
  "links": {
    "first": "http://api.aqrar.az/api/v1/trainers?page=1",
    "last": "http://api.aqrar.az/api/v1/trainers?page=5",
    "prev": null,
    "next": "http://api.aqrar.az/api/v1/trainers?page=2"
  }
}
```

#### Field-lərin İzahı:

- **`id`** - Trainer-in unikal ID-si
- **`first_name`** - Trainer-in adı
- **`last_name`** - Trainer-in soyadı
- **`profile_photo_url`** - Trainer-in şəkilinin tam URL-i (null ola bilər)
- **`trainer_category`** - Multilang object: `{az: string, en: string}`
- **`trainer_category_string`** - Cari dilə görə kateqoriya string-i (göstərmək üçün rahatdır)
- **`specializations`** - İxtisaslaşma sahələri array-i (hər biri multilang object)
- **`specializations_strings`** - Cari dilə görə ixtisaslaşma sahələri string array-i
- **`experience_years`** - İllərlə təcrübə (integer)
- **`experience_months`** - Aylarla təcrübə (integer, 0-11)
- **`experience_formatted`** - Formatlaşdırılmış təcrübə: `"3 il 5 ay"`, `"3 il"`, `"5 ay"` və ya `null`
- **`trainings_count`** - Bu trainer-in **published** training-lərinin sayı

---

### 2. Trainer Detalları (Public - Authentication yoxdur)

**GET** `/api/v1/trainers/{id}`

Müəyyən bir trainer-in bütün detallı məlumatlarını qaytarır.

#### Response Strukturu:

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
    "az": "15 ildir kənd təsərrüfatı sahəsində çalışır...",
    "en": "Working in agriculture for 15 years..."
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
    }
  ],
  "specializations_strings": [
    "Buğda becərməsi",
    "Taxıl bitkiləri"
  ],
  "specializations_string": "Buğda becərməsi, Taxıl bitkiləri",
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
        "az": "Bu training-də...",
        "en": "This training covers..."
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
    }
  ],
  "trainings_count": 3
}
```

#### Field-lərin İzahı:

**Trainer Məlumatları:**
- **`id`** - Trainer ID
- **`first_name`**, **`last_name`** - Ad və soyad
- **`email`** - Email ünvanı
- **`profile_photo_url`** - Şəkil URL-i (null ola bilər)
- **`trainer_category`** - Multilang kateqoriya object
- **`trainer_category_string`** - Cari dilə görə kateqoriya
- **`trainer_description`** - Multilang təsvir object
- **`experience_years`**, **`experience_months`** - Təcrübə illəri və ayları
- **`experience_formatted`** - Formatlaşdırılmış təcrübə mətni

**İxtisaslaşma və Kvalifikasiyalar:**
- **`specializations`** - Multilang array: `[{az: string, en: string}, ...]`
- **`specializations_strings`** - Cari dilə görə string array
- **`specializations_string`** - Vergüllə ayrılmış mətn
- **`qualifications`** - Multilang array: `[{az: string, en: string}, ...]`
- **`qualifications_strings`** - Cari dilə görə string array
- **`qualifications_string`** - Vergüllə ayrılmış mətn

**Training-lər:**
- **`trainings`** - Bu trainer-in **published** training-ləri
- **`trainings_count`** - Training-lərin sayı
- Hər training üçün:
  - `title`, `description` - Multilang object-lər
  - `category`, `start_date`, `end_date`
  - `difficulty`, `type`, `status`
  - `registrations_count` - Qeydiyyat sayı
  - `media_counts` - Media fayl sayları (videos, documents, images, audio)

---

## 🌐 Multilang Field-lərin İşlənməsi

### Dəstəklənən Dillər:
- **az** - Azərbaycan dili
- **en** - İngilis dili

### Multilang Field-lər:
1. **`trainer_category`** - `{az: string, en: string}`
2. **`trainer_description`** - `{az: string, en: string}`
3. **`specializations`** - `[{az: string, en: string}, ...]`
4. **`qualifications`** - `[{az: string, en: string}, ...]`
5. Training-lərdə: **`title`**, **`description`** - `{az: string, en: string}`

### Frontend-də İstifadə:

**Yol 1: `_string` və `_strings` field-lərindən istifadə (Tövsiyə olunur)**

API avtomatik olaraq cari dilə görə string-lər qaytarır:
- `trainer_category_string` - Hazır string
- `specializations_strings` - String array
- `specializations_string` - Vergüllə ayrılmış mətn

```javascript
// React nümunəsi
const category = trainer.trainer_category_string || 'Yoxdur';
const specs = trainer.specializations_strings?.join(', ') || 'Yoxdur';
```

**Yol 2: Manual multilang object-dən oxumaq**

```javascript
const locale = 'az'; // və ya 'en'
const category = trainer.trainer_category?.[locale] || trainer.trainer_category?.az || 'Yoxdur';
```

---

## 🎨 Frontend UI Tövsiyələri

### Trainer Card Komponenti (List):

```jsx
function TrainerCard({ trainer }) {
  return (
    <div className="trainer-card">
      <img 
        src={trainer.profile_photo_url || '/default-avatar.png'} 
        alt={trainer.first_name}
      />
      <h3>{trainer.first_name} {trainer.last_name}</h3>
      <p className="category">{trainer.trainer_category_string || 'Yoxdur'}</p>
      <p className="experience">{trainer.experience_formatted || 'Yoxdur'}</p>
      <p className="trainings">{trainer.trainings_count} training</p>
      <div className="specializations">
        {trainer.specializations_strings?.map((spec, i) => (
          <span key={i} className="tag">{spec}</span>
        ))}
      </div>
    </div>
  );
}
```

### Trainer Detail Səhifəsi:

```jsx
function TrainerDetail({ trainer }) {
  return (
    <div className="trainer-detail">
      <img src={trainer.profile_photo_url || '/default-avatar.png'} />
      <h1>{trainer.first_name} {trainer.last_name}</h1>
      <p>Email: {trainer.email}</p>
      <p>Kateqoriya: {trainer.trainer_category_string}</p>
      <p>Təcrübə: {trainer.experience_formatted}</p>
      
      {trainer.trainer_description && (
        <div className="description">
          <h2>Haqqında</h2>
          <p>{trainer.trainer_description.az || trainer.trainer_description.en}</p>
        </div>
      )}
      
      <div className="specializations">
        <h2>İxtisaslaşma sahələri</h2>
        <ul>
          {trainer.specializations_strings?.map((spec, i) => (
            <li key={i}>{spec}</li>
          ))}
        </ul>
      </div>
      
      <div className="qualifications">
        <h2>Kvalifikasiyalar</h2>
        <ul>
          {trainer.qualifications_strings?.map((qual, i) => (
            <li key={i}>{qual}</li>
          ))}
        </ul>
      </div>
      
      <div className="trainings">
        <h2>Training-lər ({trainer.trainings_count})</h2>
        {trainer.trainings.map(training => (
          <TrainingCard key={training.id} training={training} />
        ))}
      </div>
    </div>
  );
}
```

---

## 📝 Əhəmiyyətli Qeydlər

1. **Pagination:** List endpoint-i həmişə pagination qaytarır
2. **Published Training-lər:** Yalnız `status: 'published'` olan training-lər göstərilir
3. **Null Dəyərlər:** `profile_photo_url`, `trainer_category`, və s. null ola bilər - default dəyərlər göstərin
4. **Experience Format:** 
   - Həm years, həm də months varsa: `"3 il 5 ay"`
   - Yalnız years varsa: `"3 il"`
   - Yalnız months varsa: `"5 ay"`
   - Hər ikisi 0-dırsa: `null`
5. **Multilang:** Həmişə `_string` və `_strings` field-lərindən istifadə edin - daha asandır
6. **Created_at:** ISO 8601 formatında qaytarılır

---

## 🔍 Axtarış və Filtr Nümunələri

```javascript
// Axtarış
GET /api/v1/trainers?search=əli

// Filtr
GET /api/v1/trainers?trainer_category=Kənd təsərrüfatı

// Sıralama
GET /api/v1/trainers?sort_by=created_at&sort_order=desc

// Pagination
GET /api/v1/trainers?page=2&per_page=20

// Kombinasiya
GET /api/v1/trainers?search=əli&trainer_category=Kənd təsərrüfatı&sort_by=created_at&sort_order=desc&page=1&per_page=20
```

---

## ❌ Error Response-lər

### 404 Not Found:
```json
{
  "message": "Trainer tapılmadı"
}
```

### 422 Validation Error:
```json
{
  "message": "Invalid trainer ID"
}
```

---

## ✅ Nümunə JavaScript/React Kodları

### Trainer List Fetch:

```javascript
async function fetchTrainers(page = 1, search = '') {
  const params = new URLSearchParams({
    page: page.toString(),
    per_page: '15',
    ...(search && { search })
  });
  
  const response = await fetch(`/api/v1/trainers?${params}`);
  const data = await response.json();
  
  return {
    trainers: data.data,
    pagination: data.meta,
    links: data.links
  };
}
```

### Trainer Detail Fetch:

```javascript
async function fetchTrainerDetail(trainerId) {
  const response = await fetch(`/api/v1/trainers/${trainerId}`);
  if (!response.ok) {
    throw new Error('Trainer tapılmadı');
  }
  return response.json();
}
```

---

## 🎯 Əsas Çıxışlar

1. **GET `/api/v1/trainers`** - Trainer-lərin siyahısı (public, pagination, search, filter)
2. **GET `/api/v1/trainers/{id}`** - Trainer detalları + training-ləri (public)
3. Multilang dəstəyi - `_string` və `_strings` field-ləri istifadə edin
4. Experience formatlaşdırılır - `experience_formatted` field-indən istifadə edin
5. Yalnız published training-lər göstərilir

Bu sistem tam public-dir və frontend-də istifadə etmək üçün hazırdır! 🚀
