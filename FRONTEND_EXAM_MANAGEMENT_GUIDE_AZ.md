# İmtahan İdarəetmə Sistemi - Frontend Təlimatları

## 📋 **Ümumi Baxış**

Bu sənəd Aqrar Portal layihəsində imtahan idarəetmə sisteminin frontend tərəfdən necə işlədiyini izah edir. Sistem iki növ imtahan dəstəkləyir:

1. **Təlimə əsaslanan imtahanlar** - Müəyyən təlim kursu ilə bağlı
2. **Müstəqil imtahanlar** - Heç bir təlimə bağlı olmayan

---

## 🔐 **İstifadəçi Rolları və İcazələr**

### **Admin:**
- Bütün imtahanları idarə edə bilər
- Statistikaları görə bilər
- Kateqoriya yarada/redaktə edə bilər
- Bütün təlimçilərin imtahanlarını görə bilər

### **Təlimçi (Trainer):**
- Yalnız öz imtahanlarını idarə edə bilər
- Öz imtahanlarının statistikalarını görə bilər
- Müstəqil imtahan yarada bilər

### **Şagird (Student):**
- Mövcud imtahanlara qeydiyyatdan keçə bilər
- İmtahan verə bilər
- Nəticələrini görə bilər

---

## 🎨 **Admin Dashboard - İmtahan İdarəetməsi**

### **Səhifə 1: Dashboard Əsas Səhifəsi**

#### **Statistika Kartları:**
```javascript
// API çağırışı
GET /api/v1/exams/stats

// Cavab
{
  "total_exams": 127,        // Ümumi İmtahanlar
  "active_exams": 45,        // Aktiv İmtahanlar  
  "total_registrations": 1847, // Ümumi İştirakçılar
  "average_score": 76.4      // Orta Nəticə
}
```

**Frontend tərkibi:**
- 4 statistika kartı
- Artım/azalma faizləri (+8%, +12%, +15%, +3%)
- Real vaxt yenilənmə

#### **İmtahan Cədvəli:**
```javascript
// API çağırışı
GET /api/v1/exams?search=&category=&status=&page=1&per_page=15

// Axtarış
GET /api/v1/exams?search=bitki

// Kateqoriya filtri  
GET /api/v1/exams?category=Bitki%20Becerilmesi

// Status filtri
GET /api/v1/exams?status=active
```

**Cədvəl sütunları:**
- **İmtahan:** Başlıq və təlim məlumatı
- **Müəllif:** Təlimçinin adı
- **İştirakçılar:** Qeydiyyatlı şagird sayı
- **Performans:** Tamamlanma faizi (progress bar)
- **Parametrlər:** Müddət və keçid balı
- **Status:** Aktiv/Qeyri-aktiv
- **Əməliyyatlar:** Redaktə/Sil düymələri

---

### **Səhifə 2: Yeni İmtahan Yaratma (4 Addım)**

#### **Addım 1: İmtahan Məlumatları**
```javascript
// Form data API çağırışı
GET /api/v1/exams/form-data

// Cavab
{
  "categories": [
    {"id": 1, "name": "Bitki Becerilmesi"},
    {"id": 2, "name": "Torpaq Sağlamlığı"}
  ],
  "trainings": [
    {
      "id": 1,
      "title": "Bitki Becerilmesi Əsasları",
      "category": "Bitki Becerilmesi"
    }
  ],
  "supports_independent_exams": true
}
```

**Form sahələri:**
```javascript
{
  title: "İmtahan Başlığı",           // Məcburi
  description: "İmtahan Təsviri",     // İstəyə bağlı
  exam_type: "training_based",        // "training_based" və ya "independent"
  training_id: null,                  // Təlim seçimi (istəyə bağlı)
  category: "",                       // Kateqoriya (müstəqil imtahan üçün məcburi)
}
```

**Frontend məntiq:**
- Əgər `exam_type === "training_based"` → təlim seçimi göstər
- Əgər `exam_type === "independent"` → kateqoriya sahəsi göstər
- Təlim seçildikdə kateqoriya avtomatik doldurulsun

#### **Addım 2: Suallar**

**Sual növləri:**
```javascript
const questionTypes = [
  {
    type: "single_choice",
    name: "Çoxseçimli",
    icon: "✅",
    description: "4 variant, 1 düzgün cavab"
  },
  {
    type: "multiple_choice", 
    name: "Doğru/Yanlış",
    icon: "✅/❌",
    description: "Birdən çox düzgün cavab"
  },
  {
    type: "text",
    name: "Açıq Cavab", 
    icon: "📝",
    description: "Mətn cavabı"
  }
];
```

**Çətinlik səviyyələri:**
```javascript
const difficultyLevels = [
  {
    value: "easy",
    name: "Asan",
    color: "green",
    points: "2-5 bal"
  },
  {
    value: "medium", 
    name: "Orta",
    color: "yellow",
    points: "5-10 bal"
  },
  {
    value: "hard",
    name: "Çətin",
    color: "red", 
    points: "10-20 bal"
  }
];
```

**Sual formu:**
```javascript
{
  question_text: "Sual mətni",
  question_type: "single_choice",
  difficulty: "medium",
  points: 5,
  sequence: 1,
  explanation: "Sualın izahı",
  choices: [
    {
      choice_text: "Variant A",
      is_correct: true,
      points: 5,
      explanation: "Düzgün cavabın izahı"
    },
    {
      choice_text: "Variant B", 
      is_correct: false,
      points: 0
    }
  ]
}
```

#### **Addım 3: Parametrlər**
```javascript
{
  passing_score: 70,              // Keçid balı (%)
  duration_minutes: 60,           // İmtahan müddəti (dəqiqə)
  start_date: "2024-08-20",      // Başlama tarixi
  end_date: "2024-08-25",        // Bitmə tarixi
  max_attempts: 3,               // Maksimum cəhd sayı
  show_results: true,            // Nəticələri göstər
  randomize_questions: false,    // Sualları qarışdır
  auto_submit: true              // Avtomatik təqdim
}
```

#### **Addım 4: Önizləmə**
- Bütün məlumatları göstər
- Sualları və cavabları göstər
- Parametrləri göstər
- Redaktə linkləri

#### **Son Addım: Yaratma**
```javascript
// Tam imtahan yaratma API çağırışı
POST /api/v1/exams
{
  "training_id": 1,  // və ya null (müstəqil imtahan üçün)
  "category": "Bitki Becerilmesi", // müstəqil imtahan üçün
  "title": "İmtahan başlığı",
  "description": "İmtahan təsviri",
  "passing_score": 70,
  "duration_minutes": 60,
  "start_date": "2024-08-20",
  "end_date": "2024-08-25",
  "questions": [
    // Bütün suallar və cavablar
  ]
}
```

---

## 👨‍🎓 **Şagird İmtahan Prosesi**

### **Addım 1: Mövcud İmtahanları Görüntülə**
```javascript
GET /api/v1/exams
```

**Şagird görür:**
- İmtahan kartları
- Başlıq, təsvir, müddət
- Status (gələcək/aktiv/bitmiş)
- Qeydiyyat düyməsi

### **Addım 2: İmtahana Qeydiyyat**
```javascript
POST /api/v1/exams/{exam_id}/register

// Cavab
{
  "id": 1,
  "user_id": 5,
  "exam_id": 1,
  "status": "approved",
  "registration_date": "2024-08-20T09:00:00Z"
}
```

### **Addım 3: İmtahana Başla**
```javascript
POST /api/v1/exams/{exam_id}/start

// Cavab  
{
  "id": 1,
  "status": "in_progress",
  "started_at": "2024-08-20T10:00:00Z"
}
```

**Frontend:**
- Timer başlayır
- İmtahan interfeysinə keçid

### **Addım 4: İmtahan Suallarını Al**
```javascript
GET /api/v1/exams/{exam_id}/take

// Cavab
{
  "exam": {
    "id": 1,
    "title": "İmtahan başlığı",
    "duration_minutes": 60,
    "passing_score": 70
  },
  "questions": [
    {
      "id": 5,
      "question_text": "Sual mətni?",
      "question_type": "single_choice",
      "points": 4,
      "choices": [
        {
          "id": 11,
          "choice_text": "Variant A"
        },
        {
          "id": 12, 
          "choice_text": "Variant B"
        }
      ]
    }
  ],
  "time_info": {
    "time_elapsed_minutes": 2,
    "time_remaining_minutes": 58,
    "time_exceeded": false
  }
}
```

**Frontend interfeys:**
- **Timer:** "58:00 qalıb" formatında
- **Sual göstəricisi:** "1/10 sual"
- **Cavab sahələri:**
  - Tək seçim: Radio button
  - Çox seçim: Checkbox
  - Mətn: Textarea
- **Naviqasiya:** Əvvəlki/Növbəti düymələri

### **Addım 5: Cavabları Təqdim Et**
```javascript
POST /api/v1/exams/{exam_id}/submit
{
  "answers": [
    {
      "question_id": 5,
      "choice_id": 11
    },
    {
      "question_id": 6,
      "choice_ids": [15, 16, 17]
    },
    {
      "question_id": 7,
      "answer_text": "Mətn cavabı..."
    }
  ]
}
```

### **Addım 6: Nəticələri Gör**

#### **Vaxtında təqdim edildikdə:**
```json
{
  "status": "passed",           // və ya "failed"
  "score": 85,                  // Bal (%)
  "time_elapsed_minutes": 45,   // Sərf olunan vaxt
  "time_exceeded": false,       // Vaxt aşılmayıb
  "certificate": {              // Sertifikat (uğur halında)
    "certificate_number": "CERT-12345"
  }
}
```

#### **Vaxt aşıldıqdan sonra təqdim edildikdə:**
```json
{
  "status": "timeout",          // ❌ Vaxt aşılıb
  "score": 75,                  // Bal hesablanıb amma əhəmiyyəti yox
  "time_elapsed_minutes": 90,   // 60 dəqiqəni aşıb
  "time_exceeded": true,        // ❌ Vaxt aşılıb
  "certificate": null           // ❌ Sertifikat verilmir
}
```

**Frontend nəticə səhifəsi:**
- **Uğur halında:** "Təbriklər! İmtahanı uğurla keçdiniz"
- **Uğursuzluq halında:** "Təəssüf ki, imtahanı keçə bilmədiniz"
- **Vaxt aşılması halında:** "Vaxt bitdiyinə görə imtahan dayandırıldı"
- **Bal göstəricisi:** Dairəvi progress bar
- **Sertifikat yükləmə:** PDF yükləmə düyməsi

---

## ⏰ **Vaxt İdarəetməsi Sistemi**

### **İmtahan Müddəti Necə İşləyir:**

#### **1. İmtahan Parametrləri:**
```javascript
{
  duration_minutes: 60,        // İmtahan müddəti
  start_date: "2024-08-20",   // İmtahan açılır
  end_date: "2024-08-25"      // İmtahan bağlanır
}
```

#### **2. Şagird İmtahana Başlayır:**
- `started_at` qeyd olunur
- Timer başlayır
- Status `"in_progress"` olur

#### **3. İmtahan Zamanı:**
- **Qalan vaxt:** `duration_minutes - elapsed_minutes`
- **Real vaxt sayğacı:** Hər saniyə yenilənir
- **Xəbərdarlıqlar:** 10, 5, 1 dəqiqə qaldıqda

#### **4. Avtomatik Təqdim:**
```javascript
// Frontend timer məntiq
useEffect(() => {
  if (timeRemaining <= 0) {
    // Avtomatik olaraq imtahanı təqdim et
    autoSubmitExam();
    showTimeoutMessage();
  }
}, [timeRemaining]);
```

### **Vaxt Ssenarilər:**

#### **Ssenariya 1: Vaxtında Təqdim**
- ✅ Normal qiymətləndirmə
- ✅ Sertifikat alınabilir
- ✅ Status: "passed" və ya "failed"

#### **Ssenariya 2: Vaxt Aşıldıqdan Sonra Təqdim**
- ❌ Status: "timeout"
- ❌ Sertifikat verilmir
- ❌ Bal hesablanır amma qəbul edilmir

#### **Ssenariya 3: Heç Vaxt Təqdim Etmir**
- Status: "in_progress" qalır
- Admin görə bilər ki, imtahan yarımçıq qalıb

---

## 🔧 **API Endpoint-ləri**

### **Admin İmtahan İdarəetməsi:**

#### **Dashboard Statistikaları:**
```
GET /api/v1/exams/stats
```

#### **Form Məlumatları:**
```
GET /api/v1/exams/form-data
```

#### **İmtahan Siyahısı:**
```
GET /api/v1/exams?search=&category=&status=&sort_by=title&page=1
```

#### **İmtahan Yaratma:**
```
POST /api/v1/exams
{
  "title": "İmtahan başlığı",
  "training_id": 1,  // və ya null
  "category": "Kateqoriya",  // müstəqil imtahan üçün
  "questions": [...]
}
```

#### **İmtahan Redaktəsi:**
```
GET /api/v1/exams/{id}     // Məlumatları al
PUT /api/v1/exams/{id}     // Əsas məlumatları yenilə
```

#### **Sual Redaktəsi:**
```
PUT /api/v1/exams/{id}/questions/{question_id}     // Sualı yenilə
DELETE /api/v1/exams/{id}/questions/{question_id}  // Sualı sil
```

#### **İmtahan Silmə:**
```
DELETE /api/v1/exams/{id}
```

### **Kateqoriya İdarəetməsi:**

```
GET /api/v1/categories/dropdown    // Dropdown üçün
GET /api/v1/categories             // Tam siyahı
POST /api/v1/categories            // Yeni kateqoriya
PUT /api/v1/categories/{id}        // Kateqoriya yenilə
DELETE /api/v1/categories/{id}     // Kateqoriya sil
```

### **Şagird İmtahan Prosesi:**

```
POST /api/v1/exams/{id}/register   // Qeydiyyat
POST /api/v1/exams/{id}/start      // Başlat
GET /api/v1/exams/{id}/take        // Sualları al
POST /api/v1/exams/{id}/submit     // Cavabları təqdim et
```

---

## 🎯 **Frontend Komponent Strukturu**

### **Admin Dashboard Komponentləri:**

#### **1. StatisticsCards.jsx**
```javascript
// Statistika kartları
const StatisticsCards = () => {
  const [stats, setStats] = useState({});
  
  useEffect(() => {
    fetchStats(); // GET /api/v1/exams/stats
  }, []);
  
  return (
    <div className="stats-grid">
      <StatCard title="Ümumi İmtahanlar" value={stats.total_exams} />
      <StatCard title="Aktiv İmtahanlar" value={stats.active_exams} />
      <StatCard title="İştirakçılar" value={stats.total_registrations} />
      <StatCard title="Orta Nəticə" value={`${stats.average_score}%`} />
    </div>
  );
};
```

#### **2. ExamTable.jsx**
```javascript
// İmtahan cədvəli
const ExamTable = () => {
  const [exams, setExams] = useState([]);
  const [filters, setFilters] = useState({
    search: '',
    category: '',
    status: '',
    page: 1
  });
  
  useEffect(() => {
    fetchExams(); // GET /api/v1/exams with filters
  }, [filters]);
  
  return (
    <div>
      <SearchBar onSearch={handleSearch} />
      <FilterDropdowns onFilter={handleFilter} />
      <Table data={exams} onEdit={handleEdit} onDelete={handleDelete} />
      <Pagination />
    </div>
  );
};
```

#### **3. CreateExamModal.jsx**
```javascript
// 4 addımlı imtahan yaratma
const CreateExamModal = () => {
  const [currentStep, setCurrentStep] = useState(1);
  const [examData, setExamData] = useState({
    // Addım 1
    title: '',
    description: '',
    exam_type: 'training_based',
    training_id: null,
    category: '',
    
    // Addım 2
    questions: [],
    
    // Addım 3
    passing_score: 70,
    duration_minutes: 60,
    start_date: '',
    end_date: ''
  });
  
  const handleSubmit = async () => {
    // POST /api/v1/exams with complete data
    const response = await createExam(examData);
  };
  
  return (
    <Modal>
      <StepIndicator currentStep={currentStep} />
      {currentStep === 1 && <ExamInfoStep />}
      {currentStep === 2 && <QuestionsStep />}
      {currentStep === 3 && <ParametersStep />}
      {currentStep === 4 && <PreviewStep />}
    </Modal>
  );
};
```

### **Şagird İmtahan Komponentləri:**

#### **1. ExamTimer.jsx**
```javascript
// İmtahan timer-i
const ExamTimer = ({ timeInfo }) => {
  const [timeRemaining, setTimeRemaining] = useState(timeInfo.time_remaining_minutes);
  
  useEffect(() => {
    const timer = setInterval(() => {
      setTimeRemaining(prev => {
        if (prev <= 1) {
          autoSubmitExam(); // Avtomatik təqdim
          return 0;
        }
        return prev - 1;
      });
    }, 60000); // Hər dəqiqə
    
    return () => clearInterval(timer);
  }, []);
  
  return (
    <div className={`timer ${timeRemaining <= 5 ? 'warning' : ''}`}>
      <span>⏰ {formatTime(timeRemaining)} qalıb</span>
      {timeRemaining <= 10 && <div className="time-warning">Vaxt azalır!</div>}
    </div>
  );
};
```

#### **2. QuestionDisplay.jsx**
```javascript
// Sual göstərmə
const QuestionDisplay = ({ question, onAnswer }) => {
  const renderAnswerInput = () => {
    switch (question.question_type) {
      case 'single_choice':
        return (
          <div className="choices">
            {question.choices.map(choice => (
              <label key={choice.id}>
                <input 
                  type="radio" 
                  name={`question_${question.id}`}
                  value={choice.id}
                  onChange={(e) => onAnswer(question.id, 'choice_id', e.target.value)}
                />
                {choice.choice_text}
              </label>
            ))}
          </div>
        );
        
      case 'multiple_choice':
        return (
          <div className="choices">
            {question.choices.map(choice => (
              <label key={choice.id}>
                <input 
                  type="checkbox"
                  value={choice.id}
                  onChange={(e) => handleMultipleChoice(question.id, choice.id, e.target.checked)}
                />
                {choice.choice_text}
              </label>
            ))}
          </div>
        );
        
      case 'text':
        return (
          <textarea
            placeholder="Cavabınızı yazın..."
            onChange={(e) => onAnswer(question.id, 'answer_text', e.target.value)}
          />
        );
    }
  };
  
  return (
    <div className="question">
      <div className="question-header">
        <span className="difficulty">{question.difficulty}</span>
        <span className="points">{question.points} bal</span>
      </div>
      <h3>{question.question_text}</h3>
      {renderAnswerInput()}
    </div>
  );
};
```

#### **3. ExamSubmission.jsx**
```javascript
// İmtahan təqdim etmə
const handleSubmitExam = async () => {
  const answers = formatAnswers(); // Cavabları formatla
  
  try {
    const response = await fetch(`/api/v1/exams/${examId}/submit`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ answers })
    });
    
    const result = await response.json();
    
    if (result.status === 'passed') {
      showSuccessMessage("Təbriklər! İmtahanı keçdiniz!");
      showCertificate(result.certificate);
    } else if (result.status === 'failed') {
      showFailureMessage("Təəssüf ki, imtahanı keçə bilmədiniz");
    } else if (result.status === 'timeout') {
      showTimeoutMessage("Vaxt bitdiyinə görə imtahan dayandırıldı");
    }
    
    showResults(result);
  } catch (error) {
    showErrorMessage("Xəta baş verdi: " + error.message);
  }
};
```

---

## 📊 **Məlumat Formatları**

### **İmtahan Yaratma Məlumatı:**
```javascript
const examData = {
  // Əsas məlumatlar
  title: "İmtahan Başlığı",
  description: "Təsvir",
  exam_type: "training_based", // və ya "independent"
  training_id: 1,              // və ya null
  category: "Kateqoriya",      // müstəqil imtahan üçün
  
  // Parametrlər
  passing_score: 70,
  duration_minutes: 60,
  start_date: "2024-08-20",
  end_date: "2024-08-25",
  
  // Suallar
  questions: [
    {
      question_text: "Sual mətni?",
      question_type: "single_choice",
      difficulty: "medium",
      points: 5,
      sequence: 1,
      explanation: "Sualın izahı",
      choices: [
        {
          choice_text: "Variant A",
          is_correct: true,
          points: 5,
          explanation: "Düzgün cavab"
        }
      ]
    }
  ]
};
```

### **Şagird Cavab Formatı:**
```javascript
const answers = [
  {
    question_id: 5,
    choice_id: 11              // Tək seçim üçün
  },
  {
    question_id: 6,
    choice_ids: [15, 16, 17]   // Çox seçim üçün
  },
  {
    question_id: 7,
    answer_text: "Mətn cavabı"  // Mətn sualı üçün
  }
];
```

---

## 🚨 **Xəta İdarəetməsi**

### **Ümumi Xətalar:**

#### **401 - Unauthorized:**
```json
{
  "message": "Unauthorized"
}
```
**Frontend reaksiya:** Login səhifəsinə yönləndir

#### **403 - Forbidden:**
```json
{
  "message": "Forbidden"
}
```
**Frontend reaksiya:** "Bu əməliyyat üçün icazəniz yoxdur" mesajı

#### **422 - Validation Error:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "title": ["Başlıq sahəsi məcburidir"],
    "questions.0.choices": ["Seçim suallarında ən azı bir cavab olmalıdır"]
  }
}
```
**Frontend reaksiya:** Xəta mesajlarını form sahələrinin yanında göstər

#### **404 - Not Found:**
```json
{
  "message": "İmtahan tapılmadı"
}
```

### **İmtahan Xüsusi Xətaları:**

#### **İmtahan Silmə Xətası:**
```json
{
  "message": "Qeydiyyatlı iştirakçıları olan imtahan silinə bilməz",
  "registrations_count": 156
}
```

#### **Qeydiyyat Xətası:**
```json
{
  "message": "Bu imtahan üçün qeydiyyat tapılmadı"
}
```

---

## 🎉 **Nəticə**

Bu sistem tam funksional imtahan idarəetmə platformasıdır:

### **Admin üçün:**
- ✅ Tam imtahan idarəetməsi
- ✅ Statistika və hesabatlar
- ✅ Kateqoriya idarəetməsi
- ✅ Çətinlik səviyyəli suallar

### **Şagird üçün:**
- ✅ Asan qeydiyyat prosesi
- ✅ İnteraktiv imtahan interfeysi
- ✅ Real vaxt timer
- ✅ Dərhal nəticə və sertifikat

### **Texniki:**
- ✅ Yüksək performans
- ✅ Təhlükəsizlik
- ✅ Məlumat bütövlüyü
- ✅ Tam audit sistemi

**Sistem istehsala hazırdır və bütün tələbləri qarşılayır!**
