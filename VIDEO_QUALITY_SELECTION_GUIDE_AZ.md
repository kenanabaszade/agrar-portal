# Video Keyfiyyət Seçimi - Frontend Guide

## 🎯 Məqsəd

Bu sənəd frontend developer-lər üçün video keyfiyyət seçimi funksionallığının necə tətbiq edilməsi barədə təlimatları izah edir.

---

## ✅ Bəli, İstifadəçi Keyfiyyəti Dəyişə Bilər!

HLS streaming sayəsində istifadəçi video keyfiyyətini seçə bilər:
- **Avtomatik** - İnternet sürətinə görə avtomatik seçir (tövsiyə olunur)
- **480p** - Zəif internet üçün (500 kbps)
- **720p** - Orta internet üçün (1000 kbps)
- **1080p** - Güclü internet üçün (2000 kbps)

---

## 📋 API Response Format

`/api/v1/trainings/{id}/detailed?lang=az` endpoint-indən alınan response-da:

```json
{
  "media_files": [
    {
      "type": "video",
      "hls_master_playlist_url": "http://localhost:8000/api/v1/modules/1/lessons/2/media/download?path=lessons/2/hls/abc123/master.m3u8&signature=...&expires=...",
      "hls_variants": {
        "480p": {
          "playlist": "lessons/2/hls/abc123/480p.m3u8",
          "playlist_url": "http://localhost:8000/api/v1/modules/1/lessons/2/media/download?path=lessons/2/hls/abc123/480p.m3u8&signature=...&expires=...",
          "bandwidth": 500000,
          "resolution": "854x480"
        },
        "720p": {
          "playlist": "lessons/2/hls/abc123/720p.m3u8",
          "playlist_url": "http://localhost:8000/api/v1/modules/1/lessons/2/media/download?path=lessons/2/hls/abc123/720p.m3u8&signature=...&expires=...",
          "bandwidth": 1000000,
          "resolution": "1280x720"
        },
        "1080p": {
          "playlist": "lessons/2/hls/abc123/1080p.m3u8",
          "playlist_url": "http://localhost:8000/api/v1/modules/1/lessons/2/media/download?path=lessons/2/hls/abc123/1080p.m3u8&signature=...&expires=...",
          "bandwidth": 2000000,
          "resolution": "1920x1080"
        }
      }
    }
  ]
}
```

---

## 🎬 Video Player-də Keyfiyyət Seçimi

### Vue.js Nümunəsi

```vue
<template>
  <div class="video-player">
    <video
      ref="videoPlayer"
      controls
      preload="metadata"
    >
      Video faylı yüklənə bilmədi.
    </video>
    
    <!-- Video Keyfiyyət Seçimi -->
    <div v-if="hlsVariants && Object.keys(hlsVariants).length > 0" class="quality-selector">
      <label>Video Keyfiyyəti:</label>
      <select v-model="selectedQuality" @change="changeQuality">
        <option value="auto">Avtomatik (Tövsiyə olunur)</option>
        <option v-for="(variant, quality) in hlsVariants" :key="quality" :value="quality">
          {{ quality }} ({{ formatBandwidth(variant.bandwidth) }})
        </option>
      </select>
      
      <!-- Cari keyfiyyət göstəricisi -->
      <span v-if="currentQuality" class="current-quality">
        Cari: {{ currentQuality }}
      </span>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onUnmounted } from 'vue'
import Hls from 'hls.js'

export default {
  name: 'VideoPlayerWithQuality',
  props: {
    lesson: {
      type: Object,
      required: true
    }
  },
  setup(props) {
    const videoPlayer = ref(null)
    const hls = ref(null)
    const selectedQuality = ref('auto')
    const currentQuality = ref(null)
    const hlsVariants = ref({})

    // Video faylını tap
    const findVideoFile = () => {
      if (!props.lesson.media_files || !Array.isArray(props.lesson.media_files)) {
        return null
      }
      return props.lesson.media_files.find(file => file.type === 'video')
    }

    // HLS player-i başlat
    const initHLSPlayer = () => {
      const video = videoPlayer.value
      if (!video) return

      const videoFile = findVideoFile()
      if (!videoFile || !videoFile.hls_master_playlist_url) {
        // Fallback: signed URL istifadə et
        if (videoFile && videoFile.signed_url) {
          video.src = videoFile.signed_url
        }
        return
      }

      // HLS variants məlumatını saxla
      if (videoFile.hls_variants) {
        hlsVariants.value = videoFile.hls_variants
      }

      // HLS.js dəstəklənir?
      if (Hls.isSupported()) {
        hls.value = new Hls({
          enableWorker: true,
          lowLatencyMode: false,
        })

        // Master playlist yüklə
        hls.value.loadSource(videoFile.hls_master_playlist_url)
        hls.value.attachMedia(video)

        // Quality change listener
        hls.value.on(Hls.Events.LEVEL_SWITCHED, (event, data) => {
          const level = hls.value.levels[data.level]
          const qualityName = Object.keys(hlsVariants.value)[data.level] || `Level ${data.level}`
          currentQuality.value = qualityName
          console.log('Quality switched to:', qualityName)
        })

        // Levels loaded
        hls.value.on(Hls.Events.LEVELS_UPDATED, () => {
          console.log('Available qualities:', hls.value.levels.length)
        })

      } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
        // Safari native HLS
        video.src = videoFile.hls_master_playlist_url
      }
    }

    // Video keyfiyyətini dəyiş
    const changeQuality = () => {
      if (!hls.value) return

      if (selectedQuality.value === 'auto') {
        // Avtomatik keyfiyyət
        hls.value.currentLevel = -1
        currentQuality.value = 'Avtomatik'
        console.log('Quality set to: Auto')
        return
      }

      // Müəyyən keyfiyyət seç
      const variants = Object.keys(hlsVariants.value)
      const qualityIndex = variants.indexOf(selectedQuality.value)

      if (qualityIndex !== -1 && qualityIndex < hls.value.levels.length) {
        hls.value.currentLevel = qualityIndex
        currentQuality.value = selectedQuality.value
        console.log('Quality changed to:', selectedQuality.value)
      }
    }

    // Bandwidth formatla
    const formatBandwidth = (bandwidth) => {
      if (bandwidth >= 1000000) {
        return (bandwidth / 1000000).toFixed(1) + ' Mbps'
      }
      return (bandwidth / 1000).toFixed(0) + ' kbps'
    }

    onMounted(() => {
      initHLSPlayer()
    })

    onUnmounted(() => {
      if (hls.value) {
        hls.value.destroy()
      }
    })

    return {
      videoPlayer,
      selectedQuality,
      currentQuality,
      hlsVariants,
      changeQuality,
      formatBandwidth
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
  padding: 1rem;
  background-color: #f5f5f5;
  border-radius: 4px;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.quality-selector label {
  font-weight: bold;
  white-space: nowrap;
}

.quality-selector select {
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  min-width: 200px;
}

.current-quality {
  margin-left: auto;
  color: #666;
  font-size: 0.9rem;
}
</style>
```

---

## 🎯 Keyfiyyət Seçimi Necə İşləyir?

### 1. Avtomatik Keyfiyyət (Default)

```javascript
// HLS.js avtomatik olaraq internet sürətinə görə keyfiyyət seçir
hls.currentLevel = -1  // Auto
```

**Nə vaxt dəyişir:**
- İnternet sürəti artdıqda → Yüksək keyfiyyətə keçir
- İnternet sürəti azaldıqda → Aşağı keyfiyyətə keçir

### 2. Manual Keyfiyyət Seçimi

```javascript
// 480p seç
hls.currentLevel = 0

// 720p seç
hls.currentLevel = 1

// 1080p seç
hls.currentLevel = 2
```

---

## 📊 Keyfiyyət Variantları

| Keyfiyyət | Resolution | Bitrate | İnternet Tələbi |
|-----------|------------|---------|-----------------|
| **480p** | 854x480 | 500 kbps | Zəif (1-2 Mbps) |
| **720p** | 1280x720 | 1000 kbps | Orta (3-5 Mbps) |
| **1080p** | 1920x1080 | 2000 kbps | Güclü (10+ Mbps) |

---

## 🔄 Keyfiyyət Dəyişməsi

### Avtomatik Dəyişmə

HLS.js avtomatik olaraq:
- **Buffer azalır** → Aşağı keyfiyyətə keçir
- **Buffer artır** → Yüksək keyfiyyətə keçir

### Manual Dəyişmə

İstifadəçi dropdown-dan seçir:
- **Avtomatik** → HLS.js avtomatik seçir
- **480p** → Həmişə 480p
- **720p** → Həmişə 720p
- **1080p** → Həmişə 1080p

---

## ✅ Xülasə

1. ✅ **İstifadəçi keyfiyyəti dəyişə bilər** - Dropdown ilə
2. ✅ **Avtomatik keyfiyyət** - Default olaraq aktivdir
3. ✅ **3 variant:** 480p, 720p, 1080p
4. ✅ **Real-time dəyişmə** - Video dayandırmadan dəyişir

---

## 🎨 UI Təkmilləşdirməsi

### Daha Yaxşı UI üçün:

```vue
<!-- Quality selector button style -->
<div class="quality-selector">
  <button @click="showQualityMenu = !showQualityMenu" class="quality-btn">
    {{ currentQuality || 'Avtomatik' }}
    <span class="arrow">▼</span>
  </button>
  
  <div v-if="showQualityMenu" class="quality-menu">
    <div 
      v-for="(variant, quality) in hlsVariants" 
      :key="quality"
      @click="selectQuality(quality)"
      :class="{ active: selectedQuality === quality }"
      class="quality-option"
    >
      {{ quality }} ({{ formatBandwidth(variant.bandwidth) }})
    </div>
    <div 
      @click="selectQuality('auto')"
      :class="{ active: selectedQuality === 'auto' }"
      class="quality-option"
    >
      Avtomatik
    </div>
  </div>
</div>
```

Bu təlimatları izlədikdə istifadəçi video keyfiyyətini rahatlıqla dəyişə biləcək!

