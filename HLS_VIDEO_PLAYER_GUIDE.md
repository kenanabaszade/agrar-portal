# HLS Video Player - Frontend Guide

## API Response Format

API'den gelen video dosyası için şu alanlar var:

```json
{
  "type": "video",
  "signed_url": "http://localhost:8000/api/v1/modules/2/lessons/5/media/download?path=...",
  "hls_master_playlist_url": "http://localhost:8000/api/v1/modules/2/lessons/5/media/download?path=lessons%2F5%2Fhls%2Fmaster.m3u8&signature=...&expires=...",
  "hls_variants": {
    "480p": {
      "playlist_url": "http://localhost:8000/api/v1/modules/2/lessons/5/media/download?path=lessons%2F5%2Fhls%2F480p.m3u8&signature=...&expires=..."
    },
    "720p": {
      "playlist_url": "..."
    },
    "1080p": {
      "playlist_url": "..."
    }
  }
}
```

## Frontend'de Kullanım

### ✅ DOĞRU: `hls_master_playlist_url` kullanın

Video player'da **MUTLAKA** `hls_master_playlist_url` alanını kullanın. Bu URL:
- Signed URL içerir (authentication gerektirmez)
- Master playlist'e işaret eder (.m3u8)
- Player otomatik olarak quality seçimi yapar

### ❌ YANLIŞ: `signed_url` veya `url` kullanmayın

- `signed_url`: MP4 dosyasına işaret eder, HLS değil
- `url`: Protected endpoint, authentication gerektirir

---

## React Örneği (hls.js ile)

### 1. hls.js Kurulumu

```bash
npm install hls.js
# veya
yarn add hls.js
```

### 2. Video Player Component (Loading Spinner ile)

```jsx
import { useEffect, useRef, useState } from 'react';
import Hls from 'hls.js';

const VideoPlayer = ({ videoFile }) => {
  const videoRef = useRef(null);
  const hlsRef = useRef(null);
  const [isLoading, setIsLoading] = useState(true);
  const [hasError, setHasError] = useState(false);
  const [isManifestLoaded, setIsManifestLoaded] = useState(false);

  useEffect(() => {
    const video = videoRef.current;
    if (!video || !videoFile) return;

    // HLS master playlist URL'ini al
    const hlsUrl = videoFile.hls_master_playlist_url;

    if (!hlsUrl) {
      console.error('HLS master playlist URL not found');
      setHasError(true);
      setIsLoading(false);
      return;
    }

    // Loading state'i başlat
    setIsLoading(true);
    setHasError(false);
    setIsManifestLoaded(false);

    // Video event listener'ları
    const handleCanPlay = () => {
      setIsLoading(false);
    };

    const handleWaiting = () => {
      setIsLoading(true);
    };

    const handlePlaying = () => {
      setIsLoading(false);
    };

    const handleError = () => {
      setIsLoading(false);
      setHasError(true);
    };

    video.addEventListener('canplay', handleCanPlay);
    video.addEventListener('waiting', handleWaiting);
    video.addEventListener('playing', handlePlaying);
    video.addEventListener('error', handleError);

    // Browser HLS desteğini kontrol et
    if (Hls.isSupported()) {
      // hls.js ile oynat
      const hls = new Hls({
        enableWorker: true,
        lowLatencyMode: false,
        // CORS sorunları varsa
        xhrSetup: function (xhr, url) {
          xhr.withCredentials = false; // CORS için
        }
      });

      hls.loadSource(hlsUrl);
      hls.attachMedia(video);

      hlsRef.current = hls;

      // HLS loading event'leri
      hls.on(Hls.Events.MANIFEST_PARSED, () => {
        // Manifest yüklendi, video oynatılmaya hazır
        setIsManifestLoaded(true);
        setIsLoading(false);
      });

      hls.on(Hls.Events.LEVEL_LOADED, () => {
        // Quality level yüklendi
        setIsManifestLoaded(true);
      });

      hls.on(Hls.Events.FRAG_LOADING, () => {
        // Fragment yükleniyor (buffering) - sadece manifest yüklendikten sonra göster
        setIsLoading(true);
      });

      hls.on(Hls.Events.FRAG_LOADED, () => {
        // Fragment yüklendi
        setIsLoading(false);
      });

      hls.on(Hls.Events.LEVEL_LOADED, () => {
        // Quality level yüklendi
        setIsManifestLoaded(true);
      });

      // Error handling
      hls.on(Hls.Events.ERROR, (event, data) => {
        if (data.fatal) {
          setIsLoading(false);
          setHasError(true);
          switch (data.type) {
            case Hls.ErrorTypes.NETWORK_ERROR:
              console.error('Network error, trying to recover...');
              hls.startLoad();
              break;
            case Hls.ErrorTypes.MEDIA_ERROR:
              console.error('Media error, trying to recover...');
              hls.recoverMediaError();
              break;
            default:
              console.error('Fatal error, cannot recover');
              hls.destroy();
              break;
          }
        }
      });

      // Cleanup
      return () => {
        video.removeEventListener('canplay', handleCanPlay);
        video.removeEventListener('waiting', handleWaiting);
        video.removeEventListener('playing', handlePlaying);
        video.removeEventListener('error', handleError);
        if (hlsRef.current) {
          hlsRef.current.destroy();
        }
      };
    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
      // Native HLS desteği (Safari, iOS)
      video.src = hlsUrl;
      
      // Safari için event listener'lar
      const handleLoadedMetadata = () => {
        setIsManifestLoaded(true);
        setIsLoading(false);
      };
      
      video.addEventListener('loadedmetadata', handleLoadedMetadata);
      
      return () => {
        video.removeEventListener('loadedmetadata', handleLoadedMetadata);
      };
    } else {
      console.error('HLS is not supported in this browser');
      setIsLoading(false);
      setHasError(true);
    }
  }, [videoFile]);

  return (
    <div 
      className="video-player-container" 
      style={{ 
        position: 'relative', 
        width: '100%',
        maxWidth: '100%',
        // ✅ Sabit aspect ratio (16:9) - Video yüklenene kadar da aynı boyutta kalır
        aspectRatio: '16 / 9',
        backgroundColor: '#000',
        borderRadius: '8px',
        overflow: 'hidden'
      }}
    >
      {/* Loading Spinner - Video yüklenene kadar göster */}
      {isLoading && (
        <div
          style={{
            position: 'absolute',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'center',
            justifyContent: 'center',
            zIndex: 10,
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            gap: '16px'
          }}
        >
          <div
            style={{
              width: '60px',
              height: '60px',
              border: '5px solid rgba(255, 255, 255, 0.2)',
              borderTop: '5px solid #3498db',
              borderRadius: '50%',
              animation: 'spin 1s linear infinite'
            }}
          />
          <span style={{ color: '#fff', fontSize: '16px', fontWeight: '500' }}>
            Video yüklənir...
          </span>
        </div>
      )}

      {/* Error Message */}
      {hasError && !isLoading && (
        <div
          style={{
            position: 'absolute',
            top: '50%',
            left: '50%',
            transform: 'translate(-50%, -50%)',
            zIndex: 10,
            padding: '20px',
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            borderRadius: '8px',
            color: '#fff',
            textAlign: 'center'
          }}
        >
          <p style={{ margin: 0 }}>Video yüklənə bilmədi</p>
          <button
            onClick={() => {
              setHasError(false);
              setIsLoading(true);
              // Video'yu yeniden yükle
              if (videoRef.current) {
                videoRef.current.load();
              }
            }}
            style={{
              marginTop: '10px',
              padding: '8px 16px',
              backgroundColor: '#3498db',
              color: '#fff',
              border: 'none',
              borderRadius: '4px',
              cursor: 'pointer'
            }}
          >
            Yenidən yoxla
          </button>
        </div>
      )}

      <video
        ref={videoRef}
        controls
        style={{
          width: '100%',
          height: '100%',
          objectFit: 'contain',
          backgroundColor: '#000',
          // ✅ Manifest yüklenene kadar gizle (flash önlemek için)
          visibility: isManifestLoaded ? 'visible' : 'hidden',
          opacity: isLoading ? 0.3 : 1,
          transition: 'opacity 0.3s ease'
        }}
        playsInline
        preload="metadata"
      >
        Your browser does not support the video tag.
      </video>

      {/* CSS Animation */}
      <style>{`
        @keyframes spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
        }
      `}</style>
    </div>
  );
};

export default VideoPlayer;
```

### 3. Kullanım

```jsx
import VideoPlayer from './VideoPlayer';

const LessonContent = ({ lesson }) => {
  const videoFile = lesson.media_files?.find(file => file.type === 'video');

  return (
    <div>
      <h2>{lesson.title}</h2>
      {videoFile && (
        <VideoPlayer videoFile={videoFile} />
      )}
    </div>
  );
};
```

---

## Vue.js Örneği (hls.js ile Loading Spinner ile)

### 1. Video Player Component

```vue
<template>
  <div class="video-player-container" style="position: relative; width: 100%;">
    <!-- Loading Spinner -->
    <div v-if="isLoading" class="loading-overlay">
      <div class="spinner"></div>
      <span class="loading-text">Video yüklənir...</span>
    </div>

    <!-- Error Message -->
    <div v-if="hasError && !isLoading" class="error-overlay">
      <p>Video yüklənə bilmədi</p>
      <button @click="retry" class="retry-button">Yenidən yoxla</button>
    </div>

    <video
      ref="videoElement"
      controls
      :style="{
        width: '100%',
        maxWidth: '100%',
        backgroundColor: '#000',
        opacity: isLoading ? 0.5 : 1,
        transition: 'opacity 0.3s ease'
      }"
      playsinline
      @canplay="onCanPlay"
      @waiting="onWaiting"
      @playing="onPlaying"
      @error="onError"
    >
      Your browser does not support the video tag.
    </video>
  </div>
</template>

<script>
import Hls from 'hls.js';

export default {
  name: 'VideoPlayer',
  props: {
    videoFile: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      hls: null,
      isLoading: true,
      hasError: false
    };
  },
  mounted() {
    this.initPlayer();
  },
  beforeUnmount() {
    this.destroyPlayer();
  },
  watch: {
    videoFile: {
      handler() {
        this.destroyPlayer();
        this.initPlayer();
      },
      deep: true
    }
  },
  methods: {
    initPlayer() {
      const video = this.$refs.videoElement;
      if (!video || !this.videoFile) return;

      const hlsUrl = this.videoFile.hls_master_playlist_url;

      if (!hlsUrl) {
        console.error('HLS master playlist URL not found');
        this.hasError = true;
        this.isLoading = false;
        return;
      }

      // Loading state'i başlat
      this.isLoading = true;
      this.hasError = false;

      if (Hls.isSupported()) {
        this.hls = new Hls({
          enableWorker: true,
          lowLatencyMode: false
        });

        this.hls.loadSource(hlsUrl);
        this.hls.attachMedia(video);

        // HLS loading event'leri
        this.hls.on(Hls.Events.MANIFEST_PARSED, () => {
          this.isLoading = false;
        });

        this.hls.on(Hls.Events.FRAG_LOADING, () => {
          this.isLoading = true;
        });

        this.hls.on(Hls.Events.FRAG_LOADED, () => {
          this.isLoading = false;
        });

        this.hls.on(Hls.Events.ERROR, (event, data) => {
          if (data.fatal) {
            this.isLoading = false;
            this.hasError = true;
            switch (data.type) {
              case Hls.ErrorTypes.NETWORK_ERROR:
                this.hls.startLoad();
                break;
              case Hls.ErrorTypes.MEDIA_ERROR:
                this.hls.recoverMediaError();
                break;
              default:
                this.hls.destroy();
                break;
            }
          }
        });
      } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
        // Native HLS (Safari)
        video.src = hlsUrl;
      } else {
        this.isLoading = false;
        this.hasError = true;
      }
    },
    destroyPlayer() {
      if (this.hls) {
        this.hls.destroy();
        this.hls = null;
      }
    },
    onCanPlay() {
      this.isLoading = false;
    },
    onWaiting() {
      this.isLoading = true;
    },
    onPlaying() {
      this.isLoading = false;
    },
    onError() {
      this.isLoading = false;
      this.hasError = true;
    },
    retry() {
      this.hasError = false;
      this.isLoading = true;
      const video = this.$refs.videoElement;
      if (video) {
        video.load();
      }
    }
  }
};
</script>

<style scoped>
.video-player-container {
  position: relative;
  width: 100%;
}

.loading-overlay {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 10;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #3498db;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.loading-text {
  color: #fff;
  font-size: 14px;
  font-weight: 500;
}

.error-overlay {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 10;
  padding: 20px;
  background-color: rgba(0, 0, 0, 0.8);
  border-radius: 8px;
  color: #fff;
  text-align: center;
}

.error-overlay p {
  margin: 0 0 10px 0;
}

.retry-button {
  padding: 8px 16px;
  background-color: #3498db;
  color: #fff;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.retry-button:hover {
  background-color: #2980b9;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>
```

---

## Video.js ile (Alternatif)

### Kurulum

```bash
npm install video.js videojs-contrib-quality-levels videojs-hls-quality-selector
```

### Kullanım

```jsx
import videojs from 'video.js';
import 'video.js/dist/video-js.css';
import 'videojs-contrib-quality-levels';
import 'videojs-hls-quality-selector';

const VideoPlayer = ({ videoFile }) => {
  const videoRef = useRef(null);
  const playerRef = useRef(null);

  useEffect(() => {
    const hlsUrl = videoFile?.hls_master_playlist_url;
    if (!hlsUrl) return;

    const player = videojs(videoRef.current, {
      controls: true,
      responsive: true,
      fluid: true,
      sources: [{
        src: hlsUrl,
        type: 'application/x-mpegURL'
      }]
    });

    player.ready(() => {
      player.hlsQualitySelector({
        displayCurrentQuality: true,
      });
    });

    playerRef.current = player;

    return () => {
      if (playerRef.current) {
        playerRef.current.dispose();
      }
    };
  }, [videoFile]);

  return (
    <div data-vjs-player>
      <video ref={videoRef} className="video-js vjs-big-play-centered" />
    </div>
  );
};
```

---

## Plyr.js ile (Alternatif)

### Kurulum

```bash
npm install plyr
```

### Kullanım

```jsx
import Plyr from 'plyr';
import 'plyr/dist/plyr.css';
import { useEffect, useRef } from 'react';

const VideoPlayer = ({ videoFile }) => {
  const videoRef = useRef(null);
  const playerRef = useRef(null);

  useEffect(() => {
    const hlsUrl = videoFile?.hls_master_playlist_url;
    if (!hlsUrl) return;

    const player = new Plyr(videoRef.current, {
      controls: ['play', 'progress', 'current-time', 'mute', 'volume', 'settings', 'fullscreen'],
      settings: ['quality', 'speed'],
      quality: {
        default: 720,
        options: [480, 720, 1080],
        forced: true,
        onChange: (quality) => {
          // Quality değişimi için hls variants kullanılabilir
        }
      }
    });

    // HLS source ekle
    if (videoRef.current) {
      videoRef.current.src = hlsUrl;
    }

    playerRef.current = player;

    return () => {
      if (playerRef.current) {
        playerRef.current.destroy();
      }
    };
  }, [videoFile]);

  return (
    <div className="plyr__video-outer">
      <video ref={videoRef} playsInline />
    </div>
  );
};
```

---

## Basit Loading Spinner Component (Reusable)

Eğer birden fazla yerde kullanmak istiyorsanız, ayrı bir component oluşturabilirsiniz:

### React Spinner Component

```jsx
// components/LoadingSpinner.jsx
const LoadingSpinner = ({ message = 'Yüklənir...', size = 50 }) => {
  return (
    <div
      style={{
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        gap: '12px',
        padding: '20px'
      }}
    >
      <div
        style={{
          width: size,
          height: size,
          border: `4px solid #f3f3f3`,
          borderTop: `4px solid #3498db`,
          borderRadius: '50%',
          animation: 'spin 1s linear infinite'
        }}
      />
      <span style={{ color: '#666', fontSize: '14px' }}>{message}</span>
      <style>{`
        @keyframes spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
        }
      `}</style>
    </div>
  );
};

export default LoadingSpinner;
```

### Vue Spinner Component

```vue
<!-- components/LoadingSpinner.vue -->
<template>
  <div class="loading-spinner">
    <div class="spinner" :style="{ width: size + 'px', height: size + 'px' }"></div>
    <span class="message">{{ message }}</span>
  </div>
</template>

<script>
export default {
  name: 'LoadingSpinner',
  props: {
    message: {
      type: String,
      default: 'Yüklənir...'
    },
    size: {
      type: Number,
      default: 50
    }
  }
};
</script>

<style scoped>
.loading-spinner {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 20px;
}

.spinner {
  border: 4px solid #f3f3f3;
  border-top: 4px solid #3498db;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.message {
  color: #666;
  font-size: 14px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>
```

### Kullanım

```jsx
// React
import LoadingSpinner from './components/LoadingSpinner';

{isLoading && (
  <div style={{ position: 'absolute', top: '50%', left: '50%', transform: 'translate(-50%, -50%)' }}>
    <LoadingSpinner message="Video yüklənir..." />
  </div>
)}
```

```vue
<!-- Vue -->
<template>
  <div v-if="isLoading" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
    <LoadingSpinner message="Video yüklənir..." />
  </div>
</template>
```

---

## Önemli Notlar

### 1. URL Formatı

✅ **DOĞRU:**
```
hls_master_playlist_url: "http://localhost:8000/api/v1/modules/2/lessons/5/media/download?path=lessons%2F5%2Fhls%2Fmaster.m3u8&signature=...&expires=..."
```

❌ **YANLIŞ:**
```
signed_url: "http://localhost:8000/api/v1/modules/2/lessons/5/media/download?path=lessons%2F5%2Fvideo.mp4&signature=..."
```

### 2. CORS Ayarları

Eğer CORS sorunları yaşıyorsanız, backend'de CORS ayarlarını kontrol edin:

```php
// config/cors.php veya middleware
'Access-Control-Allow-Origin' => '*',
'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
'Access-Control-Allow-Headers' => 'Content-Type, Authorization, Range',
```

### 3. Range Header

HLS player'lar otomatik olarak `Range` header gönderir. Backend bunu destekliyor.

### 4. Quality Selection

Player otomatik olarak `hls_variants` içindeki quality seçeneklerini kullanır:
- 480p
- 720p  
- 1080p

### 5. Signed URL Expiry

Signed URL'ler 2 saat geçerlidir. Eğer video uzunsa, yeniden yükleme gerekebilir.

---

## Test Etmek İçin

1. API'den training detayını alın:
```javascript
const response = await fetch('http://localhost:8000/api/v1/trainings/2/detailed?lang=az');
const data = await response.json();
```

2. Video dosyasını bulun:
```javascript
const videoFile = data.modules[0].lessons[0].media_files.find(f => f.type === 'video');
```

3. HLS URL'ini kontrol edin:
```javascript
console.log('HLS URL:', videoFile.hls_master_playlist_url);
```

4. Player'a verin:
```jsx
<VideoPlayer videoFile={videoFile} />
```

---

## Sorun Giderme

### Problem: Video oynatılmıyor

**Çözüm:**
- `hls_master_playlist_url` kullandığınızdan emin olun
- Browser console'da hata mesajlarını kontrol edin
- CORS ayarlarını kontrol edin
- Signed URL'in expire olmadığından emin olun

### Problem: Quality seçimi çalışmıyor

**Çözüm:**
- `hls_variants` alanının dolu olduğundan emin olun
- Player'ın quality selector plugin'ini yüklediğinizden emin olun

### Problem: Safari'de çalışmıyor

**Çözüm:**
- Safari native HLS desteği kullanır, hls.js gerekmez
- `video.canPlayType('application/vnd.apple.mpegurl')` kontrolü yapın

---

# Auto-Navigate to Next Lesson (Otomatik Sonraki Lesson'a Geçiş)

## Özellik: Önceki Lesson Completed İse Sonraki Lesson'u Otomatik Aç

Eğer kullanıcı bir lesson'u tamamladıysa, bir sonraki lesson'u otomatik olarak açmak için aşağıdaki implementasyonu kullanabilirsiniz.

## API Response Format

Training detailed endpoint'inden gelen response'da `user_progress` içinde `next_lesson` bilgisi var:

```json
{
  "user_progress": {
    "is_completed": false,
    "last_lesson": {
      "id": 4,
      "title": "Lesson 1",
      "module_id": 2,
      "module_title": "Module 1",
      "status": "completed"
    },
    "next_lesson": {
      "id": 5,
      "title": "Lesson 2",
      "module_id": 2,
      "module_title": "Module 1"
    },
    "completed_lessons": 1,
    "total_lessons": 3,
    "completion_percentage": 33.33
  }
}
```

## React Implementation

### Training Detail Component

```jsx
import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';

const TrainingDetail = () => {
  const { trainingId } = useParams();
  const navigate = useNavigate();
  const [training, setTraining] = useState(null);
  const [currentLessonId, setCurrentLessonId] = useState(null);

  useEffect(() => {
    loadTraining();
  }, [trainingId]);

  const loadTraining = async () => {
    try {
      const response = await fetch(`/api/v1/trainings/${trainingId}/detailed?lang=az`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      });
      const data = await response.json();
      setTraining(data);

      // Eğer önceki lesson completed ise ve next_lesson varsa, otomatik aç
      if (data.user_progress?.last_lesson?.status === 'completed' && data.user_progress?.next_lesson) {
        const nextLessonId = data.user_progress.next_lesson.id;
        setCurrentLessonId(nextLessonId);
        // URL'i güncelle (opsiyonel)
        navigate(`/trainings/${trainingId}/lessons/${nextLessonId}`, { replace: true });
      } else if (data.user_progress?.last_lesson && data.user_progress.last_lesson.status !== 'completed') {
        // Eğer son lesson completed değilse, onu aç
        setCurrentLessonId(data.user_progress.last_lesson.id);
      } else if (data.user_progress?.next_lesson) {
        // İlk lesson'u aç
        setCurrentLessonId(data.user_progress.next_lesson.id);
      }
    } catch (error) {
      console.error('Error loading training:', error);
    }
  };

  const handleLessonComplete = async (lessonId) => {
    // Lesson'u completed olarak işaretle
    try {
      await fetch(`/api/v1/lessons/${lessonId}/complete`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        }
      });

      // Training'i yeniden yükle ve sonraki lesson'u aç
      await loadTraining();
    } catch (error) {
      console.error('Error completing lesson:', error);
    }
  };

  if (!training) {
    return <div>Yüklənir...</div>;
  }

  return (
    <div>
      <h1>{training.title}</h1>
      {currentLessonId && (
        <LessonViewer
          lessonId={currentLessonId}
          trainingId={trainingId}
          onComplete={handleLessonComplete}
        />
      )}
    </div>
  );
};
```

### Lesson Viewer Component (Auto-Navigate ile)

```jsx
import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';

const LessonViewer = ({ lessonId, trainingId, onComplete }) => {
  const navigate = useNavigate();
  const [lesson, setLesson] = useState(null);
  const [training, setTraining] = useState(null);
  const [isCompleted, setIsCompleted] = useState(false);

  useEffect(() => {
    loadLesson();
    loadTraining();
  }, [lessonId]);

  const loadLesson = async () => {
    try {
      const response = await fetch(`/api/v1/modules/${moduleId}/lessons/${lessonId}`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      });
      const data = await response.json();
      setLesson(data);
      
      // Lesson'un completed olup olmadığını kontrol et
      setIsCompleted(data.progress?.status === 'completed');
    } catch (error) {
      console.error('Error loading lesson:', error);
    }
  };

  const loadTraining = async () => {
    try {
      const response = await fetch(`/api/v1/trainings/${trainingId}/detailed?lang=az`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      });
      const data = await response.json();
      setTraining(data);
    } catch (error) {
      console.error('Error loading training:', error);
    }
  };

  const handleComplete = async () => {
    if (onComplete) {
      await onComplete(lessonId);
    }

    // Training'i yeniden yükle
    await loadTraining();

    // Eğer next_lesson varsa, otomatik olarak aç
    if (training?.user_progress?.next_lesson) {
      const nextLessonId = training.user_progress.next_lesson.id;
      navigate(`/trainings/${trainingId}/lessons/${nextLessonId}`);
    }
  };

  return (
    <div>
      {lesson && (
        <>
          <h2>{lesson.title}</h2>
          {/* Video player veya lesson content */}
          {lesson.media_files?.map((file, index) => (
            file.type === 'video' && (
              <VideoPlayer key={index} videoFile={file} />
            )
          ))}
          
          {!isCompleted && (
            <button onClick={handleComplete}>
              Lesson'u Tamamla
            </button>
          )}
        </>
      )}
    </div>
  );
};
```

## Vue.js Implementation

### Training Detail Component

```vue
<template>
  <div>
    <h1>{{ training?.title }}</h1>
    <LessonViewer
      v-if="currentLessonId"
      :lesson-id="currentLessonId"
      :training-id="trainingId"
      @complete="handleLessonComplete"
    />
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import LessonViewer from './LessonViewer.vue';

export default {
  name: 'TrainingDetail',
  components: {
    LessonViewer
  },
  setup() {
    const route = useRoute();
    const router = useRouter();
    const trainingId = route.params.trainingId;
    const training = ref(null);
    const currentLessonId = ref(null);

    const loadTraining = async () => {
      try {
        const response = await fetch(`/api/v1/trainings/${trainingId}/detailed?lang=az`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        });
        const data = await response.json();
        training.value = data;

        // Eğer önceki lesson completed ise ve next_lesson varsa, otomatik aç
        if (data.user_progress?.last_lesson?.status === 'completed' && data.user_progress?.next_lesson) {
          currentLessonId.value = data.user_progress.next_lesson.id;
          router.replace(`/trainings/${trainingId}/lessons/${currentLessonId.value}`);
        } else if (data.user_progress?.last_lesson && data.user_progress.last_lesson.status !== 'completed') {
          currentLessonId.value = data.user_progress.last_lesson.id;
        } else if (data.user_progress?.next_lesson) {
          currentLessonId.value = data.user_progress.next_lesson.id;
        }
      } catch (error) {
        console.error('Error loading training:', error);
      }
    };

    const handleLessonComplete = async (lessonId) => {
      try {
        await fetch(`/api/v1/lessons/${lessonId}/complete`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          }
        });

        await loadTraining();
      } catch (error) {
        console.error('Error completing lesson:', error);
      }
    };

    onMounted(() => {
      loadTraining();
    });

    return {
      training,
      currentLessonId,
      trainingId,
      handleLessonComplete
    };
  }
};
</script>
```

## Basit Kullanım Örneği (Sayfa Yüklendiğinde Otomatik Aç)

### React - Training Start Component

```jsx
import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';

const TrainingStart = () => {
  const { trainingId } = useParams();
  const navigate = useNavigate();
  const [training, setTraining] = useState(null);
  const [loading, setLoading] = useState(true);
  const [targetLessonId, setTargetLessonId] = useState(null);

  useEffect(() => {
    loadTraining();
  }, [trainingId]);

  const loadTraining = async () => {
    try {
      setLoading(true);
      const response = await fetch(`/api/v1/trainings/${trainingId}/detailed?lang=az`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      });
      const data = await response.json();
      setTraining(data);

      // ✅ ÖNEMLİ: Doğru lesson'u belirle (flash önlemek için)
      let lessonIdToOpen = null;

      if (data.user_progress?.last_lesson?.status === 'completed' && data.user_progress?.next_lesson) {
        // Eğer önceki lesson completed ise, sonraki lesson'u aç
        lessonIdToOpen = data.user_progress.next_lesson.id;
      } else if (data.user_progress?.last_lesson && data.user_progress.last_lesson.status !== 'completed') {
        // Eğer son lesson completed değilse, onu aç
        lessonIdToOpen = data.user_progress.last_lesson.id;
      } else if (data.user_progress?.next_lesson) {
        // İlk lesson'u aç
        lessonIdToOpen = data.user_progress.next_lesson.id;
      } else if (data.modules?.[0]?.lessons?.[0]) {
        // Fallback: İlk module'un ilk lesson'unu aç
        lessonIdToOpen = data.modules[0].lessons[0].id;
      }

      // ✅ ÖNEMLİ: Lesson ID'yi set et ve navigate et (flash önlemek için)
      if (lessonIdToOpen) {
        setTargetLessonId(lessonIdToOpen);
        // replace: true kullanarak history'yi temizle
        navigate(`/trainings/${trainingId}/lessons/${lessonIdToOpen}`, { replace: true });
      }
    } catch (error) {
      console.error('Error loading training:', error);
    } finally {
      setLoading(false);
    }
  };

  // ✅ ÖNEMLİ: Loading sırasında hiçbir şey gösterme (flash önlemek için)
  // veya sadece loading spinner göster
  if (loading || !targetLessonId) {
    return (
      <div style={{ 
        display: 'flex', 
        justifyContent: 'center', 
        alignItems: 'center', 
        height: '100vh',
        backgroundColor: '#f5f5f5'
      }}>
        <div style={{ textAlign: 'center' }}>
          <div
            style={{
              width: '50px',
              height: '50px',
              border: '4px solid #f3f3f3',
              borderTop: '4px solid #3498db',
              borderRadius: '50%',
              animation: 'spin 1s linear infinite',
              margin: '0 auto 16px'
            }}
          />
          <div>Yüklənir...</div>
        </div>
        <style>{`
          @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
          }
        `}</style>
      </div>
    );
  }

  return (
    <div>
      {/* Training bilgileri gösterilebilir */}
      <h1>{training?.title?.az || training?.title}</h1>
      {/* Lesson viewer component'i burada render edilecek */}
      {/* targetLessonId ile doğru lesson'u göster */}
    </div>
  );
};

export default TrainingStart;
```

### Vue.js - Training Start Component

```vue
<template>
  <div>
    <div v-if="loading" class="loading">
      Yüklənir...
    </div>
    <div v-else>
      <h1>{{ training?.title?.az || training?.title }}</h1>
      <!-- Lesson viewer burada render edilecek -->
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';

export default {
  name: 'TrainingStart',
  setup() {
    const route = useRoute();
    const router = useRouter();
    const trainingId = route.params.trainingId;
    const training = ref(null);
    const loading = ref(true);

    const loadTraining = async () => {
      try {
        loading.value = true;
        const response = await fetch(`/api/v1/trainings/${trainingId}/detailed?lang=az`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        });
        const data = await response.json();
        training.value = data;

        // ✅ ÖNEMLİ: Eğer önceki lesson completed ise, sonraki lesson'u otomatik aç
        if (data.user_progress?.last_lesson?.status === 'completed' && data.user_progress?.next_lesson) {
          const nextLessonId = data.user_progress.next_lesson.id;
          router.replace(`/trainings/${trainingId}/lessons/${nextLessonId}`);
        } else if (data.user_progress?.last_lesson && data.user_progress.last_lesson.status !== 'completed') {
          router.replace(`/trainings/${trainingId}/lessons/${data.user_progress.last_lesson.id}`);
        } else if (data.user_progress?.next_lesson) {
          router.replace(`/trainings/${trainingId}/lessons/${data.user_progress.next_lesson.id}`);
        } else if (data.modules?.[0]?.lessons?.[0]) {
          router.replace(`/trainings/${trainingId}/lessons/${data.modules[0].lessons[0].id}`);
        }
      } catch (error) {
        console.error('Error loading training:', error);
      } finally {
        loading.value = false;
      }
    };

    onMounted(() => {
      loadTraining();
    });

    return {
      training,
      loading
    };
  }
};
</script>

<style scoped>
.loading {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
}
</style>
```

## API Response Örneği (Sizin Durumunuz)

Sizin API response'unuzda:

```json
{
  "user_progress": {
    "last_lesson": {
      "id": 4,
      "status": "completed"  // ✅ Completed!
    },
    "next_lesson": {
      "id": 5,  // ✅ Bu lesson'u otomatik açmalısınız
      "title": "sQSq"
    }
  }
}
```

**Kod Mantığı:**
```javascript
// Eğer last_lesson.status === 'completed' VE next_lesson varsa
if (data.user_progress?.last_lesson?.status === 'completed' && data.user_progress?.next_lesson) {
  // Sonraki lesson'u aç
  navigate(`/trainings/${trainingId}/lessons/${data.user_progress.next_lesson.id}`);
}
```

## Önemli Notlar

1. **Auto-Navigation Logic:**
   - ✅ Eğer `last_lesson.status === 'completed'` ve `next_lesson` varsa → Sonraki lesson'u aç
   - Eğer `last_lesson.status !== 'completed'` → Son lesson'u devam ettir
   - Eğer hiç progress yoksa → İlk lesson'u aç

2. **URL Management:**
   - `replace: true` kullanarak browser history'yi temiz tutun
   - Kullanıcı geri butonuna bastığında training list'e dönsün

3. **User Experience:**
   - Lesson tamamlandığında kısa bir mesaj gösterin
   - Sonraki lesson'a geçiş animasyonu ekleyebilirsiniz
   - Loading state gösterin

4. **Edge Cases:**
   - Eğer son lesson completed ise → Training completed mesajı göster
   - Eğer next_lesson yoksa → Training'i tamamla

---

## 🐛 Bug Fixes - Video Player Sorunları Düzeltildi

### Sorun 1: Video yüklenene kadar player küçük görünüyor

**Çözüm:** Sabit aspect ratio (16:9) eklendi

```jsx
<div style={{ 
  aspectRatio: '16 / 9',  // ✅ Sabit boyut
  backgroundColor: '#000',
  borderRadius: '8px',
  overflow: 'hidden'
}}>
```

### Sorun 2: Player'da spinner yok

**Çözüm:** Belirgin loading spinner eklendi

```jsx
{isLoading && (
  <div style={{
    position: 'absolute',
    top: 0, left: 0, right: 0, bottom: 0,
    backgroundColor: 'rgba(0, 0, 0, 0.8)',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center'
  }}>
    <div className="spinner" />
    <span>Video yüklənir...</span>
  </div>
)}
```

### Sorun 3: Sayfa açılırken önce ilk video görünüyor (Flash)

**Çözüm:** 
1. Training yüklenirken loading göster
2. Doğru lesson'u belirle
3. Video manifest yüklenene kadar gizle

```jsx
// ✅ TrainingStart Component
const [targetLessonId, setTargetLessonId] = useState(null);

// Doğru lesson'u belirle
if (data.user_progress?.last_lesson?.status === 'completed' && data.user_progress?.next_lesson) {
  lessonIdToOpen = data.user_progress.next_lesson.id;
}

// Loading sırasında hiçbir şey gösterme
if (loading || !targetLessonId) {
  return <LoadingSpinner />;
}

// ✅ VideoPlayer Component
const [isManifestLoaded, setIsManifestLoaded] = useState(false);

// Manifest yüklenene kadar video'yu gizle
<video style={{
  visibility: isManifestLoaded ? 'visible' : 'hidden'
}} />
```

### Tüm Düzeltmeler Özeti

1. ✅ **Sabit Player Boyutu:** `aspectRatio: '16 / 9'` ile video yüklenene kadar aynı boyutta kalır
2. ✅ **Loading Spinner:** Video yüklenirken belirgin spinner gösterilir
3. ✅ **Flash Önleme:** Manifest yüklenene kadar video gizlenir
4. ✅ **Auto-Navigation:** Doğru lesson otomatik açılır
5. ✅ **Loading State:** Training yüklenirken loading gösterilir

### Kullanım

Yukarıdaki kod örneklerini kullanarak:
- Video player sabit boyutta kalır
- Loading spinner görünür
- Flash sorunu çözülür
- Doğru lesson otomatik açılır

5. **Sizin Durumunuz:**
   - `last_lesson.id = 4` ve `status = 'completed'` ✅
   - `next_lesson.id = 5` ✅
   - Bu durumda lesson 5'i otomatik açmalısınız

