# 🔧 Redirect URI Mismatch Xətasının Həlli

## ❌ Problem

Google OAuth2 authorization zamanı bu xəta görünür:
```
Error 400: redirect_uri_mismatch
```

**Səbəb:** Google Cloud Console-da redirect URI düzgün qeyd edilməyib.

---

## ✅ Həll Addımları

### Addım 1: Google Cloud Console-a Daxil Olun

1. [Google Cloud Console](https://console.cloud.google.com/) saytına daxil olun
2. Layihənizi seçin
3. **"APIs & Services"** → **"Credentials"** seçin

### Addım 2: OAuth 2.0 Client ID-ni Açın

1. **"OAuth 2.0 Client IDs"** bölməsində Client ID-nizi tapın
2. Client ID-nin yanındakı **pencil icon** (✏️) klik edin (Edit)

### Addım 3: Redirect URI Əlavə Edin

1. **"Authorized redirect URIs"** bölməsinə scroll edin
2. **"+ ADD URI"** klik edin
3. Bu URL-i əlavə edin:
   ```
   http://localhost:8000/api/v1/google/callback
   ```
4. **"SAVE"** klik edin

### Addım 4: .env Faylını Yoxlayın

Backend-də `.env` faylında bu dəyərin olduğunu yoxlayın:

```env
GOOGLE_REDIRECT_URI=http://localhost:8000/api/v1/google/callback
```

**Qeyd:** 
- URL tam eyni olmalıdır (trailing slash olmadan)
- `http` və ya `https` düzgün olmalıdır
- Port nömrəsi (8000) düzgün olmalıdır

### Addım 5: Config Cache Təmizləyin

```bash
php artisan config:clear
php artisan cache:clear
```

---

## 🧪 Test Edin

1. Yenidən authorization URL-i alın:
   ```http
   GET {{base_url}}/api/v1/google/auth-url
   Authorization: Bearer YOUR_TOKEN
   ```

2. `auth_url` dəyərini browser-də açın

3. Artıq xəta görünməməlidir ✅

---

## 📋 Yoxlama Siyahısı

- [ ] Google Cloud Console-da redirect URI əlavə edildi
- [ ] `.env` faylında `GOOGLE_REDIRECT_URI` düzgün dəyərə malikdir
- [ ] Config cache təmizləndi
- [ ] URL tam eynidir (trailing slash yoxdur)

---

## 🎯 Production Üçün

Production mühitində:

1. Google Cloud Console-da əlavə edin:
   ```
   https://yourdomain.com/api/v1/google/callback
   ```

2. `.env` faylında:
   ```env
   GOOGLE_REDIRECT_URI=https://yourdomain.com/api/v1/google/callback
   ```

---

## ⚠️ Vacib Qeydlər

1. **URL tam eyni olmalıdır:**
   - ✅ `http://localhost:8000/api/v1/google/callback`
   - ❌ `http://localhost:8000/api/v1/google/callback/` (trailing slash)
   - ❌ `https://localhost:8000/api/v1/google/callback` (https)

2. **Development və Production üçün ayrı-ayrı URI-lər əlavə edin**

3. **Dəyişikliklərdən sonra bir neçə dəqiqə gözləyin** (Google cache təmizlənməsi üçün)

---

## 🚨 Hələ də işləmirsə?

1. **Browser cache-i təmizləyin** (Ctrl+Shift+Delete)
2. **Incognito/Private mode-da test edin**
3. **Google Cloud Console-da dəyişikliklərin save olunduğunu yoxlayın**
4. **Config cache-i yenidən təmizləyin**

---

**Hazırdır! İndi authorization işləməlidir.** ✅

