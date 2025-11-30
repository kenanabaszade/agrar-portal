# Video Player Problemi və Həlli - Azərbaycan Dili

## ❌ Problem

Frontend-də video göstərilmir və error verir:
```
Video error: MediaError
Video error message: Video faylı tapılmadı
```

**Səbəb:** `<video>` HTML elementi protected URL-ə birbaşa request göndərir, amma **authentication header göndərə bilmir**.

---

## ✅ Həll: Blob URL İstifadə Etmək

### Addım 1: Response-dan Video URL-ini Al

Training detailed response-dan video URL-ini çıxart:

```javascript
// Response-dan video URL-ini tapmaq
const training = response.data; // Training detailed response

// Module və lesson-u tap
const module = training.modules.find(m => m.id === 1); // Module ID 1
const lesson = module.lessons.find(l => l.id === 2); // Lesson ID 2

// Video faylını tap
const videoFile = lesson.media_files.find(f => f.type === 'video');

// Video URL (protected endpoint)
const videoUrl = videoFile.url;
// Nəticə: "http://localhost:8000/api/v1/modules/1/lessons/2/media/download?path=lessons%2F2%2Fvideo.mp4"
```

### Addım 2: Fetch ilə Video Yüklə və Blob URL Yarat

**❌ YANLIŞ (işləməyəcək):**
```vue
<template>
  <!-- Bu işləməyəcək çünki authentication header göndərilmir -->
  <video :src="videoUrl" controls />
</template>
```

**✅ DÜZGÜN:**
```vue
<template>
  <div>
    <video 
      v-if="videoBlobUrl" 
      :src="videoBlobUrl" 
      controls 
      preload="metadata"
      @error="handleVideoError"
    />
    <div v-if="loading">Video yüklənir...</div>
    <div v-if="error" class="error">{{ error }}</div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  videoUrl: String, // Protected endpoint URL
});

const videoBlobUrl = ref(null);
const loading = ref(false);
const error = ref(null);

const loadVideo = async () => {
  try {
    loading.value = true;
    error.value = null;

    // 1. Token al
    const token = localStorage.getItem('auth_token') || localStorage.getItem('token');
    if (!token) {
      throw new Error('Authentication token tapılmadı. Zəhmət olmasa yenidən login olun.');
    }

    // 2. Fetch ilə video yüklə (authentication header ilə)
    const response = await fetch(props.videoUrl, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });

    // 3. Error yoxla
    if (!response.ok) {
      if (response.status === 401) {
        throw new Error('Authentication uğursuz oldu. Zəhmət olmasa yenidən login olun.');
      } else if (response.status === 403) {
        throw new Error('Giriş qadağandır. Bu təlimə qeydiyyatdan keçməlisiniz.');
      } else if (response.status === 404) {
        throw new Error('Video faylı tapılmadı.');
      } else {
        throw new Error(`Server xətası: ${response.status}`);
      }
    }

    // 4. Response-u blob-a çevir
    const blob = await response.blob();

    // 5. Blob-dan Object URL yarat
    videoBlobUrl.value = URL.createObjectURL(blob);

  } catch (err) {
    error.value = err.message;
    console.error('Video yükləmə xətası:', err);
  } finally {
    loading.value = false;
  }
};

const handleVideoError = (e) => {
  console.error('Video element xətası:', e);
  error.value = 'Video oynatıla bilmədi. Zəhmət olmasa yenidən yoxlayın.';
};

// Component mount olduqda video yüklə
onMounted(() => {
  if (props.videoUrl) {
    loadVideo();
  }
});

// Component unmount olduqda memory təmizlə (vacib!)
onUnmounted(() => {
  if (videoBlobUrl.value) {
    URL.revokeObjectURL(videoBlobUrl.value);
  }
});
</script>
```

### Addım 3: İstifadə

```vue
<template>
  <div v-for="module in training.modules" :key="module.id">
    <div v-for="lesson in module.lessons" :key="lesson.id">
      <div v-for="mediaFile in lesson.media_files" :key="mediaFile.url">
        <!-- Video üçün -->
        <VideoPlayer 
          v-if="mediaFile.type === 'video'"
          :video-url="mediaFile.url" 
        />
        
        <!-- Şəkil üçün (şəkillər üçün blob URL lazım deyil) -->
        <img 
          v-else-if="mediaFile.type === 'image'"
          :src="mediaFile.url" 
          alt="Lesson image"
        />
      </div>
    </div>
  </div>
</template>
```

---

## 🔍 Niyə Blob URL Lazımdır?

1. **HTML5 `<video>` elementi authentication header göndərə bilmir**
   - `<video src="url">` birbaşa browser tərəfindən request göndərilir
   - Custom header əlavə edilə bilməz

2. **Protected endpoint authentication tələb edir**
   - `Authorization: Bearer {token}` header lazımdır
   - Token olmadan 401 Unauthorized alınır

3. **Blob URL həlli:**
   - `fetch()` ilə authenticated request göndərilir
   - Response blob-a çevrilir
   - Blob-dan Object URL yaradılır
   - Video element blob URL istifadə edir (artıq authentication lazım deyil)

---

## 📝 Axios ilə Nümunə

Əgər Axios istifadə edirsinizsə:

```javascript
import axios from 'axios';

const loadVideoWithAxios = async (videoUrl) => {
  try {
    const token = localStorage.getItem('auth_token');
    
    const response = await axios.get(videoUrl, {
      headers: {
        'Authorization': `Bearer ${token}`
      },
      responseType: 'blob' // Vacib: blob olaraq al
    });

    // Blob URL yarat
    const blobUrl = URL.createObjectURL(response.data);
    return blobUrl;
    
  } catch (error) {
    console.error('Video yükləmə xətası:', error);
    throw error;
  }
};
```

---

## ⚠️ Vacib Qeydlər

1. **Memory təmizləmə:** Component unmount olduqda `URL.revokeObjectURL()` çağırın
2. **Error handling:** Hər zaman error handling əlavə edin
3. **Loading state:** Video yüklənən zaman loading indicator göstərin
4. **Token yoxlama:** Token-in mövcud olduğunu yoxlayın

---

## 🐛 Debugging

Əgər hələ də problem varsa:

1. **Browser Console-da yoxlayın:**
   ```javascript
   // Video URL-i yoxlayın
   console.log('Video URL:', videoUrl);
   
   // Token-i yoxlayın
   console.log('Token:', localStorage.getItem('auth_token'));
   
   // Fetch request-i test edin
   fetch(videoUrl, {
     headers: { 'Authorization': `Bearer ${token}` }
   }).then(r => console.log('Response:', r));
   ```

2. **Network tab-da yoxlayın:**
   - Video request-inin göndərildiyini
   - Authentication header-inin əlavə olunduğunu
   - Response status-unun 200 olduğunu

3. **Backend log-larını yoxlayın:**
   - Request-in gəldiyini
   - Authentication-un uğurlu olduğunu
   - File-in tapıldığını

---

## 📚 Əlavə Məlumat

Daha ətraflı məlumat üçün `FRONTEND_VIDEO_PLAYER_GUIDE.md` faylına baxın.

