# 🌍 Çoxdilli Sistem - Frontend Developer Guide

## 📋 Ümumi Məlumat

Sistemə **3 dilli dəstək** (Azərbaycan, İngilis, Rus) əlavə edildi. Bütün text sahələri artıq JSON formatında translation obyektləri kimi saxlanılır.

### Dəstəklənən Dillər:
- `az` - Azərbaycan (default)
- `en` - İngilis
- `ru` - Rus

---

## 🚀 Əsas Yeniliklər

### 1. **API Request Format Dəyişikliyi**

Artıq text sahələri **object** formatında göndərilməlidir:

**❌ Köhnə Format (artıq işləmir):**
```json
{
  "title": "Test Training",
  "description": "This is a test"
}
```

**✅ Yeni Format:**
```json
{
  "title": {
    "az": "Test Təlim",
    "en": "Test Training",
    "ru": "Тестовое обучение"
  },
  "description": {
    "az": "Bu bir testdir",
    "en": "This is a test",
    "ru": "Это тест"
  }
}
```

### 2. **API Response Format**

API response-ları **həmişə request-dəki dil parametrinə görə** qaytarılır:

**Request:**
```
GET /api/v1/trainings?lang=en
```

**Response:**
```json
{
  "id": 1,
  "title": "Test Training",  // İngilis versiyası
  "description": "This is a test"
}
```

### 3. **Dil Parametri**

Bütün API endpoint-lərində `lang` query parametri istifadə oluna bilər:

```
?lang=az  → Azərbaycan
?lang=en  → İngilis
?lang=ru  → Rus
```

**Default:** Əgər `lang` parametri verilməsə, avtomatik olaraq `az` (Azərbaycan) dilində qaytarılır.

---

## 📡 API İstifadəsi

### GET Request-ləri

Dil parametri **query string**-də göndərilir:

```javascript
// Azərbaycan (default)
GET /api/v1/trainings

// İngilis
GET /api/v1/trainings?lang=en

// Rus
GET /api/v1/trainings?lang=ru

// Paged request
GET /api/v1/trainings?lang=en&page=1&per_page=10
```

### POST/PUT Request-ləri

**Yeni məzmun yaradarkən:**

```javascript
const response = await fetch('/api/v1/trainings', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({
    title: {
      az: "Yeni Təlim",
      en: "New Training",
      ru: "Новое обучение"
    },
    description: {
      az: "Bu yeni bir təlimdir",
      en: "This is a new training",
      ru: "Это новое обучение"
    },
    trainer_id: 1,
    // ... digər sahələr
  })
});
```

**Məzmunu yeniləyərkən (PUT/PATCH):**

```javascript
const response = await fetch('/api/v1/trainings/1', {
  method: 'PUT',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({
    title: {
      az: "Yenilənmiş Başlıq",
      en: "Updated Title",
      ru: "Обновленный заголовок"
    },
    // ... digər sahələr
  })
});
```

**Qismən yeniləmə (yalnız bir dili dəyişdirmək):**

Eğer yalnız bir dilin tərcüməsini yeniləmək istəyirsinizsə, **bütün translation obyektini** göndərməlisiniz (backend mövcud dilləri saxlayacaq):

```javascript
// ❌ YANLIŞ - yalnız bir dil göndərmək
{
  "title": {
    "en": "Only English"
  }
}

// ✅ DOĞRU - bütün dilləri göndərmək
// Backend mövcud dilləri saxlayacaq, yalnız göndərilən dilləri yeniləyəcək
{
  "title": {
    "az": "Mövcud Azərbaycan versiyası", // Backend-dən gələn
    "en": "Updated English version",      // Yenilənir
    "ru": "Mövcud Rus versiyası"          // Backend-dən gələn
  }
}
```

---

## 🎨 Frontend İmplementasiyası

### 1. **Dil Seçimi Komponenti**

```vue
<template>
  <select v-model="selectedLang" @change="onLanguageChange">
    <option value="az">Azərbaycan</option>
    <option value="en">English</option>
    <option value="ru">Русский</option>
  </select>
</template>

<script>
export default {
  data() {
    return {
      selectedLang: localStorage.getItem('preferred_lang') || 'az'
    }
  },
  methods: {
    onLanguageChange() {
      localStorage.setItem('preferred_lang', this.selectedLang);
      // API request-lərini yenidən yüklə
      this.loadData();
    },
    async loadData() {
      const response = await fetch(`/api/v1/trainings?lang=${this.selectedLang}`);
      // ...
    }
  }
}
</script>
```

### 2. **Translation Input Komponenti**

Form sahələri üçün multi-language input komponenti:

```vue
<template>
  <div class="translation-input">
    <div class="tabs">
      <button 
        v-for="lang in languages" 
        :key="lang.code"
        @click="activeLang = lang.code"
        :class="{ active: activeLang === lang.code }"
      >
        {{ lang.label }}
      </button>
    </div>
    
    <textarea
      v-for="lang in languages"
      :key="lang.code"
      v-if="activeLang === lang.code"
      v-model="translations[lang.code]"
      :placeholder="`Enter ${lang.label} text...`"
    />
  </div>
</template>

<script>
export default {
  props: {
    value: {
      type: Object,
      default: () => ({ az: '', en: '', ru: '' })
    }
  },
  data() {
    return {
      activeLang: 'az',
      languages: [
        { code: 'az', label: 'Azərbaycan' },
        { code: 'en', label: 'English' },
        { code: 'ru', label: 'Русский' }
      ],
      translations: { ...this.value }
    }
  },
  watch: {
    translations: {
      deep: true,
      handler(newVal) {
        this.$emit('input', newVal);
      }
    },
    value: {
      deep: true,
      handler(newVal) {
        this.translations = { ...newVal };
      }
    }
  }
}
</script>
```

### 3. **API Service Wrapper**

Təkrar istifadə üçün API service:

```javascript
// services/api.js
class ApiService {
  constructor() {
    this.baseURL = '/api/v1';
    this.defaultLang = localStorage.getItem('preferred_lang') || 'az';
  }

  getLanguage() {
    return localStorage.getItem('preferred_lang') || 'az';
  }

  setLanguage(lang) {
    localStorage.setItem('preferred_lang', lang);
    this.defaultLang = lang;
  }

  async request(endpoint, options = {}) {
    const lang = this.getLanguage();
    const url = `${this.baseURL}${endpoint}${endpoint.includes('?') ? '&' : '?'}lang=${lang}`;
    
    const defaultOptions = {
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${this.getToken()}`
      }
    };

    const response = await fetch(url, {
      ...defaultOptions,
      ...options,
      headers: {
        ...defaultOptions.headers,
        ...options.headers
      }
    });

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Request failed');
    }

    return response.json();
  }

  getToken() {
    return localStorage.getItem('auth_token');
  }

  // GET request
  async get(endpoint) {
    return this.request(endpoint, { method: 'GET' });
  }

  // POST request
  async post(endpoint, data) {
    return this.request(endpoint, {
      method: 'POST',
      body: JSON.stringify(data)
    });
  }

  // PUT request
  async put(endpoint, data) {
    return this.request(endpoint, {
      method: 'PUT',
      body: JSON.stringify(data)
    });
  }

  // DELETE request
  async delete(endpoint) {
    return this.request(endpoint, { method: 'DELETE' });
  }
}

export default new ApiService();
```

### 4. **Form Submit Nümunəsi**

```vue
<template>
  <form @submit.prevent="submitForm">
    <TranslationInput 
      v-model="formData.title"
      label="Title"
      required
    />
    
    <TranslationInput 
      v-model="formData.description"
      label="Description"
    />
    
    <button type="submit">Save</button>
  </form>
</template>

<script>
import ApiService from '@/services/api';
import TranslationInput from '@/components/TranslationInput.vue';

export default {
  components: {
    TranslationInput
  },
  data() {
    return {
      formData: {
        title: {
          az: '',
          en: '',
          ru: ''
        },
        description: {
          az: '',
          en: '',
          ru: ''
        }
      }
    }
  },
  methods: {
    async submitForm() {
      try {
        // Validation
        if (!this.formData.title.az) {
          alert('Azərbaycan dilində başlıq mütləqdir!');
          return;
        }

        const response = await ApiService.post('/trainings', this.formData);
        console.log('Success:', response);
        // Redirect or show success message
      } catch (error) {
        console.error('Error:', error);
        // Show error message
      }
    }
  }
}
</script>
```

---

## ⚠️ Validation Qaydaları

### Backend Validation

1. **Mütləq sahələr** (məsələn `title`):
   - Ən azı `az` (Azərbaycan) versiyası olmalıdır
   - Digər dillər optional-dır

2. **Optional sahələr** (məsələn `description`):
   - Heç bir dil mütləq deyil
   - Ancaq ən azı bir dil versiyası olmalıdır

3. **Format Validation:**
   - Translation obyekti `object` olmalıdır
   - Dəstəklənən dillər: `az`, `en`, `ru`
   - Hər dil versiyası `string` olmalıdır

### Frontend Validation Nümunəsi

```javascript
function validateTranslations(field, required = false) {
  const errors = [];

  if (required && !field.az) {
    errors.push('Azərbaycan dilində versiya mütləqdir');
  }

  // Check if at least one language is provided
  const hasAnyTranslation = Object.values(field).some(val => val && val.trim());
  if (!hasAnyTranslation && required) {
    errors.push('Ən azı bir dil versiyası daxil edilməlidir');
  }

  // Check for unsupported languages
  const supportedLangs = ['az', 'en', 'ru'];
  Object.keys(field).forEach(lang => {
    if (!supportedLangs.includes(lang)) {
      errors.push(`${lang} dili dəstəklənmir`);
    }
  });

  return errors;
}

// Usage
const titleErrors = validateTranslations(formData.title, true);
if (titleErrors.length > 0) {
  console.error('Title errors:', titleErrors);
}
```

---

## 📝 Tərcümə olunan Sahələr

Aşağıdakı sahələr artıq translation formatında olmalıdır:

### Trainings
- `title` ✅ (mütləq)
- `description` ⚠️ (optional)

### Exams
- `title` ✅
- `description` ⚠️
- `sertifikat_description` ⚠️
- `rules` ⚠️
- `instructions` ⚠️

### Forum Questions
- `title` ✅
- `summary` ⚠️
- `body` ✅

### Forum Answers
- `body` ✅

### Educational Content
- `title` ✅
- `short_description` ⚠️
- `body_html` ⚠️
- `description` ⚠️
- `announcement_title` ⚠️
- `announcement_body` ⚠️

### Internship Programs
- `title` ✅
- `description` ⚠️
- `location` ⚠️
- `instructor_description` ⚠️
- `cv_requirements` ⚠️

### Categories
- `name` ✅
- `description` ⚠️

### FAQs
- `question` ✅
- `answer` ✅

### Service Packages
- `name` ✅
- `description` ⚠️

### Notifications
- `title` ✅
- `message` ✅

### Meetings
- `title` ✅
- `description` ⚠️

**Legend:**
- ✅ = Mütləq sahə (ən azı `az` versiyası tələb olunur)
- ⚠️ = Optional sahə

---

## 🔄 Migration Məlumatları

**Qeyd:** Bu məlumat yalnız məlumat üçündür. Migration-lar artıq tamamlanıb.

### Köhnə Data Formatı

Mövcud data-lar avtomatik olaraq yeni format-a köçürülüb:

**Köhnə:**
```json
{
  "title": "Test Training"
}
```

**Yeni:**
```json
{
  "title": {
    "az": "Test Training"
  }
}
```

Mövcud data-lar avtomatik olaraq `az` dilində saxlanılıb. Digər dilləri əlavə etmək üçün admin panelindən edit etmək lazımdır.

---

## 🐛 Error Handling

### Validation Error Response

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "title": [
      "The title field is required.",
      "The title must have at least the default language (az) translation."
    ],
    "title.en": [
      "The title.en must be a string."
    ]
  }
}
```

### Frontend Error Handling

```javascript
try {
  const response = await ApiService.post('/trainings', formData);
} catch (error) {
  if (error.response?.status === 422) {
    // Validation errors
    const errors = error.response.data.errors;
    console.error('Validation errors:', errors);
    
    // Show errors to user
    Object.keys(errors).forEach(field => {
      console.error(`${field}: ${errors[field].join(', ')}`);
    });
  } else {
    // Other errors
    console.error('Error:', error.message);
  }
}
```

---

## 💡 Best Practices

### 1. **Dil Seçimini Saxlamaq**

İstifadəçinin dil seçimini `localStorage`-da saxlayın:

```javascript
// Save
localStorage.setItem('preferred_lang', 'en');

// Load
const lang = localStorage.getItem('preferred_lang') || 'az';
```

### 2. **Default Dil Fallback**

Əgər seçilən dil üçün translation yoxdursa, avtomatik olaraq `az` versiyası göstəriləcək. Ancaq frontend-də də fallback təmin edin:

```javascript
function getTranslation(field, lang = 'az') {
  return field[lang] || field.az || field.en || field.ru || '';
}
```

### 3. **Loading States**

Translation input komponentində loading state göstərin:

```vue
<template>
  <div v-if="loading" class="loading">
    Loading translations...
  </div>
  <TranslationInput v-else v-model="data.title" />
</template>
```

### 4. **Empty State Handling**

Əgər translation boşdursa, default mətn göstərin:

```vue
<template>
  <div>
    <h2>{{ getTitle(training.title) || 'No title available' }}</h2>
  </div>
</template>

<script>
methods: {
  getTitle(translations) {
    const lang = this.$store.state.language || 'az';
    return translations?.[lang] || translations?.az || '';
  }
}
</script>
```

---

## 🧪 Test Nümunələri

### 1. Yeni Training Yaradılması

```javascript
const newTraining = {
  title: {
    az: "Yeni Təlim",
    en: "New Training",
    ru: "Новое обучение"
  },
  description: {
    az: "Bu yeni bir təlimdir",
    en: "This is a new training"
  },
  trainer_id: 1,
  category: "Technology"
};

const response = await ApiService.post('/trainings', newTraining);
```

### 2. Training-lərin Siyahısı

```javascript
// İngilis dilində
const trainings = await ApiService.get('/trainings?lang=en');

// Response:
// [
//   {
//     id: 1,
//     title: "New Training",  // İngilis versiyası
//     description: "This is a new training"
//   }
// ]
```

### 3. Training Yenilənməsi

```javascript
const updatedTraining = {
  title: {
    az: "Yenilənmiş Təlim",
    en: "Updated Training",
    ru: "Обновленное обучение"
  }
};

const response = await ApiService.put('/trainings/1', updatedTraining);
```

---

## 📞 Dəstək

Əgər sualınız varsa və ya problem yaşayırsınızsa:

1. Bu dokumentasiyaya yenidən baxın
2. API response-larını yoxlayın (Network tab)
3. Browser console-da error-ları yoxlayın
4. Backend developer ilə əlaqə saxlayın

---

## 📚 Əlavə Qeydlər

1. **Backward Compatibility:** Köhnə format (string) hələ də qəbul oluna bilər, ancaq **məsləhət görülmür**. Mütləq yeni format istifadə edin.

2. **Performance:** Translation obyektləri JSON formatında saxlanılır, buna görə də performance impact minimaldır.

3. **Search:** Search funksionallığı bütün dillərdə işləyir. Backend avtomatik olaraq bütün translation versiyalarında axtarır.

4. **Admin Panel:** Admin panel-də bütün dillərin versiyalarını edit etmək mümkündür.

---

**Son Yeniləmə:** 2025-11-01
**Versiya:** 1.0.0

