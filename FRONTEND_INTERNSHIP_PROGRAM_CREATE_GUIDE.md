# Frontend: Yeni Staj Proqramı Yaratmaq Üçün Materiallar

## 📋 Endpoint
```
POST /api/v1/internship-programs
```

## 🔐 Headers
```
Authorization: Bearer {admin_token}
Content-Type: multipart/form-data
```

## 📦 Göndərilməli Materiallar

### ⚠️ MƏCBURİ SAHƏLƏR (Required)

| Sahə | Tip | Təsvir | Nümunə |
|------|-----|--------|--------|
| `title` | string (max 255) | Proqramın adı | "Web Development Stajı" |
| `description` | string | Proqramın təsviri | "Müasir web texnologiyaları öyrənmək..." |
| `category` | string (max 100) | Kateqoriya | "IT", "Marketing", "Finance" |
| `duration_weeks` | integer (min 1) | Müddət (həftə) | 8, 10, 12 |
| `start_date` | date | Başlama tarixi (bu gün və ya gələcək) | "2024-03-01" |
| `end_date` | date | Bitmə tarixi (start_date-dən sonra, opsional) | "2024-05-01" |
| `location` | string (max 255) | Məkan | "Bakı, Nəsimi rayonu" |
| `max_capacity` | integer (min 1) | Maksimum iştirakçı sayı | 20, 25, 30 |
| `instructor_title` | string (max 255) | Məktəbçinin vəzifəsi | "Senior Developer" |
| `registration_status` | enum | Qeydiyyat statusu | "open", "closed", "full" |

**Qeyd:** `instructor_name` **YALNIZ** `trainer_id` göndərilməyərsə məcburidir.

### ✅ OPSIONAL SAHƏLƏR (Optional)

| Sahə | Tip | Təsvir | Nümunə |
|------|-----|--------|--------|
| `trainer_id` | integer | Sistemdəki məktəbçinin ID-si | 5, 12 |
| `instructor_name` | string (max 255) | Məktəbçinin adı (trainer_id yoxdursa məcburi) | "Əli Məmmədov" |
| `instructor_initials` | string (max 10) | Məktəbçinin baş hərfləri | "ƏM" |
| `instructor_photo_url` | string (max 500) | Məktəbçinin şəkli URL | "https://example.com/photo.jpg" |
| `instructor_description` | string | Məktəbçi haqqında | "5 ildir developer kimi çalışır..." |
| `instructor_rating` | numeric (0-5) | Məktəbçinin reytinqi | 4.5, 4.9 |
| `end_date` | date | Bitmə tarixi (start_date-dən sonra) | "2024-05-01" |
| `last_register_date` | date | Son qeydiyyat tarixi | "2024-02-25" |
| `image` | file | Proqram şəkli (JPEG, PNG, JPG, GIF, max 2MB) | File object |
| `image_url` | string (max 500) | Proqram şəkli URL (image yoxdursa) | "https://example.com/image.jpg" |
| `is_featured` | boolean | Təqdim edilən proqram | true, false |
| `cv_requirements` | string | CV tələbləri | "HTML, CSS əsasları" |
| `details_link` | string (max 500) | Əlavə link | "https://example.com/details" |
| `current_enrollment` | integer (min 0) | Cari iştirakçı sayı (default: 0) | 0 |

### 📚 ARRAY SAHƏLƏR (Array Fields)

#### 1. Modules (Modullar)
```javascript
modules: [
  {
    title: "HTML/CSS əsasları",      // required, max 255
    description: "Web dizaynın əsasları", // optional
    order: 1                          // optional, integer
  },
  {
    title: "JavaScript Fundamentals",
    description: "JavaScript proqramlaşdırma",
    order: 2
  }
]
```

#### 2. Requirements (Tələblər)
```javascript
requirements: [
  {
    requirement: "HTML və CSS əsasları", // required, max 500
    order: 1                               // optional, integer
  },
  {
    requirement: "JavaScript bilikləri",
    order: 2
  }
]
```

#### 3. Goals (Məqsədlər)
```javascript
goals: [
  {
    goal: "Responsive web dizaynı yarada bilmək", // required, max 500
    order: 1                                       // optional, integer
  },
  {
    goal: "JavaScript ilə interaktiv səhifələr yaratmaq",
    order: 2
  }
]
```

## 🎯 Frontend Nümunəsi (JavaScript/Vue.js)

### FormData ilə Göndərmə
```javascript
// FormData yaradırıq
const formData = new FormData();

// Məcburi sahələr
formData.append('title', 'Web Development Stajı');
formData.append('description', 'Müasir web texnologiyaları öyrənmək üçün praktiki staj proqramı');
formData.append('category', 'IT');
formData.append('duration_weeks', '8');
formData.append('start_date', '2024-03-01');
formData.append('end_date', '2024-05-01'); // Opsional
formData.append('location', 'Bakı, Nəsimi rayonu');
formData.append('max_capacity', '20');
formData.append('instructor_title', 'Senior Full-Stack Developer');
formData.append('registration_status', 'open');

// Opsional sahələr
formData.append('trainer_id', '5'); // Əgər sistemdəki məktəbçini seçibsə
// VƏ ya
formData.append('instructor_name', 'Əli Məmmədov'); // Əgər trainer_id yoxdursa

formData.append('last_register_date', '2024-02-25');
formData.append('is_featured', 'true');
formData.append('instructor_description', '5 ildir web development sahəsində çalışır');
formData.append('instructor_rating', '4.8');
formData.append('cv_requirements', 'HTML, CSS əsasları, JavaScript bilikləri');

// Şəkil yükləmə (opsional)
const imageFile = document.querySelector('input[type="file"]').files[0];
if (imageFile) {
  formData.append('image', imageFile);
}

// Array sahələr (JSON string kimi)
formData.append('modules', JSON.stringify([
  {
    title: 'HTML/CSS əsasları',
    description: 'Web səhifələrin strukturunu yaratmaq',
    order: 1
  },
  {
    title: 'JavaScript Fundamentals',
    description: 'JavaScript proqramlaşdırma dilinin əsasları',
    order: 2
  }
]));

formData.append('requirements', JSON.stringify([
  {
    requirement: 'HTML və CSS əsasları',
    order: 1
  },
  {
    requirement: 'JavaScript əsasları',
    order: 2
  }
]));

formData.append('goals', JSON.stringify([
  {
    goal: 'Responsive web dizaynı yarada bilmək',
    order: 1
  },
  {
    goal: 'JavaScript ilə interaktiv səhifələr yarada bilmək',
    order: 2
  }
]));

// API çağırışı
fetch('/api/v1/internship-programs', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + adminToken
    // Content-Type qoyma - FormData avtomatik qoyur
  },
  body: formData
})
.then(response => response.json())
.then(data => {
  console.log('Success:', data);
})
.catch(error => {
  console.error('Error:', error);
});
```

### Axios ilə Göndərmə
```javascript
import axios from 'axios';

const formData = new FormData();

// Məcburi sahələr
formData.append('title', 'Web Development Stajı');
formData.append('description', 'Müasir web texnologiyaları...');
formData.append('category', 'IT');
formData.append('duration_weeks', '8');
formData.append('start_date', '2024-03-01');
formData.append('end_date', '2024-05-01'); // Opsional
formData.append('location', 'Bakı, Nəsimi rayonu');
formData.append('max_capacity', '20');
formData.append('instructor_title', 'Senior Full-Stack Developer');
formData.append('registration_status', 'open');

// Opsional sahələr
formData.append('trainer_id', '5');
formData.append('last_register_date', '2024-02-25');
formData.append('is_featured', 'true');

// Şəkil
if (imageFile) {
  formData.append('image', imageFile);
}

// Array sahələr
formData.append('modules', JSON.stringify([...]));
formData.append('requirements', JSON.stringify([...]));
formData.append('goals', JSON.stringify([...]));

axios.post('/api/v1/internship-programs', formData, {
  headers: {
    'Authorization': 'Bearer ' + adminToken,
    'Content-Type': 'multipart/form-data'
  }
})
.then(response => {
  console.log('Success:', response.data);
})
.catch(error => {
  console.error('Error:', error.response.data);
});
```

### Vue.js Composition API Nümunəsi
```vue
<template>
  <form @submit.prevent="createProgram">
    <input v-model="form.title" placeholder="Proqram Adı" required />
    <textarea v-model="form.description" placeholder="Təsvir" required></textarea>
    <input v-model="form.category" placeholder="Kateqoriya" required />
    <input v-model="form.duration_weeks" type="number" placeholder="Müddət (həftə)" required />
    <input v-model="form.start_date" type="date" required />
    <input v-model="form.location" placeholder="Məkan" required />
    <input v-model="form.max_capacity" type="number" placeholder="Maksimum iştirakçı" required />
    <input v-model="form.instructor_title" placeholder="Məktəbçi vəzifəsi" required />
    <select v-model="form.registration_status" required>
      <option value="open">Açıq</option>
      <option value="closed">Bağlı</option>
      <option value="full">Dolub</option>
    </select>
    <input type="file" @change="handleImageUpload" accept="image/*" />
    <button type="submit">Yarat</button>
  </form>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';

const form = ref({
  title: '',
  description: '',
  category: '',
  duration_weeks: '',
  start_date: '',
  end_date: '',
  location: '',
  max_capacity: '',
  instructor_title: '',
  registration_status: 'open',
  trainer_id: null,
  last_register_date: '',
  is_featured: false,
  modules: [],
  requirements: [],
  goals: []
});

const imageFile = ref(null);

const handleImageUpload = (event) => {
  imageFile.value = event.target.files[0];
};

const createProgram = async () => {
  const formData = new FormData();
  
  // Məcburi sahələr
  formData.append('title', form.value.title);
  formData.append('description', form.value.description);
  formData.append('category', form.value.category);
  formData.append('duration_weeks', form.value.duration_weeks);
  formData.append('start_date', form.value.start_date);
  formData.append('end_date', form.value.end_date); // Opsional
  formData.append('location', form.value.location);
  formData.append('max_capacity', form.value.max_capacity);
  formData.append('instructor_title', form.value.instructor_title);
  formData.append('registration_status', form.value.registration_status);
  
  // Opsional sahələr
  if (form.value.trainer_id) {
    formData.append('trainer_id', form.value.trainer_id);
  }
  if (form.value.last_register_date) {
    formData.append('last_register_date', form.value.last_register_date);
  }
  formData.append('is_featured', form.value.is_featured);
  
  // Şəkil
  if (imageFile.value) {
    formData.append('image', imageFile.value);
  }
  
  // Array sahələr
  if (form.value.modules.length > 0) {
    formData.append('modules', JSON.stringify(form.value.modules));
  }
  if (form.value.requirements.length > 0) {
    formData.append('requirements', JSON.stringify(form.value.requirements));
  }
  if (form.value.goals.length > 0) {
    formData.append('goals', JSON.stringify(form.value.goals));
  }
  
  try {
    const response = await axios.post('/api/v1/internship-programs', formData, {
      headers: {
        'Authorization': 'Bearer ' + localStorage.getItem('admin_token'),
        'Content-Type': 'multipart/form-data'
      }
    });
    console.log('Success:', response.data);
    alert('Proqram uğurla yaradıldı!');
  } catch (error) {
    console.error('Error:', error.response.data);
    alert('Xəta: ' + JSON.stringify(error.response.data.errors));
  }
};
</script>
```

## ⚠️ XƏTA MESAJLARI

### Validasiya Xətaları
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "title": ["The title field is required."],
    "start_date": ["The start date field must be a date after or equal to today."],
    "max_capacity": ["The max capacity must be at least 1."],
    "image": ["The image must be an image.", "The image may not be greater than 2048 kilobytes."]
  }
}
```

### Autentifikasiya Xətaları
```json
{
  "message": "Unauthorized",
  "status": 401
}
```

### İcazə Xətaları
```json
{
  "message": "This action is unauthorized.",
  "status": 403
}
```

## ✅ UĞURLU CAVAB
```json
{
  "message": "Internship program created successfully",
  "program": {
    "id": 3,
    "title": "Web Development Stajı",
    "description": "...",
    "category": "IT",
    "duration_weeks": 8,
    "start_date": "2024-03-01",
    "location": "Bakı, Nəsimi rayonu",
    "max_capacity": 20,
    "registration_status": "open",
    "modules": [...],
    "requirements": [...],
    "goals": [...]
  }
}
```

## 📝 QEYDLƏR

1. **Content-Type**: `multipart/form-data` istifadə edilməlidir (şəkil yükləmə üçün)
2. **Şəkil**: Maksimum 2MB, formatlar: JPEG, PNG, JPG, GIF
3. **Array sahələr**: JSON string kimi göndərilir
4. **trainer_id vs instructor_name**: 
   - Əgər `trainer_id` göndərilirsə, `instructor_name` tələb olunmur
   - Əgər `trainer_id` göndərilmirsə, `instructor_name` məcburidir
5. **Tarixlər**: ISO formatında (YYYY-MM-DD)
6. **Boolean**: `true`/`false` string kimi göndərilir
7. **Integer**: String kimi göndərilə bilər (avtomatik çevrilir)
