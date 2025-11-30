# HLS Streaming - Frontend Guide

## 🎯 Məqsəd

Bu sənəd frontend developer-lər üçün HLS (HTTP Live Streaming) video streaming-inin necə istifadə edilməsi barədə təlimatları izah edir. HLS streaming sayəsində zəif internetli istifadəçilər böyük videoları rahatlıqla izləyə bilərlər.

---

## 📋 Ümumi Məlumat

### HLS Streaming Nədir?

- **HLS (HTTP Live Streaming)** - Video-nu kiçik segmentlərə bölür (10 saniyəlik)
- **Adaptive Bitrate** - İnternet sürətinə görə avtomatik keyfiyyət seçir
- **Müxtəlif Keyfiyyət Variantları:**
  - **480p** - Zəif internet üçün (500 kbps)
  - **720p** - Orta internet üçün (1000 kbps)
  - **1080p** - Güclü internet üçün (2000 kbps)

### Niyə HLS?

- ✅ **Zəif internet üçün:** Video dərhal başlayır (480p)
- ✅ **Güclü internet üçün:** Yüksək keyfiyyət (1080p)
- ✅ **Avtomatik keyfiyyət seçimi:** İnternet sürəti dəyişdikdə avtomatik dəyişir
- ✅ **Hissə-hissə yüklənmə:** Yalnız izlədiyi hissə yüklənir

---

## 🔑 API Response Format

`/api/v1/trainings/{id}/detailed?lang=az` endpoint-indən alınan response-da hər video faylı üçün HLS məlumatı mövcuddur:

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
              "hls_master_playlist": "lessons/2/hls/abc123/master.m3u8",
              "hls_master_playlist_url": "http://localhost:8000/api/v1/modules/1/lessons/2/media/download?path=lessons/2/hls/abc123/master.m3u8&signature=...&expires=...",
              "hls_variants": {
                "480p": {
                  "playlist": "lessons/2/hls/abc123/480p.m3u8",
                  "bandwidth": 500000,
                  "resolution": "854x480"
                },
                "720p": {
                  "playlist": "lessons/2/hls/abc123/720p.m3u8",
                  "bandwidth": 1000000,
                  "resolution": "1280x720"
                },
                "1080p": {
                  "playlist": "lessons/2/hls/abc123/1080p.m3u8",
                  "bandwidth": 2000000,
                  "resolution": "1920x1080"
                }
              },
              "filename": "video.mp4",
              "size": 17028948
            }
          ]
        }
      ]
    }
  ]
}
```

---

## 🎬 Video Player-də İstifadə

### Vue.js Nümunəsi (HLS.js ilə)

```vue
<template>
  <div class="video-player">
    <video
      ref="videoPlayer"
      controls
      preload="metadata"
      @error="handleVideoError"
      @loadstart="handleLoadStart"
    >
      Video faylı yüklənə bilmədi.
    </video>
    
    <!-- Video Quality Selection -->
    <div v-if="hlsVariants && Object.keys(hlsVariants).length > 0" class="quality-selector">
      <label>Video Keyfiyyəti:</label>
      <select v-model="selectedQuality" @change="changeQuality">
        <option value="auto">Avtomatik</option>
        <option v-for="(variant, quality) in hlsVariants" :key="quality" :value="quality">
          {{ quality }} ({{ formatBandwidth(variant.bandwidth) }})
        </option>
      </select>
    </div>
    
    <div v-if="error" class="error-message">
      {{ error }}
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import Hls from 'hls.js'

export default {
  name: 'HLSVideoPlayer',
  props: {
    lesson: {
      type: Object,
      required: true
    }
  },
  setup(props) {
    const videoPlayer = ref(null)
    const error = ref(null)
    const hls = ref(null)
    const selectedQuality = ref('auto')
    const hlsVariants = ref({})
    const masterPlaylistUrl = ref(null)

    // Video faylını tap
    const findVideoFile = () => {
      if (!props.lesson.media_files || !Array.isArray(props.lesson.media_files)) {
        return null
      }
      
      return props.lesson.media_files.find(file => file.type === 'video')
    }

    // HLS master playlist URL-i yarat
    const getHLSMasterPlaylistUrl = () => {
      const videoFile = findVideoFile()
      
      if (!videoFile) {
        return null
      }

      // HLS master playlist URL-i varsa istifadə et
      if (videoFile.hls_master_playlist_url) {
        return videoFile.hls_master_playlist_url
      }
      
      // Əgər hls_master_playlist path varsa, signed URL yarat
      if (videoFile.hls_master_playlist) {
        // Backend-dən signed URL al (API call lazımdır)
        // Bu nümunədə sadəcə path-dən URL yaradırıq
        const baseUrl = 'http://localhost:8000/api/v1/modules/' + props.lesson.module_id + '/lessons/' + props.lesson.id + '/media/download'
        return baseUrl + '?path=' + encodeURIComponent(videoFile.hls_master_playlist)
      }
      
      return null
    }

    // HLS player-i başlat
    const initHLSPlayer = () => {
      const video = videoPlayer.value
      if (!video) {
        return
      }

      const masterPlaylist = getHLSMasterPlaylistUrl()
      
      if (!masterPlaylist) {
        error.value = 'HLS streaming mövcud deyil. Fallback video istifadə edilir.'
        // Fallback: signed URL istifadə et
        const videoFile = findVideoFile()
        if (videoFile && videoFile.signed_url) {
          video.src = videoFile.signed_url
        }
        return
      }

      // HLS.js dəstəklənir?
      if (Hls.isSupported()) {
        // HLS.js ilə player yarat
        hls.value = new Hls({
          enableWorker: true,
          lowLatencyMode: false,
          backBufferLength: 90,
        })

        // Master playlist yüklə
        hls.value.loadSource(masterPlaylist)
        hls.value.attachMedia(video)

        // HLS variants məlumatını saxla
        const videoFile = findVideoFile()
        if (videoFile && videoFile.hls_variants) {
          hlsVariants.value = videoFile.hls_variants
        }

        // Event listeners
        hls.value.on(Hls.Events.MANIFEST_PARSED, () => {
          console.log('HLS manifest parsed')
          error.value = null
        })

        hls.value.on(Hls.Events.ERROR, (event, data) => {
          console.error('HLS error:', data)
          if (data.fatal) {
            switch (data.type) {
              case Hls.ErrorTypes.NETWORK_ERROR:
                error.value = 'Şəbəkə xətası. Yenidən yoxlayın.'
                hls.value.startLoad()
                break
              case Hls.ErrorTypes.MEDIA_ERROR:
                error.value = 'Media xətası. Yenidən yoxlayın.'
                hls.value.recoverMediaError()
                break
              default:
                error.value = 'Video yüklənə bilmədi.'
                hls.value.destroy()
                break
            }
          }
        })

        // Quality change listener
        hls.value.on(Hls.Events.LEVEL_SWITCHED, (event, data) => {
          const level = hls.value.levels[data.level]
          const qualityName = Object.keys(hlsVariants.value)[data.level] || `Level ${data.level}`
          console.log('Quality switched to:', qualityName, {
            level: data.level,
            bandwidth: level?.bitrate,
            resolution: level ? `${level.width}x${level.height}` : 'unknown'
          })
          
          // UI-da cari keyfiyyəti göstər
          // selectedQuality.value = qualityName (optional)
        })
        
        // Available levels loaded
        hls.value.on(Hls.Events.LEVELS_UPDATED, () => {
          console.log('Available quality levels:', getAvailableQualities())
        })

      } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
        // Safari native HLS support
        video.src = masterPlaylist
        error.value = null
      } else {
        error.value = 'HLS streaming dəstəklənmir. Fallback video istifadə edilir.'
        // Fallback: signed URL istifadə et
        const videoFile = findVideoFile()
        if (videoFile && videoFile.signed_url) {
          video.src = videoFile.signed_url
        }
      }
    }

    // Video keyfiyyətini dəyiş
    const changeQuality = () => {
      if (!hls.value) {
        return
      }
      
      if (selectedQuality.value === 'auto') {
        // Auto quality - HLS.js avtomatik seçir
        hls.value.currentLevel = -1
        console.log('Quality set to: Auto')
        return
      }

      // Müəyyən keyfiyyət seç
      const variants = Object.keys(hlsVariants.value)
      const qualityIndex = variants.indexOf(selectedQuality.value)
      
      if (qualityIndex !== -1 && qualityIndex < hls.value.levels.length) {
        hls.value.currentLevel = qualityIndex
        console.log('Quality changed to:', selectedQuality.value, 'Level:', qualityIndex)
      } else {
        console.warn('Quality not found:', selectedQuality.value)
      }
    }
    
    // Mövcud keyfiyyətləri al
    const getAvailableQualities = () => {
      if (!hls.value || !hls.value.levels) {
        return []
      }
      
      return hls.value.levels.map((level, index) => ({
        index: index,
        quality: Object.keys(hlsVariants.value)[index] || `Level ${index}`,
        bandwidth: level.bitrate,
        resolution: `${level.width}x${level.height}`,
      }))
    }

    // Bandwidth formatla
    const formatBandwidth = (bandwidth) => {
      if (bandwidth >= 1000000) {
        return (bandwidth / 1000000).toFixed(1) + ' Mbps'
      }
      return (bandwidth / 1000).toFixed(0) + ' kbps'
    }

    // Video error handler
    const handleVideoError = (event) => {
      console.error('Video error:', event)
      error.value = 'Video oynatıla bilmədi'
    }

    const handleLoadStart = () => {
      error.value = null
    }

    // Component mount olduqda
    onMounted(() => {
      initHLSPlayer()
    })

    // Component unmount olduqda
    onUnmounted(() => {
      if (hls.value) {
        hls.value.destroy()
      }
    })

    // Lesson dəyişdikdə player-i yenilə
    watch(() => props.lesson, () => {
      if (hls.value) {
        hls.value.destroy()
      }
      initHLSPlayer()
    }, { deep: true })

    return {
      videoPlayer,
      error,
      selectedQuality,
      hlsVariants,
      changeQuality,
      formatBandwidth,
      handleVideoError,
      handleLoadStart
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

.quality-selector {
  margin-top: 1rem;
  padding: 0.5rem;
  background-color: #f5f5f5;
  border-radius: 4px;
}

.quality-selector label {
  margin-right: 0.5rem;
  font-weight: bold;
}

.quality-selector select {
  padding: 0.25rem 0.5rem;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.error-message {
  padding: 1rem;
  background-color: #fee;
  color: #c33;
  border-radius: 4px;
  margin-top: 1rem;
}
</style>
```

---

## 📦 HLS.js Quraşdırma

### npm ilə

```bash
npm install hls.js
```

### CDN ilə

```html
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
```

---

## 🔄 Fallback Strategiyası

HLS streaming mövcud olmadıqda və ya dəstəklənmədikdə fallback strategiyası:

1. **HLS mövcud deyil:** `signed_url` istifadə et
2. **HLS.js dəstəklənmir:** Safari native HLS istifadə et
3. **HLS işləmir:** `signed_url` fallback istifadə et

---

## ⚙️ Konfiqurasiya

### HLS.js Options

```javascript
const hls = new Hls({
  enableWorker: true,           // Web Worker istifadə et (performans)
  lowLatencyMode: false,        // Low latency mode (live streaming üçün)
  backBufferLength: 90,         // Back buffer uzunluğu (saniyə)
  maxBufferLength: 30,          // Max buffer uzunluğu (saniyə)
  maxMaxBufferLength: 600,      // Max max buffer uzunluğu (saniyə)
  maxBufferSize: 60 * 1000 * 1000, // Max buffer ölçüsü (bytes)
  maxBufferHole: 0.5,           // Max buffer hole (saniyə)
  highBufferWatchdogPeriod: 2,  // High buffer watchdog period
  nudgeOffset: 0.1,             // Nudge offset
  nudgeMaxRetry: 3,             // Nudge max retry
  maxFragLoadingTimeOut: 200000, // Max fragment loading timeout
  fragLoadingTimeOut: 20000,    // Fragment loading timeout
  manifestLoadingTimeOut: 10000, // Manifest loading timeout
  levelLoadingTimeOut: 10000,   // Level loading timeout
})
```

---

## 🎯 Video Quality Selection

### Avtomatik Keyfiyyət Seçimi

HLS.js avtomatik olaraq internet sürətinə görə keyfiyyət seçir:
- **Zəif internet:** 480p
- **Orta internet:** 720p
- **Güclü internet:** 1080p

### Manual Keyfiyyət Seçimi

```javascript
// Müəyyən keyfiyyət seç
hls.currentLevel = 0  // 480p
hls.currentLevel = 1  // 720p
hls.currentLevel = 2  // 1080p
hls.currentLevel = -1 // Auto
```

---

## 📊 Performans Metrikaları

### HLS Streaming Avantajları

| Metric | Normal Video | HLS Streaming |
|--------|-------------|----------------|
| **İlk yüklənmə** | 15-30s | 2-5s |
| **Zəif internet** | Buffer edir | 480p avtomatik |
| **Güclü internet** | Eyni | 1080p avtomatik |
| **Seek sürəti** | Yavaş | Sürətli |
| **Bandwidth istifadəsi** | Yüksək | Optimallaşdırılmış |

---

## ✅ Xülasə

1. **HLS.js quraşdırın** - `npm install hls.js`
2. **Master playlist URL-i alın** - API response-dan `hls_master_playlist_url`
3. **HLS player yaradın** - `new Hls()` və `loadSource()`
4. **Quality selection əlavə edin** - İstifadəçi keyfiyyət seçə bilməlidir
5. **Fallback strategiyası** - HLS işləmədikdə `signed_url` istifadə edin

Bu təlimatları izlədikdə zəif internetli istifadəçilər böyük videoları rahatlıqla izləyə biləcəklər!

