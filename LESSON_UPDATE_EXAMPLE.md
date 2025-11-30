# Lesson Update - Praktik Nümunə

## Senaryo: Lesson-un content, title və media-sını eyni zamanda update etmək

### Frontend-də proses:

```javascript
async function updateLessonWithMedia(moduleId, lessonId, changes) {
  const token = localStorage.getItem('auth_token');
  const fileCodes = [];
  
  // 1. YENİ MEDIA FAYLLARI YÜKLƏYİN (əgər varsa)
  if (changes.newMediaFiles && changes.newMediaFiles.length > 0) {
    for (const mediaFile of changes.newMediaFiles) {
      const formData = new FormData();
      formData.append('file', mediaFile.file);
      formData.append('type', mediaFile.type); // 'video', 'image', 'audio', 'document'
      
      if (mediaFile.title) formData.append('title', mediaFile.title);
      if (mediaFile.description) formData.append('description', mediaFile.description);
      
      try {
        const uploadResponse = await fetch('/api/v1/lessons/upload-temp-media', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${token}`
          },
          body: formData
        });
        
        if (!uploadResponse.ok) {
          throw new Error('Media upload failed');
        }
        
        const { file_code } = await uploadResponse.json();
        fileCodes.push(file_code);
        console.log('Media uploaded:', file_code);
      } catch (error) {
        console.error('Error uploading media:', error);
        throw error;
      }
    }
  }
  
  // 2. LESSON-U UPDATE EDİN (BÜTÜN DƏYİŞİKLİKLƏR BİR YERDƏ)
  const updateData = {
    // Content dəyişikliyi
    content: changes.content || undefined, // { az: "...", en: "..." }
    
    // Title (ad) dəyişikliyi
    title: changes.title || undefined, // { az: "...", en: "..." }
    
    // Description dəyişikliyi
    description: changes.description || undefined,
    
    // Digər field-lər
    lesson_type: changes.lesson_type || undefined,
    duration_minutes: changes.duration_minutes || undefined,
    status: changes.status || undefined,
    
    // YENİ MEDIA FAYLLARI (file_codes ilə)
    file_codes: fileCodes.length > 0 ? fileCodes : undefined,
    
    // MÖVCUD MEDIA FAYLLARI (saxlamaq və ya silmək üçün)
    // Əgər media_files göndərsəniz, bütün media files replace olunur
    // Əgər göndərməsəniz, mövcud media files saxlanılır + yeni fayllar əlavə olunur
    media_files: changes.mediaFiles || undefined
  };
  
  // Undefined field-ləri silin (cleanup)
  Object.keys(updateData).forEach(key => {
    if (updateData[key] === undefined) {
      delete updateData[key];
    }
  });
  
  try {
    const response = await fetch(`/api/v1/modules/${moduleId}/lessons/${lessonId}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify(updateData)
    });
    
    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Update failed');
    }
    
    const updatedLesson = await response.json();
    console.log('Lesson updated successfully:', updatedLesson);
    return updatedLesson;
    
  } catch (error) {
    console.error('Error updating lesson:', error);
    throw error;
  }
}
```

---

## İstifadə Nümunəsi:

```javascript
// Nümunə 1: Content, title və yeni media əlavə etmək
await updateLessonWithMedia(
  1, // moduleId
  5, // lessonId
  {
    // Content dəyişikliyi
    content: {
      az: "Yeni məzmun azərbaycan dilində",
      en: "New content in English"
    },
    
    // Title (ad) dəyişikliyi
    title: {
      az: "Yeni dərs adı",
      en: "New lesson title"
    },
    
    // Yeni media faylları
    newMediaFiles: [
      {
        file: videoFile, // File object
        type: 'video',
        title: 'Video başlığı',
        description: 'Video təsviri'
      },
      {
        file: imageFile,
        type: 'image',
        title: 'Şəkil başlığı'
      }
    ]
  }
);

// Nümunə 2: Yalnız content və title dəyişmək (media yoxdur)
await updateLessonWithMedia(
  1, // moduleId
  5, // lessonId
  {
    content: {
      az: "Yalnız məzmun dəyişdi",
      en: "Only content changed"
    },
    title: {
      az: "Yalnız ad dəyişdi",
      en: "Only title changed"
    }
    // newMediaFiles yoxdur
  }
);

// Nümunə 3: Yalnız yeni media əlavə etmək (content/title dəyişmir)
await updateLessonWithMedia(
  1, // moduleId
  5, // lessonId
  {
    newMediaFiles: [
      {
        file: newVideoFile,
        type: 'video',
        title: 'Yeni video'
      }
    ]
  }
);

// Nümunə 4: Mövcud media-nı silmək və yenisini əlavə etmək
await updateLessonWithMedia(
  1, // moduleId
  5, // lessonId
  {
    content: { az: "Yeni məzmun", en: "New content" },
    title: { az: "Yeni ad", en: "New title" },
    
    // Yeni media
    newMediaFiles: [
      {
        file: newVideoFile,
        type: 'video'
      }
    ],
    
    // Mövcud media files (yalnız saxlamaq istədiyiniz)
    // Bu array-də olmayan fayllar silinəcək
    mediaFiles: [
      {
        type: 'image',
        url: '/storage/lessons/5/old-image.jpg',
        filename: 'old-image.jpg',
        title: 'Saxlanılacaq şəkil'
      }
      // Köhnə video burada yoxdur, ona görə silinəcək
    ]
  }
);
```

---

## Vue.js Component Nümunəsi:

```vue
<template>
  <div>
    <form @submit.prevent="handleUpdate">
      <!-- Title -->
      <input v-model="formData.title.az" placeholder="Azərbaycan dilində ad" />
      <input v-model="formData.title.en" placeholder="English title" />
      
      <!-- Content -->
      <textarea v-model="formData.content.az" placeholder="Məzmun (AZ)" />
      <textarea v-model="formData.content.en" placeholder="Content (EN)" />
      
      <!-- Yeni media faylları -->
      <input 
        type="file" 
        @change="handleFileSelect" 
        accept="video/*,image/*,audio/*"
        multiple
      />
      
      <div v-for="(file, index) in selectedFiles" :key="index">
        {{ file.name }}
        <select v-model="file.type">
          <option value="video">Video</option>
          <option value="image">Şəkil</option>
          <option value="audio">Audio</option>
        </select>
      </div>
      
      <button type="submit" :disabled="loading">
        {{ loading ? 'Yenilənir...' : 'Yenilə' }}
      </button>
    </form>
  </div>
</template>

<script>
export default {
  data() {
    return {
      moduleId: 1,
      lessonId: 5,
      loading: false,
      formData: {
        title: { az: '', en: '' },
        content: { az: '', en: '' },
      },
      selectedFiles: []
    }
  },
  
  methods: {
    handleFileSelect(event) {
      this.selectedFiles = Array.from(event.target.files).map(file => ({
        file: file,
        type: 'video', // default
        title: file.name
      }));
    },
    
    async handleUpdate() {
      this.loading = true;
      
      try {
        const changes = {
          title: this.formData.title,
          content: this.formData.content,
          newMediaFiles: this.selectedFiles.map(f => ({
            file: f.file,
            type: f.type,
            title: f.title
          }))
        };
        
        await this.updateLessonWithMedia(this.moduleId, this.lessonId, changes);
        
        this.$toast.success('Lesson uğurla yeniləndi!');
        // Redirect və ya refresh
        this.$router.push(`/lessons/${this.lessonId}`);
        
      } catch (error) {
        this.$toast.error('Xəta: ' + error.message);
      } finally {
        this.loading = false;
      }
    },
    
    async updateLessonWithMedia(moduleId, lessonId, changes) {
      const token = localStorage.getItem('auth_token');
      const fileCodes = [];
      
      // 1. Yeni media faylları yüklə
      if (changes.newMediaFiles && changes.newMediaFiles.length > 0) {
        for (const mediaFile of changes.newMediaFiles) {
          const formData = new FormData();
          formData.append('file', mediaFile.file);
          formData.append('type', mediaFile.type);
          
          if (mediaFile.title) formData.append('title', mediaFile.title);
          if (mediaFile.description) formData.append('description', mediaFile.description);
          
          const uploadResponse = await fetch('/api/v1/lessons/upload-temp-media', {
            method: 'POST',
            headers: { 'Authorization': `Bearer ${token}` },
            body: formData
          });
          
          if (!uploadResponse.ok) throw new Error('Media upload failed');
          
          const { file_code } = await uploadResponse.json();
          fileCodes.push(file_code);
        }
      }
      
      // 2. Lesson-u update et
      const updateData = {
        ...changes,
        file_codes: fileCodes.length > 0 ? fileCodes : undefined
      };
      
      // Undefined field-ləri sil
      Object.keys(updateData).forEach(key => {
        if (updateData[key] === undefined || key === 'newMediaFiles') {
          delete updateData[key];
        }
      });
      
      const response = await fetch(`/api/v1/modules/${moduleId}/lessons/${lessonId}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(updateData)
      });
      
      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message || 'Update failed');
      }
      
      return await response.json();
    }
  }
}
</script>
```

---

## React Hook Nümunəsi:

```javascript
import { useState } from 'react';

function useLessonUpdate() {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  
  const updateLesson = async (moduleId, lessonId, changes) => {
    setLoading(true);
    setError(null);
    
    try {
      const token = localStorage.getItem('auth_token');
      const fileCodes = [];
      
      // 1. Yeni media faylları yüklə
      if (changes.newMediaFiles?.length > 0) {
        for (const mediaFile of changes.newMediaFiles) {
          const formData = new FormData();
          formData.append('file', mediaFile.file);
          formData.append('type', mediaFile.type);
          
          if (mediaFile.title) formData.append('title', mediaFile.title);
          
          const uploadResponse = await fetch('/api/v1/lessons/upload-temp-media', {
            method: 'POST',
            headers: { 'Authorization': `Bearer ${token}` },
            body: formData
          });
          
          if (!uploadResponse.ok) throw new Error('Media upload failed');
          
          const { file_code } = await uploadResponse.json();
          fileCodes.push(file_code);
        }
      }
      
      // 2. Lesson-u update et
      const updateData = {
        title: changes.title,
        content: changes.content,
        description: changes.description,
        file_codes: fileCodes.length > 0 ? fileCodes : undefined
      };
      
      // Cleanup undefined
      Object.keys(updateData).forEach(key => {
        if (updateData[key] === undefined) delete updateData[key];
      });
      
      const response = await fetch(`/api/v1/modules/${moduleId}/lessons/${lessonId}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(updateData)
      });
      
      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || 'Update failed');
      }
      
      const updatedLesson = await response.json();
      return updatedLesson;
      
    } catch (err) {
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  };
  
  return { updateLesson, loading, error };
}

// İstifadə:
function LessonEditForm({ moduleId, lessonId }) {
  const { updateLesson, loading, error } = useLessonUpdate();
  const [title, setTitle] = useState({ az: '', en: '' });
  const [content, setContent] = useState({ az: '', en: '' });
  const [files, setFiles] = useState([]);
  
  const handleSubmit = async (e) => {
    e.preventDefault();
    
    try {
      await updateLesson(moduleId, lessonId, {
        title,
        content,
        newMediaFiles: files.map(f => ({
          file: f,
          type: 'video'
        }))
      });
      
      alert('Lesson uğurla yeniləndi!');
    } catch (err) {
      alert('Xəta: ' + err.message);
    }
  };
  
  return (
    <form onSubmit={handleSubmit}>
      {/* Form fields */}
      <button type="submit" disabled={loading}>
        {loading ? 'Yenilənir...' : 'Yenilə'}
      </button>
    </form>
  );
}
```

---

## Xülasə:

1. **Yeni media faylları yükləyin** → `file_code` alın
2. **Lesson-u update edin** → `file_codes`, `content`, `title` və digər field-ləri bir yerdə göndərin
3. **Backend avtomatik:**
   - `file_codes`-dən faylları final folder-ə köçürür
   - `content` və `title`-ı update edir
   - Bütün dəyişiklikləri bir yerdə tətbiq edir

**Vacib:** Bütün dəyişikliklər (content, title, media) **bir request-də** göndərilir!

