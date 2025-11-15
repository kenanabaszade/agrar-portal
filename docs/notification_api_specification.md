# Bildiriş API Spesifikasiyası

## 📋 API Endpoint-ləri

### 1. Bildiriş Parametrlərini Gətirmək

**Endpoint:**
```
GET /api/v1/notifications/preferences
```

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Cavab (200 OK):**
```json
{
  "email_notifications_enabled": true,
  "push_notifications_enabled": true
}
```

**Xəta (401 Unauthorized):**
```json
{
  "message": "Unauthenticated."
}
```

---

### 2. Bildiriş Parametrlərini Yeniləmək

**Endpoint:**
```
PATCH /api/v1/notifications/preferences
```

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "email_notifications_enabled": false,
  "push_notifications_enabled": true
}
```

**Qeyd:** Hər iki field `optional`-dır. Yalnız dəyişdirmək istədiyinizi göndərin.

**Cavab (200 OK):**
```json
{
  "message": "Bildiriş parametrləri yeniləndi",
  "email_notifications_enabled": false,
  "push_notifications_enabled": true
}
```

**Xəta (422 Validation Error):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email_notifications_enabled": ["The email notifications enabled must be true or false."]
  }
}
```

---

### 3. Bildirişləri Gətirmək

**Endpoint:**
```
GET /api/v1/notifications
```

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Query Parametrləri:**
- `per_page` (optional, integer, default: 20, max: 100) - Səhifədə neçə bildiriş
- `type` (optional, string) - Bildiriş tipi: `training`, `exam`, `system`, `payment`, `forum`
- `unread` (optional, boolean) - Yalnız oxunmamış bildirişlər

**Nümunə Sorğular:**
```
GET /api/v1/notifications
GET /api/v1/notifications?per_page=50
GET /api/v1/notifications?type=training
GET /api/v1/notifications?unread=true
GET /api/v1/notifications?type=training&unread=true&per_page=30
```

**Cavab (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "type": "training",
      "title": {
        "az": "Yeni təlim əlavə olundu",
        "en": "New training added"
      },
      "message": {
        "az": "Laravel Backend Development adlı yeni təlim əlavə olundu.",
        "en": "New training added: Laravel Backend Development"
      },
      "data": {
        "training_id": 123,
        "action": "created",
        "google_meet_link": null
      },
      "channels": ["database", "push", "mail"],
      "is_read": false,
      "sent_at": "2025-11-15T10:30:00.000000Z",
      "created_at": "2025-11-15T10:30:00.000000Z"
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/v1/notifications?page=1",
    "last": "http://localhost:8000/api/v1/notifications?page=5",
    "prev": null,
    "next": "http://localhost:8000/api/v1/notifications?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "path": "http://localhost:8000/api/v1/notifications",
    "per_page": 20,
    "to": 20,
    "total": 100
  }
}
```

---

### 4. Bildirişi Oxunmuş Kimi İşarələmək

**Endpoint:**
```
POST /api/v1/notifications/{notification_id}/read
```

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**URL Parametrləri:**
- `notification_id` (required, integer) - Bildiriş ID-si

**Nümunə:**
```
POST /api/v1/notifications/1/read
```

**Request Body:** Yoxdur (boş)

**Cavab (200 OK):**
```json
{
  "id": 1,
  "type": "training",
  "title": {
    "az": "Yeni təlim əlavə olundu"
  },
  "message": {
    "az": "Laravel Backend Development adlı yeni təlim əlavə olundu."
  },
  "data": {
    "training_id": 123,
    "action": "created"
  },
  "is_read": true,
  "sent_at": "2025-11-15T10:30:00.000000Z"
}
```

**Xəta (403 Forbidden):**
```json
{
  "message": "This action is unauthorized."
}
```
*Bu bildiriş başqa user-ə aid olduqda*

**Xəta (404 Not Found):**
```json
{
  "message": "No query results for model [App\\Models\\Notification] 999"
}
```

---

### 5. Bütün Bildirişləri Oxunmuş Kimi İşarələmək

**Endpoint:**
```
POST /api/v1/notifications/mark-all-read
```

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Request Body:** Yoxdur (boş)

**Cavab (200 OK):**
```json
{
  "message": "Bütün bildirişlər oxundu kimi işarələndi"
}
```

---

### 6. Oxunmamış Bildirişlərin Sayı

**Endpoint:**
```
GET /api/v1/notifications/unread-count
```

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Cavab (200 OK):**
```json
{
  "count": 5
}
```

---

## 🔐 Authentication

Bütün endpoint-lər **authentication** tələb edir.

**Header:**
```
Authorization: Bearer {token}
```

**Token almaq:**
- Login endpoint-dən token alın
- Token-i localStorage və ya state management-da saxlayın
- Hər sorğuda `Authorization` header-ında göndərin

**Xəta (401 Unauthorized):**
```json
{
  "message": "Unauthenticated."
}
```

---

## 📊 Bildiriş Strukturu

### Bildiriş Objekti

```json
{
  "id": 1,
  "type": "training",
  "title": {
    "az": "Yeni təlim əlavə olundu",
    "en": "New training added"
  },
  "message": {
    "az": "Laravel Backend Development adlı yeni təlim əlavə olundu.",
    "en": "New training added: Laravel Backend Development"
  },
  "data": {
    "training_id": 123,
    "action": "created",
    "google_meet_link": null
  },
  "channels": ["database", "push", "mail"],
  "is_read": false,
  "sent_at": "2025-11-15T10:30:00.000000Z",
  "created_at": "2025-11-15T10:30:00.000000Z"
}
```

### Field Açıqlamaları

- **id** (integer) - Bildiriş ID-si
- **type** (string) - Bildiriş tipi: `training`, `exam`, `system`, `payment`, `forum`
- **title** (object) - Çoxdilli başlıq: `{ "az": "...", "en": "..." }`
- **message** (object) - Çoxdilli mesaj: `{ "az": "...", "en": "..." }`
- **data** (object|null) - Əlavə məlumatlar (bildiriş tipindən asılıdır)
- **channels** (array) - Bildirişin göndərildiyi kanallar: `["database", "push", "mail"]`
- **is_read** (boolean) - Oxunub-oxunmadığı
- **sent_at** (datetime) - Göndərilmə tarixi
- **created_at** (datetime) - Yaradılma tarixi

### Data Field Məzmunu

**Training bildirişləri üçün:**
```json
{
  "training_id": 123,
  "action": "created",
  "google_meet_link": "https://meet.google.com/..."
}
```

**Exam bildirişləri üçün:**
```json
{
  "exam_id": 456,
  "registration_id": 789,
  "result": "passed"
}
```

---

## 🎯 Bildiriş Tipləri

| Tip | Açıqlama | Data Field |
|-----|----------|------------|
| `training` | Təlim bildirişləri | `training_id`, `action`, `google_meet_link` |
| `exam` | İmtahan bildirişləri | `exam_id`, `registration_id`, `result` |
| `system` | Sistem bildirişləri | `null` və ya custom |
| `payment` | Ödəniş bildirişləri | `payment_id`, `amount`, etc. |
| `forum` | Forum bildirişləri | `question_id`, `answer_id`, etc. |

---

## ⚠️ Xəta Kodları

| Kod | Açıqlama |
|-----|----------|
| **200** | Uğurlu |
| **201** | Yaradıldı |
| **401** | Unauthenticated (token yoxdur və ya yanlışdır) |
| **403** | Forbidden (bu bildiriş sizə aid deyil) |
| **404** | Not Found (bildiriş tapılmadı) |
| **422** | Validation Error (request body yanlışdır) |
| **500** | Server Error |

---

## 📝 Nümunə Request/Response

### Nümunə 1: Parametrləri Gətirmək

**Request:**
```http
GET /api/v1/notifications/preferences HTTP/1.1
Host: localhost:8000
Authorization: Bearer 1|abc123def456...
Accept: application/json
```

**Response:**
```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "email_notifications_enabled": true,
  "push_notifications_enabled": true
}
```

---

### Nümunə 2: Parametrləri Yeniləmək

**Request:**
```http
PATCH /api/v1/notifications/preferences HTTP/1.1
Host: localhost:8000
Authorization: Bearer 1|abc123def456...
Content-Type: application/json
Accept: application/json

{
  "email_notifications_enabled": false,
  "push_notifications_enabled": true
}
```

**Response:**
```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "message": "Bildiriş parametrləri yeniləndi",
  "email_notifications_enabled": false,
  "push_notifications_enabled": true
}
```

---

### Nümunə 3: Bildirişləri Gətirmək

**Request:**
```http
GET /api/v1/notifications?per_page=20&unread=true HTTP/1.1
Host: localhost:8000
Authorization: Bearer 1|abc123def456...
Accept: application/json
```

**Response:**
```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "data": [...],
  "links": {...},
  "meta": {...}
}
```

---

### Nümunə 4: Bildirişi Oxunmuş Kimi İşarələmək

**Request:**
```http
POST /api/v1/notifications/1/read HTTP/1.1
Host: localhost:8000
Authorization: Bearer 1|abc123def456...
Accept: application/json
```

**Response:**
```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "id": 1,
  "is_read": true,
  ...
}
```

---

## 🔄 Real-Time Broadcasting

Real-time bildirişlər üçün WebSocket istifadə olunur.

**Channel:** `private-notifications.{userId}`

**Event:** `App\Events\NotificationCreated`

**Broadcasting Auth Endpoint:**
```
POST /api/v1/broadcasting/auth
```

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Request Body:**
```json
{
  "socket_id": "123.456",
  "channel_name": "private-notifications.1"
}
```

**Response:**
```json
{
  "auth": "presence-channel-signature",
  "channel_data": "..."
}
```

---

## ✅ Xülasə

**GET Endpoint-lər:**
- `GET /api/v1/notifications/preferences` - Parametrləri gətir
- `GET /api/v1/notifications` - Bildirişləri gətir
- `GET /api/v1/notifications/unread-count` - Oxunmamış sayı

**POST Endpoint-lər:**
- `POST /api/v1/notifications/{id}/read` - Bildirişi oxunmuş kimi işarələ
- `POST /api/v1/notifications/mark-all-read` - Hamısını oxunmuş kimi işarələ
- `POST /api/v1/broadcasting/auth` - WebSocket auth

**PATCH Endpoint-lər:**
- `PATCH /api/v1/notifications/preferences` - Parametrləri yenilə

**Bütün endpoint-lər:**
- Authentication tələb edir (`Authorization: Bearer {token}`)
- JSON format istifadə edir
- Error handling lazımdır

