# Lesson Media Security - Təhlükəsizlik Təlimatı

## 🔒 Problem

Əvvəllər lesson media faylları `storage/app/public/lessons/` qovluğunda saxlanılırdı və `public/storage` symlink vasitəsilə birbaşa access olunurdu. Bu o deməkdir ki, hər kəs URL-i bilirsə, birbaşa fayllara access edə bilərdi.

## ✅ Həll

İndi lesson media faylları **qorunur** və yalnız authorized userlər access edə bilər.

### 1. Protected Download Endpoint

Yeni endpoint yaradıldı:
```
GET /api/v1/modules/{module}/lessons/{lesson}/media/download?path={file_path}
```

**Tələblər:**
- ✅ Authentication tələb olunur (`auth:sanctum`)
- ✅ User training-ə qeydiyyatdan keçməlidir (və ya admin/trainer olmalıdır)
- ✅ File path validation (directory traversal attack-ləri qarşısı alınır)

### 2. Private Storage

Yeni yüklənən fayllar artıq `storage/app/private/lessons/` qovluğunda saxlanılır (public deyil).

### 3. .htaccess Protection

`public/.htaccess` faylında lesson media fayllarına birbaşa access bloklanıb:
```apache
# Block direct access to lesson media files
RewriteCond %{REQUEST_URI} ^/storage/lessons/
RewriteRule ^ - [F,L]
```

## 📋 Frontend-də İstifadə

### Media URL Format

Artıq media faylları üçün URL-lər belədir:
```json
{
  "media_files": [
    {
      "type": "video",
      "url": "/api/v1/modules/1/lessons/5/media/download?path=lessons/5/video.mp4",
      "path": "lessons/5/video.mp4",
      "filename": "video.mp4"
    }
  ]
}
```

### Frontend-də Video/Media Oynatmaq

```javascript
// Video player üçün
const videoUrl = mediaFile.url; // Protected URL
// Bu URL authentication tələb edir

// Video element-də istifadə:
<video controls>
  <source src={videoUrl} type="video/mp4" />
</video>

// Fetch ilə:
fetch(videoUrl, {
  headers: {
    'Authorization': `Bearer ${token}`
  }
})
```

### Authorization

Frontend-də video/media yükləyərkən:
1. User authenticated olmalıdır (token lazımdır)
2. User training-ə qeydiyyatdan keçməlidir
3. Və ya admin/trainer olmalıdır

## 🔐 Təhlükəsizlik Xüsusiyyətləri

1. **Authentication Required**: Yalnız authenticated userlər access edə bilər
2. **Authorization Check**: User training-ə qeydiyyatdan keçməlidir
3. **Path Validation**: Directory traversal attack-ləri qarşısı alınır
4. **Private Storage**: Fayllar public storage-dan private storage-ə köçürülür
5. **.htaccess Block**: Birbaşa URL access bloklanıb

## ⚠️ Vacib Qeydlər

1. **Köhnə fayllar**: Əvvəlki yüklənmiş fayllar hələ də `public` storage-dadır. Onlar da protected endpoint vasitəsilə access olunur (backward compatibility).

2. **Migration**: Köhnə faylları private storage-ə köçürmək istəsəniz, migration script yarada bilərsiniz.

3. **Performance**: Protected endpoint bir az daha yavaş ola bilər (authentication check), amma təhlükəsizlik üçün vacibdir.

## 🧪 Test

```bash
# Unauthorized access (403 gözlənilir)
curl http://localhost:8000/api/v1/modules/1/lessons/5/media/download?path=lessons/5/video.mp4

# Authorized access (200 gözlənilir)
curl -H "Authorization: Bearer {token}" \
  http://localhost:8000/api/v1/modules/1/lessons/5/media/download?path=lessons/5/video.mp4

# Direct storage access (403 gözlənilir)
curl http://localhost:8000/storage/lessons/5/video.mp4
```

