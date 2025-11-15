# Windows-da Redis Quraşdırma (Sürətli Təlimat)

## 🎯 Seçim 1: Memurai (Ən Asan - Tövsiyə Olunur)

### Addım 1: Memurai yükləyin

1. Bu linkə daxil olun: https://www.memurai.com/get-memurai
2. "Download Memurai Developer Edition" düyməsini klikləyin
3. Installer faylını yükləyin (`.msi` faylı)

### Addım 2: Quraşdırın

1. Yüklənmiş `.msi` faylını işə salın
2. "Next" düymələrini klikləyin
3. Quraşdırmanı tamamlayın
4. Sistem yenidən başladılması tələb oluna bilər

### Addım 3: Memurai-i işə salın

1. Start Menu-də "Memurai" axtarın
2. "Memurai" proqramını işə salın
3. System Tray-də (sağ alt küncdə) Memurai ikonu görünəcək

### Addım 4: Laravel konfiqurasiyası

`.env` faylında əlavə edin:

```env
# Broadcasting
BROADCAST_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CLIENT=phpredis
```

### Addım 5: Test edin

```powershell
php artisan tinker
```

Tinker-də:
```php
Redis::connection()->ping();
// Cavab: "PONG" olmalıdır
```

---

## 🐳 Seçim 2: Docker Desktop (Alternativ)

### Addım 1: Docker Desktop yükləyin

1. Bu linkə daxil olun: https://www.docker.com/products/docker-desktop
2. "Download for Windows" düyməsini klikləyin
3. Installer-ı quraşdırın
4. Sistem yenidən başladılması tələb oluna bilər

### Addım 2: Docker Desktop-ı işə salın

1. Start Menu-dən "Docker Desktop" işə salın
2. Docker-ın işləməsini gözləyin (sistem tray-də icon görünəcək)

### Addım 3: Redis container işə salın

PowerShell-də:

```powershell
docker run -d --name redis -p 6379:6379 redis:latest
```

### Addım 4: Laravel konfiqurasiyası

`.env` faylında əlavə edin:

```env
# Broadcasting
BROADCAST_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CLIENT=phpredis
```

### Addım 5: Test edin

```powershell
docker ps
# Redis container görünməlidir

php artisan tinker
```

Tinker-də:
```php
Redis::connection()->ping();
// Cavab: "PONG" olmalıdır
```

---

## 🐧 Seçim 3: WSL2 (Linux Mühiti)

### Addım 1: WSL2 quraşdırın

PowerShell-də (Administrator kimi):

```powershell
wsl --install
```

Sistem yenidən başladılması tələb olunacaq.

### Addım 2: WSL2-də Redis quraşdırın

WSL2 terminal açın və:

```bash
sudo apt update
sudo apt install redis-server -y
sudo service redis-server start
```

### Addım 3: Redis-i avtomatik başlatmaq

```bash
sudo systemctl enable redis-server
```

### Addım 4: Laravel konfiqurasiyası

`.env` faylında:

```env
# Broadcasting
BROADCAST_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CLIENT=phpredis
```

---

## ✅ Test və Yoxlama

### 1. Redis işləyirmi?

**Memurai üçün:**
- System Tray-də Memurai icon görünməlidir
- Right-click edib "Open Memurai" seçin

**Docker üçün:**
```powershell
docker ps
# redis container görünməlidir
```

**WSL2 üçün:**
```bash
redis-cli ping
# Cavab: PONG
```

### 2. Laravel-dən test

```powershell
php artisan tinker
```

```php
// Redis connection test
Redis::connection()->ping();
// Cavab: "PONG"

// Test bildiriş göndər
$user = App\Models\User::first();
$notification = App\Models\Notification::create([
    'user_id' => $user->id,
    'type' => 'system',
    'title' => ['az' => 'Test'],
    'message' => ['az' => 'Test mesajı'],
    'is_read' => false,
    'sent_at' => now(),
]);
event(new App\Events\NotificationCreated($notification));
```

### 3. Queue Worker işə salın

Yeni PowerShell terminal açın:

```powershell
php artisan queue:work
```

İndi bildirişlər real-time göndəriləcək!

---

## 🔧 Problem Həlləri

### Problem: "Connection refused"

**Həll:**
1. Redis server işləyirmi yoxlayın
2. Port 6379 açıqdırmı yoxlayın
3. `.env` faylında `REDIS_HOST=127.0.0.1` olduğundan əmin olun

### Problem: "Class 'Redis' not found"

**Həll:**
```powershell
composer require predis/predis
```

Və ya PHP Redis extension quraşdırın.

### Problem: Queue worker işləmir

**Həll:**
```powershell
# Queue worker-i işə salın
php artisan queue:work

# Və ya background-da
php artisan queue:work --daemon
```

---

## 📝 Qeydlər

- **Memurai**: Windows üçün ən asan, GUI ilə
- **Docker**: Container texnologiyası, gələcək üçün yaxşı
- **WSL2**: Tam Linux mühiti, production-a yaxın

Hansını seçmək istəyirsiniz?


