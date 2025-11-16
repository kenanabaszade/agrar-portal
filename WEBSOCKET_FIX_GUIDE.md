# WebSocket Xətasının Həlli

## Problem

Frontend-də aşağıdakı xəta görünür:
```
WebSocket connection to 'ws://localhost:6001/app/your-app-key' failed
```

**Səbəb:** `VITE_PUSHER_APP_KEY` environment variable-ı `your-app-key` placeholder dəyərinə qoyulub.

---

## Həll Addımları

### 1. Paketləri Quraşdırın

```bash
npm install
```

Bu komanda `laravel-echo` və `pusher-js` paketlərini quraşdıracaq.

---

### 2. Backend `.env` Faylını Konfiqurasiya Edin

Backend-də `.env` faylına aşağıdakı dəyərləri əlavə edin:

```env
# Broadcasting Configuration
BROADCAST_DRIVER=pusher

# Pusher Configuration (Laravel Reverb üçün)
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_HOST=localhost
PUSHER_PORT=6001
PUSHER_SCHEME=http
PUSHER_APP_CLUSTER=mt1
```

**Qeyd:** 
- `PUSHER_APP_KEY` - Bu dəyər frontend-də `VITE_PUSHER_APP_KEY` ilə eyni olmalıdır
- `PUSHER_APP_ID` və `PUSHER_APP_SECRET` - Laravel Reverb serveri üçün lazımdır
- Development üçün `PUSHER_SCHEME=http` və `PUSHER_PORT=6001` istifadə edin

---

### 3. Frontend `.env` Faylını Konfiqurasiya Edin

Frontend-də `.env` faylına (və ya `.env.local`) aşağıdakı dəyərləri əlavə edin:

```env
# Pusher/WebSocket Configuration
VITE_PUSHER_APP_KEY=your-app-key
VITE_PUSHER_APP_CLUSTER=mt1
VITE_PUSHER_HOST=localhost
VITE_PUSHER_PORT=6001
VITE_PUSHER_SCHEME=http
VITE_PUSHER_APP_ID=your-app-id
```

**⚠️ VACİB:** 
- `VITE_PUSHER_APP_KEY` dəyəri backend-dəki `PUSHER_APP_KEY` ilə **tam eyni** olmalıdır
- `your-app-key` yerinə **real dəyər** yazın (məsələn: `abc123xyz`)

---

### 4. Laravel Reverb Serverini İşə Salın

Laravel Reverb WebSocket serverini işə salmaq üçün:

```bash
php artisan reverb:start
```

Və ya development üçün:

```bash
php artisan reverb:start --host=localhost --port=6001
```

**Qeyd:** Əgər Laravel Reverb quraşdırılmayıbsa:

```bash
composer require laravel/reverb
php artisan reverb:install
```

---

### 5. Config Cache-i Təmizləyin

Backend-də:

```bash
php artisan config:clear
php artisan cache:clear
```

---

### 6. Frontend Build-i Yeniləyin

Frontend-də:

```bash
npm run dev
```

Və ya production üçün:

```bash
npm run build
```

---

## Test Etmək

1. **Backend Reverb serveri işləyir:**
   ```bash
   php artisan reverb:start
   ```

2. **Frontend development serveri işləyir:**
   ```bash
   npm run dev
   ```

3. **Browser Console-da yoxlayın:**
   - Artıq `your-app-key` xətası görünməməlidir
   - WebSocket bağlantısı uğurlu olmalıdır

---

## Alternativ: Redis Broadcasting İstifadə Edin

Əgər Pusher istifadə etmək istəmirsinizsə, Redis istifadə edə bilərsiniz:

### Backend `.env`:
```env
BROADCAST_DRIVER=redis
```

### Frontend `echo.js`:
```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    wsHost: import.meta.env.VITE_PUSHER_HOST || window.location.hostname,
    wsPort: import.meta.env.VITE_PUSHER_PORT || 6001,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
    authEndpoint: '/api/v1/broadcasting/auth',
});
```

---

## Problem Həlləri

### Problem: "Connection failed" xətası

**Həll:**
1. Reverb serveri işləyir? `php artisan reverb:start`
2. Port 6001 açıqdır? Firewall yoxlayın
3. `.env` dəyərləri düzgündürmü? `VITE_PUSHER_APP_KEY` və `PUSHER_APP_KEY` eynidirmi?

### Problem: "Authentication failed"

**Həll:**
1. `routes/api.php`-də broadcasting auth route var?
2. Token localStorage-da varmı?
3. CORS konfiqurasiyası düzgündürmü?

### Problem: "your-app-key" hələ də görünür

**Həll:**
1. Frontend `.env` faylında `VITE_PUSHER_APP_KEY` dəyərini dəyişdirin
2. `npm run dev` komandasını yenidən işə salın
3. Browser cache-i təmizləyin (Ctrl+Shift+R)

---

## Əlavə Məlumat

- Laravel Reverb: https://laravel.com/docs/reverb
- Laravel Broadcasting: https://laravel.com/docs/broadcasting
- Pusher JS: https://github.com/pusher/pusher-js

