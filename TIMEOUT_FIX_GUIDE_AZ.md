# Timeout Probleminin Həlli - Azərbaycan Dili

## 🔴 Problem

Frontend-də timeout xətası alınır:

```
AxiosError: timeout of 10000ms exceeded
```

**Xəta olan endpoint-lər:**
- `/api/v1/trainings/{id}/detailed` - Training detailed
- Notification endpoints
- Preferences endpoints

---

## 🔍 Səbəblər

1. **Backend yavaş işləyir** - Training detailed endpoint-i çox kompleksdir
2. **Database query-ləri yavaşdır** - Çox sayda join və eager loading
3. **Frontend timeout çox qısadır** - 10 saniyə kifayət etmir
4. **Network problemi** - Yavaş internet bağlantısı

---

## ✅ Həllər

### 1. Frontend-də Timeout Artırılması

#### Axios Config-də Timeout Artırın

```javascript
// api.js və ya axios config faylında

import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8000/api/v1',
  timeout: 30000, // ✅ 30 saniyə (10 saniyə əvəzinə)
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// Request interceptor
api.interceptors.request.use(
  (config) => {
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

// Response interceptor - timeout xətası üçün retry
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config;
    
    // Əgər timeout xətasıdırsa və hələ retry edilməyibsə
    if (error.code === 'ECONNABORTED' && !originalRequest._retry) {
      originalRequest._retry = true;
      
      // 2 saniyə gözlə və yenidən cəhd et
      await new Promise(resolve => setTimeout(resolve, 2000));
      
      // Timeout-u artır və yenidən cəhd et
      originalRequest.timeout = 60000; // 60 saniyə
      
      return api(originalRequest);
    }
    
    return Promise.reject(error);
  }
);

export default api;
```

#### useNotifications.js-də Timeout Artırın

```javascript
// useNotifications.js

import api from '@/api'; // Axios instance

export const useNotifications = () => {
  const fetchUnreadCount = async () => {
    try {
      const response = await api.get('/notifications/unread-count', {
        timeout: 30000 // ✅ 30 saniyə
      });
      return response.data;
    } catch (error) {
      console.error('Error fetching unread count:', error);
      throw error;
    }
  };
  
  const fetchPreferences = async () => {
    try {
      const response = await api.get('/notifications/preferences', {
        timeout: 30000 // ✅ 30 saniyə
      });
      return response.data;
    } catch (error) {
      console.error('Error fetching preferences:', error);
      throw error;
    }
  };
  
  // ... qalan kod
};
```

#### TrainingStart.vue-də Timeout Artırın

```javascript
// TrainingStart.vue - api.js-də getTrainingDetailed metodu

// api.js
export const getTrainingDetailed = async (trainingId, lang = 'az') => {
  try {
    const response = await api.get(`/trainings/${trainingId}/detailed`, {
      params: { lang },
      timeout: 60000 // ✅ 60 saniyə (training detailed çox kompleksdir)
    });
    return response.data;
  } catch (error) {
    console.error('Training Detailed API Error:', error);
    throw error;
  }
};
```

---

### 2. Retry Mexanizmi Əlavə Edin

#### Retry Helper Function

```javascript
// utils/retry.js

export const retryRequest = async (requestFn, maxRetries = 3, delay = 2000) => {
  for (let i = 0; i < maxRetries; i++) {
    try {
      return await requestFn();
    } catch (error) {
      // Əgər son cəhddirsə, xətanı at
      if (i === maxRetries - 1) {
        throw error;
      }
      
      // Əgər timeout xətasıdırsa, gözlə və yenidən cəhd et
      if (error.code === 'ECONNABORTED' || error.message.includes('timeout')) {
        console.warn(`Request timeout, retrying... (${i + 1}/${maxRetries})`);
        await new Promise(resolve => setTimeout(resolve, delay * (i + 1)));
        continue;
      }
      
      // Digər xətalar üçün dərhal at
      throw error;
    }
  }
};
```

#### İstifadəsi

```javascript
// TrainingStart.vue

import { retryRequest } from '@/utils/retry';

const fetchTrainingData = async () => {
  try {
    this.loading = true;
    
    // Retry mexanizmi ilə request göndər
    const data = await retryRequest(
      () => getTrainingDetailed(this.trainingId, this.lang),
      3, // 3 dəfə cəhd et
      2000 // 2 saniyə gözlə
    );
    
    this.training = data;
    this.findVideoFile();
  } catch (error) {
    console.error('Error fetching training:', error);
    this.error = 'Training yüklənə bilmədi. Zəhmət olmasa yenidən yoxlayın.';
  } finally {
    this.loading = false;
  }
};
```

---

### 3. Backend-də Timeout Artırılması

#### PHP.ini Ayarları

```ini
; php.ini faylında
max_execution_time = 300  ; ✅ 5 dəqiqə (default: 30 saniyə)
memory_limit = 256M       ; ✅ 256 MB (default: 128M)
```

#### Laravel Config-də Timeout

```php
// config/app.php və ya bootstrap/app.php

// Request timeout
ini_set('max_execution_time', 300); // 5 dəqiqə

// Memory limit
ini_set('memory_limit', '256M');
```

#### TrainingController-də Timeout Artırın

```php
// app/Http/Controllers/TrainingController.php

public function detailed(Request $request, Training $training)
{
    // ✅ Timeout artır (10 dəqiqə)
    @ini_set('max_execution_time', 600);
    @ini_set('memory_limit', '512M');
    
    // ... qalan kod
}
```

---

### 4. Backend-də Performans Optimizasiyası

#### Database Query Optimizasiyası

```php
// app/Http/Controllers/TrainingController.php

public function detailed(Request $request, Training $training)
{
    // ✅ Eager loading ilə N+1 problemi həll et
    $training->load([
        'modules.lessons' => function ($query) {
            // Yalnız lazım olan field-ləri seç
            $query->select([
                'id',
                'module_id',
                'title',
                'sequence',
                'lesson_type',
                'duration_minutes',
                'status',
                'is_required',
                'media_files',
                'created_at',
                'updated_at'
            ]);
        },
        'trainer:id,first_name,last_name,email',
        'exam:id,title'
    ]);
    
    // ✅ User progress-i ayrıca query ilə yüklə (lazy loading)
    if (auth()->check()) {
        $userProgress = UserTrainingProgress::where('user_id', auth()->id())
            ->where('training_id', $training->id)
            ->with('lesson:id,title,module_id')
            ->get()
            ->keyBy('lesson_id');
        
        $training->user_progress_data = $userProgress;
    }
    
    // ... qalan kod
}
```

#### Caching Əlavə Edin

```php
// app/Http/Controllers/TrainingController.php

public function detailed(Request $request, Training $training)
{
    $cacheKey = "training_detailed_{$training->id}_{$request->get('lang', 'az')}";
    
    // ✅ Cache-dən yoxla (5 dəqiqə)
    return Cache::remember($cacheKey, 300, function () use ($training, $request) {
        // ... kompleks query-lər
        
        return $training;
    });
}
```

#### Signed URL Generation Optimizasiyası

```php
// app/Http/Controllers/TrainingController.php

// Media files üçün signed URL-ləri batch-də yarat
$mediaFiles = collect($lesson->media_files ?? [])->map(function ($mediaFile) use ($module, $lesson) {
    // ... signed URL generation
    
    // ✅ Əgər video faylıdırsa, HLS məlumatlarını lazy load et
    if ($mediaFile['type'] === 'video' && isset($mediaFile['hls_master_playlist'])) {
        // HLS URL-ləri yalnız lazım olduqda yarat
        $hlsMasterPlaylist = $mediaFile['hls_master_playlist'];
        
        try {
            $hlsSignedUrl = \App\Http\Controllers\LessonMediaController::generateSignedUrl(
                $module,
                $lesson,
                $hlsMasterPlaylist,
                null,
                120
            );
            
            $mediaFile['hls_master_playlist_url'] = $hlsSignedUrl;
            
            // Variants üçün də signed URL-lər yarat
            if (!empty($mediaFile['hls_variants'])) {
                foreach ($mediaFile['hls_variants'] as $quality => $variant) {
                    if (isset($variant['playlist'])) {
                        $variantSignedUrl = \App\Http\Controllers\LessonMediaController::generateSignedUrl(
                            $module,
                            $lesson,
                            $variant['playlist'],
                            null,
                            120
                        );
                        $mediaFile['hls_variants'][$quality]['playlist_url'] = $variantSignedUrl;
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to generate HLS signed URL', [
                'error' => $e->getMessage(),
                'lesson_id' => $lesson->id
            ]);
        }
    }
    
    return $mediaFile;
})->toArray();
```

---

### 5. Frontend-də Loading State Göstərin

```vue
<!-- TrainingStart.vue -->

<template>
  <div class="training-start">
    <!-- Loading Spinner -->
    <div v-if="loading" class="loading-container">
      <div class="spinner"></div>
      <p>Training yüklənir... Bu bir neçə saniyə çəkə bilər.</p>
      <p class="loading-hint">Zəhmət olmasa gözləyin...</p>
    </div>
    
    <!-- Error Message -->
    <div v-else-if="error" class="error-container">
      <p>{{ error }}</p>
      <button @click="fetchTrainingData" class="retry-button">
        Yenidən yoxla
      </button>
    </div>
    
    <!-- Content -->
    <div v-else-if="training">
      <!-- ... training content -->
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      loading: true,
      error: null,
      retryCount: 0
    };
  },
  methods: {
    async fetchTrainingData() {
      try {
        this.loading = true;
        this.error = null;
        
        // Timeout artırılmış request
        const data = await getTrainingDetailed(this.trainingId, this.lang);
        
        this.training = data;
        this.findVideoFile();
        this.retryCount = 0; // Reset retry count
      } catch (error) {
        console.error('Error fetching training:', error);
        
        // Əgər timeout xətasıdırsa, retry et
        if (error.code === 'ECONNABORTED' && this.retryCount < 3) {
          this.retryCount++;
          console.log(`Retrying... (${this.retryCount}/3)`);
          
          // 2 saniyə gözlə və yenidən cəhd et
          await new Promise(resolve => setTimeout(resolve, 2000));
          return this.fetchTrainingData();
        }
        
        this.error = 'Training yüklənə bilmədi. Zəhmət olmasa yenidən yoxlayın.';
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style scoped>
.loading-container {
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

.loading-hint {
  color: #666;
  font-size: 14px;
}

.error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
  padding: 40px;
}

.retry-button {
  padding: 10px 20px;
  background-color: #3498db;
  color: white;
  border: none;
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

## 📋 Yoxlama Siyahısı

### Frontend:
- [ ] Axios timeout 30-60 saniyəyə artırılıb
- [ ] Retry mexanizmi əlavə edilib
- [ ] Loading state düzgün göstərilir
- [ ] Error handling düzgün işləyir

### Backend:
- [ ] PHP max_execution_time artırılıb (300 saniyə)
- [ ] Memory limit artırılıb (256M)
- [ ] Database query-ləri optimizasiya edilib
- [ ] Caching əlavə edilib (opsiyonel)

---

## 🎯 Tez Həll

### 1. Frontend-də (Dərhal)

```javascript
// api.js
const api = axios.create({
  baseURL: 'http://localhost:8000/api/v1',
  timeout: 60000, // ✅ 60 saniyə
});
```

### 2. Backend-də (Dərhal)

```php
// TrainingController.php - detailed() metodunda
@ini_set('max_execution_time', 600); // 10 dəqiqə
@ini_set('memory_limit', '512M');
```

---

## 🔍 Debugging

### Network Tab-da Yoxlayın

1. **Request Time:**
   - Normal: 1-3 saniyə
   - Yavaş: 10-30 saniyə
   - Problem: 30+ saniyə

2. **Response Size:**
   - Kiçik: < 1 MB
   - Orta: 1-5 MB
   - Böyük: 5+ MB (bu problem ola bilər)

3. **Status Code:**
   - `200` = Uğurlu
   - `504` = Gateway Timeout
   - `ECONNABORTED` = Client timeout

---

## 📊 Performans Metrikləri

### İdeal Vaxtlar:
- Training list: < 1 saniyə
- Training detailed: < 3 saniyə
- Notifications: < 1 saniyə

### Problemli Vaxtlar:
- Training detailed: > 10 saniyə → Optimizasiya lazımdır
- Notifications: > 5 saniyə → Optimizasiya lazımdır

---

## 🚀 Əlavə Optimizasiyalar

### 1. Lazy Loading

```javascript
// TrainingStart.vue - Video faylını lazy load et
const findVideoFile = async () => {
  // Video faylını ayrıca request ilə yüklə
  if (this.currentLesson?.id) {
    const response = await api.get(`/lessons/${this.currentLesson.id}/media`);
    this.videoFile = response.data.video;
  }
};
```

### 2. Pagination

```javascript
// Training detailed-də modules və lessons pagination
const response = await api.get(`/trainings/${id}/detailed`, {
  params: {
    lang: 'az',
    include_modules: true,
    modules_page: 1,
    lessons_page: 1
  }
});
```

---

**Son yeniləmə:** 2025-11-26

