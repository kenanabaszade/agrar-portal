# WSL2-də Redis Quraşdırma (Addım-Addım)

## ✅ Addım 1: Ubuntu Terminal Açın

1. Start Menu-də "Ubuntu" axtarın
2. "Ubuntu" proqramını işə salın
3. Terminal açılacaq

---

## 📦 Addım 2: Sistem Yeniləyin

Ubuntu terminal-də:

```bash
sudo apt update
```

Bu əmər sistem paketlərini yeniləyir. Bir neçə dəqiqə çəkə bilər.

---

## 🔴 Addım 3: Redis Quraşdırın

Sistem yeniləndikdən sonra:

```bash
sudo apt install redis-server -y
```

Bu əmər Redis-i quraşdıracaq. `-y` flag-i bütün suallara "yes" cavabı verir.

---

## ▶️ Addım 4: Redis-i İşə Salın

Quraşdırıldıqdan sonra:

```bash
sudo service redis-server start
```

---

## 🧪 Addım 5: Test Edin

Redis işləyirmi yoxlayın:

```bash
redis-cli ping
```

**Cavab:** `PONG` olmalıdır.

Əgər `PONG` gəlmirsə, Redis işləmir. Yenidən başladın:

```bash
sudo service redis-server restart
redis-cli ping
```

---

## ⚙️ Addım 6: Redis-i Avtomatik Başlatmaq

Hər dəfə WSL2 açılanda Redis avtomatik işə düşməsi üçün:

```bash
sudo systemctl enable redis-server
```

**Qeyd:** Bəzən `systemctl` WSL2-də işləməyə bilər. Əgər xəta gəlsə, problem deyil, hər dəfə manual başlatmaq lazım olacaq.

---

## 🔧 Addım 7: Laravel Konfiqurasiyası

İndi Windows PowerShell-də (Laravel proyektinizdə):

`.env` faylına bu sətirləri əlavə edin:

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

## 🧪 Addım 8: Laravel-dən Test Edin

Windows PowerShell-də (Laravel proyektinizdə):

```powershell
php artisan tinker
```

Tinker-də:

```php
Redis::connection()->ping();
```

**Cavab:** `"PONG"` olmalıdır.

Əgər xəta gəlsə:
1. WSL2-də Redis işləyirmi yoxlayın: `redis-cli ping`
2. `.env` faylında `REDIS_HOST=127.0.0.1` olduğundan əmin olun

---

## 🔄 Addım 9: Queue Worker İşə Salın

Yeni PowerShell terminal açın:

```powershell
php artisan queue:work
```

İndi bildirişlər real-time göndəriləcək!

---

## ❓ Problem Həlləri

### Problem: "sudo: command not found"

**Həll:** Bu normal deyil, amma əgər gəlsə:
```bash
# Username və password tələb olunacaq
su -
```

### Problem: "E: Unable to locate package redis-server"

**Həll:**
```bash
# Sistem yeniləyin
sudo apt update
# Yenidən cəhd edin
sudo apt install redis-server -y
```

### Problem: "Connection refused" (Laravel-dən)

**Həll:**
1. WSL2-də Redis işləyirmi yoxlayın:
   ```bash
   redis-cli ping
   ```
2. Əgər işləmirsə:
   ```bash
   sudo service redis-server start
   ```

### Problem: "Class 'Redis' not found" (Laravel-dən)

**Həll:**
```powershell
composer require predis/predis
```

---

## ✅ Hazırsınız!

İndi:
1. ✅ WSL2 quraşdırıldı
2. ✅ Ubuntu işləyir
3. ⏳ Redis quraşdırılmalıdır (yuxarıdakı addımlar)
4. ⏳ Laravel konfiqurasiyası edilməlidir
5. ⏳ Test edilməlidir

**Ubuntu terminal-də yuxarıdakı əmrləri yerinə yetirin və mənə bildirin!**

