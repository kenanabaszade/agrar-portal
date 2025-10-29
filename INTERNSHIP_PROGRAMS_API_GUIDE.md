# Staj Proqramları API Sənədləri

## Ümumi Məlumat

Staj Proqramları sistemi adminlərə staj proqramları yaratmaq və idarə etmək, istifadəçilərə isə bu proqramlara müraciət etmək imkanı verir.

## Əsas Xüsusiyyətlər

- **Admin Funksionallığı**: Staj proqramları yaratmaq, redaktə etmək və silmək
- **İstifadəçi Funksionallığı**: Staj proqramlarına baxmaq və CV ilə müraciət etmək
- **Avtomatik Bildiriş**: Yeni müraciətlər adminlərin e-mailinə göndərilir
- **Status İdarəetməsi**: Müraciətlərin statusunu dəyişdirmək (pending, accepted, rejected)

## API Endpoint-ləri

### 1. Staj Proqramları (Public)

#### Bütün Staj Proqramlarını Gətir
```http
GET /api/v1/internship-programs
```

**Parametrlər:**
- `category` (optional): Kateqoriya ilə filtr
- `registration_status` (optional): Qeydiyyat statusu ilə filtr (open, closed, full)
- `featured` (optional): Təqdim edilən proqramlar (true/false)
- `search` (optional): Axtarış mətnini
- `per_page` (optional): Səhifə başına element sayı (default: 15)

**Cavab:**
```json
{
  "programs": {
    "data": [
      {
        "id": 1,
        "title": "Web Development Stajı",
        "description": "Müasir web texnologiyaları öyrənmək",
        "category": "IT",
        "duration_weeks": 8,
        "start_date": "2024-01-15",
        "last_register_date": "2024-01-10",
        "location": "Bakı",
        "current_enrollment": 5,
        "max_capacity": 20,
        "registration_status": "open",
        "is_featured": true,
        "instructor_name": "Əli Məmmədov",
        "instructor_title": "Senior Developer",
        "image_url": "https://example.com/image.jpg",
        "enrollment_percentage": 25.0,
        "is_full": false,
        "modules": [...],
        "requirements": [...],
        "goals": [...]
      }
    ],
    "meta": {
      "total": 10,
      "per_page": 15,
      "current_page": 1,
      "last_page": 1
    }
  }
}
```

#### Təqdim Edilən Proqramları Gətir
```http
GET /api/v1/internship-programs/featured
```

#### Kateqoriyaları Gətir
```http
GET /api/v1/internship-programs/categories
```

#### Məktəbçiləri Gətir
```http
GET /api/v1/internship-programs/trainers
```

#### Tək Proqramı Gətir
```http
GET /api/v1/internship-programs/{id}
```

### 2. İstifadəçi Müraciətləri

#### Staj Proqramına Müraciət Et
```http
POST /api/v1/internship-programs/{id}/apply
```

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Form Data:**
- `cv` (required): CV faylı (PDF, DOC, DOCX, max 5MB)
- `cover_letter` (optional): Müraciət məktubu (max 2000 simvol)

**Cavab:**
```json
{
  "message": "Müraciətiniz uğurla göndərildi",
  "application": {
    "id": 1,
    "user_id": 5,
    "internship_program_id": 1,
    "cv_file_name": "cv.pdf",
    "cv_file_size": "1024000",
    "cover_letter": "Mən bu staj proqramına müraciət edirəm...",
    "status": "pending",
    "created_at": "2024-01-01T10:00:00Z"
  }
}
```

#### Mənim Müraciətlərimi Gətir
```http
GET /api/v1/my-internship-applications
```

**Headers:**
```
Authorization: Bearer {token}
```

#### Müraciət Detallarını Gətir
```http
GET /api/v1/internship-applications/{id}
```

#### CV Faylını Yüklə
```http
GET /api/v1/internship-applications/{id}/download-cv
```

#### Müraciəti Sil
```http
DELETE /api/v1/internship-applications/{id}
```

### 3. Admin Funksionallığı

#### Staj Proqramı Yarat
```http
POST /api/v1/internship-programs
```

**Headers:**
```
Authorization: Bearer {admin_token}
Content-Type: multipart/form-data
```

**Form Data:**
- `title` (required): Proqram adı
- `description` (required): Təsvir
- `category` (required): Kateqoriya
- `duration_weeks` (required): Müddət (həftə)
- `start_date` (required): Başlama tarixi
- `last_register_date` (optional): Son qeydiyyat tarixi
- `location` (required): Məkan
- `max_capacity` (required): Maksimum iştirakçı sayı
- `instructor_name` (required): Məktəbçi adı
- `instructor_title` (required): Məktəbçi vəzifəsi
- `instructor_description` (optional): Məktəbçi haqqında
- `trainer_id` (optional): Məktəbçi ID (sistemdəki istifadəçi)
- `registration_status` (required): Qeydiyyat statusu (open/closed/full)
- `is_featured` (optional): Təqdim edilən (true/false)
- `image` (optional): Şəkil faylı
- `modules` (optional): Modullar array
- `requirements` (optional): Tələblər array
- `goals` (optional): Məqsədlər array

**Modullar Formatı:**
```json
{
  "modules": [
    {
      "title": "HTML/CSS",
      "description": "Web dizaynın əsasları",
      "order": 1
    }
  ]
}
```

**Tələblər Formatı:**
```json
{
  "requirements": [
    {
      "requirement": "HTML bilikləri",
      "order": 1
    }
  ]
}
```

**Məqsədlər Formatı:**
```json
{
  "goals": [
    {
      "goal": "Responsive dizayn öyrənmək",
      "order": 1
    }
  ]
}
```

#### Staj Proqramını Yenilə
```http
PUT /api/v1/internship-programs/{id}
```

#### Staj Proqramını Sil
```http
DELETE /api/v1/internship-programs/{id}
```

#### Proqramın Müraciətlərini Gətir
```http
GET /api/v1/internship-programs/{id}/applications
```

#### Proqramın Qəbul Edilmiş İstifadəçilərini Gətir
```http
GET /api/v1/internship-programs/{id}/enrolled-users
```

#### Proqram Statistikalarını Gətir
```http
GET /api/v1/internship-programs/{id}/stats
```

**Cavab:**
```json
{
  "stats": {
    "total_applications": 25,
    "pending_applications": 10,
    "accepted_applications": 12,
    "rejected_applications": 3,
    "enrollment_percentage": 60.0,
    "is_full": false,
    "remaining_spots": 8
  }
}
```

#### Bütün Müraciətləri Gətir (Admin)
```http
GET /api/v1/admin/internship-applications
```

**Parametrlər:**
- `program_id` (optional): Proqram ID ilə filtr
- `status` (optional): Status ilə filtr
- `user_id` (optional): İstifadəçi ID ilə filtr
- `search` (optional): İstifadəçi adı/e-mail ilə axtarış

#### Müraciət Statusunu Yenilə
```http
PUT /api/v1/admin/internship-applications/{id}/status
```

**Body:**
```json
{
  "status": "accepted",
  "admin_notes": "Çox yaxşı CV və təcrübə"
}
```

## Xəta Kodları

- `400`: Yanlış məlumat göndərilmişdir
- `401`: Autentifikasiya tələb olunur
- `403`: Bu əməliyyatı yerinə yetirmək üçün icazəniz yoxdur
- `404`: Məlumat tapılmadı
- `422`: Validasiya xətası
- `500`: Server xətası

## Xəta Mesajları

```json
{
  "message": "Bu staj proqramına qeydiyyat bağlıdır",
  "error": "Registration is closed"
}
```

## Fayl Yükləmə Qaydaları

- **CV Faylları**: PDF, DOC, DOCX formatları qəbul edilir
- **Maksimum Ölçü**: 5MB
- **Şəkil Faylları**: JPEG, PNG, JPG, GIF formatları qəbul edilir
- **Maksimum Ölçü**: 2MB

## E-mail Bildirişləri

Yeni müraciət göndərildikdə bütün adminlərə e-mail göndərilir. E-mail-də:
- İstifadəçi məlumatları
- Proqram məlumatları
- CV faylı əlavə edilir
- Müraciət məktubu (varsa)

## Statuslar

### Proqram Statusları
- `open`: Qeydiyyat açıqdır
- `closed`: Qeydiyyat bağlıdır
- `full`: Yerlər dolub

### Müraciət Statusları
- `pending`: Gözləyir
- `reviewed`: Nəzərdən keçirilmişdir
- `accepted`: Qəbul edilmişdir
- `rejected`: Rədd edilmişdir

## İstifadə Nümunələri

### 1. İstifadəçi Müraciət Edir

```javascript
const formData = new FormData();
formData.append('cv', cvFile);
formData.append('cover_letter', 'Mən bu proqramda iştirak etmək istəyirəm...');

fetch('/api/v1/internship-programs/1/apply', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token
  },
  body: formData
})
.then(response => response.json())
.then(data => console.log(data));
```

### 2. Admin Proqram Yaradır

```javascript
const formData = new FormData();
formData.append('title', 'Web Development Stajı');
formData.append('description', 'Müasir web texnologiyaları...');
formData.append('category', 'IT');
formData.append('duration_weeks', '8');
formData.append('start_date', '2024-01-15');
formData.append('location', 'Bakı');
formData.append('max_capacity', '20');
formData.append('instructor_name', 'Əli Məmmədov');
formData.append('instructor_title', 'Senior Developer');
formData.append('registration_status', 'open');

fetch('/api/v1/internship-programs', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + adminToken
  },
  body: formData
})
.then(response => response.json())
.then(data => console.log(data));
```

### 3. Admin Müraciət Statusunu Yeniləyir

```javascript
fetch('/api/v1/admin/internship-applications/1/status', {
  method: 'PUT',
  headers: {
    'Authorization': 'Bearer ' + adminToken,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    status: 'accepted',
    admin_notes: 'Çox yaxşı CV və təcrübə'
  })
})
.then(response => response.json())
.then(data => console.log(data));
```

## Qeydlər

- Bütün tarixlər ISO 8601 formatında (YYYY-MM-DD)
- Fayl yükləmələri multipart/form-data formatında göndərilməlidir
- Admin token-ları role:admin middleware-i ilə yoxlanılır
- İstifadəçi token-ları auth:sanctum middleware-i ilə yoxlanılır
- E-mail bildirişləri queue-da işlənir (performans üçün)