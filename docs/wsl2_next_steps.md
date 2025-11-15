# WSL2 Quraşdırıldı - Növbəti Addımlar

## ✅ Addım 1: Sistem Yenidən Başladın

WSL2 quraşdırıldı, amma aktiv olması üçün sistem yenidən başladılmalıdır.

**Nə etməli:**
1. Bütün açıq faylları saxlayın
2. Sistem yenidən başladın
3. Windows yenidən açıldıqdan sonra WSL2 avtomatik işə düşəcək

---

## 🐧 Addım 2: WSL2 Terminal Açın

Sistem yenidən başladıldıqdan sonra:

**Seçim 1:** WSL2 terminal avtomatik açılacaq (bəzən)

**Seçim 2:** Manual açın:
1. Start Menu-də "Ubuntu" axtarın
2. "Ubuntu" proqramını işə salın
3. İlk dəfə açılanda username və password tələb olunacaq

**Qeyd:** İlk dəfə açılanda:
- Username yazın (məsələn: `umida`)
- Password yazın (göstərilməyəcək, normaldır)
- Password təsdiqləyin

---

## 📦 Addım 3: WSL2-də Redis Quraşdırın

WSL2 terminal açıldıqdan sonra:

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

---

## ⚙️ Addım 4: Redis-i Avtomatik Başlatmaq

Redis-i hər dəfə manual başlatmaq istəmirsinizsə:

```bash
# Avtomatik başlatmaq
sudo systemctl enable redis-server
```

İndi hər dəfə WSL2 açılanda Redis avtomatik işə düşəcək.

---

## 🔧 Addım 5: Laravel Konfiqurasiyası

Windows PowerShell-də (Laravel proyektinizdə):

`.env` faylına əlavə edin:

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

## 🧪 Addım 6: Test Edin

Windows PowerShell-də:

```powershell
php artisan tinker
```

Tinker-də:

```php
// Redis connection test
Redis::connection()->ping();
// Cavab: "PONG" olmalıdır

// Əgər "PONG" gəlmirsə, WSL2-də Redis işləyirmi yoxlayın
```

---

## 🔄 Addım 7: Queue Worker İşə Salın

Yeni PowerShell terminal açın:

```powershell
php artisan queue:work
```

İndi bildirişlər real-time göndəriləcək!

---

## ❓ Problem Həlləri

### Problem: "Connection refused"

**Həll:**
1. WSL2 terminal açın
2. Redis işləyirmi yoxlayın:
   ```bash
   redis-cli ping
   ```
3. Əgər işləmirsə:
   ```bash
   sudo service redis-server start
   ```

### Problem: "Class 'Redis' not found"

**Həll:**
```powershell
composer require predis/predis
```

### Problem: WSL2 terminal açılmır

**Həll:**
1. Start Menu-də "Ubuntu" axtarın
2. Əgər yoxdursa, Microsoft Store-dan "Ubuntu" yükləyin
3. Və ya PowerShell-də:
   ```powershell
   wsl --install -d Ubuntu
   ```

---

## ✅ Hazırsınız!

Sistem yenidən başladıldıqdan sonra:
1. WSL2 terminal açın
2. Redis quraşdırın
3. Laravel konfiqurasiyası edin
4. Test edin

**Problem olarsa, mənə yazın!**


