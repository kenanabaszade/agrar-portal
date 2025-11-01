# Frontend пропуск - Educational Content Create

## Laravel Backend-ə POST Request Göndərmək

### 1. FormData İstifadə Etmək (Vacib!)

Laravel backend `multipart/form-data` formatını gözləyir, çünki file upload var. Ona görə **FormData** istifadə etmək lazımdır.

### 2. Nümunə Kod

```javascript
async function createEducationalContent(data) {
  const formData = new FormData();

  // Basic fields
  formData.append('type', 'telimat'); // və ya 'meqale', 'elan'
  formData.append('title', data.title || '');
  formData.append('short_description', data.short_description || '');
  formData.append('description', data.description || '');
  formData.append('sequence', data.sequence || 1);
  formData.append('hashtags', data.hashtags || '');
  formData.append('category', data.category || '');
  formData.append('send_to_our_user', data.send_to_our_user || false);

  // IMAGE - ƏSAS MƏSƏLƏ BURADADIR!
  // Əgər image faylı varsa:
  if (data.image && data.image instanceof File) {
    formData.append('image', data.image); // Field adı dəqiq 'image' olmalıdır!
  }

  // SEO fields (nested array)
  if (data.seo) {
    formData.append('seo[meta_title]', data.seo.meta_title || '');
    formData.append('seo[meta_desc]', data.seo.meta_desc || '');
    formData.append('seo[canonical_url]', data.seo.canonical_url || '');
    formData.append('seo[og_title]', data.seo.og_title || '');
    formData.append('seo[og_description]', data.seo.og_description || '');
    formData.append('seo[og_image]', data.seo.og_image || '');

    // SEO meta_tags array
    if (data.seo.meta_tags && Array.isArray(data.seo.meta_tags)) {
      data.seo.meta_tags.forEach((tag, index) => {
        formData.append(`seo[meta_tags][]`, tag);
      });
    }

    // SEO key_words array
    if (data.seo.key_words && Array.isArray(data.seo.key_words)) {
      data.seo.key_words.forEach((keyword, index) => {
        formData.append(`seo[key_words][]`, keyword);
      });
    }
  }

  // Media files array
  if (data.media_files && Array.isArray(data.media_files)) {
    data.media_files.forEach((mediaFile, index) => {
      // Əgər fayl upload edilirsə
      if (mediaFile.file && mediaFile.file instanceof File) {
        formData.append(`media_files[${index}][file]`, mediaFile.file); // File əvvəl!
        formData.append(`media_files[${index}][name]`, mediaFile.name || '');
        formData.append(`media_files[${index}][type]`, mediaFile.type || '');
      } else if (mediaFile.path) {
        // Əgər path varsa (mövcud fayl)
        formData.append(`media_files[${index}][path]`, mediaFile.path);
        formData.append(`media_files[${index}][name]`, mediaFile.name || '');
        formData.append(`media_files[${index}][type]`, mediaFile.type || '');
      }
    });
  }

  // API Request
  try {
    const response = await fetch('http://localhost:8000/api/v1/education', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${your_token_here}`, // Token əlavə et!
        // ⚠️ MÜHÜM: Content-Type header əlavə ETMƏYİN!
        // Browser avtomatik olaraq 'multipart/form-data' + boundary qoyacaq
      },
      body: formData, // FormData object-i birbaşa göndəririk
    });

    const result = await response.json();
    console.log('Response:', result);
    return result;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}

// İstifadə nümunəsi:
const data = {
  type: 'telimat',
  title: 'test',
  short_description: 'test',
  description: 'test',
  sequence: 1,
  category: 'kənd təsərrüfatı',
  send_to_our_user: false,
  
  // IMAGE - File object olmalıdır!
  image: document.querySelector('#imageInput').files[0], // File input-dan
  // və ya
  // image: new File([blob], slideshow.jpg', { type: 'image/jpeg' }),
  
  seo: {
    meta_title: 'Torpaq Becərilməsi...',
    meta_desc: 'Azərbaycanda...',
    meta_tags: ['torpaq', 'kənd təsərrüfatı', 'becərmə'],
    key_words: ['torpaq becərilməsi', 'kənd təsərrüfatı'],
    canonical_url: 'https://aqrar.az/...',
    og_title: 'Torpaq Becərilməsi...',
    og_description: 'Azərbaycanda...',
    og_image: 'https://aqrar.az/storage/...',
  },
  
  media_files: [
    {
      file: document.querySelector('#pdfInput').files[0], // File object
      name: 'pdf-sened',
      type: 'pdf',
    },
  ],
};

createEducationalContent(data);
```

### 3. React/Next.js Nümunəsi

```jsx
import React, { useState } from 'react';

function CreateEducationalContent() {
  const [formData, setFormData] = useState({
    type: 'telimat',
    title: '',
    image: null, // File object
    media_files: [],
  });

  const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      setFormData({ ...formData, image: file });
    }
  };

  const handleMediaFileChange = (e, index) => {
    const file = e.target.files[0];
    if (file) {
      const newMediaFiles = [...formData.media_files];
      newMediaFiles[index] = {
        file: file,
        name: file.name,
        type: 'pdf',
      };
      setFormData({ ...formData, media_files: newMediaFiles });
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    const formDataToSend = new FormData();
    
    // Basic fields
    formDataToSend.append('type', formData.type);
    formDataToSend.append('title', formData.title);
    
    // IMAGE - Vacib!
    if (formData.image) {
      formDataToSend.append('image', formData.image); // Field adı 'image' olmalıdır!
    }
    
    // Media files
    formData.media_files.forEach((mediaFile, index) => {
      if (mediaFile.file) {
        formDataToSend.append(`media_files[${index}][file]`, mediaFile.file);
        formDataToSend.append(`media_files[${index}][name]`, mediaFile.name);
        formDataToSend.append(`media_files[${index}][type]`, mediaFile.type);
      }
    });
    
    // SEO
    if (formData.seo) {
      Object.keys(formData.seo).forEach(key => {
        if (Array.isArray(formData.seo[key])) {
          formData.seo[key].forEach(item => {
            formDataToSend.append(`seo[${key}][]`, item);
          });
        } else {
          formDataToSend.append(`seo[${key}]`, formData.seo[key]);
        }
      });
    }
    
    try {
      const response = await fetch('http://localhost:8000/api/v1/education', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          // Content-Type header ETMƏ!
        },
        body: formDataToSend,
      });
      
      const result = await response.json();
      console.log('Success:', result);
    } catch (error) {
      console.error('Error:', error);
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      <input
        type="file"
        accept="image/*"
        onChange={handleImageChange}
      />
      {/* Digər field-lər */}
      <button type="submit">Create</button>
    </form>
  );
}
```

### 4. ƏSAS MƏSƏLƏLƏR (Vacib!)

#### ✅ DOĞRU:

```javascript
// 1. FormData istifadə et
const formData = new FormData();

// 2. Image field adı dəqiq 'image' olmalıdır
formData.append('image', imageFile);

// 3. File object göndər
formData.append('image', file); // ✅ DOĞRU

// 4. Authorization header əlavə et, amma Content-Type ETMƏ!
headers: {
  'Authorization': `Bearer ${token}`,
  // Content-Type yoxdur - Browser avtomatik qoyacaq!
}
```

#### ❌ YANLIŞ:

```javascript
// 1. JSON göndərmə (file upload ilə işləməz)
body: JSON.stringify({ image: ... }) // ❌ YANLIŞ

// 2. Field adı səhv
formData.append('image_file', file); // ❌ YANLIŞ - 'image' olmalıdır!
formData.append('image_path', file); // ❌ YANLIŞ

// 3. Base64 və ya string göndərmə
formData.append('image', base64String); // ❌ YANLIŞ - File object olmalıdır!

// 4. Content-Type header əlavə etmə
headers: {
  'Content-Type': 'multipart/form-data', // ❌ YANLIŞ - Browser avtomatik qoyacaq!
}
```

### 5. Debug üçün Console-da Yoxlamaq

```javascript
// FormData-nın içində nə var yoxlamaq:
for (let [key, value] of formData.entries()) {
  console.log(key, value);
}

// Bu göstərəcək:
// type telimat
// title test
// image File { name: "image.jpg", size: 12345, ... }
// media_files[0][file] File { ... }
```

### 6. Axios ilə (Əgər Axios istifadə edirsən)

```javascript
import axios from 'axios';

const formData = new FormData();
formData.append('type', 'telimat');
formData.append('image', imageFile); // File object

await axios.post('http://localhost:8000/api/v1/education', formData, {
  headers: {
    'Authorization': `Bearer ${token}`,
    // Content-Type ETMƏ - Axios avtomatik qoyacaq!
  },
});
```

### 7. ƏSAS YOXLAMA SİYAHISI

- [ ] FormData istifadə edirəm
- [ ] Image field adı dəqiq `'image'`-dir (səhv deyil: `'image_file'`, `'image_path'`)
- [ ] Image File object-dir (string/base64 deyil)
- [ ] Content-Type header əlavə ETMİRƏM (Browser avtomatik qoyacaq)
- [ ] Authorization header əlavə edirəm
- [ ] Array field-lər düzgün format olunub (`seo[meta_tags][]`, `media_files[0][file]`)

