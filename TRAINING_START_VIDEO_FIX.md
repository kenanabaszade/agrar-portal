# TrainingStart.vue Video Error Fix

## ❌ Problem

1. **Video URL düzgün təyin olunmayıb:**
   ```
   src: 'http://localhost:5174/training/1/start'
   ```
   Bu training start səhifəsinin URL-idir, video URL deyil!

2. **`currentLang is not defined` error:**
   ```javascript
   ReferenceError: currentLang is not defined
   at refreshVideoUrl (TrainingStart.vue:662:23)
   ```

---

## ✅ Həll

### 1. Video URL-ini Düzgün Təyin Etmək

**Problem:** Video element-də `src` training start URL-inə təyin olunub.

**Həll:** Signed URL-i düzgün təyin et:

```vue
<template>
  <div>
    <!-- Video player -->
    <video 
      v-if="currentVideoSignedUrl" 
      ref="videoPlayer"
      :src="currentVideoSignedUrl" 
      controls 
      preload="metadata"
      @error="onVideoError"
      @loadstart="onVideoLoadStart"
    />
    
    <div v-if="videoLoading">Video yüklənir...</div>
    <div v-if="videoError" class="error">{{ videoError }}</div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const trainingId = computed(() => parseInt(route.params.id));

// Video state
const currentVideoSignedUrl = ref(null);
const videoLoading = ref(false);
const videoError = ref(null);
const videoPlayer = ref(null);

// Current lesson və video file
const currentLesson = ref(null);
const currentVideoFile = ref(null);

// Language (vacib!)
const currentLang = ref('az'); // Default language

// Training data
const training = ref(null);

// Video URL-ini təyin et
const setVideoUrl = (lesson) => {
  if (!lesson || !lesson.media_files) {
    currentVideoSignedUrl.value = null;
    return;
  }

  // Video faylını tap
  const videoFile = lesson.media_files.find(f => f.type === 'video');
  
  if (!videoFile) {
    currentVideoSignedUrl.value = null;
    return;
  }

  currentVideoFile.value = videoFile;
  currentLesson.value = lesson;

  // Signed URL-i istifadə et (əgər varsa)
  if (videoFile.signed_url) {
    // Signed URL-in expire olub-olmadığını yoxla
    const expiresAt = videoFile.signed_url_expires_at 
      ? new Date(videoFile.signed_url_expires_at) 
      : null;
    const now = new Date();
    
    if (expiresAt && expiresAt > now) {
      // Signed URL hələ də aktivdir
      currentVideoSignedUrl.value = videoFile.signed_url;
      videoError.value = null;
      return;
    } else {
      // Signed URL expire olub
      console.warn('Signed URL expired, will refresh...');
    }
  }

  // Fallback: adi URL istifadə et
  if (videoFile.url) {
    currentVideoSignedUrl.value = videoFile.url;
  } else {
    currentVideoSignedUrl.value = null;
    videoError.value = 'Video URL tapılmadı';
  }
};

// Video error handler
const onVideoError = async (e) => {
  console.error('Video error:', e);
  console.error('Video error details:', {
    error: e.target.error,
    networkState: e.target.networkState,
    readyState: e.target.readyState,
    src: e.target.src,
    currentSrc: e.target.currentSrc
  });

  videoError.value = 'Video faylı tapılmadı';

  // Signed URL expire olubsa, yenilə
  if (currentVideoFile.value?.signed_url) {
    console.log('Video error detected, trying to refresh signed URL');
    await refreshVideoUrl();
  }
};

const onVideoLoadStart = () => {
  videoLoading.value = false;
  videoError.value = null;
};

// Video URL-i yenilə
const refreshVideoUrl = async () => {
  try {
    videoLoading.value = true;
    videoError.value = null;

    const token = localStorage.getItem('auth_token') || localStorage.getItem('token');
    if (!token) {
      throw new Error('Authentication token tapılmadı');
    }

    // currentLang-i düzgün təyin et (vacib!)
    const lang = currentLang.value || 'az'; // Default 'az'

    // Training detailed endpoint-dən yenidən response al
    const response = await axios.get(
      `/api/v1/trainings/${trainingId.value}/detailed`,
      {
        params: { lang: lang }, // currentLang istifadə et
        headers: {
          'Authorization': `Bearer ${token}`
        }
      }
    );

    training.value = response.data;

    // Yeni signed URL-i tap
    if (currentLesson.value) {
      const module = training.value.modules.find(
        m => m.lessons.some(l => l.id === currentLesson.value.id)
      );
      
      if (module) {
        const lesson = module.lessons.find(l => l.id === currentLesson.value.id);
        if (lesson) {
          setVideoUrl(lesson);
        }
      }
    }

  } catch (error) {
    console.error('Video URL yeniləmə xətası:', error);
    videoError.value = 'Video URL yenilənə bilmədi: ' + (error.message || 'Naməlum xəta');
  } finally {
    videoLoading.value = false;
  }
};

// Training yüklə
const loadTraining = async () => {
  try {
    videoLoading.value = true;
    const token = localStorage.getItem('auth_token') || localStorage.getItem('token');
    
    if (!token) {
      throw new Error('Authentication token tapılmadı');
    }

    // currentLang-i düzgün təyin et
    const lang = currentLang.value || 'az';

    const response = await axios.get(
      `/api/v1/trainings/${trainingId.value}/detailed`,
      {
        params: { lang: lang },
        headers: {
          'Authorization': `Bearer ${token}`
        }
      }
    );

    training.value = response.data;

    // İlk lesson-un video URL-ini təyin et (əgər varsa)
    if (training.value.modules && training.value.modules.length > 0) {
      const firstModule = training.value.modules[0];
      if (firstModule.lessons && firstModule.lessons.length > 0) {
        const firstLesson = firstModule.lessons[0];
        setVideoUrl(firstLesson);
      }
    }

  } catch (error) {
    console.error('Training yükləmə xətası:', error);
    videoError.value = 'Training yüklənə bilmədi: ' + (error.message || 'Naməlum xəta');
  } finally {
    videoLoading.value = false;
  }
};

// Lesson dəyişdikdə video URL-i yenilə
const onLessonChange = (lesson) => {
  setVideoUrl(lesson);
};

// Component mount olduqda training yüklə
onMounted(() => {
  loadTraining();
});

// Language dəyişdikdə training yenilə
watch(currentLang, () => {
  if (training.value) {
    loadTraining();
  }
});
</script>
```

---

## 🔍 Əsas Düzəlişlər

### 1. `currentLang` Təyin Etmək

**Problem:**
```javascript
// ❌ YANLIŞ - currentLang təyin olunmayıb
const refreshVideoUrl = async () => {
  const response = await axios.get(`/api/v1/trainings/${trainingId}/detailed`, {
    params: { lang: currentLang }, // currentLang is not defined!
  });
};
```

**Həll:**
```javascript
// ✅ DÜZGÜN - currentLang təyin olunub
const currentLang = ref('az'); // Default language

const refreshVideoUrl = async () => {
  const lang = currentLang.value || 'az'; // Default 'az'
  const response = await axios.get(`/api/v1/trainings/${trainingId}/detailed`, {
    params: { lang: lang },
  });
};
```

### 2. Video URL-ini Düzgün Təyin Etmək

**Problem:**
```vue
<!-- ❌ YANLIŞ - Training start URL-i verilir -->
<video :src="'http://localhost:5174/training/1/start'" />
```

**Həll:**
```vue
<!-- ✅ DÜZGÜN - Signed URL istifadə olunur -->
<video :src="currentVideoSignedUrl" />
```

```javascript
// Signed URL-i düzgün təyin et
const setVideoUrl = (lesson) => {
  const videoFile = lesson.media_files.find(f => f.type === 'video');
  if (videoFile?.signed_url) {
    currentVideoSignedUrl.value = videoFile.signed_url;
  }
};
```

---

## 📝 Tam Nümunə

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
    <div v-for="module in training?.modules" :key="module.id">
      <div v-for="lesson in module.lessons" :key="lesson.id">
        <button @click="onLessonClick(lesson)">
          {{ lesson.title.az }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
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
const currentVideoFile = ref(null);
const videoPlayer = ref(null);

// Language (VACIB!)
const currentLang = ref('az'); // Default 'az'

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

  currentVideoFile.value = videoFile;
  currentLesson.value = lesson;

  // Signed URL istifadə et
  if (videoFile.signed_url) {
    const expiresAt = videoFile.signed_url_expires_at 
      ? new Date(videoFile.signed_url_expires_at) 
      : null;
    
    if (!expiresAt || expiresAt > new Date()) {
      currentVideoSignedUrl.value = videoFile.signed_url;
      videoError.value = null;
      return;
    }
  }

  // Fallback
  currentVideoSignedUrl.value = videoFile.url || null;
};

// Video error
const onVideoError = async (e) => {
  console.error('Video error:', e);
  videoError.value = 'Video faylı tapılmadı';
  
  if (currentVideoFile.value?.signed_url) {
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
    
    // VACIB: currentLang istifadə et
    const lang = currentLang.value || 'az';
    
    const response = await axios.get(
      `/api/v1/trainings/${trainingId.value}/detailed`,
      {
        params: { lang: lang },
        headers: { 'Authorization': `Bearer ${token}` }
      }
    );

    training.value = response.data;

    if (currentLesson.value) {
      const module = training.value.modules.find(
        m => m.lessons.some(l => l.id === currentLesson.value.id)
      );
      const lesson = module?.lessons.find(l => l.id === currentLesson.value.id);
      if (lesson) setVideoUrl(lesson);
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
    const lang = currentLang.value || 'az'; // VACIB!
    
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
    if (firstLesson) setVideoUrl(firstLesson);
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

## ⚠️ Vacib Qeydlər

1. **`currentLang` təyin et:** Həmişə `currentLang` ref-ini təyin et və istifadə et
2. **Video URL düzgün təyin et:** Signed URL-i düzgün təyin et, training start URL-i deyil
3. **Error handling:** Video error verəndə signed URL-i yenilə
4. **Expiration yoxla:** Signed URL-in expire olub-olmadığını yoxla

---

## 🐛 Debugging

1. **Console-da yoxla:**
   ```javascript
   console.log('currentLang:', currentLang.value);
   console.log('currentVideoSignedUrl:', currentVideoSignedUrl.value);
   console.log('currentVideoFile:', currentVideoFile.value);
   ```

2. **Network tab-da yoxla:**
   - Video request-inin göndərildiyini
   - Signed URL-in düzgün olduğunu
   - Response status-unun 200 olduğunu

