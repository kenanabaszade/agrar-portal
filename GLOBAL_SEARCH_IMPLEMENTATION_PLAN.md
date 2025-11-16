# Global Search API - İmplementasiya Planı

## Sualların Cavabları

### 1. Axtarış Metodları

**Tövsiyə: (b) Ümumi metod ilə tipə görə switch**

**Səbəb:**
- Daha sadə və maintainable kod
- Bir yerdə bütün axtarış logikası
- Daha asan test edilir
- Performance fərqi minimal (paralel query-lər istifadə edilə bilər)

**Alternativ (a) istifadə edilərsə:**
- Hər tip üçün ayrı metod yaradılmalıdır
- Daha modulyar, lakin daha çox kod
- Hər metod üçün ayrıca test yazılmalıdır

**Qərar:** Ümumi metod ilə switch istifadə edin. Hər tip üçün ayrı private helper metod yarada bilərsiniz, lakin əsas axtarış metodu bir olsun.

---

### 2. Multilang Field-lərin İşlənməsi

**Tövsiyə: (a) HasTranslations trait-də mövcud metodları istifadə etmək**

**Səbəb:**
- Artıq mövcud sistem var
- Consistency (tutarlılıq) üçün
- Kod təkrarlanmır
- Digər endpoint-lərdə də eyni sistem işləyir

**Əgər mövcud metodlar kifayət etmirsə:**
- Yeni helper metod yarada bilərsiniz
- Lakin HasTranslations trait-dən inherit edin

**Qərar:** HasTranslations trait-dəki mövcud metodları istifadə edin. Əgər `lang` parametri varsa, artıq translate edilmiş string qaytaran metod istifadə edin.

---

### 3. Response Formatı

**Tövsiyə: (a) Sadə array (faylda göstərildiyi kimi)**

**Səbəb:**
- Daha sürətli (transformasiya yoxdur)
- Daha sadə kod
- Frontend-də parsing asandır
- Axtarış nəticələri üçün Resource class-larına ehtiyac yoxdur

**Resource class-ları nə zaman istifadə olunur:**
- Detal səhifələri üçün (məsələn, training detail, exam detail)
- Axtarış nəticələri üçün lazım deyil

**Qərar:** Sadə array formatı istifadə edin. Hər tip üçün minimal field-lər qaytarın (spesifikasiyada göstərildiyi kimi).

---

## Results Tipi Haqqında

**Results tipi iki mənbədən gəlir:**

1. **Training Results:**
   - **Table:** `training_registrations`
   - **Şərt:** `is_completed = true` və ya `completion_percentage = 100`
   - **Axtarış:** `training.title`, `training.category` (join ilə)
   - **Qaytarılmalı:** `training.title`, `training.category`, `completion_percentage` (score kimi), `completed_at`

2. **Exam Results:**
   - **Table:** `exam_registrations` və ya `exam_attempts`
   - **Şərt:** Tamamlanmış imtahanlar (status = 'completed')
   - **Axtarış:** `exam.title`, `exam.category` (join ilə)
   - **Qaytarılmalı:** `exam.title`, `exam.category`, `score`, `completed_at`

**Qeyd:** Results tipi üçün həm training, həm də exam nəticələrini birləşdirmək lazımdır. Frontend-də `course` obyekti altında göstərilir.

---

## İmplementasiya Planı

### Addım 1: Controller və Route

**Fayl:** `app/Http/Controllers/Api/V1/SearchController.php`

```php
// Route: GET /api/v1/search/global
public function globalSearch(Request $request)
{
    // Validation
    // Axtarış logikası
    // Response qaytarma
}
```

**Validation:**
- `q` parametri: required, min:2, max:255
- `lang` parametri: optional, in:az,en,ru
- `exclude_types` parametri: optional, string (comma-separated)
- `limit` parametri: optional, integer, min:1, max:20, default:10

---

### Addım 2: Axtarış Metodunun Strukturu

**Ümumi struktur:**

```php
public function globalSearch(Request $request)
{
    // 1. Validation
    $validated = $request->validate([...]);
    
    // 2. Parametrləri parse et
    $query = $validated['q'];
    $lang = $validated['lang'] ?? 'az';
    $excludeTypes = explode(',', $validated['exclude_types'] ?? 'certificates');
    $limit = $validated['limit'] ?? 10;
    
    // 3. Results array-i yarat
    $results = [];
    
    // 4. Hər tip üçün axtarış (paralel və ya sequential)
    if (!in_array('video_trainings', $excludeTypes)) {
        $results['video_trainings'] = $this->searchVideoTrainings($query, $lang, $limit);
    }
    
    if (!in_array('online_trainings', $excludeTypes)) {
        $results['online_trainings'] = $this->searchOnlineTrainings($query, $lang, $limit);
    }
    
    // ... digər tiplər
    
    // 5. Response qaytar
    return response()->json([
        'data' => $results,
        'meta' => [
            'query' => $query,
            'total' => array_sum(array_map('count', $results)),
            'excluded_types' => $excludeTypes
        ]
    ]);
}
```

---

### Addım 3: Hər Tip Üçün Axtarış Metodları

**Nümunə: Video Trainings**

```php
private function searchVideoTrainings(string $query, string $lang, int $limit): array
{
    return Training::where('type', 'video')
        ->where(function($q) use ($query) {
            $q->where('title', 'ILIKE', "%{$query}%")
              ->orWhere('description', 'ILIKE', "%{$query}%")
              ->orWhere('category', 'ILIKE', "%{$query}%")
              ->orWhereHas('trainer', function($trainerQuery) use ($query) {
                  $trainerQuery->where('first_name', 'ILIKE', "%{$query}%")
                               ->orWhere('last_name', 'ILIKE', "%{$query}%");
              });
        })
        ->with(['trainer:id,first_name,last_name'])
        ->limit($limit)
        ->get()
        ->map(function($training) use ($lang) {
            return [
                'id' => $training->id,
                'title' => $this->getTranslatedField($training->title, $lang),
                'description' => $this->getTranslatedField($training->description, $lang),
                'category' => $this->getTranslatedField($training->category, $lang),
                'image' => $training->image,
                'trainer' => [
                    'id' => $training->trainer->id,
                    'first_name' => $this->getTranslatedField($training->trainer->first_name, $lang),
                    'last_name' => $this->getTranslatedField($training->trainer->last_name, $lang),
                ],
                'difficulty' => $training->difficulty,
                'duration' => $training->duration
            ];
        })
        ->toArray();
}
```

**Qeyd:** `getTranslatedField()` metodu HasTranslations trait-dən gəlir və ya yeni helper metod ola bilər.

---

### Addım 4: Multilang Field-lərin İşlənməsi

**Helper metod (HasTranslations trait-də varsa istifadə edin):**

```php
private function getTranslatedField($field, string $lang): string
{
    // Əgər field artıq string-dirsə (lang parametrinə görə translate edilibsə)
    if (is_string($field)) {
        return $field;
    }
    
    // Əgər field JSON object-dirsə
    if (is_array($field) || is_object($field)) {
        $fieldArray = (array) $field;
        
        // Əvvəlcə seçilmiş dil
        if (isset($fieldArray[$lang])) {
            return (string) $fieldArray[$lang];
        }
        
        // Fallback: az → en → ru → first available
        $fallbackOrder = ['az', 'en', 'ru'];
        foreach ($fallbackOrder as $fallbackLang) {
            if (isset($fieldArray[$fallbackLang])) {
                return (string) $fieldArray[$fallbackLang];
            }
        }
        
        // Əgər heç biri yoxdursa, ilk mövcud dəyəri götür
        if (!empty($fieldArray)) {
            return (string) reset($fieldArray);
        }
    }
    
    return '';
}
```

**Və ya HasTranslations trait-dəki mövcud metod:**

```php
// Məsələn, trait-də belə bir metod varsa:
$training->getTranslation('title', $lang)
```

---

### Addım 5: Results Tipi Üçün Xüsusi Metod

**Results tipi iki mənbədən gəlir:**

```php
private function searchResults(string $query, string $lang, int $limit): array
{
    $results = [];
    
    // 1. Training Results
    $trainingResults = TrainingRegistration::where('is_completed', true)
        ->orWhere('completion_percentage', 100)
        ->whereHas('training', function($q) use ($query) {
            $q->where('title', 'ILIKE', "%{$query}%")
              ->orWhere('category', 'ILIKE', "%{$query}%");
        })
        ->with(['training:id,title,category'])
        ->limit($limit / 2) // Yarısı training, yarısı exam
        ->get()
        ->map(function($registration) use ($lang) {
            return [
                'id' => $registration->id,
                'course' => [
                    'title' => $this->getTranslatedField($registration->training->title, $lang),
                    'category' => $this->getTranslatedField($registration->training->category, $lang),
                ],
                'score' => $registration->completion_percentage,
                'completed_at' => $registration->completed_at,
                'type' => 'training'
            ];
        });
    
    // 2. Exam Results
    $examResults = ExamRegistration::where('status', 'completed')
        ->whereHas('exam', function($q) use ($query) {
            $q->where('title', 'ILIKE', "%{$query}%")
              ->orWhere('category', 'ILIKE', "%{$query}%");
        })
        ->with(['exam:id,title,category'])
        ->limit($limit / 2)
        ->get()
        ->map(function($registration) use ($lang) {
            return [
                'id' => $registration->id,
                'course' => [
                    'title' => $this->getTranslatedField($registration->exam->title, $lang),
                    'category' => $this->getTranslatedField($registration->exam->category, $lang),
                ],
                'score' => $registration->score,
                'completed_at' => $registration->completed_at,
                'type' => 'exam'
            ];
        });
    
    // 3. Birləşdir və limit tətbiq et
    return $trainingResults->merge($examResults)
        ->take($limit)
        ->values()
        ->toArray();
}
```

---

### Addım 6: Performance Optimizasiyası

1. **Database Index-ləri:**
   - `trainings.title`, `trainings.category` üçün index
   - `trainings.type` üçün index
   - `trainings.trainer_id` üçün foreign key index
   - Hər tip üçün axtarış edilən sahələr üçün index

2. **Paralel Query-lər:**
   - Mümkündürsə, fərqli tiplərdə axtarış paralel aparın
   - Laravel-də `DB::transaction()` və ya queue istifadə edə bilərsiniz

3. **Caching:**
   - Tez-tez soruşulan sorğular üçün cache (Redis)
   - Cache key: `search:global:{query}:{lang}:{limit}`

---

### Addım 7: Test Edilməsi

**Test ediləcək ssenarilər:**

1. ✅ Valid request (q parametri ilə)
2. ✅ Invalid request (q < 2 simvol)
3. ✅ Boş nəticə (tapılmayan sorğu)
4. ✅ Multilang field-lərin düzgün translate olunması
5. ✅ Exclude types düzgün işləyir
6. ✅ Limit düzgün tətbiq olunur
7. ✅ Results tipi həm training, həm exam nəticələrini qaytarır
8. ✅ Performance test (response time < 500ms)

---

## Qısa Xülasə

1. **Axtarış metodu:** Ümumi metod + switch (hər tip üçün private helper)
2. **Multilang:** HasTranslations trait-dəki mövcud metodlar
3. **Response:** Sadə array formatı
4. **Results:** Həm `training_registrations`, həm də `exam_registrations` table-larından
5. **Performance:** Index-lər, caching, paralel query-lər

---

## Növbəti Addımlar

1. Controller və route yaradın
2. Validation əlavə edin
3. Hər tip üçün axtarış metodunu yazın
4. Multilang helper metodunu yoxlayın/yaradın
5. Results tipi üçün xüsusi metod yazın
6. Test edin
7. Performance optimizasiyası edin

**Müddət təxmini:** 4-6 saat (təcrübəyə görə)

---

**Son yeniləmə:** 2024

