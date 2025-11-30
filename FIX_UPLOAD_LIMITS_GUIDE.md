# PHP Upload Limitləri Düzəltmək - Təlimat

## Problem
PHP upload limitləri çox kiçikdir:
- `upload_max_filesize`: 2M
- `post_max_size`: 8M

Bu limitlər 15MB video fayllarını yükləməyə imkan vermir.

## Həll Yolu

### Avtomatik Həll (Windows)

1. `fix_php_upload_limits.bat` faylını çalıştırın (Administrator kimi):
   ```bash
   fix_php_upload_limits.bat
   ```

2. Script avtomatik olaraq:
   - `php.ini` faylının backup-ını yaradacaq
   - Limitləri artıracaq:
     - `upload_max_filesize = 25M`
     - `post_max_size = 30M`

3. PHP server-i restart edin:
   ```bash
   # Ctrl+C ilə server-i dayandırın
   php artisan serve
   ```

### Manual Həll

1. **php.ini faylını tapın:**
   ```bash
   php --ini
   ```
   
   Nəticə: `C:\Program Files\php-8.3.26-nts-Win32-vs16-x64\php.ini`

2. **php.ini faylını açın** (Notepad++ və ya başqa text editor ilə, Administrator kimi)

3. **Aşağıdakı sətirləri tapın və dəyişdirin:**
   ```ini
   ; Əvvəlki:
   upload_max_filesize = 2M
   post_max_size = 8M
   
   ; Yeni:
   upload_max_filesize = 25M
   post_max_size = 30M
   ```

4. **Əgər sətirlər comment-dədirsə (; ilə başlayır), comment-i silin:**
   ```ini
   ; upload_max_filesize = 2M  ❌
   upload_max_filesize = 25M   ✅
   ```

5. **Faylı saxlayın**

6. **PHP server-i restart edin:**
   ```bash
   # Ctrl+C ilə server-i dayandırın
   php artisan serve
   ```

### Yoxlama

Test etmək üçün:
```bash
php -i | findstr /i "upload_max_filesize post_max_size"
```

Gözlənilən nəticə:
```
upload_max_filesize => 25M => 25M
post_max_size => 30M => 30M
```

Və ya backend-də upload etməyə çalışın - indi 15MB video faylları yüklənə bilməlidir.

## Qeydlər

- `post_max_size` həmişə `upload_max_filesize`-dən böyük olmalıdır
- Dəyişikliklərdən sonra PHP server-i mütləq restart edilməlidir
- Əgər hələ də problem varsa, `php.ini` faylının düzgün yerdə olduğunu yoxlayın



