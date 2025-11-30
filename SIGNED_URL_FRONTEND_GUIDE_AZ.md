# Signed URL ilə Video Oynatma - Frontend Guide

## 📋 Sistemin İşləmə Prinsipi

### 1. API Response-dan Signed URL Almaq

Training detailed endpoint-dən (`GET /api/v1/trainings/{id}/detailed`) response alındıqda, hər bir lesson-un video materialı üçün **signed URL** gəlir.

**Response struktur:**
```json
{
  "modules": [
    {
      "lessons": [
        {
          "id": 2,
          "media_files": [
            {
              "type": "video",
              "path": "lessons/2/video.mp4",
              "filename": "video.mp4",
              "size": 17028948,
              "mime_type": "video/mp4",
              "signed_url": "http://localhost:8000/api/v1/modules/1/lessons/2/media/download?path=lessons%2F2%2Fvideo.mp4&signature=abc123&expires=1234567890",
              "signed_url_expires_at": "2025-11-25T14:00:00.000000Z",
              "url": "http://localhost:8000/api/v1/modules/1/lessons/2/media/download?path=lessons%2F2%2Fvideo.mp4"
            }
          ]
        }
      ]
    }
  ]
}
```

### 2. Signed URL Xüsusiyyətləri

**Signed URL:**
- ✅ **Temporary**: 2 saat sonra expire olur
- ✅ **Secure**: Signature ilə verify olunur
- ✅ **User-specific**: User ID ilə bağlıdır
- ✅ **Direct usage**: Birbaşa `<video>` tag-də istifadə oluna bilər
- ✅ **No blob needed**: Blob URL yaratmağa ehtiyac yoxdur

**Vacib qeydlər:**
- Signed URL yalnız **video faylları** üçün gəlir
- Şəkil və digər fayllar üçün adi `url` field-i istifadə olunur
- Signed URL expire olduqdan sonra yenidən training detailed endpoint-dən alınmalıdır

### 3. Response-dan Signed URL Çıxartmaq

**Addım-addım:**

1. **Training detailed response-u al:**
   ```javascript
   GET /api/v1/trainings/1/detailed?lang=az
   Headers: Authorization: Bearer {token}
   ```

2. **Module və lesson-u tap:**
   ```javascript
   const training = response.data;
   const module = training.modules.find(m => m.id === 1);
   const lesson = module.lessons.find(l => l.id === 2);
   ```

3. **Video faylını tap və signed URL-i al:**
   ```javascript
   const videoFile = lesson.media_files.find(f => f.type === 'video');
   
   // Signed URL (temporary, 2 saat sonra expire olur)
   const signedUrl = videoFile.signed_url;
   
   // Fallback URL (əgər signed URL expire olubsa)
   const fallbackUrl = videoFile.url;
   ```

### 4. Video Player-də İstifadə

**✅ DÜZGÜN (Signed URL ilə):**
```vue
<template>
  <div>
    <video 
      v-if="videoSignedUrl" 
      :src="videoSignedUrl" 
      controls 
      preload="metadata"
      @error="handleVideoError"
      @loadstart="handleVideoLoadStart"
    />
    <div v-if="loading">Video yüklənir...</div>
    <div v-if="error" class="error">{{ error }}</div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';

const props = defineProps({
  lesson: Object, // Lesson object from API response
});

const videoSignedUrl = ref(null);
const loading = ref(false);
const error = ref(null);

const loadVideo = () => {
  try {
    loading.value = true;
    error.value = null;

    // Video faylını tap
    const videoFile = props.lesson.media_files?.find(f => f.type === 'video');
    
    if (!videoFile) {
      throw new Error('Video faylı tapılmadı');
    }

    // Signed URL-i istifadə et (əgər varsa)
    if (videoFile.signed_url) {
      // Signed URL-in expire olub-olmadığını yoxla
      const expiresAt = new Date(videoFile.signed_url_expires_at);
      const now = new Date();
      
      if (expiresAt > now) {
        // Signed URL hələ də aktivdir
        videoSignedUrl.value = videoFile.signed_url;
      } else {
        // Signed URL expire olub, fallback URL istifadə et
        console.warn('Signed URL expired, using fallback URL');
        videoSignedUrl.value = videoFile.url;
      }
    } else {
      // Signed URL yoxdursa, adi URL istifadə et
      videoSignedUrl.value = videoFile.url;
    }

  } catch (err) {
    error.value = err.message;
    console.error('Video yükləmə xətası:', err);
  } finally {
    loading.value = false;
  }
};

const handleVideoError = (e) => {
  console.error('Video element xətası:', e);
  
  // Əgər signed URL ilə error varsa, fallback URL yoxla
  const videoFile = props.lesson.media_files?.find(f => f.type === 'video');
  if (videoFile && videoSignedUrl.value === videoFile.signed_url && videoFile.url) {
    console.log('Trying fallback URL...');
    videoSignedUrl.value = videoFile.url;
  } else {
    error.value = 'Video oynatıla bilmədi. Zəhmət olmasa yenidən yoxlayın.';
  }
};

const handleVideoLoadStart = () => {
  loading.value = false;
};

// Component mount olduqda video yüklə
onMounted(() => {
  if (props.lesson) {
    loadVideo();
  }
});

// Lesson dəyişdikdə video yenilə
watch(() => props.lesson, () => {
  if (props.lesson) {
    loadVideo();
  }
}, { deep: true });
</script>
```

### 5. Training Start Səhifəsində İstifadə

**TrainingStart.vue nümunəsi:**

```vue
<template>
  <div class="training-start">
    <div v-for="module in training.modules" :key="module.id" class="module">
      <h2>{{ module.title.az }}</h2>
      
      <div v-for="lesson in module.lessons" :key="lesson.id" class="lesson">
        <h3>{{ lesson.title.az }}</h3>
        
        <!-- Video player -->
        <div v-if="hasVideo(lesson)" class="video-container">
          <VideoPlayer :lesson="lesson" />
        </div>
        
        <!-- Digər media faylları -->
        <div v-for="mediaFile in lesson.media_files" :key="mediaFile.url">
          <img 
            v-if="mediaFile.type === 'image'"
            :src="mediaFile.url" 
            alt="Lesson image"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import VideoPlayer from '@/components/VideoPlayer.vue';
import axios from 'axios';

const props = defineProps({
  trainingId: Number,
});

const training = ref(null);
const loading = ref(false);

const hasVideo = (lesson) => {
  return lesson.media_files?.some(f => f.type === 'video');
};

const loadTraining = async () => {
  try {
    loading.value = true;
    const token = localStorage.getItem('auth_token');
    
    const response = await axios.get(`/api/v1/trainings/${props.trainingId}/detailed`, {
      params: { lang: 'az' },
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });
    
    training.value = response.data;
  } catch (error) {
    console.error('Training yükləmə xətası:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadTraining();
});
</script>
```

### 6. Signed URL Expire Olduqda

**Problem:** Signed URL 2 saat sonra expire olur.

**Həll:** 
1. Video error verəndə training detailed endpoint-dən yenidən response al
2. Yeni signed URL-i istifadə et

```javascript
const refreshVideoUrl = async () => {
  try {
    const token = localStorage.getItem('auth_token');
    const response = await axios.get(`/api/v1/trainings/${trainingId}/detailed`, {
      params: { lang: 'az' },
      headers: { 'Authorization': `Bearer ${token}` }
    });
    
    // Yeni signed URL-i tap və istifadə et
    const lesson = response.data.modules
      .find(m => m.id === moduleId)
      .lessons.find(l => l.id === lessonId);
    
    const videoFile = lesson.media_files.find(f => f.type === 'video');
    if (videoFile.signed_url) {
      videoSignedUrl.value = videoFile.signed_url;
    }
  } catch (error) {
    console.error('Video URL yeniləmə xətası:', error);
  }
};

// Video error verəndə refresh et
const handleVideoError = async (e) => {
  // Əgər signed URL expire olubsa, yenilə
  if (e.target.error?.code === MediaError.MEDIA_ERR_SRC_NOT_SUPPORTED) {
    await refreshVideoUrl();
  }
};
```

### 7. Təhlükəsizlik

**Signed URL-lərin təhlükəsizliyi:**
- ✅ Expire olur (2 saat sonra)
- ✅ Signature ilə verify olunur
- ✅ User ID ilə bağlıdır
- ✅ URL kopyalanıb paylaşılsa belə, expire olduqdan sonra işləməyəcək

**Vacib qeydlər:**
- Signed URL-lər yalnız authenticated user-lər üçün yaradılır
- URL kopyalanıb paylaşılsa belə, expire olduqdan sonra işləməyəcək
- Hər user üçün fərqli signed URL yaradılır

### 8. Xülasə

**Proses:**
1. Training detailed endpoint-dən response al
2. Response-dan `lesson.media_files[].signed_url` tap
3. Signed URL-i birbaşa `<video>` tag-də istifadə et
4. Expire olduqda yenidən training detailed endpoint-dən al

**Üstünlüklər:**
- ✅ Blob URL yaratmağa ehtiyac yoxdur
- ✅ Birbaşa `<video>` tag-də istifadə oluna bilər
- ✅ Temporary və secure-dir
- ✅ Expire olduqdan sonra işləməyəcək

**Vacib qeydlər:**
- Signed URL yalnız video faylları üçün gəlir
- 2 saat sonra expire olur
- Expire olduqda yenidən training detailed endpoint-dən alınmalıdır

