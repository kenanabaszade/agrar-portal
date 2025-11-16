# 🎥 Google Meet İnteqrasiyası - Addım-Addım Təlimat

## 📋 Ümumi Məlumat

Bu təlimat sizə Google Meet inteqrasiyasını quraşdırmaq üçün lazım olan bütün addımları izah edir. Sistemdə artıq Google Meet API-ləri hazırdır, sadəcə konfiqurasiya etmək lazımdır.

---

## 🔧 Addım 1: Google Cloud Console-da Layihə Yaratmaq

### 1.1. Google Cloud Console-a Daxil Olun

1. [Google Cloud Console](https://console.cloud.google.com/) saytına daxil olun
2. Google hesabınızla giriş edin

### 1.2. Yeni Layihə Yaratmaq

1. Yuxarıda "Project" seçin
2. "New Project" klik edin
3. Layihə adı: `Aqrar Portal` (və ya istədiyiniz ad)
4. "Create" klik edin
5. Yeni layihəni seçin

---

## 🔑 Addım 2: Google Calendar API-ni Aktivləşdirmək

### 2.1. API Library-yə Getmək

1. Sol menyudan **"APIs & Services"** → **"Library"** seçin
2. Axtarış sahəsinə `Google Calendar API` yazın
3. **"Google Calendar API"** seçin
4. **"Enable"** klik edin

### 2.2. Google Meet API-ni Aktivləşdirmək (Əlavə)

1. Eyni şəkildə **"Google Meet API"** axtarın
2. **"Enable"** klik edin

---

## 🔐 Addım 3: OAuth 2.0 Credentials Yaratmaq

### 3.1. OAuth 2.0 Client ID Yaratmaq

1. **"APIs & Services"** → **"Credentials"** seçin
2. Yuxarıda **"+ CREATE CREDENTIALS"** klik edin
3. **"OAuth client ID"** seçin

### 3.2. OAuth Consent Screen Konfiqurasiyası

**İlk dəfədirsə, əvvəlcə OAuth Consent Screen-i konfiqurasiya etməlisiniz:**

1. **"OAuth consent screen"** tab-ına keçin
2. **"External"** seçin və **"CREATE"** klik edin
3. Doldurun:
   - **App name**: `Aqrar Portal`
   - **User support email**: Öz email-inizi
   - **Developer contact information**: Öz email-inizi
4. **"SAVE AND CONTINUE"** klik edin
5. **Scopes** addımında **"SAVE AND CONTINUE"** klik edin (default scopes kifayətdir)
6. **Test users** addımında öz email-inizi əlavə edin (development üçün)
7. **"SAVE AND CONTINUE"** və **"BACK TO DASHBOARD"** klik edin

### 3.3. OAuth 2.0 Client ID Yaratmaq

1. Yenidən **"Credentials"** → **"+ CREATE CREDENTIALS"** → **"OAuth client ID"**
2. **Application type**: **"Web application"** seçin
3. **Name**: `Aqrar Portal Web Client`
4. **Authorized redirect URIs** bölməsinə əlavə edin:
   ```
   http://localhost:8000/api/v1/google/callback
   ```
   
   **Production üçün:**
   ```
   https://yourdomain.com/api/v1/google/callback
   ```
5. **"CREATE"** klik edin

### 3.4. Credentials-ləri Kopyalamaq

1. Açılan pəncərədə **Client ID** və **Client Secret** görünəcək
2. Bu dəyərləri kopyalayın və saxlayın (sonra `.env` faylına əlavə edəcəksiniz)

---

## ⚙️ Addım 4: Backend `.env` Faylını Konfiqurasiya Etmək

### 4.1. `.env` Faylına Dəyərləri Əlavə Etmək

Backend-də `.env` faylını açın və aşağıdakı dəyərləri əlavə edin:

```env
# Google Calendar API Configuration
GOOGLE_CLIENT_ID=your_client_id_here
GOOGLE_CLIENT_SECRET=your_client_secret_here
GOOGLE_REDIRECT_URI=http://localhost:8000/api/v1/google/callback
GOOGLE_CALENDAR_ID=primary
GOOGLE_CREDENTIALS_PATH=storage/app/google-credentials.json
```

**Qeyd:**
- `your_client_id_here` yerinə Google Cloud Console-dan kopyaladığınız **Client ID** yazın
- `your_client_secret_here` yerinə **Client Secret** yazın
- Production üçün `GOOGLE_REDIRECT_URI`-ni dəyişdirin

### 4.2. Config Cache-i Təmizləmək

```bash
php artisan config:clear
php artisan cache:clear
```

---

## 🧪 Addım 5: OAuth2 Authorization Tamamlamaq

### 5.1. Authorization URL-i Almaq

Postman və ya browser-də aşağıdakı request göndərin:

```http
GET http://localhost:8000/api/v1/google/auth-url
Authorization: Bearer {your_token}
```

**Response:**
```json
{
  "success": true,
  "auth_url": "https://accounts.google.com/o/oauth2/auth?..."
}
```

### 5.2. Google Hesabı ilə Giriş Etmək

1. `auth_url` dəyərini kopyalayın
2. Browser-də açın
3. Google hesabınızla giriş edin
4. "Agrar Portal" tətbiqinə icazə verin
5. Siz redirect olunacaqsınız: `http://localhost:8000/api/v1/google/callback?code=...`

### 5.3. Authorization Code-u İstifadə Etmək

1. URL-dən `code=` parametrindən sonrakı dəyəri kopyalayın
2. Aşağıdakı request göndərin:

```http
POST http://localhost:8000/api/v1/google/callback
Authorization: Bearer {your_token}
Content-Type: application/json

{
  "code": "your_authorization_code_here"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Authorization successful",
  "user": {
    "google_access_token": "ya29.a0...",
    "google_refresh_token": "1//...",
    "google_token_expires_at": "2025-01-15T..."
  }
}
```

### 5.4. Access Yoxlamaq

```http
GET http://localhost:8000/api/v1/google/check-access
Authorization: Bearer {your_token}
```

**Response:**
```json
{
  "success": true,
  "has_access": true
}
```

---

## 🎬 Addım 6: Google Meet Meeting Yaratmaq

### 6.1. Meeting Yaratmaq

```http
POST http://localhost:8000/api/v1/meetings
Authorization: Bearer {your_token}
Content-Type: application/json

{
  "title": "Kənd Təsərrüfatı Təlimi",
  "description": "Müasir əkinçilik texnikaları haqqında təlim",
  "start_time": "2025-01-20 10:00:00",
  "end_time": "2025-01-20 12:00:00",
  "timezone": "Asia/Baku",
  "max_attendees": 50,
  "training_id": 1,
  "is_recurring": false,
  "attendees": [
    {
      "email": "user@example.com",
      "name": "İstifadəçi Adı"
    }
  ]
}
```

**Response:**
```json
{
  "message": "Meeting created successfully",
  "meeting": {
    "id": 1,
    "title": "Kənd Təsərrüfatı Təlimi",
    "google_meet_link": "https://meet.google.com/abc-defg-hij",
    "meeting_id": "abc-defg-hij",
    "start_time": "2025-01-20T10:00:00.000000Z",
    "end_time": "2025-01-20T12:00:00.000000Z",
    "status": "scheduled"
  }
}
```

### 6.2. Meeting Link-i Yoxlamaq

1. `google_meet_link` dəyərini kopyalayın
2. Browser-də açın
3. Google Meet açılmalıdır və meeting hazır olmalıdır

---

## 📚 Addım 7: Training-lərdə Google Meet İstifadə Etmək

### 7.1. Online Training Yaratmaq (Google Meet ilə)

```http
POST http://localhost:8000/api/v1/trainings
Authorization: Bearer {your_token}
Content-Type: application/json

{
  "title": {
    "az": "Online Təlim",
    "en": "Online Training",
    "ru": "Онлайн Обучение"
  },
  "type": "online",
  "google_meet_enabled": true,
  "start_date": "2025-01-20",
  "end_date": "2025-01-20",
  "start_time": "10:00:00",
  "end_time": "12:00:00",
  "timezone": "Asia/Baku",
  "description": {
    "az": "Təlim təsviri"
  }
}
```

**Response:**
```json
{
  "message": "Training created successfully",
  "training": {
    "id": 1,
    "title": "Online Təlim",
    "google_meet_link": "https://meet.google.com/abc-defg-hij",
    "google_meet_enabled": true
  }
}
```

---

## 🔍 Addım 8: API Endpoint-ləri

### 8.1. Meeting Management

| Method | Endpoint | Açıqlama |
|--------|----------|----------|
| GET | `/api/v1/google/auth-url` | OAuth2 authorization URL al |
| POST | `/api/v1/google/callback` | Authorization code-u exchange et |
| GET | `/api/v1/google/check-access` | Access yoxla |
| POST | `/api/v1/meetings` | Meeting yarat |
| GET | `/api/v1/meetings` | Meeting-ləri listlə |
| GET | `/api/v1/meetings/{id}` | Meeting detalları |
| PATCH | `/api/v1/meetings/{id}` | Meeting yenilə |
| DELETE | `/api/v1/meetings/{id}` | Meeting sil |

### 8.2. Meeting Registration

| Method | Endpoint | Açıqlama |
|--------|----------|----------|
| POST | `/api/v1/meetings/{id}/register` | Meeting-ə qeydiyyat |
| DELETE | `/api/v1/meetings/{id}/cancel-registration` | Qeydiyyatı ləğv et |
| GET | `/api/v1/my-meetings` | Mənim meeting-lərim |

---

## 🚨 Problem Həlləri

### Problem 1: "Invalid redirect URI" xətası

**Həll:**
1. Google Cloud Console-da **"Credentials"** → OAuth 2.0 Client ID-ni açın
2. **"Authorized redirect URIs"** bölməsində düzgün URL olduğunu yoxlayın:
   ```
   http://localhost:8000/api/v1/google/callback
   ```
3. `.env` faylında `GOOGLE_REDIRECT_URI` dəyərinin eyni olduğunu yoxlayın

### Problem 2: "Access denied" xətası

**Həll:**
1. OAuth2 authorization-u yenidən tamamlayın
2. Google hesabınızda Calendar access icazəsi olduğunu yoxlayın
3. Token-lərin yenilənməsi lazımdırsa, yenidən authorization edin

### Problem 3: "Calendar API not enabled" xətası

**Həll:**
1. Google Cloud Console-da **"APIs & Services"** → **"Library"**
2. **"Google Calendar API"** aktiv olduğunu yoxlayın
3. Əgər aktiv deyilsə, **"Enable"** klik edin

### Problem 4: Meeting link yaranmır

**Həll:**
1. OAuth2 authorization tamamlandığını yoxlayın
2. `has_access` endpoint-i `true` qaytarırmı yoxlayın
3. Google Calendar API aktivdir
4. Meeting vaxtı gələcək tarixdədir

---

## ✅ Yoxlama Siyahısı

Quraşdırmanın düzgün olduğunu yoxlamaq üçün:

- [ ] Google Cloud Console-da layihə yaradıldı
- [ ] Google Calendar API aktivləşdirildi
- [ ] OAuth 2.0 Client ID yaradıldı
- [ ] Redirect URI düzgün konfiqurasiya edildi
- [ ] `.env` faylında bütün dəyərlər dolduruldu
- [ ] Config cache təmizləndi
- [ ] OAuth2 authorization tamamlandı
- [ ] `check-access` endpoint `true` qaytarır
- [ ] Test meeting yaradıldı
- [ ] Google Meet link işləyir

---

## 🎉 Hazırdır!

İndi Google Meet inteqrasiyası hazırdır və istifadəyə yararlıdır. Trainers və admin-lər meeting-lər yarada bilər, istifadəçilər isə qeydiyyatdan keçib meeting-lərə qoşula bilərlər.

---

## 📞 Əlavə Məlumat

- **Laravel Backend**: Artıq hazırdır, konfiqurasiya lazımdır
- **API Endpoints**: Bütün endpoint-lər işləyir
- **Database**: Migration-lar tamamlanıb
- **Frontend**: API-ləri çağıraraq istifadə edə bilərsiniz

**Suallarınız varsa, dokumentasiya fayllarına baxın:**
- `GOOGLE_MEET_INTEGRATION_GUIDE.md`
- `GOOGLE_MEET_OAUTH2_COMPLETE_GUIDE.md`
- `POSTMAN_GOOGLE_MEET_OAUTH2_GUIDE.md`

