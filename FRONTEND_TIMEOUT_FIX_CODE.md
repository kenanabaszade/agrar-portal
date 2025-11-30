# Frontend Timeout Probleminin Düzəldilməsi - Konkret Kod

## 🚨 Problem

Bütün API request-ləri 10 saniyədən sonra timeout verir:
- `useNotifications.js` - unread count və preferences
- `api.js` - training detailed
- `TrainingStart.vue` - training fetch

---

## ✅ Həll: 3 Faylı Düzəltmək Lazımdır

### 1. `src/api.js` və ya `src/utils/api.js` faylında

**Tapın və dəyişdirin:**

```javascript
// ❌ KÖHNƏ (timeout: 10000)
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8000/api/v1',
  timeout: 10000, // ❌ BU ÇOX QISADIR!
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});
```

**✅ YENİ:**

```javascript
// ✅ YENİ (timeout: 60000)
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8000/api/v1',
  timeout: 60000, // ✅ 60 saniyə (10 saniyə əvəzinə)
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

---

### 2. `src/composables/useNotifications.js` faylında

**Tapın və dəyişdirin:**

```javascript
// ❌ KÖHNƏ (line 67)
const fetchUnreadCount = async () => {
  try {
    const response = await api.get('/notifications/unread-count');
    return response.data;
  } catch (error) {
    console.error('Error fetching unread count:', error);
    throw error;
  }
};
```

**✅ YENİ:**

```javascript
// ✅ YENİ (line 67)
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
```

**Tapın və dəyişdirin:**

```javascript
// ❌ KÖHNƏ (line 124)
const fetchPreferences = async () => {
  try {
    const response = await api.get('/notifications/preferences');
    return response.data;
  } catch (error) {
    console.error('Error fetching preferences:', error);
    throw error;
  }
};
```

**✅ YENİ:**

```javascript
// ✅ YENİ (line 124)
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
```

---

### 3. `src/api.js` və ya `src/services/api.js` faylında - `getTrainingDetailed` funksiyası

**Tapın və dəyişdirin:**

```javascript
// ❌ KÖHNƏ (line 364)
export const getTrainingDetailed = async (trainingId, lang = 'az') => {
  try {
    const response = await api.get(`/trainings/${trainingId}/detailed`, {
      params: { lang }
    });
    return response.data;
  } catch (error) {
    console.error('Training Detailed API Error:', error);
    throw error;
  }
};
```

**✅ YENİ:**

```javascript
// ✅ YENİ (line 364)
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

### 4. `TrainingStart.vue` faylında (opsiyonel - əgər direkt api çağırırsa)

**Tapın və dəyişdirin:**

```javascript
// ❌ KÖHNƏ
const fetchTrainingData = async () => {
  try {
    const response = await fetch(`/api/v1/trainings/${this.trainingId}/detailed?lang=${this.lang}`);
    const data = await response.json();
    this.training = data;
  } catch (error) {
    console.error('Error fetching training:', error);
  }
};
```

**✅ YENİ:**

```javascript
// ✅ YENİ
const fetchTrainingData = async () => {
  try {
    // AbortController ilə timeout idarə et
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 60000); // 60 saniyə
    
    const response = await fetch(`/api/v1/trainings/${this.trainingId}/detailed?lang=${this.lang}`, {
      signal: controller.signal
    });
    
    clearTimeout(timeoutId);
    const data = await response.json();
    this.training = data;
  } catch (error) {
    if (error.name === 'AbortError') {
      console.error('Request timeout');
    } else {
      console.error('Error fetching training:', error);
    }
  }
};
```

**Və ya daha yaxşısı - api.js-dən istifadə et:**

```javascript
// ✅ YENİ (api.js-dən istifadə)
import { getTrainingDetailed } from '@/api';

const fetchTrainingData = async () => {
  try {
    const data = await getTrainingDetailed(this.trainingId, this.lang);
    this.training = data;
  } catch (error) {
    console.error('Error fetching training:', error);
  }
};
```

---

## 📋 Düzəliş Siyahısı

### ✅ Addım 1: `src/api.js` və ya `src/utils/api.js`
- [ ] `timeout: 10000` → `timeout: 60000` dəyişdirin
- [ ] Retry interceptor əlavə edin (opsiyonel, amma tövsiyə olunur)

### ✅ Addım 2: `src/composables/useNotifications.js`
- [ ] `fetchUnreadCount()` funksiyasında `timeout: 30000` əlavə edin
- [ ] `fetchPreferences()` funksiyasında `timeout: 30000` əlavə edin

### ✅ Addım 3: `src/api.js` və ya `src/services/api.js`
- [ ] `getTrainingDetailed()` funksiyasında `timeout: 60000` əlavə edin

### ✅ Addım 4: Test Edin
- [ ] Browser-də saytı yeniləyin
- [ ] Console-da timeout xətasının getmədiyini yoxlayın
- [ ] Network tab-da request-lərin uğurla tamamlandığını yoxlayın

---

## 🔍 Yoxlama

### Console-da Görməli Olduğunuz:

**❌ KÖHNƏ:**
```
Error fetching unread count: AxiosError {message: 'timeout of 10000ms exceeded'}
```

**✅ YENİ:**
```
✅ Notifications loaded successfully
✅ Training loaded successfully
```

### Network Tab-da:

**❌ KÖHNƏ:**
```
GET /notifications/unread-count → (canceled) → 10.00s
GET /trainings/2/detailed → (canceled) → 10.00s
```

**✅ YENİ:**
```
GET /notifications/unread-count → 200 → 0.5s
GET /trainings/2/detailed → 200 → 3.5s
```

---

## 🚀 Tez Həll (Copy-Paste)

### 1. `api.js` faylında:

```javascript
// Tapın: timeout: 10000
// Dəyişdirin: timeout: 60000
```

### 2. `useNotifications.js` faylında:

```javascript
// Line 67 - fetchUnreadCount funksiyasında
const response = await api.get('/notifications/unread-count', {
  timeout: 30000 // ✅ Bu sətri əlavə edin
});

// Line 124 - fetchPreferences funksiyasında
const response = await api.get('/notifications/preferences', {
  timeout: 30000 // ✅ Bu sətri əlavə edin
});
```

### 3. `api.js` faylında - getTrainingDetailed:

```javascript
// Line 364 - getTrainingDetailed funksiyasında
const response = await api.get(`/trainings/${trainingId}/detailed`, {
  params: { lang },
  timeout: 60000 // ✅ Bu sətri əlavə edin
});
```

---

## ⚠️ Əgər Hələ Də Problem Varsa

### 1. Browser Cache Təmizləyin
```
Ctrl + Shift + Delete → Clear cache
```

### 2. Dev Server-i Yenidən Başladın
```bash
npm run dev
# və ya
yarn dev
```

### 3. Backend-də Yoxlayın
- Backend server işləyir?
- Database connection düzgündür?
- Laravel log-larında xəta varmı?

---

## 📞 Əlavə Yardım

Əgər problem davam edirsə:
1. Browser console-da tam xəta mesajını göndərin
2. Network tab-da request detallarını göndərin
3. Backend log-larını yoxlayın (`storage/logs/laravel.log`)

---

**Son yeniləmə:** 2025-11-26

