# FAQ API Guide

Bu sənəd FAQ (Tez-tez verilən suallar) API-sinin istifadə qaydalarını izah edir.

## API Endpoints

### 1. Bütün FAQ-ları əldə etmək
```
GET /api/v1/faqs
```

**Parametrlər:**
- `search` (optional): Axtarış mətnini daxil edin
- `category` (optional): Kateqoriya ilə filtr edin
- `is_active` (optional): Aktiv status ilə filtr edin (true/false)
- `sort_by` (optional): Sıralama sahəsi (question, category, helpful_count, created_at)
- `sort_order` (optional): Sıralama istiqaməti (asc, desc)

**Nümunə sorğu:**
```
GET /api/v1/faqs?search=qeydiyyat&category=Qeydiyyat&sort_by=helpful_count&sort_order=desc
```

**Cavab:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "question": "Sistemdə necə qeydiyyatdan keçmək olar?",
            "answer": "Sistemdə qeydiyyatdan keçmək üçün ana səhifədə 'Qeydiyyat' düyməsini basın...",
            "category": "Qeydiyyat",
            "helpful_count": 5,
            "is_active": true,
            "created_at": "2025-10-29T18:00:00.000000Z",
            "updated_at": "2025-10-29T18:00:00.000000Z",
            "creator": {
                "id": 1,
                "first_name": "Admin",
                "last_name": "User"
            }
        }
    ],
    "total": 1
}
```

### 2. Yeni FAQ yaratmaq (Admin)
```
POST /api/v1/faqs
```

**Tələb olunan sahələr:**
- `question` (string, max: 1000, min: 10): Sual
- `answer` (string, max: 2000, min: 10): Cavab
- `category` (string, max: 255, min: 2): Kateqoriya
- `is_active` (boolean, optional): Aktiv status (default: true)

**Nümunə sorğu:**
```json
{
    "question": "Sistemdə necə qeydiyyatdan keçmək olar?",
    "answer": "Sistemdə qeydiyyatdan keçmək üçün ana səhifədə 'Qeydiyyat' düyməsini basın və bütün tələb olunan məlumatları doldurun.",
    "category": "Qeydiyyat",
    "is_active": true
}
```

### 3. FAQ detallarını əldə etmək
```
GET /api/v1/faqs/{id}
```

### 4. FAQ yeniləmək (Admin)
```
PUT /api/v1/faqs/{id}
PATCH /api/v1/faqs/{id}
```

**Nümunə sorğu:**
```json
{
    "question": "Yenilənmiş sual",
    "answer": "Yenilənmiş cavab",
    "category": "Yenilənmiş Kateqoriya",
    "is_active": false
}
```

### 5. FAQ silmək (Admin)
```
DELETE /api/v1/faqs/{id}
```

### 6. FAQ-i faydalı kimi işarələmək
```
POST /api/v1/faqs/{id}/helpful
```

Bu endpoint FAQ-in `helpful_count` sahəsini 1 artırır.

### 7. FAQ kateqoriyalarını əldə etmək
```
GET /api/v1/faqs/categories
```

**Cavab:**
```json
{
    "success": true,
    "data": [
        "Qeydiyyat",
        "Təlimlər",
        "İmtahanlar",
        "Sertifikatlar"
    ]
}
```

### 8. FAQ statistikalarını əldə etmək (Admin)
```
GET /api/v1/faqs/stats
```

**Cavab:**
```json
{
    "success": true,
    "data": {
        "total_faqs": 25,
        "active_faqs": 23,
        "inactive_faqs": 2,
        "total_helpful_votes": 156,
        "categories_count": 8,
        "most_helpful": [
            {
                "id": 1,
                "question": "Ən çox faydalı sual",
                "helpful_count": 25
            }
        ]
    }
}
```

## Xəta Cavabları

### 422 Validation Error
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "question": [
            "Sual mütləqdir."
        ],
        "answer": [
            "Cavab ən azı 10 simvol olmalıdır."
        ]
    }
}
```

### 401 Unauthorized
```json
{
    "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
    "message": "This action is unauthorized."
}
```

### 404 Not Found
```json
{
    "message": "No query results for model [App\\Models\\Faq] {id}"
}
```

## İstifadə Nümunələri

### Frontend-də FAQ siyahısını göstərmək
```javascript
// Bütün FAQ-ları əldə et
fetch('/api/v1/faqs')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            data.data.forEach(faq => {
                console.log(`Sual: ${faq.question}`);
                console.log(`Cavab: ${faq.answer}`);
                console.log(`Kateqoriya: ${faq.category}`);
                console.log(`Faydalı sayı: ${faq.helpful_count}`);
            });
        }
    });
```

### Kateqoriya ilə filtr etmək
```javascript
// Qeydiyyat kateqoriyasındakı FAQ-ları əldə et
fetch('/api/v1/faqs?category=Qeydiyyat')
    .then(response => response.json())
    .then(data => {
        // FAQ-ları göstər
    });
```

### Axtarış etmək
```javascript
// "qeydiyyat" sözünü axtar
fetch('/api/v1/faqs?search=qeydiyyat')
    .then(response => response.json())
    .then(data => {
        // Nəticələri göstər
    });
```

### FAQ-i faydalı kimi işarələmək
```javascript
// FAQ-i faydalı kimi işarələ
fetch('/api/v1/faqs/1/helpful', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/json'
    }
})
.then(response => response.json())
.then(data => {
    console.log('Faydalı sayı:', data.helpful_count);
});
```

## Admin Panel üçün

### Yeni FAQ yaratmaq
```javascript
const newFaq = {
    question: "Yeni sual",
    answer: "Yeni cavab",
    category: "Yeni Kateqoriya",
    is_active: true
};

fetch('/api/v1/faqs', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer ' + adminToken,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify(newFaq)
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('FAQ uğurla yaradıldı');
    }
});
```

### FAQ yeniləmək
```javascript
const updatedFaq = {
    question: "Yenilənmiş sual",
    is_active: false
};

fetch('/api/v1/faqs/1', {
    method: 'PUT',
    headers: {
        'Authorization': 'Bearer ' + adminToken,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify(updatedFaq)
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('FAQ uğurla yeniləndi');
    }
});
```

## Qeydlər

1. **Avtorizasiya**: FAQ yaratmaq, yeniləmək və silmək üçün admin hüququ tələb olunur.
2. **Axtarış**: Axtarış həm sual, həm də cavab mətnində aparılır.
3. **Sıralama**: Default olaraq FAQ-lar yaradılma tarixinə görə azalan sırada sıralanır.
4. **Pagination**: FAQ siyahısında pagination yoxdur - bütün FAQ-lar bir dəfədə qaytarılır.
5. **Audit Log**: Bütün admin əməliyyatları audit log-a qeyd edilir.
