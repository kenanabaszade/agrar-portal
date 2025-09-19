### Agrar Portal – Forum Prosesi Üçün Frontend Bələdçi (Vue)

Bu sənəd həm admin idarəetməsi, həm də istifadəçi tərəfi üçün forum funksiyalarını izah edir. Bütün nümunələr Vue Composition API ilə verilir.

Əsas fokus: Endpoint-lərin davranışı, validasiyalar, rollar və biznes məntiqi. Kod yalnız bonusdur.

---

## 1. Arxitektura və rollar (düzgün model)
- Admin: forumun idarə edilməsi – sual yaratmaq, redaktə, silmək, pin etmək, şərhləri aktiv/deaktiv etmək, sualı açıq/bağlı statusuna gətirmək.
- İstifadəçi: yalnız sual əlavə edir və mövcud suallara cavab (şərh) yazır. İstifadəçi sualını sonradan redaktə/silməmir (bu, admin səlahiyyətindədir).

---

## 2. Model sahələri (backend)
`forum_questions` cədvəli əsas sahələr:
- `title`, `summary`, `body`, `category`, `difficulty`
- `tags` (json array)
- `question_type`: `general | technical | discussion | poll`
- `poll_options` (json array, yalnız sorğu üçün)
- `is_pinned`, `allow_comments`, `is_open`, `status`

---

## 3. İctimai siyahı və baxış
Endpointlər:
- `GET /api/v1/forum/questions` – siyahı (filtrlər: `search, category, question_type, is_pinned, tags, per_page, page`)
- `GET /api/v1/forum/questions/{id}` – detal
- `GET /api/v1/forum/questions/{id}/answers` – cavablar

Davranış və qaydalar:
- `search` həm `title`, həm `summary`, həm də `body` üzərindən axtarır.
- `tags` JSON massividir; filter üçün ən azı bir tag uyğunluğu nəzərə alınır.
- `is_pinned=true` verilərsə yalnız pinlənmişlər qaytarıla bilər; verilməzsə hamısı.
- `question_type`: `general | technical | discussion | poll`.
- Cavablar paginator formatındadır.

```javascript
import { api } from '@/services/api'

export async function listPublicQuestions(params) {
  const { data } = await api.get('/api/v1/forum/questions', { params })
  return data
}

export async function getQuestion(id) {
  const { data } = await api.get(`/api/v1/forum/questions/${id}`)
  return data
}
```

```vue
<script setup>
import { ref, onMounted } from 'vue'
import { listPublicQuestions } from '@/services/forum'

const items = ref([])
const loading = ref(false)

async function load() {
  loading.value = true
  try {
    const res = await listPublicQuestions({ per_page: 10, page: 1, search: '' })
    items.value = res.data
  } finally { loading.value = false }
}
onMounted(load)
</script>

<template>
  <div>
    <h3>Forum Sualları</h3>
    <div v-if="loading">Yüklənir...</div>
    <div v-else>
      <article v-for="q in items" :key="q.id" class="q">
        <h4>{{ q.title }}</h4>
        <p>{{ q.summary }}</p>
        <div class="tags">
          <span v-for="t in (q.tags || [])" :key="t">#{{ t }}</span>
        </div>
      </article>
    </div>
  </div>
</template>
```

---

## 4. İstifadəçi tərəfində sual yaratmaq və şəxsi siyahı
Endpointlər:
- `GET /api/v1/my/forum/questions` – istifadəçinin yaratdığı sualların siyahısı (read-only)
- `POST /api/v1/my/forum/questions` – yeni sual yaratmaq

- Validasiya və biznes məntiqi:
  - Yaratma (`POST`):
  - `title` (required, max 255), `body` (required)
  - `summary` (optional, max 300), `category` (optional), `difficulty` (`beginner|intermediate|advanced`)
  - `tags` (optional array of strings), `question_type` (required), `poll_options` (optional array; yalnız `question_type==='poll'` üçün mənalıdır)
  - Default flags: `is_pinned=false`, `allow_comments=true`, `is_open=true`, `status='open'`
  - Siyahı (`GET /my/forum/questions`): yalnız həmin istifadəçinin sualları; paginator formatı.
  - Yeniləmə/silmə: istifadəçi üçün YOXDUR (admin hüququdur).

```javascript
export async function myQuestions(params) {
  const { data } = await api.get('/api/v1/my/forum/questions', { params })
  return data
}

export async function createMyQuestion(payload) {
  const { data } = await api.post('/api/v1/my/forum/questions', payload)
  return data
}

export async function updateMyQuestion(id, payload) {
  const { data } = await api.patch(`/api/v1/my/forum/questions/${id}`, payload)
  return data
}

export async function deleteMyQuestion(id) {
  const { data } = await api.delete(`/api/v1/my/forum/questions/${id}`)
  return data
}
```

Form nümunəsi (sizin UI maketinə uyğun):
```vue
<script setup>
import { ref } from 'vue'
import { createMyQuestion } from '@/services/forum'

const form = ref({
  title: '',
  summary: '',
  body: '',
  category: '',
  difficulty: 'beginner',
  tags: [],
  question_type: 'general',
  poll_options: []
})

async function submit() {
  await createMyQuestion(form.value)
}
</script>

<template>
  <div>
    <!-- Sual növü seçimləri: ümumi, texniki, müzakirə, sorğu -->
    <!-- Kateqoriya, çətinlik, etiketlər, parametrlər -->
    <button @click="submit">Göndər</button>
  </div>
</template>
```

---

## 5. Cavab yazmaq (istifadəçi)
Endpoint: `POST /api/v1/forum/questions/{id}/answers`

```javascript
export async function answerQuestion(questionId, body) {
  const { data } = await api.post(`/api/v1/forum/questions/${questionId}/answers`, { body })
  return data
}
```

Məntiq:
- `allow_comments=false` olan suallara cavab qəbul edilmir → 400.
- `is_open=false` və ya `status='closed'` isə cavab rədd edilir.
- Uğurda 201 və cavab obyekti qaytarılır.

---

## 6. Admin idarəetməsi
Endpointlər (admin):
- `POST /api/v1/forum/questions`
- `PATCH /api/v1/forum/questions/{id}`
- `DELETE /api/v1/forum/questions/{id}`

Tövsiyələr:
- Pinlənmiş sualları siyahıda yuxarıda göstərin (`is_pinned: true`).
- `question_type === 'poll'` olduqda `poll_options` massivindən səsvermə interfeysi qurun.
- Şərhlər bağlanıbsa (`allow_comments: false`), cavab formunu gizlədin.

Biznes qaydaları:
- Admin `status` (`open|closed`), `is_pinned`, `allow_comments`, `is_open` kimi idarəetmə sahələrini dəyişə bilir.
- Silmə əməliyyatı sualın cavabları ilə birlikdə (FK cascade) həyata keçirilir.
- Redaktədə `tags` JSON massividir; backend onu mass-assign ilə yeniləyir.

---

## 7. UX tövsiyələri
- Axtarış inputu + kateqoriya seçimi + etiketlər üçün çoxseçimli selector.
- Məzmun redaktoru (markdown dəstəyi istəyə görə).
- Cavab göndərərkən loading göstərin; uğurda forma təmizlənsin.

Tipik səhvlər və kodlar:
- 401: autentifikasiya yoxdur/bitib.
- 403: icazə yoxdur (sahib deyil və ya admin tələb olunur).
- 404: sual tapılmadı.
- 422: forma validasiyası.


