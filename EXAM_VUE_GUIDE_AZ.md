### Agrar Portal – İmtahan (Exam) Prosesi Üçün Frontend Bələdçi (Vue)

Bu sənədin əsas məqsədi: İmtahan sistemindəki bütün endpoint-lərin necə işlədiyini, hansı giriş/çıxışları, yoxlamaları, rolları və biznes qaydalarını dəqiq izah etməkdir. Kod nümunələri yalnız kömək üçündür; əsas fokus API-nin davranışıdır.

---

## 1. Autentifikasiya və icazə
- İmtahan API-ləri `auth:sanctum` ilə qorunur. İstifadəçi login olduqdan sonra Bearer token istifadə edin.
- Postman kolleksiyasında “Development/Testing Authentication” bölməsində test tokenləri yaratmaq üçün nümunələr var.

Rollar və icazələr (ümumi):
- admin: bütün imtahanların idarəsi
- trainer: öz təlimləri ilə əlaqəli imtahanların idarəsi (backend rolu ilə yoxlanır)
- authenticated user (tələbə): imtahana qeydiyyat, imtahanı başlamaq, həll etmək və cavab göndərmək

---

## 2. İmtahanların siyahısı (admin paneli)
Endpoint: `GET /api/v1/exams`

Filtrlər: `search, category, training_id, status, sort_by, sort_order, per_page, page`

İş prinsipi və biznes qaydaları:
- Sorğunu göndərən istifadəçinin roluna görə nəticə məhdudlaşa bilər:
  - admin: bütün imtahanlar
  - trainer: yalnız özü ilə əlaqəli təlimlərin imtahanları
- `status` serverdə tarixlərə görə hesablanır:
  - upcoming: `start_date` gələcəkdə
  - active: bu gün `start_date <= now <= end_date`
  - ended: `end_date < now`
- Sıralama: `sort_by` dəyərləri `title, created_at, start_date, end_date, passing_score`.
- Cavab: Laravel paginator formatı (data, meta, links).

```javascript
// services/api.js
import axios from 'axios'

export const api = axios.create({ baseURL: import.meta.env.VITE_API_URL })

export async function listExams(params) {
  const { data } = await api.get('/api/v1/exams', { params })
  return data
}
```

```vue
<script setup>
import { ref, onMounted } from 'vue'
import { listExams } from '@/services/api'

const loading = ref(false)
const exams = ref([])

async function load() {
  loading.value = true
  try {
    const data = await listExams({ per_page: 20, page: 1, search: '' })
    exams.value = data.data
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div>
    <h3>İmtahan Siyahısı</h3>
    <div v-if="loading">Yüklənir...</div>
    <ul v-else>
      <li v-for="exam in exams" :key="exam.id">
        {{ exam.title }} — keçid: /exams/{{ exam.id }}
      </li>
    </ul>
  </div>
  </template>
```

---

## 3. İmtahan yaratmaq (admin, trainer)
Endpoint: `POST /api/v1/exams`

Tələblər:
- Təlimə bağlı imtahan: `training_id` verin (kategoriya təlimdən gəlir)
- Müstəqil imtahan: `category` verin
- `questions[]` daxilində sual strukturları (single_choice, multiple_choice, text)

Validasiya və biznes qaydaları:
- `title` (required), `passing_score` (required, 0-100 arası məntiqli dəyər olmalıdır), `duration_minutes` (default 60, >0), tarixlər məntiqli olmalıdır (`end_date >= start_date`).
- Təlimə bağlı imtahan üçün `training_id` mütləq mövcud olmalı və icazə yoxlanmalıdır (trainer yalnız öz təliminə bağlı imtahan yarada bilər).
- Suallar:
  - `question_type`: `single_choice | multiple_choice | text`
  - `sequence`: imtahan daxilində unikal sıra (server təmin etməyə çalışır, lakin kolliziyada 422 qaytara bilər)
  - single/multiple üçün `choices[]` tələb olunur; ən azı 2 variant, ən azı 1 doğru (multiple üçün bir neçə doğru ola bilər)
  - text üçün `choices` tələb olunmur
- Əməliyyat atomikdir: istənilən addım uğursuz olarsa 400/422 qaytarılır və heç nə yaradılmır.

Cavab:
- 201 statusu, yaradılmış imtahan obyekti (ID ilə), sual və seçimlər daxil olmaqla.

```javascript
export async function createExam(payload) {
  const { data } = await api.post('/api/v1/exams', payload)
  return data
}
```

```vue
<script setup>
import { ref } from 'vue'
import { createExam } from '@/services/api'

const form = ref({
  training_id: 1,
  title: 'Bitki Becerilmesi Sınağı',
  description: 'Təsvir...',
  passing_score: 70,
  duration_minutes: 60,
  start_date: '2025-10-01',
  end_date: '2025-10-15',
  questions: [
    { question_text: 'Əsas prinsip?', question_type: 'single_choice', sequence: 1, choices: [
      { choice_text: 'Torpaq hazırlığı', is_correct: true },
      { choice_text: 'Yalnız suvarma', is_correct: false }
    ]}
  ]
})

async function submit() {
  await createExam(form.value)
}
</script>

<template>
  <button @click="submit">Yarat</button>
</template>
```

---

## 4. İmtahanı başlamaq (tələbə)
Endpoint: `POST /api/v1/exams/{exam}/start`

İş prinsipi:
- Şərtlər: istifadəçi imtahana qeydiyyatdan keçmiş olmalıdır (`/exams/{id}/register`).
- Server bir sessiya/registration qeydini `in_progress` vəziyyətinə gətirir və `started_at` qeyd edir.
- Vaxt ölçümü buradan başlayır (serverdə). İcazə verilməyən hallarda:
  - imtahan aktiv deyil (upcoming/ended) → 400
  - artıq `in_progress` və vaxt bitməyibsə → 200 döndərə bilər (idempotent).

Cavab:
- 200, registration məlumatı və ya sadə təsdiq.
```javascript
export async function startExam(examId) {
  const { data } = await api.post(`/api/v1/exams/${examId}/start`)
  return data
}
```

---

## 5. İmtahanı həll etmək (take)
Endpoint: `GET /api/v1/exams/{exam}/take`

Qayıdır: suallar, vaxt məlumatı (`time_remaining`, `time_exceeded` və s.)

Biznes qaydaları və məntiq:
- Server istifadəçinin aktiv sessiyasını tapır (`in_progress`).
- Vaxt hesablanması: `time_elapsed = now - started_at`, `time_remaining = duration_minutes - time_elapsed`. Əgər `time_remaining <= 0` isə `time_exceeded=true`.
- Suallar cavab açarları olmadan qaytarılır; yalnız tələbənin həll etməsi üçün lazım olan sahələr.
- Əgər sessiya yoxdur və ya imtahan aktiv deyil → 400/404.

```javascript
export async function getExamForTaking(examId) {
  const { data } = await api.get(`/api/v1/exams/${examId}/take`)
  return data
}
```

---

## 6. Cavabların göndərilməsi
Endpoint: `POST /api/v1/exams/{exam}/submit`

Göndərmə nümunəsi:

Qiymətləndirmə və biznes məntiqi:
- Server hər cavabı sual tipinə görə yoxlayır:
  - single_choice: `choice_id` bir dəyər, doğruluq `exam_choices.is_correct`
  - multiple_choice: `choice_ids[]` toplusu, tam uyğunluq və ya qismən bal qaydası layihədə müəyyən edilib (hazırda tam uyğunluq üstünlük təşkil edir; qismən bal varsa modeldə `points` bölünür)
  - text: avtomatik qiymətləndirilməyən, `is_correct=false`, lakin bal 0/sonradan manual ola bilər (hazırkı layihədə default 0)
- Vaxt nəzarəti: əgər `time_exceeded=true`, status `timeout` və ya `failed` qaytarıla bilər (Layihədə: late submission → `timeout`, sertifikat yoxdur).
- `score` hesablanır, `passed` meyarı: `score >= passing_score`.
- Uğurda: registration `completed`/`passed`/`failed` olaraq yenilənir, sertifikat yaradıla bilər (pass olduqda).

Tipik cavablar:
- 200: `{ status: 'passed' | 'failed' | 'timeout', score, certificate?: {...} }`
- 400/422: validasiya, vaxt və ya sessiya səhvləri.
```javascript
export async function submitExam(examId, answers) {
  const { data } = await api.post(`/api/v1/exams/${examId}/submit`, { answers })
  return data
}
```

```vue
<script setup>
import { ref } from 'vue'
import { submitExam } from '@/services/api'

const answers = ref([
  { question_id: 1, choice_id: 2 },
  { question_id: 2, choice_ids: [3, 4] },
  { question_id: 3, answer_text: 'Mətn cavabı' }
])

async function send(examId: number) {
  const result = await submitExam(examId, answers.value)
  console.log(result.status, result.score)
}
</script>
```

---

## 7. Sertifikatlar
Endpointlər: `GET /api/v1/certificates`, `GET /api/v1/certificates/{id}`

```javascript
export async function listCertificates() {
  const { data } = await api.get('/api/v1/certificates')
  return data
}
```

İş prinsipi:
- Siyahıda istifadəçiyə məxsus aktiv sertifikatlar qaytarılır.
- Detal çağırışında həmin sertifikatın bütün məlumatları (nömrə, issue/expiry, bağlı imtahan/təlim) təqdim edilir.

---

## 8. Əsas UI axını (tələbə)
1) Kurs → İmtahana qeydiyyat `POST /api/v1/exams/{id}/register`
2) Başlat `POST /api/v1/exams/{id}/start`
3) Sualları yüklə `GET /api/v1/exams/{id}/take`
4) Cavabları göndər `POST /api/v1/exams/{id}/submit`
5) Nəticə ekranı və Sertifikat siyahısı

---

## 9. Error handling və UX təklifləri
- Vaxt keçibsə backend `time_exceeded` qaytarır → frontend avtomatik nəticə ekranına yönləndirsin.
- Çoxseçimli suallar üçün checkboxes, tək seçim üçün radio istifadə edin.
- Göndər düyməsində loading və disable vəziyyəti.

Tipik səhvlər və kodlar:
- 401: token etibarsızdır.
- 403: rol/akses icazəsi yoxdur.
- 404: imtahan/sual/sessiya tapılmadı.
- 422: forma/validasiya səhvləri (mesajlar sahə üzrə).
- 409: paralel sessiya konflikti (nadir hal, idempotentlik zədələnərsə).


