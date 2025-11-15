# User Bildiriş Parametrləri - Tam Təlimat

## ✅ Sistem Dəstəkləyir!

Backend-də user öz bildiriş parametrlərini idarə edə bilir:
- ✅ **Push bildirişləri:** Aktiv/Deaktiv
- ✅ **Mail bildirişləri:** Aktiv/Deaktiv
- ✅ **Ayrı-ayrılıqda:** Hər biri müstəqil idarə olunur

---

## 🔌 API Endpoint-ləri

### 1. Cari Parametrləri Gətirmək

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

### 2. Parametrləri Yeniləmək

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

**Nümunə:**
- Push aktiv, Mail deaktiv → Yalnız push bildirişləri gəlir
- Push deaktiv, Mail aktiv → Yalnız mail bildirişləri gəlir
- Hər ikisi deaktiv → Heç bir bildiriş gəlmir

---

## 🔧 Backend-də Necə İşləyir?

### NotificationService

```php
// Push bildirişləri
if (in_array('push', $channels, true) && $user->wantsPushNotifications()) {
    event(new NotificationCreated($notification));
}

// Mail bildirişləri
if ($user && !$user->wantsEmailNotifications()) {
    return false; // Mail göndərilmir
}
```

### User Model

```php
public function wantsEmailNotifications(): bool
{
    return $this->email_notifications_enabled ?? true;
}

public function wantsPushNotifications(): bool
{
    return $this->push_notifications_enabled ?? true;
}
```

**Nəticə:**
- User `email_notifications_enabled = false` edərsə → Mail göndərilmir
- User `push_notifications_enabled = false` edərsə → Push göndərilmir
- Hər biri müstəqil işləyir

---

## 💻 Frontend İmplementasiyası

### Vue.js Nümunəsi

**`composables/useNotificationPreferences.js`:**

```javascript
import { ref } from 'vue';
import { useAuthStore } from './useAuthStore';

export function useNotificationPreferences() {
    const preferences = ref({
        email_notifications_enabled: true,
        push_notifications_enabled: true,
    });
    const isLoading = ref(false);
    const error = ref(null);

    const authStore = useAuthStore();
    const token = authStore.token;

    // Parametrləri gətir
    const fetchPreferences = async () => {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await fetch('/api/v1/notifications/preferences', {
                headers: {
                    Authorization: `Bearer ${token}`,
                    Accept: 'application/json',
                },
            });

            if (!response.ok) throw new Error('Failed to fetch preferences');

            const data = await response.json();
            preferences.value = data;
            return data;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Parametrləri yenilə
    const updatePreferences = async (newPreferences) => {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await fetch('/api/v1/notifications/preferences', {
                method: 'PATCH',
                headers: {
                    Authorization: `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify(newPreferences),
            });

            if (!response.ok) throw new Error('Failed to update preferences');

            const data = await response.json();
            preferences.value = {
                email_notifications_enabled: data.email_notifications_enabled,
                push_notifications_enabled: data.push_notifications_enabled,
            };
            return data;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    return {
        preferences,
        isLoading,
        error,
        fetchPreferences,
        updatePreferences,
    };
}
```

### Vue.js Komponenti

**`components/NotificationSettings.vue`:**

```vue
<template>
    <div class="notification-settings">
        <h2>Bildiriş Parametrləri</h2>

        <div class="setting-item">
            <label>
                <input
                    type="checkbox"
                    v-model="preferences.email_notifications_enabled"
                    @change="savePreferences"
                />
                Email bildirişləri
            </label>
            <p class="description">
                Email bildirişləri aktivdirsə, bildirişlər email-ə də göndəriləcək
            </p>
        </div>

        <div class="setting-item">
            <label>
                <input
                    type="checkbox"
                    v-model="preferences.push_notifications_enabled"
                    @change="savePreferences"
                />
                Push bildirişləri
            </label>
            <p class="description">
                Push bildirişləri aktivdirsə, real-time bildirişlər göstəriləcək
            </p>
        </div>

        <div v-if="isLoading" class="loading">
            Yüklənir...
        </div>

        <div v-if="error" class="error">
            {{ error }}
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useNotificationPreferences } from '@/composables/useNotificationPreferences';

const {
    preferences,
    isLoading,
    error,
    fetchPreferences,
    updatePreferences,
} = useNotificationPreferences();

const savePreferences = async () => {
    try {
        await updatePreferences({
            email_notifications_enabled: preferences.value.email_notifications_enabled,
            push_notifications_enabled: preferences.value.push_notifications_enabled,
        });
        // Success message
        console.log('✅ Parametrlər yeniləndi!');
    } catch (err) {
        console.error('❌ Xəta:', err);
    }
};

onMounted(() => {
    fetchPreferences();
});
</script>

<style scoped>
.notification-settings {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
}

.setting-item {
    margin-bottom: 20px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
}

.setting-item label {
    display: flex;
    align-items: center;
    font-weight: bold;
    cursor: pointer;
}

.setting-item input[type="checkbox"] {
    margin-right: 10px;
    width: 20px;
    height: 20px;
}

.description {
    margin-top: 5px;
    color: #666;
    font-size: 14px;
}

.loading {
    color: #007bff;
}

.error {
    color: #dc3545;
}
</style>
```

### React Nümunəsi

**`hooks/useNotificationPreferences.js`:**

```javascript
import { useState, useEffect } from 'react';

const useNotificationPreferences = () => {
    const [preferences, setPreferences] = useState({
        email_notifications_enabled: true,
        push_notifications_enabled: true,
    });
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState(null);

    const token = localStorage.getItem('auth_token');

    const fetchPreferences = async () => {
        setIsLoading(true);
        setError(null);

        try {
            const response = await fetch('/api/v1/notifications/preferences', {
                headers: {
                    Authorization: `Bearer ${token}`,
                    Accept: 'application/json',
                },
            });

            if (!response.ok) throw new Error('Failed to fetch preferences');

            const data = await response.json();
            setPreferences(data);
            return data;
        } catch (err) {
            setError(err.message);
            throw err;
        } finally {
            setIsLoading(false);
        }
    };

    const updatePreferences = async (newPreferences) => {
        setIsLoading(true);
        setError(null);

        try {
            const response = await fetch('/api/v1/notifications/preferences', {
                method: 'PATCH',
                headers: {
                    Authorization: `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify(newPreferences),
            });

            if (!response.ok) throw new Error('Failed to update preferences');

            const data = await response.json();
            setPreferences({
                email_notifications_enabled: data.email_notifications_enabled,
                push_notifications_enabled: data.push_notifications_enabled,
            });
            return data;
        } catch (err) {
            setError(err.message);
            throw err;
        } finally {
            setIsLoading(false);
        }
    };

    useEffect(() => {
        fetchPreferences();
    }, []);

    return {
        preferences,
        isLoading,
        error,
        fetchPreferences,
        updatePreferences,
    };
};

export default useNotificationPreferences;
```

---

## 🧪 Test

### 1. Parametrləri Gətir

```bash
curl -X GET http://localhost:8000/api/v1/notifications/preferences \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### 2. Parametrləri Yenilə

```bash
curl -X PATCH http://localhost:8000/api/v1/notifications/preferences \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email_notifications_enabled": false,
    "push_notifications_enabled": true
  }'
```

### 3. Test Bildiriş Göndər

Tinker-də:

```php
$user = App\Models\User::first();
$user->email_notifications_enabled = false;
$user->push_notifications_enabled = true;
$user->save();

// Bildiriş göndər
$notificationService = app(\App\Services\NotificationService::class);
$notificationService->send(
    $user,
    'system',
    ['az' => 'Test'],
    ['az' => 'Test mesajı'],
    [
        'channels' => ['database', 'push', 'mail'],
        'mail' => new \App\Mail\GenericNotificationMail(...),
    ]
);

// Nəticə:
// ✅ Database-ə yazılır
// ✅ Push göndərilir (push_notifications_enabled = true)
// ❌ Mail göndərilmir (email_notifications_enabled = false)
```

---

## ✅ Nəticə

**Sistem tam dəstəkləyir:**
- ✅ User push bildirişləri aktiv/deaktiv edə bilir
- ✅ User mail bildirişləri aktiv/deaktiv edə bilir
- ✅ Hər biri müstəqil işləyir
- ✅ API endpoint-ləri hazırdır
- ✅ Backend avtomatik yoxlayır

**Nümunə:**
- Push: ✅ Aktiv
- Mail: ❌ Deaktiv
- **Nəticə:** Yalnız push bildirişləri gəlir, mail göndərilmir

