# Agrar Portal - Ətraflı Sistem Analizi və Sənədləşmə

> Bu sənəd Agrar Portal sisteminin tam təhlilini və texniki sənədləşməsini əhatə edir. Bütün komponentlər, database struktur, API endpointlər, modellər və biznes məntiq burada ətraflı şəkildə təsvir olunub.

---

## 📋 MÜNDƏRİCAT

1. [Sistem Ümumi Görünüşü](#1-sistem-ümumi-görünüşü)
2. [Texnologiya Stack](#2-texnologiya-stack)
3. [Database Struktur (28 Cədvəl)](#3-database-struktur-28-cədvəl)
4. [Model Relationships](#4-model-relationships)
5. [API Endpoints](#5-api-endpoints)
6. [Authentication & Authorization](#6-authentication--authorization)
7. [Biznes Məntiq və Workflow](#7-biznes-məntiq-və-workflow)
8. [Xüsusi Xüsusiyyətlər](#8-xüsusi-xüsusiyyətlər)
9. [Middleware və Validation](#9-middleware-və-validation)
10. [Services və Helper Classes](#10-services-və-helper-classes)
11. [Email Notifications](#11-email-notifications)
12. [File Upload Sistemi](#12-file-upload-sistemi)
13. [Sistem Arxitekturası](#13-sistem-arxitekturası)

---

## 1. SİSTEM ÜMUMİ GÖRÜNÜŞÜ

**Agrar Portal** - Kənd təsərrüfatı sahəsi üçün tam funksional LMS (Learning Management System) platformasıdır.

### Əsas Funksionallıqlar:
- ✅ **Təlim İdarəetmə Sistemi** (Training Management)
- ✅ **İmtahan Sistemi** (Exam System)
- ✅ **Sertifikat Generasiyası** (Certificate Generation)
- ✅ **Forum Platforması** (Q&A, Polls)
- ✅ **Vebinar/Meeting Sistemi** (Google Meet Integration)
- ✅ **Təhsil Kontenti** (Articles, Instructions, Announcements)
- ✅ **Staj Proqramları** (Internship Programs)
- ✅ **İstifadəçi İdarəetmə** (User Management)
- ✅ **Dashboard və Statistika** (Analytics)

---

## 2. TEXNOLOGIYA STACK

### Backend:
- **Framework**: Laravel 12
- **PHP Version**: 8.2+
- **Database**: PostgreSQL 14+
- **Authentication**: Laravel Sanctum (Token-based API)
- **PDF Generation**: barryvdh/laravel-dompdf
- **Google Integration**: google/apiclient (^2.18)
  - Google Calendar API
  - Google Meet API

### Frontend:
- **Framework**: Vue.js 3
- **Build Tool**: Vite 7
- **CSS Framework**: TailwindCSS 4
- **HTTP Client**: Axios

### Development Tools:
- **Package Manager**: Composer, npm
- **Testing**: PHPUnit 11
- **Code Quality**: Laravel Pint

---

## 3. DATABASE STRUKTUR (28 CƏDVƏL)

### 3.1 İstifadəçi və Autentifikasiya Modulları

#### `users` - İstifadəçilər
```sql
Fields:
- id (primary key)
- first_name, last_name, username, father_name
- email (unique), phone
- password_hash
- user_type (enum: farmer, trainer, admin, agronom, veterinary, government, entrepreneur, researcher)
- region, birth_date, gender, how_did_you_hear
- profile_photo
- is_active (boolean)
- two_factor_enabled (boolean)
- otp_code, otp_expires_at (OTP verification)
- email_verified, email_verified_at
- google_access_token, google_refresh_token, google_token_expires_at
- last_login_at
- timestamps
```

**Xüsusiyyətlər:**
- OTP ilə email verification (10 dəqiqə müddət)
- 2FA (Two Factor Authentication) support
- Google OAuth token management
- Multiple user types support
- Profile photo management

#### `email_change_requests` - Email Dəyişikliyi
```sql
Fields:
- id, user_id (foreign key)
- new_email, otp_code, otp_expires_at
- status (enum: pending, verified, expired, cancelled)
- timestamps
```

#### `roles` və `permissions` - RBAC
```sql
roles:
- id, name (unique), description

permissions:
- id, name (unique), description

user_roles (pivot):
- user_id, role_id (unique constraint)

role_permissions (pivot):
- role_id, permission_id (unique constraint)
```

**Default Roles:**
- `admin` - Tam sistem idarəetməsi
- `trainer` - Təlim və imtahan yaratma
- `farmer` - Təlimə qeydiyyat, imtahan vermə

**Default Permissions:**
- `manage_users`
- `manage_trainings`
- `take_exams`
- `post_forum`

---

### 3.2 Təlim Sistemi (Training System)

#### `trainings` - Təlimlər
```sql
Fields:
- id (primary key)
- title, description, category
- trainer_id (foreign key → users)
- type (enum: online, offline, video)
- start_date, end_date, start_time, end_time, timezone
- is_online (boolean)
- status (enum: draft, published, cancelled, completed)
- difficulty (enum: beginner, intermediate, advanced)
- media_files (JSON) - Array of media objects
  {
    type: "banner|intro_video|general",
    path: "storage path",
    original_name: "...",
    mime_type: "...",
    size: number,
    uploaded_at: "ISO datetime",
    url: "public URL"
  }
- online_details (JSON) - {participant_size, google_meet_link}
- offline_details (JSON) - {participant_size, address, coordinates}
- has_certificate (boolean)
- require_email_verification (boolean)
- has_exam (boolean)
- exam_id (foreign key → exams, nullable)
- exam_required (boolean)
- min_exam_score (integer)
- google_meet_link, google_event_id, meeting_id
- is_recurring (boolean)
- recurrence_frequency, recurrence_end_date
- timestamps
```

**Xüsusiyyətlər:**
- Üç növ təlim: Online, Offline, Video
- Google Meet inteqrasiyası (automatik link yaradılması)
- Recurring meetings support
- Rich media files (banner, intro video, general files)
- Exam linking (optional)
- Certificate generation support

#### `training_modules` - Təlim Modulları
```sql
Fields:
- id, training_id (foreign key)
- title, sequence (integer)
- timestamps
- Unique constraint: (training_id, sequence)
```

#### `training_lessons` - Dərslər
```sql
Fields:
- id, module_id (foreign key)
- title, content (text)
- video_url, pdf_url
- duration_minutes (integer)
- lesson_media (JSON) - Rich media array
- sequence (integer)
- timestamps
- Unique constraint: (module_id, sequence)
```

#### `training_registrations` - Təlimə Qeydiyyat
```sql
Fields:
- id
- user_id (foreign key → users)
- training_id (foreign key → trainings)
- registration_date (datetime)
- status (enum: pending, approved, rejected, completed, cancelled)
- certificate_id (foreign key → certificates, nullable)
- timestamps
- Unique constraint: (user_id, training_id)
- Index: status
```

#### `user_training_progress` - Tərəqqi Tracking
```sql
Fields:
- id
- user_id, training_id, module_id, lesson_id (all foreign keys)
- status (enum: not_started, in_progress, completed)
- last_accessed (datetime)
- completed_at (datetime)
- notes (text) - Personal lesson notes
- timestamps
- Unique constraint: (user_id, lesson_id)
```

---

### 3.3 İmtahan Sistemi (Exam System)

#### `exams` - İmtahanlar
```sql
Fields:
- id, training_id (foreign key, nullable - müstəqil imtahanlar)
- title, description, sertifikat_description
- category (nullable)
- passing_score (integer)
- duration_minutes (integer, default: 60)
- start_date, end_date (nullable)
- status (enum: draft, published, closed)
- rules, instructions (text)
- hashtags (JSON array)
- time_warning_minutes (integer)
- max_attempts (integer)
- randomize_questions (boolean)
- randomize_choices (boolean)
- show_results_immediately (boolean)
- show_correct_answers (boolean)
- show_explanations (boolean)
- allow_tab_switching (boolean)
- track_tab_changes (boolean)
- exam_question_count (integer)
- is_required (boolean)
- auto_submit (boolean)
- timestamps
```

**Xüsusiyyətlər:**
- Training-ə bağlı və ya müstəqil imtahanlar
- Question randomization
- Tab switching tracking
- Auto və manual grading support
- Multiple attempts support

#### `exam_questions` - Suallar
```sql
Fields:
- id, exam_id (foreign key)
- question_text (text)
- question_type (enum: single_choice, multiple_choice, text, true_false)
- difficulty (enum: easy, medium, hard)
- question_media (JSON) - Rich media array
- points (integer)
- sequence (integer)
- is_required (boolean)
- explanation (text)
- metadata (JSON)
- timestamps
- Unique constraint: (exam_id, sequence)
```

#### `exam_choices` - Cavab Variantları
```sql
Fields:
- id, question_id (foreign key)
- choice_text
- is_correct (boolean)
- choice_media (JSON) - Rich media array
- points (integer)
- explanation (text)
- metadata (JSON)
- timestamps
```

#### `exam_registrations` - İmtahana Qeydiyyat
```sql
Fields:
- id
- user_id (foreign key → users)
- exam_id (foreign key → exams)
- registration_date (datetime)
- status (enum: pending, approved, rejected, in_progress, completed, passed, failed, cancelled)
- score (integer, nullable)
- started_at, finished_at (datetime, nullable)
- certificate_id (foreign key → certificates, nullable)
- attempt_number (integer)
- needs_manual_grading (boolean)
- auto_graded_score (integer, nullable)
- selected_question_ids (JSON array) - Random seçilmiş suallar
- total_questions (integer)
- timestamps
- Unique constraint: (user_id, exam_id)
- Index: status
```

#### `exam_user_answers` - İstifadəçi Cavabları
```sql
Fields:
- id
- registration_id (foreign key → exam_registrations)
- question_id (foreign key → exam_questions)
- choice_id (foreign key → exam_choices, nullable) - Single choice
- choice_ids (JSON array, nullable) - Multiple choice
- answer_text (text, nullable) - Text questions
- is_correct (boolean)
- needs_manual_grading (boolean)
- answered_at (datetime)
- timestamps
- Unique constraint: (registration_id, question_id)
```

---

### 3.4 Sertifikat Sistemi

#### `certificates` - Sertifikatlar
```sql
Fields:
- id
- user_id (foreign key → users)
- related_training_id (foreign key → trainings, nullable)
- related_exam_id (foreign key → exams, nullable)
- certificate_number (unique string)
- issue_date, expiry_date (date, nullable)
- issuer_name, issuer_logo_url
- digital_signature (text)
- qr_code (text)
- pdf_url, pdf_path (string, nullable)
- status (enum: active, revoked, expired)
- timestamps
- Index: user_id
```

**Xüsusiyyətlər:**
- PDF generation (dompdf)
- QR code verification
- Public verification endpoints
- Digital signatures

---

### 3.5 Forum Sistemi

#### `forum_questions` - Forum Sualları
```sql
Fields:
- id, user_id (foreign key → users)
- title, summary, body (text)
- status (enum: open, closed)
- question_type (enum: general, technical, discussion, poll)
- poll_options (JSON array, nullable)
- tags (JSON array, nullable)
- category (string, nullable)
- difficulty (enum: beginner, intermediate, advanced, nullable)
- is_pinned (boolean)
- allow_comments (boolean)
- is_open (boolean)
- is_public (boolean)
- views (integer, default: 0)
- likes_count (integer, default: 0)
- timestamps
- Index: user_id
```

#### `forum_answers` - Cavablar
```sql
Fields:
- id
- question_id (foreign key → forum_questions)
- user_id (foreign key → users)
- body (text)
- is_accepted (boolean, default: false)
- timestamps
- Index: question_id
```

#### `forum_question_likes` və `forum_answer_likes` - Like Sistemi
```sql
forum_question_likes:
- id, question_id, user_id
- timestamps
- Unique constraint: (question_id, user_id)

forum_answer_likes:
- id, answer_id, user_id
- timestamps
- Unique constraint: (answer_id, user_id)
```

#### `forum_question_views` - Baxış Tracking
```sql
Fields:
- id, question_id, user_id (nullable - anonymous views)
- ip_address
- viewed_at (datetime)
- timestamps
```

**Xüsusiyyətlər:**
- Unique viewers tracking
- Poll voting system
- Like/Unlike functionality
- Pin questions
- Admin və User management

#### `forum_poll_votes` - Poll Səsverməsi
```sql
Fields:
- id, question_id, user_id
- selected_option (string)
- timestamps
- Unique constraint: (question_id, user_id)
```

---

### 3.6 Vebinar/Meeting Sistemi

#### `meetings` - Google Meet Vebinarlar
```sql
Fields:
- id
- title, description
- google_event_id, google_meet_link, meeting_id
- meeting_password
- start_time, end_time (datetime)
- timezone (string)
- max_attendees (integer)
- is_recurring (boolean)
- recurrence_rules (JSON)
- status (enum: scheduled, live, ended, cancelled)
- created_by, trainer_id (foreign keys → users)
- attendees (JSON array)
- google_metadata (JSON)
- category (string, nullable)
- image_url
- has_materials (boolean)
- documents (JSON array)
- level (string, nullable)
- language (string, nullable)
- hashtags (JSON array)
- is_permanent (boolean)
- has_certificate (boolean)
- timestamps
```

#### `meeting_registrations` - İştirakçı Qeydiyyatı
```sql
Fields:
- id
- meeting_id (foreign key → meetings)
- user_id (foreign key → users)
- registration_date (datetime)
- status (enum: registered, attended, absent, cancelled)
- timestamps
- Unique constraint: (meeting_id, user_id)
```

**Xüsusiyyətlər:**
- Google Calendar API integration
- Automatic Google Meet link generation
- Recurring meetings
- Attendance tracking

---

### 3.7 Təhsil Kontenti (Educational Content)

#### `educational_contents` - Maqalələr, Təlimatlar, Elanlar
```sql
Fields:
- id
- type (enum: meqale, telimat, elan)
- seo (JSON) - SEO metadata
- created_by (foreign key → users)
- image_path
- title, short_description, body_html (longText)
- description (text)
- sequence (integer)
- hashtags (string)
- category (string)
- send_to_our_user (boolean)
- media_files (JSON) - Array of media objects
- documents (JSON) - Array of document objects
- announcement_title, announcement_body (text, nullable)
- likes_count (integer, default: 0)
- views_count (integer, default: 0)
- timestamps
- Indexes: (type, created_by), category
```

#### `educational_content_likes` - Like Tracking
```sql
Fields:
- id, educational_content_id, user_id
- timestamps
- Unique constraint: (educational_content_id, user_id)
```

#### `saved_educational_contents` - Saxlanılmış Kontent
```sql
Fields:
- id, educational_content_id, user_id
- timestamps
- Unique constraint: (educational_content_id, user_id)
```

#### `educational_content_views` - Baxış Tracking
```sql
Fields:
- id, educational_content_id, user_id (nullable)
- ip_address
- viewed_at (datetime)
- timestamps
```

---

### 3.8 Staj Proqramları (Internship Programs)

#### `internship_programs` - Staj Proqramları
```sql
Fields:
- id
- trainer_id (foreign key → users, nullable)
- trainer_mail (string, nullable)
- title, description
- image_url
- is_featured (boolean)
- registration_status (enum: open, closed, full)
- category (string)
- duration_weeks (integer)
- start_date, end_date, last_register_date (date)
- location (string)
- current_enrollment (integer, default: 0)
- max_capacity (integer)
- instructor_name, instructor_title, instructor_initials
- instructor_photo_url, instructor_description
- instructor_rating (decimal 2,1)
- details_link
- cv_requirements (text)
- is_active (boolean)
- timestamps
```

#### `internship_applications` - Müraciətlər
```sql
Fields:
- id
- internship_program_id (foreign key → internship_programs)
- user_id (foreign key → users)
- cv_file_path
- cover_letter (text, nullable)
- status (enum: pending, accepted, rejected)
- applied_at (datetime)
- timestamps
- Index: (internship_program_id, user_id)
```

**Xüsusiyyətlər:**
- CV upload support
- Multiple applications allowed (no unique constraint)
- Admin status management

#### `program_modules`, `program_requirements`, `program_goals`
```sql
program_modules:
- id, internship_program_id, title, description, order

program_requirements:
- id, internship_program_id, requirement_text, order

program_goals:
- id, internship_program_id, goal_text, order
```

---

### 3.9 Digər Modullar

#### `categories` - Kateqoriyalar
```sql
Fields:
- id, name (unique), description
- is_active (boolean)
- sort_order (integer)
- timestamps
```

#### `faqs` - Tez-tez Verilən Suallar
```sql
Fields:
- id, question (text), answer (text)
- category (string)
- created_by (foreign key → users)
- helpful_count (integer, default: 0)
- is_active (boolean)
- timestamps
```

#### `about_blocks` - Haqqımızda Səhifəsi Blokları
```sql
Fields:
- id, title, content (text), image_url
- order (integer)
- is_active (boolean)
- timestamps
```

#### `service_packages` - Xidmət Paketləri
```sql
Fields:
- id, name, description (text)
- price (decimal 10,2, nullable)
- price_type (enum: free, monthly, annual)
- price_label (string, nullable)
- is_recommended (boolean)
- features (JSON array)
- sort_order (integer)
- is_active (boolean)
- timestamps
```

#### `notifications` - Sistem Bildirişləri
```sql
Fields:
- id, user_id (foreign key → users)
- type (enum: system, training, exam, payment, forum)
- title, message (text)
- is_read (boolean)
- sent_at (datetime)
- created_at
- Index: (user_id, is_read)
```

#### `payments` - Ödənişlər
```sql
Fields:
- id, user_id (foreign key → users)
- amount (decimal 12,2)
- currency (string, default: 'USD')
- payment_date (datetime)
- payment_method (enum: card, bank_transfer, cash, mobile_money)
- status (enum: pending, paid, failed, refunded)
- related_exam_registration_id (foreign key → exam_registrations, nullable)
- timestamps
- Index: (user_id, status)
```

#### `audit_logs` - Audit Trail
```sql
Fields:
- id, user_id (foreign key → users, nullable)
- action (string)
- entity (string)
- entity_id (unsignedBigInteger)
- details (JSON, nullable)
- created_at
- Index: (entity, entity_id)
```

#### `temp_lesson_files` - Temporary Files
```sql
Fields:
- id, file_path, file_name, file_size
- mime_type, uploaded_by (foreign key → users)
- expires_at (datetime)
- timestamps
```

---

## 4. MODEL RELATIONSHIPS

### User Model Relationships:
```php
belongsToMany: Role
hasMany: 
  - TrainingRegistration
  - UserTrainingProgress
  - EmailChangeRequest
  - InternshipProgram (as trainer)
  - Training (as trainer)
  - ForumQuestion
  - ForumAnswer
  - Meeting (as creator, trainer)
  - EducationalContent (as creator)
```

### Training Model Relationships:
```php
belongsTo: 
  - User (trainer)
  - Category
  - Exam (optional)

hasMany: 
  - TrainingModule
  - TrainingRegistration
```

### Exam Model Relationships:
```php
belongsTo: 
  - Training (optional - independent exams)

hasMany: 
  - ExamQuestion
  - ExamRegistration
```

### ForumQuestion Model Relationships:
```php
belongsTo: User

hasMany: 
  - ForumAnswer
  - ForumPollVote
  - ForumQuestionView
  - ForumQuestionLike
```

### Meeting Model Relationships:
```php
belongsTo: 
  - User (creator, trainer)
  - Category

hasMany: MeetingRegistration
```

### Certificate Model Relationships:
```php
belongsTo: 
  - User
  - Training (related_training_id)
  - Exam (related_exam_id)
```

---

## 5. API ENDPOINTS

### 5.1 Authentication Endpoints (`/api/v1/auth/*`)

#### Public Endpoints:
- `POST /auth/register` - Qeydiyyat (OTP göndərilir)
- `POST /auth/verify-otp` - OTP təsdiqləmə (token qaytarır)
- `POST /auth/resend-otp` - OTP yenidən göndərmə
- `POST /auth/login` - Giriş (2FA enabled isə OTP tələb olunur)
- `POST /auth/verify-login-otp` - Login OTP təsdiqləmə
- `POST /auth/resend-login-otp` - Login OTP yenidən göndərmə
- `POST /auth/forgot-password` - Şifrə unutma
- `POST /auth/verify-password-reset-otp` - Password reset OTP
- `POST /auth/reset-password` - Şifrə sıfırlama
- `POST /auth/resend-password-reset-otp` - Password reset OTP yenidən göndərmə

#### Protected Endpoints (auth:sanctum):
- `POST /auth/logout` - Çıxış
- `GET /auth/2fa/status` - 2FA status
- `POST /auth/2fa/enable` - 2FA aktivləşdirmə
- `POST /auth/2fa/verify-enable` - 2FA aktivləşdirmə təsdiqi
- `POST /auth/2fa/disable` - 2FA deaktivləşdirmə

#### Development Endpoints:
- `POST /auth/generate-test-token` - Test token yaratma
- `POST /auth/verify-otp-dev` - Dev OTP verification
- `POST /auth/verify-login-otp-dev` - Dev Login OTP verification

---

### 5.2 Training Management (`/api/v1/trainings/*`)

#### Public Endpoints:
- `GET /trainings/public` - Public trainings (optional auth)
- `GET /trainings/online` - Online trainings
- `GET /trainings/offline` - Offline trainings
- `GET /trainings/offline/{training}` - Offline training details (optional auth)
- `GET /trainings/{training}/detailed` - Detailed training info (optional auth)

#### Protected Endpoints (auth:sanctum):
- `GET /trainings` - List trainings (admin, trainer)
- `POST /trainings` - Create training (admin, trainer)
- `GET /trainings/{training}` - Show training
- `PUT /trainings/{training}` - Update training (admin, trainer)
- `DELETE /trainings/{training}` - Delete training (admin, trainer)
- `GET /trainings/dropdown` - Dropdown list (admin, trainer)
- `GET /trainings/future` - Future trainings
- `GET /trainings/ongoing` - Ongoing trainings
- `GET /trainings/all` - All trainings (no pagination)

#### Training Media:
- `GET /trainings/{training}/media` - Get media files
- `POST /trainings/{training}/upload-media` - Upload media (admin, trainer)
- `DELETE /trainings/{training}/media/{mediaId}` - Delete media (admin, trainer)

#### Training Modules:
- `GET /trainings/{training}/modules` - List modules
- `POST /trainings/{training}/modules` - Create module (admin, trainer)
- `GET /trainings/{training}/modules/{module}` - Show module
- `PUT /trainings/{training}/modules/{module}` - Update module (admin, trainer)
- `DELETE /trainings/{training}/modules/{module}` - Delete module (admin, trainer)

#### Training Lessons:
- `GET /modules/{module}/lessons` - List lessons
- `POST /modules/{module}/lessons` - Create lesson (admin, trainer)
- `GET /modules/{module}/lessons/{lesson}` - Show lesson
- `PUT /modules/{module}/lessons/{lesson}` - Update lesson (admin, trainer)
- `DELETE /modules/{module}/lessons/{lesson}` - Delete lesson (admin, trainer)
- `POST /modules/{module}/reorder-lessons` - Reorder lessons (admin, trainer)
- `POST /lessons/upload-temp-media` - Upload temp media (admin, trainer)
- `DELETE /lessons/delete-temp-media` - Delete temp media (admin, trainer)
- `POST /lessons/{lesson}/upload-media` - Upload lesson media (admin, trainer)
- `DELETE /lessons/{lesson}/remove-media` - Remove lesson media (admin, trainer)

#### Lesson Progress (Students):
- `GET /lessons/{lesson}/progress` - Get progress
- `POST /lessons/{lesson}/complete` - Mark lesson completed
- `POST /lessons/{lesson}/notes` - Add/Update notes
- `GET /lessons/{lesson}/notes` - Get notes
- `PUT /lessons/{lesson}/notes` - Update notes
- `DELETE /lessons/{lesson}/notes` - Delete notes

#### Training Registration:
- `POST /trainings/{training}/register` - Register for training
- `DELETE /trainings/{training}/cancel-registration` - Cancel registration
- `GET /my-training-registrations` - My registrations
- `POST /trainings/{training}/complete` - Mark training completed
- `GET /trainings/{training}/completion-status` - Get completion status

---

### 5.3 Exam Management (`/api/v1/exams/*`)

#### Public Endpoints:
- `GET /exams/{exam}/public` - Public exam info

#### Protected Endpoints (auth:sanctum):
- `GET /exams` - List exams (admin, trainer)
- `POST /exams` - Create exam (admin, trainer)
- `GET /exams/{exam}` - Show exam
- `PUT /exams/{exam}` - Update exam (admin, trainer)
- `DELETE /exams/{exam}` - Delete exam (admin, trainer)
- `GET /exams/stats` - Exam statistics (admin)
- `GET /exams/comprehensive-stats` - Comprehensive stats (admin)
- `GET /exams/detailed-list` - Detailed exam list (admin)
- `GET /exams/form-data` - Form data for create/edit (admin, trainer)

#### Exam Questions:
- `GET /exams/{exam}/questions` - Get exam with questions (admin, trainer)
- `PUT /exams/{exam}/questions/{question}` - Update question (admin, trainer)
- `DELETE /exams/{exam}/questions/{question}` - Delete question (admin, trainer)
- `POST /exams/{exam}/upload-question-media` - Upload question media (admin, trainer)

#### Exam Status:
- `PUT /exams/{exam}/status` - Update exam status (admin, trainer)

#### Student Exam Taking:
- `POST /exams/{exam}/register` - Register for exam
- `GET /exams/{exam}/take` - Get exam for taking
- `POST /exams/{exam}/start` - Start exam
- `POST /exams/{exam}/submit` - Submit exam
- `GET /exams/{exam}/result` - Get exam result

#### Admin Exam Grading:
- `GET /admin/exams/pending-reviews` - Get pending reviews (admin)
- `GET /admin/exams/{registrationId}/for-grading` - Get exam for grading (admin)
- `POST /admin/exams/{registrationId}/grade-text-questions` - Grade text questions (admin)

---

### 5.4 Forum (`/api/v1/forum/*`)

#### Public Endpoints:
- `GET /forum/questions` - List questions (with filters)
- `GET /forum/questions/{question}` - Show question
- `GET /forum/questions/{question}/answers` - Get answers

#### Protected Endpoints (auth:sanctum):
- `POST /forum/questions/{question}/answers` - Answer question
- `POST /forum/questions/{question}/vote` - Vote on poll
- `POST /forum/questions/{question}/like` - Like question
- `POST /forum/questions/{question}/unlike` - Unlike question
- `POST /forum/answers/{answer}/like` - Like answer
- `POST /forum/answers/{answer}/unlike` - Unlike answer

#### User Forum Management:
- `GET /my/forum/questions` - My questions
- `POST /my/forum/questions` - Create my question
- `DELETE /my/forum/questions/{question}` - Delete my question
- `DELETE /my/forum/answers/{answer}` - Delete my answer

#### Admin Forum Management:
- `POST /forum/questions` - Create question (admin)
- `PATCH /forum/questions/{question}` - Update question (admin)
- `DELETE /forum/questions/{question}` - Delete question (admin)
- `DELETE /forum/answers/{answer}` - Delete answer (admin)

#### Forum Stats:
- `GET /forum/stats` - Forum statistics
- `GET /forum/cards` - Forum cards data

---

### 5.5 Meetings (`/api/v1/meetings/*`)

#### Protected Endpoints (auth:sanctum):
- `GET /meetings` - List meetings (admin, trainer)
- `POST /meetings` - Create meeting (admin, trainer)
- `GET /meetings/{meeting}` - Show meeting
- `PUT /meetings/{meeting}` - Update meeting (admin, trainer)
- `DELETE /meetings/{meeting}` - Delete meeting (admin, trainer)
- `GET /meetings/cards` - Meeting cards
- `GET /meetings/{meeting}/attendees` - Get attendees (admin, trainer)
- `POST /meetings/{meeting}/register` - Register for meeting
- `DELETE /meetings/{meeting}/cancel-registration` - Cancel registration
- `GET /my-meetings` - My meeting registrations

---

### 5.6 Educational Content (`/api/v1/education/*`)

#### Public Endpoints:
- `GET /education/{id}` - Show content
- `GET /education/telimats` - List telimats (instructions)
- `GET /education/articles` - List articles
- `GET /telimats` - Alias for telimats
- `GET /meqaleler` - Alias for articles

#### Protected Endpoints (auth:sanctum):
- `GET /education/stats` - Education stats
- `POST /education` - Create content (admin, trainer)
- `GET /education` - List content (admin, trainer)
- `GET /education/{id}` - Show content
- `PUT /education/{id}` - Update content (admin, trainer)
- `DELETE /education/{id}` - Delete content (admin, trainer)
- `POST /education/{id}/like` - Like content
- `POST /education/{id}/unlike` - Unlike content
- `POST /education/{id}/save` - Save content
- `POST /education/{id}/unsave` - Unsave content
- `GET /my-saved-articles` - My saved articles

---

### 5.7 Internship Programs (`/api/v1/internship-programs/*`)

#### Public Endpoints (optional auth):
- `GET /internship-programs` - List programs
- `GET /internship-programs/{internshipProgram}` - Show program
- `GET /internship-programs/featured` - Featured programs
- `GET /internship-programs/categories` - Categories
- `GET /internship-programs/trainers` - Trainers list

#### Protected Endpoints (auth:sanctum):
- `POST /internship-programs` - Create program (admin, trainer)
- `PUT /internship-programs/{internshipProgram}` - Update program (admin, trainer)
- `DELETE /internship-programs/{internshipProgram}` - Delete program (admin, trainer)

#### Application Endpoints:
- `POST /internship-programs/{internshipProgram}/apply` - Apply for program
- `GET /my-internship-applications` - My applications
- `GET /internship-applications/{application}` - Show application
- `DELETE /internship-applications/{application}` - Delete application
- `GET /internship-applications/{application}/download-cv` - Download CV

#### Admin Endpoints:
- `GET /internship-programs/{internshipProgram}/applications` - Get applications (admin)
- `GET /internship-programs/{internshipProgram}/enrolled-users` - Get enrolled users (admin)
- `GET /internship-programs/{internshipProgram}/stats` - Get stats (admin)
- `GET /admin/internship-applications` - All applications (admin)
- `PUT /admin/internship-applications/{application}/status` - Update application status (admin)

---

### 5.8 Profile Management (`/api/v1/profile/*`)

#### Protected Endpoints (auth:sanctum):
- `GET /profile` - Get profile
- `PATCH /profile` - Update profile
- `POST /profile/change-password` - Change password
- `POST /profile/request-email-change` - Request email change
- `POST /profile/verify-email-change` - Verify email change
- `POST /profile/resend-email-change-otp` - Resend email change OTP
- `POST /profile/cancel-email-change` - Cancel email change
- `POST /profile/upload-photo` - Upload profile photo
- `DELETE /profile/delete-photo` - Delete profile photo

---

### 5.9 Dashboard & Statistics (`/api/v1/*`)

#### Protected Endpoints (auth:sanctum):
- `GET /dashboard` - Dashboard data
- `GET /user-statistics` - User statistics
- `GET /training-stats` - Training statistics
- `GET /webinar-stats` - Webinar statistics
- `GET /webinar-analytics` - Webinar analytics

---

### 5.10 Certificates (`/api/v1/certificates/*`)

#### Public Endpoints:
- `GET /certificates/{certificate}/data` - Get certificate data
- `GET /certificates/{certificateNumber}/verify` - Verify certificate
- `GET /certificates/verify/{signature}` - Verify by signature
- `GET /certificates/verify-page/{signature}` - Verification page
- `GET /certificates/download/{signature}` - Download PDF

#### Protected Endpoints (auth:sanctum):
- `GET /certificates` - List certificates
- `GET /certificates/{certificate}` - Show certificate
- `GET /my/certificates` - My certificates
- `POST /certificates/{certificate}/upload-pdf` - Upload PDF
- `POST /certificates/generate-pdf` - Generate PDF certificate

---

### 5.11 Google OAuth (`/api/v1/google/*`)

#### Protected Endpoints (auth:sanctum):
- `GET /google/auth-url` - Get OAuth URL
- `GET /google/check-access` - Check access status
- `POST /google/revoke-access` - Revoke access
- `GET /google/oauth2-code` - Get OAuth2 code

#### Public Endpoint:
- `GET /google/callback` - OAuth callback

---

### 5.12 Categories (`/api/v1/categories/*`)

#### Protected Endpoints (auth:sanctum):
- `GET /categories` - List categories
- `POST /categories` - Create category (admin)
- `GET /categories/{category}` - Show category
- `PUT /categories/{category}` - Update category (admin)
- `DELETE /categories/{category}` - Delete category (admin)
- `GET /categories/dropdown` - Dropdown list (admin, trainer)

---

### 5.13 FAQs (`/api/v1/faqs/*`)

#### Protected Endpoints (auth:sanctum):
- `GET /faqs` - List FAQs
- `POST /faqs` - Create FAQ (admin)
- `GET /faqs/{faq}` - Show FAQ
- `PUT /faqs/{faq}` - Update FAQ (admin)
- `DELETE /faqs/{faq}` - Delete FAQ (admin)
- `GET /faqs/categories` - FAQ categories
- `GET /faqs/stats` - FAQ statistics (admin)
- `POST /faqs/{faq}/helpful` - Mark FAQ as helpful

---

### 5.14 Service Packages (`/api/v1/service-packages/*`)

#### Public Endpoints:
- `GET /service-packages` - List packages
- `GET /service-packages/{servicePackage}` - Show package

#### Protected Endpoints (auth:sanctum, admin):
- `POST /service-packages` - Create package (admin)
- `PUT /service-packages/{servicePackage}` - Update package (admin)
- `DELETE /service-packages/{servicePackage}` - Delete package (admin)

---

### 5.15 About Page (`/api/v1/about/*`)

#### Public Endpoint:
- `GET /about` - Get about page content

#### Protected Endpoint (auth:sanctum, admin):
- `POST /about/blocks` - Create about block (admin)

---

### 5.16 Content Aggregation (`/api/v1/content/*`)

#### Public Endpoint:
- `GET /content/all` - Get all content (trainings, webinars, internship programs, articles)

---

### 5.17 Users Management (`/api/v1/users/*`)

#### Protected Endpoints (auth:sanctum, admin):
- `GET /users` - List users (admin)
- `POST /users` - Create user (admin)
- `GET /users/{user}` - Show user (admin)
- `PATCH /users/{user}` - Update user (admin)
- `DELETE /users/{user}` - Delete user (admin)
- `GET /users/stats` - User statistics (admin)
- `GET /users/simple` - Simple users list
- `GET /trainers` - Trainers list
- `GET /categories` - Categories list (users endpoint)
- `POST /users/{user}/toggle-2fa` - Toggle 2FA (admin)

---

### 5.18 Notifications (`/api/v1/notifications/*`)

#### Protected Endpoints (auth:sanctum):
- `GET /notifications` - List notifications
- `POST /notifications/{notification}/read` - Mark as read

---

### 5.19 Payments (`/api/v1/payments/*`)

#### Protected Endpoints (auth:sanctum):
- `GET /payments` - List payments
- `POST /payments` - Create payment
- `POST /payments/webhook` - Payment webhook (no auth)

---

### 5.20 Progress Tracking (`/api/v1/progress/*`)

#### Protected Endpoints (auth:sanctum):
- `GET /progress` - List progress
- `POST /progress` - Create progress
- `GET /progress/{progress}` - Show progress
- `PUT /progress/{progress}` - Update progress
- `DELETE /progress/{progress}` - Delete progress

---

### 5.21 Upload (`/api/v1/upload`)

#### Protected Endpoint (auth:sanctum):
- `POST /upload` - Upload file

---

## 6. AUTHENTICATION & AUTHORIZATION

### 6.1 Authentication Flow

#### Registration Flow:
```
1. User submits registration form (POST /auth/register)
   → Email, password, user info
2. System generates 6-digit OTP
   → OTP expires in 10 minutes
   → OTP sent via email
3. User verifies OTP (POST /auth/verify-otp)
   → OTP matched → Email verified
   → Sanctum token generated
   → User can now login
```

#### Login Flow (2FA Enabled):
```
1. User submits login form (POST /auth/login)
   → Email + Password
2. If 2FA enabled:
   → OTP sent to email
   → User enters OTP (POST /auth/verify-login-otp)
   → Token generated
3. If 2FA disabled:
   → Token generated immediately
```

#### Password Reset Flow:
```
1. User requests password reset (POST /auth/forgot-password)
   → OTP sent to email
2. User verifies OTP (POST /auth/verify-password-reset-otp)
   → OTP verified
3. User sets new password (POST /auth/reset-password)
   → Password updated
```

### 6.2 Authorization System

#### Middleware:
1. **auth:sanctum** - Token authentication required
2. **role:admin,trainer** - Role-based access control
3. **optional.auth** - Optional authentication (token varsa user data qaytarır)

#### User Types:
- **admin** - Full system access
- **trainer** - Create/manage trainings, exams, meetings
- **farmer** - Register for trainings, take exams, forum participation
- **agronom** - Agricultural expert
- **veterinary** - Veterinary expert
- **government** - Government official
- **entrepreneur** - Business person
- **researcher** - Research person

#### Role Hierarchy:
```
Admin > Trainer > Farmer/Others
```

---

## 7. BİZNES MƏNTIQ VƏ WORKFLOW

### 7.1 Training Lifecycle

```
1. ADMIN/TRAINER creates training
   → Basic info (title, description, category)
   → Training type (online/offline/video)
   → Dates and times
   → Media files upload

2. Add Modules
   → Create modules with sequence
   → Modules organized in order

3. Add Lessons
   → Create lessons within modules
   → Upload lesson media (video, PDF, images)
   → Set lesson duration

4. Google Meet Integration (if online)
   → User connects Google account
   → System creates Google Calendar event
   → Google Meet link automatically generated

5. Users Register
   → User sees public training listing
   → Registers for training
   → Status: pending → approved

6. Users Complete Lessons
   → Mark lessons as completed
   → Progress tracked in user_training_progress
   → Notes can be added to lessons

7. Training Completion
   → All lessons completed
   → Status updated to completed
   → Certificate generated (if enabled)
```

### 7.2 Exam Lifecycle

```
1. ADMIN/TRAINER creates exam
   → Basic info (title, description, category)
   → Exam parameters (duration, passing score)
   → Exam settings (randomization, tab tracking, etc.)
   → Link to training (optional - can be independent)

2. Add Questions
   → Create questions (single/multiple choice, text, true/false)
   → Add answer choices (for choice questions)
   → Set difficulty level
   → Upload question media
   → Set points and explanations

3. Student Registration
   → Student registers for exam
   → Status: pending → approved

4. Student Takes Exam
   → Exam starts (timer begins)
   → Questions randomized (if enabled)
   → Selected questions stored in registration
   → Student answers questions
   → Tab switching tracked (if enabled)

5. Exam Submission
   → Auto-grading for choice questions
   → Manual grading for text questions
   → Score calculated
   → Status: passed/failed

6. Certificate Generation
   → If passed and certificate enabled
   → Certificate automatically generated
   → PDF created
   → QR code generated
```

### 7.3 Certificate Generation Process

```
1. Trigger Points:
   → Training completion (if has_certificate = true)
   → Exam passing (if exam has certificate)

2. Certificate Creation:
   → Unique certificate number generated
   → User, training/exam linked
   → Issue date set
   → Status: active

3. PDF Generation:
   → Using dompdf library
   → Certificate template used
   → User info, training/exam info populated
   → QR code embedded
   → Digital signature added

4. Storage:
   → PDF saved to storage
   → pdf_path stored in database
   → Public URL generated

5. Verification:
   → Public verification endpoint
   → QR code scanning
   → Certificate number lookup
```

### 7.4 Google Meet Integration Flow

```
1. User Connects Google Account
   → GET /google/auth-url
   → User redirected to Google OAuth
   → Authorization granted
   → Callback receives code
   → Access token stored in users table

2. Training/Meeting Creation
   → Training created with online type
   → Google Meet enabled
   → Meeting times provided
   → GoogleCalendarService called

3. Google Calendar Event Creation
   → Event created via Google Calendar API
   → Conference data added (Google Meet)
   → Meet link extracted
   → Event ID stored

4. Recurring Meetings
   → If is_recurring = true
   → Recurrence rules set
   → Series of meetings created
   → All linked to same training
```

### 7.5 Forum Workflow

```
1. Admin/User Creates Question
   → Admin can create any question
   → Users can create their own questions
   → Question types: general, technical, discussion, poll
   → Tags and categories assigned

2. Users View and Answer
   → Public question listing
   → Filter by category, type, tags
   → View question details
   → Answer questions (if allow_comments = true)

3. Interaction
   → Like/Unlike questions and answers
   → Poll voting (one vote per user)
   → Views tracked (unique viewers)

4. Admin Management
   → Pin important questions
   → Close/open questions
   → Delete questions/answers
   → Moderate content
```

### 7.6 Internship Program Workflow

```
1. Admin/Trainer Creates Program
   → Basic info (title, description, category)
   → Dates and location
   → Capacity settings
   → Instructor info (manual or trainer link)
   → Modules, requirements, goals added

2. Users Browse Programs
   → Public listing (optional auth)
   → Filter by category, featured
   → View program details

3. User Application
   → User applies for program
   → CV upload required
   → Cover letter optional
   → Status: pending

4. Admin Review
   → Admin views applications
   → Updates status: accepted/rejected
   → Notification sent to user

5. Enrollment Tracking
   → current_enrollment incremented
   → Status changes to "full" if capacity reached
```

---

## 8. XÜSUSİ XÜSUSİYYƏTLƏR

### 8.1 Rich Media Support

**Training Media:**
- Banner images
- Intro videos
- General media files (images, videos, documents)
- JSON structure with metadata

**Lesson Media:**
- Video files
- PDF documents
- Images
- Rich content support

**Exam Question/Choice Media:**
- Images in questions
- Audio/video in questions
- Images in answer choices
- Enhanced visual learning

### 8.2 Progress Tracking

- **Lesson Completion**: Track which lessons user completed
- **Training Progress**: Calculate percentage of completion
- **Learning Hours**: Track total learning time
- **Notes System**: Personal notes for each lesson
- **Last Accessed**: Track when user last accessed content

### 8.3 Notification System

**Email Notifications:**
- OTP codes
- Training created/updated
- Exam results
- Meeting reminders
- Internship application status

**In-App Notifications:**
- System notifications
- Training notifications
- Exam notifications
- Forum notifications
- Payment notifications

### 8.4 Statistics & Analytics

**Dashboard Statistics:**
- Total trainings
- Active farmers
- Certificates issued
- New users
- Growth percentages

**User Statistics:**
- Completed courses
- Ongoing courses
- Certificates earned
- Total learning hours

**Training Analytics:**
- Popular trainings
- Completion rates
- User progress

**Exam Analytics:**
- Total exams
- Active exams
- Total participants
- Average scores
- Pass/fail rates

**Webinar Analytics:**
- Meeting attendance
- Registration rates
- Popular meetings

### 8.5 Search & Filtering

**Training Filters:**
- By type (online/offline/video)
- By category
- By status
- By date range
- Search by title/description

**Forum Filters:**
- By category
- By question type
- By tags
- By difficulty
- Search in title/body

**Educational Content Filters:**
- By type (article/instruction/announcement)
- By category
- By creator

### 8.6 Like & Save System

**Forum:**
- Like questions
- Like answers
- Unlike functionality
- Like counts tracked

**Educational Content:**
- Like articles/instructions
- Save for later
- Unlike/Unsave
- Like counts tracked

### 8.7 Audit Logging

**Complete Change Tracking:**
- All user actions logged
- Entity-based logging
- JSON details stored
- Timestamp tracking
- User attribution

---

## 9. MIDDLEWARE VƏ VALIDATION

### 9.1 Custom Middleware

#### `EnsureUserHasRole`
```php
Purpose: Role-based access control
Checks:
  - User authenticated
  - User has required role (user_type or attached roles)
Response: 403 if unauthorized
```

#### `OptionalAuth`
```php
Purpose: Optional authentication for public endpoints
Behavior:
  - If token present → attach user to request
  - If no token → continue without user
Use Case: Public listings that show user-specific data if authenticated
```

#### `FormatPagination`
```php
Purpose: Standardize pagination format
Action: Formats pagination response consistently
```

### 9.2 Validation

#### Form Requests:
- Custom validation classes
- Request-specific rules
- Custom error messages

#### Validation Rules:
- File upload validation (size, mime types)
- Email validation
- Unique constraints
- Enum validation
- Date validation
- JSON validation

---

## 10. SERVICES VƏ HELPER CLASSES

### 10.1 GoogleCalendarService

**Purpose**: Google Calendar and Meet integration

**Methods:**
- `createMeeting(array $meetingData)` - Create Google Calendar event with Meet
- `updateMeeting(string $eventId, array $meetingData)` - Update event
- `deleteMeeting(string $eventId)` - Delete event
- `setAccessToken(string $token)` - Set OAuth token
- `validateAccessToken()` - Check if token is valid
- `refreshAccessToken()` - Refresh expired token

**Features:**
- Automatic Google Meet link generation
- Recurring meetings support
- Timezone handling
- Attendee management

### 10.2 Email Notification Classes

**Mail Classes:**
- `OtpNotification` - OTP codes
- `TrainingCreatedNotification` - New training created
- `TrainingNotification` - Training updates
- `TrainingRegistrationNotification` - Registration confirmations
- `TrainingReminderNotification` - Training reminders
- `ExamPassedMail` - Exam passed
- `ExamFailedMail` - Exam failed
- `ExamPendingReviewMail` - Exam needs manual grading
- `MeetingCreatedNotification` - Meeting created
- `InternshipApplicationConfirmation` - Application received
- `InternshipApplicationNotification` - Application status update
- `InternshipProgramNotification` - Program updates

---

## 11. EMAIL NOTIFICATIONS

### 11.1 OTP Notifications

**Triggers:**
- User registration
- Email verification
- Login (if 2FA enabled)
- Password reset
- Email change request

**Content:**
- 6-digit OTP code
- Expiration time (10 minutes)
- Security instructions

### 11.2 Training Notifications

**Types:**
- Training created
- Training updated
- Registration confirmed
- Training starting soon
- Training reminder

### 11.3 Exam Notifications

**Types:**
- Exam passed
- Exam failed
- Exam pending review (manual grading needed)
- Exam results available

### 11.4 Meeting Notifications

**Types:**
- Meeting created
- Meeting updated
- Registration confirmed
- Meeting starting soon

### 11.5 Internship Notifications

**Types:**
- Application received
- Application accepted
- Application rejected
- Program updates

---

## 12. FILE UPLOAD SISTEMİ

### 12.1 Storage Structure

```
storage/app/
├── public/
│   ├── profile_photos/        - User profile photos
│   ├── training_media/        - Training banners, videos
│   ├── lesson_media/          - Lesson files
│   ├── exam_media/            - Exam question/choice media
│   ├── certificates/          - Certificate PDFs
│   ├── internship_cvs/        - Internship CV files
│   └── temp_files/            - Temporary uploads
└── google-credentials.json    - Google service account (if used)
```

### 12.2 Upload Types

**Training Media:**
- Banner images (replace existing)
- Intro videos (replace existing)
- General media files (multiple allowed)

**Lesson Media:**
- Video files
- PDF documents
- Images
- Temp files during creation

**Profile Photos:**
- Single photo per user
- Automatic resizing (if implemented)
- Default placeholder if none

**Certificate PDFs:**
- Auto-generated or manually uploaded
- Stored with unique names

**Internship CVs:**
- PDF or DOC files
- Stored per application

### 12.3 File Validation

- **Size Limits**: Configurable per file type
- **Mime Types**: Validated against allowed types
- **Security**: File names sanitized
- **Storage**: Public or private based on content type

---

## 13. SİSTEM ARXİTEKTURASI

### 13.1 Folder Structure

```
app/
├── Console/
│   └── Commands/
│       └── SendTrainingReminders.php
├── Http/
│   ├── Controllers/              (24 controllers)
│   │   ├── AboutPageController.php
│   │   ├── AdminExamController.php
│   │   ├── AuthController.php
│   │   ├── CategoryController.php
│   │   ├── CertificateController.php
│   │   ├── ContentController.php
│   │   ├── DashboardController.php
│   │   ├── EducationalContentController.php
│   │   ├── ExamController.php
│   │   ├── FaqController.php
│   │   ├── ForumController.php
│   │   ├── GoogleAuthController.php
│   │   ├── InternshipApplicationController.php
│   │   ├── InternshipProgramController.php
│   │   ├── MeetingController.php
│   │   ├── NotificationController.php
│   │   ├── PaymentController.php
│   │   ├── ProfileController.php
│   │   ├── ProgressController.php
│   │   ├── RegistrationController.php
│   │   ├── ServicePackageController.php
│   │   ├── TrainingController.php
│   │   ├── TrainingLessonController.php
│   │   ├── TrainingModuleController.php
│   │   ├── TrainingStatsController.php
│   │   ├── UploadController.php
│   │   ├── UsersController.php
│   │   └── WebinarStatsController.php
│   ├── Middleware/
│   │   ├── EnsureUserHasRole.php
│   │   ├── FormatPagination.php
│   │   └── OptionalAuth.php
│   └── Requests/
│       └── FaqRequest.php
├── Mail/                          (11 mail classes)
├── Models/                        (34 Eloquent models)
├── Notifications/
│   ├── OtpNotification.php
│   └── UserCreatedNotification.php
├── Providers/
│   └── AppServiceProvider.php
└── Services/
    └── GoogleCalendarService.php

database/
├── factories/
│   └── UserFactory.php
├── migrations/                    (84 migration files)
└── seeders/
    ├── AboutBlocksSeeder.php
    ├── AboutPageBlocksSeeder.php
    └── DatabaseSeeder.php

routes/
├── api.php                        (Main API routes)
├── console.php                    (Artisan commands)
└── web.php                        (Web routes)

config/
├── app.php
├── database.php
├── sanctum.php
└── ...

resources/
├── views/                         (Blade templates if any)
└── ...

public/
└── storage -> ../storage/app/public  (Symlink)

storage/
├── app/
│   ├── public/                   (Public files)
│   └── ...
└── logs/                         (Application logs)
```

### 13.2 API Design Patterns

**RESTful Design:**
- Resource-based URLs
- HTTP methods for actions
- Consistent response formats
- Proper status codes

**Response Format:**
```json
{
  "data": {...},
  "message": "...",
  "meta": {
    "pagination": {...}
  }
}
```

**Error Format:**
```json
{
  "message": "Error message",
  "errors": {
    "field": ["Error details"]
  }
}
```

### 13.3 Database Design Patterns

**Foreign Keys:**
- Cascade on delete where appropriate
- Nullable for optional relationships
- Indexed for performance

**JSON Fields:**
- Media files arrays
- Metadata objects
- Configuration objects

**Enum Fields:**
- Status fields
- Type fields
- Category fields

**Timestamps:**
- created_at, updated_at on all tables
- Additional date fields as needed

---

## 🎯 NƏTICƏ

Bu sistem tam funksional bir **Learning Management System (LMS)** platformasıdır və aşağıdakı əsas funksionallıqları təmin edir:

✅ **Təlim İdarəetmə** - Online, Offline, Video təlimlər  
✅ **İmtahan Sistemi** - Çoxnöv imtahanlar, auto və manual grading  
✅ **Sertifikat Generasiyası** - PDF, QR code, verification  
✅ **Forum Platforması** - Q&A, Polls, Discussions  
✅ **Vebinar/Meeting** - Google Meet inteqrasiyası  
✅ **Təhsil Kontenti** - Maqalələr, Təlimatlar, Elanlar  
✅ **Staj Proqramları** - CV upload, application management  
✅ **İstifadəçi İdarəetmə** - RBAC, 2FA, OTP verification  
✅ **Analytics** - Dashboard, statistics, reporting  

Sistem Laravel 12, PostgreSQL, Vue.js 3 texnologiyaları ilə qurulub və production-ready vəziyyətdədir.

---

**Sənəd Versiyası**: 1.0  
**Tarix**: 2025  
**Müəllif**: Sistem Analizi

