# Trainer-lərin Siyahısı - GET Endpoint

## 🔗 Endpoint URL

```
GET /api/v1/trainers
```

**Status:** ✅ Public (Authentication lazım deyil)

---

## 📋 Query Parameters

| Parametr | Tip | Tələb olunur | Default | Təsvir |
|----------|-----|--------------|---------|--------|
| `search` | string | Yox | - | Ad, soyad, kateqoriya və ya ixtisaslaşma sahələrində axtarış |
| `trainer_category` | string | Yox | - | Trainer kateqoriyasına görə filtr |
| `sort_by` | string | Yox | `first_name` | Sıralama: `first_name`, `last_name`, `trainer_category`, `created_at` |
| `sort_order` | string | Yox | `asc` | Sıralama istiqaməti: `asc` və ya `desc` |
| `per_page` | integer | Yox | `15` | Səhifə başına trainer sayı (max: 100) |
| `page` | integer | Yox | `1` | Səhifə nömrəsi |

---

## 📤 Response Strukturu

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
    "first": "http://localhost:8000/api/v1/trainers?page=1",
    "last": "http://localhost:8000/api/v1/trainers?page=5",
    "prev": null,
    "next": "http://localhost:8000/api/v1/trainers?page=2"
  }
}
```

---

## 📝 Response Field-lərinin İzahı

### Data Array (Hər bir trainer üçün):

| Field | Tip | Təsvir |
|-------|-----|--------|
| `id` | integer | Trainer-in unikal ID-si |
| `first_name` | string | Trainer-in adı |
| `last_name` | string | Trainer-in soyadı |
| `profile_photo_url` | string \| null | Trainer-in şəkilinin tam URL-i |
| `trainer_category` | object \| null | Multilang kateqoriya: `{az: string, en: string}` |
| `trainer_category_string` | string \| null | Cari dilə görə kateqoriya string-i (göstərmək üçün) |
| `specializations` | array | İxtisaslaşma sahələri (multilang): `[{az: string, en: string}, ...]` |
| `specializations_strings` | array | Cari dilə görə ixtisaslaşma sahələri string array-i |
| `experience_years` | integer | İllərlə təcrübə (default: 0) |
| `experience_months` | integer | Aylarla təcrübə (0-11, default: 0) |
| `experience_formatted` | string \| null | Formatlaşdırılmış təcrübə: `"3 il 5 ay"`, `"3 il"`, `"5 ay"` və ya `null` |
| `trainings_count` | integer | Bu trainer-in **published** training-lərinin sayı |

### Meta Object (Pagination):

| Field | Tip | Təsvir |
|-------|-----|--------|
| `current_page` | integer | Cari səhifə nömrəsi |
| `last_page` | integer | Son səhifə nömrəsi |
| `per_page` | integer | Səhifə başına trainer sayı |
| `total` | integer | Ümumi trainer sayı |
| `from` | integer | Bu səhifədəki ilk trainer-in sıra nömrəsi |
| `to` | integer | Bu səhifədəki son trainer-in sıra nömrəsi |

### Links Object (Pagination Link-ləri):

| Field | Tip | Təsvir |
|-------|-----|--------|
| `first` | string | İlk səhifə URL-i |
| `last` | string | Son səhifə URL-i |
| `prev` | string \| null | Əvvəlki səhifə URL-i |
| `next` | string \| null | Növbəti səhifə URL-i |

---

## 🔍 Nümunə Request-lər

### 1. Bütün trainer-lər (ilk səhifə):
```
GET /api/v1/trainers
```

### 2. Axtarış ilə:
```
GET /api/v1/trainers?search=əli
```

### 3. Filtr ilə:
```
GET /api/v1/trainers?trainer_category=Kənd təsərrüfatı
```

### 4. Sıralama ilə:
```
GET /api/v1/trainers?sort_by=created_at&sort_order=desc
```

### 5. Pagination ilə:
```
GET /api/v1/trainers?page=2&per_page=20
```

### 6. Kombinasiya:
```
GET /api/v1/trainers?search=əli&trainer_category=Kənd təsərrüfatı&sort_by=created_at&sort_order=desc&page=1&per_page=20
```

---

## 💻 JavaScript/React Nümunəsi

```javascript
// Fetch trainers list
async function getTrainers(params = {}) {
  const queryParams = new URLSearchParams({
    page: params.page || '1',
    per_page: params.perPage || '15',
    ...(params.search && { search: params.search }),
    ...(params.category && { trainer_category: params.category }),
    ...(params.sortBy && { sort_by: params.sortBy }),
    ...(params.sortOrder && { sort_order: params.sortOrder }),
  });

  const response = await fetch(`/api/v1/trainers?${queryParams}`);
  
  if (!response.ok) {
    throw new Error('Trainer-lər yüklənə bilmədi');
  }
  
  return response.json();
}

// İstifadə:
const data = await getTrainers({
  page: 1,
  perPage: 15,
  search: 'əli',
  sortBy: 'created_at',
  sortOrder: 'desc'
});

console.log('Trainers:', data.data);
console.log('Ümumi sayı:', data.meta.total);
console.log('Cari səhifə:', data.meta.current_page);
```

---

## ⚠️ Əhəmiyyətli Qeydlər

1. **Public Endpoint:** Token lazım deyil, hər kəs istifadə edə bilər
2. **Published Training-lər:** Yalnız `status: 'published'` olan training-lər `trainings_count`-a daxil edilir
3. **Multilang Field-lər:** Həmişə `trainer_category_string` və `specializations_strings` field-lərindən istifadə edin - daha asandır
4. **Experience Format:** 
   - `experience_formatted` həmişə istifadə edin: `"3 il 5 ay"`, `"3 il"`, `"5 ay"` və ya `null`
5. **Null Dəyərlər:** `profile_photo_url`, `trainer_category`, və s. null ola bilər - default dəyərlər göstərin
6. **Pagination:** Həmişə `meta` və `links` object-lərindən istifadə edin

---

## 🎯 Əsas Çıxışlar

- ✅ **Public endpoint** - Authentication lazım deyil
- ✅ **Pagination dəstəyi** - Meta və links object-ləri ilə
- ✅ **Axtarış funksionallığı** - Ad, soyad, kateqoriya, ixtisaslaşma sahələrində
- ✅ **Filtr** - Kateqoriyaya görə
- ✅ **Sıralama** - 4 müxtəlif field üzrə
- ✅ **Multilang dəstək** - Azərbaycan və İngilis dilləri
- ✅ **Formatlaşdırılmış təcrübə** - `experience_formatted` field-i
- ✅ **Published training sayı** - Hər trainer üçün

