# Forum Silmə Funksionallığı - Sənədləşmə

## ✅ Yeni Endpoint-lər

İndi istifadəçilər öz suallarını və cavablarını silə bilirlər, admin isə istənilən sual və cavabı silə bilir.

---

## 🔑 1. İstifadəçi Öz Sualını Silə Bilər

### **DELETE /api/v1/my/forum/questions/{question}**

İstifadəçi yalnız öz suallarını silə bilər.

**Authentication:** Tələb olunur (`auth:sanctum`)

**Request:**
```
DELETE /api/v1/my/forum/questions/1
```

**Response (200 OK):**
```json
{
  "message": "Question deleted successfully"
}
```

**Response (403 Forbidden) - Başqasının sualını silməyə cəhd:**
```json
{
  "message": "You can only delete your own questions"
}
```

**Response (404 Not Found) - Sual tapılmadı:**
```json
{
  "message": "No query results for model [App\\Models\\ForumQuestion] {id}"
}
```

---

## 🔑 2. İstifadəçi Öz Cavabını Silə Bilər

### **DELETE /api/v1/my/forum/answers/{answer}**

İstifadəçi yalnız öz cavablarını silə bilər.

**Authentication:** Tələb olunur (`auth:sanctum`)

**Request:**
```
DELETE /api/v1/my/forum/answers/5
```

**Response (200 OK):**
```json
{
  "message": "Answer deleted successfully"
}
```

**Response (403 Forbidden) - Başqasının cavabını silməyə cəhd:**
```json
{
  "message": "You can only delete your own answers"
}
```

**Response (404 Not Found) - Cavab tapılmadı:**
```json
{
  "message": "No query results for model [App\\Models\\ForumAnswer] {id}"
}
```

---

## 🔑 3. Admin İstənilən Sualı Silə Bilər

### **DELETE /api/v1/forum/questions/{question}**

Admin istənilən sualı silə bilər (artıq mövcud idi).

**Authentication:** Tələb olunur (`auth:sanctum`)
**Middleware:** `role:admin`

**Request:**
```
DELETE /api/v1/forum/questions/1
```

**Response (200 OK):**
```json
{
  "message": "Question deleted successfully"
}
```

---

## 🔑 4. Admin İstənilən Cavabı Silə Bilər

### **DELETE /api/v1/forum/answers/{answer}**

Admin istənilən cavabı silə bilər.

**Authentication:** Tələb olunur (`auth:sanctum`)
**Middleware:** `role:admin`

**Request:**
```
DELETE /api/v1/forum/answers/5
```

**Response (200 OK):**
```json
{
  "message": "Answer deleted successfully"
}
```

---

## 📊 Endpoint Cədvəli

| Endpoint | Method | Auth | Role | Təsvir |
|----------|--------|------|------|--------|
| `/api/v1/my/forum/questions/{question}` | DELETE | ✅ | - | İstifadəçi öz sualını silir |
| `/api/v1/my/forum/answers/{answer}` | DELETE | ✅ | - | İstifadəçi öz cavabını silir |
| `/api/v1/forum/questions/{question}` | DELETE | ✅ | admin | Admin istənilən sualı silir |
| `/api/v1/forum/answers/{answer}` | DELETE | ✅ | admin | Admin istənilən cavabı silir |

---

## 💻 Frontend İstifadə Nümunələri

### **JavaScript Service:**

```javascript
// services/forum.js
import api from './api'

/**
 * İstifadəçi öz sualını silir
 */
export async function deleteMyQuestion(questionId) {
  try {
    const { data } = await api.delete(`/api/v1/my/forum/questions/${questionId}`)
    return { success: true, data }
  } catch (error) {
    console.error('Delete question error:', error)
    return { 
      success: false, 
      error: error.response?.data?.message || 'Xəta baş verdi' 
    }
  }
}

/**
 * İstifadəçi öz cavabını silir
 */
export async function deleteMyAnswer(answerId) {
  try {
    const { data } = await api.delete(`/api/v1/my/forum/answers/${answerId}`)
    return { success: true, data }
  } catch (error) {
    console.error('Delete answer error:', error)
    return { 
      success: false, 
      error: error.response?.data?.message || 'Xəta baş verdi' 
    }
  }
}

/**
 * Admin istənilən sualı silir
 */
export async function deleteQuestion(questionId) {
  try {
    const { data } = await api.delete(`/api/v1/forum/questions/${questionId}`)
    return { success: true, data }
  } catch (error) {
    console.error('Delete question error:', error)
    return { 
      success: false, 
      error: error.response?.data?.message || 'Xəta baş verdi' 
    }
  }
}

/**
 * Admin istənilən cavabı silir
 */
export async function deleteAnswer(answerId) {
  try {
    const { data } = await api.delete(`/api/v1/forum/answers/${answerId}`)
    return { success: true, data }
  } catch (error) {
    console.error('Delete answer error:', error)
    return { 
      success: false, 
      error: error.response?.data?.message || 'Xəta baş verdi' 
    }
  }
}
```

---

### **Vue Component - Sual Silmə:**

```vue
<template>
  <div class="question-item">
    <h3>{{ question.title }}</h3>
    <p>{{ question.body }}</p>
    
    <!-- İstifadəçi öz sualını görürsə -->
    <button 
      v-if="isMyQuestion"
      @click="handleDelete"
      class="delete-btn"
      :disabled="isDeleting"
    >
      {{ isDeleting ? 'Silinir...' : 'Sil' }}
    </button>
    
    <!-- Admin görürsə -->
    <button 
      v-else-if="isAdmin"
      @click="handleAdminDelete"
      class="delete-btn admin"
      :disabled="isDeleting"
    >
      {{ isDeleting ? 'Silinir...' : 'Admin: Sil' }}
    </button>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { deleteMyQuestion, deleteQuestion } from '@/services/forum'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  question: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['deleted'])

const authStore = useAuthStore()
const isDeleting = ref(false)

const isMyQuestion = computed(() => {
  return props.question.user_id === authStore.user?.id
})

const isAdmin = computed(() => {
  return authStore.user?.roles?.includes('admin')
})

const handleDelete = async () => {
  if (!confirm('Bu sualı silmək istədiyinizə əminsiniz?')) {
    return
  }

  isDeleting.value = true
  try {
    const result = await deleteMyQuestion(props.question.id)
    if (result.success) {
      emit('deleted', props.question.id)
      alert('Sual silindi')
    } else {
      alert(result.error || 'Xəta baş verdi')
    }
  } catch (error) {
    alert('Xəta baş verdi')
  } finally {
    isDeleting.value = false
  }
}

const handleAdminDelete = async () => {
  if (!confirm('Bu sualı silmək istədiyinizə əminsiniz? (Admin hüququ)')) {
    return
  }

  isDeleting.value = true
  try {
    const result = await deleteQuestion(props.question.id)
    if (result.success) {
      emit('deleted', props.question.id)
      alert('Sual silindi')
    } else {
      alert(result.error || 'Xəta baş verdi')
    }
  } catch (error) {
    alert('Xəta baş verdi')
  } finally {
    isDeleting.value = false
  }
}
</script>

<style scoped>
.delete-btn {
  padding: 8px 16px;
  background: #dc3545;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.delete-btn:hover:not(:disabled) {
  background: #c82333;
}

.delete-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.delete-btn.admin {
  background: #ff6b6b;
}
</style>
```

---

### **Vue Component - Cavab Silmə:**

```vue
<template>
  <div class="answer-item">
    <p>{{ answer.body }}</p>
    <div class="answer-actions">
      <!-- İstifadəçi öz cavabını görürsə -->
      <button 
        v-if="isMyAnswer"
        @click="handleDelete"
        class="delete-btn"
        :disabled="isDeleting"
      >
        {{ isDeleting ? 'Silinir...' : 'Sil' }}
      </button>
      
      <!-- Admin görürsə -->
      <button 
        v-else-if="isAdmin"
        @click="handleAdminDelete"
        class="delete-btn admin"
        :disabled="isDeleting"
      >
        {{ isDeleting ? 'Silinir...' : 'Admin: Sil' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { deleteMyAnswer, deleteAnswer } from '@/services/forum'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  answer: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['deleted'])

const authStore = useAuthStore()
const isDeleting = ref(false)

const isMyAnswer = computed(() => {
  return props.answer.user_id === authStore.user?.id
})

const isAdmin = computed(() => {
  return authStore.user?.roles?.includes('admin')
})

const handleDelete = async () => {
  if (!confirm('Bu cavabı silmək istədiyinizə əminsiniz?')) {
    return
  }

  isDeleting.value = true
  try {
    const result = await deleteMyAnswer(props.answer.id)
    if (result.success) {
      emit('deleted', props.answer.id)
      alert('Cavab silindi')
    } else {
      alert(result.error || 'Xəta baş verdi')
    }
  } catch (error) {
    alert('Xəta baş verdi')
  } finally {
    isDeleting.value = false
  }
}

const handleAdminDelete = async () => {
  if (!confirm('Bu cavabı silmək istədiyinizə əminsiniz? (Admin hüququ)')) {
    return
  }

  isDeleting.value = true
  try {
    const result = await deleteAnswer(props.answer.id)
    if (result.success) {
      emit('deleted', props.answer.id)
      alert('Cavab silindi')
    } else {
      alert(result.error || 'Xəta baş verdi')
    }
  } catch (error) {
    alert('Xəta baş verdi')
  } finally {
    isDeleting.value = false
  }
}
</script>
```

---

## 🔒 Təhlükəsizlik

### **İstifadəçi Silməsi:**
- İstifadəçi yalnız öz suallarını və cavablarını silə bilər
- Başqasının sualını/cavabını silməyə cəhd etsə, **403 Forbidden** qaytarılır
- `user_id` yoxlanılır

### **Admin Silməsi:**
- Admin istənilən sualı və cavabı silə bilər
- `role:admin` middleware ilə qorunur
- Heç bir məhdudiyyət yoxdur

---

## 🔄 İş Axını

### **İstifadəçi Öz Sualını Silir:**
1. İstifadəçi "Sil" düyməsinə basır
2. Frontend `DELETE /api/v1/my/forum/questions/{id}` çağırır
3. Backend yoxlayır: `question.user_id === request.user().id`
4. Əgər uyğun gəlirsə → Silir
5. Əgər uyğun gəlmirsə → 403 qaytarır

### **İstifadəçi Öz Cavabını Silir:**
1. İstifadəçi "Sil" düyməsinə basır
2. Frontend `DELETE /api/v1/my/forum/answers/{id}` çağırır
3. Backend yoxlayır: `answer.user_id === request.user().id`
4. Əgər uyğun gəlirsə → Silir
5. Əgər uyğun gəlmirsə → 403 qaytarır

### **Admin Silir:**
1. Admin "Sil" düyməsinə basır
2. Frontend `DELETE /api/v1/forum/questions/{id}` və ya `/forum/answers/{id}` çağırır
3. Backend admin rolunu yoxlayır
4. Heç bir məhdudiyyət olmadan silir

---

## ✅ Xülasə

- ✅ İstifadəçi öz suallarını silə bilir
- ✅ İstifadəçi öz cavablarını silə bilir
- ✅ Admin istənilən sualı silə bilir
- ✅ Admin istənilən cavabı silə bilir
- ✅ Təhlükəsizlik yoxlamaları var
- ✅ Proper error handling

Bütün silmə funksionallığı hazırdır! 🚀

