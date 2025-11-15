# Test Bildirişi - Bütün Userlərə Göndərmək

## ❌ Hazırkı Kod (Yalnız Birinci User-ə)

```php
$user = App\Models\User::first();
$notification = App\Models\Notification::create([
    'user_id' => $user->id,
    'type' => 'system',
    'title' => ['az' => 'Test Bildiriş'],
    'message' => ['az' => 'Bu test bildirişidir'],
    'is_read' => false,
    'sent_at' => now(),
]);
event(new App\Events\NotificationCreated($notification));
```

Bu kod **yalnız birinci user-ə** bildiriş göndərir.

---

## ✅ Bütün Userlərə Göndərmək

### Seçim 1: Loop ilə

```php
$users = App\Models\User::all();

foreach ($users as $user) {
    $notification = App\Models\Notification::create([
        'user_id' => $user->id,
        'type' => 'system',
        'title' => ['az' => 'Test Bildiriş'],
        'message' => ['az' => 'Bu test bildirişidir'],
        'is_read' => false,
        'sent_at' => now(),
    ]);
    event(new App\Events\NotificationCreated($notification));
}

echo "Bildiriş " . $users->count() . " user-ə göndərildi!";
```

### Seçim 2: NotificationService istifadə edərək

```php
$users = App\Models\User::all();
$notificationService = app(\App\Services\NotificationService::class);

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

echo "Bildiriş " . $users->count() . " user-ə göndərildi!";
```

### Seçim 3: Yalnız Aktiv Userlərə

```php
$users = App\Models\User::where('is_active', true)->get();
$notificationService = app(\App\Services\NotificationService::class);

foreach ($users as $user) {
    $notificationService->send(
        $user,
        'system',
        ['az' => 'Test Bildiriş'],
        ['az' => 'Bu test bildirişidir'],
        [
            'channels' => ['database', 'push'],
        ]
    );
}

echo "Bildiriş " . $users->count() . " aktiv user-ə göndərildi!";
```

### Seçim 4: Yalnız Push Bildirişləri Aktiv Olan Userlərə

```php
$users = App\Models\User::where('push_notifications_enabled', true)->get();
$notificationService = app(\App\Services\NotificationService::class);

foreach ($users as $user) {
    $notificationService->send(
        $user,
        'system',
        ['az' => 'Test Bildiriş'],
        ['az' => 'Bu test bildirişidir'],
        [
            'channels' => ['database', 'push'],
        ]
    );
}

echo "Bildiriş " . $users->count() . " user-ə göndərildi!";
```

---

## 🎯 Real Sistemdə Nə Baş Verir?

### Training yaradıldıqda:

`TrainingController::sendTrainingNotifications()` funksiyası:

```php
// Bütün userləri götürür
$users = User::where('email', '!=', null)
    ->where('email', '!=', '')
    ->where('email_verified', true)
    ->get();

// Hər user-ə bildiriş göndərir
foreach ($users as $user) {
    $notificationService->send(...);
}
```

**Nəticə:** Bütün email verified userlərə bildiriş gedir.

---

## 📊 Test Üçün

### Bir user-ə test:

```php
$user = App\Models\User::find(1); // ID ilə
// və ya
$user = App\Models\User::where('email', 'test@example.com')->first();

$notificationService = app(\App\Services\NotificationService::class);
$notificationService->send(
    $user,
    'system',
    ['az' => 'Test'],
    ['az' => 'Test mesajı'],
    ['channels' => ['database', 'push']]
);
```

### Bütün userlərə test:

```php
$users = App\Models\User::all();
$notificationService = app(\App\Services\NotificationService::class);

foreach ($users as $user) {
    $notificationService->send(
        $user,
        'system',
        ['az' => 'Test Bildiriş'],
        ['az' => 'Bu test bildirişidir'],
        ['channels' => ['database', 'push']]
    );
}

echo "✅ " . $users->count() . " user-ə bildiriş göndərildi!";
```

---

## ⚠️ Qeyd

**Çox user olduqda:**
- Loop çox vaxt ala bilər
- Queue job istifadə etmək daha yaxşıdır
- Real sistemdə `TrainingController` artıq queue istifadə edir

**Test üçün:**
- Az sayda user varsa, loop kifayətdir
- Çox user varsa, yalnız bir neçə user-ə test edin

