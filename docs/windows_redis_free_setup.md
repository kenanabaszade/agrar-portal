# Windows-da Pulsuz Redis Quraşdırma

## 🐧 Seçim 1: WSL2 + Redis (Tövsiyə Olunur - Tam Pulsuz)

### Addım 1: WSL2 quraşdırın

PowerShell-də **Administrator kimi** işə salın:

```powershell
wsl --install
```

**Qeyd:** Sistem yenidən başladılması tələb olunacaq.

### Addım 2: Sistem yenidən başladın

WSL2 quraşdırıldıqdan sonra sistem yenidən başladılacaq.

### Addım 3: WSL2-də Redis quraşdırın

Yenidən başladıldıqdan sonra, WSL2 terminal açılacaq. Əgər açılmazsa, Start Menu-dən "Ubuntu" axtarın.

WSL2 terminal-də:

```bash
# Sistem yeniləyin
sudo apt update

# Redis quraşdırın
sudo apt install redis-server -y

# Redis-i işə salın
sudo service redis-server start

# Test edin
redis-cli ping
# Cavab: PONG olmalıdır
```

### Addım 4: Redis-i avtomatik başlatmaq

```bash
sudo systemctl enable redis-server
```

### Addım 5: Laravel konfiqurasiyası

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

### Addım 6: Test edin

PowerShell-də (Windows terminal):

```powershell
php artisan tinker
```

Tinker-də:
```php
Redis::connection()->ping();
// Cavab: "PONG" olmalıdır
```

---

## 🐳 Seçim 2: Docker Desktop (Pulsuz)

### Addım 1: Docker Desktop yükləyin

1. Bu linkə daxil olun: https://www.docker.com/products/docker-desktop
2. "Download for Windows" düyməsini klikləyin
3. **Docker Desktop Community Edition** pulsuzdur
4. Installer-ı quraşdırın

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
# Container işləyirmi yoxlayın
docker ps

# Laravel test
php artisan tinker
```

Tinker-də:
```php
Redis::connection()->ping();
// Cavab: "PONG" olmalıdır
```

---

## ☁️ Seçim 3: Upstash (Cloud - Pulsuz Plan)

### Addım 1: Upstash hesabı yaradın

1. Bu linkə daxil olun: https://upstash.com/
2. "Sign Up" düyməsini klikləyin
3. Email ilə qeydiyyatdan keçin
4. Email təsdiqləyin

### Addım 2: Redis database yaradın

1. Dashboard-da "Create Database" düyməsini klikləyin
2. Database adı verin (məsələn: "aqrar-backend")
3. Region seçin (Avropa yaxın olsun - məsələn: "eu-west-1")
4. "Create" düyməsini klikləyin

### Addım 3: Connection string-i kopyalayın

1. Database-ə daxil olun
2. "Details" bölməsində connection məlumatlarını görəcəksiniz:
   - `REDIS_HOST` (məsələn: `eu-west-1-redis.upstash.io`)
   - `REDIS_PASSWORD` (uzun string)
   - `REDIS_PORT` (adətən: `6379`)

### Addım 4: Laravel konfiqurasiyası

`.env` faylında əlavə edin:

```env
# Broadcasting
BROADCAST_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis (Upstash)
REDIS_HOST=your-redis-host.upstash.io
REDIS_PASSWORD=your-redis-password
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

**Qeyd:** Upstash pulsuz planında:
- 10,000 günlük request
- 256 MB storage
- Development üçün kifayətdir

---

## 🧪 Test və Yoxlama

### 1. Redis işləyirmi?

**WSL2 üçün:**
```bash
# WSL2 terminal-də
redis-cli ping
# Cavab: PONG
```

**Docker üçün:**
```powershell
docker ps
# redis container görünməlidir

docker exec -it redis redis-cli ping
# Cavab: PONG
```

**Upstash üçün:**
- Dashboard-da "Metrics" bölməsində aktivliyi görə bilərsiniz

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

### Problem: WSL2 quraşdırılmır

**Həll:**
1. Windows Update etdiyinizdən əmin olun
2. BIOS-da Virtualization aktiv olmalıdır
3. PowerShell-də Administrator kimi işə salın

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

## 📝 Tövsiyə

**Development üçün:**
- **WSL2**: Ən yaxşı seçim, Linux mühiti, production-a yaxın
- **Docker**: Container texnologiyası, asan idarəetmə
- **Upstash**: Cloud, heç bir quraşdırma lazım deyil

**Production (Linux) üçün:**
- Native Redis server quraşdırın
- Supervisor/Systemd ilə queue worker

---

## ✅ Nəticə

Bütün seçimlər **tam pulsuzdur**:
- ✅ WSL2 + Redis - Tam pulsuz
- ✅ Docker Desktop - Community Edition pulsuzdur
- ✅ Upstash - Pulsuz plan var (10K request/gün)

Hansını seçmək istəyirsiniz?


