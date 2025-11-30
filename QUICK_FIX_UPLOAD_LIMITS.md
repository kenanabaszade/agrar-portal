# ⚡ TEZ HƏLL - PHP Upload Limitləri

## Problem
```
upload_max_filesize: 2M
post_max_size: 8M
```

## Həll (3 addım)

### Addım 1: php.ini faylını açın

**Notepad++ və ya başqa text editor ilə açın (sağ klik → "Run as Administrator"):**

```
C:\Program Files\php-8.3.26-nts-Win32-vs16-x64\php.ini
```

### Addım 2: Dəyişdirin

**Axtarın (Ctrl+F):**
```
upload_max_filesize
```

**Tapın və dəyişdirin:**
```ini
; ƏVVƏL
upload_max_filesize = 2M

; SONRA
upload_max_filesize = 25M
```

**Yenidən axtarın:**
```
post_max_size
```

**Tapın və dəyişdirin:**
```ini
; ƏVVƏL
post_max_size = 8M

; SONRA
post_max_size = 30M
```

**Faylı saxlayın (Ctrl+S)**

### Addım 3: Server-i restart edin

1. Terminal-də `Ctrl+C` basın (server-i dayandırın)
2. Yenidən başladın:
   ```bash
   php artisan serve
   ```

### Yoxlama

```bash
php -i | findstr /i "upload_max_filesize post_max_size"
```

**Gözlənilən nəticə:**
```
upload_max_filesize => 25M => 25M
post_max_size => 30M => 30M
```

✅ **Hazır! İndi 15MB video faylları yüklənə bilər.**



