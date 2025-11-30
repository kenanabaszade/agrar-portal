# HLS Video Troubleshooting Guide

## Problem: 404 Not Found for HLS Playlist Files

Eğer `.m3u8` dosyaları için 404 hatası alıyorsanız, aşağıdaki adımları takip edin:

## 1. HLS Dosyalarının Oluşturulup Oluşturulmadığını Kontrol Edin

### Storage Disk'te Kontrol

```bash
# Local disk'te kontrol
ls -la storage/app/private/lessons/{lessonId}/hls/

# Public disk'te kontrol  
ls -la storage/app/public/lessons/{lessonId}/hls/
```

### Beklenen Dosya Yapısı

```
lessons/{lessonId}/hls/
├── master.m3u8                    # VEYA
└── {randomId}/
    ├── master.m3u8
    ├── 480p.m3u8
    ├── 720p.m3u8
    ├── 1080p.m3u8
    └── *.ts (video segments)
```

## 2. HLS Processing Job'unun Çalıştığını Kontrol Edin

### Queue Worker Kontrolü

```bash
# Queue worker çalışıyor mu?
php artisan queue:work

# Veya supervisor/systemd ile çalışıyorsa:
sudo systemctl status queue-worker
```

### Job Log Kontrolü

```bash
# Laravel log dosyasını kontrol edin
tail -f storage/logs/laravel.log | grep -i hls
```

### Database'de Job Kontrolü

```sql
-- Failed jobs kontrolü
SELECT * FROM failed_jobs WHERE payload LIKE '%ProcessVideoHLS%' OR payload LIKE '%ProcessLessonVideoHLS%';

-- Jobs tablosu varsa
SELECT * FROM jobs WHERE queue = 'default' AND payload LIKE '%ProcessVideoHLS%';
```

## 3. FFmpeg Konfigürasyonunu Kontrol Edin

### .env Dosyası

```env
FFMPEG_ENABLED=true
FFMPEG_PATH=/usr/bin/ffmpeg
FFMPEG_HLS_ENABLED=true
```

### Config Dosyası

```php
// config/ffmpeg.php (varsa)
'hls' => [
    'enabled' => env('FFMPEG_HLS_ENABLED', false),
    'path' => env('FFMPEG_PATH', 'ffmpeg'),
    'variants' => [
        '480p' => ['width' => 854, 'height' => 480, 'bitrate' => 500000],
        '720p' => ['width' => 1280, 'height' => 720, 'bitrate' => 1000000],
        '1080p' => ['width' => 1920, 'height' => 1080, 'bitrate' => 2000000],
    ],
],
```

### FFmpeg Kurulumu Kontrolü

```bash
# FFmpeg kurulu mu?
ffmpeg -version

# HLS desteği var mı?
ffmpeg -codecs | grep h264
```

## 4. Video Upload Sonrası HLS Processing

### Lesson Video Upload

Video upload edildiğinde, `ProcessLessonVideoHLS` job'u otomatik olarak dispatch edilmelidir:

```php
// app/Http/Controllers/TrainingLessonController.php
// Video upload sonrası:
if ($tempFile->type === 'video') {
    // HLS processing job dispatch edilmeli
    \App\Jobs\ProcessLessonVideoHLS::dispatch($lesson->id, $finalPath)
        ->delay(now()->addSeconds(5));
}
```

### Job'un Çalıştığını Kontrol

```bash
# Queue'yu manuel olarak çalıştır
php artisan queue:work --once

# Veya specific job'u test et
php artisan tinker
>>> \App\Jobs\ProcessLessonVideoHLS::dispatch(5, 'lessons/5/video.mp4');
```

## 5. API Response'da Path Kontrolü

### Doğru Format

API response'da `hls_master_playlist` şu formatlardan biri olmalı:

```json
{
  "hls_master_playlist": "lessons/5/hls/master.m3u8"
  // VEYA
  "hls_master_playlist": "lessons/5/hls/69270ba450692/master.m3u8"
}
```

### Path Kontrolü

```php
// Tinker'da test
php artisan tinker

$lesson = \App\Models\TrainingLesson::find(5);
$mediaFiles = $lesson->media_files;
$videoFile = collect($mediaFiles)->firstWhere('type', 'video');
echo $videoFile['hls_master_playlist'] ?? 'HLS path not found';
```

## 6. Storage Disk Kontrolü

### Dosyaların Hangi Disk'te Olduğunu Kontrol

```php
// Tinker'da
use Illuminate\Support\Facades\Storage;

$path = 'lessons/5/hls/master.m3u8';

// Local disk'te var mı?
Storage::disk('local')->exists($path);

// Public disk'te var mı?
Storage::disk('public')->exists($path);

// Tüm HLS dosyalarını listele
Storage::disk('local')->allFiles('lessons/5/hls/');
Storage::disk('public')->allFiles('lessons/5/hls/');
```

## 7. Manuel HLS Oluşturma

Eğer HLS dosyaları oluşturulmamışsa, manuel olarak oluşturabilirsiniz:

### FFmpeg Komutu

```bash
# Video dosyasını HLS formatına çevir
ffmpeg -i input.mp4 \
  -c:v libx264 -c:a aac \
  -hls_time 10 -hls_list_size 0 \
  -hls_segment_filename "output_%03d.ts" \
  -master_playlist_path "master.m3u8" \
  -var_stream_map "v:0,a:0 v:1,a:1 v:2,a:2" \
  -f hls \
  -hls_time 10 \
  -hls_playlist_type vod \
  -hls_segment_type mpegts \
  output.m3u8
```

### Laravel Job ile Manuel Oluşturma

```php
php artisan tinker

$lesson = \App\Models\TrainingLesson::find(5);
$mediaFiles = $lesson->media_files;
$videoFile = collect($mediaFiles)->firstWhere('type', 'video');

if ($videoFile && isset($videoFile['path'])) {
    \App\Jobs\ProcessLessonVideoHLS::dispatch($lesson->id, $videoFile['path']);
    echo "HLS processing job dispatched";
}
```

## 8. Common Issues ve Çözümleri

### Issue 1: Path Format Mismatch

**Problem:** API'de `lessons/5/hls/69270ba450692/master.m3u8` dönüyor ama dosya `lessons/5/hls/master.m3u8` konumunda.

**Çözüm:** `detectHLSFiles` metodu artık alt klasörleri de kontrol ediyor. Kod güncellendi.

### Issue 2: Storage Disk Mismatch

**Problem:** Dosyalar `local` disk'te ama kod `public` disk'te arıyor.

**Çözüm:** `detectHLSFiles` metodu her iki disk'i de kontrol ediyor.

### Issue 3: Job Çalışmıyor

**Problem:** Video upload edildi ama HLS dosyaları oluşturulmadı.

**Çözüm:**
1. Queue worker'ın çalıştığından emin olun
2. Job'un dispatch edildiğini kontrol edin
3. Job log'larını kontrol edin
4. FFmpeg'in kurulu ve çalıştığını kontrol edin

### Issue 4: Permission Issues

**Problem:** Dosyalar oluşturulamıyor.

**Çözüm:**
```bash
# Storage klasörüne yazma izni ver
chmod -R 775 storage/app/private/lessons
chmod -R 775 storage/app/public/lessons

# Web server user'ına sahiplik ver
chown -R www-data:www-data storage/app/private/lessons
chown -R www-data:www-data storage/app/public/lessons
```

## 9. Debug Komutları

### Tüm HLS Dosyalarını Listele

```bash
find storage/app -name "*.m3u8" -type f
find storage/app -name "*.ts" -type f | head -20
```

### Storage Disk Kullanımı

```bash
du -sh storage/app/private/lessons/
du -sh storage/app/public/lessons/
```

### API Test

```bash
# Training detayını al
curl "http://localhost:8000/api/v1/trainings/2/detailed?lang=az" | jq '.modules[0].lessons[0].media_files[] | select(.type=="video") | .hls_master_playlist_url'
```

## 10. Son Kontrol Listesi

- [ ] FFmpeg kurulu ve çalışıyor
- [ ] Queue worker çalışıyor
- [ ] HLS dosyaları storage'da mevcut
- [ ] API response'da doğru path dönüyor
- [ ] Storage disk izinleri doğru
- [ ] Job log'larında hata yok
- [ ] `.env` dosyasında HLS enabled

## Support

Sorun devam ederse:
1. Laravel log dosyasını kontrol edin: `storage/logs/laravel.log`
2. Queue log'larını kontrol edin
3. Browser console'da network request'leri kontrol edin
4. API response'u kontrol edin


