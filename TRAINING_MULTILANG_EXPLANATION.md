# Təlimlərdə Multilang Sistemi - İzahat

Bu sənəddə təlimlərdə (trainings) multilang sisteminin necə işlədiyi izah olunur.

## 📋 Ümumi Baxış

Təlimlərdə multilang sistemi 3 dillə işləyir:
- **az** (Azərbaycan dili) - Default dil
- **en** (İngilis dili)
- **ru** (Rus dili)

## 🏗️ Sistem Arxitekturası

### 1. **Model Səviyyəsində** (`Training` Model)

`Training` modelində `HasTranslations` trait istifadə olunur:

```php
use App\Traits\HasTranslations;

class Training extends Model
{
    use HasTranslations;
    
    // Hansı field-lər multilang olacaq?
    protected $translatable = ['title', 'description'];
    
    // Bu field-ləri JSON array kimi saxlayır
    protected $casts = [
        'title' => 'array',
        'description' => 'array',
    ];
}
```

**Nə deməkdir?**
- `title` və `description` field-ləri JSON formatında çoxdilli məlumat saxlayır
- Database-də bu field-lər JSON string kimi saxlanılır:
  ```json
  {
    "az": "Təlim Başlığı",
    "en": "Training Title",
    "ru": "Название обучения"
  }
  ```

### 2. **HasTranslations Trait**

Bu trait aşağıdakı funksiyaları təmin edir:

#### a) **getTranslated()** - Tərcümə olunmuş dəyəri almaq
```php
$training->getTranslated('title', 'en'); // İngilis dilində başlıq
$training->getTranslated('title', 'az'); // Azərbaycan dilində başlıq
```

**İş prinsipi:**
1. Əvvəlcə istədiyiniz dildə (məs: `en`) axtarır
2. Tapmasa, default dildə (`az`) axtarır
3. Həm də tapmasa, ilk mövcud dildə olan dəyəri qaytarır

#### b) **getAttribute()** - Avtomatik tərcümə
```php
$training->title; // Cari dildə tərcümə olunmuş başlıq
```

Model-dən field oxuyanda avtomatik olaraq cari dilə uyğun tərcümə qaytarılır.

**Cari dil necə müəyyən olunur?**
1. Request-də `?lang=en` parametri varsa, onu istifadə edir
2. Yoxsa `Accept-Language` header-ını yoxlayır
3. Yoxsa Laravel-in `App::getLocale()` dəyərini götürür
4. Son olaraq default `az` dilini qaytarır

### 3. **Validation Səviyyəsində** (`TranslationRule`)

Validation zamanı `TranslationRule` istifadə olunur:

```php
$validated = $request->validate([
    'title' => ['required', new \App\Rules\TranslationRule(true)],
    'description' => ['nullable', new \App\Rules\TranslationRule(false)],
]);
```

**Nə yoxlayır?**
- `true` parametri = default dil (`az`) mütləq lazımdır
- `false` parametri = default dil mütləq deyil, amma varsa düzgün formatda olmalıdır

**Validation qaydaları:**
1. Dəyər ya string, ya da array ola bilər (backward compatibility üçün)
2. Array olsa, yalnız dəstəklənən dilləri (`az`, `en`, `ru`) ehtiva etməlidir
3. Ən azı bir dilin dəyəri doldurulmalıdır

### 4. **Controller Səviyyəsində** (`TrainingController`)

#### a) **normalizeTranslationRequest()** - Request Formatlaşdırma

Frontend-dən gələn request-lər müxtəlif formatda ola bilər. Bu metod onları vahid formata çevirir:

**Format 1: Ayrı-ayrı field-lər**
```json
{
  "title_az": "Azərbaycan başlığı",
  "title_en": "English title",
  "title_ru": "Русское название"
}
```

**Format 2: Object formatı** (İstədiyimiz format)
```json
{
  "title": {
    "az": "Azərbaycan başlığı",
    "en": "English title",
    "ru": "Русское название"
  }
}
```

**Format 3: Sadə string** (Backward compatibility)
```json
{
  "title": "Sadə başlıq"
}
```

`normalizeTranslationRequest()` metodu Format 1 və Format 3-ü Format 2-yə çevirir.

#### b) **TranslationHelper::normalizeTranslation()** - Normalizasiya

Validation-dan sonra bu metod tərcümə dəyərlərini təmizləyir və formatlaşdırır:

```php
if (isset($validated['title'])) {
    $validated['title'] = TranslationHelper::normalizeTranslation($validated['title']);
}
```

**Nə edir?**
1. String olsa → `{"az": "string dəyəri"}` formatına çevirir
2. Array olsa → Yalnız dəstəklənən dilləri saxlayır və boş dəyərləri silir
3. Null olsa → Boş array qaytarır

## 📥 API Request Nümunələri

### Training Yaratmaq

**POST** `/api/v1/trainings`

**Format 1: Ayrı-ayrı field-lər**
```json
{
  "title_az": "Kənd təsərrüfatı əsasları",
  "title_en": "Agriculture Basics",
  "title_ru": "Основы сельского хозяйства",
  "description_az": "Bu təlimdə...",
  "description_en": "In this training...",
  "description_ru": "В этом обучении...",
  "trainer_id": 1,
  "is_online": true
}
```

**Format 2: Object formatı** (Tövsiyə olunan)
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

**Format 3: Sadə string** (Yalnız Azərbaycan dili)
```json
{
  "title": "Kənd təsərrüfatı əsasları",
  "trainer_id": 1,
  "is_online": true
}
```

## 📤 API Response Nümunələri

### Training Məlumatını Almaq

**GET** `/api/v1/trainings/{id}`

**Cavab:**
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
  "trainer_id": 1,
  "is_online": true
}
```

**Yəqin ki, siz cari dildə tərcümə istəyirsiniz?**

**GET** `/api/v1/trainings/{id}?lang=en`

**Cavab:**
```json
{
  "id": 1,
  "title": "Agriculture Basics",  // Yalnız İngilis dili
  "description": "In this training...",
  "trainer_id": 1,
  "is_online": true
}
```

**Qeyd:** `HasTranslations` trait-inin `getAttribute()` metodu avtomatik olaraq `lang` parametrinə görə düzgün tərcüməni qaytarır.

## 🔄 İş Axını (Workflow)

### Training Yaratmaq

1. **Request Gəlməsi**
   ```
   POST /api/v1/trainings
   {
     "title_az": "...",
     "title_en": "..."
   }
   ```

2. **Request Normalizasiyası**
   ```php
   $requestData = $this->normalizeTranslationRequest($request->all());
   // Artıq: { "title": {"az": "...", "en": "..."} }
   ```

3. **Validation**
   ```php
   $validated = $request->validate([
       'title' => ['required', new TranslationRule(true)]
   ]);
   ```

4. **Translation Normalizasiyası**
   ```php
   $validated['title'] = TranslationHelper::normalizeTranslation($validated['title']);
   ```

5. **Database-ə Saxlama**
   ```php
   Training::create($validated);
   // Database-də JSON string kimi saxlanır:
   // {"az": "...", "en": "..."}
   ```

6. **Response Qaytarma**
   ```json
   {
     "title": {
       "az": "...",
       "en": "..."
     }
   }
   ```

### Training Oxumaq

1. **Request Gəlməsi**
   ```
   GET /api/v1/trainings/1?lang=en
   ```

2. **Model-dən Data Götürmə**
   ```php
   $training = Training::find(1);
   ```

3. **Avtomatik Tərcümə** (`HasTranslations` trait)
   ```php
   $training->title; // "en" dilində dəyəri qaytarır
   ```

4. **Response Qaytarma**
   ```json
   {
     "title": "English Title"  // Yalnız İngilis dili
   }
   ```

## 📊 Database Strukturu

### `trainings` Cədvəli

```sql
CREATE TABLE trainings (
    id BIGINT PRIMARY KEY,
    title JSON,  -- {"az": "...", "en": "...", "ru": "..."}
    description JSON,  -- {"az": "...", "en": "...", "ru": "..."}
    category VARCHAR(255),
    trainer_id BIGINT,
    ...
);
```

**Qeyd:** `title` və `description` field-ləri JSON type-dır və çoxdilli məlumat saxlayır.

## 🎯 Əsas Xüsusiyyətlər

### 1. **Backward Compatibility**
- Köhnə sistemdə sadə string kimi saxlanan field-lər də dəstəklənir
- Yeni formatda array, köhnə formatda string ola bilər

### 2. **Fallback Mexanizmi**
- İstədiyiniz dil yoxdursa, default dil (`az`) istifadə olunur
- O da yoxdursa, ilk mövcud dil istifadə olunur

### 3. **Validation**
- Yalnız dəstəklənən dillər (`az`, `en`, `ru`) qəbul olunur
- Ən azı default dil (`az`) mütləq lazımdır (əgər field `required`-dırsa)

### 4. **Avtomatik Dil Müəyyənləşdirməsi**
- Request parametrindən (`?lang=en`)
- HTTP header-dan (`Accept-Language`)
- Laravel locale-dən
- Default dil (`az`)

## 🔍 Kod Nümunələri

### Training Model-də İstifadə

```php
$training = Training::find(1);

// Cari dildə başlıq
echo $training->title; // Cari dilə uyğun

// Müəyyən dildə başlıq
echo $training->getTranslated('title', 'en'); // İngilis dili

// Bütün tərcümələr
print_r($training->getFullTranslation('title'));
// Output: ['az' => '...', 'en' => '...', 'ru' => '...']
```

### TrainingController-də İstifadə

```php
public function store(Request $request)
{
    // Request normalizasiyası
    $requestData = $this->normalizeTranslationRequest($request->all());
    $request->merge($requestData);
    
    // Validation
    $validated = $request->validate([
        'title' => ['required', new TranslationRule(true)],
        'description' => ['nullable', new TranslationRule(false)],
    ]);
    
    // Translation normalizasiyası
    if (isset($validated['title'])) {
        $validated['title'] = TranslationHelper::normalizeTranslation($validated['title']);
    }
    
    // Yaratma
    $training = Training::create($validated);
    
    return response()->json($training);
}
```

## 📝 Xülasə

1. **Model**: `HasTranslations` trait istifadə edir və `translatable` property-də hansı field-lərin multilang olacağını təyin edir
2. **Database**: Multilang field-lər JSON formatında saxlanılır
3. **Controller**: Request normalizasiyası, validation və translation normalizasiyası edir
4. **Validation**: `TranslationRule` yalnız dəstəklənən dilləri qəbul edir
5. **Response**: `lang` parametrinə görə müvafiq dil qaytarılır

Bu sistem sayəsində təlimlərin başlıq və təsvirləri 3 dildə saxlanılır və istifadə olunur! 🎉

