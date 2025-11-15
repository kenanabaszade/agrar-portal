# Frontend Real-Time Bildirişlər - Sürətli Başlanğıc

## 📦 Addım 1: Paketləri Quraşdırın

```bash
npm install laravel-echo pusher-js
```

---

## ⚙️ Addım 2: Laravel Echo Konfiqurasiyası

### Vue.js / Nuxt.js üçün

**`resources/js/echo.js` və ya `src/echo.js` faylı yaradın:**

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    wsHost: import.meta.env.VITE_PUSHER_HOST || window.location.hostname,
    wsPort: import.meta.env.VITE_PUSHER_PORT || 6001,
    wssPort: import.meta.env.VITE_PUSHER_PORT || 6001,
    forceTLS: (import.meta.env.VITE_PUSHER_SCHEME || 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/api/v1/broadcasting/auth',
    auth: {
        headers: {
            Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
            Accept: 'application/json',
        },
    },
});

export default window.Echo;
```

### React üçün

**`src/echo.js` faylı yaradın:**

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.REACT_APP_PUSHER_APP_KEY,
    cluster: process.env.REACT_APP_PUSHER_APP_CLUSTER,
    wsHost: process.env.REACT_APP_PUSHER_HOST || window.location.hostname,
    wsPort: process.env.REACT_APP_PUSHER_PORT || 6001,
    forceTLS: (process.env.REACT_APP_PUSHER_SCHEME || 'https') === 'https',
    authEndpoint: '/api/v1/broadcasting/auth',
    auth: {
        headers: {
            Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
            Accept: 'application/json',
        },
    },
});

export default echo;
```

---

## 🔧 Addım 3: .env Konfiqurasiyası

Frontend `.env` faylına əlavə edin:

```env
# Pusher/Redis Broadcasting
VITE_PUSHER_APP_KEY=your-app-key
VITE_PUSHER_APP_CLUSTER=mt1
VITE_PUSHER_HOST=localhost
VITE_PUSHER_PORT=6001
VITE_PUSHER_SCHEME=http
VITE_PUSHER_APP_ID=your-app-id
```

**Qeyd:** Backend-də `.env` faylında da eyni dəyərlər olmalıdır.

---

## 💻 Addım 4: Echo Import Edin

### Vue.js / Nuxt.js

**`main.js` və ya `app.js`:**

```javascript
import './echo';
```

### React

**`index.js` və ya `App.js`:**

```javascript
import './echo';
```

---

## 🎧 Addım 5: Bildirişləri Dinləyin

### Vue.js Composable

**`composables/useNotifications.js`:**

```javascript
import { ref, onMounted, onUnmounted } from 'vue';
import { useAuthStore } from './useAuthStore'; // və ya token store

export function useNotifications() {
    const notifications = ref([]);
    const unreadCount = ref(0);
    const authStore = useAuthStore();

    const listenForNotifications = () => {
        if (!window.Echo || !authStore.user) {
            return;
        }

        const userId = authStore.user.id;
        const channel = `private-notifications.${userId}`;

        window.Echo.private(channel)
            .listen('.App\\Events\\NotificationCreated', (event) => {
                console.log('📬 Yeni bildiriş:', event);

                // Yeni bildirişi əlavə et
                notifications.value.unshift(event.notification);

                // Oxunmamış sayını artır
                if (!event.notification.is_read) {
                    unreadCount.value++;
                }

                // Toast notification göstər
                showToast(event.notification);
            });
    };

    const showToast = (notification) => {
        const locale = localStorage.getItem('locale') || 'az';
        const title = notification.title[locale] || notification.title.az;
        const message = notification.message[locale] || notification.message.az;

        // Toast library istifadə edin (Vue Toastification, etc.)
        // toast.success(message, { title });
        
        // Və ya console
        console.log('🔔 Bildiriş:', title, message);
    };

    onMounted(() => {
        listenForNotifications();
    });

    onUnmounted(() => {
        if (window.Echo && authStore.user) {
            const userId = authStore.user.id;
            window.Echo.leave(`private-notifications.${userId}`);
        }
    });

    return {
        notifications,
        unreadCount,
        listenForNotifications,
    };
}
```

### React Hook

**`hooks/useNotifications.js`:**

```javascript
import { useState, useEffect } from 'react';
import Echo from '../echo';

const useNotifications = () => {
    const [notifications, setNotifications] = useState([]);
    const [unreadCount, setUnreadCount] = useState(0);

    useEffect(() => {
        const userId = JSON.parse(localStorage.getItem('user'))?.id;
        if (!userId) return;

        const channel = echo.private(`notifications.${userId}`);

        channel.listen('.App\\Events\\NotificationCreated', (event) => {
            console.log('📬 Yeni bildiriş:', event);
            setNotifications((prev) => [event.notification, ...prev]);
            if (!event.notification.is_read) {
                setUnreadCount((prev) => prev + 1);
            }
        });

        return () => {
            echo.leave(`notifications.${userId}`);
        };
    }, []);

    return { notifications, unreadCount };
};

export default useNotifications;
```

---

## 🔔 Addım 6: Notification Bell Komponenti

### Vue.js

**`components/NotificationBell.vue`:**

```vue
<template>
    <div class="notification-bell">
        <button @click="toggleDropdown" class="relative p-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <span v-if="unreadCount > 0"
                class="absolute top-0 right-0 block h-4 w-4 rounded-full bg-red-500 text-white text-xs">
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>
    </div>
</template>

<script setup>
import { useNotifications } from '@/composables/useNotifications';

const { unreadCount } = useNotifications();

const toggleDropdown = () => {
    // Dropdown aç/bağla
};
</script>
```

---

## 📡 Addım 7: Backend Broadcasting Auth

Backend-də `routes/api.php` faylında artıq var:

```php
Route::post('broadcasting/auth', function (\Illuminate\Http\Request $request) {
    return \Illuminate\Support\Facades\Broadcast::auth($request);
})->middleware('auth:sanctum');
```

---

## 🧪 Addım 8: Test Edin

1. **Frontend işə salın:**
   ```bash
   npm run dev
   ```

2. **Backend queue worker işə salın:**
   ```bash
   php artisan queue:work
   ```

3. **Test bildiriş göndərin:**
   - Training yaradın
   - Və ya Tinker-də test bildiriş göndərin

4. **Browser Console-da görün:**
   - `📬 Yeni bildiriş:` mesajı görünməlidir
   - Bildiriş real-time gəlməlidir

---

## ❓ Problem Həlləri

### Problem: "Connection failed"

**Həll:**
1. Backend-də Redis işləyirmi yoxlayın
2. Queue worker işləyirmi yoxlayın
3. `.env` faylında `VITE_PUSHER_*` dəyərləri düzgündürmü yoxlayın

### Problem: "Authentication failed"

**Həll:**
1. Token localStorage-da varmı yoxlayın
2. Broadcasting auth route düzgündürmü yoxlayın
3. Token düzgündürmü yoxlayın

### Problem: "Channel subscription failed"

**Həll:**
1. User ID düzgündürmü yoxlayın
2. Channel adı düzgündürmü yoxlayın: `private-notifications.{userId}`
3. Backend-də `routes/channels.php` düzgündürmü yoxlayın

---

## ✅ Hazırsınız!

İndi:
1. ✅ Paketlər quraşdırıldı
2. ✅ Echo konfiqurasiya olundu
3. ✅ Bildirişlər dinlənir
4. ✅ Real-time işləyir

**Tam təlimat:** `docs/push_notifications_frontend_guide.md` faylındadır.

