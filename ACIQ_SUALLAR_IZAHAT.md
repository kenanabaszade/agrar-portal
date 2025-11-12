# Açıq Suallar (Text Questions) Sisteminin İzahı

## 📋 Ümumi Məlumat

İmtahan sistemində **açıq suallar** (`question_type = 'text'`) istifadəçinin sərbəst mətn cavabı yazmasını tələb edir. Bu suallar avtomatik qiymətləndirilə bilməz və **admin tərəfindən manual qiymətləndirilməyə** ehtiyac duyur.

---

## 🔄 Sistemin İşləmə Axını

### **1. İstifadəçi İmtahanı Təqdim Edir**

**Endpoint:** `POST /api/exams/{exam}/submit`

**Controller:** `ExamController@submit`

**Proses:**
1. İstifadəçi imtahanı bitirib cavabları göndərir
2. Hər bir cavab üçün:
   - Əgər sual tipi `text` (açıq sual) olarsa:
     - `answer_text` bazaya yazılır
     - `needs_manual_grading = true` qeyd edilir
     - `calculatePoints()` metodu `null` qaytarır (manual grading lazımdır)
   - Əgər sual tipi başqa tipdirsə (single_choice, multiple_choice, true_false):
     - Avtomatik qiymətləndirilir
     - Dərhal bal hesablanır

**Kod yeri:** `app/Http/Controllers/ExamController.php:1536-1569`

```php
// Açıq sual yoxlanılır
if ($question->question_type === 'text') {
    $hasTextQuestions = true;
    $textQuestionsCount++;
}

// Points hesablanır
$questionPoints = $question->calculatePoints($ans);

// Əgər null qayıdırsa, manual grading lazımdır
if ($questionPoints === null) {
    $earnedPoints += 0; // Hələ bal verilmir
} else {
    $earnedPoints += $questionPoints;
}

// Cavab bazaya yazılır
ExamUserAnswer::updateOrCreate([
    'registration_id' => $registration->id,
    'question_id' => $question->id,
], [
    'answer_text' => $ans['answer_text'] ?? null,
    'needs_manual_grading' => $question->question_type === 'text' && !empty(trim($ans['answer_text'] ?? '')),
]);
```

---

### **2. İmtahan Statusunun Təyini**

**Kod yeri:** `app/Http/Controllers/ExamController.php:1576-1595`

**Status müəyyənləşdirilməsi:**
- **Açıq suallar varsa:**
  - `status = 'pending_review'` (gözləyən qiymətləndirmə)
  - `needs_manual_grading = true`
  - `auto_graded_score` = yalnız avtomatik qiymətləndirilən sualların balı
  - Final bal hələ hesablanmır

- **Açıq suallar yoxdursa:**
  - `status = 'passed'` və ya `'failed'` (dərhal)
  - Final bal hesablanır
  - Keçid olsa, sertifikat yaradılır

```php
if ($hasTextQuestions) {
    // Açıq suallar üçün partial score
    $autoGradedPoints = $totalPoints - ($textQuestionsCount * 1);
    $score = $autoGradedPoints > 0 ? (int) floor(($earnedPoints / $autoGradedPoints) * 100) : 0;
    $passed = false; // Manual gradingdən sonra müəyyənləşəcək
    $finalStatus = 'pending_review';
} else {
    // Bütün suallar avtomatik qiymətləndirilir
    $score = $totalPoints > 0 ? (int) floor(($earnedPoints / $totalPoints) * 100) : 0;
    $passed = $score >= (int) $exam->passing_score;
    $finalStatus = $passed ? 'passed' : 'failed';
}
```

---

### **3. Admin Gözləyən İmtahanları Görür**

**Endpoint:** `GET /api/admin/exams/pending-reviews`

**Controller:** `AdminExamController@getPendingReviews`

**Proses:**
- `status = 'pending_review'` olan bütün imtahanlar göstərilir
- İstifadəçi məlumatları (ad, soyad, email) ilə birlikdə
- Pagination ilə (20 səhifə)

**Kod yeri:** `app/Http/Controllers/AdminExamController.php:156-167`

```php
$pendingExams = ExamRegistration::where('status', 'pending_review')
    ->with(['user:id,first_name,last_name,email', 'exam:id,title,passing_score'])
    ->orderBy('created_at', 'desc')
    ->paginate(20);
```

---

### **4. Admin İmtahanın Detallarını Görür**

**Endpoint:** `GET /api/admin/exams/{registrationId}/for-grading`

**Controller:** `AdminExamController@getExamForGrading`

**Proses:**
- İmtahan qeydiyyatının tam məlumatları
- İstifadəçi məlumatları
- İmtahan məlumatları
- **Yalnız açıq suallar və onların cavabları** göstərilir

**Kod yeri:** `app/Http/Controllers/AdminExamController.php:172-223`

**Response strukturu:**
```json
{
  "message": "Exam data retrieved successfully",
  "registration": {
    "id": 1,
    "user": { "id": 1, "first_name": "Ad", "last_name": "Soyad", "email": "email@example.com" },
    "exam": { "id": 1, "title": "İmtahan adı", "passing_score": 70 },
    "score": 65,  // Avtomatik qiymətləndirilən sualların balı
    "auto_graded_score": 65,
    "started_at": "2024-11-05 10:00:00",
    "finished_at": "2024-11-05 11:00:00",
    "attempt_number": 1
  },
  "text_questions": [
    {
      "id": 123,  // ExamUserAnswer ID
      "question_id": 45,
      "question_text": { "az": "Sual mətni..." },
      "answer_text": "İstifadəçinin cavabı...",
      "answered_at": "2024-11-05 11:00:00"
    }
  ]
}
```

---

### **5. Admin Açıq Sualları Qiymətləndirir**

**Endpoint:** `POST /api/admin/exams/{registrationId}/grade-text-questions`

**Controller:** `AdminExamController@gradeTextQuestions`

**Request formatı:**
```json
{
  "grades": [
    {
      "answer_id": 123,  // ExamUserAnswer ID
      "is_correct": true,  // true və ya false
      "feedback": "Yaxşı cavab, lakin daha ətraflı ola bilərdi"  // İxtiyari
    },
    {
      "answer_id": 124,
      "is_correct": false,
      "feedback": "Düzgün cavab deyil"
    }
  ],
  "admin_notes": "Ümumi qeydlər..."  // İxtiyari
}
```

**Proses:**
1. Hər bir açıq sual üçün:
   - `is_correct` (doğru/yanlış) qeyd edilir
   - `admin_feedback` (admin rəyi) yazılır
   - `graded_at` (qiymətləndirmə vaxtı) qeyd edilir
   - `graded_by` (admin ID) qeyd edilir

2. Final bal hesablanır:
   - Avtomatik qiymətləndirilən sualların doğru sayı + Açıq sualların doğru sayı
   - Final bal = (Doğru suallar / Ümumi suallar) × 100

3. Status yenilənir:
   - `status = 'passed'` və ya `'failed'` (final bala görə)
   - `score` = final bal
   - `admin_notes` = adminin ümumi qeydləri
   - `graded_at` = qiymətləndirmə vaxtı
   - `graded_by` = admin ID

4. Sertifikat yaradılır (əgər keçibsə):
   - `has_certificate = true` olan training üçün
   - PDF sertifikat yaradılır
   - QR kod yaradılır

5. Email göndərilir:
   - Keçibsə: `ExamPassedMail`
   - Kəsilibsə: `ExamFailedMail`

**Kod yeri:** `app/Http/Controllers/AdminExamController.php:228-333`

---

## 📊 Database Strukturu

### **exam_user_answers** cədvəli

**Açıq suallar üçün mühüm sahələr:**
- `answer_text` (text) - İstifadəçinin yazdığı cavab
- `needs_manual_grading` (boolean) - Manual qiymətləndirmə lazımdırmı?
- `is_correct` (boolean) - Admin tərəfindən qiymətləndirildikdən sonra
- `admin_feedback` (text, nullable) - Adminin rəyi
- `graded_at` (datetime, nullable) - Qiymətləndirmə vaxtı
- `graded_by` (foreignId, nullable) - Admin ID

### **exam_registrations** cədvəli

**Açıq suallar üçün mühüm sahələr:**
- `status` (enum) - `'pending_review'`, `'passed'`, `'failed'`, və s.
- `needs_manual_grading` (boolean) - Manual qiymətləndirmə lazımdırmı?
- `auto_graded_score` (integer, nullable) - Yalnız avtomatik qiymətləndirilən sualların balı
- `score` (integer) - Final bal (manual gradingdən sonra)
- `admin_notes` (text, nullable) - Adminin ümumi qeydləri
- `graded_at` (datetime, nullable) - Qiymətləndirmə vaxtı
- `graded_by` (foreignId, nullable) - Admin ID

---

## 🔍 Model Metodları

### **ExamQuestion Model**

#### `calculatePoints($answer)`
- **Açıq suallar üçün:** `null` qaytarır (manual grading lazımdır)
- **Digər suallar üçün:** Avtomatik bal hesablayır

```php
if ($this->question_type === 'text') {
    $answerText = trim($answer['answer_text'] ?? '');
    return !empty($answerText) ? null : 0; // null = manual grading lazımdır
}
```

#### `needsManualGrading()`
- **Açıq suallar üçün:** `true` qaytarır
- **Digər suallar üçün:** `false` qaytarır

```php
public function needsManualGrading()
{
    return $this->question_type === 'text';
}
```

---

## 🎯 API Endpoint-ləri

### **İstifadəçi üçün:**
1. `POST /api/exams/{exam}/submit` - İmtahanı təqdim et
   - Açıq suallar varsa, status `pending_review` olur

### **Admin üçün:**
1. `GET /api/admin/exams/pending-reviews` - Gözləyən imtahanları gör
   - Pagination ilə (20 səhifə)
   - Status: `pending_review`

2. `GET /api/admin/exams/{registrationId}/for-grading` - İmtahan detallarını gör
   - Yalnız açıq suallar və cavabları
   - İstifadəçi və imtahan məlumatları

3. `POST /api/admin/exams/{registrationId}/grade-text-questions` - Açıq sualları qiymətləndir
   - Hər bir sual üçün `is_correct` və `feedback`
   - Final bal hesablanır
   - Status yenilənir
   - Sertifikat yaradılır (keçid olsa)
   - Email göndərilir

---

## ⚠️ Mühüm Qeydlər

1. **Avtomatik bal hesablanması:**
   - Açıq suallar olan imtahanlarda yalnız avtomatik qiymətləndirilən sualların balı hesablanır
   - Final bal manual gradingdən sonra hesablanır

2. **Status dəyişiklikləri:**
   - `in_progress` → `pending_review` (açıq suallar varsa)
   - `pending_review` → `passed` və ya `'failed'` (admin qiymətləndirdikdən sonra)

3. **Sertifikat yaradılması:**
   - Yalnız `status = 'passed'` və `has_certificate = true` olduqda
   - Manual gradingdən sonra yaradılır

4. **Email bildirişləri:**
   - İstifadəçi imtahanı bitirdikdə: Yoxdur (çünki status `pending_review`)
   - Admin qiymətləndirdikdən sonra: `ExamPassedMail` və ya `ExamFailedMail`

---

## 🔧 Son Dəyişikliklər (05.11.2024)

1. ✅ `exam_user_answers` cədvəlinə əlavə edildi:
   - `admin_feedback` (text, nullable)
   - `graded_at` (datetime, nullable)
   - `graded_by` (foreignId, nullable)

2. ✅ `exam_registrations` cədvəlinə əlavə edildi:
   - `admin_notes` (text, nullable)
   - `graded_at` (datetime, nullable)
   - `graded_by` (foreignId, nullable)

3. ✅ Model-lər yeniləndi:
   - `ExamUserAnswer` modelində `fillable` array-ə yeni sahələr əlavə edildi
   - `ExamRegistration` modelində `fillable` array-ə yeni sahələr əlavə edildi
   - `gradedBy()` relationship-ləri əlavə edildi
   - `userAnswers()` alias əlavə edildi

4. ✅ Migration faylları yaradıldı:
   - `2025_11_05_222019_add_admin_grading_fields_to_exam_user_answers_table.php`
   - `2025_11_05_222031_add_admin_grading_fields_to_exam_registrations_table.php`

---

## 📝 Nümunə İş Axını

1. **İstifadəçi imtahanı bitirir:**
   - 10 sual (7 avtomatik, 3 açıq sual)
   - 7 avtomatik sualdan 5-i doğru → 50 bal
   - 3 açıq sual cavablanır
   - Status: `pending_review`
   - `auto_graded_score`: 50

2. **Admin gözləyən imtahanları görür:**
   - `GET /api/admin/exams/pending-reviews`
   - İmtahan siyahısında görünür

3. **Admin imtahanı açır:**
   - `GET /api/admin/exams/1/for-grading`
   - 3 açıq sual və cavabları görür

4. **Admin qiymətləndirir:**
   - 3 açıq sualdan 2-si doğru
   - `POST /api/admin/exams/1/grade-text-questions`
   - Final bal: (5 avtomatik + 2 açıq) / 10 = 70%
   - Status: `passed` (70% >= 70%)
   - Sertifikat yaradılır
   - Email göndərilir

---

## ✅ Sistem Hazırdır!

Bütün lazımi sahələr database-də və model-lərdə mövcuddur. Admin tərəfindən açıq suallar düzgün qiymətləndirilə bilər.


