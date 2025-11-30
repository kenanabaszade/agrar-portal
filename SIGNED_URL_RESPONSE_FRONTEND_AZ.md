# Signed URL Response - Frontend İstifadəsi

## ✅ Yeni Response Formatı

Artıq `/api/v1/trainings/{id}/detailed` endpoint-dən response alındıqda, **video faylları üçün `signed_url` field-i gəlir**.

---

## 📋 Response Strukturu

### Əvvəlki Format (Signed URL yoxdur):
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
              "url": "http://localhost:8000/api/v1/modules/1/lessons/2/media/download?path=lessons%2F2%2Fvideo.mp4",
              "filename": "video.mp4"
            }
          ]
        }
      ]
    }
  ]
}
```

### Yeni Format (Signed URL ilə):
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
              "url": "http://localhost:8000/api/v1/modules/1/lessons/2/media/download?path=lessons%2F2%2Fvideo.mp4",
              "signed_url": "http://localhost:8000/api/v1/modules/1/lessons/2/media/download?path=lessons%2F2%2Fvideo.mp4&signature=abc123...&expires=1735123456",
              "signed_url_expires_at": "2025-11-25T14:00:00.000000Z",
              "path": "lessons/2/video.mp4",
              "filename": "video.mp4",
              "size": 17028948,
              "mime_type": "video/mp4"
            }
          ]
        }
      ]
    }
  ]
}
```

---

## 🎯 Yeni Field-lər

### 1. `signed_url`
- **Nədir?** Müvəqqəti (2 saat) və təhlükəsiz video link
- **Nə üçün?** Birbaşa `<video>` tag-də istifadə oluna bilər
- **Expiration:** 2 saat sonra expire olur

### 2. `signed_url_expires_at`
- **Nədir?** Signed URL-in expire olacağı tarix
- **Format:** ISO 8601 (e.g., "2025-11-25T14:00:00.000000Z")
- **Nə üçün?** Expire olub-olmadığını yoxlamaq üçün

### 3. `path`
- **Nədir?** Video faylının path-i
- **Nümunə:** "lessons/2/video.mp4"
- **Nə üçün?** Reference üçün

---

## 💻 Frontend İstifadəsi

### Addım 1: Response-dan Signed URL Al

```javascript
// Training detailed response-u al
const response = await axios.get(`/api/v1/trainings/${trainingId}/detailed`, {
  params: { lang: 'az' },
  headers: { 'Authorization': `Bearer ${token}` }
});

const training = response.data;

// Module və lesson-u tap
const module = training.modules.find(m => m.id === 1);
const lesson = module.lessons.find(l => l.id === 2);

// Video faylını tap
const videoFile = lesson.media_files.find(f => f.type === 'video');

// Signed URL-i al
const signedUrl = videoFile.signed_url; // ✅ Yeni field
const expiresAt = videoFile.signed_url_expires_at; // ✅ Expiration tarixi
const fallbackUrl = videoFile.url; // Fallback URL
```

### Addım 2: Signed URL-in Expire Olub-Olmadığını Yoxla

```javascript
const isSignedUrlValid = (videoFile) => {
  if (!videoFile.signed_url || !videoFile.signed_url_expires_at) {
    return false;
  }
  
  const expiresAt = new Date(videoFile.signed_url_expires_at);
  const now = new Date();
  
  return expiresAt > now; // Hələ expire olmayıbsa true
};
```

### Addım 3: Video Player-də İstifadə Et

```vue
<template>
  <div>
    <!-- Signed URL birbaşa istifadə oluna bilər -->
    <video 
      v-if="videoSignedUrl" 
      :src="videoSignedUrl" 
      controls 
      preload="metadata"
      @error="onVideoError"
    />
    
    <div v-if="videoLoading">Video yüklənir...</div>
    <div v-if="videoError" class="error">{{ videoError }}</div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  lesson: Object, // Lesson object from API response
});

const videoSignedUrl = ref(null);
const videoLoading = ref(false);
const videoError = ref(null);

// Video URL-ini təyin et
const setVideoUrl = () => {
  if (!props.lesson?.media_files) {
    return;
  }

  const videoFile = props.lesson.media_files.find(f => f.type === 'video');
  
  if (!videoFile) {
    videoSignedUrl.value = null;
    return;
  }

  // Signed URL-in expire olub-olmadığını yoxla
  if (videoFile.signed_url && videoFile.signed_url_expires_at) {
    const expiresAt = new Date(videoFile.signed_url_expires_at);
    const now = new Date();
    
    if (expiresAt > now) {
      // Signed URL hələ də aktivdir
      videoSignedUrl.value = videoFile.signed_url;
      videoError.value = null;
      return;
    } else {
      // Signed URL expire olub
      console.warn('Signed URL expired, using fallback URL');
    }
  }

  // Fallback: adi URL istifadə et
  if (videoFile.url) {
    videoSignedUrl.value = videoFile.url;
  } else {
    videoSignedUrl.value = null;
    videoError.value = 'Video URL tapılmadı';
  }
};

// Video error handler
const onVideoError = async (e) => {
  console.error('Video error:', e);
  videoError.value = 'Video faylı tapılmadı';
  
  // Signed URL expire olubsa, yenilə
  if (props.lesson?.media_files) {
    const videoFile = props.lesson.media_files.find(f => f.type === 'video');
    if (videoFile?.signed_url) {
      await refreshVideoUrl();
    }
  }
};

// Video URL-i yenilə (signed URL expire olduqda)
const refreshVideoUrl = async () => {
  try {
    videoLoading.value = true;
    const token = localStorage.getItem('auth_token');
    const lang = 'az'; // və ya currentLang.value
    
    // Training detailed endpoint-dən yenidən response al
    const response = await axios.get(
      `/api/v1/trainings/${trainingId}/detailed`,
      {
        params: { lang: lang },
        headers: { 'Authorization': `Bearer ${token}` }
      }
    );

    // Yeni signed URL-i tap
    const module = response.data.modules.find(m => m.id === moduleId);
    const lesson = module.lessons.find(l => l.id === props.lesson.id);
    const videoFile = lesson.media_files.find(f => f.type === 'video');
    
    if (videoFile?.signed_url) {
      videoSignedUrl.value = videoFile.signed_url;
      videoError.value = null;
    }
  } catch (error) {
    console.error('Video URL yeniləmə xətası:', error);
    videoError.value = 'Video URL yenilənə bilmədi';
  } finally {
    videoLoading.value = false;
  }
};

onMounted(() => {
  setVideoUrl();
});
</script>
```

---

## 📝 Tam Nümunə (TrainingStart.vue)

```vue
<template>
  <div class="training-start">
    <!-- Video player -->
    <div v-if="currentVideoSignedUrl" class="video-container">
      <video 
        ref="videoPlayer"
        :src="currentVideoSignedUrl" 
        controls 
        preload="metadata"
        @error="onVideoError"
        @loadstart="onVideoLoadStart"
        class="video-player"
      />
    </div>

    <!-- Loading -->
    <div v-if="videoLoading" class="loading">
      Video yüklənir...
    </div>

    <!-- Error -->
    <div v-if="videoError" class="error">
      {{ videoError }}
    </div>

    <!-- Lessons list -->
    <div v-for="module in training?.modules" :key="module.id" class="module">
      <h2>{{ module.title.az }}</h2>
      
      <div v-for="lesson in module.lessons" :key="lesson.id" class="lesson">
        <h3>{{ lesson.title.az }}</h3>
        
        <button @click="onLessonClick(lesson)">
          {{ lesson.title.az }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const trainingId = computed(() => parseInt(route.params.id));

// State
const training = ref(null);
const currentVideoSignedUrl = ref(null);
const videoLoading = ref(false);
const videoError = ref(null);
const currentLesson = ref(null);
const videoPlayer = ref(null);
const currentLang = ref('az'); // Default language

// Video URL təyin et
const setVideoUrl = (lesson) => {
  if (!lesson?.media_files) {
    currentVideoSignedUrl.value = null;
    return;
  }

  const videoFile = lesson.media_files.find(f => f.type === 'video');
  
  if (!videoFile) {
    currentVideoSignedUrl.value = null;
    return;
  }

  currentLesson.value = lesson;

  // Signed URL-in expire olub-olmadığını yoxla
  if (videoFile.signed_url && videoFile.signed_url_expires_at) {
    const expiresAt = new Date(videoFile.signed_url_expires_at);
    const now = new Date();
    
    if (expiresAt > now) {
      // Signed URL hələ də aktivdir ✅
      currentVideoSignedUrl.value = videoFile.signed_url;
      videoError.value = null;
      console.log('Using signed URL:', videoFile.signed_url);
      return;
    } else {
      // Signed URL expire olub
      console.warn('Signed URL expired, using fallback URL');
    }
  }

  // Fallback: adi URL istifadə et
  if (videoFile.url) {
    currentVideoSignedUrl.value = videoFile.url;
    console.log('Using fallback URL:', videoFile.url);
  } else {
    currentVideoSignedUrl.value = null;
    videoError.value = 'Video URL tapılmadı';
  }
};

// Video error handler
const onVideoError = async (e) => {
  console.error('Video error:', e);
  videoError.value = 'Video faylı tapılmadı';
  
  // Signed URL expire olubsa, yenilə
  if (currentLesson.value) {
    await refreshVideoUrl();
  }
};

const onVideoLoadStart = () => {
  videoLoading.value = false;
};

// Video URL yenilə
const refreshVideoUrl = async () => {
  try {
    videoLoading.value = true;
    const token = localStorage.getItem('auth_token');
    const lang = currentLang.value || 'az';
    
    // Training detailed endpoint-dən yenidən response al
    const response = await axios.get(
      `/api/v1/trainings/${trainingId.value}/detailed`,
      {
        params: { lang: lang },
        headers: { 'Authorization': `Bearer ${token}` }
      }
    );

    training.value = response.data;

    // Yeni signed URL-i tap
    if (currentLesson.value) {
      const module = training.value.modules.find(
        m => m.lessons.some(l => l.id === currentLesson.value.id)
      );
      const lesson = module?.lessons.find(l => l.id === currentLesson.value.id);
      if (lesson) {
        setVideoUrl(lesson);
      }
    }
  } catch (error) {
    console.error('Video URL yeniləmə xətası:', error);
    videoError.value = 'Video URL yenilənə bilmədi';
  } finally {
    videoLoading.value = false;
  }
};

// Training yüklə
const loadTraining = async () => {
  try {
    videoLoading.value = true;
    const token = localStorage.getItem('auth_token');
    const lang = currentLang.value || 'az';
    
    const response = await axios.get(
      `/api/v1/trainings/${trainingId.value}/detailed`,
      {
        params: { lang: lang },
        headers: { 'Authorization': `Bearer ${token}` }
      }
    );

    training.value = response.data;

    // İlk lesson-un video URL-ini təyin et
    const firstLesson = training.value.modules?.[0]?.lessons?.[0];
    if (firstLesson) {
      setVideoUrl(firstLesson);
    }
  } catch (error) {
    console.error('Training yükləmə xətası:', error);
    videoError.value = 'Training yüklənə bilmədi';
  } finally {
    videoLoading.value = false;
  }
};

// Lesson click
const onLessonClick = (lesson) => {
  setVideoUrl(lesson);
};

onMounted(() => {
  loadTraining();
});
</script>
```

---

## 🔍 Signed URL vs Adi URL

| Xüsusiyyət | Adi URL (`url`) | Signed URL (`signed_url`) |
|------------|-----------------|---------------------------|
| **Expiration** | Yoxdur | 2 saat sonra expire olur |
| **Security** | Aşağı | Yüksək (signature ilə) |
| **Video Tag** | İşləmir (auth header lazımdır) | İşləyir (signature kifayətdir) |
| **Blob URL** | Lazımdır | Lazım deyil |
| **İstifadə** | Fallback | Primary |

---

## ⚠️ Vacib Qeydlər

### 1. Signed URL Priority
```javascript
// ✅ DÜZGÜN - Signed URL-i əvvəlcə yoxla
if (videoFile.signed_url && isSignedUrlValid(videoFile)) {
  videoElement.src = videoFile.signed_url; // Signed URL istifadə et
} else {
  videoElement.src = videoFile.url; // Fallback URL istifadə et
}
```

### 2. Expiration Yoxlama
```javascript
// Signed URL-in expire olub-olmadığını həmişə yoxla
const expiresAt = new Date(videoFile.signed_url_expires_at);
const now = new Date();

if (expiresAt > now) {
  // Signed URL aktivdir
} else {
  // Signed URL expire olub, yenilə
}
```

### 3. Error Handling
```javascript
// Video error verəndə signed URL-i yenilə
const onVideoError = async () => {
  await refreshVideoUrl(); // Training detailed endpoint-dən yenidən al
};
```

---

## 📊 Response Field-ləri

### Video Faylı üçün:
- ✅ `signed_url` - Müvəqqəti signed URL (2 saat)
- ✅ `signed_url_expires_at` - Expiration tarixi
- ✅ `url` - Fallback protected URL
- ✅ `path` - Fayl path-i
- ✅ `filename` - Fayl adı
- ✅ `size` - Fayl ölçüsü
- ✅ `mime_type` - MIME type

### Şəkil və digər fayllar üçün:
- ✅ `url` - Protected URL
- ✅ `path` - Fayl path-i
- ❌ `signed_url` - Yoxdur (yalnız video üçün)

---

## 🎯 Xülasə

1. **Response-da `signed_url` field-i gəlir** (yalnız video faylları üçün)
2. **Signed URL-i birbaşa `<video>` tag-də istifadə et**
3. **Expiration yoxla** - 2 saat sonra expire olur
4. **Expire olduqda yenilə** - Training detailed endpoint-dən yenidən al
5. **Fallback URL** - Əgər signed URL yoxdursa, `url` field-ini istifadə et

**Üstünlüklər:**
- ✅ Blob URL yaratmağa ehtiyac yoxdur
- ✅ Birbaşa `<video>` tag-də istifadə oluna bilər
- ✅ Təhlükəsizdir (expire olur, signature ilə verify olunur)
- ✅ Temporary-dir (2 saat sonra expire olur)

