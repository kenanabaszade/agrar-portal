# Video Optimallaşdırma - Backend Tapşırıqları

## 🎯 Məqsəd
Video yüklənmə performansını **10-20x** artırmaq. Hazırkı vəziyyətdə 16MB video ~15-30 saniyə yüklənir, optimallaşdırmadan sonra **3-5 saniyə** yüklənməlidir.

## 📋 Tapşırıqlar Prioritet Sırası ilə

### 🔴 YÜKSƏK PRIORİTET (1-2 həftə)

#### Tapşırıq 1: Video Compression (FFmpeg)
**Məqsəd:** Video ölçüsünü 70-80% azaltmaq keyfiyyəti saxlayaraq

**Tələblər:**
- Video upload zamanı avtomatik sıxışdırma
- H.264 codec istifadə etmək
- Target bitrate: 1000-1500 kbps (1080p üçün)
- Keyfiyyət: CRF 23-28 (yaxşı balans)

**Laravel Implementation:**
```php
// 1. Composer package əlavə et
// composer require php-ffmpeg/php-ffmpeg

// 2. Config faylı yarat: config/ffmpeg.php
<?php
return [
    'ffmpeg' => [
        'binaries' => [
            'ffmpeg' => env('FFMPEG_BIN', '/usr/bin/ffmpeg'),
            'ffprobe' => env('FFPROBE_BIN', '/usr/bin/ffprobe'),
        ],
        'threads' => 12,
        'timeout' => 3600,
    ],
];

// 3. Service Provider yarat: app/Services/VideoCompressionService.php
<?php

namespace App\Services;

use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class VideoCompressionService
{
    private $ffmpeg;
    
    public function __construct()
    {
        $this->ffmpeg = FFMpeg::create([
            'ffmpeg.binaries' => config('ffmpeg.ffmpeg.binaries.ffmpeg'),
            'ffprobe.binaries' => config('ffmpeg.ffmpeg.binaries.ffprobe'),
            'timeout' => config('ffmpeg.ffmpeg.timeout'),
            'ffmpeg.threads' => config('ffmpeg.ffmpeg.threads'),
        ]);
    }
    
    /**
     * Video-nu sıxışdır
     * 
     * @param string $inputPath Original video path
     * @param string $outputPath Compressed video path
     * @param array $options Compression options
     * @return string Compressed video path
     */
    public function compressVideo(string $inputPath, string $outputPath, array $options = []): string
    {
        try {
            $video = $this->ffmpeg->open($inputPath);
            
            // Video format və codec seçimi
            $format = new X264('libmp3lame', 'libx264');
            
            // Bitrate təyin et (default: 1000 kbps)
            $bitrate = $options['bitrate'] ?? 1000;
            $format->setKiloBitrate($bitrate);
            
            // CRF (Constant Rate Factor) - keyfiyyət üçün
            $crf = $options['crf'] ?? 23;
            $format->setAdditionalParameters(['-crf', $crf]);
            
            // Two-pass encoding (daha yaxşı keyfiyyət)
            if ($options['two_pass'] ?? false) {
                return $this->twoPassEncoding($video, $format, $outputPath);
            }
            
            // Single-pass encoding (daha sürətli)
            $video->save($format, $outputPath);
            
            Log::info('Video compressed successfully', [
                'input' => $inputPath,
                'output' => $outputPath,
                'original_size' => filesize($inputPath),
                'compressed_size' => filesize($outputPath),
            ]);
            
            return $outputPath;
            
        } catch (\Exception $e) {
            Log::error('Video compression failed', [
                'error' => $e->getMessage(),
                'input' => $inputPath,
            ]);
            throw $e;
        }
    }
    
    /**
     * Video-nu müxtəlif keyfiyyətdə variantlara böl (HLS üçün)
     */
    public function createMultipleQualities(string $inputPath, string $outputDir): array
    {
        $qualities = [
            '480p' => ['width' => 854, 'height' => 480, 'bitrate' => 500],
            '720p' => ['width' => 1280, 'height' => 720, 'bitrate' => 1000],
            '1080p' => ['width' => 1920, 'height' => 1080, 'bitrate' => 2000],
        ];
        
        $outputs = [];
        
        foreach ($qualities as $quality => $config) {
            $video = $this->ffmpeg->open($inputPath);
            
            // Video ölçüsünü dəyişdir
            $video->filters()
                ->resize(new \FFMpeg\Coordinate\Dimension($config['width'], $config['height']))
                ->synchronize();
            
            // Format və bitrate
            $format = new X264('libmp3lame', 'libx264');
            $format->setKiloBitrate($config['bitrate']);
            
            $outputPath = $outputDir . "/{$quality}.mp4";
            $video->save($format, $outputPath);
            
            $outputs[$quality] = $outputPath;
        }
        
        return $outputs;
    }
    
    private function twoPassEncoding($video, $format, $outputPath): string
    {
        // First pass
        $video->filters()->custom('-pass', '1');
        $video->save($format, $outputPath . '.pass1');
        
        // Second pass
        $video->filters()->custom('-pass', '2');
        $video->save($format, $outputPath);
        
        // Cleanup
        @unlink($outputPath . '.pass1');
        
        return $outputPath;
    }
}

// 4. Controller-də istifadə: app/Http/Controllers/MediaController.php
public function uploadVideo(Request $request)
{
    $file = $request->file('video');
    $originalPath = $file->store('temp', 'local');
    $fullPath = Storage::disk('local')->path($originalPath);
    
    // Compress video
    $compressionService = new VideoCompressionService();
    $compressedPath = storage_path('app/videos/compressed/' . basename($originalPath));
    
    $compressedPath = $compressionService->compressVideo(
        $fullPath,
        $compressedPath,
        [
            'bitrate' => 1000,
            'crf' => 23,
            'two_pass' => false, // true for better quality, false for speed
        ]
    );
    
    // Original faylı sil
    Storage::disk('local')->delete($originalPath);
    
    // Compressed video-nu storage-a köçür
    $finalPath = Storage::disk('public')->putFile('videos', new \Illuminate\Http\File($compressedPath));
    
    return response()->json([
        'path' => $finalPath,
        'original_size' => $file->getSize(),
        'compressed_size' => filesize($compressedPath),
        'compression_ratio' => round((1 - filesize($compressedPath) / $file->getSize()) * 100, 2) . '%',
    ]);
}
```

**Test:**
```bash
# FFmpeg quraşdırma (Ubuntu/Debian)
sudo apt-get update
sudo apt-get install ffmpeg

# Test
ffmpeg -version
```

**Gözlənilən Nəticə:**
- 16MB video → 4-6MB (70-80% azalma)
- Yüklənmə vaxtı: 15-30s → 3-5s (5-10x sürətli)

---

#### Tapşırıq 2: Video Thumbnail Generation
**Məqsəd:** Video yüklənərkən thumbnail göstərmək

**Implementation:**
```php
// app/Services/VideoThumbnailService.php
<?php

namespace App\Services;

use FFMpeg\FFMpeg;
use Illuminate\Support\Facades\Storage;

class VideoThumbnailService
{
    private $ffmpeg;
    
    public function __construct()
    {
        $this->ffmpeg = FFMpeg::create([
            'ffmpeg.binaries' => config('ffmpeg.ffmpeg.binaries.ffmpeg'),
            'ffprobe.binaries' => config('ffmpeg.ffmpeg.binaries.ffprobe'),
        ]);
    }
    
    /**
     * Video-dan thumbnail yarat
     */
    public function generateThumbnail(string $videoPath, int $timeInSeconds = 1): string
    {
        $video = $this->ffmpeg->open($videoPath);
        
        // Video-dan frame çıxar
        $frame = $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds($timeInSeconds));
        
        // Thumbnail path
        $thumbnailPath = storage_path('app/temp/thumbnails/' . uniqid() . '.jpg');
        
        // Thumbnail yarat
        $frame->save($thumbnailPath);
        
        // Storage-a köçür
        $storedPath = Storage::disk('public')->putFile(
            'thumbnails',
            new \Illuminate\Http\File($thumbnailPath)
        );
        
        // Temp faylı sil
        @unlink($thumbnailPath);
        
        return $storedPath;
    }
    
    /**
     * Bir neçə thumbnail yarat (carousel üçün)
     */
    public function generateMultipleThumbnails(string $videoPath, int $count = 5): array
    {
        $video = $this->ffmpeg->open($videoPath);
        $duration = $video->getStreams()->first()->get('duration');
        
        $thumbnails = [];
        $interval = $duration / ($count + 1);
        
        for ($i = 1; $i <= $count; $i++) {
            $time = $interval * $i;
            $thumbnails[] = $this->generateThumbnail($videoPath, $time);
        }
        
        return $thumbnails;
    }
}

// Controller-də istifadə
public function uploadVideo(Request $request)
{
    // ... video upload kodu ...
    
    // Thumbnail yarat
    $thumbnailService = new VideoThumbnailService();
    $thumbnailPath = $thumbnailService->generateThumbnail($compressedPath);
    
    return response()->json([
        'video_path' => $finalPath,
        'thumbnail_path' => $thumbnailPath,
    ]);
}
```

**Gözlənilən Nəticə:**
- Video yüklənərkən thumbnail dərhal görünür
- UX təkmilləşməsi: 100%

---

### 🟡 ORTA PRIORİTET (2-4 həftə)

#### Tapşırıq 3: Adaptive Bitrate Streaming (HLS)
**Məqsəd:** İstifadəçinin internet sürətinə görə avtomatik keyfiyyət seçimi

**Tələblər:**
- HLS formatında video yaratmaq
- 3 variant: 480p, 720p, 1080p
- M3U8 playlist faylı yaratmaq

**Implementation:**
```php
// app/Services/HLSStreamingService.php
<?php

namespace App\Services;

use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use Illuminate\Support\Facades\Storage;

class HLSStreamingService
{
    private $ffmpeg;
    
    public function __construct()
    {
        $this->ffmpeg = FFMpeg::create([
            'ffmpeg.binaries' => config('ffmpeg.ffmpeg.binaries.ffmpeg'),
            'ffprobe.binaries' => config('ffmpeg.ffmpeg.binaries.ffprobe'),
        ]);
    }
    
    /**
     * Video-nu HLS formatına çevir
     */
    public function createHLSStream(string $inputPath, string $outputDir): array
    {
        $qualities = [
            '480p' => ['width' => 854, 'height' => 480, 'bitrate' => 500],
            '720p' => ['width' => 1280, 'height' => 720, 'bitrate' => 1000],
            '1080p' => ['width' => 1920, 'height' => 1080, 'bitrate' => 2000],
        ];
        
        $playlistFiles = [];
        
        foreach ($qualities as $quality => $config) {
            $video = $this->ffmpeg->open($inputPath);
            
            // Video ölçüsünü dəyişdir
            $video->filters()
                ->resize(new \FFMpeg\Coordinate\Dimension($config['width'], $config['height']))
                ->synchronize();
            
            // HLS format
            $format = new X264('aac', 'libx264');
            $format->setKiloBitrate($config['bitrate']);
            
            // HLS parametrləri
            $format->setAdditionalParameters([
                '-hls_time', '10',           // 10 saniyəlik segmentlər
                '-hls_list_size', '0',        // Bütün segmentləri saxla
                '-hls_segment_filename', $outputDir . "/{$quality}_%03d.ts",
                '-f', 'hls',
            ]);
            
            $playlistPath = $outputDir . "/{$quality}.m3u8";
            $video->save($format, $playlistPath);
            
            $playlistFiles[$quality] = $playlistPath;
        }
        
        // Master playlist yarat
        $masterPlaylist = $this->createMasterPlaylist($playlistFiles, $outputDir);
        
        return [
            'master_playlist' => $masterPlaylist,
            'playlists' => $playlistFiles,
        ];
    }
    
    private function createMasterPlaylist(array $playlists, string $outputDir): string
    {
        $masterPlaylist = "#EXTM3U\n";
        $masterPlaylist .= "#EXT-X-VERSION:3\n\n";
        
        foreach ($playlists as $quality => $playlistPath) {
            $bandwidth = match($quality) {
                '480p' => 500000,
                '720p' => 1000000,
                '1080p' => 2000000,
                default => 1000000,
            };
            
            $masterPlaylist .= "#EXT-X-STREAM-INF:BANDWIDTH={$bandwidth}\n";
            $masterPlaylist .= basename($playlistPath) . "\n\n";
        }
        
        $masterPath = $outputDir . '/master.m3u8';
        file_put_contents($masterPath, $masterPlaylist);
        
        return $masterPath;
    }
}

// Controller-də istifadə
public function uploadVideo(Request $request)
{
    // ... video upload və compression ...
    
    // HLS stream yarat
    $hlsService = new HLSStreamingService();
    $hlsOutput = storage_path('app/videos/hls/' . uniqid());
    mkdir($hlsOutput, 0755, true);
    
    $hlsStream = $hlsService->createHLSStream($compressedPath, $hlsOutput);
    
    // Storage-a köçür
    $hlsStoragePath = 'videos/hls/' . basename($hlsOutput);
    Storage::disk('public')->putDirectory($hlsStoragePath, $hlsOutput);
    
    return response()->json([
        'hls_master_playlist' => $hlsStoragePath . '/master.m3u8',
        'hls_playlists' => $hlsStream['playlists'],
    ]);
}
```

**Frontend-də istifadə:**
```javascript
// HLS.js library istifadə et
// npm install hls.js

import Hls from 'hls.js';

if (Hls.isSupported()) {
  const hls = new Hls();
  hls.loadSource('http://localhost:8000/storage/videos/hls/master.m3u8');
  hls.attachMedia(videoPlayer.value);
} else if (videoPlayer.value.canPlayType('application/vnd.apple.mpegurl')) {
  // Safari native HLS support
  videoPlayer.value.src = 'http://localhost:8000/storage/videos/hls/master.m3u8';
}
```

**Gözlənilən Nəticə:**
- Zəif internet üçün: 480p avtomatik seçilir
- Orta internet üçün: 720p seçilir
- Güclü internet üçün: 1080p seçilir
- Performans artımı: 200-300%

---

#### Tapşırıq 4: CDN Integration
**Məqsəd:** Video fayllarını CDN-də saxlamaq

**Tələblər:**
- Cloudflare, AWS CloudFront, və ya Azure CDN
- Video faylları CDN-də cache olunmalıdır
- Signed URL-lər CDN-dən gəlməlidir

**Implementation:**
```php
// config/filesystems.php
'cdn' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
    'url' => env('AWS_URL'), // CDN URL
    'endpoint' => env('AWS_ENDPOINT'),
],

// app/Services/CDNService.php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class CDNService
{
    /**
     * Video-nu CDN-ə yüklə
     */
    public function uploadToCDN(string $localPath, string $remotePath): string
    {
        $file = Storage::disk('local')->get($localPath);
        Storage::disk('cdn')->put($remotePath, $file);
        
        return Storage::disk('cdn')->url($remotePath);
    }
    
    /**
     * CDN-dən signed URL yarat
     */
    public function getSignedUrl(string $path, int $expiresInMinutes = 60): string
    {
        return Storage::disk('cdn')->temporaryUrl(
            $path,
            now()->addMinutes($expiresInMinutes)
        );
    }
}
```

**Gözlənilən Nəticə:**
- Coğrafi məsafəyə görə: 50-100% performans artımı
- CDN cache: 90-95% performans artımı (ikinci dəfə)

---

### 🟢 AŞAĞI PRIORİTET (1-2 ay)

#### Tapşırıq 5: Video Caching Headers
**Məqsəd:** Browser və proxy cache optimallaşdırması

**Implementation:**
```php
// app/Http/Middleware/CacheVideoHeaders.php
<?php

namespace App\Http\Middleware;

use Closure;

class CacheVideoHeaders
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        // Video faylları üçün cache headers
        if ($request->is('storage/videos/*') || $request->is('api/v1/modules/*/lessons/*/media/*')) {
            $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
            $response->headers->set('Expires', now()->addYear()->toRfc7231String());
            $response->headers->set('ETag', md5($response->getContent()));
        }
        
        return $response;
    }
}
```

---

#### Tapşırıq 6: Video Progress Tracking API
**Məqsəd:** İstifadəçinin video izləmə progress-ini izləmək

**Implementation:**
```php
// routes/api.php
Route::post('/lessons/{lesson}/video-progress', [LessonController::class, 'updateVideoProgress']);

// app/Http/Controllers/LessonController.php
public function updateVideoProgress(Request $request, $lessonId)
{
    $user = auth()->user();
    
    $progress = $user->lessonProgress()->updateOrCreate(
        ['user_id' => $user->id, 'lesson_id' => $lessonId],
        [
            'watched_time' => $request->watched_time,
            'total_time' => $request->total_time,
            'progress_percentage' => ($request->watched_time / $request->total_time) * 100,
            'last_watched_at' => now(),
        ]
    );
    
    return response()->json($progress);
}
```

---

## 📊 Performans Metrikaları

### Ölçüləcək Metrikalar:
1. **Video Upload Time** - Video yüklənmə vaxtı
2. **Compression Time** - Sıxışdırma vaxtı
3. **Compression Ratio** - Sıxışdırma nisbəti
4. **CDN Hit Rate** - CDN cache hit rate
5. **Video Load Time** - Frontend-də video yüklənmə vaxtı

### Monitoring:
```php
// app/Services/VideoMetricsService.php
class VideoMetricsService
{
    public function logVideoMetrics(string $videoId, array $metrics)
    {
        \Log::channel('video_metrics')->info('Video metrics', [
            'video_id' => $videoId,
            'upload_time' => $metrics['upload_time'] ?? null,
            'compression_time' => $metrics['compression_time'] ?? null,
            'compression_ratio' => $metrics['compression_ratio'] ?? null,
            'file_size_before' => $metrics['file_size_before'] ?? null,
            'file_size_after' => $metrics['file_size_after'] ?? null,
            'cdn_url' => $metrics['cdn_url'] ?? null,
        ]);
    }
}
```

---

## 🎯 Gözlənilən Nəticələr

### Hazırkı Vəziyyət:
- 16MB video: ~15-30 saniyə yüklənmə
- 30 dəqiqəlik video: ~2-5 dəqiqə yüklənmə

### Optimallaşdırmadan Sonra:
- **Tapşırıq 1 (Compression):** 16MB → 4-6MB (5-10x sürətli)
- **Tapşırıq 2 (Thumbnail):** UX təkmilləşməsi (100%)
- **Tapşırıq 3 (HLS):** 20-30x sürətli (zəif internet üçün)
- **Tapşırıq 4 (CDN):** 50-100% performans artımı

### Ümumi Gözlənilən Nəticə:
- **16MB video:** 15-30s → **2-4s** (7-15x sürətli)
- **30 dəqiqəlik video:** 2-5 dəq → **5-10s** (20-30x sürətli)

---

## ✅ Test Planı

### 1. Compression Test:
```bash
# Test video yüklə
curl -X POST http://localhost:8000/api/v1/media/upload-video \
  -F "video=@test_video.mp4"

# Nəticəni yoxla
# - Original size
# - Compressed size
# - Compression ratio
# - Quality check
```

### 2. HLS Test:
```bash
# HLS stream yarat
# Master playlist URL-ni yoxla
curl http://localhost:8000/storage/videos/hls/master.m3u8

# Segmentləri yoxla
curl http://localhost:8000/storage/videos/hls/720p_001.ts
```

### 3. CDN Test:
```bash
# CDN URL-ni yoxla
curl -I http://cdn.example.com/videos/test.mp4

# Cache headers yoxla
# Cache-Control: public, max-age=31536000
```

---

## 📝 Qeydlər

1. **FFmpeg quraşdırma:** Server-də FFmpeg quraşdırılmalıdır
2. **Storage:** Video faylları üçün kifayət qədər disk sahəsi lazımdır
3. **CDN:** CDN service aktiv olmalıdır
4. **Queue:** Video compression queue-da işləməlidir (background job)
5. **Monitoring:** Video metrics izlənilməlidir

---

## 🔗 Əlaqəli Fayllar

- `VIDEO_OPTIMIZATION_GUIDE_AZ.md` - Ümumi optimallaşdırma təlimatı
- `VIDEO_SECURITY_BACKEND_AZ.md` - Video təhlükəsizliyi
- `SIGNED_URL_RESPONSE_FRONTEND_AZ.md` - Signed URL frontend təlimatı

---

**Prioritet:** Tapşırıq 1 (Video Compression) və Tapşırıq 2 (Thumbnail) ən böyük təsirə malikdir və tez tətbiq edilə bilər.

