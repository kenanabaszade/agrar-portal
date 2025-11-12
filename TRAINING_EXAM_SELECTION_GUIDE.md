# Training Create Form - İmtahan Seçimi Bələdçisi

Bu sənəd training yaradılarkən admin-ə imtahan seçmək imkanını təmin etmək üçün lazımi addımları izah edir.

## Backend Hazırlığı ✅

Backend-də aşağıdakı endpoint-lər hazırdır:

### 1. İmtahanlar Siyahısı (Dropdown üçün)
**GET** `/api/v1/exams/dropdown`

**Autentifikasiya:** Admin və Trainer üçün əlçatan

**Cavab formatı:**
```json
[
  {
    "id": 1,
    "title": "İmtahan Adı",
    "training_id": 5,
    "training_title": "Training Adı",
    "category": "Bitki Becerilmesi",
    "passing_score": 70,
    "duration_minutes": 60,
    "display_text": "İmtahan Adı (Training Adı)"
  }
]
```

### 2. Training Yaradılarkən İmtahan Əlavə Etmək
**POST** `/api/v1/trainings`

Training yaradılarkən aşağıdakı field-lər göndərilə bilər:
- `has_exam`: boolean - Training-də imtahan olub-olmadığı
- `exam_id`: integer - Seçilmiş imtahanın ID-si
- `exam_required`: boolean - İmtahan məcburi olub-olmadığı
- `min_exam_score`: integer (0-100) - Minimum keçid balı

**Nümunə request:**
```json
{
  "title": {
    "az": "Yeni Training",
    "en": "New Training"
  },
  "trainer_id": 1,
  "has_exam": true,
  "exam_id": 5,
  "exam_required": true,
  "min_exam_score": 70
}
```

## Frontend İmplementasiyası

### 1. İmtahanlar Siyahısını Yükləmək

Training create form-un yüklənəndə imtahanlar siyahısını gətirin:

```javascript
// Vue.js nümunəsi
import { ref, onMounted } from 'vue'
import axios from 'axios'

const exams = ref([])
const loadingExams = ref(false)

async function loadExams() {
  loadingExams.value = true
  try {
    const response = await axios.get('/api/v1/exams/dropdown', {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    })
    exams.value = response.data
  } catch (error) {
    console.error('İmtahanlar yüklənərkən xəta:', error)
  } finally {
    loadingExams.value = false
  }
}

onMounted(() => {
  loadExams()
})
```

### 2. Form-a İmtahan Seçimi Əlavə Etmək

Training form-una aşağıdakı field-ləri əlavə edin:

```vue
<template>
  <div class="training-form">
    <!-- Digər form field-ləri -->
    
    <!-- İmtahan Seçimi Bölməsi -->
    <div class="form-section">
      <h3>İmtahan Parametrləri</h3>
      
      <!-- İmtahan var/yox checkbox -->
      <div class="form-group">
        <label>
          <input 
            type="checkbox" 
            v-model="formData.has_exam"
            @change="handleExamToggle"
          />
          Bu training üçün imtahan var
        </label>
      </div>
      
      <!-- İmtahan seçimi dropdown (yalnız has_exam true olanda görünür) -->
      <div v-if="formData.has_exam" class="form-group">
        <label for="exam_id">İmtahan Seçin</label>
        <select 
          id="exam_id"
          v-model="formData.exam_id"
          class="form-control"
          :disabled="loadingExams"
        >
          <option value="">İmtahan seçin...</option>
          <option 
            v-for="exam in exams" 
            :key="exam.id" 
            :value="exam.id"
          >
            {{ exam.display_text }}
          </option>
        </select>
        <small class="text-muted" v-if="loadingExams">
          İmtahanlar yüklənir...
        </small>
      </div>
      
      <!-- İmtahan məcburi checkbox -->
      <div v-if="formData.has_exam && formData.exam_id" class="form-group">
        <label>
          <input 
            type="checkbox" 
            v-model="formData.exam_required"
          />
          İmtahan məcburidir
        </label>
      </div>
      
      <!-- Minimum keçid balı (yalnız exam_required true olanda) -->
      <div v-if="formData.has_exam && formData.exam_id && formData.exam_required" class="form-group">
        <label for="min_exam_score">Minimum Keçid Balı (0-100)</label>
        <input 
          id="min_exam_score"
          type="number" 
          v-model.number="formData.min_exam_score"
          min="0"
          max="100"
          class="form-control"
        />
        <small class="text-muted">
          İştirakçılar bu baldan yüksək qazanmalıdırlar
        </small>
      </div>
      
      <!-- Seçilmiş imtahan məlumatları -->
      <div v-if="formData.has_exam && formData.exam_id" class="exam-info">
        <div class="info-card">
          <strong>Seçilmiş İmtahan:</strong>
          <div v-if="selectedExam">
            <p><strong>Adı:</strong> {{ selectedExam.display_text }}</p>
            <p><strong>Keçid balı:</strong> {{ selectedExam.passing_score }}%</p>
            <p><strong>Müddət:</strong> {{ selectedExam.duration_minutes }} dəqiqə</p>
            <p v-if="selectedExam.category"><strong>Kateqoriya:</strong> {{ selectedExam.category }}</p>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Submit button -->
    <button type="submit" @click="submitForm">Training Yarat</button>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const formData = ref({
  title: { az: '', en: '' },
  trainer_id: null,
  has_exam: false,
  exam_id: null,
  exam_required: false,
  min_exam_score: 70,
  // ... digər field-lər
})

const exams = ref([])
const loadingExams = ref(false)

// Seçilmiş imtahanın detallarını göstərmək üçün
const selectedExam = computed(() => {
  if (!formData.value.exam_id) return null
  return exams.value.find(e => e.id === formData.value.exam_id)
})

async function loadExams() {
  loadingExams.value = true
  try {
    const response = await axios.get('/api/v1/exams/dropdown', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      }
    })
    exams.value = response.data
  } catch (error) {
    console.error('İmtahanlar yüklənərkən xəta:', error)
    alert('İmtahanlar yüklənərkən xəta baş verdi')
  } finally {
    loadingExams.value = false
  }
}

function handleExamToggle() {
  if (!formData.value.has_exam) {
    // İmtahan seçimi ləğv edildikdə field-ləri təmizlə
    formData.value.exam_id = null
    formData.value.exam_required = false
    formData.value.min_exam_score = 70
  }
}

async function submitForm() {
  try {
    const response = await axios.post('/api/v1/trainings', formData.value, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json'
      }
    })
    
    console.log('Training uğurla yaradıldı:', response.data)
    // Redirect və ya uğur mesajı
  } catch (error) {
    console.error('Training yaradılarkən xəta:', error)
    alert(error.response?.data?.message || 'Xəta baş verdi')
  }
}

onMounted(() => {
  loadExams()
})
</script>

<style scoped>
.form-section {
  margin: 20px 0;
  padding: 20px;
  border: 1px solid #ddd;
  border-radius: 8px;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
}

.form-control {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.exam-info {
  margin-top: 20px;
}

.info-card {
  padding: 15px;
  background: #f8f9fa;
  border-radius: 4px;
  border-left: 4px solid #007bff;
}
</style>
```

### 3. React Nümunəsi

```jsx
import { useState, useEffect } from 'react'
import axios from 'axios'

function TrainingCreateForm() {
  const [formData, setFormData] = useState({
    title: { az: '', en: '' },
    trainer_id: null,
    has_exam: false,
    exam_id: null,
    exam_required: false,
    min_exam_score: 70,
  })
  
  const [exams, setExams] = useState([])
  const [loadingExams, setLoadingExams] = useState(false)
  
  useEffect(() => {
    loadExams()
  }, [])
  
  const loadExams = async () => {
    setLoadingExams(true)
    try {
      const response = await axios.get('/api/v1/exams/dropdown', {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      })
      setExams(response.data)
    } catch (error) {
      console.error('İmtahanlar yüklənərkən xəta:', error)
    } finally {
      setLoadingExams(false)
    }
  }
  
  const selectedExam = exams.find(e => e.id === formData.exam_id)
  
  const handleExamToggle = (e) => {
    const hasExam = e.target.checked
    setFormData(prev => ({
      ...prev,
      has_exam: hasExam,
      exam_id: hasExam ? prev.exam_id : null,
      exam_required: hasExam ? prev.exam_required : false,
      min_exam_score: hasExam ? prev.min_exam_score : 70,
    }))
  }
  
  const handleSubmit = async (e) => {
    e.preventDefault()
    try {
      const response = await axios.post('/api/v1/trainings', formData, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        }
      })
      console.log('Training uğurla yaradıldı:', response.data)
    } catch (error) {
      console.error('Xəta:', error)
    }
  }
  
  return (
    <form onSubmit={handleSubmit}>
      {/* Digər form field-ləri */}
      
      <div className="form-section">
        <h3>İmtahan Parametrləri</h3>
        
        <div className="form-group">
          <label>
            <input 
              type="checkbox" 
              checked={formData.has_exam}
              onChange={handleExamToggle}
            />
            Bu training üçün imtahan var
          </label>
        </div>
        
        {formData.has_exam && (
          <>
            <div className="form-group">
              <label htmlFor="exam_id">İmtahan Seçin</label>
              <select 
                id="exam_id"
                value={formData.exam_id || ''}
                onChange={(e) => setFormData(prev => ({ ...prev, exam_id: parseInt(e.target.value) || null }))}
                disabled={loadingExams}
              >
                <option value="">İmtahan seçin...</option>
                {exams.map(exam => (
                  <option key={exam.id} value={exam.id}>
                    {exam.display_text}
                  </option>
                ))}
              </select>
            </div>
            
            {formData.exam_id && (
              <>
                <div className="form-group">
                  <label>
                    <input 
                      type="checkbox" 
                      checked={formData.exam_required}
                      onChange={(e) => setFormData(prev => ({ ...prev, exam_required: e.target.checked }))}
                    />
                    İmtahan məcburidir
                  </label>
                </div>
                
                {formData.exam_required && (
                  <div className="form-group">
                    <label htmlFor="min_exam_score">Minimum Keçid Balı (0-100)</label>
                    <input 
                      id="min_exam_score"
                      type="number" 
                      value={formData.min_exam_score}
                      onChange={(e) => setFormData(prev => ({ ...prev, min_exam_score: parseInt(e.target.value) }))}
                      min="0"
                      max="100"
                    />
                  </div>
                )}
                
                {selectedExam && (
                  <div className="exam-info">
                    <strong>Seçilmiş İmtahan:</strong>
                    <p><strong>Adı:</strong> {selectedExam.display_text}</p>
                    <p><strong>Keçid balı:</strong> {selectedExam.passing_score}%</p>
                    <p><strong>Müddət:</strong> {selectedExam.duration_minutes} dəqiqə</p>
                  </div>
                )}
              </>
            )}
          </>
        )}
      </div>
      
      <button type="submit">Training Yarat</button>
    </form>
  )
}

export default TrainingCreateForm
```

## Test

1. Frontend-də training create səhifəsini açın
2. "Bu training üçün imtahan var" checkbox-ını işarələyin
3. Dropdown-dan bir imtahan seçin
4. Lazım olduqda "İmtahan məcburidir" checkbox-ını işarələyin
5. Minimum keçid balını təyin edin
6. Form-u yoxlayın və göndərin

## Qeydlər

- İmtahan seçimi yalnız admin və trainer üçün əlçatandır
- Trainer yalnız öz training-ləri ilə əlaqəli imtahanları görə bilər
- Admin bütün imtahanları görə bilər
- `has_exam` false olanda, digər exam field-ləri nəzərə alınmır
- `exam_id` göndəriləndə, backend-də mövcud olub-olmadığı yoxlanılır

