# Tinker-də Bildiriş Testi (Düzgün Kod)

## ✅ Düzgün Kod

Tinker-də bu kodu yazın:

```php
// NotificationService-i götür
$notificationService = app(\App\Services\NotificationService::class);

// Bütün email verified userləri götür
$users = App\Models\User::where('email', '!=', null)
    ->where('email', '!=', '')
    ->where('email_verified', true)
    ->get();

// Hər user-ə bildiriş göndər
foreach ($users as $user) {
    $notificationService->send(
        $user,
        'system',
        ['az' => 'Test Bildiriş'],
        ['az' => 'Bu test bildirişidir'],
        [
            'channels' => ['database', 'push'],
            'data' => ['test' => true],
        ]
    );
}

echo "✅ " . $users->count() . " user-ə bildiriş göndərildi!";
```

---

## 🎯 Yalnız Bir User-ə Test

```php
$user = App\Models\User::first();
$notificationService = app(\App\Services\NotificationService::class);

$notificationService->send(
    $user,
    'system',
    ['az' => 'Test Bildiriş'],
    ['az' => 'Bu test bildirişidir'],
    [
        'channels' => ['database', 'push'],
    ]
);

echo "✅ Bildiriş göndərildi!";
```

---

## 📝 Qeydlər

1. **Namespace:** Həmişə `App\Models\User` yazın, `User` yox
2. **NotificationService:** `app(\App\Services\NotificationService::class)` ilə götürün
3. **Channels:** `['database', 'push']` - database-ə yazır və real-time push göndərir

---

## 🔍 Bildirişləri Yoxlamaq

```php
// Son bildirişləri görün
App\Models\Notification::latest()->take(10)->get();

// Bir user-ın bildirişləri
$user = App\Models\User::first();
$user->notifications()->latest()->get();
```

---

## ⚠️ Xətalar

### "Class 'User' not found"
**Həll:** `App\Models\User` yazın

### "Class 'NotificationService' not found"
**Həll:** `app(\App\Services\NotificationService::class)` istifadə edin

### "Method 'send' does not exist"
**Həll:** NotificationService düzgün götürüldüyündən əmin olun

