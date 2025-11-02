# 🎛️ Admin Panel - Çoxdilli Sistem Tam Bələdçi

## 📋 Giriş

Bu bələdçi admin paneli üçün çoxdilli sistemin tam implementasiyasını əhatə edir. Admin panelində bütün məzmunu (trainings, exams, articles, forums və s.) yaratmaq, redaktə etmək və idarə etmək üçün çoxdilli dəstəyin tam funksionallığını öyrənəcəksiniz.

---

## 🎯 Admin Panel Üçün Əsas Prinsiplər

### 1. **Translation Input Komponenti**

Admin panelində **hər translatable sahə üçün** xüsusi translation input komponenti istifadə edilməlidir. Bu komponent:
- 3 dil üçün tab/accordion interfeysi göstərir
- Hər dil üçün ayrı input field
- Real-time validation
- Required field indicator (az dili üçün)

### 2. **Dil İdarəetməsi**

Admin panelində:
- **Default görünən dil:** Azərbaycan (az)
- **Mütləq sahələr:** Ən azı az dili mütləqdir
- **Optional sahələr:** İstənilən dil versiyası boş ola bilər

### 3. **Form Submission**

Form submit zamanı:
- Bütün dillərin versiyalarını object formatında göndərmək
- Backend avtomatik olaraq validation edir
- Error-lar specific dil üçün göstərilir

---

## 🧩 1. Translation Input Komponenti

### Vue.js Komponent Nümunəsi (Tam Versiya)

```vue
<template>
  <div class="translation-input-wrapper">
    <!-- Label -->
    <label class="block text-sm font-medium text-gray-700 mb-2">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>

    <!-- Tab Navigation -->
    <div class="border-b border-gray-200 mb-4">
      <nav class="-mb-px flex space-x-8">
        <button
          v-for="lang in languages"
          :key="lang.code"
          @click="activeLang = lang.code"
          :class="[
            'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors',
            activeLang === lang.code
              ? 'border-blue-500 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            hasError(lang.code) && 'border-red-500 text-red-600'
          ]"
        >
          {{ lang.label }}
          <span v-if="lang.code === 'az' && required" class="text-red-500 ml-1">*</span>
          <span
            v-if="translations[lang.code] && translations[lang.code].trim()"
            class="ml-1 text-green-500"
          >
            ✓
          </span>
        </button>
      </nav>
    </div>

    <!-- Input Fields -->
    <div class="relative">
      <textarea
        v-for="lang in languages"
        :key="lang.code"
        v-if="activeLang === lang.code"
        v-model="translations[lang.code]"
        :placeholder="`Enter ${lang.label} ${label.toLowerCase()}...`"
        :rows="rows"
        :class="[
          'w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
          hasError(lang.code) && 'border-red-500 focus:ring-red-500 focus:border-red-500'
        ]"
        @blur="validate"
        @input="handleInput"
      />

      <!-- Error Messages -->
      <div v-if="errors[activeLang]" class="mt-1 text-sm text-red-600">
        {{ errors[activeLang] }}
      </div>

      <!-- Helper Text -->
      <p v-if="helperText" class="mt-1 text-sm text-gray-500">
        {{ helperText }}
      </p>
    </div>

    <!-- Translation Status Summary -->
    <div class="mt-2 flex items-center space-x-4 text-xs text-gray-500">
      <span
        v-for="lang in languages"
        :key="lang.code"
        :class="[
          'flex items-center',
          translations[lang.code]?.trim() ? 'text-green-600' : 'text-gray-400'
        ]"
      >
        <span class="w-2 h-2 rounded-full mr-1" :class="
          translations[lang.code]?.trim() ? 'bg-green-500' : 'bg-gray-300'
        "></span>
        {{ lang.code.toUpperCase() }}
      </span>
    </div>
  </div>
</template>

<script>
export default {
  name: 'TranslationInput',
  props: {
    value: {
      type: Object,
      default: () => ({ az: '', en: '', ru: '' })
    },
    label: {
      type: String,
      required: true
    },
    required: {
      type: Boolean,
      default: false
    },
    rows: {
      type: Number,
      default: 3
    },
    helperText: {
      type: String,
      default: ''
    },
    errors: {
      type: Object,
      default: () => ({})
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
      translations: {
        az: this.value?.az || '',
        en: this.value?.en || '',
        ru: this.value?.ru || ''
      }
    };
  },
  watch: {
    value: {
      deep: true,
      handler(newVal) {
        if (newVal) {
          this.translations = {
            az: newVal.az || '',
            en: newVal.en || '',
            ru: newVal.ru || ''
          };
        }
      },
      immediate: true
    },
    translations: {
      deep: true,
      handler() {
        this.$emit('input', { ...this.translations });
      }
    }
  },
  methods: {
    handleInput() {
      this.$emit('input', { ...this.translations });
      this.validate();
    },
    validate() {
      const validationErrors = {};
      
      if (this.required && !this.translations.az?.trim()) {
        validationErrors.az = 'Azərbaycan dilində versiya mütləqdir';
      }
      
      this.$emit('validate', validationErrors);
      return Object.keys(validationErrors).length === 0;
    },
    hasError(lang) {
      return !!this.errors[lang] || (lang === 'az' && this.required && !this.translations.az?.trim());
    }
  }
};
</script>

<style scoped>
.translation-input-wrapper {
  @apply w-full;
}
</style>
```

---

## 📝 2. Form Komponentləri Nümunələri

### Training Yaratma Formu

```vue
<template>
  <form @submit.prevent="handleSubmit" class="space-y-6">
    <!-- Title (Required) -->
    <TranslationInput
      v-model="formData.title"
      label="Başlıq"
      :required="true"
      :errors="errors.title || {}"
      helper-text="Təlim üçün başlıq daxil edin (ən azı Azərbaycan dilində)"
      :rows="1"
    />

    <!-- Description (Optional) -->
    <TranslationInput
      v-model="formData.description"
      label="Təsvir"
      :required="false"
      :errors="errors.description || {}"
      :rows="5"
    />

    <!-- Category -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Kateqoriya
      </label>
      <select
        v-model="formData.category"
        class="w-full px-3 py-2 border rounded-md"
      >
        <option value="">Kateqoriya seçin</option>
        <option v-for="cat in categories" :key="cat.id" :value="cat.name">
          {{ cat.name }}
        </option>
      </select>
    </div>

    <!-- Trainer Selection -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Təlimçi *
      </label>
      <select
        v-model="formData.trainer_id"
        required
        class="w-full px-3 py-2 border rounded-md"
      >
        <option value="">Təlimçi seçin</option>
        <option v-for="trainer in trainers" :key="trainer.id" :value="trainer.id">
          {{ trainer.first_name }} {{ trainer.last_name }}
        </option>
      </select>
    </div>

    <!-- Dates -->
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Başlanğıc Tarixi
        </label>
        <input
          v-model="formData.start_date"
          type="date"
          class="w-full px-3 py-2 border rounded-md"
        />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Bitmə Tarixi
        </label>
        <input
          v-model="formData.end_date"
          type="date"
          class="w-full px-3 py-2 border rounded-md"
        />
      </div>
    </div>

    <!-- Submit Button -->
    <div class="flex justify-end space-x-4">
      <button
        type="button"
        @click="$router.go(-1)"
        class="px-4 py-2 border rounded-md hover:bg-gray-50"
      >
        Ləğv et
      </button>
      <button
        type="submit"
        :disabled="loading || !isFormValid"
        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <span v-if="loading">Yüklənir...</span>
        <span v-else>Yadda saxla</span>
      </button>
    </div>
  </form>
</template>

<script>
import TranslationInput from '@/components/admin/TranslationInput.vue';
import ApiService from '@/services/api';

export default {
  components: {
    TranslationInput
  },
  data() {
    return {
      loading: false,
      formData: {
        title: { az: '', en: '', ru: '' },
        description: { az: '', en: '', ru: '' },
        category: '',
        trainer_id: '',
        start_date: '',
        end_date: ''
      },
      errors: {},
      categories: [],
      trainers: []
    };
  },
  computed: {
    isFormValid() {
      // Az dili üçün title mütləqdir
      return !!(
        this.formData.title.az?.trim() &&
        this.formData.trainer_id
      );
    }
  },
  async mounted() {
    await this.loadFormData();
  },
  methods: {
    async loadFormData() {
      // Load categories and trainers
      try {
        const [categoriesRes, trainersRes] = await Promise.all([
          ApiService.get('/categories'),
          ApiService.get('/users?role=trainer')
        ]);
        this.categories = categoriesRes;
        this.trainers = trainersRes;
      } catch (error) {
        console.error('Error loading form data:', error);
      }
    },
    async handleSubmit() {
      // Validate before submit
      if (!this.validateForm()) {
        return;
      }

      this.loading = true;
      this.errors = {};

      try {
        const response = await ApiService.post('/trainings', this.formData);
        
        // Success
        this.$notify({
          type: 'success',
          title: 'Uğurlu',
          message: 'Təlim uğurla yaradıldı'
        });

        // Redirect to training list or detail
        this.$router.push(`/admin/trainings/${response.training.id}`);
      } catch (error) {
        if (error.response?.status === 422) {
          // Validation errors
          this.errors = error.response.data.errors || {};
          
          this.$notify({
            type: 'error',
            title: 'Xəta',
            message: 'Form validasiyası xətası. Zəhmət olmasa yoxlayın.'
          });
        } else {
          this.$notify({
            type: 'error',
            title: 'Xəta',
            message: error.message || 'Gözlənilməz xəta baş verdi'
          });
        }
      } finally {
        this.loading = false;
      }
    },
    validateForm() {
      const errors = {};

      // Title validation
      if (!this.formData.title.az?.trim()) {
        errors.title = { az: 'Azərbaycan dilində başlıq mütləqdir' };
      }

      // Trainer validation
      if (!this.formData.trainer_id) {
        errors.trainer_id = 'Təlimçi seçilməlidir';
      }

      if (Object.keys(errors).length > 0) {
        this.errors = errors;
        return false;
      }

      return true;
    }
  }
};
</script>
```

---

## ✏️ 3. Edit Form Nümunəsi

### Training Redaktə Formu

```vue
<template>
  <form v-if="!loading" @submit.prevent="handleSubmit" class="space-y-6">
    <!-- Load existing translations -->
    <TranslationInput
      v-model="formData.title"
      label="Başlıq"
      :required="true"
      :errors="errors.title || {}"
    />

    <TranslationInput
      v-model="formData.description"
      label="Təsvir"
      :required="false"
      :errors="errors.description || {}"
      :rows="5"
    />

    <!-- ... digər sahələr ... -->

    <div class="flex justify-end space-x-4">
      <button
        type="button"
        @click="$router.go(-1)"
        class="px-4 py-2 border rounded-md"
      >
        Ləğv et
      </button>
      <button
        type="submit"
        :disabled="saving"
        class="px-4 py-2 bg-blue-600 text-white rounded-md"
      >
        {{ saving ? 'Yadda saxlanılır...' : 'Yadda saxla' }}
      </button>
    </div>
  </form>
  <div v-else class="text-center py-12">
    Yüklənir...
  </div>
</template>

<script>
import TranslationInput from '@/components/admin/TranslationInput.vue';
import ApiService from '@/services/api';

export default {
  components: {
    TranslationInput
  },
  props: {
    trainingId: {
      type: [String, Number],
      required: true
    }
  },
  data() {
    return {
      loading: true,
      saving: false,
      formData: {
        title: { az: '', en: '', ru: '' },
        description: { az: '', en: '', ru: '' }
      },
      errors: {},
      originalData: null
    };
  },
  async mounted() {
    await this.loadTraining();
  },
  methods: {
    async loadTraining() {
      try {
        // Backend-dən tam translation obyektlərini alırıq
        // ?include_translations=true parametri ilə (əgər backend dəstəkləyirsə)
        const response = await ApiService.get(`/trainings/${this.trainingId}?include_translations=true`);
        
        // Əgər backend yalnız default dili qaytarırsa, bütün dilləri al
        if (!response.title || typeof response.title === 'string') {
          // Full translations üçün ayrı request
          // Və ya backend-də endpoint əlavə et: /trainings/{id}/translations
          const translations = await ApiService.get(`/trainings/${this.trainingId}/translations`);
          this.formData = {
            title: translations.title || { az: response.title || '', en: '', ru: '' },
            description: translations.description || { az: response.description || '', en: '', ru: '' }
          };
        } else {
          // Backend artıq full translation obyektini qaytarır
          this.formData = {
            title: response.title || { az: '', en: '', ru: '' },
            description: response.description || { az: '', en: '', ru: '' }
          };
        }

        this.originalData = { ...this.formData };
        this.loading = false;
      } catch (error) {
        console.error('Error loading training:', error);
        this.$notify({
          type: 'error',
          title: 'Xəta',
          message: 'Təlim məlumatları yüklənə bilmədi'
        });
      }
    },
    async handleSubmit() {
      this.saving = true;
      this.errors = {};

      try {
        // Yalnız dəyişdirilmiş sahələri göndər
        const updateData = {};
        
        if (JSON.stringify(this.formData.title) !== JSON.stringify(this.originalData.title)) {
          updateData.title = this.formData.title;
        }
        
        if (JSON.stringify(this.formData.description) !== JSON.stringify(this.originalData.description)) {
          updateData.description = this.formData.description;
        }

        await ApiService.put(`/trainings/${this.trainingId}`, updateData);

        this.$notify({
          type: 'success',
          title: 'Uğurlu',
          message: 'Dəyişikliklər yadda saxlanıldı'
        });

        // Reload data
        await this.loadTraining();
      } catch (error) {
        if (error.response?.status === 422) {
          this.errors = error.response.data.errors || {};
        }
      } finally {
        this.saving = false;
      }
    }
  }
};
</script>
```

---

## 📊 4. Table/List View-lərdə Translation Göstərmə

### Training List Table

```vue
<template>
  <table class="min-w-full divide-y divide-gray-200">
    <thead>
      <tr>
        <th>ID</th>
        <th>Başlıq</th>
        <th>Təsvir</th>
        <th>Kateqoriya</th>
        <th>Əməliyyatlar</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="training in trainings" :key="training.id">
        <td>{{ training.id }}</td>
        <!-- Current language-ə görə göstər -->
        <td>
          {{ getTranslation(training.title) || '-' }}
        </td>
        <td>
          <span class="truncate max-w-xs block">
            {{ getTranslation(training.description) || '-' }}
          </span>
        </td>
        <td>{{ training.category }}</td>
        <td>
          <button @click="editTraining(training.id)">Redaktə</button>
        </td>
      </tr>
    </tbody>
  </table>
</template>

<script>
export default {
  data() {
    return {
      currentLang: localStorage.getItem('admin_view_lang') || 'az',
      trainings: []
    };
  },
  methods: {
    getTranslation(field) {
      // Əgər field string-dirsə (köhnə format), direkt qaytar
      if (typeof field === 'string') {
        return field;
      }
      
      // Əgər object-dirsə (yeni format), current lang-ə görə qaytar
      if (typeof field === 'object' && field !== null) {
        return field[this.currentLang] || field.az || field.en || field.ru || '';
      }
      
      return '';
    }
  }
};
</script>
```

---

## 🔍 5. Search və Filter Funksionallığı

### Multi-language Search

```vue
<template>
  <div class="search-wrapper">
    <input
      v-model="searchQuery"
      type="text"
      placeholder="Axtar..."
      @input="handleSearch"
      class="w-full px-4 py-2 border rounded-md"
    />
    
    <!-- Search Filters -->
    <div class="mt-2 flex space-x-2">
      <label class="flex items-center">
        <input
          type="checkbox"
          v-model="searchInAllLanguages"
          class="mr-2"
        />
        Bütün dillərdə axtar
      </label>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      searchQuery: '',
      searchInAllLanguages: true
    };
  },
  methods: {
    async handleSearch() {
      // Backend avtomatik olaraq bütün translation versiyalarında axtarır
      // Ancaq frontend-də də filter edə bilərik
      const params = {
        search: this.searchQuery,
        lang: this.searchInAllLanguages ? 'all' : this.currentLang
      };

      const results = await ApiService.get('/trainings', { params });
      this.trainings = results.data;
    }
  }
};
</script>
```

---

## 📋 6. Bulk Operations (Kütləvi Əməliyyatlar)

### Çoxlu Training-ləri Redaktə Etmək

```vue
<template>
  <div>
    <!-- Checkbox Selection -->
    <div class="mb-4">
      <button @click="selectAll">Hamısını seç</button>
      <button @click="deselectAll">Seçimi ləğv et</button>
    </div>

    <!-- Bulk Edit Modal -->
    <div v-if="selectedItems.length > 0" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
      <div class="bg-white p-6 rounded-lg max-w-2xl w-full">
        <h3 class="text-lg font-bold mb-4">
          {{ selectedItems.length }} training redaktə et
        </h3>

        <TranslationInput
          v-model="bulkEditData.title"
          label="Yeni Başlıq (Boş buraxsanız, dəyişilməyəcək)"
          :required="false"
        />

        <div class="mt-4 flex justify-end space-x-2">
          <button @click="closeBulkEdit" class="px-4 py-2 border rounded">
            Ləğv et
          </button>
          <button @click="applyBulkEdit" class="px-4 py-2 bg-blue-600 text-white rounded">
            Tətbiq et
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      selectedItems: [],
      bulkEditData: {
        title: { az: '', en: '', ru: '' }
      }
    };
  },
  methods: {
    async applyBulkEdit() {
      const updates = [];
      
      for (const item of this.selectedItems) {
        const update = { id: item.id };
        
        // Yalnız doldurulmuş sahələri əlavə et
        if (this.bulkEditData.title.az || this.bulkEditData.title.en || this.bulkEditData.title.ru) {
          // Mövcud title ilə merge et
          update.title = {
            ...item.title,
            ...Object.fromEntries(
              Object.entries(this.bulkEditData.title).filter(([_, v]) => v.trim())
            )
          };
        }
        
        updates.push(update);
      }

      // Batch update
      await ApiService.post('/trainings/bulk-update', { updates });
      
      this.$notify({
        type: 'success',
        message: `${updates.length} training yeniləndi`
      });

      this.closeBulkEdit();
      await this.loadTrainings();
    }
  }
};
</script>
```

---

## 🎨 7. Rich Text Editor İnteqrasiyası

### WYSIWYG Editor ilə Translation

```vue
<template>
  <div class="rich-text-translation">
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

    <!-- Editor for each language -->
    <div v-for="lang in languages" :key="lang.code" v-show="activeLang === lang.code">
      <ckeditor
        v-model="translations[lang.code]"
        :editor="editor"
        :config="editorConfig"
        @input="handleInput"
      />
    </div>
  </div>
</template>

<script>
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';

export default {
  props: {
    value: {
      type: Object,
      default: () => ({ az: '', en: '', ru: '' })
    }
  },
  data() {
    return {
      editor: ClassicEditor,
      activeLang: 'az',
      languages: [
        { code: 'az', label: 'Azərbaycan' },
        { code: 'en', label: 'English' },
        { code: 'ru', label: 'Русский' }
      ],
      translations: {
        az: this.value?.az || '',
        en: this.value?.en || '',
        ru: this.value?.ru || ''
      },
      editorConfig: {
        toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList'],
        language: 'az'
      }
    };
  },
  methods: {
    handleInput() {
      this.$emit('input', { ...this.translations });
    }
  }
};
</script>
```

---

## 🔄 8. Translation Copy/Paste Funksionallığı

### Bir dildən digərinə kopyalamaq

```vue
<template>
  <TranslationInput
    v-model="formData.title"
    label="Başlıq"
    @copy-translation="handleCopyTranslation"
  />
</template>

<script>
export default {
  methods: {
    handleCopyTranslation({ fromLang, toLang, field }) {
      // Google Translate API istifadə edərək avtomatik translate
      // Və ya manual copy
      if (this.formData[field][fromLang]) {
        // Manual copy (admin özü tərcümə edəcək)
        this.formData[field][toLang] = this.formData[field][fromLang];
        
        this.$notify({
          type: 'info',
          message: `${fromLang.toUpperCase()} versiyası ${toLang.toUpperCase()}-ə kopyalandı. Zəhmət olmasa tərcümə edin.`
        });
      }
    }
  }
};
</script>
```

---

## 📱 9. Mobile Responsive Design

### Translation Input Mobile View

```vue
<style scoped>
/* Desktop: Tabs */
@media (min-width: 768px) {
  .translation-tabs {
    @apply flex border-b;
  }
}

/* Mobile: Accordion */
@media (max-width: 767px) {
  .translation-tabs {
    @apply space-y-2;
  }
  
  .translation-tab {
    @apply border rounded-lg p-3;
  }
  
  .translation-content {
    @apply mt-2;
  }
}
</style>
```

---

## ✅ 10. Validation və Error Handling

### Form Validation Nümunəsi

```javascript
// utils/validation.js

export const validateTranslation = (field, fieldName, required = false) => {
  const errors = {};

  // Required field validation
  if (required && !field.az?.trim()) {
    errors.az = `${fieldName} Azərbaycan dilində mütləqdir`;
  }

  // Check if at least one language is provided (for optional fields)
  if (!required) {
    const hasAnyTranslation = Object.values(field).some(v => v && v.trim());
    if (!hasAnyTranslation) {
      errors._general = `Ən azı bir dil üçün ${fieldName} daxil edilməlidir`;
    }
  }

  // Character limit check
  Object.keys(field).forEach(lang => {
    if (field[lang] && field[lang].length > 5000) {
      errors[lang] = `${fieldName} 5000 simvoldan çox ola bilməz`;
    }
  });

  return errors;
};

// Usage in component
import { validateTranslation } from '@/utils/validation';

methods: {
  validateForm() {
    const errors = {};
    
    errors.title = validateTranslation(this.formData.title, 'Başlıq', true);
    errors.description = validateTranslation(this.formData.description, 'Təsvir', false);
    
    // Remove empty error objects
    Object.keys(errors).forEach(key => {
      if (Object.keys(errors[key]).length === 0) {
        delete errors[key];
      }
    });
    
    this.errors = errors;
    return Object.keys(errors).length === 0;
  }
}
```

---

## 🎯 11. Best Practices

### ✅ DO's (Edilməlidir)

1. **Həmişə Translation Input komponenti istifadə et**
2. **Az dili üçün mütləq validation**
3. **Real-time validation göstər**
4. **Translation status indicator göstər** (hansı dillər doldurulub)
5. **Error-ları specific dil üçün göstər**
6. **Loading state göstər**
7. **Save progress** (draft kimi)

### ❌ DON'Ts (Edilməməlidir)

1. **String format göndərməyin**
2. **Validation-u skip etməyin**
3. **Error handling-i unutmayın**
4. **Mobile view-u nəzərə almayın**
5. **Bütün dilləri eyni zamanda tələb etməyin** (yalnız az mütləqdir)

---

## 🔗 12. API Endpoint Reference

### Translation-specific Endpoints

```
# Full translations al
GET /api/v1/trainings/{id}/translations
Response: {
  title: { az: "...", en: "...", ru: "..." },
  description: { az: "...", en: "...", ru: "..." }
}

# Yalnız bir dil üçün update
PATCH /api/v1/trainings/{id}/translations/title
Body: { lang: "en", value: "English Title" }

# Bulk translation update
POST /api/v1/trainings/bulk-translations
Body: {
  ids: [1, 2, 3],
  field: "title",
  translations: { en: "New English Title" }
}
```

---

## 📚 Nümunə Ssenarilər

### Ssenari 1: Yeni Training Yaratmaq

1. Form açılır
2. Azərbaycan dilində başlıq daxil edilir (mütləq)
3. İngilis və Rus versiyaları boş qala bilər
4. Form submit olunur
5. Backend validation edir
6. Uğurlu yaradılırsa, success mesajı göstərilir

### Ssenari 2: Training Redaktə Etmək

1. Edit form açılır
2. Mövcud translation-lar yüklənir
3. Admin istədiyi dilləri dəyişdirir
4. Save edilir
5. Yalnız dəyişdirilmiş sahələr backend-ə göndərilir

### Ssenari 3: Bulk Edit

1. Çoxlu training seçilir
2. Bulk edit modal açılır
3. Yeni title daxil edilir (hansı dillərdə istəsə)
4. Apply edilir
5. Bütün seçilmiş training-lər yenilənir

---

## 🆘 Problem Həlləri

### Problem: Backend translation obyekti qaytarmır

**Həll:** 
```javascript
// Full translations üçün ayrı endpoint istifadə et
const translations = await ApiService.get(`/trainings/${id}/translations`);
```

### Problem: Validation error-ları düzgün göstərilmir

**Həll:**
```javascript
// Backend error format:
{
  "errors": {
    "title.az": ["Az dili mütləqdir"],
    "title.en": ["Invalid format"]
  }
}

// Frontend-də parse et:
Object.keys(errors).forEach(key => {
  const [field, lang] = key.split('.');
  if (!this.errors[field]) this.errors[field] = {};
  this.errors[field][lang] = errors[key][0];
});
```

---

**Son Yeniləmə:** 2025-11-01  
**Versiya:** 1.0.0

**Əlavə Məlumat:** `FRONTEND_DEVELOPER_MULTILANG_GUIDE.md` faylına baxın

