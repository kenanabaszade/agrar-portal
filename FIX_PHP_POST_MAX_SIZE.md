# PHP post_max_size Limitini Artırmaq

## Problem
PHP `post_max_size` limiti 8M-dir və bu 16MB+ video yükləməyə imkan vermir.

## Həll

### 1. php.ini Faylını Tapmaq
Fayl yeri: `C:\Program Files\php-8.3.26-nts-Win32-vs16-x64\php.ini`

### 2. Admin Hüququ ilə Açmaq
1. Notepad-i **Administrator kimi işə salın** (sağ klik → "Run as administrator")
2. `php.ini` faylını açın

### 3. Dəyərləri Dəyişdirmək
Faylda aşağıdakı sətirləri tapın və dəyişdirin:

**Tapın:**
```ini
post_max_size = 8M
```

**Dəyişdirin:**
```ini
post_max_size = 110M
```

**Tapın:**
```ini
upload_max_filesize = 45M
```

**Dəyişdirin (əgər lazımsa):**
```ini
upload_max_filesize = 105M
```

**Tapın və dəyişdirin:**
```ini
memory_limit = 512M
max_execution_time = 600
max_input_time = 600
```

### 4. Faylı Saxlamaq
Faylı saxlayın və PHP server-i restart edin.

### 5. Test Etmək
```bash
php -i | findstr /i "post_max_size upload_max_filesize"
```

Gözlənilən nəticə:
```
post_max_size => 110M => 110M
upload_max_filesize => 105M => 105M
```

## Qeyd
- `post_max_size` həmişə `upload_max_filesize`-dən böyük olmalıdır
- Dəyişikliklərdən sonra PHP server-i (Apache/Laravel server) restart edilməlidir
- Əgər Laravel built-in server istifadə edirsənizsə, `php artisan serve`-i dayandırıb yenidən başladın

