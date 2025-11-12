# ⚡ Çoxdilli Sistem - Quick Start Guide

## 🎯 5 Dəqiqədə Başlamaq

### 1. Dil Parametrini Əlavə Et

Bütün API request-lərinə `?lang=xx` parametri əlavə et:

```javascript
// ❌ Köhnə
GET /api/v1/trainings

// ✅ Yeni
GET /api/v1/trainings?lang=en
```

### 2. Request Format-ı Dəyişdir

**POST/PUT request-lərdə text sahələri object formatında göndər:**

```javascript
// ❌ Köhnə
{
  "title": "Test Training"
}

// ✅ Yeni
{
  "title": {
    "az": "Test Təlim",
    "en": "Test Training",
    "ru": "Тестовое обучение"
  }
}
```

### 3. Response Format

Response-lar avtomatik olaraq `lang` parametrinə görə qaytarılır:

```javascript
// Request: GET /api/v1/trainings?lang=en
// Response:
{
  "id": 1,
  "title": "Test Training",  // İngilis versiyası
  "description": "English description"
}
```

---

## 🔑 Əsas Nöqtələr

1. **Default dil:** `az` (lang parametri yoxdursa)
2. **Mütləq sahə:** Ən azı `az` versiyası olmalıdır
3. **Optional sahələr:** İstənilən dil versiyası olmaya bilər
4. **Format:** Translation obyekti həmişə `{az: "...", en: "...", ru: "..."}` formatında

---

## 📝 Minimal Nümunə

```javascript
// 1. API request
const trainings = await fetch('/api/v1/trainings?lang=en', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
}).then(r => r.json());

// 2. Form submit
await fetch('/api/v1/trainings', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({
    title: {
      az: "Yeni Təlim",
      en: "New Training",
      ru: "Новое обучение"
    },
    description: {
      az: "Təsvir",
      en: "Description"
    },
    trainer_id: 1
  })
});
```

---

## ⚠️ Əsas Xətalardan Qaçın

1. **String göndərməyin** - Həmişə object formatında göndərin
2. **Lang parametrini unutmayın** - Həmişə `?lang=xx` əlavə edin
3. **Az versiyasını unutmayın** - Mütləq sahələr üçün `az` versiyası olmalıdır

---

📖 **Tam dokumentasiya üçün:** `FRONTEND_DEVELOPER_MULTILANG_GUIDE.md` faylına baxın

