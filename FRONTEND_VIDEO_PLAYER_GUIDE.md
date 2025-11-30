# Frontend Video Player - Etraflı İzahat

## ⚠️ Vacib Qeyd

**Video player error verirsə (`Video error: MediaError`), bu o deməkdir ki, `<video>` tag-i birbaşa protected URL-ə request göndərir və authentication header göndərə bilmir.**

**Həll:** Bu guide-də izah olunan **Blob URL metodunu** istifadə edin. `<video src="protected-url">` işləməyəcək!

---

## 📋 Sistemin İşləmə Prinsipi

### 1. API Response-dan Video URL-ini Almaq

Training detailed endpoint-dən (`GET /api/v1/trainings/{id}/detailed`) response alındıqda, lesson media faylları artıq **protected endpoint URL** formatında gəlir.

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
              "url": "http://localhost:8000/api/v1/modules/1/lessons/2/media/download?path=lessons%2F2%2Fvideo.mp4",
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

### 2. URL Format

**Köhnə format (artıq istifadə olunmur):**
```
/storage/lessons/2/video.mp4
```

**Yeni protected format:**
```
/api/v1/modules/1/lessons/2/media/download?path=lessons%2F2%2Fvideo.mp4
```

**URL komponentləri:**
- `/api/v1/modules/{module_id}/lessons/{lesson_id}/media/download` - Protected endpoint
- `?path=lessons%2F2%2Fvideo.mp4` - Fayl path-i (URL encoded)

### 3. Video URL-ini Response-dan Çıxartmaq

**Addım-addım proses:**

1. **Training detailed response-u al:**
   ```
   GET /api/v1/trainings/1/detailed
   ```

2. **Module və lesson-u tap:**
   - Response-da `modules` array-ində axtar
   - Hər module-un `lessons` array-i var
   - Hər lesson-un `media_files` array-i var

3. **Video faylını tap:**
   - `media_files` array-ində `type: "video"` olan faylı tap
   - Bu faylın `url` field-i video URL-dir

**Nümunə:**
```javascript
// Response-dan video URL-ini çıxartmaq
const training = response.data; // Training detailed response

// Module və lesson-u tap
const module = training.modules[0]; // İlk module
const lesson = module.lessons.find(l => l.id === 2); // Lesson ID 2

// Video faylını tap
const videoFile = lesson.media_files.find(f => f.type === 'video');

// Video URL
const videoUrl = videoFile.url;
// Nəticə: "http://localhost:8000/api/v1/modules/1/lessons/2/media/download?path=lessons%2F2%2Fvideo.mp4"
```

### 4. Protected Endpoint-in İşləmə Prinsipi

**Protected endpoint:**
```
GET /api/v1/modules/{module_id}/lessons/{lesson_id}/media/download?path={file_path}
```

**Tələblər:**
1. **Authentication**: Request header-də `Authorization: Bearer {token}` olmalıdır
2. **Authorization**: User training-ə qeydiyyatdan keçməlidir (və ya admin/trainer olmalıdır)
3. **Path Validation**: Path həmin lesson-a aid olmalıdır

**Response:**
- Uğurlu olduqda: Video faylı stream olunur (200 OK, video/mp4 content-type)
- Unauthorized olduqda: 401 Unauthorized
- Forbidden olduqda: 403 Forbidden (training-ə qeydiyyatdan keçməyib)

### 5. Video Player-də İstifadə

**Problem:** HTML5 `<video>` elementi protected endpoint-ə birbaşa request göndərə bilər, amma authentication header göndərə bilməz.

**Həll yolları:**

#### Seçim 1: Blob URL İstifadə Etmək (Tövsiyə olunur)

**Proses:**
1. Protected endpoint-ə authenticated request göndər
2. Response-u Blob kimi al
3. Blob-dan Object URL yarat
4. Video element-də Object URL istifadə et

**Addımlar:**
1. **Fetch ilə video yüklə:**
   - `fetch(videoUrl, { headers: { Authorization: Bearer token } })`
   - Response-u `blob()` kimi al
   - `URL.createObjectURL(blob)` ilə Object URL yarat

2. **Video element-də istifadə:**
   - `<video src={objectUrl} controls />`
   - Və ya `videoElement.src = objectUrl`

**Nümunə proses:**
```
1. videoUrl = "/api/v1/modules/1/lessons/2/media/download?path=lessons%2F2%2Fvideo.mp4"
2. fetch(videoUrl, { headers: { Authorization: "Bearer token" } })
3. response.blob() → Blob object
4. URL.createObjectURL(blob) → "blob:http://localhost:5174/abc-123-def"
5. <video src="blob:http://localhost:5174/abc-123-def" />
```

#### Seçim 2: Video Element-in src-də Birbaşa Protected URL (Token ilə)

**Problem:** HTML5 video element authentication header göndərə bilməz.

**Həll:** Əgər token URL-də query parameter kimi göndərilə bilsə (təhlükəsiz deyil) və ya cookie-based authentication istifadə olunarsa, birbaşa istifadə edilə bilər.

**Amma ən yaxşısı:** Blob URL istifadə etməkdir.

### 6. Frontend-də Tətbiq Prosesi

**Addım 1: Training Detailed Response-u Al**
```
GET /api/v1/trainings/1/detailed
Headers: Authorization: Bearer {token}
```

**Addım 2: Response-dan Video URL-ini Çıxart**
- `training.modules[0].lessons[0].media_files[0].url`
- Bu URL artıq protected endpoint-dir

**Addım 3: Video URL-ini Blob-a Çevir**
- `fetch(videoUrl, { headers: { Authorization: Bearer token } })`
- `response.blob()`
- `URL.createObjectURL(blob)`

**Addım 4: Video Player-də İstifadə Et**
- `<video src={blobUrl} controls />`

### 7. Video Streaming

**Protected endpoint video streaming dəstəkləyir:**
- `Accept-Ranges: bytes` header göndərilir
- Browser video player range request-ləri göndərə bilər (seek, pause, resume)
- Amma hər range request-də authentication lazımdır

**Problem:** HTML5 video element range request-ləri göndərəndə authentication header göndərə bilməz.

**Həll:** Blob URL istifadə etdikdə, video artıq memory-də yüklənib, ona görə range request lazım deyil.

**Alternativ:** Əgər böyük video-lar üçün streaming lazımdırsa, custom video player istifadə etmək lazımdır (Video.js, Plyr, və s.) ki, onlar authentication header göndərə bilsin.

### 8. Response-dan Video URL-ini Çıxartmaq (Detallı)

**Training Detailed Response Strukturu:**
```json
{
  "id": 1,
  "modules": [
    {
      "id": 1,
      "lessons": [
        {
          "id": 2,
          "media_files": [
            {
              "type": "video",
              "url": "http://localhost:8000/api/v1/modules/1/lessons/2/media/download?path=lessons%2F2%2Fvideo.mp4",
              "filename": "video.mp4",
              "mime_type": "video/mp4",
              "size": 17028948
            }
          ]
        }
      ]
    }
  ]
}
```

**Video URL-ini tapmaq:**
1. `training.modules` array-indən istədiyiniz module-u tapın
2. `module.lessons` array-indən istədiyiniz lesson-u tapın
3. `lesson.media_files` array-indən `type: "video"` olan faylı tapın
4. `videoFile.url` - bu video URL-dir

**Nümunə:**
```javascript
// Module ID 1, Lesson ID 2 üçün video tapmaq
const module = training.modules.find(m => m.id === 1);
const lesson = module.lessons.find(l => l.id === 2);
const videoFile = lesson.media_files.find(f => f.type === 'video');
const videoUrl = videoFile.url;
```

### 9. Video Player-də İstifadə (Detallı)

**Blob URL Metodu:**

**Addım 1: Video URL-ini Blob-a çevir**
```javascript
// 1. Token al
const token = localStorage.getItem('auth_token');

// 2. Video URL
const videoUrl = "http://localhost:8000/api/v1/modules/1/lessons/2/media/download?path=lessons%2F2%2Fvideo.mp4";

// 3. Fetch ilə video yüklə (authentication ilə)
const response = await fetch(videoUrl, {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});

// 4. Response-u blob-a çevir
const blob = await response.blob();

// 5. Blob-dan Object URL yarat
const blobUrl = URL.createObjectURL(blob);
// Nəticə: "blob:http://localhost:5174/abc-123-def-456"
```

**Addım 2: Video Element-də İstifadə**
```javascript
// Video element
<video 
  src={blobUrl} 
  controls 
  preload="metadata"
/>

// Və ya JavaScript ilə
videoElement.src = blobUrl;
```

**Addım 3: Memory Təmizləmə (Vacib!)**
```javascript
// Video izləmə bitdikdə və ya component unmount olduqda
URL.revokeObjectURL(blobUrl);
```

### 10. Video Streaming (Böyük Video-lar üçün)

**Problem:** Blob URL bütün videonu memory-ə yükləyir. Böyük video-lar üçün problem ola bilər.

**Həll: Custom Video Player (Video.js, Plyr, və s.)**

**Video.js nümunə:**
- Video.js custom source handler yarada bilər
- Hər request-də authentication header göndərə bilər
- Streaming dəstəkləyir

**Amma ən sadə həll:** Blob URL istifadə etmək (kiçik-orta video-lar üçün kifayətdir).

### 11. Error Handling

**Mümkün error-lar:**

1. **401 Unauthorized:**
   - Token yoxdur və ya expired-dir
   - Həll: Token yenilə və ya login et

2. **403 Forbidden:**
   - User training-ə qeydiyyatdan keçməyib
   - Həll: Training-ə qeydiyyatdan keç

3. **404 Not Found:**
   - Video faylı tapılmadı
   - Həll: Error mesajı göstər

4. **Network Error:**
   - Şəbəkə problemi
   - Həll: Retry və ya error mesajı

5. **Video Error (MediaError):**
   - Video element error verir: `Video error: MediaError`
   - **Səbəb:** `<video>` tag-i birbaşa protected URL-ə request göndərir və authentication header göndərə bilmir
   - **Həll:** Blob URL istifadə et (yuxarıda izah olunub)

**Error Handling Nümunəsi:**
```javascript
try {
  // 1. Token al
  const token = localStorage.getItem('auth_token');
  if (!token) {
    throw new Error('Authentication token not found');
  }

  // 2. Video URL
  const videoUrl = videoFile.url; // Response-dan gələn protected URL

  // 3. Fetch ilə video yüklə
  const response = await fetch(videoUrl, {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });

  // 4. Error yoxla
  if (!response.ok) {
    if (response.status === 401) {
      throw new Error('Authentication failed. Please login again.');
    } else if (response.status === 403) {
      throw new Error('Access denied. You must be registered for this training.');
    } else if (response.status === 404) {
      throw new Error('Video file not found.');
    } else {
      throw new Error(`Server error: ${response.status}`);
    }
  }

  // 5. Blob-a çevir
  const blob = await response.blob();

  // 6. Blob URL yarat
  const blobUrl = URL.createObjectURL(blob);

  // 7. Video element-də istifadə
  videoElement.src = blobUrl;

  // 8. Error listener
  videoElement.addEventListener('error', (e) => {
    console.error('Video error:', e);
    // Blob URL problem ola bilər, yenidən yüklə
  });

} catch (error) {
  console.error('Video loading error:', error);
  // Error mesajı göstər istifadəçiyə
}

### 12. Performance Optimizasiyası

**Lazy Loading:**
- Video yalnız istifadəçi play-ə basdıqda yüklə
- `preload="none"` istifadə et

**Caching:**
- Blob URL-ləri cache et (session storage və ya memory)
- Eyni video üçün yenidən fetch etmə

**Progressive Loading:**
- Video metadata-nı əvvəlcə yüklə
- Sonra full video yüklə

### 13. Praktik Nümunə (Vue.js)

**Vue Component Nümunəsi:**
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
import axios from 'axios';

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

    // Token al
    const token = localStorage.getItem('auth_token');
    if (!token) {
      throw new Error('Authentication token not found');
    }

    // Fetch ilə video yüklə
    const response = await fetch(props.videoUrl, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });

    if (!response.ok) {
      if (response.status === 401) {
        throw new Error('Authentication failed. Please login again.');
      } else if (response.status === 403) {
        throw new Error('Access denied. You must be registered for this training.');
      } else if (response.status === 404) {
        throw new Error('Video file not found.');
      } else {
        throw new Error(`Server error: ${response.status}`);
      }
    }

    // Blob-a çevir
    const blob = await response.blob();

    // Blob URL yarat
    videoBlobUrl.value = URL.createObjectURL(blob);

  } catch (err) {
    error.value = err.message;
    console.error('Video loading error:', err);
  } finally {
    loading.value = false;
  }
};

const handleVideoError = (e) => {
  console.error('Video element error:', e);
  error.value = 'Video oynatıla bilmədi. Zəhmət olmasa yenidən yoxlayın.';
};

// Component mount olduqda video yüklə
onMounted(() => {
  if (props.videoUrl) {
    loadVideo();
  }
});

// Component unmount olduqda memory təmizlə
onUnmounted(() => {
  if (videoBlobUrl.value) {
    URL.revokeObjectURL(videoBlobUrl.value);
  }
});
</script>
```

**İstifadə:**
```vue
<VideoPlayer 
  :video-url="lesson.media_files.find(f => f.type === 'video')?.url" 
/>
```

### 14. Xülasə

**Proses:**
1. Training detailed endpoint-dən response al
2. Response-dan video URL-ini çıxart (`lesson.media_files[].url`)
3. Protected endpoint-ə authenticated request göndər
4. Response-u blob-a çevir
5. Blob-dan Object URL yarat
6. Video element-də Object URL istifadə et
7. İzləmə bitdikdə Object URL-i revoke et

**Vacib qeydlər:**
- Video URL artıq protected endpoint-dir
- Hər request-də authentication token lazımdır
- **Blob URL istifadə etmək MƏCBURİdir** (çünki `<video>` tag-i authentication header göndərə bilməz)
- Memory təmizləməni unutma (revokeObjectURL)
- Error handling əlavə et (401, 403, 404, network errors)

**Əsas Problem və Həll:**
- **Problem:** `<video src="protected-url">` işləmir, çünki authentication header göndərilmir
- **Həll:** Fetch API ilə authenticated request göndər, blob-a çevir, blob URL yarat, video element-də blob URL istifadə et

