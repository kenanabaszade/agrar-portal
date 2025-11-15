# Redis Quraşdırma Təlimatı

## 🪟 Windows üçün Redis

### Seçim 1: WSL2 (Tövsiyə olunur - Linux mühiti)

Windows-da ən yaxşı yol WSL2 (Windows Subsystem for Linux) istifadə etməkdir:

1. **WSL2 quraşdırın:**
   ```powershell
   wsl --install
   ```
   Sistem yenidən başladıldıqdan sonra WSL2 aktiv olacaq.

2. **WSL2-də Redis quraşdırın:**
   ```bash
   # Ubuntu/Debian üçün
   sudo apt update
   sudo apt install redis-server -y
   
   # Redis-i işə salın
   sudo service redis-server start
   
   # Test edin
   redis-cli ping
   # Cavab: PONG
   ```

3. **Redis-i avtomatik başlatmaq:**
   ```bash
   sudo systemctl enable redis-server
   ```

4. **Laravel-dən istifadə:**
   - `.env` faylında:
   ```env
   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   ```

### Seçim 2: Memurai (Windows üçün Redis GUI)

1. **Memurai yükləyin:**
   - https://www.memurai.com/get-memurai
   - Windows installer-ı yükləyin və quraşdırın

2. **Memurai işə salın:**
   - Start Menu-dən "Memurai" axtarın və işə salın
   - Default port: `6379`

3. **Laravel konfiqurasiyası:**
   ```env
   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   ```

### Seçim 3: Docker (Ən asan)

1. **Docker Desktop quraşdırın:**
   - https://www.docker.com/products/docker-desktop

2. **Redis container işə salın:**
   ```powershell
   docker run -d --name redis -p 6379:6379 redis:latest
   ```

3. **Laravel konfiqurasiyası:**
   ```env
   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   ```

### Seçim 4: Upstash (Cloud Redis - Pulsuz)

1. **Upstash hesabı yaradın:**
   - https://upstash.com/
   - Pulsuz plan: 10,000 günlük request

2. **Redis database yaradın:**
   - Dashboard-da "Create Database" klikləyin
   - Region seçin (Avropa yaxın olsun)
   - Database adı verin

3. **Connection string-i kopyalayın:**
   - `.env` faylında:
   ```env
   REDIS_HOST=your-redis-host.upstash.io
   REDIS_PASSWORD=your-redis-password
   REDIS_PORT=6379
   REDIS_CLIENT=phpredis
   ```

---

## 🐧 Linux üçün Redis

### Ubuntu/Debian

```bash
# Redis quraşdırın
sudo apt update
sudo apt install redis-server -y

# Redis konfiqurasiyası
sudo nano /etc/redis/redis.conf

# Aşağıdakı sətirləri tapın və dəyişdirin:
# bind 127.0.0.1 ::1  ->  bind 0.0.0.0 (əgər remote access lazımdırsa)
# protected-mode yes  ->  protected-mode no (əgər şifrə yoxdursa)

# Redis-i işə salın
sudo systemctl start redis-server
sudo systemctl enable redis-server

# Test edin
redis-cli ping
# Cavab: PONG

# Status yoxlayın
sudo systemctl status redis-server
```

### CentOS/RHEL

```bash
# EPEL repository əlavə edin
sudo yum install epel-release -y

# Redis quraşdırın
sudo yum install redis -y

# Redis-i işə salın
sudo systemctl start redis
sudo systemctl enable redis

# Test edin
redis-cli ping
```

### Docker (Linux)

```bash
# Redis container işə salın
docker run -d \
  --name redis \
  --restart unless-stopped \
  -p 6379:6379 \
  redis:latest

# Test edin
docker exec -it redis redis-cli ping
```

---

## ⚙️ Laravel Konfiqurasiyası

### `.env` faylı

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

### `config/database.php` yoxlayın

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
    ],
    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
    ],
],
```

---

## 🧪 Test

### 1. Redis Connection Test

```bash
php artisan tinker
```

```php
Redis::connection()->ping();
// Cavab: "PONG"
```

### 2. Broadcasting Test

```php
// Tinker-də
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

### 3. Queue Test

```bash
# Queue worker işə salın
php artisan queue:work

# Başqa terminal-da job göndərin
php artisan tinker
```

```php
App\Jobs\SendTrainingNotification::dispatch(1, ['test' => 'data']);
```

---

## 🚀 Production Deployment

### Supervisor Konfiqurasiyası (Linux)

`/etc/supervisor/conf.d/laravel-worker.conf`:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/worker.log
stopwaitsecs=3600
```

Supervisor-u yeniləyin:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

### Systemd Service (Linux)

`/etc/systemd/system/laravel-queue.service`:

```ini
[Unit]
Description=Laravel Queue Worker
After=network.target redis.service

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /path/to/your/project/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```

Service-i aktivləşdirin:
```bash
sudo systemctl daemon-reload
sudo systemctl enable laravel-queue
sudo systemctl start laravel-queue
```

---

## 🔒 Təhlükəsizlik

### Production üçün Redis Şifrəsi

1. **Redis konfiqurasiyası:**
   ```bash
   sudo nano /etc/redis/redis.conf
   ```
   
   Tapın və dəyişdirin:
   ```
   # requirepass foobared
   requirepass your-strong-password-here
   ```

2. **Redis-i yenidən başladın:**
   ```bash
   sudo systemctl restart redis-server
   ```

3. **Laravel `.env`:**
   ```env
   REDIS_PASSWORD=your-strong-password-here
   ```

---

## 📊 Monitoring

### Redis Stats

```bash
redis-cli INFO stats
```

### Memory Usage

```bash
redis-cli INFO memory
```

### Connected Clients

```bash
redis-cli CLIENT LIST
```

---

## ❓ FAQ

**S: Windows-da Redis quraşdırmadan real-time işləyə bilərmi?**
C: Xeyr, amma alternativlər var:
- **Log driver**: Yalnız test üçün (real-time deyil)
- **Database driver**: Sync, amma real-time deyil
- **Pusher**: Managed service (pulsuz plan var)

**S: Linux-a köçürəndə nə etməli?**
C: Sadəcə `.env` faylında `REDIS_HOST`-u dəyişdirin. Kod eyni qalır.

**S: Redis olmadan test edə bilərəmmi?**
C: Bəli, `BROADCAST_DRIVER=log` istifadə edin. Bildirişlər log faylına yazılacaq, amma real-time olmayacaq.

---

## 🎯 Tövsiyə

**Development (Windows):**
- WSL2 + Redis (ən yaxşı)
- Və ya Docker + Redis
- Və ya Upstash (cloud)

**Production (Linux):**
- Native Redis server
- Supervisor/Systemd ilə queue worker
- Redis şifrəsi aktiv


