# Forum Sual-Cavab Sistemi Yeniləmələri

## ✅ Yeni Funksionallıqlar

### 1. **User Tracking Sistemi**

Hər istifadəçinin hər suala bir dəfə baxması üçün tracking sistemi əlavə edildi:

- **Yeni Table:** `forum_question_views`
  - `question_id` - Hansı suala baxılıb
  - `user_id` - Hansı istifadəçi baxıb (nullable - authenticated users üçün)
  - `ip_address` - IP ünvanı (nullable - unauthenticated users üçün)
  - `created_at`, `updated_at` - Tarix

- **Yeni Model:** `ForumQuestionView`
- **Tracking Məntiq:**
  - Əgər istifadəçi authenticated-dırsa → `user_id` ilə track edilir
  - Əgər istifadəçi unauthenticated-dırsa → `ip_address` ilə track edilir
  - Hər istifadəçi hər suala bir dəfə baxa bilər (duplicate yoxdur)

---

### 2. **İctimai/Şəxsi Sual Seçimi**

İstifadəçilər artıq sual yaradarkən seçə bilərlər:
- **Ümumi sual** (`is_public = true`) - Hər kəs görə bilər
- **Şəxsi sual** (`is_public = false`) - Yalnız admin və trainerlər görə bilər

**Endpoint:** `POST /api/v1/my/forum/questions`

**Request Body:**
```json
{
  "title": "Sual başlığı",
  "body": "Sual mətni",
  "is_public": false  // false = yalnız adminlər görsün, true = hər kəs görsün
}
```

---

### 3. **Statistikalar**

Artıq bütün endpoint-lərdə aşağıdakı statistikalar mövcuddur:

#### **Baxış Sayıları:**
- `views` - Ümumi baxış sayı (hər baxış sayılır)
- `unique_viewers` - Neçə fərqli istifadəçi baxıb (hər istifadəçi bir dəfə sayılır)

#### **Cavab Sayıları:**
- `answers_count` - Cavab sayı
- `comments` - Cavab sayı (cards endpoint-də)

#### **Baxış Hesablama Məntiq:**
- **Ümumi sual (`is_public = true`):**
  - `views` = Bütün istifadəçilərin (authenticated + unauthenticated) baxış sayı
  - `unique_viewers` = Authenticated istifadəçilərin sayı

- **Şəxsi sual (`is_public = false`):**
  - `views` = Yalnız admin/trainerlərin baxış sayı
  - `unique_viewers` = Yalnız admin/trainerlərin sayı

---

## 📊 Dəyişdirilən Endpoint-lər

### 1. **GET /api/v1/forum/questions** (Sual siyahısı)

**Yeni Response:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Sual başlığı",
      "body": "Sual mətni",
      "views": 25,
      "views_count": 25,
      "unique_viewers_count": 12,
      "answers_count": 5,
      "is_public": true,
      "user": {...},
      ...
    }
  ]
}
```

**Yeni sahələr:**
- `views` - Ümumi baxış sayı
- `views_count` - Ümumi baxış sayı (alternativ)
- `unique_viewers_count` - Neçə nəfər baxıb
- `answers_count` - Cavab sayı

---

### 2. **GET /api/v1/forum/questions/{question}** (Tək sual)

**Yeni Response:**
```json
{
  "id": 1,
  "title": "Sual başlığı",
  "body": "Sual mətni",
  "views": 25,
  "user": {...},
  "answers": [...],
  "stats": {
    "views": 25,              // Ümumi baxış sayı
    "unique_viewers": 12,     // Neçə nəfər baxıb
    "answers_count": 5        // Cavab sayı
  }
}
```

**Məntiq:**
- İstifadəçi suala baxanda avtomatik olaraq tracking əlavə edilir
- Hər istifadəçi bir dəfə sayılır (duplicate yoxdur)
- `views` count avtomatik yenilənir

---

### 3. **GET /api/v1/forum/cards** (Kart görünüşü)

**Yeni Response:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Sual başlığı",
      "summary": "Qısa xülasə",
      "views": 25,              // Ümumi baxış sayı
      "unique_viewers": 12,     // Neçə nəfər baxıb
      "comments": 5,            // Cavab sayı
      "is_public": true,
      ...
    }
  ]
}
```

**Yeni sahələr:**
- `views` - Ümumi baxış sayı
- `unique_viewers` - Neçə nəfər baxıb
- `comments` - Cavab sayı
- `is_public` - İctimai görünüş

---

### 4. **GET /api/v1/my/forum/questions** (İstifadəçinin öz sualları)

**Yeni Response:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Mənim sualım",
      "views": 25,
      "views_count": 25,
      "unique_viewers_count": 12,
      "answers_count": 5,
      "is_public": false,
      ...
    }
  ]
}
```

**Yeni sahələr:**
- `views_count` - Ümumi baxış sayı
- `unique_viewers_count` - Neçə nəfər baxıb
- `answers_count` - Cavab sayı

---

### 5. **POST /api/v1/my/forum/questions** (Sual yaratmaq)

**Yeni Parametr:**
- `is_public` (boolean, optional, default: `true`)
  - `true` = Hər kəs görə bilər (ümumi sual)
  - `false` = Yalnız admin/trainerlər görə bilər (şəxsi sual)

**Nümunə Request:**
```json
{
  "title": "Şəxsi sual",
  "body": "Bu sual yalnız adminlər üçündür",
  "is_public": false,  // Şəxsi sual
  "category": "Texniki",
  "question_type": "technical"
}
```

**Nümunə Request (Ümumi sual):**
```json
{
  "title": "Ümumi sual",
  "body": "Bu sual hər kəs üçündür",
  "is_public": true,   // Ümumi sual (default)
  "category": "Ümumi",
  "question_type": "general"
}
```

---

## 🔧 Texniki Dəyişikliklər

### **Migration:**
- `2025_10_31_120838_create_forum_question_views_table.php` - Yeni table yaradıldı

### **Modellər:**
- `ForumQuestionView` - Yeni model əlavə edildi
- `ForumQuestion` - `questionViews()` relation əlavə edildi
- `ForumQuestion` - `getUniqueViewersCountAttribute()` accessor əlavə edildi

### **Controller:**
- `ForumController@showQuestion` - Tracking məntiq əlavə edildi
- `ForumController@listQuestions` - Statistikalar əlavə edildi
- `ForumController@cards` - Statistikalar əlavə edildi
- `ForumController@myQuestions` - Statistikalar əlavə edildi
- `ForumController@createMyQuestion` - `is_public` parametri artıq mövcuddur

---

## 📝 İstifadə Nümunələri

### **Frontend-də Sual Yaratmaq:**

```vue
<template>
  <div>
    <input v-model="form.title" placeholder="Sual başlığı" />
    <textarea v-model="form.body" placeholder="Sual mətni" />
    
    <!-- İctimai/Şəxsi seçimi -->
    <label>
      <input 
        type="radio" 
        v-model="form.is_public" 
        :value="true" 
      />
      Ümumi sual (hər kəs görə bilər)
    </label>
    
    <label>
      <input 
        type="radio" 
        v-model="form.is_public" 
        :value="false" 
      />
      Şəxsi sual (yalnız adminlər görə bilər)
    </label>
    
    <button @click="createQuestion">Sual Yarat</button>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import api from '@/services/api'

const form = ref({
  title: '',
  body: '',
  is_public: true,  // Default: ümumi
  category: '',
  question_type: 'general'
})

async function createQuestion() {
  const { data } = await api.post('/api/v1/my/forum/questions', form.value)
  console.log('Sual yaradıldı:', data)
}
</script>
```

### **Frontend-də Statistikaları Göstərmək:**

```vue
<template>
  <div v-for="question in questions" :key="question.id">
    <h3>{{ question.title }}</h3>
    
    <!-- Statistikalar -->
    <div class="stats">
      <span>👁️ {{ question.views }} baxış</span>
      <span>👥 {{ question.unique_viewers_count }} nəfər baxıb</span>
      <span>💬 {{ question.answers_count }} cavab</span>
      
      <!-- İctimai/Şəxsi etiketi -->
      <span v-if="question.is_public" class="badge public">
        Ümumi
      </span>
      <span v-else class="badge private">
        Şəxsi (yalnız adminlər)
      </span>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const questions = ref([])

async function loadQuestions() {
  const { data } = await api.get('/api/v1/forum/questions')
  questions.value = data.data
}

onMounted(() => {
  loadQuestions()
})
</script>
```

---

## ✅ Yoxlama Siyahısı

- [x] Hər istifadəçi hər suala bir dəfə baxır (tracking)
- [x] Ümumi baxış sayı (`views`)
- [x] Neçə nəfər baxıb (`unique_viewers`)
- [x] Cavab sayı (`answers_count`)
- [x] İstifadəçi seçə bilir: ümumi və ya şəxsi sual
- [x] Admin və user həmçinin sual yarada bilər
- [x] Bütün endpoint-lərdə statistikalar mövcuddur

---

## 🚀 Migration Çalışdırmaq

Yeni migration-ı çalışdırmaq üçün:

```bash
php artisan migrate
```

Bu komanda `forum_question_views` table-ını yaradacaq və bütün indexləri quracaq.

---

## 📌 Qeydlər

1. **Tracking Performance:**
   - `firstOrCreate` metodu istifadə olunur ki, duplicate insertləri qarşısını alsın
   - Indexlər performansı artırır
   - Unique constraint application səviyyəsində yoxlanılır (MySQL nullable unique constraint problemi üçün)

2. **Baxış Hesablama:**
   - `views` - Ümumi baxış sayı (hər baxış sayılır, hətta eyni istifadəçi tərəfindən)
   - `unique_viewers` - Yalnız authenticated istifadəçilərin sayı (hər istifadəçi bir dəfə)

3. **Görünüş Məhdudiyyətləri:**
   - `is_public = true` → Hər kəs görə bilər
   - `is_public = false` → Yalnız admin və trainerlər görə bilər
   - Şəxsi suallara baxış tracking-i yalnız admin/trainerlər üçün işləyir

