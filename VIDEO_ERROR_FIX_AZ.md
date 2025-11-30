# Video Xətasının Düzəldilməsi - Azərbaycan Dili

## 🔴 Cari Xəta

```
Video error: MediaError
Video error message: Video faylı tapılmadı
```

**Xətanın səbəbi:** Frontend-də video player `signed_url` (MP4 faylı) istifadə edir, lakin HLS formatı üçün `hls_master_playlist_url` istifadə edilməlidir.

---

## ✅ Həll

### Problem 1: Yanlış URL İstifadəsi

**YANLIŞ:**
```javascript
// ❌ Bu MP4 faylı üçündür, HLS deyil!
const videoUrl = videoFile.signed_url;
video.src = videoUrl;
```

**DOĞRU:**
```javascript
// ✅ HLS master playlist URL istifadə edin
const hlsUrl = videoFile.hls_master_playlist_url;

if (!hlsUrl) {
  console.error('HLS master playlist URL tapılmadı');
  return;
}

// hls.js ilə yükləyin
hls.loadSource(hlsUrl);
hls.attachMedia(video);
```

---

## 📝 TrainingStart.vue Düzəlişi

### Adım 1: Video Faylını Düzgün Tapın

```javascript
// TrainingStart.vue - findVideoFile() metodunda

findVideoFile() {
  const currentLesson = this.findCurrentLesson();
  if (!currentLesson) return null;
  
  // Video faylını tap
  const videoFile = currentLesson.media_files?.find(
    file => file.type === 'video'
  );
  
  if (!videoFile) {
    console.error('Video faylı tapılmadı');
    return null;
  }
  
  // ✅ ƏHƏMİYYƏTLİ: hls_master_playlist_url yoxla
  if (!videoFile.hls_master_playlist_url) {
    console.error('HLS master playlist URL tapılmadı');
    console.log('Video faylı:', videoFile);
    return null;
  }
  
  // ✅ HLS variants məlumatlarını yoxla
  if (!videoFile.hls_variants || Object.keys(videoFile.hls_variants).length === 0) {
    console.warn('HLS variants tapılmadı');
  }
  
  console.log('✅ Video faylı tapıldı:', {
    hls_master_playlist_url: videoFile.hls_master_playlist_url,
    hls_variants: videoFile.hls_variants
  });
  
  this.videoFile = videoFile;
  return videoFile;
}
```

### Adım 2: Video Player-da HLS URL İstifadə Edin

```javascript
// VideoPlayer.vue - initPlayer() metodunda

initPlayer() {
  const video = this.$refs.videoElement;
  if (!video || !this.videoFile) return;
  
  // ✅ DOĞRU: hls_master_playlist_url istifadə edin
  const hlsUrl = this.videoFile.hls_master_playlist_url;
  
  // ❌ YANLIŞ: signed_url istifadə etməyin!
  // const wrongUrl = this.videoFile.signed_url; // BU YANLIŞDIR!
  
  if (!hlsUrl) {
    console.error('HLS master playlist URL tapılmadı');
    this.hasError = true;
    this.errorMessage = 'Video faylı tapılmadı';
    return;
  }
  
  // hls.js ilə yüklə
  if (Hls.isSupported()) {
    this.hls = new Hls();
    this.hls.loadSource(hlsUrl); // ✅ HLS URL
    this.hls.attachMedia(video);
    // ... qalan kod
  }
}
```

---

## 🔍 Debugging

### Console-da Yoxlayın

```javascript
// TrainingStart.vue - loadTraining() metodunda

async loadTraining() {
  // ... API request
  
  const data = await response.json();
  this.training = data;
  
  // Debug: Video məlumatlarını göstər
  const lesson = this.findCurrentLesson();
  const videoFile = lesson?.media_files?.find(f => f.type === 'video');
  
  if (videoFile) {
    console.log('📹 Video Faylı:', {
      type: videoFile.type,
      hls_master_playlist_url: videoFile.hls_master_playlist_url,
      hls_variants: videoFile.hls_variants,
      signed_url: videoFile.signed_url, // ⚠️ Bu istifadə edilməməlidir
    });
    
    // Yoxla
    if (!videoFile.hls_master_playlist_url) {
      console.error('❌ HLS master playlist URL yoxdur!');
    } else {
      console.log('✅ HLS master playlist URL mövcuddur');
    }
  }
}
```

---

## 🌐 Locale Xətalarının Düzəldilməsi

### Problem 2: Locale Key-ləri Yoxdur

```
[intlify] Not found 'pages.trainingStart.video.loadFailed' key in 'az' locale messages.
[intlify] Not found 'pages.trainingStart.video.fileNotFound' key in 'az' locale messages.
```

**Həll:** Locale faylına key-ləri əlavə edin:

```json
// locales/az.json
{
  "pages": {
    "trainingStart": {
      "video": {
        "loadFailed": "Video yüklənə bilmədi",
        "fileNotFound": "Video faylı tapılmadı",
        "loading": "Video yüklənir...",
        "error": "Video oynatıla bilmədi"
      }
    }
  }
}
```

Və ya Vue component-də:

```javascript
// TrainingStart.vue
methods: {
  getErrorMessage(error) {
    // Locale yoxdursa, default mesajlar
    return this.$t('pages.trainingStart.video.loadFailed', 'Video yüklənə bilmədi');
  }
}
```

---

## 📋 Yoxlama Siyahısı

Video player düzgün işləmək üçün:

- [ ] `hls_master_playlist_url` mövcuddur
- [ ] `hls_variants` mövcuddur və boş deyil
- [ ] Video player `hls_master_playlist_url` istifadə edir (signed_url deyil!)
- [ ] hls.js düzgün quraşdırılıb
- [ ] Browser HLS dəstəkləyir
- [ ] CORS düzgün konfiqurasiya edilib
- [ ] Signed URL expire olmayıb

---

## 🎯 Tez Həll

Əgər tez düzəltmək istəyirsinizsə:

1. **TrainingStart.vue** faylında `findVideoFile()` metodunu tapın
2. `hls_master_playlist_url` yoxlaması əlavə edin
3. VideoPlayer component-də `hls_master_playlist_url` istifadə edin
4. `signed_url` istifadəsini silin

---

**Ətraflı təlimat üçün:** `VIDEO_QUALITY_SELECTION_FRONTEND_GUIDE_AZ.md` faylına baxın.

