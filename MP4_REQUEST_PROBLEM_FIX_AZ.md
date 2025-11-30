# MP4 Request Problemi və Həlli - Azərbaycan Dili

## 🔴 Problem

Network tab-da görünür ki, frontend MP4 faylına request göndərir:

```
GET /api/v1/modules/2/lessons/5/media/download?path=lessons%2F5%2FQ02HEboK7USDjNp3i9VmZNlt8SHMduafGjfMsu8I.mp4&signature=...&expires=...
```

**Status:** `206 Partial Content` və ya `(canceled)`

**Problem:** Frontend hələ də **HLS playlist** istifadə etmir, əvəzinə **MP4 faylı** yükləməyə çalışır!

---

## ⚠️ Niyə Problem Yaranır?

### 1. Backend Təhlükəsizlik Yoxlaması

Backend-də MP4 faylları üçün təhlükəsizlik yoxlaması var:

```php
// LessonMediaController.php - Line 63-82

if ($isSignedUrl && $isVideoFile && !$isHLSPlaylistFile) {
    $hasRangeHeader = $request->hasHeader('Range');
    $refererMatches = $referer && str_starts_with($referer, $allowedDomain);
    
    // Əgər Range header YOXDURSA VƏ Referer düzgün deyilsə → 403 Forbidden
    if (!$hasRangeHeader && !$refererMatches) {
        return response()->json([
            'message' => 'Access denied. Videos can only be viewed in the training player.',
            'error' => 'direct_access_not_allowed'
        ], 403);
    }
}
```

### 2. Frontend Yanlış URL İstifadə Edir

**YANLIŞ:**
```javascript
// ❌ MP4 faylı
const videoUrl = videoFile.signed_url;
// http://localhost:8000/api/v1/modules/2/lessons/5/media/download?path=lessons%2F5%2Fvideo.mp4&signature=...

video.src = videoUrl;  // Bu işləməyəcək!
```

**DOĞRU:**
```javascript
// ✅ HLS Master Playlist
const hlsUrl = videoFile.hls_master_playlist_url;
// http://localhost:8000/api/v1/modules/2/lessons/5/media/download?path=lessons%2F5%2Fhls%2Fmaster.m3u8&signature=...

hls.loadSource(hlsUrl);  // Bu işləyəcək!
```

---

## 🔍 Network Tab-da Nə Görürsünüz?

### Problemli Request-lər:

```
1. download?path=...video.mp4  → (canceled)  → 0.0 kB  → 16 ms
2. download?path=...video.mp4  → 206        → 1,246 kB → 636 ms
3. download?path=...video.mp4  → (canceled)  → 0.0 kB  → 6 ms
4. download?path=...video.mp4  → 206        → 1,033 kB → 373 ms
5. download?path=...video.mp4  → (canceled)  → 0.0 kB  → 5 ms
6. download?path=...video.mp4  → (pending)  → 0.0 kB  → Pending
```

**Nə deməkdir:**
- `206 Partial Content` = Backend Range request-ləri dəstəkləyir, amma bu HLS deyil!
- `(canceled)` = Request ləğv edilib, çünki video player başa düşür ki, bu düzgün format deyil
- `(pending)` = Request gözləyir, amma heç vaxt tamamlanmayacaq

### Gözlənilən Request-lər (HLS):

```
1. download?path=...master.m3u8  → 200 → 200 bytes  → 0.1s
2. download?path=...720p.m3u8     → 200 → 1 KB       → 0.1s
3. download?path=...segment_000.ts → 206 → 2 MB      → 0.5s
4. download?path=...segment_001.ts → 206 → 2 MB      → 0.5s
5. download?path=...segment_002.ts → 206 → 2 MB      → 0.5s
...
```

---

## ✅ Həll: Frontend-də Düzəliş

### Adım 1: Video Faylını Düzgün Tapın

```javascript
// TrainingStart.vue - findVideoFile() metodunda

findVideoFile() {
  const currentLesson = this.findCurrentLesson();
  if (!currentLesson) return null;
  
  const videoFile = currentLesson.media_files?.find(
    file => file.type === 'video'
  );
  
  if (!videoFile) {
    console.error('Video faylı tapılmadı');
    return null;
  }
  
  // ✅ ƏHƏMİYYƏTLİ: hls_master_playlist_url yoxla
  if (!videoFile.hls_master_playlist_url) {
    console.error('❌ HLS master playlist URL tapılmadı!');
    console.log('Video faylı:', videoFile);
    
    // ⚠️ signed_url istifadə etməyin!
    // Bu MP4 faylıdır və işləməyəcək!
    return null;
  }
  
  console.log('✅ HLS master playlist URL tapıldı:', videoFile.hls_master_playlist_url);
  
  this.videoFile = videoFile;
  return videoFile;
}
```

### Adım 2: Video Player-da HLS İstifadə Edin

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
  
  console.log('🎬 HLS URL:', hlsUrl);
  
  // hls.js ilə yüklə
  if (Hls.isSupported()) {
    this.hls = new Hls({
      enableWorker: true,
      lowLatencyMode: false
    });
    
    // ✅ HLS Master Playlist URL
    this.hls.loadSource(hlsUrl);
    this.hls.attachMedia(video);
    
    // Event listener-lar
    this.hls.on(Hls.Events.MANIFEST_PARSED, () => {
      console.log('✅ HLS Manifest yükləndi');
      this.isManifestLoaded = true;
      this.isLoading = false;
    });
    
    this.hls.on(Hls.Events.ERROR, (event, data) => {
      console.error('❌ HLS Xətası:', data);
      if (data.fatal) {
        this.hasError = true;
        this.errorMessage = 'Video yüklənə bilmədi';
      }
    });
  } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
    // Safari native HLS
    video.src = hlsUrl;
  } else {
    this.hasError = true;
    this.errorMessage = 'Brauzeriniz HLS-ni dəstəkləmir';
  }
}
```

---

## 🔧 Backend-də Niyə Bu Yoxlama Var?

### Təhlükəsizlik Səbəbi

Backend MP4 fayllarına birbaşa girişi məhdudlaşdırır, çünki:

1. **Video-nun endirilməsinin qarşısını almaq:** İstifadəçi video-nu endirə bilməsin
2. **Bandwidth qənaəti:** Yalnız video player-dan gələn request-lərə icazə ver
3. **Təhlükəsizlik:** Video-nun başqa saytlarda istifadə olunmasının qarşısını almaq

### Range Header Nədir?

Video player-lar video-nu yükləyərkən `Range` header göndərir:

```
Range: bytes=0-1023
```

Bu o deməkdir ki, video-nun yalnız **müəyyən bir hissəsini** istəyirik. Backend bunu görür və anlayır ki, bu video player-dan gələn request-dir.

### Referer Header Nədir?

`Referer` header browser tərəfindən avtomatik göndərilir və hansı səhifədən gəldiyinizi göstərir:

```
Referer: http://localhost:3000/trainings/2/lessons/5
```

Backend bunu yoxlayır və yalnız frontend domain-indən gələn request-lərə icazə verir.

---

## 🐛 Debugging

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
      
      // ✅ Bu olmalıdır
      hls_master_playlist_url: videoFile.hls_master_playlist_url,
      hls_variants: videoFile.hls_variants,
      
      // ⚠️ Bu istifadə edilməməlidir
      signed_url: videoFile.signed_url,
    });
    
    // Yoxla
    if (!videoFile.hls_master_playlist_url) {
      console.error('❌ HLS master playlist URL yoxdur!');
      console.error('⚠️ signed_url istifadə etməyin - bu MP4 faylıdır və işləməyəcək!');
    } else {
      console.log('✅ HLS master playlist URL mövcuddur');
      console.log('✅ HLS variants:', Object.keys(videoFile.hls_variants || {}));
    }
  }
}
```

### Network Tab-da Yoxlayın

1. **Yanlış Request (MP4):**
   ```
   Name: download?path=...video.mp4
   Status: 206 / (canceled)
   Type: media
   ```

2. **Doğru Request (HLS):**
   ```
   Name: download?path=...master.m3u8
   Status: 200
   Type: media
   
   Name: download?path=...720p.m3u8
   Status: 200
   Type: media
   
   Name: download?path=...segment_000.ts
   Status: 206
   Type: media
   ```

---

## 📋 Yoxlama Siyahısı

Video player düzgün işləmək üçün:

- [ ] `hls_master_playlist_url` mövcuddur
- [ ] `hls_variants` mövcuddur və boş deyil
- [ ] Video player `hls_master_playlist_url` istifadə edir
- [ ] `signed_url` (MP4) istifadə edilmir
- [ ] Network tab-da `.m3u8` və `.ts` faylları görünür
- [ ] Network tab-da `.mp4` faylı görünmür (və ya canceled)

---

## 🎯 Tez Həll

Əgər tez düzəltmək istəyirsinizsə:

1. **TrainingStart.vue** faylında `findVideoFile()` metodunu tapın
2. `hls_master_playlist_url` yoxlaması əlavə edin
3. `signed_url` istifadəsini silin
4. **VideoPlayer.vue** component-də `hls_master_playlist_url` istifadə edin
5. Network tab-da `.m3u8` request-lərinin göründüyünü yoxlayın

---

## 🔗 Əlavə Məlumat

- **HLS Streaming Necə İşləyir:** `HLS_STREAMING_HOW_IT_WORKS_AZ.md`
- **Video Keyfiyyət Seçimi:** `VIDEO_QUALITY_SELECTION_FRONTEND_GUIDE_AZ.md`
- **Video Player Guide:** `HLS_VIDEO_PLAYER_GUIDE.md`

---

**Son yeniləmə:** 2025-11-26

