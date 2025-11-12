# 🌐 Çoxdilli Sistem - User Frontend Developer Guide

## 📋 Ümumi Məlumat

Bu bələdçi **user-facing (istifadəçi tərəfi) frontend** developer-lər üçündür. User interface-də çoxdilli sistemin necə işlədiyini, dil seçiminin necə təmin olunacağını və content-in necə göstəriləcəyini əhatə edir.

### Dəstəklənən Dillər:
- `az` - Azərbaycan (default)
- `en` - İngilis
- `ru` - Rus

---

## 🎯 User Frontend Üçün Əsas Prinsiplər

### 1. **Avtomatik Translation**
- Backend **avtomatik olaraq** request-dəki dil parametrinə görə düzgün versiyanı qaytarır
- Frontend-də **heç bir translation logic** yoxdur
- Sadəcə `?lang=xx` parametri əlavə etmək kifayətdir

### 2. **Dil Seçimi**
- İstifadəçi dil seçir
- Seçim `localStorage`-da saxlanılır
- Bütün API request-ləri seçilmiş dillə göndərilir

### 3. **Content Display**
- Content-lər avtomatik olaraq seçilmiş dildə göstərilir
- Fallback: Əgər seçilmiş dil üçün content yoxdursa, default (az) versiyası göstərilir

---

## 📥 API RESPONSE FORMAT - DƏTALLI İZAHAT

### Necə İşləyir?

**Backend-də nə baş verir:**
1. Frontend request göndərir: `GET /api/v1/trainings?lang=en`
2. Middleware `lang=en` parametrini oxuyur və `App::setLocale('en')` edir
3. Controller model-i çağırır: `Training::all()`
4. Model-in `getAttribute()` metodu işləyir (HasTranslations trait-dən)
5. `title` field-i üçün JSON-dan `en` versiyasını extract edir
6. Laravel model-i JSON-a serialize edəndə, **translate olunmuş string** qaytarılır

**Frontend-də nə gəlir:**
- Response-da **artıq translate olunmuş string** gəlir
- JSON object formatında **DEYİL**, sadəcə **string** formatında
- Frontend-də **heç bir processing lazım deyil**

---

### Response Nümunələri

#### Nümunə 1: Training List (GET /api/v1/trainings?lang=en)

**Backend-də Database-də:**
```json
{
  "id": 1,
  "title": {
    "az": "Aqrar Texnologiyalar",
    "en": "Agricultural Technologies",
    "ru": "Сельскохозяйственные технологии"
  },
  "description": {
    "az": "Müasir aqrar texnologiyalar haqqında",
    "en": "About modern agricultural technologies",
    "ru": "О современных сельскохозяйственных технологиях"
  }
}
```

**Frontend-ə gələn Response:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Agricultural Technologies",  // ✅ Artıq translate olunub (string)
      "description": "About modern agricultural technologies",  // ✅ String
      "category": "Technology",
      "trainer_id": 1,
      "start_date": "2025-11-02",
      "end_date": "2025-11-22"
    }
  ]
}
```

**⚠️ QEYD:** `title` və `description` artıq **object deyil, string-dir**! Backend avtomatik olaraq `lang=en` parametrinə görə İngilis versiyasını extract edib string kimi qaytarır.

---

#### Nümunə 2: Training Detail (GET /api/v1/trainings/1?lang=ru)

**Frontend-ə gələn Response:**
```json
{
  "id": 1,
  "title": "Сельскохозяйственные технологии",  // ✅ Rus versiyası (string)
  "description": "О современных сельскохозяйственных технологиях",  // ✅ Rus versiyası
  "category": "Technology",
  "trainer": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe"
  },
  "modules": [
    {
      "id": 1,
      "title": "Modul 1",  // ✅ Module title da translate olunub
      "lessons": [
        {
          "id": 1,
          "title": "Dərs 1",  // ✅ Lesson title da translate olunub
          "content": "Dərs məzmunu...",
          "description": "Dərs təsviri..."
        }
      ]
    }
  ]
}
```

**QEYD:** Nested relation-lar (modules, lessons) da avtomatik olaraq translate olunur!

---

#### Nümunə 3: Default Language (GET /api/v1/trainings)

**Əgər `lang` parametri göndərilməsə:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Aqrar Texnologiyalar",  // ✅ Default: Azərbaycan (string)
      "description": "Müasir aqrar texnologiyalar haqqında"
    }
  ]
}
```

---

#### Nümunə 4: Fallback Behavior

**Ssenari:** `lang=en` göndərirsiniz, amma `title`-in yalnız `az` versiyası var.

**Database-də:**
```json
{
  "title": {
    "az": "Test Təlim"
  }
}
```

**Response:**
```json
{
  "id": 1,
  "title": "Test Təlim"  // ✅ Fallback: az versiyası göstərilir
}
```

**Backend avtomatik olaraq:**
1. Əvvəlcə `en` versiyasını axtarır → tapılmadı
2. Sonra `az` (default) versiyasını axtarır → tapıldı
3. `az` versiyasını qaytarır

---

### Tək bir Response-da fərqli dillər?

**❌ MÜMKÜN DEYİL!** Bir request-də yalnız bir dil versiyası gəlir.

Əgər bütün dillərin versiyalarını istəyirsinizsə (admin panel üçün), ayrı endpoint istifadə edin:

```
GET /api/v1/trainings/1/translations
```

**Response:**
```json
{
  "id": 1,
  "title": {
    "az": "Aqrar Texnologiyalar",
    "en": "Agricultural Technologies",
    "ru": "Сельскохозяйственные технологии"
  },
  "description": {
    "az": "Müasir aqrar texnologiyalar haqqında",
    "en": "About modern agricultural technologies"
  }
}
```

**QEYD:** User frontend üçün bu endpoint **lazım deyil**. User frontend sadəcə `?lang=xx` parametri ilə istifadə edir.

---

### Nested Relations (Modules, Lessons)

Nested relation-lar da avtomatik olaraq translate olunur:

**Request:** `GET /api/v1/trainings/1?lang=en`

**Response:**
```json
{
  "id": 1,
  "title": "Agricultural Technologies",
  "modules": [
    {
      "id": 1,
      "title": "Module 1",  // ✅ Translate olunub
      "lessons": [
        {
          "id": 1,
          "title": "Lesson 1",  // ✅ Translate olunub
          "content": "Lesson content...",  // ✅ Translate olunub
          "description": "Lesson description..."
        }
      ]
    }
  ]
}
```

**Backend-də nə baş verir:**
- `Training` model-i load olunur
- `modules` relation load olunur
- Hər `TrainingModule` model-inin `getAttribute()` metodu işləyir
- `lessons` relation load olunur
- Hər `TrainingLesson` model-inin `getAttribute()` metodu işləyir
- Bütün nested data-lar avtomatik olaraq translate olunur

---

### Paginated Responses

**Request:** `GET /api/v1/trainings?lang=en&page=1&per_page=10`

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Agricultural Technologies",  // ✅ String
      "description": "About modern agricultural technologies"
    },
    {
      "id": 2,
      "title": "Modern Farming",  // ✅ String
      "description": "Learn modern farming techniques"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 10,
    "total": 50
  },
  "links": {
    "first": "/api/v1/trainings?page=1",
    "last": "/api/v1/trainings?page=5",
    "prev": null,
    "next": "/api/v1/trainings?page=2"
  }
}
```

**QEYD:** Pagination link-lərində `lang` parametri **avtomatik əlavə olunmur**. Frontend-də manual əlavə etmək lazımdır:

```javascript
// Pagination link-lərini işləyərkən
const nextPageUrl = response.links.next;
if (nextPageUrl) {
  const url = new URL(nextPageUrl);
  url.searchParams.set('lang', currentLanguage);
  // Use updated URL
}
```

---

### Collection Responses (Array)

**Request:** `GET /api/v1/categories?lang=en`

**Response:**
```json
[
  {
    "id": 1,
    "name": "Technology",  // ✅ String
    "description": "Technology related trainings"
  },
  {
    "id": 2,
    "name": "Business",  // ✅ String
    "description": "Business related trainings"
  }
]
```

---

### Single Resource Response

**Request:** `GET /api/v1/trainings/1?lang=ru`

**Response:**
```json
{
  "id": 1,
  "title": "Сельскохозяйственные технологии",
  "description": "О современных сельскохозяйственных технологиях",
  "category": "Technology",
  "trainer_id": 1,
  "start_date": "2025-11-02",
  "end_date": "2025-11-22",
  "type": "video",
  "difficulty": "advanced",
  "status": "published",
  "created_at": "2025-10-01T10:00:00.000000Z",
  "updated_at": "2025-11-01T15:30:00.000000Z",
  "trainer": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com"
  }
}
```

---

### Error Responses

**Error response-lər həmişə default (az) dilində olur** (və ya error message-lər translation olunmur):

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "title": [
      "The title field is required."
    ]
  }
}
```

**QEYD:** Error message-ləri hələ də multilang deyil. Gələcəkdə əlavə edilə bilər.

---

### Frontend-də İstifadə

**Sadə istifadə:**
```javascript
// API call
const response = await fetch('/api/v1/trainings?lang=en');
const data = await response.json();

// Data artıq translate olunub, sadəcə göstər
console.log(data.data[0].title);  // "Agricultural Technologies" (string)

// ❌ Object deyil, string-dir!
// console.log(data.data[0].title.az);  // UNDEFINED - bu format yoxdur!
```

**Component-də göstərmə:**
```vue
<template>
  <div v-for="training in trainings" :key="training.id">
    <h3>{{ training.title }}</h3>
    <!-- ✅ Sadəcə string kimi göstər, heç bir processing lazım deyil -->
    <p>{{ training.description }}</p>
  </div>
</template>
```

---

### Real Network Request/Response Nümunəsi

**Request:**
```
GET /api/v1/trainings/1?lang=en HTTP/1.1
Host: localhost:8000
Authorization: Bearer token123
Accept: application/json
```

**Response:**
```
HTTP/1.1 200 OK
Content-Type: application/json

{
  "id": 1,
  "title": "Agricultural Technologies",
  "description": "About modern agricultural technologies",
  "category": "Technology",
  "trainer_id": 1,
  "start_date": "2025-11-02",
  "end_date": "2025-11-22",
  "type": "video",
  "difficulty": "advanced",
  "status": "published",
  "trainer": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe"
  },
  "modules": [
    {
      "id": 1,
      "training_id": 1,
      "title": "Module 1",
      "sequence": 1,
      "lessons": [
        {
          "id": 1,
          "module_id": 1,
          "title": "Lesson 1",
          "content": "Lesson content in English...",
          "description": "Lesson description..."
        }
      ]
    }
  ]
}
```

**Browser Network Tab-də görünən:**
- Request URL: `http://localhost:8000/api/v1/trainings/1?lang=en`
- Response Body: Yuxarıdakı JSON (artıq translate olunub)

---

### Frontend Developer Üçün Əsas Qeyd

**✅ DO:**
```javascript
// Response-dan gələn string-i direkt istifadə et
<h1>{{ training.title }}</h1>
<p>{{ training.description }}</p>
```

**❌ DON'T:**
```javascript
// ❌ Response-dan object gözləmə
training.title.az  // UNDEFINED - title artıq string-dir!

// ❌ Manual translation etmə
getTranslation(training.title, 'en')  // LAZIM DEYİL - artıq translate olunub!

// ❌ JSON parse etmə
JSON.parse(training.title)  // ERROR - title string-dir, JSON deyil!
```

---

## 🔧 1. Dil Seçimi Komponenti

### Vue.js Dil Seçimi Komponenti

```vue
<template>
  <div class="language-selector">
    <select 
      v-model="selectedLanguage" 
      @change="onLanguageChange"
      class="language-select"
    >
      <option value="az">🇦🇿 Azərbaycan</option>
      <option value="en">🇬🇧 English</option>
      <option value="ru">🇷🇺 Русский</option>
    </select>
  </div>
</template>

<script>
export default {
  name: 'LanguageSelector',
  data() {
    return {
      selectedLanguage: this.getSavedLanguage()
    };
  },
  mounted() {
    // Page load zamanı saved language-i apply et
    this.applyLanguage(this.selectedLanguage);
  },
  methods: {
    getSavedLanguage() {
      // localStorage-dan saved language-i oxu
      return localStorage.getItem('user_language') || 'az';
    },
    onLanguageChange() {
      // Dil dəyişdikdə
      this.saveLanguage(this.selectedLanguage);
      this.applyLanguage(this.selectedLanguage);
      
      // Page-i reload et və ya state-i update et
      this.$store.commit('setLanguage', this.selectedLanguage);
      
      // Əgər lazımdırsa, page-i reload et
      // window.location.reload();
      
      // Və ya API call-ları yenidən et
      this.$emit('language-changed', this.selectedLanguage);
    },
    saveLanguage(lang) {
      localStorage.setItem('user_language', lang);
    },
    applyLanguage(lang) {
      // Global state-ə set et
      this.$store?.commit('setLanguage', lang);
      
      // Document language attribute set et
      document.documentElement.lang = lang;
      
      // İstifadəçiyə bildir
      this.$notify?.({
        type: 'success',
        message: `Dil ${this.getLanguageName(lang)}-ə dəyişdirildi`
      });
    },
    getLanguageName(code) {
      const names = {
        az: 'Azərbaycan',
        en: 'English',
        ru: 'Русский'
      };
      return names[code] || code;
    }
  }
};
</script>

<style scoped>
.language-selector {
  @apply relative;
}

.language-select {
  @apply px-4 py-2 border rounded-md bg-white cursor-pointer;
}
</style>
```

### Advanced: Dropdown with Flags

```vue
<template>
  <div class="language-selector-dropdown">
    <button 
      @click="toggleDropdown"
      class="language-button"
    >
      <span class="flag">{{ getFlag(currentLanguage) }}</span>
      <span>{{ getLanguageName(currentLanguage) }}</span>
      <span class="arrow">▼</span>
    </button>

    <div v-if="isOpen" class="dropdown-menu">
      <button
        v-for="lang in languages"
        :key="lang.code"
        @click="selectLanguage(lang.code)"
        :class="['dropdown-item', { active: currentLanguage === lang.code }]"
      >
        <span class="flag">{{ lang.flag }}</span>
        <span>{{ lang.name }}</span>
        <span v-if="currentLanguage === lang.code" class="check">✓</span>
      </button>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      isOpen: false,
      currentLanguage: localStorage.getItem('user_language') || 'az',
      languages: [
        { code: 'az', name: 'Azərbaycan', flag: '🇦🇿' },
        { code: 'en', name: 'English', flag: '🇬🇧' },
        { code: 'ru', name: 'Русский', flag: '🇷🇺' }
      ]
    };
  },
  mounted() {
    // Click outside to close
    document.addEventListener('click', this.handleClickOutside);
  },
  beforeUnmount() {
    document.removeEventListener('click', this.handleClickOutside);
  },
  methods: {
    toggleDropdown() {
      this.isOpen = !this.isOpen;
    },
    selectLanguage(code) {
      this.currentLanguage = code;
      this.isOpen = false;
      localStorage.setItem('user_language', code);
      this.$store?.commit('setLanguage', code);
      this.$emit('language-changed', code);
      
      // Reload content
      window.location.reload();
    },
    handleClickOutside(event) {
      if (!this.$el.contains(event.target)) {
        this.isOpen = false;
      }
    },
    getFlag(code) {
      const lang = this.languages.find(l => l.code === code);
      return lang?.flag || '🌐';
    },
    getLanguageName(code) {
      const lang = this.languages.find(l => l.code === code);
      return lang?.name || code;
    }
  }
};
</script>

<style scoped>
.language-selector-dropdown {
  @apply relative;
}

.language-button {
  @apply flex items-center space-x-2 px-4 py-2 border rounded-md bg-white hover:bg-gray-50;
}

.dropdown-menu {
  @apply absolute top-full mt-1 bg-white border rounded-md shadow-lg z-50 min-w-[150px];
}

.dropdown-item {
  @apply w-full flex items-center space-x-2 px-4 py-2 hover:bg-gray-100 text-left;
}

.dropdown-item.active {
  @apply bg-blue-50;
}
</style>
```

---

## 📡 2. API Service İnterqrasiyası

### API Service Wrapper (Vuex/Pinia Store ilə)

```javascript
// services/api.js
import axios from 'axios';

class ApiService {
  constructor() {
    this.baseURL = '/api/v1';
    this.client = axios.create({
      baseURL: this.baseURL,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    });

    // Request interceptor - hər request-ə lang parametri əlavə et
    this.client.interceptors.request.use(
      (config) => {
        const lang = this.getCurrentLanguage();
        
        // Query parametrinə lang əlavə et
        if (config.params) {
          config.params.lang = lang;
        } else {
          config.params = { lang };
        }

        // Token əlavə et (əgər varsa)
        const token = localStorage.getItem('auth_token');
        if (token) {
          config.headers.Authorization = `Bearer ${token}`;
        }

        return config;
      },
      (error) => {
        return Promise.reject(error);
      }
    );

    // Response interceptor
    this.client.interceptors.response.use(
      (response) => response,
      (error) => {
        // Error handling
        if (error.response?.status === 401) {
          // Unauthorized - logout
          localStorage.removeItem('auth_token');
          window.location.href = '/login';
        }
        return Promise.reject(error);
      }
    );
  }

  getCurrentLanguage() {
    // Əvvəlcə localStorage-dan oxu
    let lang = localStorage.getItem('user_language');
    
    // Əgər yoxdursa, browser language-dən təyin et
    if (!lang) {
      const browserLang = navigator.language || navigator.userLanguage;
      lang = browserLang.substring(0, 2).toLowerCase();
      
      // Əgər dəstəklənmirsə, default az
      if (!['az', 'en', 'ru'].includes(lang)) {
        lang = 'az';
      }
      
      localStorage.setItem('user_language', lang);
    }
    
    return lang;
  }

  setLanguage(lang) {
    localStorage.setItem('user_language', lang);
  }

  // GET request
  async get(endpoint, config = {}) {
    const response = await this.client.get(endpoint, config);
    return response.data;
  }

  // POST request
  async post(endpoint, data, config = {}) {
    const response = await this.client.post(endpoint, data, config);
    return response.data;
  }

  // PUT request
  async put(endpoint, data, config = {}) {
    const response = await this.client.put(endpoint, data, config);
    return response.data;
  }

  // DELETE request
  async delete(endpoint, config = {}) {
    const response = await this.client.delete(endpoint, config);
    return response.data;
  }
}

export default new ApiService();
```

### Vuex Store (Vue 2) Nümunəsi

```javascript
// store/index.js
import { createStore } from 'vuex';
import ApiService from '@/services/api';

export default createStore({
  state: {
    language: localStorage.getItem('user_language') || 'az',
    trainings: [],
    loading: false
  },
  
  mutations: {
    SET_LANGUAGE(state, lang) {
      state.language = lang;
      localStorage.setItem('user_language', lang);
      ApiService.setLanguage(lang);
    },
    
    SET_TRAININGS(state, trainings) {
      state.trainings = trainings;
    },
    
    SET_LOADING(state, loading) {
      state.loading = loading;
    }
  },
  
  actions: {
    async changeLanguage({ commit }, lang) {
      commit('SET_LANGUAGE', lang);
      
      // Reload current page data
      // Bu component-də çağırılmalıdır
      this.dispatch('loadTrainings');
    },
    
    async loadTrainings({ commit, state }) {
      commit('SET_LOADING', true);
      try {
        // API service avtomatik olaraq lang parametrini əlavə edəcək
        const trainings = await ApiService.get('/trainings');
        commit('SET_TRAININGS', trainings.data);
      } catch (error) {
        console.error('Error loading trainings:', error);
      } finally {
        commit('SET_LOADING', false);
      }
    }
  },
  
  getters: {
    currentLanguage: state => state.language,
    trainings: state => state.trainings
  }
});
```

### Pinia Store (Vue 3) Nümunəsi

```javascript
// stores/language.js
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useLanguageStore = defineStore('language', () => {
  const language = ref(localStorage.getItem('user_language') || 'az');

  const setLanguage = (lang) => {
    language.value = lang;
    localStorage.setItem('user_language', lang);
    // API service language update
    window.apiService?.setLanguage(lang);
  };

  const languageName = computed(() => {
    const names = {
      az: 'Azərbaycan',
      en: 'English',
      ru: 'Русский'
    };
    return names[language.value] || language.value;
  });

  return {
    language,
    setLanguage,
    languageName
  };
});
```

---

## 📄 3. Content Display Komponentləri

### Training List Komponenti

```vue
<template>
  <div class="trainings-list">
    <div v-if="loading" class="loading">
      Yüklənir...
    </div>

    <div v-else-if="trainings.length === 0" class="empty-state">
      <p>Təlim tapılmadı</p>
    </div>

    <div v-else class="trainings-grid">
      <div
        v-for="training in trainings"
        :key="training.id"
        class="training-card"
        @click="$router.push(`/trainings/${training.id}`)"
      >
        <img 
          v-if="training.banner_url" 
          :src="training.banner_url" 
          :alt="training.title"
          class="banner"
        />
        
        <div class="content">
          <h3 class="title">{{ training.title }}</h3>
          <p class="description">{{ training.description }}</p>
          
          <div class="meta">
            <span class="category">{{ training.category }}</span>
            <span class="trainer">{{ training.trainer?.first_name }} {{ training.trainer?.last_name }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useLanguageStore } from '@/stores/language';
import ApiService from '@/services/api';

export default {
  name: 'TrainingsList',
  setup() {
    const languageStore = useLanguageStore();
    return { languageStore };
  },
  data() {
    return {
      trainings: [],
      loading: false
    };
  },
  async mounted() {
    await this.loadTrainings();
    
    // Dil dəyişdikdə yenidən yüklə
    this.$watch(
      () => this.languageStore.language,
      () => {
        this.loadTrainings();
      }
    );
  },
  methods: {
    async loadTrainings() {
      this.loading = true;
      try {
        // API service avtomatik olaraq current language-i əlavə edəcək
        const response = await ApiService.get('/trainings');
        this.trainings = response.data || response;
      } catch (error) {
        console.error('Error loading trainings:', error);
        this.$notify({
          type: 'error',
          message: 'Təlimlər yüklənə bilmədi'
        });
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style scoped>
.trainings-grid {
  @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6;
}

.training-card {
  @apply bg-white rounded-lg shadow-md overflow-hidden cursor-pointer hover:shadow-lg transition-shadow;
}

.training-card .banner {
  @apply w-full h-48 object-cover;
}

.training-card .content {
  @apply p-4;
}

.training-card .title {
  @apply text-xl font-bold mb-2;
}

.training-card .description {
  @apply text-gray-600 mb-4 line-clamp-3;
}

.training-card .meta {
  @apply flex justify-between text-sm text-gray-500;
}
</style>
```

### Training Detail Komponenti

```vue
<template>
  <div v-if="training" class="training-detail">
    <div class="banner-section">
      <img 
        v-if="training.banner_url" 
        :src="training.banner_url" 
        :alt="training.title"
        class="banner-image"
      />
    </div>

    <div class="content-section">
      <h1 class="title">{{ training.title }}</h1>
      
      <div class="meta-info">
        <span class="category">{{ training.category }}</span>
        <span class="trainer">
          Təlimçi: {{ training.trainer?.first_name }} {{ training.trainer?.last_name }}
        </span>
        <span class="date">
          {{ formatDate(training.start_date) }} - {{ formatDate(training.end_date) }}
        </span>
      </div>

      <div class="description">
        <h2>Təsvir</h2>
        <div v-html="training.description"></div>
      </div>

      <!-- Modules and Lessons -->
      <div v-if="training.modules" class="modules">
        <h2>Modullar</h2>
        <div
          v-for="module in training.modules"
          :key="module.id"
          class="module"
        >
          <h3>{{ module.title }}</h3>
          <div class="lessons">
            <div
              v-for="lesson in module.lessons"
              :key="lesson.id"
              class="lesson"
            >
              <h4>{{ lesson.title }}</h4>
              <p>{{ lesson.description }}</p>
            </div>
          </div>
        </div>
      </div>

      <button 
        @click="registerTraining"
        class="register-button"
        :disabled="isRegistered"
      >
        {{ isRegistered ? 'Qeydiyyatdan keçmisiniz' : 'Qeydiyyatdan keç' }}
      </button>
    </div>
  </div>
  
  <div v-else class="loading">
    Yüklənir...
  </div>
</template>

<script>
import ApiService from '@/services/api';
import { useLanguageStore } from '@/stores/language';

export default {
  name: 'TrainingDetail',
  props: {
    trainingId: {
      type: [String, Number],
      required: true
    }
  },
  setup() {
    const languageStore = useLanguageStore();
    return { languageStore };
  },
  data() {
    return {
      training: null,
      loading: false,
      isRegistered: false
    };
  },
  async mounted() {
    await this.loadTraining();
    
    // Dil dəyişdikdə yenidən yüklə
    this.$watch(
      () => this.languageStore.language,
      () => {
        this.loadTraining();
      }
    );
  },
  methods: {
    async loadTraining() {
      this.loading = true;
      try {
        // API avtomatik olaraq current language-i əlavə edəcək
        this.training = await ApiService.get(`/trainings/${this.trainingId}`);
        
        // Check if user is registered
        await this.checkRegistration();
      } catch (error) {
        console.error('Error loading training:', error);
        this.$notify({
          type: 'error',
          message: 'Təlim məlumatları yüklənə bilmədi'
        });
      } finally {
        this.loading = false;
      }
    },
    async checkRegistration() {
      try {
        const registrations = await ApiService.get('/trainings/registrations');
        this.isRegistered = registrations.some(
          reg => reg.training_id === this.trainingId
        );
      } catch (error) {
        console.error('Error checking registration:', error);
      }
    },
    async registerTraining() {
      try {
        await ApiService.post(`/trainings/${this.trainingId}/register`);
        this.isRegistered = true;
        this.$notify({
          type: 'success',
          message: 'Uğurla qeydiyyatdan keçdiniz'
        });
      } catch (error) {
        console.error('Error registering:', error);
        this.$notify({
          type: 'error',
          message: error.response?.data?.message || 'Qeydiyyat zamanı xəta baş verdi'
        });
      }
    },
    formatDate(date) {
      if (!date) return '';
      return new Date(date).toLocaleDateString(this.languageStore.language);
    }
  }
};
</script>
```

---

## 🔄 4. Dil Dəyişikliyində Content Yeniləməsi

### Reactivity Pattern

```vue
<template>
  <div>
    <LanguageSelector @language-changed="handleLanguageChange" />
    
    <TrainingsList :key="languageKey" />
  </div>
</template>

<script>
export default {
  data() {
    return {
      languageKey: 0
    };
  },
  methods: {
    handleLanguageChange(lang) {
      // Force component re-render
      this.languageKey++;
      
      // Və ya store-dan watch et
      // Vuex/Pinia avtomatik olaraq reactivity təmin edəcək
    }
  }
};
</script>
```

### Composables Pattern (Vue 3)

```javascript
// composables/useLanguage.js
import { ref, watch } from 'vue';
import { useLanguageStore } from '@/stores/language';
import ApiService from '@/services/api';

export function useLanguage() {
  const languageStore = useLanguageStore();
  const data = ref(null);
  const loading = ref(false);
  const error = ref(null);

  const loadData = async (endpoint) => {
    loading.value = true;
    error.value = null;
    
    try {
      // API service avtomatik olaraq current language-i əlavə edəcək
      data.value = await ApiService.get(endpoint);
    } catch (err) {
      error.value = err;
      console.error('Error loading data:', err);
    } finally {
      loading.value = false;
    }
  };

  // Dil dəyişdikdə avtomatik yenilə
  watch(
    () => languageStore.language,
    () => {
      // Component-də loadData yenidən çağırılmalıdır
      // Və ya endpoint-i watch et
    }
  );

  return {
    data,
    loading,
    error,
    loadData,
    currentLanguage: () => languageStore.language
  };
}
```

---

## 📱 5. Mobile Responsive Design

### Mobile Language Selector

```vue
<template>
  <div class="mobile-language-selector">
    <button 
      @click="showMobileMenu = true"
      class="mobile-language-button"
    >
      <span class="flag">{{ getFlag(currentLanguage) }}</span>
    </button>

    <!-- Mobile Menu -->
    <div 
      v-if="showMobileMenu" 
      class="mobile-language-menu"
      @click.self="showMobileMenu = false"
    >
      <div class="menu-content">
        <button
          v-for="lang in languages"
          :key="lang.code"
          @click="selectLanguage(lang.code)"
          :class="['menu-item', { active: currentLanguage === lang.code }]"
        >
          <span class="flag">{{ lang.flag }}</span>
          <span>{{ lang.name }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      showMobileMenu: false,
      currentLanguage: localStorage.getItem('user_language') || 'az',
      languages: [
        { code: 'az', name: 'Azərbaycan', flag: '🇦🇿' },
        { code: 'en', name: 'English', flag: '🇬🇧' },
        { code: 'ru', name: 'Русский', flag: '🇷🇺' }
      ]
    };
  },
  methods: {
    selectLanguage(code) {
      this.currentLanguage = code;
      this.showMobileMenu = false;
      localStorage.setItem('user_language', code);
      this.$store?.commit('setLanguage', code);
      window.location.reload();
    },
    getFlag(code) {
      return this.languages.find(l => l.code === code)?.flag || '🌐';
    }
  }
};
</script>

<style scoped>
@media (max-width: 768px) {
  .mobile-language-button {
    @apply p-2 rounded-full bg-gray-100;
  }
  
  .mobile-language-menu {
    @apply fixed inset-0 bg-black bg-opacity-50 z-50 flex items-end;
  }
  
  .menu-content {
    @apply w-full bg-white rounded-t-lg p-4;
  }
  
  .menu-item {
    @apply w-full flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100;
  }
}
</style>
```

---

## 🎨 6. SEO və Meta Tags

### Dynamic Meta Tags (Dilə görə)

```vue
<template>
  <div>
    <!-- Content -->
  </div>
</template>

<script>
export default {
  name: 'TrainingDetailPage',
  props: {
    trainingId: [String, Number]
  },
  data() {
    return {
      training: null
    };
  },
  async mounted() {
    await this.loadTraining();
    this.updateMetaTags();
  },
  watch: {
    '$store.state.language'() {
      this.loadTraining().then(() => {
        this.updateMetaTags();
      });
    }
  },
  methods: {
    updateMetaTags() {
      if (!this.training) return;

      // Meta title
      document.title = this.training.title || 'Training';

      // Meta description
      const metaDescription = document.querySelector('meta[name="description"]');
      if (metaDescription) {
        metaDescription.setAttribute('content', this.training.description || '');
      } else {
        const meta = document.createElement('meta');
        meta.name = 'description';
        meta.content = this.training.description || '';
        document.head.appendChild(meta);
      }

      // Open Graph tags
      this.updateOGTag('og:title', this.training.title);
      this.updateOGTag('og:description', this.training.description);
      if (this.training.banner_url) {
        this.updateOGTag('og:image', this.training.banner_url);
      }

      // Language attribute
      document.documentElement.lang = this.$store.state.language;
    },
    updateOGTag(property, content) {
      let tag = document.querySelector(`meta[property="${property}"]`);
      if (tag) {
        tag.setAttribute('content', content);
      } else {
        tag = document.createElement('meta');
        tag.setAttribute('property', property);
        tag.setAttribute('content', content);
        document.head.appendChild(tag);
      }
    }
  }
};
</script>
```

---

## 🔍 7. Search Funksionallığı

### Multi-language Search

```vue
<template>
  <div class="search-wrapper">
    <input
      v-model="searchQuery"
      type="text"
      :placeholder="getPlaceholder()"
      @input="handleSearch"
      class="search-input"
    />
  </div>
</template>

<script>
import ApiService from '@/services/api';
import { useLanguageStore } from '@/stores/language';

export default {
  setup() {
    const languageStore = useLanguageStore();
    return { languageStore };
  },
  data() {
    return {
      searchQuery: '',
      searchResults: [],
      loading: false
    };
  },
  methods: {
    async handleSearch() {
      if (!this.searchQuery.trim()) {
        this.searchResults = [];
        return;
      }

      this.loading = true;
      try {
        // Backend avtomatik olaraq bütün dillərdə axtarır
        // Ancaq response current language-də olacaq
        const results = await ApiService.get('/trainings', {
          params: {
            search: this.searchQuery
            // lang parametri avtomatik əlavə olunur
          }
        });
        
        this.searchResults = results.data || results;
      } catch (error) {
        console.error('Search error:', error);
      } finally {
        this.loading = false;
      }
    },
    getPlaceholder() {
      const placeholders = {
        az: 'Axtar...',
        en: 'Search...',
        ru: 'Поиск...'
      };
      return placeholders[this.languageStore.language] || 'Search...';
    }
  }
};
</script>
```

---

## 📝 8. Form Input-lar (User Forms)

### Registration Form (Dil-agnostic)

```vue
<template>
  <form @submit.prevent="submitForm">
    <input
      v-model="formData.first_name"
      type="text"
      :placeholder="$t('forms.first_name')"
      required
    />
    
    <input
      v-model="formData.last_name"
      type="text"
      :placeholder="$t('forms.last_name')"
      required
    />
    
    <!-- Training selection -->
    <select v-model="formData.training_id" required>
      <option value="">{{ $t('forms.select_training') }}</option>
      <option
        v-for="training in trainings"
        :key="training.id"
        :value="training.id"
      >
        {{ training.title }}
      </option>
    </select>

    <button type="submit">{{ $t('forms.submit') }}</button>
  </form>
</template>

<script>
import ApiService from '@/services/api';

export default {
  data() {
    return {
      formData: {
        first_name: '',
        last_name: '',
        training_id: ''
      },
      trainings: []
    };
  },
  async mounted() {
    // Trainings avtomatik olaraq current language-də olacaq
    this.trainings = await ApiService.get('/trainings');
  },
  methods: {
    async submitForm() {
      // Form data-dan training title-i çıxarmaq lazım deyil
      // Backend training_id ilə işləyir
      await ApiService.post('/trainings/register', this.formData);
    }
  }
};
</script>
```

---

## 🎯 9. URL və Routing

### Language-aware Routes

```javascript
// router/index.js
import { createRouter, createWebHistory } from 'vue-router';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/:lang?/trainings',
      name: 'trainings',
      component: () => import('@/views/TrainingsList.vue'),
      beforeEnter: (to, from, next) => {
        // URL-dən lang parametrini oxu
        const lang = to.params.lang || localStorage.getItem('user_language') || 'az';
        
        // Validate language
        if (['az', 'en', 'ru'].includes(lang)) {
          // Store-da set et
          store.commit('setLanguage', lang);
          localStorage.setItem('user_language', lang);
        }
        
        next();
      }
    },
    {
      path: '/:lang?/trainings/:id',
      name: 'training-detail',
      component: () => import('@/views/TrainingDetail.vue')
    }
  ]
});

// Navigation guard - hər route change-də language-i yoxla
router.beforeEach((to, from, next) => {
  const lang = localStorage.getItem('user_language') || 'az';
  
  // Əgər URL-də lang yoxdursa, əlavə et
  if (!to.params.lang && !to.path.startsWith(`/${lang}`)) {
    // Redirect to language-prefixed URL
    next(`/${lang}${to.path}`);
  } else {
    next();
  }
});

export default router;
```

---

## 🌐 10. Browser Language Detection

### İlk Yükləmədə Dil Təyini

```javascript
// utils/language.js

export function detectBrowserLanguage() {
  // 1. Əvvəlcə saved language-i yoxla
  const saved = localStorage.getItem('user_language');
  if (saved && ['az', 'en', 'ru'].includes(saved)) {
    return saved;
  }

  // 2. Browser language-dən təyin et
  const browserLang = (
    navigator.language || 
    navigator.userLanguage || 
    navigator.languages?.[0] ||
    'az'
  ).substring(0, 2).toLowerCase();

  // 3. Dəstəklənən dillərdə olub-olmadığını yoxla
  if (['az', 'en', 'ru'].includes(browserLang)) {
    localStorage.setItem('user_language', browserLang);
    return browserLang;
  }

  // 4. Default: az
  localStorage.setItem('user_language', 'az');
  return 'az';
}

// main.js və ya App.vue-da istifadə
import { detectBrowserLanguage } from '@/utils/language';

const initialLanguage = detectBrowserLanguage();
store.commit('setLanguage', initialLanguage);
```

---

## 💡 11. Best Practices

### ✅ DO's (Edilməlidir)

1. **Həmişə API service-dən istifadə et** - Lang parametri avtomatik əlavə olunur
2. **localStorage-da dil saxla** - İstifadəçi seçimini saxla
3. **Browser language detect et** - İlk dəfə açılanda
4. **Loading state göstər** - Dil dəyişikliyində
5. **Error handling** - Network error-ları handle et
6. **Fallback content** - Əgər translation yoxdursa, default göstər

### ❌ DON'Ts (Edilməməlidir)

1. **Manual lang parametri əlavə etmə** - API service bunu edir
2. **Frontend-də translate etmə** - Backend bunu edir
3. **Hardcode language** - Həmişə dynamic istifadə et
4. **Dil dəyişikliyində page reload etmə** - Reactivity istifadə et

---

## 📊 12. Performance Optimizasiyası

### Caching Strategy

```javascript
// services/api.js (extended)

class ApiService {
  constructor() {
    // ... previous code
    this.cache = new Map();
    this.cacheTimeout = 5 * 60 * 1000; // 5 minutes
  }

  async get(endpoint, config = {}) {
    // Cache key: endpoint + language + params
    const lang = this.getCurrentLanguage();
    const cacheKey = `${endpoint}_${lang}_${JSON.stringify(config.params || {})}`;
    
    // Check cache
    const cached = this.cache.get(cacheKey);
    if (cached && Date.now() - cached.timestamp < this.cacheTimeout) {
      return cached.data;
    }

    // Fetch from API
    const response = await this.client.get(endpoint, config);
    
    // Cache response
    this.cache.set(cacheKey, {
      data: response.data,
      timestamp: Date.now()
    });

    return response.data;
  }

  clearCache() {
    this.cache.clear();
  }

  clearCacheForLanguage(lang) {
    // Clear all cache entries for specific language
    for (const [key, value] of this.cache.entries()) {
      if (key.includes(`_${lang}_`)) {
        this.cache.delete(key);
      }
    }
  }
}
```

---

## 🎬 13. Tam Nümunə: Training List Page

### Complete Implementation

```vue
<template>
  <div class="trainings-page">
    <!-- Header with Language Selector -->
    <header class="page-header">
      <h1>{{ pageTitle }}</h1>
      <LanguageSelector />
    </header>

    <!-- Search Bar -->
    <div class="search-section">
      <input
        v-model="searchQuery"
        type="text"
        :placeholder="searchPlaceholder"
        @input="handleSearch"
        class="search-input"
      />
    </div>

    <!-- Filters -->
    <div class="filters">
      <select v-model="selectedCategory" @change="applyFilters">
        <option value="">{{ allCategoriesText }}</option>
        <option
          v-for="category in categories"
          :key="category.id"
          :value="category.name"
        >
          {{ category.name }}
        </option>
      </select>
    </div>

    <!-- Trainings List -->
    <TrainingsList 
      :trainings="filteredTrainings"
      :loading="loading"
    />

    <!-- Pagination -->
    <Pagination
      v-if="pagination"
      :current-page="pagination.current_page"
      :last-page="pagination.last_page"
      @page-change="handlePageChange"
    />
  </div>
</template>

<script>
import { useLanguageStore } from '@/stores/language';
import ApiService from '@/services/api';
import LanguageSelector from '@/components/LanguageSelector.vue';
import TrainingsList from '@/components/TrainingsList.vue';
import Pagination from '@/components/Pagination.vue';

export default {
  name: 'TrainingsPage',
  components: {
    LanguageSelector,
    TrainingsList,
    Pagination
  },
  setup() {
    const languageStore = useLanguageStore();
    return { languageStore };
  },
  data() {
    return {
      trainings: [],
      categories: [],
      loading: false,
      searchQuery: '',
      selectedCategory: '',
      pagination: null,
      currentPage: 1
    };
  },
  computed: {
    pageTitle() {
      const titles = {
        az: 'Təlimlər',
        en: 'Trainings',
        ru: 'Обучения'
      };
      return titles[this.languageStore.language] || 'Trainings';
    },
    searchPlaceholder() {
      const placeholders = {
        az: 'Təlim axtar...',
        en: 'Search trainings...',
        ru: 'Поиск обучений...'
      };
      return placeholders[this.languageStore.language] || 'Search...';
    },
    allCategoriesText() {
      const texts = {
        az: 'Bütün kateqoriyalar',
        en: 'All categories',
        ru: 'Все категории'
      };
      return texts[this.languageStore.language] || 'All';
    },
    filteredTrainings() {
      return this.trainings;
    }
  },
  async mounted() {
    await Promise.all([
      this.loadTrainings(),
      this.loadCategories()
    ]);

    // Watch language changes
    this.$watch(
      () => this.languageStore.language,
      () => {
        this.loadTrainings();
        this.loadCategories();
      }
    );
  },
  methods: {
    async loadTrainings() {
      this.loading = true;
      try {
        const params = {
          page: this.currentPage
        };

        if (this.searchQuery) {
          params.search = this.searchQuery;
        }

        if (this.selectedCategory) {
          params.category = this.selectedCategory;
        }

        // API service avtomatik olaraq lang parametrini əlavə edəcək
        const response = await ApiService.get('/trainings', { params });
        
        this.trainings = response.data || response;
        this.pagination = response.meta || null;
      } catch (error) {
        console.error('Error loading trainings:', error);
        this.$notify({
          type: 'error',
          message: 'Təlimlər yüklənə bilmədi'
        });
      } finally {
        this.loading = false;
      }
    },
    async loadCategories() {
      try {
        // Categories də avtomatik olaraq current language-də olacaq
        this.categories = await ApiService.get('/categories');
      } catch (error) {
        console.error('Error loading categories:', error);
      }
    },
    handleSearch() {
      // Debounce istifadə et
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.currentPage = 1;
        this.loadTrainings();
      }, 500);
    },
    applyFilters() {
      this.currentPage = 1;
      this.loadTrainings();
    },
    handlePageChange(page) {
      this.currentPage = page;
      this.loadTrainings();
      // Scroll to top
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  }
};
</script>
```

---

## 🔄 14. Dil Dəyişikliyində State Management

### Vuex Action Pattern

```javascript
// store/modules/content.js
export default {
  namespaced: true,
  
  state: {
    trainings: [],
    categories: [],
    language: 'az'
  },
  
  mutations: {
    SET_LANGUAGE(state, lang) {
      state.language = lang;
    },
    SET_TRAININGS(state, trainings) {
      state.trainings = trainings;
    },
    SET_CATEGORIES(state, categories) {
      state.categories = categories;
    }
  },
  
  actions: {
    async changeLanguage({ commit, dispatch }, lang) {
      commit('SET_LANGUAGE', lang);
      localStorage.setItem('user_language', lang);
      
      // Reload all content with new language
      await Promise.all([
        dispatch('loadTrainings'),
        dispatch('loadCategories')
      ]);
    },
    
    async loadTrainings({ commit, state }) {
      const trainings = await ApiService.get('/trainings');
      commit('SET_TRAININGS', trainings);
    },
    
    async loadCategories({ commit, state }) {
      const categories = await ApiService.get('/categories');
      commit('SET_CATEGORIES', categories);
    }
  }
};
```

---

## 📱 15. PWA Support

### Service Worker Cache Strategy

```javascript
// sw.js (Service Worker)

const CACHE_NAME = 'agrar-portal-v1';
const API_CACHE_NAME = 'agrar-api-v1';

// Cache API responses with language
self.addEventListener('fetch', (event) => {
  if (event.request.url.includes('/api/v1/')) {
    event.respondWith(
      caches.open(API_CACHE_NAME).then((cache) => {
        return fetch(event.request).then((response) => {
          // Cache response with language-specific key
          cache.put(event.request, response.clone());
          return response;
        }).catch(() => {
          // Return cached version if offline
          return cache.match(event.request);
        });
      })
    );
  }
});
```

---

## 🎨 16. UI/UX Best Practices

### Loading States

```vue
<template>
  <div class="content-wrapper">
    <!-- Skeleton Loading -->
    <div v-if="loading" class="skeleton-loading">
      <div v-for="i in 3" :key="i" class="skeleton-item">
        <div class="skeleton-image"></div>
        <div class="skeleton-title"></div>
        <div class="skeleton-description"></div>
      </div>
    </div>

    <!-- Actual Content -->
    <div v-else>
      <!-- Content -->
    </div>
  </div>
</template>

<style scoped>
.skeleton-loading {
  @apply space-y-4;
}

.skeleton-item {
  @apply bg-gray-200 rounded-lg p-4 animate-pulse;
}

.skeleton-image {
  @apply w-full h-48 bg-gray-300 rounded mb-4;
}

.skeleton-title {
  @apply h-6 bg-gray-300 rounded mb-2;
}

.skeleton-description {
  @apply h-4 bg-gray-300 rounded;
}
</style>
```

### Empty States

```vue
<template>
  <div v-if="isEmpty" class="empty-state">
    <img src="/empty-state.svg" alt="Empty" />
    <h3>{{ emptyStateTitle }}</h3>
    <p>{{ emptyStateMessage }}</p>
  </div>
</template>

<script>
export default {
  computed: {
    emptyStateTitle() {
      const titles = {
        az: 'Məzmun tapılmadı',
        en: 'No content found',
        ru: 'Контент не найден'
      };
      return titles[this.$store.state.language] || 'No content';
    },
    emptyStateMessage() {
      const messages = {
        az: 'Axtardığınız məzmun hazırda mövcud deyil',
        en: 'The content you are looking for is not available',
        ru: 'Искомый контент недоступен'
      };
      return messages[this.$store.state.language] || 'Not available';
    }
  }
};
</script>
```

---

## 🧪 17. Testing

### Component Testing Example

```javascript
// tests/components/LanguageSelector.spec.js
import { mount } from '@vue/test-utils';
import LanguageSelector from '@/components/LanguageSelector.vue';

describe('LanguageSelector', () => {
  it('should save language to localStorage', async () => {
    const wrapper = mount(LanguageSelector);
    
    await wrapper.find('select').setValue('en');
    
    expect(localStorage.getItem('user_language')).toBe('en');
  });

  it('should emit language-changed event', async () => {
    const wrapper = mount(LanguageSelector);
    
    await wrapper.find('select').setValue('ru');
    
    expect(wrapper.emitted('language-changed')).toBeTruthy();
    expect(wrapper.emitted('language-changed')[0]).toEqual(['ru']);
  });
});
```

---

## 📚 18. İstifadə Nümunələri

### Ssenari 1: İstifadəçi Saytı Açır

1. Browser language detect olunur (məsələn: `en`)
2. Əgər saved language yoxdursa, browser language istifadə olunur
3. Əgər browser language dəstəklənmirsə, default `az` istifadə olunur
4. Bütün API request-lər `?lang=en` ilə göndərilir
5. Content-lər İngilis dilində göstərilir

### Ssenari 2: İstifadəçi Dili Dəyişdirir

1. İstifadəçi dil seçir (məsələn: `ru`)
2. Language localStorage-da saxlanılır
3. Store-da language update olunur
4. Bütün content komponentləri yenilənir
5. API request-lər yenidən göndərilir `?lang=ru` ilə
6. Content-lər Rus dilində göstərilir

### Ssenari 3: Training Detail Səhifəsi

1. İstifadəçi training-ə klik edir
2. URL: `/trainings/123` (current language ilə API call olunur)
3. Backend `?lang=xx` parametrinə görə düzgün versiyanı qaytarır
4. Training title, description və modullar seçilmiş dildə göstərilir
5. Əgər hər hansı bir modul üçün translation yoxdursa, default (az) göstərilir

---

## 🆘 Problem Həlləri

### Problem 1: Dil dəyişikliyində content yenilənmir

**Həll:**
```javascript
// Component-də watch istifadə et
watch: {
  '$store.state.language'() {
    this.loadData();
  }
}
```

### Problem 2: API request-lər language parametrisiz göndərilir

**Həll:**
```javascript
// API service interceptor-dan istifadə et
// Hər request-ə avtomatik lang parametri əlavə olunur
```

### Problem 3: Browser language detect olunmur

**Həll:**
```javascript
// utils/language.js-də detectBrowserLanguage funksiyasından istifadə et
const lang = detectBrowserLanguage();
```

---

## 📖 Əsas Xülasə

### User Frontend üçün 3 əsas prinsip:

1. **Dil Seçimi** → localStorage-da saxla
2. **API Request** → API service avtomatik lang parametrini əlavə edir
3. **Content Display** → Backend-dən gələn response artıq translate olunub

### Frontend-də etməməli olduğunuz şeylər:

- ❌ Manual translation etmə
- ❌ Lang parametrini manual əlavə etmə
- ❌ Content-i frontend-də translate etmə

### Frontend-də etməli olduğunuz şeylər:

- ✅ Dil seçimi komponenti
- ✅ localStorage-da dil saxla
- ✅ API service-dən istifadə et
- ✅ Store-da language state saxla
- ✅ Dil dəyişikliyində content-i yenilə

---

**Son Yeniləmə:** 2025-11-01  
**Versiya:** 1.0.0

**Əlavə Məlumat:**
- Admin Panel üçün: `ADMIN_PANEL_MULTILANG_GUIDE.md`
- Ümumi Guide: `FRONTEND_DEVELOPER_MULTILANG_GUIDE.md`

