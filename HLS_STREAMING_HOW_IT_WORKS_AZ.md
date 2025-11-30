# HLS Streaming - Video Necə Göndərilir? (Azərbaycan Dili)

## ❓ Sual

**Video keyfiyyətindən asılı olmayaraq, backend frontend-ə bütün videonu göndərir, yoxsa hissə-hissə göndərir?**

## ✅ Cavab: **HİSSƏ-HİSSƏ GÖNDƏRİLİR!**

Backend **bütün videonu bir dəfədə göndərmir**. HLS (HTTP Live Streaming) texnologiyası istifadə olunur və video **kiçik segmentlərə bölünür**, sonra frontend bu segmentləri **ardıcıl olaraq yükləyir**.

---

## 🎬 HLS Streaming Necə İşləyir?

### 1. Video Segmentlərə Bölünür

Video upload zamanı backend video-nu kiçik segmentlərə (.ts faylları) bölür:

```
video.mp4 (17 MB)
    ↓
    ├── segment_000.ts (2 MB)
    ├── segment_001.ts (2 MB)
    ├── segment_002.ts (2 MB)
    ├── segment_003.ts (2 MB)
    ├── segment_004.ts (2 MB)
    ├── segment_005.ts (2 MB)
    ├── segment_006.ts (2 MB)
    └── segment_007.ts (3 MB)
```

### 2. Hər Keyfiyyət Üçün Ayrı Segmentlər

Hər keyfiyyət (480p, 720p, 1080p) üçün ayrı segmentlər yaradılır:

```
lessons/5/hls/
    ├── master.m3u8                    (Master playlist)
    ├── 480p/
    │   ├── 480p.m3u8                   (480p playlist)
    │   ├── segment_000.ts             (480p segment 1)
    │   ├── segment_001.ts              (480p segment 2)
    │   └── ...
    ├── 720p/
    │   ├── 720p.m3u8                   (720p playlist)
    │   ├── segment_000.ts              (720p segment 1)
    │   ├── segment_001.ts              (720p segment 2)
    │   └── ...
    └── 1080p/
        ├── 1080p.m3u8                  (1080p playlist)
        ├── segment_000.ts              (1080p segment 1)
        ├── segment_001.ts              (1080p segment 2)
        └── ...
```

---

## 📡 Frontend Necə Video Yükləyir?

### Adım 1: Master Playlist Yüklənir

Frontend əvvəlcə **master playlist** (.m3u8) faylını yükləyir:

```javascript
// Frontend request
GET /api/v1/modules/2/lessons/5/media/download?path=lessons%2F5%2Fhls%2Fmaster.m3u8&signature=...&expires=...

// Backend response (master.m3u8 məzmunu)
#EXTM3U
#EXT-X-VERSION:3
#EXT-X-STREAM-INF:BANDWIDTH=500000,RESOLUTION=854x480
480p.m3u8
#EXT-X-STREAM-INF:BANDWIDTH=1000000,RESOLUTION=1280x720
720p.m3u8
#EXT-X-STREAM-INF:BANDWIDTH=2000000,RESOLUTION=1920x1080
1080p.m3u8
```

**Ölçü:** ~200-500 bytes (çox kiçik!)

### Adım 2: Variant Playlist Yüklənir

Frontend şəbəkə sürətinə görə uyğun variant playlist-i seçir və yükləyir:

```javascript
// Frontend request (məsələn 720p seçildi)
GET /api/v1/modules/2/lessons/5/media/download?path=lessons%2F5%2Fhls%2F720p.m3u8&signature=...&expires=...

// Backend response (720p.m3u8 məzmunu)
#EXTM3U
#EXT-X-VERSION:3
#EXT-X-TARGETDURATION:10
#EXTINF:10.0,
segment_000.ts
#EXTINF:10.0,
segment_001.ts
#EXTINF:10.0,
segment_002.ts
#EXTINF:10.0,
segment_003.ts
...
```

**Ölçü:** ~1-2 KB (çox kiçik!)

### Adım 3: Video Segmentləri Ardıcıl Yüklənir

Frontend playlist-dəki segmentləri **ardıcıl olaraq** yükləyir:

```javascript
// Segment 1
GET /api/v1/modules/2/lessons/5/media/download?path=lessons%2F5%2Fhls%2F720p%2Fsegment_000.ts&signature=...&expires=...
// Response: 2 MB video segment

// Segment 2 (Segment 1 bitdikdən sonra)
GET /api/v1/modules/2/lessons/5/media/download?path=lessons%2F5%2Fhls%2F720p%2Fsegment_001.ts&signature=...&expires=...
// Response: 2 MB video segment

// Segment 3
GET /api/v1/modules/2/lessons/5/media/download?path=lessons%2F5%2Fhls%2F720p%2Fsegment_002.ts&signature=...&expires=...
// Response: 2 MB video segment

// ... və s.
```

**Hər segment:** ~2-3 MB (video-nun kiçik bir hissəsi)

---

## 🔄 Real-Time Streaming Prosesi

### Timeline

```
T=0s    → Master playlist yüklənir (200 bytes)
T=0.1s  → 720p playlist yüklənir (1 KB)
T=0.2s  → Segment 1 yüklənir (2 MB) → Video oynatma başlayır
T=2s    → Segment 2 yüklənir (2 MB) → Video davam edir
T=4s    → Segment 3 yüklənir (2 MB) → Video davam edir
T=6s    → Segment 4 yüklənir (2 MB) → Video davam edir
...
```

### Adaptive Streaming

Əgər şəbəkə yavaşdırsa, frontend avtomatik olaraq **aşağı keyfiyyətə** keçir:

```
T=0s    → 720p playlist seçildi
T=2s    → Segment 1 yüklənir (yavaş)
T=5s    → Segment 2 hələ yüklənməyib → 480p-ə keçir
T=5.1s  → 480p playlist yüklənir
T=5.2s  → 480p Segment 1 yüklənir (daha sürətli)
```

---

## 💾 Backend-də Necə İşləyir?

### Range Request Dəstəyi

Backend **Range request**-ləri dəstəkləyir. Bu o deməkdir ki, frontend video-nun yalnız **müəyyən bir hissəsini** istəyə bilər:

```php
// LessonMediaController.php - Range request handler

if ($range) {
    // Parse Range header (e.g., "bytes=0-1023")
    if (preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
        $start = intval($matches[1]);
        $end = $matches[2] === '' ? $fileSize - 1 : intval($matches[2]);
        $length = $end - $start + 1;
        
        // Yalnız tələb olunan hissəni göndər
        return response()->stream(function () use ($fullPath, $start, $length) {
            $stream = fopen($fullPath, 'rb');
            fseek($stream, $start);  // Müəyyən pozisiyaya atla
            $remaining = $length;
            $chunkSize = 8192;  // 8 KB chunk-larla göndər
            
            while ($remaining > 0 && !feof($stream)) {
                $read = min($remaining, $chunkSize);
                echo fread($stream, $read);
                $remaining -= $read;
                flush();  // Hər chunk-dan sonra göndər
            }
            fclose($stream);
        }, 206, [  // 206 = Partial Content
            'Content-Range' => "bytes {$start}-{$end}/{$fileSize}",
            'Content-Length' => $length,
            'Accept-Ranges' => 'bytes'
        ]);
    }
}
```

### HLS Playlist Transformasiyası

Backend HLS playlist fayllarını (.m3u8) oxuyur və daxilindəki segment URL-lərini **signed URL-lərə** çevirir:

```php
// LessonMediaController.php - HLS playlist transformation

if ($isM3U8) {
    $content = Storage::disk($disk)->get($filePath);
    $lines = explode("\n", $content);
    
    foreach ($lines as $line) {
        // Əgər bu .ts segment faylıdırsa
        if (str_ends_with($trimmedLine, '.ts')) {
            // Signed URL yarat və əvəz et
            $signedUrl = self::generateSignedUrl(...);
            $transformedLines[] = $signedUrl;
        } else {
            $transformedLines[] = $line;
        }
    }
    
    return response($transformedContent, 200, [
        'Content-Type' => 'application/vnd.apple.mpegurl'
    ]);
}
```

---

## 📊 Məlumat Trafikinin Müqayisəsi

### ❌ Bütün Video Bir Dəfədə Göndərmək (YANLIŞ)

```
Frontend Request:
GET /video.mp4

Backend Response:
[17 MB video faylı bir dəfədə göndərilir]
├── Yükləmə müddəti: 10-30 saniyə (şəbəkəyə görə)
├── Yaddaş istifadəsi: 17 MB RAM
├── Başlanğıc gecikmə: 10-30 saniyə
└── Problem: Video-nun sonuna atlamaq mümkün deyil
```

### ✅ HLS Segmentləri (DOĞRU)

```
Frontend Request 1:
GET /master.m3u8
Response: 200 bytes (0.001 saniyə)

Frontend Request 2:
GET /720p.m3u8
Response: 1 KB (0.01 saniyə)

Frontend Request 3:
GET /segment_000.ts
Response: 2 MB (0.5 saniyə) → Video oynatma başlayır!

Frontend Request 4:
GET /segment_001.ts
Response: 2 MB (0.5 saniyə) → Video davam edir

...
├── Yükləmə müddəti: Hissə-hissə (hər segment 0.5 saniyə)
├── Yaddaş istifadəsi: ~4-6 MB RAM (2-3 segment buffer)
├── Başlanğıc gecikmə: 0.5-1 saniyə
└── Üstünlük: Video-nun istənilən yerinə atlaya bilərsiniz
```

---

## 🎯 Üstünlüklər

### 1. **Tez Başlanğıc**
- Video-nun tamamını gözləmək lazım deyil
- İlk segment yüklənən kimi oynatma başlayır

### 2. **Adaptive Streaming**
- Şəbəkə sürətinə görə keyfiyyət avtomatik dəyişir
- Yavaş şəbəkədə aşağı keyfiyyət, sürətli şəbəkədə yüksək keyfiyyət

### 3. **Seek (Atlama) Dəstəyi**
- Video-nun istənilən yerinə atlaya bilərsiniz
- Backend yalnız lazım olan segmenti göndərir

### 4. **Yaddaş Qənaəti**
- Bütün video RAM-də saxlanmır
- Yalnız 2-3 segment buffer-da saxlanır

### 5. **Bandwidth Qənaəti**
- İstifadəçi video-nu bitirməsə belə, yalnız izlədiyi hissələr yüklənir
- Bandwidth qənaət edilir

---

## 🔍 Network Tab-da Nə Görürsünüz?

Browser Developer Tools-da Network tab-da bunları görəcəksiniz:

```
1. master.m3u8          → 200 bytes   (0.1s)
2. 720p.m3u8            → 1 KB        (0.1s)
3. segment_000.ts        → 2 MB        (0.5s)  ← Video başlayır
4. segment_001.ts       → 2 MB        (0.5s)
5. segment_002.ts       → 2 MB        (0.5s)
6. segment_003.ts       → 2 MB        (0.5s)
...
```

**Qeyd:** Hər segment **ayrıca HTTP request**-dir!

---

## 📝 Kod Nümunəsi: Frontend-də Necə İşləyir?

### hls.js İstifadəsi

```javascript
import Hls from 'hls.js';

const hls = new Hls();
hls.loadSource('http://...master.m3u8');  // Master playlist
hls.attachMedia(videoElement);

// hls.js avtomatik olaraq:
// 1. Master playlist-i yükləyir
// 2. Uyğun variant playlist-i seçir
// 3. Segmentləri ardıcıl yükləyir
// 4. Video-nu oynatır
```

### Network Request-ləri

hls.js avtomatik olaraq bu request-ləri göndərir:

```javascript
// 1. Master playlist
fetch('http://...master.m3u8')
  .then(response => response.text())
  .then(playlist => {
    // Playlist-i parse et
    // Variant playlist-i seç
    loadVariantPlaylist('720p.m3u8');
  });

// 2. Variant playlist
fetch('http://...720p.m3u8')
  .then(response => response.text())
  .then(playlist => {
    // Segmentlərin siyahısını al
    // Segmentləri yüklə
    loadSegment('segment_000.ts');
  });

// 3. Segmentlər
fetch('http://...segment_000.ts')
  .then(response => response.arrayBuffer())
  .then(data => {
    // Segment-i video player-a ver
    videoElement.appendBuffer(data);
    // Növbəti segment-i yüklə
    loadSegment('segment_001.ts');
  });
```

---

## 🎬 Real-World Nümunə

### 10 dəqiqəlik video (100 MB)

**Bütün video bir dəfədə:**
- Yükləmə müddəti: 30-60 saniyə
- Başlanğıc gecikmə: 30-60 saniyə
- RAM istifadəsi: 100 MB

**HLS segmentləri:**
- İlk segment: 2 MB (0.5 saniyə) → Video başlayır!
- Hər segment: 2 MB (0.5 saniyə)
- RAM istifadəsi: ~6 MB (3 segment buffer)
- Başlanğıc gecikmə: 0.5 saniyə

---

## ✅ Xülasə

| Xüsusiyyət | Bütün Video | HLS Segmentləri |
|------------|-------------|----------------|
| **Başlanğıc gecikmə** | 10-60 saniyə | 0.5-1 saniyə |
| **RAM istifadəsi** | Tam video ölçüsü | 2-3 segment |
| **Seek dəstəyi** | ❌ Yox | ✅ Var |
| **Adaptive streaming** | ❌ Yox | ✅ Var |
| **Bandwidth qənaəti** | ❌ Yox | ✅ Var |
| **Network request sayı** | 1 | 10-100+ |

---

## 🎯 Nəticə

**Backend frontend-ə bütün videonu göndərmir!**

✅ **HLS texnologiyası** istifadə olunur
✅ Video **kiçik segmentlərə** bölünür
✅ Frontend segmentləri **ardıcıl olaraq** yükləyir
✅ Hər segment **ayrıca HTTP request**-dir
✅ Yalnız **lazım olan segmentlər** yüklənir
✅ **Adaptive streaming** dəstəklənir
✅ **Seek (atlama)** dəstəklənir

Bu yanaşma **daha sürətli**, **daha qənaətli** və **daha yaxşı istifadəçi təcrübəsi** təmin edir!

---

**Son yeniləmə:** 2025-11-26

