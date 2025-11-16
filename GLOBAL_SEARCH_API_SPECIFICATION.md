# Global Search API Spesifikasiyası

## Ümumi Məlumat

Bu sənəd frontend-də istifadə olunan global axtarış sistemi üçün backend API-nin tələblərini izah edir. Sistem istifadəçinin yuxarıdakı axtarış sahəsindən bütün məzmun tiplərində axtarış aparır, lakin **sertifikatlar istisna olunur**.

---

## 1. Endpoint

**URL:** `GET /api/v1/search/global`

**Məqsəd:** Bütün məzmun tiplərində (sertifikatlar istisna) global axtarış aparmaq.

---

## 2. Request Parametrləri

### 2.1. Query Parametrləri

| Parametr | Tip | Tələb | Təsvir |
|----------|-----|-------|--------|
| `q` | string | **Required** | Axtarış sorğusu. Minimum 2 simvol olmalıdır. |
| `lang` | string | Optional | Dil kodu: `az`, `en`, `ru`. Default: `az` |
| `exclude_types` | string | Optional | İstisna ediləcək məzmun tipləri (comma-separated). Frontend həmişə `certificates` göndərir. |
| `limit` | number | Optional | Hər tip üçün maksimum nəticə sayı. Default: 10, Maksimum: 20 |

### 2.2. Nümunə Request

```
GET /api/v1/search/global?q=pomidor&lang=az&exclude_types=certificates&limit=10
```

**Qeyd:** Frontend-də API interceptor avtomatik olaraq `lang` parametrini əlavə edir. Bu parametr istifadəçinin seçdiyi dildən gəlir (localStorage-dan `user_language` və ya `app_language`).

---

## 3. Axtarış Ediləcək Məzmun Tipləri

Aşağıdakı 11 tipdə axtarış aparılmalıdır:

### 3.1. Video Trainings (`video_trainings`)
- **Mənbə:** Video təlimlər cədvəli
- **Axtarış sahələri:**
  - `title` (başlıq)
  - `description` (təsvir)
  - `category` (kateqoriya)
  - `trainer.first_name` və `trainer.last_name` (təlimçinin adı)
- **Qaytarılmalı field-lər:**
  - `id` (məcburi)
  - `title` (multilang)
  - `description` (multilang)
  - `category` (multilang)
  - `image` (şəkil URL-i, null ola bilər)
  - `trainer` obyekti: `id`, `first_name` (multilang), `last_name` (multilang)
  - `difficulty` (səviyyə: beginner, intermediate, advanced)
  - `duration` (müddət dəqiqə ilə, null ola bilər)

### 3.2. Online Trainings (`online_trainings`)
- **Mənbə:** Online təlimlər cədvəli
- **Axtarış sahələri:** Video trainings ilə eyni
- **Qaytarılmalı field-lər:** Video trainings ilə eyni struktur

### 3.3. Onsite Trainings (`onsite_trainings`)
- **Mənbə:** Onsite (fiziki) təlimlər cədvəli
- **Axtarış sahələri:** Video trainings ilə eyni
- **Qaytarılmalı field-lər:** Video trainings ilə eyni struktur

### 3.4. Webinars (`webinars`)
- **Mənbə:** Vebinarlar cədvəli (meetings)
- **Axtarış sahələri:**
  - `title` (başlıq)
  - `description` (təsvir)
  - `trainer.name` və ya `trainer.first_name` + `trainer.last_name` (təlimçi)
- **Qaytarılmalı field-lər:**
  - `id` (məcburi)
  - `title` (multilang)
  - `description` (multilang)
  - `trainer` obyekti: `name` (multilang) və ya `first_name` + `last_name` (multilang)
  - `status` obyekti: `status` (planned, live, completed, cancelled), `label` (multilang)

### 3.5. Internship Programs (`internship_programs`)
- **Mənbə:** Staj proqramları cədvəli
- **Axtarış sahələri:**
  - `title` (başlıq)
  - `description` (təsvir)
  - `category` (kateqoriya)
  - `company_name` (şirkət adı, əgər varsa)
- **Qaytarılmalı field-lər:**
  - `id` (məcburi)
  - `title` (multilang)
  - `description` (multilang)
  - `category` (multilang)
  - `company_name` (multilang, əgər varsa)

### 3.6. Trainers (`trainers`)
- **Mənbə:** Təlimçilər cədvəli
- **Axtarış sahələri:**
  - `first_name` (ad)
  - `last_name` (soyad)
  - `trainer_description` (təlimçi haqqında məlumat)
  - `region` (region/bölgə)
- **Qaytarılmalı field-lər:**
  - `id` (məcburi)
  - `first_name` (multilang)
  - `last_name` (multilang)
  - `trainer_description` (multilang)
  - `region` (multilang)

### 3.7. Exams (`exams`)
- **Mənbə:** İmtahanlar cədvəli
- **Axtarış sahələri:**
  - `title` (başlıq)
  - `description` (təsvir)
  - `category` (kateqoriya)
- **Qaytarılmalı field-lər:**
  - `id` (məcburi)
  - `title` (multilang)
  - `description` (multilang)
  - `category` (multilang)

### 3.8. Articles (`articles`)
- **Mənbə:** Məqalələr cədvəli (education/articles)
- **Axtarış sahələri:**
  - `title` (başlıq)
  - `short_description` (qısa təsvir)
  - `body` və ya `content` (məzmun, yalnız qısa hissə axtarışda)
  - `category` (kateqoriya)
- **Qaytarılmalı field-lər:**
  - `id` (məcburi)
  - `title` (multilang)
  - `short_description` (multilang)
  - `category` (multilang)

### 3.9. Guides (`guides`)
- **Mənbə:** Təlimatlar cədvəli (education/telimats)
- **Axtarış sahələri:**
  - `title` (başlıq)
  - `description` (təsvir)
  - `category` (kateqoriya)
- **Qaytarılmalı field-lər:**
  - `id` (məcburi)
  - `title` (multilang)
  - `description` (multilang)
  - `category` (multilang)

### 3.10. QnA (`qna`)
- **Mənbə:** Forum sualları cədvəli (forum/questions)
- **Axtarış sahələri:**
  - `title` (sual başlığı)
  - `body` və ya `description` (sual məzmunu)
  - `category` (kateqoriya)
- **Qaytarılmalı field-lər:**
  - `id` (məcburi)
  - `title` (multilang)
  - `body` (multilang)
  - `category` (multilang)

### 3.11. Results (`results`)
- **Mənbə:** İstifadəçinin imtahan/təlim nəticələri
  - **Training Results:** `training_registrations` cədvəli (training tamamlanmaları)
  - **Exam Results:** `exam_registrations` və ya `exam_attempts` cədvəli (imtahan nəticələri)
- **Axtarış sahələri:**
  - Training üçün: `training.title`, `training.category`
  - Exam üçün: `exam.title`, `exam.category`
- **Qaytarılmalı field-lər:**
  - `id` (məcburi)
  - `course` obyekti: `title` (multilang), `category` (multilang)
  - `score` (ball - exam üçün, progress_percentage - training üçün)
  - `completed_at` (tamamlama tarixi)
  - `type` (optional: "training" və ya "exam" - fərqləndirmək üçün)

---

## 4. İstisna Ediləcək Tiplər

### 4.1. Certificates (`certificates`)
- **Səbəb:** Sertifikatlar yalnız sidebar-da göstərilir və global axtarışda olmamalıdır.
- **Qeyd:** Frontend həmişə `exclude_types=certificates` göndərir. Backend bu parametri nəzərə almalı və sertifikatlar üzrə axtarış aparmamalıdır.

---

## 5. Multilang (Çoxdilli) Sistem

### 5.1. Multilang Field-lərin Formatı

Backend multilang field-ləri **iki yolla** qaytara bilər:

#### Variant 1: String Format (Tövsiyə Olunan) ✅
Əgər request-də `lang` parametri varsa (məsələn `?lang=az`), backend multilang field-ləri **artıq translate edilmiş string** kimi qaytarmalıdır.

**Nümunə:**
```json
{
  "title": "Pomidor yetişdirmə texnikaları"  // artıq az dilində string
}
```

**Üstünlükləri:**
- Frontend-də əlavə parsing lazım deyil
- Daha sürətli
- Daha sadə

#### Variant 2: JSON Object Format
Əgər `lang` parametri yoxdursa və ya backend string formatı dəstəkləmir, onda JSON object formatında qaytara bilər:

**Nümunə:**
```json
{
  "title": {
    "az": "Pomidor yetişdirmə texnikaları",
    "en": "Tomato growing techniques",
    "ru": "Техники выращивания помидоров"
  }
}
```

**Qeyd:** Frontend hər iki formatı handle edir, lakin **Variant 1 (string format) tövsiyə olunur**.

### 5.2. Multilang Field-lərin Siyahısı

Aşağıdakı field-lər multilang ola bilər (hər tip üçün):

- `title` - Başlıq
- `description` - Təsvir
- `short_description` - Qısa təsvir
- `category` - Kateqoriya
- `first_name` - Ad (trainer üçün)
- `last_name` - Soyad (trainer üçün)
- `name` - Ad (trainer üçün, bəzən)
- `trainer_description` - Təlimçi haqqında
- `region` - Region/bölgə
- `body` - Məzmun (QnA və Articles üçün)
- `label` - Etiket (status label üçün)
- `company_name` - Şirkət adı (internship programs üçün)

### 5.3. Dil Parametrinin İşləməsi

1. **Frontend-dən gələn request:**
   - Frontend API interceptor avtomatik olaraq `lang` parametrini əlavə edir
   - Bu parametr istifadəçinin seçdiyi dildən gəlir (localStorage: `user_language` və ya `app_language`)
   - Mümkün dəyərlər: `az`, `en`, `ru`

2. **Backend-in cavabı:**
   - Əgər `lang=az` göndərilibsə, multilang field-ləri azərbaycan dilində string kimi qaytarmalıdır
   - Əgər `lang=en` göndərilibsə, multilang field-ləri ingilis dilində string kimi qaytarmalıdır
   - Əgər `lang=ru` göndərilibsə, multilang field-ləri rus dilində string kimi qaytarmalıdır
   - Əgər dil yoxdursa və ya dəstəklənmirsə, fallback olaraq azərbaycan dilini istifadə etməlidir

3. **Fallback mexanizmi:**
   - Əgər müəyyən bir dildə tərcümə yoxdursa, azərbaycan dilinə fallback etməlidir
   - Əgər azərbaycan dilində də yoxdursa, ingilis dilinə fallback etməlidir
   - Əgər heç birində yoxdursa, mövcud olan ilk dildən istifadə etməlidir

---

## 6. Response Strukturu

### 6.1. Uğurlu Response

```json
{
  "data": {
    "video_trainings": [
      {
        "id": 1,
        "title": "Pomidor yetişdirmə texnikaları",
        "description": "Bu kursda pomidor bitkisinin...",
        "category": "Bitki İstehsalı",
        "image": "https://example.com/image.jpg",
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
          "name": "Aydın Həsənov"
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

### 6.2. Boş Nəticə Response

Əgər heç bir nəticə tapılmasa, hər tip üçün boş array qaytarılmalıdır:

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

### 6.3. Meta Field-ləri

- `query` - Axtarış sorğusu (request-dən gələn `q` parametri)
- `total` - Ümumi tapılan nəticələrin sayı (bütün tiplərdə)
- `excluded_types` - İstisna edilən tiplərin siyahısı (array)

---

## 7. Axtarış Alqoritmi

### 7.1. Axtarış Qaydaları

1. **Case-insensitive:** Böyük/kiçik hərf fərqi yoxdur
   - Nümunə: "Pomidor" və "pomidor" eyni nəticəni verir

2. **Partial match:** Tam uyğunluq lazım deyil, hissəvi uyğunluq kifayətdir
   - Nümunə: "pomidor" sorğusu "Pomidor yetişdirmə" başlığında tapılır

3. **Multiple fields:** Hər tip üçün yuxarıda göstərilən bütün sahələrdə axtarış aparılmalıdır
   - Nümunə: Training üçün `title`, `description`, `category`, `trainer.first_name`, `trainer.last_name` sahələrində axtarış

4. **Full-text search:** Məzmun sahələrində (description, body) də axtarış aparılmalıdır

### 7.2. Limit və Pagination

- Hər tip üçün maksimum `limit` sayda nəticə qaytarılmalıdır (default: 10)
- Pagination lazım deyil (axtarış nəticələri üçün)
- Əgər `limit` parametri göndərilməyibsə, default 10 istifadə edilməlidir
- Maksimum limit: 20 (frontend-dən göndərilə bilər)

---

## 8. Error Handling

### 8.1. Validation Error (400 Bad Request)

Əgər `q` parametri 2 simvoldan azdırsa:

```json
{
  "message": "Search query must be at least 2 characters",
  "errors": {
    "q": ["The query field must be at least 2 characters."]
  }
}
```

### 8.2. Server Error (500 Internal Server Error)

Əgər server xətası baş verərsə:

```json
{
  "message": "Internal server error",
  "error": "Database connection failed"
}
```

---

## 9. Performance Tələbləri

1. **Response Time:** Normal halda 500ms-dən az olmalıdır
2. **Database Queries:** Mümkün qədər optimize edilməlidir (index-lər, full-text search)
3. **Caching:** Mümkündürsə, tez-tez soruşulan sorğular üçün cache istifadə edilməlidir
4. **Parallel Processing:** Mümkündürsə, fərqli tiplərdə axtarış paralel aparılmalıdır

---

## 10. Tövsiyələr

1. **String Format Tövsiyə Olunur:** Multilang field-ləri `lang` parametrinə görə artıq translate edilmiş string kimi qaytarmaq daha yaxşıdır
2. **Full-text Search:** PostgreSQL-də `ILIKE` və ya `tsvector` istifadə edə bilərsiniz
3. **Index-lər:** Axtarış edilən sahələr üçün database index-ləri yaratmaq performansı artırır
4. **Limit:** Hər tip üçün limit tətbiq edin (default 10)
5. **Exclude Types:** `exclude_types` parametrini parse edin və həmin tipləri skip edin

---

## 11. Nümunə İmplementasiya (Konseptual)

**Qeyd:** Bu sadəcə konseptual izahatdır, real kod deyil.

1. Request-dən `q`, `lang`, `exclude_types`, `limit` parametrlərini al
2. `q` parametrini validate et (minimum 2 simvol)
3. `exclude_types`-ı parse et və `certificates`-ı istisna et
4. Hər tip üçün (certificates istisna):
   - Müvafiq cədvəldə axtarış apar
   - Multilang field-ləri `lang` parametrinə görə translate et
   - `limit` sayda nəticə götür
5. Bütün nəticələri `data` obyektində qruplaşdır
6. `meta` obyektini hazırla (query, total, excluded_types)
7. Response qaytar

---

## 12. Əlaqə

Əgər suallarınız varsa, frontend developer ilə əlaqə saxlayın.

**Son yeniləmə:** 2024

