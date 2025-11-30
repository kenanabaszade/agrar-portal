# Lesson Update - Frontend Guide

## 📋 Endpoint-lər

### 1. Lesson Update (Əsas Update)
```http
PUT /api/v1/modules/{module}/lessons/{lesson}
```

### 2. Temporary Media Upload (Yeni fayl yükləmək üçün)
```http
POST /api/v1/lessons/upload-temp-media
```

### 3. Direct Media Upload (Birbaşa lesson-a yükləmək)
```http
POST /api/v1/lessons/{lesson}/upload-media
```

### 4. Media Silmək
```http
DELETE /api/v1/lessons/{lesson}/remove-media
```

---

## 🔄 Lesson Update Prosesi

### **Seçim 1: Temporary Media İstifadə Etmək (Tövsiyə olunur - böyük fayllar üçün)**

#### Addım 1: Yeni media faylını temporary folder-ə yükləyin

```javascript
// 1. Yeni video/image/audio faylını yükləyin
const formData = new FormData();
formData.append('file', file); // File object
formData.append('type', 'video'); // 'image', 'video', 'audio', 'document'
formData.append('title', 'Video başlığı'); // Optional
formData.append('description', 'Video təsviri'); // Optional

const uploadResponse = await fetch('/api/v1/lessons/upload-temp-media', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`
  },
  body: formData
});

const { file_code, temp_url } = await uploadResponse.json();
// file_code: "FILE_57C3CCC0" - bu kodu saxlayın!
```

#### Addım 2: Lesson-u update edin (file_codes ilə)

```javascript
// 2. Lesson-u update edin və file_code-u əlavə edin
const updateData = {
  // Digər field-lər
  title: { az: "Yeni başlıq", en: "New title" },
  content: { az: "Yeni məzmun", en: "New content" },
  
  // YENİ: Temporary folder-dən faylları əlavə etmək üçün
  file_codes: ["FILE_57C3CCC0"], // Əvvəlki addımda alınan file_code
  
  // VƏ YA mövcud media_files-i update etmək üçün
  media_files: [
    // Mövcud fayllar (silməmək üçün)
    ...existingMediaFiles,
    // Yeni fayllar file_codes-dən avtomatik əlavə olunacaq
  ]
};

const response = await fetch(`/api/v1/modules/${moduleId}/lessons/${lessonId}`, {
  method: 'PUT',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify(updateData)
});
```

**Nəticə:**
- `file_codes` array-indəki fayllar temporary folder-dən lesson-un final folder-inə köçürüləcək
- Lesson-un `media_files` array-inə avtomatik əlavə olunacaq
- Temporary file record-lar silinəcək

---

### **Seçim 2: Direct Media Upload (Kiçik fayllar üçün)**

```javascript
// Birbaşa lesson-a fayl yükləyin
const formData = new FormData();
formData.append('file', file);
formData.append('type', 'video');
formData.append('title', 'Video başlığı');
formData.append('description', 'Video təsviri');

const response = await fetch(`/api/v1/lessons/${lessonId}/upload-media`, {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`
  },
  body: formData
});

const { media_file } = await response.json();
// media_file artıq lesson-a əlavə olunub
```

---

### **Seçim 3: Media Files Array-i ilə Update (Tam nəzarət)**

```javascript
// Lesson-un bütün media_files-ini update edin
const updateData = {
  title: { az: "Yeni başlıq", en: "New title" },
  
  // Bütün media_files-i yenidən göndərin
  media_files: [
    // Mövcud fayllar (saxlamaq istədiyiniz)
    {
      type: 'video',
      url: '/storage/lessons/1/existing-video.mp4',
      filename: 'existing-video.mp4',
      title: 'Mövcud video',
      description: 'Bu video saxlanılacaq'
    },
    // Yeni fayllar (file_codes-dən əlavə olunacaq)
    // Və ya yeni upload edilmiş fayllar
  ],
  
  // Yeni fayllar üçün file_codes
  file_codes: ["FILE_57C3CCC0", "FILE_ABC12345"]
};

const response = await fetch(`/api/v1/modules/${moduleId}/lessons/${lessonId}`, {
  method: 'PUT',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify(updateData)
});
```

**Qeyd:** `file_codes` göndərdikdə, yeni fayllar mövcud `media_files`-ə **əlavə** olunur (replace deyil).

---

## 📝 Tam Update Nümunəsi

```javascript
async function updateLesson(moduleId, lessonId, lessonData, newFiles = []) {
  const token = localStorage.getItem('auth_token');
  
  // 1. Yeni faylları temporary folder-ə yükləyin
  const fileCodes = [];
  
  for (const file of newFiles) {
    const formData = new FormData();
    formData.append('file', file.file);
    formData.append('type', file.type); // 'video', 'image', 'audio', 'document'
    if (file.title) formData.append('title', file.title);
    if (file.description) formData.append('description', file.description);
    
    const uploadResponse = await fetch('/api/v1/lessons/upload-temp-media', {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${token}` },
      body: formData
    });
    
    const { file_code } = await uploadResponse.json();
    fileCodes.push(file_code);
  }
  
  // 2. Lesson-u update edin
  const updateData = {
    // Lesson məlumatları
    title: lessonData.title, // { az: "...", en: "..." }
    content: lessonData.content,
    description: lessonData.description,
    lesson_type: lessonData.lesson_type,
    duration_minutes: lessonData.duration_minutes,
    status: lessonData.status, // 'draft', 'published', 'archived'
    is_required: lessonData.is_required,
    sequence: lessonData.sequence,
    
    // Media faylları
    file_codes: fileCodes, // Yeni fayllar
    // Və ya mövcud media_files-i update etmək istəsəniz:
    // media_files: lessonData.media_files
  };
  
  const response = await fetch(`/api/v1/modules/${moduleId}/lessons/${lessonId}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify(updateData)
  });
  
  return await response.json();
}

// İstifadə:
updateLesson(
  1, // moduleId
  5, // lessonId
  {
    title: { az: "Yeni dərs", en: "New lesson" },
    content: { az: "Məzmun...", en: "Content..." },
    lesson_type: "video",
    status: "published"
  },
  [
    { file: videoFile, type: 'video', title: 'Video başlığı' },
    { file: imageFile, type: 'image', title: 'Şəkil başlığı' }
  ]
);
```

---

## 🗑️ Media Silmək

### Seçim 1: Media Files Array-dən çıxarın

```javascript
// Lesson-u update edərkən media_files array-indən silin
const updateData = {
  media_files: [
    // Yalnız saxlamaq istədiyiniz fayllar
    // Silmək istədiyiniz faylları buraya yazmayın
  ]
};
```

### Seçim 2: Remove Media Endpoint

```javascript
// Birbaşa media silmək
const response = await fetch(`/api/v1/lessons/${lessonId}/remove-media`, {
  method: 'DELETE',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({
    media_index: 0 // Silmək istədiyiniz media-nın index-i
  })
});
```

---

## 📋 Update Edilə Bilən Field-lər

```typescript
interface LessonUpdateData {
  // Tərcümə field-ləri (object format)
  title?: { az?: string; en?: string; ru?: string };
  content?: { az?: string; en?: string; ru?: string };
  description?: { az?: string; en?: string; ru?: string };
  
  // Digər field-lər
  lesson_type?: 'text' | 'video' | 'audio' | 'image' | 'mixed';
  duration_minutes?: number;
  video_url?: string;
  pdf_url?: string;
  sequence?: number;
  status?: 'draft' | 'published' | 'archived';
  is_required?: boolean;
  min_completion_time?: number;
  metadata?: Record<string, any>;
  
  // Media faylları
  file_codes?: string[]; // Yeni fayllar üçün (temporary folder-dən)
  media_files?: MediaFile[]; // Tam media files array-i
}

interface MediaFile {
  type: 'image' | 'video' | 'audio' | 'document';
  url: string;
  filename?: string;
  size?: number;
  mime_type?: string;
  title?: string;
  description?: string;
}
```

---

## ⚠️ Vacib Qeydlər

1. **`file_codes` vs `media_files`:**
   - `file_codes`: Yeni fayllar üçün (temporary folder-dən)
   - `media_files`: Bütün media files array-i (tam nəzarət)

2. **Media Files Update:**
   - `file_codes` göndərdikdə, yeni fayllar mövcud `media_files`-ə **əlavə** olunur
   - `media_files` göndərdikdə, **bütün** media files replace olunur (köhnələr silinir)

3. **Temporary Files:**
   - Temporary fayllar 24 saat sonra avtomatik silinir
   - `file_codes` göndərdikdə, fayllar final folder-ə köçürülür və temp record silinir

4. **File Size Limits:**
   - Video: 100MB
   - Image: 5MB
   - Audio: 10MB
   - Document: 10MB

---

## 🔍 Response Format

### Update Success Response:
```json
{
  "id": 1,
  "module_id": 1,
  "title": { "az": "Yeni dərs", "en": "New lesson" },
  "lesson_type": "video",
  "media_files": [
    {
      "type": "video",
      "url": "/storage/lessons/1/video.mp4",
      "filename": "video.mp4",
      "size": 17029641,
      "mime_type": "video/mp4",
      "title": "Video başlığı",
      "description": "Video təsviri"
    }
  ],
  "status": "published",
  ...
}
```

---

## 💡 Best Practices

1. **Böyük fayllar üçün:** `upload-temp-media` + `file_codes` istifadə edin
2. **Kiçik fayllar üçün:** `upload-media` endpoint-i istifadə edin
3. **Çoxlu fayl üçün:** Hər fayl üçün ayrı-ayrı `upload-temp-media` çağırın, sonra bütün `file_codes`-i bir yerdə göndərin
4. **Media silmək üçün:** `media_files` array-indən çıxarın və update edin

