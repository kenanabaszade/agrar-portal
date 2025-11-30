# Video Keyfiyyət Seçimi - Frontend İstifadə Təlimatı (Azərbaycan Dili)

## 📋 Mündəricat

1. [Backend Response Strukturu](#backend-response-strukturu)
2. [Video Faylının Tapılması](#video-faylının-tapılması)
3. [HLS Master Playlist URL İstifadəsi](#hls-master-playlist-url-istifadəsi)
4. [Video Keyfiyyət Variantlarının Oxunması](#video-keyfiyyət-variantlarının-oxunması)
5. [Video Player-da İstifadə](#video-player-da-istifadə)
6. [Manual Keyfiyyət Seçimi](#manual-keyfiyyət-seçimi)
7. [Tam Kod Nümunələri](#tam-kod-nümunələri)
8. [Xəta İdarəetməsi](#xəta-idarəetməsi)

---

## 🔍 Backend Response Strukturu

### API Request

```
GET /api/v1/trainings/{trainingId}/detailed?lang=az
```

### Response Strukturu

Backend-dən gələn response-da video məlumatları `modules[].lessons[].media_files[]` array-ində yerləşir:

```json
{
  "modules": [
    {
      "id": 2,
      "lessons": [
        {
          "id": 5,
          "media_files": [
            {
              "type": "video",
              "url": "http://localhost:8000/api/v1/modules/2/lessons/5/media/download?path=...",
              "path": "lessons/5/Q02HEboK7USDjNp3i9VmZNlt8SHMduafGjfMsu8I.mp4",
              "filename": "video.mp4",
              "size": 17028948,
              "mime_type": "video/mp4",
              
              // ⚠️ ƏHƏMİYYƏTLİ: Bu URL-i video player-da İSTİFADƏ ETMƏYİN!
              "signed_url": "http://localhost:8000/api/v1/modules/2/lessons/5/media/download?path=lessons%2F5%2Fvideo.mp4&signature=...&expires=...",
              
              // ✅ DOĞRU: HLS Master Playlist URL-i istifadə edin
              "hls_master_playlist": "lessons/5/hls/master.m3u8",
              "hls_master_playlist_url": "http://localhost:8000/api/v1/modules/2/lessons/5/media/download?path=lessons%2F5%2Fhls%2Fmaster.m3u8&signature=...&expires=...",
              
              // ✅ Video Keyfiyyət Variantları
              "hls_variants": {
                "480p": {
                  "playlist": "lessons/5/hls/480p.m3u8",
                  "bandwidth": 500000,
                  "resolution": "854x480",
                  "playlist_url": "http://localhost:8000/api/v1/modules/2/lessons/5/media/download?path=lessons%2F5%2Fhls%2F480p.m3u8&signature=...&expires=..."
                },
                "720p": {
                  "playlist": "lessons/5/hls/720p.m3u8",
                  "bandwidth": 1000000,
                  "resolution": "1280x720",
                  "playlist_url": "http://localhost:8000/api/v1/modules/2/lessons/5/media/download?path=lessons%2F5%2Fhls%2F720p.m3u8&signature=...&expires=..."
                },
                "1080p": {
                  "playlist": "lessons/5/hls/1080p.m3u8",
                  "bandwidth": 2000000,
                  "resolution": "1920x1080",
                  "playlist_url": "http://localhost:8000/api/v1/modules/2/lessons/5/media/download?path=lessons%2F5%2Fhls%2F1080p.m3u8&signature=...&expires=..."
                }
              },
              
              "signed_url_expires_at": "2025-11-26T19:23:53+00:00"
            }
          ]
        }
      ]
    }
  ]
}
```

---

## 📍 Video Faylının Tapılması

### Adım 1: Training Məlumatlarını Yükləyin

```javascript
// Vue.js nümunəsi
async loadTraining() {
  try {
    const response = await fetch(
      `http://localhost:8000/api/v1/trainings/${this.trainingId}/detailed?lang=az`,
      {
        headers: {
          'Authorization': `Bearer ${this.token}`,
          'Content-Type': 'application/json'
        }
      }
    );
    
    const data = await response.json();
    this.training = data;
    
    // Video faylını tap
    this.findVideoFile();
  } catch (error) {
    console.error('Training yüklənərkən xəta:', error);
  }
}
```

### Adım 2: Video Faylını Tapın

```javascript
// Vue.js nümunəsi
findVideoFile() {
  // 1. Cari lesson-u tap
  const currentLesson = this.findCurrentLesson();
  
  if (!currentLesson) {
    console.error('Lesson tapılmadı');
    return null;
  }
  
  // 2. Media files array-indən video faylını tap
  const videoFile = currentLesson.media_files?.find(
    file => file.type === 'video'
  );
  
  if (!videoFile) {
    console.error('Video faylı tapılmadı');
    return null;
  }
  
  // 3. HLS məlumatlarını yoxla
  if (!videoFile.hls_master_playlist_url) {
    console.error('HLS master playlist URL tapılmadı');
    return null;
  }
  
  this.videoFile = videoFile;
  return videoFile;
}

findCurrentLesson() {
  // URL-dən lesson ID-ni al və ya user_progress-dən
  const lessonId = this.$route.params.lessonId;
  
  // Training-dən lesson-u tap
  for (const module of this.training.modules || []) {
    const lesson = module.lessons?.find(l => l.id === parseInt(lessonId));
    if (lesson) return lesson;
  }
  
  return null;
}
```

---

## 🎬 HLS Master Playlist URL İstifadəsi

### ⚠️ ƏHƏMİYYƏTLİ QAYDALAR

1. **DOĞRU:** `hls_master_playlist_url` istifadə edin
2. **YANLIŞ:** `signed_url` (MP4 faylı üçündür, HLS deyil!)
3. **YANLIŞ:** `url` (Authentication tələb edir)

### Niyə HLS Master Playlist?

- ✅ **Adaptive Streaming:** Şəbəkə sürətinə görə avtomatik keyfiyyət dəyişir
- ✅ **Daha Yaxşı Performans:** Kiçik segmentlərlə yüklənir
- ✅ **Seek Dəstəyi:** Video-nun istənilən yerinə atlaya bilərsiniz
- ✅ **Çoxlu Keyfiyyət:** 480p, 720p, 1080p variantları avtomatik işləyir

---

## 📊 Video Keyfiyyət Variantlarının Oxunması

### Variant Məlumatlarının Strukturu

Hər bir variant aşağıdakı məlumatları ehtiva edir:

```javascript
{
  "480p": {
    "playlist": "lessons/5/hls/480p.m3u8",        // Fayl yolu
    "bandwidth": 500000,                           // Bitrate (bits/saniyə)
    "resolution": "854x480",                       // Video ölçüsü
    "playlist_url": "http://..."                   // Signed URL
  }
}
```

### Variantları Oxumaq

```javascript
// Vue.js nümunəsi
getVideoQualities() {
  if (!this.videoFile || !this.videoFile.hls_variants) {
    return [];
  }
  
  const variants = this.videoFile.hls_variants;
  const qualities = [];
  
  // Variantları sıralamaq (480p -> 720p -> 1080p)
  const qualityOrder = ['480p', '720p', '1080p'];
  
  qualityOrder.forEach(quality => {
    if (variants[quality]) {
      qualities.push({
        label: quality,
        resolution: variants[quality].resolution,
        bandwidth: variants[quality].bandwidth,
        playlistUrl: variants[quality].playlist_url
      });
    }
  });
  
  return qualities;
}
```

### Keyfiyyət Məlumatlarını Göstərmək

```javascript
// Vue.js computed property
computed: {
  availableQualities() {
    if (!this.videoFile?.hls_variants) {
      return [];
    }
    
    return Object.keys(this.videoFile.hls_variants).map(quality => ({
      value: quality,
      label: this.getQualityLabel(quality),
      resolution: this.videoFile.hls_variants[quality].resolution,
      bandwidth: this.videoFile.hls_variants[quality].bandwidth
    }));
  }
},

methods: {
  getQualityLabel(quality) {
    const labels = {
      '480p': '480p (SD)',
      '720p': '720p (HD)',
      '1080p': '1080p (Full HD)'
    };
    return labels[quality] || quality;
  }
}
```

---

## 🎥 Video Player-da İstifadə

### Vue.js + hls.js Nümunəsi

```vue
<template>
  <div class="video-player-container">
    <!-- Loading Spinner -->
    <div v-if="isLoading" class="loading-overlay">
      <div class="spinner"></div>
      <span>Video yüklənir...</span>
    </div>
    
    <!-- Error Message -->
    <div v-if="hasError && !isLoading" class="error-overlay">
      <p>{{ errorMessage }}</p>
      <button @click="retry">Yenidən yoxla</button>
    </div>
    
    <!-- Video Element -->
    <video
      ref="videoElement"
      controls
      :style="{
        width: '100%',
        aspectRatio: '16/9',
        backgroundColor: '#000',
        visibility: isManifestLoaded ? 'visible' : 'hidden'
      }"
      playsinline
      @error="onVideoError"
    >
      Brauzeriniz video tag-ını dəstəkləmir.
    </video>
    
    <!-- Quality Selector (Optional) -->
    <div v-if="showQualitySelector" class="quality-selector">
      <select v-model="selectedQuality" @change="changeQuality">
        <option value="auto">Avtomatik</option>
        <option 
          v-for="quality in availableQualities" 
          :key="quality.value"
          :value="quality.value"
        >
          {{ quality.label }} ({{ quality.resolution }})
        </option>
      </select>
    </div>
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
    },
    showQualitySelector: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      hls: null,
      isLoading: true,
      hasError: false,
      errorMessage: '',
      isManifestLoaded: false,
      selectedQuality: 'auto'
    };
  },
  computed: {
    // HLS Master Playlist URL-i
    hlsMasterUrl() {
      return this.videoFile?.hls_master_playlist_url;
    },
    
    // Mövcud keyfiyyət variantları
    availableQualities() {
      if (!this.videoFile?.hls_variants) {
        return [];
      }
      
      return Object.keys(this.videoFile.hls_variants).map(quality => {
        const variant = this.videoFile.hls_variants[quality];
        return {
          value: quality,
          label: this.getQualityLabel(quality),
          resolution: variant.resolution,
          bandwidth: variant.bandwidth,
          playlistUrl: variant.playlist_url
        };
      });
    }
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
      
      // ✅ ƏHƏMİYYƏTLİ: hls_master_playlist_url istifadə edin
      const hlsUrl = this.videoFile.hls_master_playlist_url;
      
      if (!hlsUrl) {
        console.error('HLS master playlist URL tapılmadı');
        this.hasError = true;
        this.errorMessage = 'Video faylı tapılmadı';
        this.isLoading = false;
        return;
      }
      
      console.log('HLS URL:', hlsUrl);
      console.log('HLS Variants:', this.videoFile.hls_variants);
      
      // Loading state
      this.isLoading = true;
      this.hasError = false;
      this.isManifestLoaded = false;
      
      // Browser HLS dəstəyini yoxla
      if (Hls.isSupported()) {
        // hls.js ilə oynat
        this.hls = new Hls({
          enableWorker: true,
          lowLatencyMode: false,
          // CORS üçün
          xhrSetup: (xhr, url) => {
            xhr.withCredentials = false;
          }
        });
        
        // Master playlist-i yüklə
        this.hls.loadSource(hlsUrl);
        this.hls.attachMedia(video);
        
        // Event listener-lar
        this.hls.on(Hls.Events.MANIFEST_PARSED, () => {
          console.log('HLS Manifest yükləndi');
          this.isManifestLoaded = true;
          this.isLoading = false;
          
          // Mövcud keyfiyyət səviyyələrini göstər
          const levels = this.hls.levels;
          console.log('Mövcud keyfiyyət səviyyələri:', levels);
        });
        
        this.hls.on(Hls.Events.LEVEL_LOADED, () => {
          this.isManifestLoaded = true;
        });
        
        this.hls.on(Hls.Events.FRAG_LOADING, () => {
          this.isLoading = true;
        });
        
        this.hls.on(Hls.Events.FRAG_LOADED, () => {
          this.isLoading = false;
        });
        
        // Xəta idarəetməsi
        this.hls.on(Hls.Events.ERROR, (event, data) => {
          console.error('HLS Xətası:', data);
          
          if (data.fatal) {
            this.isLoading = false;
            this.hasError = true;
            
            switch (data.type) {
              case Hls.ErrorTypes.NETWORK_ERROR:
                console.error('Şəbəkə xətası, bərpa edilir...');
                this.hls.startLoad();
                break;
              case Hls.ErrorTypes.MEDIA_ERROR:
                console.error('Media xətası, bərpa edilir...');
                this.hls.recoverMediaError();
                break;
              default:
                console.error('Fatal xəta, bərpa olunmur');
                this.errorMessage = 'Video yüklənə bilmədi';
                this.hls.destroy();
                break;
            }
          }
        });
      } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
        // Safari native HLS dəstəyi
        video.src = hlsUrl;
        
        video.addEventListener('loadedmetadata', () => {
          this.isManifestLoaded = true;
          this.isLoading = false;
        });
      } else {
        this.isLoading = false;
        this.hasError = true;
        this.errorMessage = 'Brauzeriniz HLS-ni dəstəkləmir';
      }
    },
    
    // Manual keyfiyyət dəyişdirmə
    changeQuality() {
      if (!this.hls || this.selectedQuality === 'auto') {
        // Avtomatik keyfiyyət seçimi
        this.hls.currentLevel = -1;
        return;
      }
      
      // Seçilmiş keyfiyyətə uyğun level tap
      const levels = this.hls.levels;
      const selectedVariant = this.videoFile.hls_variants[this.selectedQuality];
      
      if (!selectedVariant) return;
      
      // Bandwidth-ə görə level tap
      const targetBandwidth = selectedVariant.bandwidth;
      const levelIndex = levels.findIndex(level => 
        level.bitrate === targetBandwidth
      );
      
      if (levelIndex !== -1) {
        this.hls.currentLevel = levelIndex;
        console.log(`Keyfiyyət dəyişdirildi: ${this.selectedQuality}`);
      }
    },
    
    getQualityLabel(quality) {
      const labels = {
        '480p': '480p (SD)',
        '720p': '720p (HD)',
        '1080p': '1080p (Full HD)'
      };
      return labels[quality] || quality;
    },
    
    onVideoError(event) {
      console.error('Video xətası:', event);
      this.hasError = true;
      this.errorMessage = 'Video oynatıla bilmədi';
      this.isLoading = false;
    },
    
    retry() {
      this.hasError = false;
      this.isLoading = true;
      this.destroyPlayer();
      this.initPlayer();
    },
    
    destroyPlayer() {
      if (this.hls) {
        this.hls.destroy();
        this.hls = null;
      }
    }
  }
};
</script>

<style scoped>
.video-player-container {
  position: relative;
  width: 100%;
  aspect-ratio: 16/9;
  background-color: #000;
  border-radius: 8px;
  overflow: hidden;
}

.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 10;
  background-color: rgba(0, 0, 0, 0.8);
  gap: 16px;
}

.spinner {
  width: 60px;
  height: 60px;
  border: 5px solid rgba(255, 255, 255, 0.2);
  border-top: 5px solid #3498db;
  border-radius: 50%;
  animation: spin 1s linear infinite;
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

.quality-selector {
  position: absolute;
  bottom: 60px;
  right: 10px;
  z-index: 5;
}

.quality-selector select {
  padding: 8px 12px;
  background-color: rgba(0, 0, 0, 0.7);
  color: #fff;
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 4px;
  cursor: pointer;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>
```

---

## 🔧 Manual Keyfiyyət Seçimi

Əgər istifadəçi manual olaraq keyfiyyət seçmək istəyirsə:

### Adım 1: Quality Selector UI

```vue
<template>
  <div class="quality-selector-dropdown">
    <button @click="toggleDropdown" class="quality-button">
      {{ currentQualityLabel }}
      <span class="arrow">▼</span>
    </button>
    
    <div v-if="showDropdown" class="dropdown-menu">
      <button 
        @click="selectQuality('auto')"
        :class="{ active: selectedQuality === 'auto' }"
      >
        Avtomatik
      </button>
      <button 
        v-for="quality in availableQualities"
        :key="quality.value"
        @click="selectQuality(quality.value)"
        :class="{ active: selectedQuality === quality.value }"
      >
        {{ quality.label }} ({{ quality.resolution }})
      </button>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      showDropdown: false,
      selectedQuality: 'auto'
    };
  },
  computed: {
    currentQualityLabel() {
      if (this.selectedQuality === 'auto') {
        return 'Keyfiyyət: Avtomatik';
      }
      const quality = this.availableQualities.find(q => q.value === this.selectedQuality);
      return quality ? `Keyfiyyət: ${quality.label}` : 'Keyfiyyət';
    }
  },
  methods: {
    toggleDropdown() {
      this.showDropdown = !this.showDropdown;
    },
    selectQuality(quality) {
      this.selectedQuality = quality;
      this.showDropdown = false;
      this.changeQuality();
    }
  }
};
</script>
```

---

## 📝 Tam Kod Nümunəsi: TrainingStart.vue

```vue
<template>
  <div class="training-start">
    <!-- Loading -->
    <div v-if="loading" class="loading-container">
      <div class="spinner"></div>
      <p>Yüklənir...</p>
    </div>
    
    <!-- Training Content -->
    <div v-else-if="training && currentLesson">
      <h1>{{ training.title?.az }}</h1>
      
      <!-- Lesson Info -->
      <div class="lesson-info">
        <h2>{{ currentLesson.title?.az }}</h2>
        <p>{{ currentLesson.content?.az }}</p>
      </div>
      
      <!-- Video Player -->
      <div v-if="videoFile" class="video-section">
        <VideoPlayer 
          :video-file="videoFile"
          :show-quality-selector="true"
          @error="handleVideoError"
        />
      </div>
      
      <!-- Other Media Files -->
      <div v-if="otherMediaFiles.length > 0" class="media-files">
        <div 
          v-for="(file, index) in otherMediaFiles"
          :key="index"
          class="media-item"
        >
          <img v-if="file.type === 'image'" :src="file.signed_url" :alt="file.title" />
        </div>
      </div>
    </div>
    
    <!-- Error -->
    <div v-else-if="error" class="error-container">
      <p>{{ error }}</p>
      <button @click="loadTraining">Yenidən yoxla</button>
    </div>
  </div>
</template>

<script>
import VideoPlayer from '@/components/VideoPlayer.vue';

export default {
  name: 'TrainingStart',
  components: {
    VideoPlayer
  },
  data() {
    return {
      training: null,
      currentLesson: null,
      videoFile: null,
      otherMediaFiles: [],
      loading: true,
      error: null
    };
  },
  computed: {
    trainingId() {
      return this.$route.params.trainingId;
    },
    lessonId() {
      return this.$route.params.lessonId;
    }
  },
  async mounted() {
    await this.loadTraining();
  },
  watch: {
    lessonId: {
      handler() {
        this.loadTraining();
      }
    }
  },
  methods: {
    async loadTraining() {
      try {
        this.loading = true;
        this.error = null;
        
        const response = await fetch(
          `http://localhost:8000/api/v1/trainings/${this.trainingId}/detailed?lang=az`,
          {
            headers: {
              'Authorization': `Bearer ${this.getToken()}`,
              'Content-Type': 'application/json'
            }
          }
        );
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        this.training = data;
        
        // Cari lesson-u tap
        this.findCurrentLesson();
        
        // Video faylını tap
        this.findVideoFile();
        
      } catch (error) {
        console.error('Training yüklənərkən xəta:', error);
        this.error = 'Training yüklənə bilmədi';
      } finally {
        this.loading = false;
      }
    },
    
    findCurrentLesson() {
      const targetLessonId = parseInt(this.lessonId);
      
      // Training-dən lesson-u tap
      for (const module of this.training.modules || []) {
        const lesson = module.lessons?.find(l => l.id === targetLessonId);
        if (lesson) {
          this.currentLesson = lesson;
          return;
        }
      }
      
      // Fallback: İlk lesson-u götür
      if (this.training.modules?.[0]?.lessons?.[0]) {
        this.currentLesson = this.training.modules[0].lessons[0];
      }
    },
    
    findVideoFile() {
      if (!this.currentLesson || !this.currentLesson.media_files) {
        this.videoFile = null;
        return;
      }
      
      // Video faylını tap
      const video = this.currentLesson.media_files.find(
        file => file.type === 'video'
      );
      
      if (!video) {
        console.warn('Video faylı tapılmadı');
        this.videoFile = null;
        return;
      }
      
      // HLS məlumatlarını yoxla
      if (!video.hls_master_playlist_url) {
        console.error('HLS master playlist URL tapılmadı');
        this.videoFile = null;
        return;
      }
      
      console.log('Video faylı tapıldı:', {
        hls_master_playlist_url: video.hls_master_playlist_url,
        hls_variants: video.hls_variants
      });
      
      this.videoFile = video;
      
      // Digər media faylları
      this.otherMediaFiles = this.currentLesson.media_files.filter(
        file => file.type !== 'video'
      );
    },
    
    handleVideoError(error) {
      console.error('Video player xətası:', error);
      this.error = 'Video oynatıla bilmədi';
    },
    
    getToken() {
      return localStorage.getItem('token') || '';
    }
  }
};
</script>

<style scoped>
.training-start {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
}

.loading-container,
.error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 400px;
  gap: 20px;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #3498db;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.video-section {
  margin: 20px 0;
}

.lesson-info {
  margin-bottom: 20px;
}

.media-files {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 15px;
  margin-top: 20px;
}

.media-item img {
  width: 100%;
  height: auto;
  border-radius: 8px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>
```

---

## ⚠️ Xəta İdarəetməsi

### Ümumi Xətalar və Həlləri

#### 1. "Video faylı tapılmadı"

**Səbəb:** `hls_master_playlist_url` yoxdur

**Həll:**
```javascript
if (!videoFile.hls_master_playlist_url) {
  console.error('HLS master playlist URL tapılmadı');
  // Fallback: signed_url istifadə etməyin, xəta gösterin
  this.error = 'Video faylı hazır deyil';
  return;
}
```

#### 2. "Video yüklənə bilmədi"

**Səbəb:** Signed URL expire olub və ya şəbəkə xətası

**Həll:**
```javascript
// Signed URL-in expire olub-olmadığını yoxla
const expiresAt = new Date(videoFile.signed_url_expires_at);
if (new Date() > expiresAt) {
  // Training-i yenidən yüklə (yeni signed URL al)
  await this.loadTraining();
}
```

#### 3. "HLS is not supported"

**Səbəb:** Köhnə brauzer

**Həll:**
```javascript
if (!Hls.isSupported() && !video.canPlayType('application/vnd.apple.mpegurl')) {
  this.error = 'Brauzeriniz video formatını dəstəkləmir';
  // Fallback: MP4 faylı göstər (əgər varsa)
}
```

---

## 📋 Xülasə: İstifadə Qaydaları

### ✅ DOĞRU İstifadə

1. **HLS Master Playlist URL istifadə edin:**
   ```javascript
   const hlsUrl = videoFile.hls_master_playlist_url;
   ```

2. **Video faylını düzgün tapın:**
   ```javascript
   const videoFile = lesson.media_files.find(f => f.type === 'video');
   ```

3. **HLS variants məlumatlarını oxuyun:**
   ```javascript
   const variants = videoFile.hls_variants;
   ```

### ❌ YANLIŞ İstifadə

1. **`signed_url` istifadə etməyin** (MP4 üçündür, HLS deyil!)
2. **`url` istifadə etməyin** (Authentication tələb edir)
3. **HLS məlumatlarını yoxlamadan video oynatmağa çalışmayın**

---

## 🔗 Əlavə Resurslar

- [HLS.js Dokumentasiyası](https://github.com/video-dev/hls.js/)
- [Video.js HLS Quality Selector](https://github.com/chrisboustead/videojs-hls-quality-selector)
- [MDN Video Element](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/video)

---

## 📞 Dəstək

Əgər problem yaşayırsınızsa:

1. Browser console-da xətaları yoxlayın
2. Network tab-da request-ləri yoxlayın
3. `hls_master_playlist_url` və `hls_variants` məlumatlarını console-da göstərin
4. Backend log-larını yoxlayın

---

**Son yeniləmə:** 2025-11-26

