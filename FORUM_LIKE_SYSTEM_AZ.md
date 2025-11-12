# Forum Like Sistemi - Sənədləşmə

## ✅ Yeni Funksionallıq: Like Sistemi

İstifadəçilər artıq forum suallarına və cavablarına like qoya bilirlər!

---

## 📋 Əsas Xüsusiyyətlər

### 1. **Sual Like Sistemi**
- İstifadəçilər suallara like qoya bilər
- Hər istifadəçi hər suala bir dəfə like qoya bilər
- Like sayı avtomatik hesablanır

### 2. **Cavab Like Sistemi**
- İstifadəçilər cavablara like qoya bilər
- Hər istifadəçi hər cavaba bir dəfə like qoya bilər
- Like sayı avtomatik hesablanır

---

## 🗄️ Database Strukturu

### **Yeni Table-lar:**

1. **`forum_question_likes`**
   - `id` - Primary key
   - `question_id` - Foreign key (forum_questions)
   - `user_id` - Foreign key (users)
   - `created_at`, `updated_at` - Tarix
   - Unique constraint: `(question_id, user_id)` - Hər istifadəçi bir dəfə

2. **`forum_answer_likes`**
   - `id` - Primary key
   - `answer_id` - Foreign key (forum_answers)
   - `user_id` - Foreign key (users)
   - `created_at`, `updated_at` - Tarix
   - Unique constraint: `(answer_id, user_id)` - Hər istifadəçi bir dəfə

### **Yeni Sahələr:**

1. **`forum_questions` table-ına:**
   - `likes_count` (unsigned big integer, default: 0) - Like sayı

2. **`forum_answers` table-ına:**
   - `likes_count` (unsigned big integer, default: 0) - Like sayı

---

## 📦 Modellər

### **ForumQuestionLike Model**
```php
app/Models/ForumQuestionLike.php
```

**Relations:**
- `question()` - belongsTo ForumQuestion
- `user()` - belongsTo User

### **ForumAnswerLike Model**
```php
app/Models/ForumAnswerLike.php
```

**Relations:**
- `answer()` - belongsTo ForumAnswer
- `user()` - belongsTo User

### **ForumQuestion Model Yeniləmələri:**
- `likes()` - hasMany ForumQuestionLike
- `isLikedBy($userId)` - İstifadəçi like edibmi?

### **ForumAnswer Model Yeniləmələri:**
- `likes()` - hasMany ForumAnswerLike
- `isLikedBy($userId)` - İstifadəçi like edibmi?

---

## 🌐 API Endpoint-ləri

### **1. Sual Like Qoymaq**

```
POST /api/v1/forum/questions/{question}/like
```

**Authentication:** Tələb olunur (`auth:sanctum`)

**Response (200):**
```json
{
  "message": "Question liked successfully",
  "is_liked": true,
  "likes_count": 5
}
```

**Response (400) - Artıq like edilib:**
```json
{
  "message": "Question already liked",
  "is_liked": true
}
```

---

### **2. Sual Like Silmək**

```
POST /api/v1/forum/questions/{question}/unlike
```

**Authentication:** Tələb olunur (`auth:sanctum`)

**Response (200):**
```json
{
  "message": "Question unliked successfully",
  "is_liked": false,
  "likes_count": 4
}
```

**Response (400) - Like yoxdur:**
```json
{
  "message": "Question not liked",
  "is_liked": false
}
```

---

### **3. Cavab Like Qoymaq**

```
POST /api/v1/forum/answers/{answer}/like
```

**Authentication:** Tələb olunur (`auth:sanctum`)

**Response (200):**
```json
{
  "message": "Answer liked successfully",
  "is_liked": true,
  "likes_count": 3
}
```

**Response (400) - Artıq like edilib:**
```json
{
  "message": "Answer already liked",
  "is_liked": true
}
```

---

### **4. Cavab Like Silmək**

```
POST /api/v1/forum/answers/{answer}/unlike
```

**Authentication:** Tələb olunur (`auth:sanctum`)

**Response (200):**
```json
{
  "message": "Answer unliked successfully",
  "is_liked": false,
  "likes_count": 2
}
```

**Response (400) - Like yoxdur:**
```json
{
  "message": "Answer not liked",
  "is_liked": false
}
```

---

## 📊 Response-larda Yeni Sahələr

### **Sual Response-ları:**

**GET /api/v1/forum/questions** və **GET /api/v1/my/forum/questions:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Sual başlığı",
      "likes_count": 5,        // Like sayı
      "is_liked": true,        // Current user like edibmi?
      ...
    }
  ]
}
```

**GET /api/v1/forum/questions/{question}:**
```json
{
  "id": 1,
  "title": "Sual başlığı",
  "is_liked": true,           // Current user like edibmi?
  "stats": {
    "views": 25,
    "unique_viewers": 12,
    "answers_count": 5,
    "likes_count": 5          // Like sayı
  }
}
```

**GET /api/v1/forum/cards:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Sual başlığı",
      "likes_count": 5,        // Like sayı
      "is_liked": true,        // Current user like edibmi?
      ...
    }
  ]
}
```

---

### **Cavab Response-ları:**

**GET /api/v1/forum/questions/{question}/answers:**
```json
{
  "data": [
    {
      "id": 1,
      "body": "Cavab mətni",
      "likes_count": 3,        // Like sayı
      "is_liked": false,       // Current user like edibmi?
      "user": {...},
      ...
    }
  ]
}
```

---

## 💻 Frontend İstifadə Nümunələri

### **Vue.js Komponenti:**

```vue
<template>
  <div>
    <!-- Sual Like Butonu -->
    <button 
      @click="toggleQuestionLike(question.id)"
      :class="{ 'liked': question.is_liked }"
    >
      ❤️ {{ question.likes_count }}
    </button>

    <!-- Cavablar -->
    <div v-for="answer in answers" :key="answer.id">
      <p>{{ answer.body }}</p>
      <button 
        @click="toggleAnswerLike(answer.id)"
        :class="{ 'liked': answer.is_liked }"
      >
        ❤️ {{ answer.likes_count }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import api from '@/services/api'

const question = ref({
  id: 1,
  likes_count: 5,
  is_liked: false
})

const answers = ref([])

async function toggleQuestionLike(questionId) {
  const endpoint = question.value.is_liked 
    ? `/api/v1/forum/questions/${questionId}/unlike`
    : `/api/v1/forum/questions/${questionId}/like`
  
  try {
    const { data } = await api.post(endpoint)
    question.value.is_liked = data.is_liked
    question.value.likes_count = data.likes_count
  } catch (error) {
    console.error('Like error:', error)
  }
}

async function toggleAnswerLike(answerId) {
  const answer = answers.value.find(a => a.id === answerId)
  const endpoint = answer.is_liked 
    ? `/api/v1/forum/answers/${answerId}/unlike`
    : `/api/v1/forum/answers/${answerId}/like`
  
  try {
    const { data } = await api.post(endpoint)
    answer.is_liked = data.is_liked
    answer.likes_count = data.likes_count
  } catch (error) {
    console.error('Like error:', error)
  }
}
</script>

<style scoped>
.liked {
  color: red;
}
</style>
```

---

### **JavaScript Service Funksiyaları:**

```javascript
// services/forum.js
import api from '@/services/api'

export async function likeQuestion(questionId) {
  const { data } = await api.post(`/api/v1/forum/questions/${questionId}/like`)
  return data
}

export async function unlikeQuestion(questionId) {
  const { data } = await api.post(`/api/v1/forum/questions/${questionId}/unlike`)
  return data
}

export async function likeAnswer(answerId) {
  const { data } = await api.post(`/api/v1/forum/answers/${answerId}/like`)
  return data
}

export async function unlikeAnswer(answerId) {
  const { data } = await api.post(`/api/v1/forum/answers/${answerId}/unlike`)
  return data
}
```

---

## 🔄 İş Axını

### **Like Qoymaq:**
1. İstifadəçi like düyməsinə basır
2. `POST /api/v1/forum/questions/{id}/like` endpoint-inə sorğu göndərilir
3. Sistem yoxlayır: İstifadəçi artıq like edibmi?
4. Əgər yoxdursa:
   - `ForumQuestionLike` record yaradılır
   - `likes_count` 1 artırılır
   - Response qaytarılır
5. Əgər varsa: 400 error qaytarılır

### **Like Silmək:**
1. İstifadəçi unlike düyməsinə basır
2. `POST /api/v1/forum/questions/{id}/unlike` endpoint-inə sorğu göndərilir
3. Sistem yoxlayır: İstifadəçi like edibmi?
4. Əgər varsa:
   - `ForumQuestionLike` record silinir
   - `likes_count` 1 azaldılır
   - Response qaytarılır
5. Əgər yoxdursa: 400 error qaytarılır

---

## 📝 Migration Çalışdırmaq

Yeni migration-ları çalışdırmaq üçün:

```bash
php artisan migrate
```

Bu komanda:
1. `forum_question_likes` table-ını yaradacaq
2. `forum_answer_likes` table-ını yaradacaq
3. `forum_questions` table-ına `likes_count` sahəsini əlavə edəcək
4. `forum_answers` table-ına `likes_count` sahəsini əlavə edəcək

---

## ✅ Xülasə

- ✅ İstifadəçilər suallara like qoya bilir
- ✅ İstifadəçilər cavablara like qoya bilir
- ✅ Hər istifadəçi hər sual/cavaba bir dəfə like qoya bilir
- ✅ Like sayı avtomatik hesablanır və response-larda göstərilir
- ✅ `is_liked` sahəsi current user-in like edib-etmədiyini göstərir
- ✅ Bütün endpoint-lərdə `likes_count` və `is_liked` sahələri mövcuddur

---

## 🎯 Növbəti Addımlar

1. Migration-ları çalışdırın: `php artisan migrate`
2. Frontend-də like/unlike funksionallığını əlavə edin
3. UI-da like düymələrini və sayğacları göstərin



