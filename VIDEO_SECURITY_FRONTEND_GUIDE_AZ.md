# Video Təhlükəsizliyi - Frontend Guide

## 🎯 Məqsəd

Bu sənəd frontend developer-lər üçün video fayllarının təhlükəsiz şəkildə göstərilməsi və istifadə edilməsi üçün təlimatları izah edir.

---

## 📋 Ümumi Məlumat

Backend-də video faylları **signed URL** ilə təqdim olunur. Bu URL-lər:
- ✅ **Müvəqqətidir** (2 saat sonra expire olur)
- ✅ **Təhlükəsizdir** (signature ilə verify olunur)
- ✅ **Browser-də birbaşa işləyir** (authentication header göndərməyə ehtiyac yoxdur)
- ✅ **Video player-də istifadə edilə bilər** (`<video>` tag-də)

---

## 🔑 Signed URL Alınması

### API Response Format

`/api/v1/trainings/{id}/detailed?lang=az` endpoint-indən alınan response-da hər video faylı üçün `signed_url` mövcuddur:

```json
{
  "modules": [
    {
      "lessons": [
        {
          "media_files": [
            {
              "type": "video",
              "url": "http://localhost:8000/api/v1/modules/1/lessons/2/media/download?path=...",
              "signed_url": "http://localhost:8000/api/v1/modules/1/lessons/2/media/download?path=...&signature=...&expires=...",
              "signed_url_expires_at": "2025-11-26T03:00:59+00:00",
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

### Signed URL Xüsusiyyətləri

- **`signed_url`**: Video-nu göstərmək üçün istifadə ediləcək URL
- **`signed_url_expires_at`**: URL-in expire olacağı tarix (ISO 8601 formatında)
- **`url`**: Protected endpoint URL-i (signed URL olmadan işləmir)

---

## 🎬 Video Player-də İstifadə

**⚠️ ƏHƏMİYYƏTLİ:** Video URL-ləri yalnız video player-də (`<video>` tag) istifadə edilməlidir. Browser-də birbaşa açılanda access deny edilir.

### Vue.js Nümunəsi

```vue
<template>
  <div class="video-player">
    <!-- ✅ DÜZGÜN: Video player-də istifadə -->
    <video
      v-if="videoUrl"
      ref="videoPlayer"
      :src="videoUrl"
      controls
      preload="metadata"
      @error="handleVideoError"
      @loadstart="handleLoadStart"
    >
      <source :src="videoUrl" type="video/mp4" />
      Video faylı yüklənə bilmədi.
    </video>
    
    <!-- ❌ YANLIŞ: Browser-də birbaşa açmaq -->
    <!-- <a :href="videoUrl">Video-nu aç</a> --> <!-- Bu işləməyəcək! -->
    
    <div v-if="error" class="error-message">
      {{ error }}
    </div>
    
    <div v-if="isExpired" class="expired-message">
      Video link-i müddəti bitib. Yeniləyin.
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onUnmounted, watch } from 'vue'

export default {
  name: 'VideoPlayer',
  props: {
    lesson: {
      type: Object,
      required: true
    }
  },
  setup(props) {
    const videoUrl = ref(null)
    const error = ref(null)
    const isExpired = ref(false)
    const videoPlayer = ref(null)
    const refreshTimer = ref(null)

    // Video faylını tap
    const findVideoFile = () => {
      if (!props.lesson.media_files || !Array.isArray(props.lesson.media_files)) {
        return null
      }
      
      return props.lesson.media_files.find(file => file.type === 'video')
    }

    // Signed URL-i yoxla və təyin et
    const setVideoUrl = () => {
      const videoFile = findVideoFile()
      
      if (!videoFile) {
        error.value = 'Video faylı tapılmadı'
        return
      }

      // Signed URL-i yoxla
      if (videoFile.signed_url) {
        // Expire tarixini yoxla
        const expiresAt = new Date(videoFile.signed_url_expires_at)
        const now = new Date()
        
        if (now >= expiresAt) {
          // URL expire olub, yenilənməlidir
          isExpired.value = true
          error.value = 'Video link-i müddəti bitib. Lütfən səhifəni yeniləyin.'
          return
        }
        
        // Signed URL-i istifadə et
        videoUrl.value = videoFile.signed_url
        error.value = null
        isExpired.value = false
        
        // Expire olacağı vaxtı hesabla və xəbərdarlıq göstər
        const timeUntilExpiry = expiresAt.getTime() - now.getTime()
        
        // 5 dəqiqə qalmış xəbərdarlıq göstər
        if (timeUntilExpiry < 5 * 60 * 1000) {
          console.warn('Video URL-i tezliklə expire olacaq:', expiresAt)
        }
      } else {
        error.value = 'Video URL-i mövcud deyil'
      }
    }

    // Video URL-i yenilə
    const refreshVideoUrl = async () => {
      try {
        // Training detallarını yenidən yüklə
        const response = await fetch(`/api/v1/trainings/${props.lesson.module.training_id}/detailed?lang=az`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}` // Optional
          }
        })
        
        if (!response.ok) {
          throw new Error('Failed to refresh video URL')
        }
        
        const data = await response.json()
        
        // Lesson-u tap və video URL-i yenilə
        const module = data.modules.find(m => m.id === props.lesson.module_id)
        if (module) {
          const lesson = module.lessons.find(l => l.id === props.lesson.id)
          if (lesson) {
            props.lesson.media_files = lesson.media_files
            setVideoUrl()
          }
        }
      } catch (err) {
        console.error('Error refreshing video URL:', err)
        error.value = 'Video URL-i yenilənə bilmədi'
      }
    }

    // Video error handler
    const handleVideoError = (event) => {
      console.error('Video error:', event)
      
      // Əgər 403 və ya 401 error varsa, URL expire olub ola bilər
      const video = event.target
      if (video.error && video.error.code === MediaError.MEDIA_ERR_SRC_NOT_SUPPORTED) {
        error.value = 'Video faylı yüklənə bilmədi. URL expire olub ola bilər.'
        isExpired.value = true
      } else {
        error.value = 'Video oynatıla bilmədi'
      }
    }

    const handleLoadStart = () => {
      error.value = null
    }

    // Component mount olduqda
    onMounted(() => {
      setVideoUrl()
      
      // Expire olacağı vaxtı yoxla və avtomatik yenilə
      const videoFile = findVideoFile()
      if (videoFile && videoFile.signed_url_expires_at) {
        const expiresAt = new Date(videoFile.signed_url_expires_at)
        const now = new Date()
        const timeUntilExpiry = expiresAt.getTime() - now.getTime()
        
        // 1 saat qalmış avtomatik yenilə
        if (timeUntilExpiry > 0 && timeUntilExpiry < 60 * 60 * 1000) {
          refreshTimer.value = setTimeout(() => {
            refreshVideoUrl()
          }, timeUntilExpiry - 5 * 60 * 1000) // 5 dəqiqə əvvəl yenilə
        }
      }
    })

    // Component unmount olduqda timer-i təmizlə
    onUnmounted(() => {
      if (refreshTimer.value) {
        clearTimeout(refreshTimer.value)
      }
    })

    // Lesson dəyişdikdə video URL-i yenilə
    watch(() => props.lesson, () => {
      setVideoUrl()
    }, { deep: true })

    return {
      videoUrl,
      error,
      isExpired,
      videoPlayer,
      handleVideoError,
      handleLoadStart,
      refreshVideoUrl
    }
  }
}
</script>

<style scoped>
.video-player {
  width: 100%;
  max-width: 800px;
  margin: 0 auto;
}

video {
  width: 100%;
  height: auto;
}

.error-message,
.expired-message {
  padding: 1rem;
  background-color: #fee;
  color: #c33;
  border-radius: 4px;
  margin-top: 1rem;
}
</style>
```

---

## 🔄 Signed URL Yenilənməsi

### Nə vaxt yeniləmək lazımdır?

1. **URL expire olub** (`signed_url_expires_at` tarixi keçib)
2. **Video yüklənmir** (403 və ya 401 error)
3. **1 saat qalmış** (preventive refresh)

### Necə yeniləmək?

```javascript
// Training detallarını yenidən yüklə
const refreshVideoUrl = async (trainingId) => {
  try {
    const response = await fetch(`/api/v1/trainings/${trainingId}/detailed?lang=az`, {
      headers: {
        'Authorization': `Bearer ${token}` // Optional, amma tövsiyə olunur
      }
    })
    
    const data = await response.json()
    
    // Yeni signed URL-i al
    const videoFile = data.modules
      .flatMap(m => m.lessons)
      .flatMap(l => l.media_files)
      .find(f => f.type === 'video' && f.id === videoId)
    
    if (videoFile && videoFile.signed_url) {
      return videoFile.signed_url
    }
    
    throw new Error('Signed URL not found')
  } catch (error) {
    console.error('Error refreshing video URL:', error)
    throw error
  }
}
```

---

## ⚠️ Xəbərdarlıqlar və Məhdudiyyətlər

### 1. Signed URL Expire Olur

- **Müddət**: 2 saat
- **Həll**: URL expire olmamışdan əvvəl yeniləyin
- **Yoxlama**: `signed_url_expires_at` tarixini yoxlayın

### 2. Rate Limiting

- **Limit**: 50 request/dəqiqə (IP üzrə)
- **Həll**: Çox tez-tez yeniləməyin
- **Qeyd**: Normal istifadədə problem yaratmır

### 3. Browser Direct Access Qadağandır

- **Qadağan**: Browser-də birbaşa URL açılanda access deny edilir
- **Səbəb**: Video-nun yüklənməsinin qarşısını almaq üçün
- **Həll**: Video-nu yalnız video player-də göstərin
- **Qeyd**: Video player avtomatik olaraq Range header göndərir və referer header var

### 4. Referer Header

- **Tələb**: Request frontend saytından gəlməlidir (video player-dən)
- **Həll**: Video player-də `<video>` tag istifadə edin
- **Qeyd**: Browser-də birbaşa açıla bilməz (təhlükəsizlik üçün)

### 5. CORS

- **Tələb**: Frontend URL `.env`-də `FRONTEND_URL` kimi təyin olunmalıdır
- **Həll**: Backend developer-lə əlaqə saxlayın

---

## 📝 Best Practices

### 1. Signed URL-i Cache Etməyin

```javascript
// ❌ YANLIŞ - Signed URL-i cache etməyin
localStorage.setItem('video_url', signedUrl)

// ✅ DÜZGÜN - Hər dəfə API-dən alın
const response = await fetch('/api/v1/trainings/1/detailed?lang=az')
const data = await response.json()
const signedUrl = data.modules[0].lessons[0].media_files[0].signed_url
```

### 2. Expire Tarixini Yoxlayın

```javascript
// ✅ Expire tarixini yoxlayın
const expiresAt = new Date(videoFile.signed_url_expires_at)
const now = new Date()

if (now >= expiresAt) {
  // URL expire olub, yeniləyin
  await refreshVideoUrl()
}
```

### 3. Error Handling

```javascript
// ✅ Error handling əlavə edin
video.addEventListener('error', (event) => {
  if (event.target.error.code === MediaError.MEDIA_ERR_SRC_NOT_SUPPORTED) {
    // URL expire olub ola bilər
    refreshVideoUrl()
  }
})
```

### 4. Loading State

```javascript
// ✅ Loading state göstərin
const [isLoading, setIsLoading] = useState(true)

useEffect(() => {
  setIsLoading(true)
  fetchVideoUrl().then(() => {
    setIsLoading(false)
  })
}, [])
```

---

## 🎯 Tam Nümunə (React)

```jsx
import React, { useState, useEffect, useRef } from 'react'

const VideoPlayer = ({ lesson, trainingId }) => {
  const [videoUrl, setVideoUrl] = useState(null)
  const [error, setError] = useState(null)
  const [isExpired, setIsExpired] = useState(false)
  const videoRef = useRef(null)
  const refreshTimerRef = useRef(null)

  // Video faylını tap
  const findVideoFile = () => {
    if (!lesson.media_files || !Array.isArray(lesson.media_files)) {
      return null
    }
    return lesson.media_files.find(file => file.type === 'video')
  }

  // Signed URL-i yoxla və təyin et
  const setVideoUrl = () => {
    const videoFile = findVideoFile()
    
    if (!videoFile) {
      setError('Video faylı tapılmadı')
      return
    }

    if (videoFile.signed_url) {
      const expiresAt = new Date(videoFile.signed_url_expires_at)
      const now = new Date()
      
      if (now >= expiresAt) {
        setIsExpired(true)
        setError('Video link-i müddəti bitib. Lütfən səhifəni yeniləyin.')
        return
      }
      
      setVideoUrl(videoFile.signed_url)
      setError(null)
      setIsExpired(false)
    } else {
      setError('Video URL-i mövcud deyil')
    }
  }

  // Video URL-i yenilə
  const refreshVideoUrl = async () => {
    try {
      const response = await fetch(`/api/v1/trainings/${trainingId}/detailed?lang=az`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      })
      
      if (!response.ok) {
        throw new Error('Failed to refresh video URL')
      }
      
      const data = await response.json()
      
      // Lesson-u tap və video URL-i yenilə
      const module = data.modules.find(m => m.id === lesson.module_id)
      if (module) {
        const updatedLesson = module.lessons.find(l => l.id === lesson.id)
        if (updatedLesson) {
          lesson.media_files = updatedLesson.media_files
          setVideoUrl()
        }
      }
    } catch (err) {
      console.error('Error refreshing video URL:', err)
      setError('Video URL-i yenilənə bilmədi')
    }
  }

  // Video error handler
  const handleVideoError = (event) => {
    console.error('Video error:', event)
    const video = event.target
    if (video.error && video.error.code === MediaError.MEDIA_ERR_SRC_NOT_SUPPORTED) {
      setError('Video faylı yüklənə bilmədi. URL expire olub ola bilər.')
      setIsExpired(true)
    } else {
      setError('Video oynatıla bilmədi')
    }
  }

  useEffect(() => {
    setVideoUrl()
    
    // Expire olacağı vaxtı yoxla və avtomatik yenilə
    const videoFile = findVideoFile()
    if (videoFile && videoFile.signed_url_expires_at) {
      const expiresAt = new Date(videoFile.signed_url_expires_at)
      const now = new Date()
      const timeUntilExpiry = expiresAt.getTime() - now.getTime()
      
      if (timeUntilExpiry > 0 && timeUntilExpiry < 60 * 60 * 1000) {
        refreshTimerRef.current = setTimeout(() => {
          refreshVideoUrl()
        }, timeUntilExpiry - 5 * 60 * 1000)
      }
    }

    return () => {
      if (refreshTimerRef.current) {
        clearTimeout(refreshTimerRef.current)
      }
    }
  }, [lesson])

  return (
    <div className="video-player">
      {videoUrl && !isExpired ? (
        <video
          ref={videoRef}
          src={videoUrl}
          controls
          preload="metadata"
          onError={handleVideoError}
          onLoadStart={() => setError(null)}
        >
          <source src={videoUrl} type="video/mp4" />
          Video faylı yüklənə bilmədi.
        </video>
      ) : (
        <div className="error-message">
          {error || 'Video yüklənir...'}
        </div>
      )}
      
      {isExpired && (
        <div className="expired-message">
          Video link-i müddəti bitib. 
          <button onClick={refreshVideoUrl}>Yenilə</button>
        </div>
      )}
    </div>
  )
}

export default VideoPlayer
```

---

## ✅ Xülasə

1. **Signed URL istifadə edin** - `signed_url` field-indən alın
2. **Expire tarixini yoxlayın** - `signed_url_expires_at` tarixini kontrol edin
3. **Error handling əlavə edin** - Video yüklənmədikdə yeniləyin
4. **Cache etməyin** - Signed URL-i cache etməyin, hər dəfə API-dən alın
5. **Avtomatik yeniləyin** - Expire olmamışdan əvvəl yeniləyin

Bu təlimatları izlədikdə video faylları təhlükəsiz və düzgün şəkildə göstəriləcək.

