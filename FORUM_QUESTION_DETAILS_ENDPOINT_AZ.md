# Forum Sual Detalları Endpoint-i

## 📋 Endpoint Məlumatı

### **GET /api/v1/forum/questions/{question}**

Bu endpoint tək sualın bütün detallarını göstərir.

---

## 🔑 Əsas Xüsusiyyətlər

1. **Baxış Tracking** - Sual açılanda avtomatik tracking edilir
2. **Like Status** - Current user-in like edib-etmədiyi göstərilir
3. **Cavablar** - Bütün cavablar like statusu ilə birlikdə
4. **Statistikalar** - Baxış sayı, unique viewers, cavab sayı, like sayı
5. **Görünüş Kontrolü** - Yalnız public suallar və ya admin/trainerlər üçün

---

## 📥 Request

```
GET /api/v1/forum/questions/{id}
```

**Path Parameters:**
- `{id}` - Sualın ID-si (integer)

**Headers:**
- `Authorization: Bearer {token}` (optional - authenticated users üçün)
- `Accept: application/json`

**Query Parameters:** Yoxdur

---

## 📤 Response

### **Success Response (200 OK)**

```json
{
  "id": 1,
  "title": "Laravel-də migration nədir?",
  "summary": "Migration haqqında qısa məlumat",
  "body": "Laravel-də migration sistemi necə işləyir? Bana izah edə bilərsinizmi?",
  "category": "Laravel",
  "difficulty": "beginner",
  "tags": ["laravel", "migration", "database"],
  "question_type": "technical",
  "poll_options": null,
  "status": "open",
  "is_pinned": false,
  "allow_comments": true,
  "is_open": true,
  "is_public": true,
  "created_at": "2024-01-15T10:00:00.000000Z",
  "updated_at": "2024-01-15T10:00:00.000000Z",
  "user": {
    "id": 1,
    "first_name": "Admin",
    "last_name": "User",
    "email": "admin@example.com",
    "username": "admin"
  },
  "is_liked": true,
  "likes_count": 5,
  "views": 25,
  "answers": [
    {
      "id": 1,
      "question_id": 1,
      "user_id": 2,
      "body": "Migration Laravel-də verilənlər bazası strukturunu idarə etmək üçün istifadə olunur...",
      "is_accepted": false,
      "likes_count": 3,
      "is_liked": false,
      "created_at": "2024-01-15T10:30:00.000000Z",
      "updated_at": "2024-01-15T10:30:00.000000Z",
      "user": {
        "id": 2,
        "first_name": "User",
        "last_name": "Name",
        "email": "user@example.com",
        "username": "user"
      }
    },
    {
      "id": 2,
      "question_id": 1,
      "user_id": 3,
      "body": "Migration versiya idarəetmə sistemində kod kimi işləyir...",
      "is_accepted": false,
      "likes_count": 1,
      "is_liked": true,
      "created_at": "2024-01-15T11:00:00.000000Z",
      "updated_at": "2024-01-15T11:00:00.000000Z",
      "user": {
        "id": 3,
        "first_name": "Trainer",
        "last_name": "Name",
        "email": "trainer@example.com",
        "username": "trainer"
      }
    }
  ],
  "stats": {
    "views": 25,
    "unique_viewers": 12,
    "answers_count": 2,
    "likes_count": 5
  }
}
```

---

## 📊 Response Sahələri

### **Sual Sahələri:**

| Sahə | Tip | Təsvir |
|------|-----|--------|
| `id` | integer | Sualın ID-si |
| `title` | string | Sualın başlığı |
| `summary` | string\|null | Qısa xülasə |
| `body` | string | Sualın tam mətni |
| `category` | string\|null | Kateqoriya |
| `difficulty` | string\|null | Çətinlik: `beginner`, `intermediate`, `advanced` |
| `tags` | array | Etiketlər massivi |
| `question_type` | string | Tip: `general`, `technical`, `discussion`, `poll` |
| `poll_options` | array\|null | Səsvermə variantları (poll üçün) |
| `status` | string | Status: `open`, `closed` |
| `is_pinned` | boolean | Sabitləndirilibmi? |
| `allow_comments` | boolean | Şərhlər icazə verilibmi? |
| `is_open` | boolean | Açıqdırmı? |
| `is_public` | boolean | İctimai görünüş? |
| `created_at` | datetime | Yaradılma tarixi |
| `updated_at` | datetime | Yenilənmə tarixi |
| `user` | object | Sual yazan istifadəçi |
| `is_liked` | boolean | Current user like edibmi? |
| `likes_count` | integer | Like sayı |
| `views` | integer | Baxış sayı |

### **Cavab Sahələri:**

| Sahə | Tip | Təsvir |
|------|-----|--------|
| `id` | integer | Cavabın ID-si |
| `question_id` | integer | Hansı suala cavab |
| `user_id` | integer | Cavab yazan istifadəçi ID |
| `body` | string | Cavab mətni |
| `is_accepted` | boolean | Qəbul edilibmi? |
| `likes_count` | integer | Like sayı |
| `is_liked` | boolean | Current user like edibmi? |
| `created_at` | datetime | Yaradılma tarixi |
| `updated_at` | datetime | Yenilənmə tarixi |
| `user` | object | Cavab yazan istifadəçi |

### **Statistikalar:**

| Sahə | Tip | Təsvir |
|------|-----|--------|
| `views` | integer | Ümumi baxış sayı |
| `unique_viewers` | integer | Neçə fərqli istifadəçi baxıb |
| `answers_count` | integer | Cavab sayı |
| `likes_count` | integer | Like sayı |

---

## 🔒 Görünüş Kontrolü

### **Şərtlər:**

1. **Public Sual (`is_public = true`):**
   - Hər kəs görə bilər (authenticated və unauthenticated)

2. **Private Sual (`is_public = false`):**
   - Yalnız admin və trainerlər görə bilər
   - Adi istifadəçilər 404 və ya 403 ala bilər

### **Baxış Tracking:**

- Authenticated users: `user_id` ilə track edilir
- Unauthenticated users: `ip_address` ilə track edilir
- Hər istifadəçi bir dəfə sayılır

---

## ⚠️ Error Responses

### **404 Not Found**

Sual tapılmadıqda:

```json
{
  "message": "No query results for model [App\\Models\\ForumQuestion] {id}"
}
```

### **403 Forbidden** (Ola bilər)

Private suala baxmaq istədikdə (admin/trainer deyilsə):

```json
{
  "message": "This action is unauthorized."
}
```

---

## 💻 Frontend İstifadə Nümunəsi

### **JavaScript/Vue.js:**

```javascript
// services/forum.js
import api from './api'

export async function getQuestionDetails(questionId) {
  try {
    const { data } = await api.get(`/api/v1/forum/questions/${questionId}`)
    return { success: true, data }
  } catch (error) {
    console.error('Error loading question:', error)
    return { 
      success: false, 
      error: error.response?.data?.message || 'Sual yüklənə bilmədi' 
    }
  }
}
```

### **Vue Component:**

```vue
<template>
  <div v-if="loading">Yüklənir...</div>
  
  <div v-else-if="question" class="question-detail">
    <h1>{{ question.title }}</h1>
    
    <div class="question-meta">
      <span>{{ question.user.first_name }} {{ question.user.last_name }}</span>
      <span>{{ formatDate(question.created_at) }}</span>
    </div>
    
    <div class="question-body">
      {{ question.body }}
    </div>
    
    <!-- Statistikalar -->
    <div class="stats">
      <ForumLikeButton
        :is-liked="question.is_liked"
        :count="question.likes_count"
        :on-toggle="() => toggleLike(question.id, question.is_liked)"
      />
      
      <span>👁️ {{ question.stats.views }} baxış</span>
      <span>👥 {{ question.stats.unique_viewers }} nəfər</span>
      <span>💬 {{ question.stats.answers_count }} cavab</span>
    </div>
    
    <!-- Cavablar -->
    <div class="answers">
      <h3>Cavablar ({{ question.answers.length }})</h3>
      
      <ForumAnswer
        v-for="answer in question.answers"
        :key="answer.id"
        :answer="answer"
        @like="toggleAnswerLike(answer.id, answer.is_liked)"
      />
    </div>
    
    <!-- Cavab Formu -->
    <div v-if="isAuthenticated" class="answer-form">
      <textarea v-model="answerText" placeholder="Cavab yazın..."></textarea>
      <button @click="submitAnswer">Göndər</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { getQuestionDetails } from '@/services/forum'
import ForumLikeButton from '@/components/ForumLikeButton.vue'
import ForumAnswer from '@/components/ForumAnswer.vue'

const route = useRoute()
const question = ref(null)
const loading = ref(true)
const answerText = ref('')

const isAuthenticated = computed(() => {
  // Sizin auth check məntiqiniz
  return true
})

const loadQuestion = async () => {
  try {
    loading.value = true
    const result = await getQuestionDetails(route.params.id)
    if (result.success) {
      question.value = result.data
    }
  } catch (error) {
    console.error('Error:', error)
  } finally {
    loading.value = false
  }
}

const toggleLike = async (questionId, isLiked) => {
  // Like/unlike məntiq
}

const toggleAnswerLike = async (answerId, isLiked) => {
  // Answer like/unlike məntiq
}

const submitAnswer = async () => {
  // Cavab göndərmə məntiq
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('az-AZ', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

onMounted(() => {
  loadQuestion()
})
</script>
```

---

## 🔄 İş Axını

1. İstifadəçi sual detalları səhifəsinə keçir
2. Frontend `GET /api/v1/forum/questions/{id}` çağırır
3. Backend:
   - Sualın mövcudluğunu yoxlayır
   - Görünüş icazəsini yoxlayır (public və ya admin/trainer)
   - Baxış tracking edir (bir dəfə)
   - Bütün məlumatları (sual, cavablar, statistikalar) yükləyir
   - Like statuslarını yoxlayır
4. Response qaytarılır
5. Frontend məlumatları göstərir

---

## ✅ Xülasə

- ✅ Endpoint mövcuddur: `GET /api/v1/forum/questions/{question}`
- ✅ Bütün sual məlumatları
- ✅ Bütün cavablar like statusu ilə
- ✅ Statistikalar
- ✅ Baxış tracking
- ✅ Like status
- ✅ Görünüş kontrolü

Endpoint hazırdır və işləyir! 🚀

