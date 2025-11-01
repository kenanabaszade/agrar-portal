# PHP Upload Limitləri Düzəltmək

## Problem
PHP upload limitləri çox kiçikdir:
- `upload_max_filesize`: 2M
- `post_max_size`: 8M

Bu limitlər fayl upload-unu blokurl.

## Həll Yolları

### 1. php.ini Faylını Tapmaq

Windows-da:
```bash
php --ini
```

Bu komanda `php.ini` faylının yerini göstərəcək.

### 2. php.ini Faylında Dəyişikliklər

`php.ini` faylını açın və aşağıdakı dəyərləri tapın və dəyişdirin:

```ini
; Upload limitləri
upload_max_filesize = 25M
post_max_size = 30M
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
```

**Vacib:** `post_max_size` həmişə `upload_max_filesize`-dən böyük olmalıdır!

### 3. PHP Server-i Yenidən Başlatmaq

Dəyişikliklərdən sonra PHP server-i yenidən başlatmaq lazımdır:

```bash
# Windows (XAMPP/WAMP)
# Apache və PHP-ni restart edin

# Laravel built-in server
php artisan serve
```

### 4. Test Etmək

Test üçün:
```bash
php -i | findstr /i "upload_max_filesize post_max_size"
```

Və ya backend-də:
```php
echo ini_get('upload_max_filesize');
echo ini_get('post_max_size');
```

### 5. Əgər .htaccess İşləmirsə

Əgər `.htaccess` işləmirsə (CGI/FastCGI modunda), PHP konfiqurasiyasını birbaşa `php.ini`-də dəyişdirmək lazımdır.

### 6. Laravel Validation-da Yoxlamaq

Backend-də limitləri yoxlamaq üçün debug məlumatına baxın:
```json
{
  "debug": {
    "php_limits": {
      "upload_max_filesize": "25M",  // Bu 25M olmalıdır
      "post_max_size": "30M"         // Bu 30M olmalıdır
    }
  }
}
```

## Sürətli Həll (Development üçün)

XAMPP/WAMP istifadə edirsənizsə:
1. XAMPP Control Panel açın
2. Apache-yə klik edin → Config → PHP (php.ini)
3. `upload_max_filesize` və `post_max_size` dəyərlərini dəyişdirin
4. Apache-yi restart edin

## Production üçün

Production server-də PHP konfiqurasiyasını sistem administratoru ilə düzəltmək lazımdır.

