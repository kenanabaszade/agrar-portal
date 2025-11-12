# İmtahan Sisteminin İzahı - Backend Texniki Sənəd

## 📚 Mündəricat
1. [Ümumi Məlumat](#ümumi-məlumat)
2. [Sual Tipləri](#sual-tipləri)
3. [İmtahan Prosesi](#imtahan-prosesi)
4. [Bal Hesablama Sistemi](#bal-hesablama-sistemi)
5. [Verilənlər Bazası Strukturu](#verilənlər-bazası-strukturu)
6. [API Endpoint-ləri](#api-endpoint-ləri)

---

## 🔍 Ümumi Məlumat

Backend-imizdə imtahan sistemi Laravel framework-ü əsasında qurulmuşdur və aşağıdakı əsas komponentlərdən ibarətdir:

- **Exam Model** - İmtahan əsas məlumatları
- **ExamQuestion Model** - Suallar
- **ExamChoice Model** - Cavab variantları
- **ExamRegistration Model** - İmtahana qeydiyyat
- **ExamUserAnswer Model** - Tələbənin cavabları
- **Certificate Model** - Sertifikatlar

### 🌐 Çoxdilli Sistem (Multilang Support)

**Dəstəklənən dillər:**
- `az` - Azərbaycan dili (default)
- `en` - English
- `ru` - Русский

**Çoxdilli sahələr:**

**Exam (İmtahan):**
- `title` - Başlıq
- `description` - Təsvir
- `sertifikat_description` - Sertifikat təsviri
- `rules` - Qaydalar
- `instructions` - Təlimatlar

**ExamQuestion (Sual):**
- `question_text` - Sual mətni
- `explanation` - İzah

**ExamChoice (Variant):**
- `choice_text` - Variant mətni
- `explanation` - İzah

**Necə işləyir:**
1. Məlumatlar JSON formatında saxlanılır: `{"az": "Mətn", "en": "Text", "ru": "Текст"}`
2. Dil seçimi:
   - Request parametri ilə: `?lang=az`
   - Laravel locale ilə: `App::getLocale()`
   - Default: `az`
3. Avtomatik tərcümə:
   - İstənilən dil yoxdursa, default dil (`az`) göstərilir
   - Default dil də yoxdursa, ilk mövcud dil göstərilir

**Nümunə JSON struktur:**
```json
{
  "question_text": {
    "az": "Kompost nədir?",
    "en": "What is compost?",
    "ru": "Что такое компост?"
  },
  "choices": [
    {
      "choice_text": {
        "az": "Üzvi tullantıların parçalanması",
        "en": "Decomposition of organic waste",
        "ru": "Разложение органических отходов"
      }
    }
  ]
}
```

---

## 📝 Sual Tipləri

Backend-də **4 əsas sual tipi** dəstəklənir:

### 1. **Single Choice (Çoxseçimli - Tək Cavab)**

**Açıqlama:** Tələbə bir cavab seçməlidir.

**Xüsusiyyətləri:**
- `question_type`: `"single_choice"`
- Ən azı 2 variant olmalıdır
- Tam olaraq 1 düzgün cavab olmalıdır
- Radio button kimi göstərilir

**Nümunə (Çoxdilli):**
```json
{
  "question_text": {
    "az": "Kompost nədir?",
    "en": "What is compost?",
    "ru": "Что такое компост?"
  },
  "question_type": "single_choice",
  "choices": [
    {
      "choice_text": {
        "az": "Üzvi tullantıların parçalanması",
        "en": "Decomposition of organic waste",
        "ru": "Разложение органических отходов"
      },
      "is_correct": true,
      "explanation": {
        "az": "Kompost üzvi tullantıların təbii parçalanması prosesidir",
        "en": "Compost is the natural decomposition process of organic waste",
        "ru": "Компост - это естественный процесс разложения органических отходов"
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
}
```

**Nümunə (Sadə - yalnız Azərbaycan dili):**
```json
{
  "question_text": "Kompost nədir?",
  "question_type": "single_choice",
  "choices": [
    {"choice_text": "Üzvi tullantıların parçalanması", "is_correct": true},
    {"choice_text": "Kimyəvi gübrə", "is_correct": false}
  ]
}
```
**Qeyd:** Əgər sadə string göndərilərsə, o avtomatik olaraq default dil (`az`) kimi saxlanılır.

**Cavab Formatı:**
```json
{
  "question_id": 5,
  "choice_id": 11  // Seçilmiş variantın ID-si
}
```

**Bal Hesablama:**
- Düzgün cavab seçilsə: tam bal (sualın `points` dəyəri)
- Yanlış və ya boş cavab: 0 bal

---

### 2. **Multiple Choice (Çoxseçimli - Çox Cavab)**

**Açıqlama:** Tələbə bir neçə cavab seçə bilər. Bütün düzgün cavablar seçilməli və heç bir yanlış seçilməməlidir.

**Xüsusiyyətləri:**
- `question_type`: `"multiple_choice"`
- Ən azı 2 variant olmalıdır
- Bir neçə düzgün cavab ola bilər
- Checkbox kimi göstərilir

**Nümunə:**
```json
{
  "question_text": "Torpaq sağlamlığına hansı amillər təsir edir?",
  "question_type": "multiple_choice",
  "choices": [
    {"choice_text": "pH səviyyəsi", "is_correct": true},
    {"choice_text": "Üzvi maddə miqdarı", "is_correct": true},
    {"choice_text": "Suvarma tezliyi", "is_correct": false}
  ]
}
```

**Cavab Formatı:**
```json
{
  "question_id": 6,
  "choice_ids": [15, 16]  // Seçilmiş variantların ID-ləri array kimi
}
```

**Bal Hesablama:**
- **Bütün düzgün cavablar seçilsə VƏ heç bir yanlış seçilməsə:** tam bal
- **Hər hansı düzgün cavab seçilməsə VƏ ya yanlış seçilsə:** 0 bal
- **Qismən düzgün:** 0 bal (qismən bal sistemi yoxdur)

---

### 3. **True/False (Doğru/Yanlış)**

**Açıqlama:** Tələbə Doğru və ya Yanlış seçməlidir.

**Xüsusiyyətləri:**
- `question_type`: `"true_false"`
- Həmişə 2 variant var (Doğru və Yanlış)
- Radio button kimi göstərilir

**Nümunə:**
```json
{
  "question_text": "Kompost təbii gübrədir.",
  "question_type": "true_false",
  "choices": [
    {"choice_text": "Doğru", "is_correct": true},
    {"choice_text": "Yanlış", "is_correct": false}
  ]
}
```

**Cavab Formatı:**
```json
{
  "question_id": 7,
  "choice_id": 20  // "Doğru" və ya "Yanlış" variantının ID-si
}
```

**Bal Hesablama:**
- Düzgün cavab seçilsə: tam bal
- Yanlış cavab: 0 bal

---

### 4. **Text (Açıq Cavab - Mətn)**

**Açıqlama:** Tələbə mətn şəklində açıq cavab yazmalıdır.

**Xüsusiyyətləri:**
- `question_type`: `"text"`
- Variant yoxdur (choices yoxdur)
- Textarea kimi göstərilir
- **Avtomatik qiymətləndirilmir** - Admin manual qiymətləndirir

**Nümunə:**
```json
{
  "question_text": "Kompost hazırlanmasının mərhələlərini izah edin.",
  "question_type": "text",
  "points": 10
}
```

**Cavab Formatı:**
```json
{
  "question_id": 8,
  "answer_text": "Kompost hazırlamaq üçün üzvi tullantıları..."  // Mətn cavabı
}
```

**Bal Hesablama:**
- **İlkin:** Cavab təqdim edildikdə 0 bal verilir
- **Final:** Admin manual qiymətləndirir və bal verir
- Admin qiymətləndirməsindən sonra bal hesablanır

---

## 🎯 İmtahan Prosesi

### Addım 1: İmtahan Yaratma (Admin/Trainer)

**Endpoint:** `POST /api/v1/exams`

**Proses:**
1. Admin imtahanın əsas məlumatlarını doldurur:
   - Başlıq, təsvir
   - Keçid balı (passing_score: 0-100%)
   - Müddət (duration_minutes)
   - Başlama və bitmə tarixləri
   - Maksimum cəhd sayı (max_attempts)

2. Suallar əlavə olunur:
   - Sual mətni
   - Sual tipi seçilir
   - Çətinlik səviyyəsi (easy/medium/hard)
   - Bal (points)
   - Variantlar (choice tipləri üçün)

3. Parametrlər təyin olunur:
   - `exam_question_count`: İmtahanda göstəriləcək sual sayı
   - `shuffle_questions`: Sualları qarışdırmaq
   - `shuffle_choices`: Variantları qarışdırmaq
   - `auto_submit`: Vaxt bitdikdə avtomatik təqdim
   - `show_correct_answers`: Düzgün cavabları göstərmək
   - `show_explanations`: İzahları göstərmək

**Validasiya:**
- `exam_question_count` ümumi sual sayından çox ola bilməz
- Choice tipli suallarda ən azı 1 düzgün cavab olmalıdır
- Text suallarda variant olmamalıdır

---

### Addım 2: Tələbənin Qeydiyyatı

**Endpoint:** `POST /api/v1/exams/{id}/register`

**Proses:**
- Tələbə imtahana qeydiyyatdan keçir
- `exam_registrations` cədvəlində yeni qeyd yaradılır
- Status: `"approved"` və ya `"pending"`

---

### Addım 3: İmtahanı Başlatma

**Endpoint:** `POST /api/v1/exams/{id}/start`

**Proses:**
1. Sistem maksimum cəhd sayını yoxlayır
2. Sessiya yaradılır:
   - `status`: `"in_progress"`
   - `started_at`: Cari vaxt qeyd olunur
   - `attempt_number`: Cəhd sayı artırılır

3. Suallar seçilir:
   - `exam_question_count` sayda sual seçilir
   - Əgər `shuffle_questions` aktivdirsə, suallar qarışdırılır
   - Seçilmiş suallar `selected_question_ids`-ə yazılır

4. Variantlar qarışdırılır (əgər aktivdirsə)

---

### Addım 4: Sualları Almaq

**Endpoint:** `GET /api/v1/exams/{id}/take`

**Cavabda:**
- Suallar (düzgün cavablar gizlədir)
- Variantlar (is_correct sahəsi yoxdur)
- Vaxt məlumatı:
  - `time_elapsed_minutes`: Keçən vaxt
  - `time_remaining_minutes`: Qalan vaxt
  - `time_exceeded`: Vaxt bitib ya yox

---

### Addım 5: Cavabları Təqdim Etmək

**Endpoint:** `POST /api/v1/exams/{id}/submit`

**Göndərilən Məlumat:**
```json
{
  "answers": [
    {
      "question_id": 5,
      "choice_id": 11  // Single choice və ya true/false üçün
    },
    {
      "question_id": 6,
      "choice_ids": [15, 16]  // Multiple choice üçün
    },
    {
      "question_id": 8,
      "answer_text": "Cavab mətni..."  // Text üçün
    }
  ]
}
```

**Proses:**
1. Vaxt yoxlanılır - əgər vaxt bitibsə, status `"timeout"` olur
2. Hər cavab üçün:
   - `ExamUserAnswer` qeydi yaradılır
   - Avtomatik qiymətləndirmə edilir (text istisna olmaqla)
   - `is_correct` sahəsi doldurulur

3. Bal hesablanır:
   - Avtomatik qiymətləndirilən suallar üçün bal hesablanır
   - Text suallar üçün 0 bal (sonradan admin qiymətləndirir)
   - Ümumi bal: `(düzgün cavab sayı / ümumi sual sayı) * 100`

4. Nəticə müəyyən olunur:
   - `score >= passing_score` → `"passed"`
   - `score < passing_score` → `"failed"`
   - Vaxt bitibsə → `"timeout"`

5. Sertifikat yaradılır (əgər keçibsə):
   - PDF sertifikat generasiya olunur
   - QR kod əlavə olunur
   - `certificates` cədvəlinə yazılır

**Cavab:**
```json
{
  "status": "passed",
  "score": 85,
  "total_questions": 10,
  "correct_answers": 8.5,
  "certificate": {
    "id": 123,
    "pdf_url": "/storage/certificates/..."
  }
}
```

---

### Addım 6: Manual Qiymətləndirmə (Admin)

**Endpoint:** `POST /api/v1/admin/exams/{id}/registrations/{registration_id}/grade-text-answers`

**Proses:**
1. Admin text sualların cavablarını görür
2. Hər cavab üçün:
   - `is_correct`: true/false
   - `feedback`: Admin rəyi

3. Yenidən bal hesablanır:
   - Avtomatik qiymətləndirilən suallar + manual qiymətləndirilən suallar
   - Ümumi bal yenidən hesablanır

4. Status yenilənir (əgər lazyiqdirsə)

---

## 💯 Bal Hesablama Sistemi

### Hər Sual Tipi Üçün Bal Hesablama:

#### Single Choice:
```php
if (seçilmiş_variant.is_correct == true) {
    bal = sual.points  // Tam bal
} else {
    bal = 0  // 0 bal
}
```

#### Multiple Choice:
```php
düzgün_variantlar = sual.choices.where('is_correct', true).count()
seçilmiş_düzgün = seçilmiş_variantlar.intersect(düzgün_variantlar).count()
seçilmiş_yanlış = seçilmiş_variantlar.diff(düzgün_variantlar).count()

if (seçilmiş_düzgün == düzgün_variantlar && seçilmiş_yanlış == 0) {
    bal = sual.points  // Tam bal
} else {
    bal = 0  // 0 bal (qismən bal yoxdur)
}
```

#### True/False:
```php
if (seçilmiş_variant.is_correct == true) {
    bal = sual.points  // Tam bal
} else {
    bal = 0  // 0 bal
}
```

#### Text:
```php
// İlkin
bal = 0  // Avtomatik 0 bal

// Admin qiymətləndirməsindən sonra
if (admin.is_correct == true) {
    bal = sual.points  // Tam bal
} else {
    bal = 0  // 0 bal
}
```

### Ümumi Bal Hesablanması:

```
ümumi_düzgün_sayı = avtomatik_düzgün + manual_düzgün
ümumi_bal = (ümumi_düzgün_sayı / ümumi_sual_sayı) * 100
```

---

## 🗄️ Verilənlər Bazası Strukturu

### exams (İmtahanlar)
- `id` - İmtahan ID
- `training_id` - Təlim ID (nullable - müstəqil imtahanlar üçün)
- `category` - Kateqoriya (müstəqil imtahanlar üçün)
- `title` - Başlıq
- `description` - Təsvir
- `passing_score` - Keçid balı (0-100)
- `duration_minutes` - Müddət (dəqiqə)
- `start_date` - Başlama tarixi
- `end_date` - Bitmə tarixi
- `max_attempts` - Maksimum cəhd sayı
- `exam_question_count` - Göstəriləcək sual sayı
- `shuffle_questions` - Sualları qarışdır
- `shuffle_choices` - Variantları qarışdır
- `auto_submit` - Avtomatik təqdim
- `show_correct_answers` - Düzgün cavabları göstər
- `show_explanations` - İzahları göstər

### exam_questions (Suallar)
- `id` - Sual ID
- `exam_id` - İmtahan ID
- `question_text` - Sual mətni (JSON - çoxdilli)
- `question_type` - Sual tipi (single_choice, multiple_choice, true_false, text)
- `difficulty` - Çətinlik (easy, medium, hard)
- `points` - Bal
- `is_required` - Məcburi sual
- `sequence` - Sıra
- `question_media` - Media fayllar (JSON)
- `explanation` - İzah (JSON - çoxdilli)
- `metadata` - Əlavə məlumatlar (JSON)

### exam_choices (Variantlar)
- `id` - Variant ID
- `question_id` - Sual ID
- `choice_text` - Variant mətni (JSON - çoxdilli)
- `is_correct` - Düzgün cavab
- `points` - Bal (variant üzrə)
- `choice_media` - Media fayllar (JSON)
- `explanation` - İzah (JSON - çoxdilli)
- `metadata` - Əlavə məlumatlar (JSON)

### exam_registrations (Qeydiyyatlar)
- `id` - Qeydiyyat ID
- `user_id` - İstifadəçi ID
- `exam_id` - İmtahan ID
- `status` - Status (approved, in_progress, completed, passed, failed, timeout)
- `score` - Bal (0-100)
- `started_at` - Başlama vaxtı
- `finished_at` - Bitmə vaxtı
- `attempt_number` - Cəhd sayı
- `selected_question_ids` - Seçilmiş sualların ID-ləri (JSON array)
- `total_questions` - Ümumi sual sayı
- `certificate_id` - Sertifikat ID

### exam_user_answers (Tələbə Cavabları)
- `id` - Cavab ID
- `registration_id` - Qeydiyyat ID
- `question_id` - Sual ID
- `choice_id` - Seçilmiş variant ID (single choice və true/false üçün)
- `choice_ids` - Seçilmiş variantların ID-ləri (JSON array - multiple choice üçün)
- `answer_text` - Mətn cavabı (text suallar üçün)
- `is_correct` - Düzgün cavab (avtomatik və ya manual)
- `answered_at` - Cavab verilmə vaxtı
- `needs_manual_grading` - Manual qiymətləndirmə lazımdır (text suallar üçün)
- `admin_feedback` - Admin rəyi
- `graded_at` - Qiymətləndirmə vaxtı
- `graded_by` - Qiymətləndirən admin ID

### certificates (Sertifikatlar)
- `id` - Sertifikat ID
- `user_id` - İstifadəçi ID
- `exam_id` - İmtahan ID
- `certificate_number` - Sertifikat nömrəsi
- `pdf_path` - PDF fayl yolu
- `pdf_url` - PDF URL
- `qr_code_path` - QR kod yolu
- `expiry_date` - Müddəti bitmə tarixi

---

## 🔌 API Endpoint-ləri

### Admin/Trainer Endpoint-ləri:

1. **Statistika:**
   - `GET /api/v1/exams/stats` - Dashboard statistikaları

2. **Form Məlumatları:**
   - `GET /api/v1/exams/form-data` - Dropdown məlumatları (kateqoriyalar, təlimlər)

3. **İmtahan İdarəetməsi:**
   - `GET /api/v1/exams` - İmtahanlar siyahısı
   - `POST /api/v1/exams` - Yeni imtahan yaratmaq
   - `GET /api/v1/exams/{id}` - İmtahan detalları
   - `PUT /api/v1/exams/{id}` - İmtahanı yeniləmək
   - `DELETE /api/v1/exams/{id}` - İmtahanı silmək

4. **Sual İdarəetməsi:**
   - `POST /api/v1/exams/{id}/questions` - Yeni sual əlavə etmək
   - `PUT /api/v1/exams/{id}/questions/{question_id}` - Sualı yeniləmək
   - `DELETE /api/v1/exams/{id}/questions/{question_id}` - Sualı silmək

5. **Manual Qiymətləndirmə:**
   - `GET /api/v1/admin/exams/{id}/registrations/{registration_id}` - Qeydiyyat detalları
   - `POST /api/v1/admin/exams/{id}/registrations/{registration_id}/grade-text-answers` - Text cavabları qiymətləndirmək

### Tələbə Endpoint-ləri:

1. **Qeydiyyat:**
   - `POST /api/v1/exams/{id}/register` - İmtahana qeydiyyatdan keçmək

2. **İmtahan Vermək:**
   - `POST /api/v1/exams/{id}/start` - İmtahanı başlatmaq
   - `GET /api/v1/exams/{id}/take` - Sualları almaq
   - `POST /api/v1/exams/{id}/submit` - Cavabları təqdim etmək

3. **Nəticələr:**
   - `GET /api/v1/certificates` - Sertifikatlar siyahısı
   - `GET /api/v1/certificates/{id}` - Sertifikat detalları

---

## 📊 Xülasə

**Sual Tipləri:**
- ✅ **Single Choice** - Tək seçim, avtomatik qiymətləndirmə
- ✅ **Multiple Choice** - Çox seçim, avtomatik qiymətləndirmə
- ✅ **True/False** - Doğru/Yanlış, avtomatik qiymətləndirmə
- ✅ **Text** - Açıq cavab, manual qiymətləndirmə

**Bal Sistemi:**
- Tam bal: Sualın `points` dəyəri
- Qismən bal: Yoxdur (ya tam ya 0)
- Text suallar: Admin qiymətləndirməsindən sonra

**İmtahan Prosesi:**
1. Yaratma → 2. Qeydiyyat → 3. Başlatma → 4. Cavab vermə → 5. Təqdim → 6. Nəticə

**Xüsusiyyətlər:**
- ✅ **Çoxdilli dəstək** - Azərbaycan, İngilis, Rus dilləri (az, en, ru)
- ✅ **Media fayllar** - Şəkil, video, audio dəstəyi
- ✅ **Vaxt nəzarəti** - Müddət məhdudiyyəti və avtomatik təqdim
- ✅ **Maksimum cəhd sayı** - Hər tələbə üçün məhdud cəhd
- ✅ **Avtomatik və manual qiymətləndirmə** - Çoxseçimli avtomatik, mətn manual
- ✅ **Sertifikat generasiya** - PDF sertifikat və QR kod

---

## 🌐 Çoxdilli Sistemin İstifadəsi

### API Request-lərdə Dil Seçimi

**Metod 1: Query Parameter**
```http
GET /api/v1/exams/{id}/take?lang=en
```

**Metod 2: Header**
```http
Accept-Language: en
```

**Metod 3: Request Body (Yaratma/Yeniləmə zamanı)**
```json
{
  "title": {
    "az": "İmtahan başlığı",
    "en": "Exam title",
    "ru": "Название экзамена"
  }
}
```

### Response-da Dil

API response-da həmişə cari dilin tərcüməsi göstərilir. Əgər cari dil yoxdursa, default dil (`az`) göstərilir.

**Nümunə Response:**
```json
{
  "id": 1,
  "question_text": "Kompost nədir?",  // Azərbaycan dili (default)
  "choices": [
    {
      "choice_text": "Üzvi tullantıların parçalanması"
    }
  ]
}
```

**English üçün:**
```http
GET /api/v1/exams/1/take?lang=en
```

```json
{
  "id": 1,
  "question_text": "What is compost?",  // English
  "choices": [
    {
      "choice_text": "Decomposition of organic waste"
    }
  ]
}
```

### Tam Tərcümələri Almaq

Bütün tərcümələri almaq üçün `getFullTranslation()` metodu istifadə olunur. API-də bu adətən xüsusi endpoint-də və ya admin paneldə göstərilir.

**Nümunə:**
```json
{
  "question_text_full": {
    "az": "Kompost nədir?",
    "en": "What is compost?",
    "ru": "Что такое компост?"
  }
}
```
