# ⚠️ PROBLEM: CLI PHP və XAMPP Apache fərqli php.ini istifadə edir!

## Problem
- **XAMPP Apache**: XAMPP qovluğundakı `php.ini` (40M - düzəldilib ✅)
- **CLI PHP** (`php artisan serve`): `C:\Program Files\php-8.3.26-nts-Win32-vs16-x64\php.ini` (2M - köhnə ❌)

## Həll

### Seçim 1: CLI PHP-nin php.ini-ni düzəltmək (Tövsiyə olunur)

1. **Notepad++-ı Administrator kimi açın**
2. **Faylı açın:**
   ```
   C:\Program Files\php-8.3.26-nts-Win32-vs16-x64\php.ini
   ```
3. **Axtarın və dəyişdirin:**
   ```ini
   upload_max_filesize = 2M    ❌
   upload_max_filesize = 40M   ✅
   
   post_max_size = 8M    ❌
   post_max_size = 45M   ✅  (upload_max_filesize-dən böyük olmalıdır)
   ```
4. **Saxlayın (Ctrl+S)**
5. **Server-i restart edin:**
   ```bash
   # Ctrl+C
   php artisan serve
   ```

### Seçim 2: XAMPP Apache istifadə etmək

Əgər XAMPP Apache istifadə edirsinizsə, `php artisan serve` yerinə XAMPP Apache-ni istifadə edin:

1. XAMPP Control Panel-də Apache-ni start edin
2. Browser-də: `http://localhost/api/v1/...`

## Yoxlama

```bash
php -i | findstr /i "upload_max_filesize post_max_size"
```

**Gözlənilən nəticə:**
```
upload_max_filesize => 40M => 40M
post_max_size => 45M => 45M
```

✅ **Hazır! İndi 15MB video faylları yüklənə bilər.**



