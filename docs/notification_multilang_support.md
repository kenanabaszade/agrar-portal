# Bildirişlərin Çoxdilli Dəstəyi

## ✅ Sistem Dəstəkləyir

Backend-də bildirişlər **3 dili dəstəkləyir:**
- ✅ **Azərbaycan dili (az)** - Default
- ✅ **İngilis dili (en)**
- ✅ **Rus dili (ru)**

---

## 📊 Hazırkı Vəziyyət

### Backend-də Bildiriş Yaradılarkən

Hazırda bildirişlər yalnız **Azərbaycan dilində** göndərilir:

```php
// TrainingController-də
$notificationService->send(
    $user,
    'training',
    ['az' => $title],        // Yalnız az
    ['az' => $message],      // Yalnız az
    [...]
);
```

### Database-də Saxlanılır

Bildirişlər database-də **JSON formatında** saxlanılır:

```json
{
  "title": {
    "az": "Yeni təlim əlavə olundu"
  },
  "message": {
    "az": "Laravel Backend Development adlı yeni təlim əlavə olundu."
  }
}
```

### Frontend-də Göstərilir

Frontend-də istifadəçinin seçdiyi dilə görə göstərilir:

```javascript
const locale = localStorage.getItem('locale') || 'az';
const title = notification.title[locale] || notification.title.az;
const message = notification.message[locale] || notification.message.az;
```

**Nəticə:**
- Əgər `locale = 'az'` → Azərbaycan dili göstərilir
- Əgər `locale = 'en'` → İngilis dili göstərilir (yoxdursa, az fallback)
- Əgər `locale = 'ru'` → Rus dili göstərilir (yoxdursa, az fallback)

---

## 🔧 3 Dili Dəstəkləmək Üçün

### Seçim 1: Backend-də 3 Dili Göndərmək

Bildiriş yaradarkən 3 dili də göndərə bilərsiniz:

```php
$notificationService->send(
    $user,
    'training',
    [
        'az' => 'Yeni təlim əlavə olundu',
        'en' => 'New training added',
        'ru' => 'Добавлено новое обучение'
    ],
    [
        'az' => 'Laravel Backend Development adlı yeni təlim əlavə olundu.',
        'en' => 'New training added: Laravel Backend Development',
        'ru' => 'Добавлено новое обучение: Laravel Backend Development'
    ],
    [...]
);
```

**Nəticə:**
```json
{
  "title": {
    "az": "Yeni təlim əlavə olundu",
    "en": "New training added",
    "ru": "Добавлено новое обучение"
  },
  "message": {
    "az": "Laravel Backend Development adlı yeni təlim əlavə olundu.",
    "en": "New training added: Laravel Backend Development",
    "ru": "Добавлено новое обучение: Laravel Backend Development"
  }
}
```

### Seçim 2: Hazırkı Sistem (Yalnız Az)

Hazırkı sistemdə yalnız Azərbaycan dili göndərilir, frontend fallback edir:

```php
// Backend
['az' => $title]

// Frontend
const title = notification.title[locale] || notification.title.az;
```

**Nəticə:**
- Azərbaycan dili seçilərsə → Azərbaycan dili göstərilir
- İngilis/Rus dili seçilərsə → Azərbaycan dili göstərilir (fallback)

---

## 📝 API Response Format

### Hazırkı Format (Yalnız Az)

```json
{
  "id": 1,
  "title": {
    "az": "Yeni təlim əlavə olundu"
  },
  "message": {
    "az": "Laravel Backend Development adlı yeni təlim əlavə olundu."
  }
}
```

### 3 Dili Dəstəkləyəndə

```json
{
  "id": 1,
  "title": {
    "az": "Yeni təlim əlavə olundu",
    "en": "New training added",
    "ru": "Добавлено новое обучение"
  },
  "message": {
    "az": "Laravel Backend Development adlı yeni təlim əlavə olundu.",
    "en": "New training added: Laravel Backend Development",
    "ru": "Добавлено новое обучение: Laravel Backend Development"
  }
}
```

---

## 🎯 Frontend-də İstifadə

### Vue.js / React

```javascript
// Locale-ə görə göstər
const locale = localStorage.getItem('locale') || 'az';

// Title
const title = notification.title[locale] 
    || notification.title['az'] 
    || Object.values(notification.title)[0];

// Message
const message = notification.message[locale] 
    || notification.message['az'] 
    || Object.values(notification.message)[0];
```

### Fallback Sistemi

1. **İlk:** Seçilmiş dil (az, en, ru)
2. **İkinci:** Azərbaycan dili (default)
3. **Üçüncü:** İlk mövcud dil

---

## ✅ Nəticə

**Hazırkı sistem:**
- ✅ 3 dili dəstəkləyir (az, en, ru)
- ✅ Database-də JSON formatında saxlanır
- ✅ Frontend-də locale-ə görə göstərilir
- ⚠️ Backend-də yalnız Azərbaycan dili göndərilir

**3 dili tam dəstəkləmək üçün:**
- Backend-də bildiriş yaradarkən 3 dili də göndərmək lazımdır
- Və ya translation service istifadə etmək

**Frontend üçün:**
- Sistem hazırdır, locale-ə görə göstərir
- Fallback sistemi işləyir

