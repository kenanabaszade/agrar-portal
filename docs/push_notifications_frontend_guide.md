# Real-Time Push Bildirişləri - Frontend Təlimatı

## 📋 Məzmun

1. [Sistemin Strukturu](#sistemin-strukturu)
2. [API Endpoint-ləri](#api-endpoint-ləri)
3. [Real-Time Konfiqurasiya](#real-time-konfiqurasiya)
4. [Kod Nümunələri](#kod-nümunələri)
5. [UI Komponentləri](#ui-komponentləri)
6. [Test və Debug](#test-və-debug)

---

## 🏗️ Sistemin Strukturu

### Backend-dən Gələn Bildiriş Tipləri

1. **Training (Təlim)** - `type: 'training'`
   - Yeni təlim yaradıldıqda
   - Təlim yeniləndikdə
   - Təlim tamamlandıqda

2. **Exam (İmtahan)** - `type: 'exam'`
   - İmtahan nəticəsi hazır olduqda

3. **Internship Program (Staj)** - `type: 'training'` (internship program üçün)

### Bildiriş Strukturu

```json
{
  "id": 1,
  "user_id": 5,
  "type": "training",
  "title": {
    "az": "Yeni təlim əlavə olundu",
    "en": "New training added"
  },
  "message": {
    "az": "Yeni təlim əlavə olundu: Laravel Backend Development",
    "en": "New training added: Laravel Backend Development"
  },
  "data": {
    "training_id": 123,
    "action": "created",
    "google_meet_link": "https://meet.google.com/..."
  },
  "channels": ["database", "push", "mail"],
  "is_read": false,
  "sent_at": "2025-11-15T10:30:00.000000Z",
  "created_at": "2025-11-15T10:30:00.000000Z"
}
```

---

## 🔌 API Endpoint-ləri

### 1. Bildirişləri Gətirmək

```http
GET /api/v1/notifications
Authorization: Bearer {token}
```

**Query Parametrləri:**
- `per_page` (optional, default: 20, max: 100) - Səhifədə neçə bildiriş
- `type` (optional) - Bildiriş tipi: `training`, `exam`, `system`, `payment`, `forum`
- `unread` (optional, boolean) - Yalnız oxunmamış bildirişlər

**Cavab:**
```json
{
  "data": [
    {
      "id": 1,
      "type": "training",
      "title": {"az": "Yeni təlim"},
      "message": {"az": "..."},
      "data": {...},
      "is_read": false,
      "sent_at": "2025-11-15T10:30:00.000000Z"
    }
  ],
  "links": {...},
  "meta": {
    "current_page": 1,
    "total": 50
  }
}
```

### 2. Bildirişi Oxunmuş Kimi İşarələmək

```http
POST /api/v1/notifications/{notification_id}/read
Authorization: Bearer {token}
```

**Cavab:**
```json
{
  "id": 1,
  "type": "training",
  "title": {"az": "Yeni təlim"},
  "message": {"az": "..."},
  "is_read": true,
  ...
}
```

### 3. Bütün Bildirişləri Oxunmuş Kimi İşarələmək

```http
POST /api/v1/notifications/mark-all-read
Authorization: Bearer {token}
```

**Cavab:**
```json
{
  "message": "Bütün bildirişlər oxundu kimi işarələndi"
}
```

### 4. Oxunmamış Bildirişlərin Sayı

```http
GET /api/v1/notifications/unread-count
Authorization: Bearer {token}
```

**Cavab:**
```json
{
  "count": 5
}
```

### 5. Bildiriş Parametrləri (Preferences)

**Gətirmək:**
```http
GET /api/v1/notifications/preferences
Authorization: Bearer {token}
```

**Cavab:**
```json
{
  "email_notifications_enabled": true,
  "push_notifications_enabled": true
}
```

**Yeniləmək:**
```http
PATCH /api/v1/notifications/preferences
Authorization: Bearer {token}
Content-Type: application/json

{
  "email_notifications_enabled": false,
  "push_notifications_enabled": true
}
```

**Cavab:**
```json
{
  "message": "Bildiriş parametrləri yeniləndi",
  "email_notifications_enabled": false,
  "push_notifications_enabled": true
}
```

---

## ⚡ Real-Time Konfiqurasiya

### 1. Paketləri Quraşdırmaq

```bash
npm install --save laravel-echo pusher-js
# və ya Redis üçün
npm install --save laravel-echo @pusher/pusher-js
```

### 2. Laravel Echo Konfiqurasiyası

**`resources/js/echo.js` və ya `src/echo.js` faylı yaradın:**

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Laravel Sanctum token ilə
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
    authEndpoint: '/api/broadcasting/auth',
    auth: {
        headers: {
            Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
            Accept: 'application/json',
        },
    },
});
```

### 3. .env Konfiqurasiyası

```env
# Broadcasting
BROADCAST_DRIVER=redis
QUEUE_CONNECTION=redis

# Pusher (Redis üçün də eyni format)
VITE_PUSHER_APP_KEY=your-app-key
VITE_PUSHER_APP_CLUSTER=mt1
VITE_PUSHER_HOST=localhost
VITE_PUSHER_PORT=6001
VITE_PUSHER_SCHEME=http
VITE_PUSHER_APP_ID=your-app-id
```

### 4. Broadcasting Auth Route (Backend)

Backend-də `routes/api.php`-də broadcasting auth route əlavə edin:

```php
Route::post('/broadcasting/auth', function (Request $request) {
    return Broadcast::auth($request);
})->middleware('auth:sanctum');
```

---

## 💻 Kod Nümunələri

### Vue.js / Nuxt.js Nümunəsi

**`composables/useNotifications.js`:**

```javascript
import { ref, onMounted, onUnmounted } from 'vue';
import { useAuthStore } from './useAuthStore';

export function useNotifications() {
    const notifications = ref([]);
    const unreadCount = ref(0);
    const isLoading = ref(false);
    const error = ref(null);

    const authStore = useAuthStore();
    const token = authStore.token;

    // Bildirişləri gətir
    const fetchNotifications = async (params = {}) => {
        isLoading.value = true;
        error.value = null;

        try {
            const queryParams = new URLSearchParams({
                per_page: params.perPage || 20,
                ...(params.type && { type: params.type }),
                ...(params.unread && { unread: 'true' }),
            });

            const response = await fetch(
                `/api/v1/notifications?${queryParams}`,
                {
                    headers: {
                        Authorization: `Bearer ${token}`,
                        Accept: 'application/json',
                    },
                }
            );

            if (!response.ok) throw new Error('Failed to fetch notifications');

            const data = await response.json();
            notifications.value = data.data;
            return data;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Oxunmamış sayını gətir
    const fetchUnreadCount = async () => {
        try {
            const response = await fetch('/api/v1/notifications/unread-count', {
                headers: {
                    Authorization: `Bearer ${token}`,
                    Accept: 'application/json',
                },
            });

            if (!response.ok) throw new Error('Failed to fetch unread count');

            const data = await response.json();
            unreadCount.value = data.count;
            return data.count;
        } catch (err) {
            console.error('Error fetching unread count:', err);
            return 0;
        }
    };

    // Bildirişi oxunmuş kimi işarələ
    const markAsRead = async (notificationId) => {
        try {
            const response = await fetch(
                `/api/v1/notifications/${notificationId}/read`,
                {
                    method: 'POST',
                    headers: {
                        Authorization: `Bearer ${token}`,
                        Accept: 'application/json',
                    },
                }
            );

            if (!response.ok) throw new Error('Failed to mark as read');

            // Local state-i yenilə
            const notification = notifications.value.find(
                (n) => n.id === notificationId
            );
            if (notification) {
                notification.is_read = true;
                if (unreadCount.value > 0) {
                    unreadCount.value--;
                }
            }

            return await response.json();
        } catch (err) {
            console.error('Error marking as read:', err);
            throw err;
        }
    };

    // Bütün bildirişləri oxunmuş kimi işarələ
    const markAllAsRead = async () => {
        try {
            const response = await fetch('/api/v1/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    Authorization: `Bearer ${token}`,
                    Accept: 'application/json',
                },
            });

            if (!response.ok) throw new Error('Failed to mark all as read');

            // Local state-i yenilə
            notifications.value.forEach((n) => {
                n.is_read = true;
            });
            unreadCount.value = 0;

            return await response.json();
        } catch (err) {
            console.error('Error marking all as read:', err);
            throw err;
        }
    };

    // Real-time bildirişləri dinlə
    const listenForNotifications = () => {
        if (!window.Echo || !authStore.user) {
            return;
        }

        const userId = authStore.user.id;
        const channel = `private-notifications.${userId}`;

        window.Echo.private(channel)
            .listen('.App\\Events\\NotificationCreated', (event) => {
                console.log('New notification received:', event);

                // Yeni bildirişi əlavə et
                notifications.value.unshift(event.notification);

                // Oxunmamış sayını artır
                if (!event.notification.is_read) {
                    unreadCount.value++;
                }

                // Toast notification göstər
                showNotificationToast(event.notification);
            });
    };

    // Toast notification göstər
    const showNotificationToast = (notification) => {
        // Burada toast library istifadə edə bilərsiniz (Vue Toastification, etc.)
        const locale = localStorage.getItem('locale') || 'az';
        const title = notification.title[locale] || notification.title.az;
        const message = notification.message[locale] || notification.message.az;

        // Nümunə: Vue Toastification
        // toast.success(message, { title });

        // Və ya custom toast
        console.log('New notification:', title, message);
    };

    // Bildiriş parametrlərini gətir
    const fetchPreferences = async () => {
        try {
            const response = await fetch('/api/v1/notifications/preferences', {
                headers: {
                    Authorization: `Bearer ${token}`,
                    Accept: 'application/json',
                },
            });

            if (!response.ok) throw new Error('Failed to fetch preferences');

            return await response.json();
        } catch (err) {
            console.error('Error fetching preferences:', err);
            throw err;
        }
    };

    // Bildiriş parametrlərini yenilə
    const updatePreferences = async (preferences) => {
        try {
            const response = await fetch('/api/v1/notifications/preferences', {
                method: 'PATCH',
                headers: {
                    Authorization: `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify(preferences),
            });

            if (!response.ok) throw new Error('Failed to update preferences');

            return await response.json();
        } catch (err) {
            console.error('Error updating preferences:', err);
            throw err;
        }
    };

    // Component mount olduqda
    onMounted(() => {
        fetchUnreadCount();
        fetchNotifications();
        listenForNotifications();
    });

    // Component unmount olduqda
    onUnmounted(() => {
        if (window.Echo && authStore.user) {
            const userId = authStore.user.id;
            window.Echo.leave(`private-notifications.${userId}`);
        }
    });

    return {
        notifications,
        unreadCount,
        isLoading,
        error,
        fetchNotifications,
        fetchUnreadCount,
        markAsRead,
        markAllAsRead,
        fetchPreferences,
        updatePreferences,
        listenForNotifications,
    };
}
```

### React Nümunəsi

**`hooks/useNotifications.js`:**

```javascript
import { useState, useEffect, useCallback } from 'react';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const useNotifications = () => {
    const [notifications, setNotifications] = useState([]);
    const [unreadCount, setUnreadCount] = useState(0);
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState(null);

    const token = localStorage.getItem('auth_token');

    // Echo instance
    const echo = new Echo({
        broadcaster: 'pusher',
        key: process.env.REACT_APP_PUSHER_APP_KEY,
        cluster: process.env.REACT_APP_PUSHER_APP_CLUSTER,
        wsHost: process.env.REACT_APP_PUSHER_HOST || window.location.hostname,
        wsPort: process.env.REACT_APP_PUSHER_PORT || 6001,
        forceTLS: (process.env.REACT_APP_PUSHER_SCHEME || 'https') === 'https',
        authEndpoint: '/api/broadcasting/auth',
        auth: {
            headers: {
                Authorization: `Bearer ${token}`,
                Accept: 'application/json',
            },
        },
    });

    // Bildirişləri gətir
    const fetchNotifications = useCallback(async (params = {}) => {
        setIsLoading(true);
        setError(null);

        try {
            const queryParams = new URLSearchParams({
                per_page: params.perPage || 20,
                ...(params.type && { type: params.type }),
                ...(params.unread && { unread: 'true' }),
            });

            const response = await fetch(
                `/api/v1/notifications?${queryParams}`,
                {
                    headers: {
                        Authorization: `Bearer ${token}`,
                        Accept: 'application/json',
                    },
                }
            );

            if (!response.ok) throw new Error('Failed to fetch notifications');

            const data = await response.json();
            setNotifications(data.data);
            return data;
        } catch (err) {
            setError(err.message);
            throw err;
        } finally {
            setIsLoading(false);
        }
    }, [token]);

    // Oxunmamış sayını gətir
    const fetchUnreadCount = useCallback(async () => {
        try {
            const response = await fetch('/api/v1/notifications/unread-count', {
                headers: {
                    Authorization: `Bearer ${token}`,
                    Accept: 'application/json',
                },
            });

            if (!response.ok) throw new Error('Failed to fetch unread count');

            const data = await response.json();
            setUnreadCount(data.count);
            return data.count;
        } catch (err) {
            console.error('Error fetching unread count:', err);
            return 0;
        }
    }, [token]);

    // Bildirişi oxunmuş kimi işarələ
    const markAsRead = useCallback(async (notificationId) => {
        try {
            const response = await fetch(
                `/api/v1/notifications/${notificationId}/read`,
                {
                    method: 'POST',
                    headers: {
                        Authorization: `Bearer ${token}`,
                        Accept: 'application/json',
                    },
                }
            );

            if (!response.ok) throw new Error('Failed to mark as read');

            setNotifications((prev) =>
                prev.map((n) =>
                    n.id === notificationId ? { ...n, is_read: true } : n
                )
            );
            setUnreadCount((prev) => Math.max(0, prev - 1));

            return await response.json();
        } catch (err) {
            console.error('Error marking as read:', err);
            throw err;
        }
    }, [token]);

    // Real-time dinlə
    useEffect(() => {
        const userId = JSON.parse(localStorage.getItem('user'))?.id;
        if (!userId) return;

        const channel = echo.private(`notifications.${userId}`);

        channel.listen('.App\\Events\\NotificationCreated', (event) => {
            console.log('New notification:', event);
            setNotifications((prev) => [event.notification, ...prev]);
            if (!event.notification.is_read) {
                setUnreadCount((prev) => prev + 1);
            }
        });

        return () => {
            echo.leave(`notifications.${userId}`);
        };
    }, [echo]);

    // İlk yükləmə
    useEffect(() => {
        fetchUnreadCount();
        fetchNotifications();
    }, [fetchUnreadCount, fetchNotifications]);

    return {
        notifications,
        unreadCount,
        isLoading,
        error,
        fetchNotifications,
        fetchUnreadCount,
        markAsRead,
    };
};

export default useNotifications;
```

---

## 🎨 UI Komponentləri

### Notification Bell Icon (Vue)

**`components/NotificationBell.vue`:**

```vue
<template>
    <div class="notification-bell">
        <button
            @click="toggleDropdown"
            class="relative p-2 rounded-full hover:bg-gray-100"
        >
            <svg
                class="w-6 h-6"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                />
            </svg>
            <span
                v-if="unreadCount > 0"
                class="absolute top-0 right-0 block h-4 w-4 rounded-full bg-red-500 text-white text-xs flex items-center justify-center"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <div
            v-if="isDropdownOpen"
            class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50"
        >
            <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="font-semibold">Bildirişlər</h3>
                <button
                    v-if="unreadCount > 0"
                    @click="markAllAsRead"
                    class="text-sm text-blue-600 hover:text-blue-800"
                >
                    Hamısını oxunmuş kimi işarələ
                </button>
            </div>

            <div class="max-h-96 overflow-y-auto">
                <div v-if="isLoading" class="p-4 text-center">
                    Yüklənir...
                </div>
                <div v-else-if="notifications.length === 0" class="p-4 text-center text-gray-500">
                    Bildiriş yoxdur
                </div>
                <div v-else>
                    <NotificationItem
                        v-for="notification in notifications"
                        :key="notification.id"
                        :notification="notification"
                        @read="markAsRead"
                    />
                </div>
            </div>

            <div class="p-4 border-t border-gray-200 text-center">
                <button
                    @click="$router.push('/notifications')"
                    class="text-sm text-blue-600 hover:text-blue-800"
                >
                    Bütün bildirişləri gör
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useNotifications } from '@/composables/useNotifications';
import NotificationItem from './NotificationItem.vue';

const {
    notifications,
    unreadCount,
    isLoading,
    fetchNotifications,
    markAsRead,
    markAllAsRead,
} = useNotifications();

const isDropdownOpen = ref(false);

const toggleDropdown = () => {
    isDropdownOpen.value = !isDropdownOpen.value;
    if (isDropdownOpen.value) {
        fetchNotifications({ perPage: 10 });
    }
};

onMounted(() => {
    fetchNotifications({ perPage: 10 });
});
</script>
```

### Notification Item Component

**`components/NotificationItem.vue`:**

```vue
<template>
    <div
        @click="handleClick"
        :class="[
            'p-4 border-b border-gray-100 hover:bg-gray-50 cursor-pointer',
            !notification.is_read && 'bg-blue-50'
        ]"
    >
        <div class="flex items-start">
            <div class="flex-1">
                <div class="flex items-center justify-between mb-1">
                    <h4 class="font-semibold text-sm">
                        {{ getLocalizedTitle }}
                    </h4>
                    <span
                        v-if="!notification.is_read"
                        class="h-2 w-2 bg-blue-500 rounded-full"
                    />
                </div>
                <p class="text-sm text-gray-600">
                    {{ getLocalizedMessage }}
                </p>
                <span class="text-xs text-gray-400 mt-1 block">
                    {{ formatDate(notification.sent_at) }}
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';

const props = defineProps({
    notification: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['read']);

const router = useRouter();
const locale = localStorage.getItem('locale') || 'az';

const getLocalizedTitle = computed(() => {
    return props.notification.title[locale] || props.notification.title.az;
});

const getLocalizedMessage = computed(() => {
    return props.notification.message[locale] || props.notification.message.az;
});

const formatDate = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (minutes < 1) return 'İndi';
    if (minutes < 60) return `${minutes} dəqiqə əvvəl`;
    if (hours < 24) return `${hours} saat əvvəl`;
    if (days < 7) return `${days} gün əvvəl`;
    return date.toLocaleDateString('az-AZ');
};

const handleClick = async () => {
    if (!props.notification.is_read) {
        emit('read', props.notification.id);
    }

    // Bildiriş tipinə görə route-a yönləndir
    const { type, data } = props.notification;

    if (type === 'training' && data?.training_id) {
        router.push(`/trainings/${data.training_id}`);
    } else if (type === 'exam' && data?.exam_id) {
        router.push(`/exams/${data.exam_id}/result`);
    }
};
</script>
```

---

## 🧪 Test və Debug

### 1. Broadcasting Connection Test

```javascript
// Console-da test edin
window.Echo.connector.pusher.connection.bind('connected', () => {
    console.log('✅ WebSocket connected!');
});

window.Echo.connector.pusher.connection.bind('disconnected', () => {
    console.log('❌ WebSocket disconnected!');
});

window.Echo.connector.pusher.connection.bind('error', (error) => {
    console.error('❌ WebSocket error:', error);
});
```

### 2. Channel Subscription Test

```javascript
const userId = 1; // Test user ID
const channel = window.Echo.private(`notifications.${userId}`);

channel.subscribed(() => {
    console.log('✅ Subscribed to notifications channel');
});

channel.error((error) => {
    console.error('❌ Channel error:', error);
});
```

### 3. Event Listen Test

```javascript
window.Echo.private(`notifications.${userId}`)
    .listen('.App\\Events\\NotificationCreated', (event) => {
        console.log('📬 Notification received:', event);
    });
```

---

## 📝 Qeydlər

1. **Token Management**: Token-i localStorage-dan və ya state management-dan götürün
2. **Error Handling**: Bütün API çağırışlarında error handling əlavə edin
3. **Loading States**: İstifadəçiyə loading göstərin
4. **Pagination**: Çox bildiriş olduqda pagination istifadə edin
5. **Localization**: Bildirişlərin title və message-i çoxdilli olduğu üçün locale-ə görə göstərin

---

## 🚀 Production Deployment

1. **Redis Server**: Production-da Redis server işə salın
2. **Queue Worker**: `php artisan queue:work` prosesi işləsin
3. **WebSocket Server**: Redis pub/sub üçün WebSocket server (Node.js Socket.IO) quraşdırın
4. **SSL**: Production-da HTTPS istifadə edin (WSS üçün)

---

## ❓ FAQ

**S: Bildirişlər gəlmir?**
- Redis server işləyir?
- Queue worker işləyir?
- Broadcasting auth route düzgündür?
- Token düzgündür?

**S: Real-time işləmir?**
- Echo düzgün konfiqurasiya olunub?
- Channel subscription uğurludur?
- WebSocket server işləyir?

**S: Bildirişlər çox gec gəlir?**
- Queue worker işləyir?
- Redis performansı yaxşıdır?


