# Forum Like Sistemi - Frontend İmplementasiya Bələdçisi

## 📋 Ümumi Baxış

Bu bələdçi forum sualları və cavabları üçün like funksionallığını frontend-də necə əlavə edəcəyinizi göstərir.

---

## 🔧 1. API Service Funksiyaları

İlk öncə API çağırışları üçün service funksiyalarını yaradın.

### **`src/services/forum.js` (və ya `src/api/forum.js`)**

```javascript
import api from './api' // Sizin API instance

/**
 * Sual like qoymaq
 */
export async function likeQuestion(questionId) {
  try {
    const { data } = await api.post(`/api/v1/forum/questions/${questionId}/like`)
    return { success: true, data }
  } catch (error) {
    console.error('Like question error:', error)
    return { 
      success: false, 
      error: error.response?.data?.message || 'Xəta baş verdi' 
    }
  }
}

/**
 * Sual like silmək
 */
export async function unlikeQuestion(questionId) {
  try {
    const { data } = await api.post(`/api/v1/forum/questions/${questionId}/unlike`)
    return { success: true, data }
  } catch (error) {
    console.error('Unlike question error:', error)
    return { 
      success: false, 
      error: error.response?.data?.message || 'Xəta baş verdi' 
    }
  }
}

/**
 * Cavab like qoymaq
 */
export async function likeAnswer(answerId) {
  try {
    const { data } = await api.post(`/api/v1/forum/answers/${answerId}/like`)
    return { success: true, data }
  } catch (error) {
    console.error('Like answer error:', error)
    return { 
      success: false, 
      error: error.response?.data?.message || 'Xəta baş verdi' 
    }
  }
}

/**
 * Cavab like silmək
 */
export async function unlikeAnswer(answerId) {
  try {
    const { data } = await api.post(`/api/v1/forum/answers/${answerId}/unlike`)
    return { success: true, data }
  } catch (error) {
    console.error('Unlike answer error:', error)
    return { 
      success: false, 
      error: error.response?.data?.message || 'Xəta baş verdi' 
    }
  }
}

/**
 * Toggle like (like/unlike avtomatik)
 */
export async function toggleQuestionLike(questionId, isCurrentlyLiked) {
  if (isCurrentlyLiked) {
    return await unlikeQuestion(questionId)
  } else {
    return await likeQuestion(questionId)
  }
}

export async function toggleAnswerLike(answerId, isCurrentlyLiked) {
  if (isCurrentlyLiked) {
    return await unlikeAnswer(answerId)
  } else {
    return await likeAnswer(answerId)
  }
}
```

---

## 🎨 2. Vue.js Komponent Nümunələri

### **2.1. Like Button Komponenti (Reusable)**

**`src/components/ForumLikeButton.vue`**

```vue
<template>
  <button
    :class="[
      'like-button',
      { 'liked': isLiked, 'loading': isLoading }
    ]"
    @click="handleClick"
    :disabled="isLoading || !isAuthenticated"
    :title="isAuthenticated ? '' : 'Like etmək üçün giriş edin'"
  >
    <span class="like-icon" :class="{ 'filled': isLiked }">
      {{ isLiked ? '❤️' : '🤍' }}
    </span>
    <span class="like-count">{{ count }}</span>
  </button>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth' // Sizin auth store-unuz

const props = defineProps({
  isLiked: {
    type: Boolean,
    default: false
  },
  count: {
    type: Number,
    default: 0
  },
  onToggle: {
    type: Function,
    required: true
  }
})

const emit = defineEmits(['update:isLiked', 'update:count'])

const authStore = useAuthStore()
const isLoading = ref(false)

const isAuthenticated = computed(() => authStore.isAuthenticated)

const handleClick = async () => {
  if (!isAuthenticated.value) {
    // İstəyə görə login səhifəsinə yönləndirə bilərsiniz
    return
  }

  isLoading.value = true
  
  try {
    const result = await props.onToggle()
    
    if (result.success) {
      emit('update:isLiked', result.data.is_liked)
      emit('update:count', result.data.likes_count)
    } else {
      // Xəta mesajı göstərin
      console.error(result.error)
    }
  } catch (error) {
    console.error('Like toggle error:', error)
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
.like-button {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border: 1px solid #e0e0e0;
  border-radius: 20px;
  background: white;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 14px;
}

.like-button:hover:not(:disabled) {
  border-color: #ff4757;
  background: #fff5f5;
}

.like-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.like-button.liked {
  border-color: #ff4757;
  background: #fff5f5;
  color: #ff4757;
}

.like-button.loading {
  opacity: 0.7;
  cursor: wait;
}

.like-icon {
  font-size: 18px;
  transition: transform 0.2s;
}

.like-button:hover:not(:disabled) .like-icon {
  transform: scale(1.2);
}

.like-count {
  font-weight: 500;
  min-width: 20px;
  text-align: center;
}
</style>
```

---

### **2.2. Forum Sual Komponenti**

**`src/components/ForumQuestion.vue`**

```vue
<template>
  <div class="forum-question">
    <div class="question-header">
      <h3>{{ question.title }}</h3>
      <div class="question-meta">
        <span class="author">{{ question.user?.first_name }} {{ question.user?.last_name }}</span>
        <span class="date">{{ formatDate(question.created_at) }}</span>
      </div>
    </div>

    <div class="question-body">
      <p>{{ question.body }}</p>
    </div>

    <div class="question-stats">
      <!-- Like Button -->
      <ForumLikeButton
        :is-liked="question.is_liked"
        :count="question.likes_count || 0"
        :on-toggle="() => toggleQuestionLike(question.id, question.is_liked)"
        @update:is-liked="updateQuestionLikeStatus"
        @update:count="updateQuestionLikeCount"
      />

      <!-- Digər statistikalar -->
      <div class="stat-item">
        <span class="stat-icon">👁️</span>
        <span>{{ question.views || 0 }} baxış</span>
      </div>

      <div class="stat-item">
        <span class="stat-icon">💬</span>
        <span>{{ question.answers_count || 0 }} cavab</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'
import ForumLikeButton from './ForumLikeButton.vue'
import { toggleQuestionLike } from '@/services/forum'

const props = defineProps({
  question: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['update'])

const updateQuestionLikeStatus = (isLiked) => {
  emit('update', {
    ...props.question,
    is_liked: isLiked
  })
}

const updateQuestionLikeCount = (count) => {
  emit('update', {
    ...props.question,
    likes_count: count
  })
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('az-AZ', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}
</script>

<style scoped>
.forum-question {
  background: white;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 16px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.question-header h3 {
  margin: 0 0 8px 0;
  color: #333;
}

.question-meta {
  display: flex;
  gap: 12px;
  font-size: 14px;
  color: #666;
  margin-bottom: 16px;
}

.question-body {
  margin-bottom: 16px;
  line-height: 1.6;
}

.question-stats {
  display: flex;
  align-items: center;
  gap: 16px;
  padding-top: 12px;
  border-top: 1px solid #e0e0e0;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 14px;
  color: #666;
}

.stat-icon {
  font-size: 16px;
}
</style>
```

---

### **2.3. Forum Cavab Komponenti**

**`src/components/ForumAnswer.vue`**

```vue
<template>
  <div class="forum-answer">
    <div class="answer-header">
      <div class="answer-author">
        <img 
          :src="answer.user?.avatar || '/default-avatar.png'" 
          :alt="answer.user?.first_name"
          class="avatar"
        />
        <div>
          <strong>{{ answer.user?.first_name }} {{ answer.user?.last_name }}</strong>
          <span class="answer-date">{{ formatDate(answer.created_at) }}</span>
        </div>
      </div>
    </div>

    <div class="answer-body">
      <p>{{ answer.body }}</p>
    </div>

    <div class="answer-actions">
      <!-- Like Button -->
      <ForumLikeButton
        :is-liked="answer.is_liked"
        :count="answer.likes_count || 0"
        :on-toggle="() => toggleAnswerLike(answer.id, answer.is_liked)"
        @update:is-liked="updateAnswerLikeStatus"
        @update:count="updateAnswerLikeCount"
      />
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'
import ForumLikeButton from './ForumLikeButton.vue'
import { toggleAnswerLike } from '@/services/forum'

const props = defineProps({
  answer: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['update'])

const updateAnswerLikeStatus = (isLiked) => {
  emit('update', {
    ...props.answer,
    is_liked: isLiked
  })
}

const updateAnswerLikeCount = (count) => {
  emit('update', {
    ...props.answer,
    likes_count: count
  })
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('az-AZ', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<style scoped>
.forum-answer {
  background: #f9f9f9;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 12px;
  border-left: 3px solid #007bff;
}

.answer-header {
  margin-bottom: 12px;
}

.answer-author {
  display: flex;
  align-items: center;
  gap: 12px;
}

.avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
}

.answer-date {
  display: block;
  font-size: 12px;
  color: #666;
  margin-top: 2px;
}

.answer-body {
  margin-bottom: 12px;
  line-height: 1.6;
}

.answer-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}
</style>
```

---

### **2.4. Forum Sual Detalları Səhifəsi**

**`src/views/ForumQuestionDetail.vue`**

```vue
<template>
  <div class="forum-question-detail">
    <div v-if="loading" class="loading">Yüklənir...</div>
    
    <div v-else-if="question" class="question-detail">
      <!-- Sual -->
      <div class="question-card">
        <h1>{{ question.title }}</h1>
        <div class="question-meta">
          <span>{{ question.user?.first_name }} {{ question.user?.last_name }}</span>
          <span>{{ formatDate(question.created_at) }}</span>
        </div>
        
        <div class="question-content">
          {{ question.body }}
        </div>

        <div class="question-actions">
          <ForumLikeButton
            :is-liked="question.is_liked"
            :count="question.likes_count || 0"
            :on-toggle="() => toggleQuestionLike(question.id, question.is_liked)"
            @update:is-liked="question.is_liked = $event"
            @update:count="question.likes_count = $event"
          />

          <div class="stats">
            <span>👁️ {{ question.stats?.views || 0 }} baxış</span>
            <span>💬 {{ question.stats?.answers_count || 0 }} cavab</span>
          </div>
        </div>
      </div>

      <!-- Cavab Form -->
      <div class="answer-form" v-if="isAuthenticated">
        <h3>Cavab yazın</h3>
        <textarea 
          v-model="answerText" 
          placeholder="Cavabınızı yazın..."
          rows="4"
        ></textarea>
        <button @click="submitAnswer" :disabled="!answerText.trim()">
          Cavab göndər
        </button>
      </div>

      <!-- Cavablar -->
      <div class="answers-section">
        <h3>Cavablar ({{ answers.length }})</h3>
        
        <div v-if="answersLoading">Yüklənir...</div>
        <div v-else-if="answers.length === 0" class="no-answers">
          Hələ cavab yoxdur. İlk cavabı siz yazın!
        </div>
        
        <div v-else>
          <ForumAnswer
            v-for="answer in answers"
            :key="answer.id"
            :answer="answer"
            @update="updateAnswer"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import ForumLikeButton from '@/components/ForumLikeButton.vue'
import ForumAnswer from '@/components/ForumAnswer.vue'
import { toggleQuestionLike } from '@/services/forum'
import api from '@/services/api'

const route = useRoute()
const authStore = useAuthStore()

const question = ref(null)
const answers = ref([])
const loading = ref(true)
const answersLoading = ref(false)
const answerText = ref('')

const isAuthenticated = computed(() => authStore.isAuthenticated)

const loadQuestion = async () => {
  try {
    loading.value = true
    const { data } = await api.get(`/api/v1/forum/questions/${route.params.id}`)
    question.value = data
  } catch (error) {
    console.error('Error loading question:', error)
  } finally {
    loading.value = false
  }
}

const loadAnswers = async () => {
  try {
    answersLoading.value = true
    const { data } = await api.get(`/api/v1/forum/questions/${route.params.id}/answers`)
    answers.value = data.data
  } catch (error) {
    console.error('Error loading answers:', error)
  } finally {
    answersLoading.value = false
  }
}

const submitAnswer = async () => {
  if (!answerText.value.trim()) return

  try {
    const { data } = await api.post(`/api/v1/forum/questions/${route.params.id}/answers`, {
      body: answerText.value
    })
    
    answers.value.unshift({
      ...data,
      is_liked: false,
      likes_count: 0
    })
    
    answerText.value = ''
    
    // Sualın cavab sayını yenilə
    if (question.value.stats) {
      question.value.stats.answers_count = (question.value.stats.answers_count || 0) + 1
    }
  } catch (error) {
    console.error('Error submitting answer:', error)
    alert('Cavab göndərilmədi. Xahiş edirik yenidən cəhd edin.')
  }
}

const updateAnswer = (updatedAnswer) => {
  const index = answers.value.findIndex(a => a.id === updatedAnswer.id)
  if (index !== -1) {
    answers.value[index] = updatedAnswer
  }
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('az-AZ', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  loadQuestion()
  loadAnswers()
})
</script>

<style scoped>
.forum-question-detail {
  max-width: 900px;
  margin: 0 auto;
  padding: 20px;
}

.loading {
  text-align: center;
  padding: 40px;
}

.question-card {
  background: white;
  border-radius: 8px;
  padding: 24px;
  margin-bottom: 24px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.question-card h1 {
  margin: 0 0 12px 0;
  color: #333;
}

.question-meta {
  display: flex;
  gap: 16px;
  font-size: 14px;
  color: #666;
  margin-bottom: 16px;
}

.question-content {
  margin-bottom: 20px;
  line-height: 1.8;
  font-size: 16px;
}

.question-actions {
  display: flex;
  align-items: center;
  gap: 20px;
  padding-top: 16px;
  border-top: 1px solid #e0e0e0;
}

.stats {
  display: flex;
  gap: 16px;
  font-size: 14px;
  color: #666;
}

.answer-form {
  background: white;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 24px;
}

.answer-form h3 {
  margin: 0 0 16px 0;
}

.answer-form textarea {
  width: 100%;
  padding: 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-family: inherit;
  resize: vertical;
  margin-bottom: 12px;
}

.answer-form button {
  padding: 10px 20px;
  background: #007bff;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.answer-form button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.answers-section h3 {
  margin-bottom: 16px;
}

.no-answers {
  text-align: center;
  padding: 40px;
  color: #666;
}
</style>
```

---

## 🔄 3. Vuex/Pinia Store (Opsional)

Əgər global state idarə etmək istəyirsinizsə:

**`src/stores/forum.js` (Pinia)**

```javascript
import { defineStore } from 'pinia'
import { likeQuestion, unlikeQuestion, likeAnswer, unlikeAnswer } from '@/services/forum'

export const useForumStore = defineStore('forum', {
  state: () => ({
    questions: [],
    currentQuestion: null
  }),

  actions: {
    async toggleQuestionLike(questionId, isCurrentlyLiked) {
      const result = isCurrentlyLiked 
        ? await unlikeQuestion(questionId)
        : await likeQuestion(questionId)

      if (result.success) {
        // Local state-i yenilə
        const question = this.questions.find(q => q.id === questionId)
        if (question) {
          question.is_liked = result.data.is_liked
          question.likes_count = result.data.likes_count
        }

        if (this.currentQuestion?.id === questionId) {
          this.currentQuestion.is_liked = result.data.is_liked
          this.currentQuestion.likes_count = result.data.likes_count
        }
      }

      return result
    },

    async toggleAnswerLike(answerId, isCurrentlyLiked) {
      const result = isCurrentlyLiked 
        ? await unlikeAnswer(answerId)
        : await likeAnswer(answerId)

      if (result.success && this.currentQuestion?.answers) {
        const answer = this.currentQuestion.answers.find(a => a.id === answerId)
        if (answer) {
          answer.is_liked = result.data.is_liked
          answer.likes_count = result.data.likes_count
        }
      }

      return result
    }
  }
})
```

---

## 🎯 4. İstifadə Nümunəsi

**`src/views/Forum.vue` (Forum siyahısı)**

```vue
<template>
  <div class="forum-page">
    <h1>Forum Suallar</h1>
    
    <div v-if="loading">Yüklənir...</div>
    
    <div v-else>
      <ForumQuestion
        v-for="q in questions"
        :key="q.id"
        :question="q"
        @update="updateQuestion"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import ForumQuestion from '@/components/ForumQuestion.vue'
import api from '@/services/api'

const questions = ref([])
const loading = ref(true)

const loadQuestions = async () => {
  try {
    const { data } = await api.get('/api/v1/forum/questions')
    questions.value = data.data
  } catch (error) {
    console.error('Error loading questions:', error)
  } finally {
    loading.value = false
  }
}

const updateQuestion = (updatedQuestion) => {
  const index = questions.value.findIndex(q => q.id === updatedQuestion.id)
  if (index !== -1) {
    questions.value[index] = updatedQuestion
  }
}

onMounted(() => {
  loadQuestions()
})
</script>
```

---

## ✅ 5. Best Practices

1. **Optimistic Updates**: Like düyməsinə basılanda dərhal UI-da dəyişikliyi göstərin, sonra API çağırışını edin
2. **Error Handling**: Xəta baş verdikdə istifadəçiyə məlumat verin və state-i geri qaytarın
3. **Loading States**: İşlər aparılarkən loading indicator göstərin
4. **Authentication Check**: Unauthenticated istifadəçilər üçün like düyməsini disabled edin
5. **Debouncing**: Çox sayda like/unlike əməliyyatını qarşısını almaq üçün debounce istifadə edin

---

## 🎨 6. Animasiya və Effektlər

Like düyməsinə animasiya əlavə etmək istəyirsinizsə:

```vue
<template>
  <button
    @click="handleLike"
    :class="{ 'liked': isLiked, 'animating': isAnimating }"
    class="like-btn"
  >
    <span class="heart">❤️</span>
    <span class="count">{{ count }}</span>
  </button>
</template>

<script setup>
import { ref } from 'vue'

const isAnimating = ref(false)

const handleLike = async () => {
  isAnimating.value = true
  // API çağırışı
  setTimeout(() => {
    isAnimating.value = false
  }, 300)
}
</script>

<style scoped>
.like-btn {
  transition: all 0.3s ease;
}

.like-btn.animating .heart {
  animation: heartbeat 0.3s ease;
}

@keyframes heartbeat {
  0% { transform: scale(1); }
  50% { transform: scale(1.3); }
  100% { transform: scale(1); }
}
</style>
```

---

## 📱 7. Mobile Responsive

Mobile cihazlar üçün:

```css
@media (max-width: 768px) {
  .like-button {
    padding: 8px 10px;
    font-size: 12px;
  }

  .question-stats {
    flex-wrap: wrap;
    gap: 8px;
  }
}
```

---

Bu bələdçi ilə forum like sistemini frontend-də tam implementasiya edə bilərsiniz! 🚀

