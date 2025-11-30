# ERR_BLOCKED Xətasının Həlli - Azərbaycan Dili

## 🔴 Problem

Network tab-da görünür:
- `(failed) net::ERR_BLOC...` - Request bloklanıb
- HLS master playlist request `(canceled)` - Ləğv edilib
- MP4 request `(canceled)` - Ləğv edilib

---

## 🔍 Səbəblər

### 1. Browser Extension-lar
- Ad blocker-lar video request-lərini bloklayır
- Privacy extension-lar (uBlock Origin, Privacy Badger)
- Security extension-lar

### 2. CORS Problemi
- Backend CORS header-ları düzgün göndərmir
- Frontend domain backend-də allow edilməyib

### 3. Referer Policy
- Backend referer yoxlaması çox sərtdir
- Request referer header göndərmir

### 4. Backend Təhlükəsizlik Yoxlaması
- Signed URL verification fail olur
- File path validation fail olur

---

## ✅ Həllər

### 1. Browser Extension-ları Yoxlayın

**Test üçün:**
1. Browser-də **Incognito/Private mode** açın
2. Extension-ları söndürün
3. Saytı yenidən yoxlayın

**Əgər incognito-da işləyirsə:**
- Extension-lar problemi yaradır
- Video player URL-ləri ad blocker whitelist-ə əlavə edin

---

### 2. CORS Problemini Düzəldin

#### Backend-də CORS Yoxlayın

```php
// config/cors.php - Yoxlayın

'allowed_origins' => [
    'http://localhost:5173',
    'http://localhost:5174', // ✅ Bu olmalıdır
    'http://localhost:3000',
    // ... digər domain-lər
],
```

#### LessonMediaController-də CORS Header-ları

```php
// app/Http/Controllers/LessonMediaController.php

// Response header-larında CORS əlavə edin
return response($content, 200, [
    'Content-Type' => $mimeType,
    'Access-Control-Allow-Origin' => config('app.frontend_url', '*'),
    'Access-Control-Allow-Methods' => 'GET, OPTIONS',
    'Access-Control-Allow-Headers' => 'Content-Type, Authorization, Range',
    'Access-Control-Allow-Credentials' => 'true',
    'Access-Control-Expose-Headers' => 'Content-Range, Content-Length, Accept-Ranges',
]);
```

---

### 3. Referer Policy Problemini Düzəldin

#### Backend-də Referer Yoxlamasını Yumşaldın

```php
// app/Http/Controllers/LessonMediaController.php

// Signed URL üçün referer yoxlamasını yumşaldın
if ($isSignedUrl && $isVideoFile && !$isHLSPlaylistFile) {
    $allowedDomain = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
    $referer = $request->header('Referer');
    $refererMatches = $referer && (
        str_starts_with($referer, $allowedDomain) ||
        str_starts_with($referer, 'http://localhost:') ||
        str_starts_with($referer, 'http://127.0.0.1:')
    );
    $hasRangeHeader = $request->hasHeader('Range');
    
    // ✅ Yumşaldılmış yoxlama: Range header VƏ ya referer kifayətdir
    if (!$hasRangeHeader && !$refererMatches) {
        // Əgər heç biri yoxdursa, amma signed URL varsa, icazə ver
        // Çünki signed URL artıq təhlükəsizdir
        if (!$isSignedUrl) {
            return response()->json([
                'message' => 'Access denied. Videos can only be viewed in the training player.',
                'error' => 'direct_access_not_allowed'
            ], 403);
        }
    }
}
```

#### HLS Faylları Üçün Referer Yoxlamasını Aradan Qaldırın

```php
// app/Http/Controllers/LessonMediaController.php

// HLS playlist faylları (.m3u8, .ts) üçün referer yoxlaması yoxdur
if ($isHLSPlaylistFile) {
    // HLS faylları üçün heç bir referer yoxlaması yoxdur
    // Çünki bunlar video player tərəfindən avtomatik yüklənir
}
```

---

### 4. Signed URL Verification Problemini Düzəldin

#### Signed URL Generation-də Problem Yoxlayın

```php
// app/Http/Controllers/LessonMediaController.php

public static function verifySignedUrl($request, $module, $lesson, $filePath)
{
    $signature = $request->query('signature');
    $expires = $request->query('expires');
    
    if (!$signature || !$expires) {
        \Log::warning('Signed URL verification failed: missing signature or expires', [
            'has_signature' => $request->has('signature'),
            'has_expires' => $request->has('expires'),
            'url' => $request->fullUrl()
        ]);
        return false;
    }
    
    // Expiration yoxla
    if (time() > intval($expires)) {
        \Log::warning('Signed URL verification failed: expired', [
            'current_time' => time(),
            'expires' => intval($expires),
            'expires_date' => date('Y-m-d H:i:s', intval($expires))
        ]);
        return false;
    }
    
    // ✅ ƏHƏMİYYƏTLİ: userId null olmalıdır (browser access üçün)
    $signatureData = [
        'module_id' => $module->id,
        'lesson_id' => $lesson->id,
        'path' => $filePath,
        'user_id' => null, // ✅ Həmişə null (browser compatibility)
        'expires_at' => intval($expires)
    ];
    
    ksort($signatureData);
    $jsonString = json_encode($signatureData, JSON_UNESCAPED_SLASHES);
    $expectedSignature = hash_hmac('sha256', $jsonString, config('app.key'));
    
    if (!hash_equals($expectedSignature, $signature)) {
        \Log::warning('Signed URL verification failed: signature mismatch', [
            'module_id' => $module->id,
            'lesson_id' => $lesson->id,
            'path' => $filePath,
            'expected' => $expectedSignature,
            'provided' => $signature
        ]);
        return false;
    }
    
    return true;
}
```

---

### 5. Frontend-də Request Header-ları Əlavə Edin

#### Video Player-da Referer Header Əlavə Edin

```javascript
// VideoPlayer.vue - hls.js config

const hls = new Hls({
  enableWorker: true,
  lowLatencyMode: false,
  xhrSetup: function (xhr, url) {
    // ✅ Referer header əlavə et
    xhr.setRequestHeader('Referer', window.location.origin);
    xhr.withCredentials = false;
  }
});
```

#### Fetch Request-lərində Referer Əlavə Edin

```javascript
// api.js - Axios interceptor

api.interceptors.request.use(
  (config) => {
    // ✅ Referer header əlavə et
    config.headers['Referer'] = window.location.origin;
    
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);
```

---

### 6. Browser Console-da Xəta Yoxlayın

**Network tab-da request-ə klik edin və "Headers" tab-ına baxın:**

1. **Request Headers:**
   - `Referer` header var?
   - `Origin` header var?
   - `Range` header var? (video üçün)

2. **Response Headers:**
   - `Access-Control-Allow-Origin` var?
   - `Access-Control-Allow-Methods` var?
   - Status code nədir? (200, 403, 404?)

---

## 🔧 Debugging Addımları

### 1. Browser Console-da Yoxlayın

```javascript
// Console-da test edin
fetch('http://localhost:8000/api/v1/modules/2/lessons/5/media/download?path=lessons%2F5%2Fhls%2Fmaster.m3u8&signature=...&expires=...', {
  headers: {
    'Referer': window.location.origin
  }
})
.then(response => {
  console.log('✅ Success:', response.status);
  return response.text();
})
.then(data => console.log('Data:', data))
.catch(error => console.error('❌ Error:', error));
```

### 2. Backend Log-larını Yoxlayın

```bash
# Laravel log faylında yoxlayın
tail -f storage/logs/laravel.log

# Signed URL verification xətalarını axtarın
grep "Signed URL verification" storage/logs/laravel.log
```

### 3. Network Tab-da Request Detallarını Yoxlayın

1. Request-ə sağ klik → "Copy" → "Copy as cURL"
2. Terminal-də çalışdırın
3. Xəta mesajını yoxlayın

---

## 📋 Yoxlama Siyahısı

### Backend:
- [ ] CORS config düzgündür
- [ ] Frontend domain allow edilib
- [ ] Referer yoxlaması yumşaldılıb
- [ ] HLS faylları üçün referer yoxlaması yoxdur
- [ ] Signed URL verification düzgün işləyir

### Frontend:
- [ ] Referer header göndərilir
- [ ] Browser extension-lar söndürülüb (test üçün)
- [ ] Network tab-da request detalları yoxlanılıb

### Browser:
- [ ] Incognito mode-da test edilib
- [ ] Extension-lar söndürülüb
- [ ] Console-da xəta mesajları yoxlanılıb

---

## 🎯 Tez Həll

### 1. Backend-də Referer Yoxlamasını Yumşaldın

```php
// LessonMediaController.php - Line 63-82

// ✅ Yumşaldılmış versiya
if ($isSignedUrl && $isVideoFile && !$isHLSPlaylistFile) {
    $hasRangeHeader = $request->hasHeader('Range');
    
    // ✅ Signed URL varsa, referer yoxlaması yoxdur
    // Çünki signed URL artıq təhlükəsizdir
    if (!$hasRangeHeader) {
        // Yalnız Range header yoxdursa xəbərdarlıq ver, amma bloklama
        \Log::info('Video request without Range header', [
            'lesson_id' => $lesson->id,
            'has_signed_url' => true
        ]);
    }
}
```

### 2. Frontend-də Referer Header Əlavə Edin

```javascript
// api.js
api.interceptors.request.use((config) => {
  config.headers['Referer'] = window.location.origin;
  return config;
});
```

---

## ⚠️ Əgər Hələ Də Problem Varsa

1. **Browser Extension-ları Tamamilə Söndürün**
2. **Incognito Mode-da Test Edin**
3. **Backend Log-larını Yoxlayın** (`storage/logs/laravel.log`)
4. **Network Tab-da Request Detallarını Paylaşın**

---

**Son yeniləmə:** 2025-11-26

