# Push Bildirişləri - İzahat

## ✅ Backend Düzgün İşləyir

Log-da görünür:
```
[2025-11-15 14:32:34] local.INFO: Push notification skipped - user disabled 
{"user_id":1,"push_notifications_enabled":false}
```

**Bu o deməkdir ki:**
- ✅ Backend `wantsPushNotifications()` yoxlayır
- ✅ `push_notifications_enabled = false` olduqda push göndərilmir
- ✅ Real-time event (`NotificationCreated`) göndərilmir

---

## 📊 Bildirişlər Necə İşləyir?

### 1. Bildiriş Yaradılır

```php
$notificationService->send(
    $user,
    'training',
    ['az' => 'Yeni təlim'],
    ['az' => 'Mesaj'],
    ['channels' => ['database', 'push']]
);
```

**Nə baş verir:**
1. ✅ Bildiriş **həmişə** database-ə yazılır
2. ✅ `push_notifications_enabled = true` olduqda → Real-time event göndərilir
3. ✅ `push_notifications_enabled = false` olduqda → Real-time event göndərilmir

### 2. Frontend-də Görünmə

**Frontend-də 2 yol var:**

#### A) Real-Time Event (WebSocket)
- `NotificationCreated` event-i gəlir
- Yalnız `push_notifications_enabled = true` olduqda gəlir
- ✅ Backend düzgün işləyir - event göndərilmir

#### B) Database-dən Gətirmək (API)
- `GET /api/v1/notifications` endpoint-i
- Bildirişlər database-dən götürülür
- ⚠️ Bu "push bildirişi" deyil, bu "database bildirişi"dir

---

## 🔍 Problem Nədir?

**User deyir:** "Push bildirişləri alıram"

**Ola bilər:**
1. Frontend-də bildirişlər database-dən götürülür (API ilə)
2. Və ya frontend-də real-time dinləyir, amma event gəlir (bu ola bilməz, çünki backend skip edir)

---

## ✅ Həll

### Seçim 1: Frontend-də Yoxlama

Frontend-də bildirişləri göstərməzdən əvvəl user parametrlərini yoxlayın:

```javascript
// Bildirişləri gətir
const notifications = await fetch('/api/v1/notifications');

// User parametrlərini gətir
const preferences = await fetch('/api/v1/notifications/preferences');

// Yalnız push aktivdirsə göstər
if (preferences.push_notifications_enabled) {
    // Bildirişləri göstər
} else {
    // Bildirişləri göstərmə
}
```

### Seçim 2: Backend-də Filter

Backend-də bildirişləri gətirərkən user parametrlərinə görə filter edin:

```php
// NotificationController-də
public function index(Request $request)
{
    $user = $request->user();
    
    $query = Notification::where('user_id', $user->id);
    
    // Push deaktivdirsə, yalnız database bildirişləri göstər
    if (!$user->wantsPushNotifications()) {
        // Yalnız database bildirişləri (push olmayan)
        $query->whereJsonDoesntContain('channels', 'push');
    }
    
    return $query->paginate(20);
}
```

---

## 🎯 Nəticə

**Backend düzgün işləyir:**
- ✅ Push bildirişləri skip edilir
- ✅ Real-time event göndərilmir
- ✅ Log-da görünür

**Problem:**
- ⚠️ Bildirişlər database-ə yazılır
- ⚠️ Frontend-də database-dən götürülür və görünə bilər

**Həll:**
- Frontend-də user parametrlərinə görə filter edin
- Və ya backend-də filter əlavə edin

