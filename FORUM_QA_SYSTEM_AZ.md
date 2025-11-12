# Forum Sual-Cavab Sistemi - Ətraflı İzahat

## 📋 Ümumi Baxış

Bu sistemdə forum/sual-cavab (Q&A) funksionallığı var. Həm admin, həm də adi istifadəçilər sual yaza bilir, digər istifadəçilər isə bu suallara cavab verə bilir.

---

## 🔑 Əsas Komponentlər

### 1. **Modellər (Models)**

#### `ForumQuestion` (Sual Modeli)
**Fayl:** `app/Models/ForumQuestion.php`

**Sahələr:**
- `user_id` - Sual yazan istifadəçinin ID-si
- `title` - Sualın başlığı (maksimum 255 simvol)
- `summary` - Qısa xülasə (maksimum 300 simvol, opsional)
- `body` - Sualın tam mətni (tələb olunur)
- `status` - Status: `open` və ya `closed`
- `question_type` - Sual növü: `general`, `technical`, `discussion`, `poll`
- `poll_options` - Əgər `poll` tipidirsə, səsvermə variantları (array)
- `tags` - Etiketlər (array)
- `category` - Kateqoriya (maksimum 120 simvol, opsional)
- `difficulty` - Çətinlik səviyyəsi: `beginner`, `intermediate`, `advanced`
- `is_pinned` - Sabitləndirilibmi? (boolean, default: false)
- `allow_comments` - Şərhlər icazə verilibmi? (boolean, default: true)
- `is_open` - Açıqdırmı? (boolean, default: true)
- `is_public` - İctimai görünüş? (boolean, default: true)
- `views` - Baxış sayı

**Əlaqələr:**
- `user()` - Sualın müəllifi (belongsTo User)
- `answers()` - Bu sualın cavabları (hasMany ForumAnswer)
- `pollVotes()` - Səsvermələr (hasMany ForumPollVote)

#### `ForumAnswer` (Cavab Modeli)
**Fayl:** `app/Models/ForumAnswer.php`

**Sahələr:**
- `question_id` - Cavab verilən sualın ID-si
- `user_id` - Cavab yazan istifadəçinin ID-si
- `body` - Cavabın mətni
- `is_accepted` - Qəbul edilibmi? (boolean, default: false)

**Əlaqələr:**
- `question()` - Hansı suala cavab? (belongsTo ForumQuestion)
- `user()` - Cavab yazan istifadəçi (belongsTo User)

---

## 🌐 API Endpoint-ləri

### 📍 **Sual Yazmaq**

#### **Admin üçün:**
```
POST /api/v1/forum/questions
```
- **Middleware:** `role:admin` (yalnız adminlər istifadə edə bilir)
- **Controller Metodu:** `ForumController@postQuestion`
- **Validation:**
  - `title` - tələb olunur, string, max:255
  - `summary` - opsional, string, max:300
  - `body` - tələb olunur, string
  - `category` - opsional, string, max:120
  - `difficulty` - opsional, `beginner|intermediate|advanced`
  - `tags` - opsional, array
  - `question_type` - tələb olunur, `general|technical|discussion|poll`
  - `poll_options` - opsional, array (yalnız `poll` tipində)
  - `is_pinned` - boolean (default: false)
  - `allow_comments` - boolean (default: true)
  - `is_open` - boolean (default: true)
  - `is_public` - boolean (default: true)

**Nümunə Request:**
```json
{
  "title": "Laravel-də migration nədir?",
  "summary": "Migration haqqında qısa məlumat",
  "body": "Laravel-də migration sistemi necə işləyir?",
  "category": "Laravel",
  "difficulty": "beginner",
  "tags": ["laravel", "migration", "database"],
  "question_type": "technical",
  "allow_comments": true,
  "is_open": true,
  "is_public": true,
  "is_pinned": false
}
```

**Response (201):**
```json
{
  "id": 1,
  "user_id": 1,
  "title": "Laravel-də migration nədir?",
  "body": "Laravel-də migration sistemi necə işləyir?",
  "status": "open",
  "question_type": "technical",
  "user": {
    "id": 1,
    "first_name": "Admin",
    "last_name": "User"
  }
}
```

#### **Adi İstifadəçi üçün:**
```
POST /api/v1/my/forum/questions
```
- **Middleware:** `auth:sanctum` (authentication lazımdır)
- **Controller Metodu:** `ForumController@createMyQuestion`
- **Fərq:** Adi istifadəçilər `is_pinned` dəyişdirə bilməz (həmişə `false`), amma digər sahələri doldurub sual yaza bilərlər.

**Validation:**
- Eyni validation qaydaları, lakin:
  - `is_pinned` sahəsi olmur (həmişə `false`)
  - `allow_comments` həmişə `true`
  - `is_open` həmişə `true`
  - `is_public` istifadəçi tərəfindən seçilə bilər

**Nümunə Request:**
```json
{
  "title": "Vue.js-də reactive data necə işləyir?",
  "summary": "Vue reactivity haqqında sual",
  "body": "Vue.js-də reactive data necə işləyir? Bana izah edə bilərsinizmi?",
  "category": "Vue.js",
  "difficulty": "intermediate",
  "tags": ["vue", "javascript", "frontend"],
  "question_type": "technical",
  "is_public": true
}
```

---

### 💬 **Cavab Yazmaq**

```
POST /api/v1/forum/questions/{question}/answers
```
- **Middleware:** `auth:sanctum` (authentication lazımdır)
- **Controller Metodu:** `ForumController@answerQuestion`
- **Path Parametr:** `{question}` - ForumQuestion modelinin ID-si

**Şərtlər:**
Cavab yazmaq üçün sual aşağıdakı şərtlərə cavab verməlidir:
1. `allow_comments` = `true` olmalıdır
2. `is_open` = `true` olmalıdır
3. `status` ≠ `closed` olmalıdır

Əgər bu şərtlər yerinə yetirilmirsə, **400** status kodu qaytarılır: `"Comments are disabled for this question"`

**Validation:**
- `body` - tələb olunur, string (cavabın mətni)
- `is_helpful` - opsional, boolean

**Nümunə Request:**
```json
{
  "body": "Migration Laravel-də verilənlər bazası strukturunu idarə etmək üçün istifadə olunur. Versiya idarəetmə sistemində kod kimi, migration-lar da verilənlər bazasının dəyişikliklərinə nəzarət edir."
}
```

**Response (201):**
```json
{
  "id": 1,
  "question_id": 1,
  "user_id": 2,
  "body": "Migration Laravel-də verilənlər bazası strukturunu idarə etmək üçün istifadə olunur...",
  "is_accepted": false,
  "created_at": "2024-01-15T10:30:00.000000Z",
  "updated_at": "2024-01-15T10:30:00.000000Z"
}
```

---

### 📖 **Sual Görmək**

#### **Bütün sualların siyahısı:**
```
GET /api/v1/forum/questions
```
- **Controller Metodu:** `ForumController@listQuestions`
- **Query Parametrləri:**
  - `search` - Axtarış mətni (title, summary, body-də axtarır)
  - `category` - Kateqoriyaya görə filtrlə
  - `question_type` - Sual tipinə görə filtrlə
  - `is_pinned` - Yalnız sabitlənmiş suallar
  - `tags` - Etiketlərə görə filtrlə (vergüllə ayrılmış siyahı)
  - `per_page` - Səhifə başına element sayı (default: 20)

**Məntiq:**
- Əgər istifadəçi admin və ya trainer deyilsə, yalnız `is_public = true` olan suallar göstərilir
- Admin və trainerlər bütün sualları görə bilirlər

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Laravel-də migration nədir?",
      "summary": "Migration haqqında qısa məlumat",
      "body": "Laravel-də migration sistemi necə işləyir?",
      "status": "open",
      "question_type": "technical",
      "category": "Laravel",
      "difficulty": "beginner",
      "tags": ["laravel", "migration"],
      "user": {
        "id": 1,
        "first_name": "Admin",
        "last_name": "User"
      },
      "created_at": "2024-01-15T10:00:00.000000Z"
    }
  ],
  "current_page": 1,
  "per_page": 20,
  "total": 1
}
```

#### **Tək sual görüntüləmə:**
```
GET /api/v1/forum/questions/{question}
```
- **Controller Metodu:** `ForumController@showQuestion`
- **Məntiq:**
  - Sual görüntülənəndə `views` sayı 1 artırılır
  - Əgər istifadəçi admin və ya trainer deyilsə və sual `is_public = false` olarsa, görə bilməz
  - Cavabları ilə birlikdə göstərilir (`answers.user`)

**Response (200):**
```json
{
  "id": 1,
  "title": "Laravel-də migration nədir?",
  "body": "Laravel-də migration sistemi necə işləyir?",
  "views": 5,
  "user": {
    "id": 1,
    "first_name": "Admin"
  },
  "answers": [
    {
      "id": 1,
      "body": "Migration Laravel-də...",
      "user": {
        "id": 2,
        "first_name": "User"
      }
    }
  ]
}
```

---

### 📝 **Cavabları Görmək**

```
GET /api/v1/forum/questions/{question}/answers
```
- **Controller Metodu:** `ForumController@getAnswers`
- **Query Parametrləri:**
  - Pagination avtomatik işləyir (default: 20 cavab/səhifə)
- **Sıralama:** Ən yeni cavablar üstdə (latest)

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "question_id": 1,
      "user_id": 2,
      "body": "Migration Laravel-də verilənlər bazası...",
      "is_accepted": false,
      "user": {
        "id": 2,
        "first_name": "User",
        "last_name": "Name"
      },
      "created_at": "2024-01-15T10:30:00.000000Z"
    }
  ],
  "current_page": 1,
  "per_page": 20,
  "total": 1
}
```

---

### 👤 **İstifadəçinin Öz Sualları**

```
GET /api/v1/my/forum/questions
```
- **Controller Metodu:** `ForumController@myQuestions`
- **Middleware:** `auth:sanctum`
- **Məntiq:** Yalnız authenticated istifadəçinin yazdığı suallar göstərilir

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Mənim sualım",
      "user_id": 2,
      "status": "open"
    }
  ],
  "current_page": 1,
  "per_page": 20,
  "total": 1
}
```

---

### 🛠️ **Admin İdarəetməsi**

#### **Sualı Yeniləmək:**
```
PATCH /api/v1/forum/questions/{question}
```
- **Middleware:** `role:admin`
- **Controller Metodu:** `ForumController@updateQuestion`
- Admin bütün sahələri yeniləyə bilir, o cümlədən:
  - `status` (`open` və ya `closed`)
  - `is_pinned`
  - `allow_comments`
  - `is_open`
  - və s.

#### **Sualı Silmək:**
```
DELETE /api/v1/forum/questions/{question}
```
- **Middleware:** `role:admin`
- **Controller Metodu:** `ForumController@destroyQuestion`

---

## 🔄 İş Axını (Workflow)

### **Ssenari 1: Admin Sual Yaradır**

1. Admin `POST /api/v1/forum/questions` endpoint-inə sorğu göndərir
2. Validation keçirilir
3. `ForumQuestion::create()` metodu ilə sual yaradılır
4. `user_id` avtomatik olaraq authenticated adminin ID-si təyin edilir
5. 201 status kodu ilə yeni sual qaytarılır

### **Ssenari 2: Adi İstifadəçi Sual Yaradır**

1. İstifadəçi `POST /api/v1/my/forum/questions` endpoint-inə sorğu göndərir
2. Validation keçirilir
3. Sual yaradılır, lakin:
   - `is_pinned` həmişə `false`
   - `allow_comments` həmişə `true`
   - `is_open` həmişə `true`
4. 201 status kodu ilə yeni sual qaytarılır

### **Ssenari 3: İstifadəçi Cavab Yazır**

1. İstifadəçi `POST /api/v1/forum/questions/{question}/answers` endpoint-inə sorğu göndərir
2. Sualın vəziyyəti yoxlanılır:
   - `allow_comments` = `true`?
   - `is_open` = `true`?
   - `status` ≠ `closed`?
3. Əgər bütün şərtlər yerinə yetirilirsə:
   - `ForumAnswer::create()` metodu ilə cavab yaradılır
   - `question_id` və `user_id` avtomatik təyin edilir
   - 201 status kodu ilə cavab qaytarılır
4. Əgər şərtlər yerinə yetirilmirsə, 400 status kodu qaytarılır

### **Ssenari 4: Sualları Görmək**

1. İstifadəçi `GET /api/v1/forum/questions` endpoint-inə sorğu göndərir
2. Əgər istifadəçi admin/trainer deyilsə:
   - Yalnız `is_public = true` olan suallar göstərilir
3. Filtrləmə və axtarış parametrləri tətbiq edilir
4. Paginated nəticə qaytarılır

---

## 🎯 Əsas Qaydalar

1. **Sual Yazmaq:**
   - Admin: `POST /api/v1/forum/questions` (bütün parametrlər)
   - İstifadəçi: `POST /api/v1/my/forum/questions` (məhdud parametrlər)

2. **Cavab Yazmaq:**
   - Hər kəs (authenticated): `POST /api/v1/forum/questions/{question}/answers`
   - Şərt: Sual açıq olmalıdır və şərhlərə icazə verilməlidir

3. **Görünüş:**
   - Admin/trainer: Bütün sualları görür
   - Adi istifadəçi: Yalnız `is_public = true` olan sualları görür

4. **İdarəetmə:**
   - Yalnız admin: Sualı yeniləyə, silə və idarə edə bilir

---

## 📊 Statistika Endpoint-ləri

### **Statistika:**
```
GET /api/v1/forum/stats
```
- Ümumi suallar sayı
- Cavablandırılmış suallar sayı
- Ümumi cavablar sayı
- Aylıq aktivlik
- Artım faizi (son 30 gün vs əvvəlki 30 gün)

### **Kartlar Görünüşü:**
```
GET /api/v1/forum/cards
```
- Sual siyahısı kompakt formatda
- Tarix və saat (Asia/Baku timezone)
- Müəllif adı
- Baxış və cavab sayı

---

## 🔍 Nümunə Kod İstifadəsi

### **Frontend-də (Vue.js) sual yaratmaq:**

```javascript
// services/forum.js
import api from '@/services/api'

export async function createMyQuestion(payload) {
  const { data } = await api.post('/api/v1/my/forum/questions', payload)
  return data
}

export async function answerQuestion(questionId, body) {
  const { data } = await api.post(
    `/api/v1/forum/questions/${questionId}/answers`,
    { body }
  )
  return data
}
```

```vue
<script setup>
import { ref } from 'vue'
import { createMyQuestion, answerQuestion } from '@/services/forum'

const questionForm = ref({
  title: '',
  summary: '',
  body: '',
  category: '',
  difficulty: 'beginner',
  tags: [],
  question_type: 'general',
  is_public: true
})

const answerForm = ref({
  body: ''
})

async function submitQuestion() {
  try {
    const question = await createMyQuestion(questionForm.value)
    console.log('Sual yaradıldı:', question)
  } catch (error) {
    console.error('Xəta:', error)
  }
}

async function submitAnswer(questionId) {
  try {
    const answer = await answerQuestion(questionId, answerForm.value.body)
    console.log('Cavab göndərildi:', answer)
    answerForm.value.body = '' // Formu təmizlə
  } catch (error) {
    if (error.response?.status === 400) {
      alert('Bu suala cavab yazmaq mümkün deyil')
    }
  }
}
</script>
```

---

## ✅ Xülasə

- ✅ **Admin və istifadəçilər** sual yaza bilir
- ✅ **Bütün authenticated istifadəçilər** cavab yaza bilir
- ✅ **Sualın vəziyyəti** cavab yazmaq üçün şərtdir
- ✅ **Görünüş məhdudiyyətləri** var (public/private)
- ✅ **Admin tam idarəetmə** hüququna malikdir
- ✅ **Pagination, filtrləmə, axtarış** dəstəklənir



