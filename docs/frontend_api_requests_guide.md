# Frontend API Sorğuları - Tam Nümunələr

## 🔌 API Endpoint-ləri

### 1. Bildiriş Parametrlərini Gətirmək (GET)

**Endpoint:**
```
GET /api/v1/notifications/preferences
```

**Vue.js Nümunəsi:**

```javascript
// composables/useNotificationPreferences.js
export function useNotificationPreferences() {
    const token = localStorage.getItem('auth_token'); // və ya auth store-dan

    const fetchPreferences = async () => {
        try {
            const response = await fetch('http://localhost:8000/api/v1/notifications/preferences', {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Failed to fetch preferences');
            }

            const data = await response.json();
            return data;
            // Cavab: { email_notifications_enabled: true, push_notifications_enabled: true }
        } catch (error) {
            console.error('Error fetching preferences:', error);
            throw error;
        }
    };

    return { fetchPreferences };
}
```

**Axios ilə:**

```javascript
import axios from 'axios';

const fetchPreferences = async () => {
    try {
        const response = await axios.get('http://localhost:8000/api/v1/notifications/preferences', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                'Accept': 'application/json',
            },
        });
        
        return response.data;
    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
};
```

**React Nümunəsi:**

```javascript
const fetchPreferences = async () => {
    const token = localStorage.getItem('auth_token');
    
    try {
        const response = await fetch('http://localhost:8000/api/v1/notifications/preferences', {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
            },
        });

        if (!response.ok) throw new Error('Failed to fetch');

        return await response.json();
    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
};
```

---

### 2. Bildiriş Parametrlərini Yeniləmək (PATCH)

**Endpoint:**
```
PATCH /api/v1/notifications/preferences
```

**Vue.js Nümunəsi:**

```javascript
// composables/useNotificationPreferences.js
export function useNotificationPreferences() {
    const token = localStorage.getItem('auth_token');

    const updatePreferences = async (preferences) => {
        try {
            const response = await fetch('http://localhost:8000/api/v1/notifications/preferences', {
                method: 'PATCH',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    email_notifications_enabled: preferences.email_notifications_enabled,
                    push_notifications_enabled: preferences.push_notifications_enabled,
                }),
            });

            if (!response.ok) {
                throw new Error('Failed to update preferences');
            }

            const data = await response.json();
            return data;
            // Cavab: { message: "Bildiriş parametrləri yeniləndi", ... }
        } catch (error) {
            console.error('Error updating preferences:', error);
            throw error;
        }
    };

    return { updatePreferences };
}
```

**Axios ilə:**

```javascript
import axios from 'axios';

const updatePreferences = async (preferences) => {
    try {
        const response = await axios.patch(
            'http://localhost:8000/api/v1/notifications/preferences',
            {
                email_notifications_enabled: preferences.email_notifications_enabled,
                push_notifications_enabled: preferences.push_notifications_enabled,
            },
            {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
            }
        );
        
        return response.data;
    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
};
```

**React Nümunəsi:**

```javascript
const updatePreferences = async (preferences) => {
    const token = localStorage.getItem('auth_token');
    
    try {
        const response = await fetch('http://localhost:8000/api/v1/notifications/preferences', {
            method: 'PATCH',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                email_notifications_enabled: preferences.email_notifications_enabled,
                push_notifications_enabled: preferences.push_notifications_enabled,
            }),
        });

        if (!response.ok) throw new Error('Failed to update');

        return await response.json();
    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
};
```

---

## 📋 Bütün Bildiriş API-ləri

### 1. Bildirişləri Gətirmək (GET)

**Endpoint:**
```
GET /api/v1/notifications
```

**Query Parametrləri:**
- `per_page` (optional, default: 20)
- `type` (optional): `training`, `exam`, `system`, etc.
- `unread` (optional, boolean): Yalnız oxunmamış bildirişlər

**Nümunə:**

```javascript
// Bütün bildirişlər
const response = await fetch('http://localhost:8000/api/v1/notifications?per_page=20', {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
    },
});

// Yalnız oxunmamış bildirişlər
const response = await fetch('http://localhost:8000/api/v1/notifications?unread=true', {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
    },
});

// Yalnız training bildirişləri
const response = await fetch('http://localhost:8000/api/v1/notifications?type=training', {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
    },
});
```

### 2. Bildirişi Oxunmuş Kimi İşarələmək (POST)

**Endpoint:**
```
POST /api/v1/notifications/{notification_id}/read
```

**Nümunə:**

```javascript
const markAsRead = async (notificationId) => {
    const response = await fetch(
        `http://localhost:8000/api/v1/notifications/${notificationId}/read`,
        {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
            },
        }
    );
    
    return await response.json();
};
```

### 3. Bütün Bildirişləri Oxunmuş Kimi İşarələmək (POST)

**Endpoint:**
```
POST /api/v1/notifications/mark-all-read
```

**Nümunə:**

```javascript
const markAllAsRead = async () => {
    const response = await fetch(
        'http://localhost:8000/api/v1/notifications/mark-all-read',
        {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
            },
        }
    );
    
    return await response.json();
};
```

### 4. Oxunmamış Bildirişlərin Sayı (GET)

**Endpoint:**
```
GET /api/v1/notifications/unread-count
```

**Nümunə:**

```javascript
const getUnreadCount = async () => {
    const response = await fetch(
        'http://localhost:8000/api/v1/notifications/unread-count',
        {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
            },
        }
    );
    
    const data = await response.json();
    return data.count; // { count: 5 }
};
```

---

## 🔧 Base URL Konfiqurasiyası

### Vue.js / Nuxt.js

**`.env` faylı:**

```env
VITE_API_BASE_URL=http://localhost:8000
```

**`src/config/api.js`:**

```javascript
export const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000';

export const apiRequest = async (endpoint, options = {}) => {
    const token = localStorage.getItem('auth_token');
    
    const response = await fetch(`${API_BASE_URL}${endpoint}`, {
        ...options,
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...options.headers,
        },
    });

    if (!response.ok) {
        throw new Error(`API Error: ${response.status}`);
    }

    return await response.json();
};
```

**İstifadə:**

```javascript
import { apiRequest } from '@/config/api';

// GET
const preferences = await apiRequest('/api/v1/notifications/preferences');

// PATCH
const updated = await apiRequest('/api/v1/notifications/preferences', {
    method: 'PATCH',
    body: JSON.stringify({
        email_notifications_enabled: false,
        push_notifications_enabled: true,
    }),
});
```

### React

**`.env` faylı:**

```env
REACT_APP_API_BASE_URL=http://localhost:8000
```

**`src/config/api.js`:**

```javascript
export const API_BASE_URL = process.env.REACT_APP_API_BASE_URL || 'http://localhost:8000';

export const apiRequest = async (endpoint, options = {}) => {
    const token = localStorage.getItem('auth_token');
    
    const response = await fetch(`${API_BASE_URL}${endpoint}`, {
        ...options,
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...options.headers,
        },
    });

    if (!response.ok) {
        throw new Error(`API Error: ${response.status}`);
    }

    return await response.json();
};
```

---

## 📝 Tam Nümunə - Vue.js Composable

**`composables/useNotificationPreferences.js`:**

```javascript
import { ref } from 'vue';
import { apiRequest } from '@/config/api';

export function useNotificationPreferences() {
    const preferences = ref({
        email_notifications_enabled: true,
        push_notifications_enabled: true,
    });
    const isLoading = ref(false);
    const error = ref(null);

    // GET - Parametrləri gətir
    const fetchPreferences = async () => {
        isLoading.value = true;
        error.value = null;

        try {
            const data = await apiRequest('/api/v1/notifications/preferences');
            preferences.value = data;
            return data;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // PATCH - Parametrləri yenilə
    const updatePreferences = async (newPreferences) => {
        isLoading.value = true;
        error.value = null;

        try {
            const data = await apiRequest('/api/v1/notifications/preferences', {
                method: 'PATCH',
                body: JSON.stringify(newPreferences),
            });
            
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

---

## 🎯 Tam Nümunə - React Hook

**`hooks/useNotificationPreferences.js`:**

```javascript
import { useState, useEffect } from 'react';
import { apiRequest } from '../config/api';

const useNotificationPreferences = () => {
    const [preferences, setPreferences] = useState({
        email_notifications_enabled: true,
        push_notifications_enabled: true,
    });
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState(null);

    // GET - Parametrləri gətir
    const fetchPreferences = async () => {
        setIsLoading(true);
        setError(null);

        try {
            const data = await apiRequest('/api/v1/notifications/preferences');
            setPreferences(data);
            return data;
        } catch (err) {
            setError(err.message);
            throw err;
        } finally {
            setIsLoading(false);
        }
    };

    // PATCH - Parametrləri yenilə
    const updatePreferences = async (newPreferences) => {
        setIsLoading(true);
        setError(null);

        try {
            const data = await apiRequest('/api/v1/notifications/preferences', {
                method: 'PATCH',
                body: JSON.stringify(newPreferences),
            });
            
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

## 📊 API Endpoint-ləri Xülasəsi

| Metod | Endpoint | Açıqlama |
|-------|----------|----------|
| **GET** | `/api/v1/notifications/preferences` | Parametrləri gətir |
| **PATCH** | `/api/v1/notifications/preferences` | Parametrləri yenilə |
| **GET** | `/api/v1/notifications` | Bildirişləri gətir |
| **POST** | `/api/v1/notifications/{id}/read` | Bildirişi oxunmuş kimi işarələ |
| **POST** | `/api/v1/notifications/mark-all-read` | Hamısını oxunmuş kimi işarələ |
| **GET** | `/api/v1/notifications/unread-count` | Oxunmamış sayı |

---

## ✅ Hazırsınız!

Bütün endpoint-lər hazırdır və işləyir. Frontend-də yuxarıdakı nümunələri istifadə edin!

