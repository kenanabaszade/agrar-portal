# Signed URL Nədir və Nə Üçün Lazımdır?

## 🎯 Signed URL-in Əsas Məqsədi

**Signed URL** - bu, **müvəqqəti (temporary) və təhlükəsiz (secure) video link**dir ki, frontend-də birbaşa `<video>` tag-də istifadə oluna bilər.

---

## ❌ Problem (Signed URL olmadan)

### Problem 1: Authentication Header Problemi
```vue
<!-- Bu İŞLƏMƏYƏCƏK -->
<video src="http://localhost:8000/api/v1/modules/1/lessons/2/media/download?path=..." controls />
```

**Səbəb:** 
- `<video>` tag-i birbaşa browser tərəfindən request göndərir
- Authentication header (`Authorization: Bearer token`) göndərilə bilməz
- Server 401 Unauthorized qaytarır
- Video oynatıla bilməz

### Problem 2: Təhlükəsizlik
- Əgər adi URL olsa, hər kəs URL-i kopyalayıb paylaşa bilər
- URL-in sonuna yazıb videonu tapa bilər
- Video məzmunu qorunmamış olur

---

## ✅ Həll: Signed URL

### Signed URL Nədir?

**Signed URL** - bu, **xüsusi imza (signature) və son istifadə tarixi (expiration)** olan URL-dir.

**Nümunə:**
```
http://localhost:8000/api/v1/modules/1/lessons/2/media/download?
  path=lessons%2F2%2Fvideo.mp4&
  signature=abc123def456...&
  expires=1735123456
```

**Komponentlər:**
1. **Base URL**: Video faylının yolu
2. **signature**: Xüsusi imza (HMAC SHA256)
3. **expires**: Son istifadə tarixi (timestamp)

---

## 🔒 Signed URL-in Təhlükəsizliyi

### 1. Expiration (Son Tarix)
- Signed URL **2 saat** sonra expire olur
- Expire olduqdan sonra işləməyəcək
- URL kopyalanıb paylaşılsa belə, 2 saat sonra işləməyəcək

### 2. Signature (İmza)
- URL-də **xüsusi imza** var
- Bu imza server tərəfindən yoxlanılır
- İmza düzgün deyilsə, access qadağandır

### 3. User-Specific (User-ə Xas)
- Signed URL **user ID** ilə bağlıdır
- Hər user üçün fərqli signed URL yaradılır
- Bir user-in signed URL-i digər user üçün işləməyəcək

---

## 🎬 Signed URL Necə İşləyir?

### Addım 1: Backend-də Yaradılması

```php
// TrainingController-də
$signedUrl = LessonMediaController::generateSignedUrl(
    $module,
    $lesson,
    $filePath,
    $userId,
    120 // 2 saat expiration
);
```

**Nə edir:**
1. Expiration timestamp yaradır (2 saat sonra)
2. Signature data hazırlayır (module_id, lesson_id, path, user_id, expires_at)
3. HMAC SHA256 ilə signature yaradır
4. URL-ə signature və expires parametrlərini əlavə edir

### Addım 2: Response-da Göndərilməsi

```json
{
  "media_files": [
    {
      "type": "video",
      "signed_url": "http://localhost:8000/api/v1/modules/1/lessons/2/media/download?path=...&signature=abc123&expires=1234567890",
      "signed_url_expires_at": "2025-11-25T14:00:00.000000Z"
    }
  ]
}
```

### Addım 3: Frontend-də İstifadəsi

```vue
<template>
  <!-- Signed URL birbaşa istifadə oluna bilər -->
  <video :src="videoFile.signed_url" controls />
</template>
```

**Niyə işləyir?**
- Signed URL-də **signature** və **expires** parametrləri var
- Server bu parametrləri yoxlayır
- Əgər düzgündürsə, video faylını göndərir
- Authentication header lazım deyil (çünki signature var)

### Addım 4: Server-də Yoxlanılması

```php
// LessonMediaController-də
if ($request->has('signature') && $request->has('expires')) {
    // Signed URL yoxlanılır
    if (self::verifySignedUrl($request, $module, $lesson, $filePath)) {
        // Signature düzgündür və expire olmayıb
        // Video faylını göndər
    }
}
```

**Yoxlama prosesi:**
1. Signature və expires parametrlərini alır
2. Expiration yoxlanılır (hələ expire olmayıb?)
3. Signature verify olunur (düzgün imza?)
4. Əgər hər şey düzgündürsə, video göndərilir

---

## 📊 Signed URL vs Adi URL

| Xüsusiyyət | Adi URL | Signed URL |
|------------|---------|------------|
| **Authentication** | Header lazımdır | Signature kifayətdir |
| **Expiration** | Yoxdur | 2 saat sonra expire olur |
| **Security** | Aşağı | Yüksək (signature ilə) |
| **Video Tag** | İşləmir | İşləyir |
| **Blob URL** | Lazımdır | Lazım deyil |
| **Paylaşılma** | Həmişə işləyir | 2 saat sonra işləmir |

---

## 🎯 Signed URL-in Üstünlükləri

### 1. Video Player-də Birbaşa İstifadə
```vue
<!-- Blob URL yaratmağa ehtiyac yoxdur -->
<video :src="signedUrl" controls />
```

### 2. Təhlükəsizlik
- URL kopyalanıb paylaşılsa belə, 2 saat sonra işləməyəcək
- Signature ilə verify olunur
- User-specific-dir

### 3. Sadəlik
- Frontend-də blob URL yaratmağa ehtiyac yoxdur
- Birbaşa `<video>` tag-də istifadə oluna bilər
- Authentication header göndərməyə ehtiyac yoxdur

---

## ⚠️ Signed URL-in Məhdudiyyətləri

### 1. Expiration
- **2 saat** sonra expire olur
- Expire olduqdan sonra yenidən training detailed endpoint-dən alınmalıdır

### 2. Yalnız Video üçün
- Signed URL yalnız **video faylları** üçün yaradılır
- Şəkil və digər fayllar üçün adi `url` field-i istifadə olunur

### 3. User-Specific
- Hər user üçün fərqli signed URL yaradılır
- Bir user-in signed URL-i digər user üçün işləməyəcək

---

## 🔄 Signed URL Expire Olduqda

**Problem:** Signed URL 2 saat sonra expire olur.

**Həll:**
1. Video error verəndə training detailed endpoint-dən yenidən response al
2. Yeni signed URL-i istifadə et

```javascript
const refreshVideoUrl = async () => {
  const response = await axios.get(`/api/v1/trainings/${trainingId}/detailed`);
  const newSignedUrl = response.data.modules[0].lessons[0].media_files[0].signed_url;
  videoElement.src = newSignedUrl;
};
```

---

## 📝 Xülasə

**Signed URL nədir?**
- Müvəqqəti (2 saat) və təhlükəsiz video link
- Signature ilə verify olunur
- User-specific-dir

**Nə üçün lazımdır?**
- `<video>` tag-də birbaşa istifadə oluna bilər
- Authentication header lazım deyil
- Təhlükəsizdir (expire olur, signature ilə verify olunur)

**Necə işləyir?**
1. Backend-də signed URL yaradılır (signature + expiration)
2. Response-da göndərilir
3. Frontend-də birbaşa `<video>` tag-də istifadə olunur
4. Server-də signature verify olunur və video göndərilir

**Üstünlükləri:**
- ✅ Blob URL yaratmağa ehtiyac yoxdur
- ✅ Birbaşa video tag-də istifadə oluna bilər
- ✅ Təhlükəsizdir (expire olur, signature ilə verify olunur)

