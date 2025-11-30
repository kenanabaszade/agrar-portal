# FFmpeg Quraşdırma Təlimatı

## 🎯 Məqsəd

FFmpeg video compression və thumbnail generation üçün lazımdır. Bu təlimat FFmpeg-i Windows, Linux və macOS-də quraşdırmaq üçündür.

---

## Windows

### 1. FFmpeg Yükləyin

1. **FFmpeg binaries yükləyin:**
   - [https://www.gyan.dev/ffmpeg/builds/](https://www.gyan.dev/ffmpeg/builds/) - Git Full Build

2. **Zip faylını açın:**
   - Məsələn: `C:\ffmpeg\` directory-ə

3. **Path əlavə edin (optional):**
   - Windows Search → "Environment Variables"
   - System Properties → Environment Variables
   - System variables → Path → Edit
   - New → `C:\ffmpeg\bin`
   - OK

### 2. Test Edin

```bash
# Command Prompt və ya PowerShell-də
C:\ffmpeg\bin\ffmpeg -version

# Gözlənilən output:
# ffmpeg version ...
```

### 3. `.env` Faylında Path Təyin Edin

```.env
FFMPEG_BIN=C:\ffmpeg\bin\ffmpeg.exe
FFPROBE_BIN=C:\ffmpeg\bin\ffprobe.exe
```

---

## Linux (Ubuntu/Debian)

### 1. FFmpeg Quraşdırın

```bash
sudo apt update
sudo apt install ffmpeg -y
```

### 2. Test Edin

```bash
ffmpeg -version

# Gözlənilən output:
# ffmpeg version ...
```

### 3. `.env` Faylında Path Təyin Edin

```.env
FFMPEG_BIN=/usr/bin/ffmpeg
FFPROBE_BIN=/usr/bin/ffprobe
```

---

## macOS

### 1. Homebrew ilə Quraşdırın

```bash
# Homebrew quraşdırın (yoxdursa)
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# FFmpeg quraşdırın
brew install ffmpeg
```

### 2. Test Edin

```bash
ffmpeg -version

# Gözlənilən output:
# ffmpeg version ...
```

### 3. `.env` Faylında Path Təyin Edin

```.env
FFMPEG_BIN=/usr/local/bin/ffmpeg
FFPROBE_BIN=/usr/local/bin/ffprobe
```

---

## Test

Laravel-də FFmpeg-in işlədiyini yoxlayın:

```bash
php artisan tinker
```

```php
// FFmpeg path yoxla
config('ffmpeg.ffmpeg.binaries.ffmpeg')

// FFmpeg test
$ffmpeg = FFMpeg\FFMpeg::create([
    'ffmpeg.binaries' => config('ffmpeg.ffmpeg.binaries.ffmpeg'),
    'ffprobe.binaries' => config('ffmpeg.ffmpeg.binaries.ffprobe'),
]);

// Gözlənilən: FFmpeg\FFMpeg object
```

---

## Xətalar və Həlli

### Error: "FFmpeg binary not found"

**Həll:**
1. FFmpeg quraşdırıldığını yoxlayın: `ffmpeg -version`
2. `.env` faylında path düzgündür: `FFMPEG_BIN=...`
3. Cache təmizləyin: `php artisan config:clear`

### Error: "Permission denied"

**Həll (Linux/Mac):**
```bash
chmod +x /usr/bin/ffmpeg
chmod +x /usr/bin/ffprobe
```

### Error: "Timeout"

**Həll:**
`.env` faylında timeout artırın:
```env
FFMPEG_TIMEOUT=7200
```

---

## Performans Optimizasyonu

### 1. Thread Sayı

CPU core sayınıza uyğun thread sayı təyin edin:

```.env
# 4 core CPU üçün
FFMPEG_THREADS=4

# 8 core CPU üçün
FFMPEG_THREADS=8
```

### 2. Preset

Compression sürəti vs keyfiyyət balansı:

- `ultrafast` - Ən sürətli (böyük fayl ölçüsü)
- `fast` - Sürətli (yaxşı balans) ✅
- `medium` - Orta (default)
- `slow` - Yavaş (kiçik fayl ölçüsü)
- `veryslow` - Çox yavaş (ən kiçik fayl)

### 3. CRF (Quality)

Keyfiyyət parametri (18-28 arası):

- `18` - Çox yüksək keyfiyyət (böyük fayl)
- `23` - Yaxşı keyfiyyət (balans) ✅
- `28` - Aşağı keyfiyyət (kiçik fayl)

---

## Video Upload Test

```bash
# Test video yüklə
curl -X POST http://localhost:8000/api/v1/lessons/upload-temp-media \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@test_video.mp4" \
  -F "type=video"
```

**Gözlənilən Response:**
```json
{
  "message": "Media uploaded successfully",
  "file_code": "FILE_ABC12345",
  "temp_url": "/storage/temp/lessons/compressed/video_compressed.mp4",
  "original_size": 16777216,
  "compressed_size": 4194304,
  "compression_ratio": "75%",
  "thumbnail_url": "/storage/lessons/temp/thumbnails/video_thumb.jpg"
}
```

---

## Xülasə

1. ✅ FFmpeg quraşdırın
2. ✅ `.env` faylında path təyin edin
3. ✅ Test edin (`ffmpeg -version`)
4. ✅ Laravel-də test edin (video upload)
5. ✅ Performans optimizasyonu edin

---

**Kömək lazımdırsa:**
- FFmpeg sənədləri: [https://ffmpeg.org/documentation.html](https://ffmpeg.org/documentation.html)
- PHP-FFmpeg: [https://github.com/PHP-FFMpeg/PHP-FFMpeg](https://github.com/PHP-FFMpeg/PHP-FFMpeg)

