<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route Model Binding
Route::model('lesson', \App\Models\TrainingLesson::class);
Route::model('module', \App\Models\TrainingModule::class);

// Custom route model binding for nested resources (only when module parameter exists)
Route::bind('lesson', function ($value, $route) {
    // If module parameter exists, use nested binding
    if ($route->parameter('module')) {
        $module = $route->parameter('module');
        $moduleId = is_object($module) ? $module->id : $module;
        return \App\Models\TrainingLesson::where('id', $value)
            ->where('module_id', $moduleId)
            ->firstOrFail();
    }
    
    // Otherwise, use simple binding
    return \App\Models\TrainingLesson::findOrFail($value);
});

Route::prefix('v1')->middleware('set.locale')->group(function () {
// Public Certificate Routes (no auth required)
Route::get('certificates/{certificate}/data', [\App\Http\Controllers\CertificateController::class, 'getCertificateData']);
Route::get('certificates/{certificateNumber}/verify', [\App\Http\Controllers\CertificateController::class, 'verify']);
    
    // Auth
    Route::post('auth/register', [\App\Http\Controllers\AuthController::class, 'register']);
    Route::post('auth/verify-otp', [\App\Http\Controllers\AuthController::class, 'verifyOtp']);
    Route::post('auth/resend-otp', [\App\Http\Controllers\AuthController::class, 'resendOtp']);
    Route::post('auth/login', [\App\Http\Controllers\AuthController::class, 'login']);
    Route::post('auth/verify-login-otp', [\App\Http\Controllers\AuthController::class, 'verifyLoginOtp']);
    Route::post('auth/resend-login-otp', [\App\Http\Controllers\AuthController::class, 'resendLoginOtp']);
    
    // Password Reset (Public routes)
    Route::post('auth/forgot-password', [\App\Http\Controllers\AuthController::class, 'forgotPassword']);
    Route::post('auth/verify-password-reset-otp', [\App\Http\Controllers\AuthController::class, 'verifyPasswordResetOtp']);
    Route::post('auth/reset-password', [\App\Http\Controllers\AuthController::class, 'resetPassword']);
    Route::post('auth/resend-password-reset-otp', [\App\Http\Controllers\AuthController::class, 'resendPasswordResetOtp']);
    
    // Development/Testing endpoints (only available in non-production environments)
    Route::post('auth/generate-test-token', [\App\Http\Controllers\AuthController::class, 'generateTestToken']);
    Route::post('auth/verify-otp-dev', [\App\Http\Controllers\AuthController::class, 'verifyOtpDev']);
    Route::post('auth/verify-login-otp-dev', [\App\Http\Controllers\AuthController::class, 'verifyLoginOtpDev']);

    // Global Search (public endpoint)
    Route::get('search/global', [\App\Http\Controllers\SearchController::class, 'globalSearch']);

    // Public endpoints (no authentication required)
    Route::get('trainings/public', [\App\Http\Controllers\TrainingController::class, 'public'])
        ->middleware('optional.auth'); // Optional auth - token varsa user məlumatlarını qaytarır
    Route::get('trainings/online', [\App\Http\Controllers\TrainingController::class, 'online']); // Online telimlerin siyahısı
    Route::get('trainings/offline', [\App\Http\Controllers\TrainingController::class, 'offline']); // Offline telimlerin siyahısı
    Route::get('trainings/offline/{training}', [\App\Http\Controllers\TrainingController::class, 'offlineDetail'])
        ->middleware('optional.auth'); // Offline training detalları - token varsa user məlumatlarını qaytarır
    
    // Trainers endpoints (public access)
    Route::get('trainers', [\App\Http\Controllers\TrainerController::class, 'index']);
    Route::get('trainers/{id}', [\App\Http\Controllers\TrainerController::class, 'show'])
        ->where('id', '[0-9]+');
    
    // Telimats endpoint (public access)
    Route::get('telimats', [\App\Http\Controllers\EducationalContentController::class, 'telimats']);
    Route::get('education/telimats', [\App\Http\Controllers\EducationalContentController::class, 'telimats']);
    
    // Meqaleler endpoint (public access)
    Route::get('meqaleler', [\App\Http\Controllers\EducationalContentController::class, 'articles']);
    Route::get('education/articles', [\App\Http\Controllers\EducationalContentController::class, 'articles']);
    
    // Get all content (trainings, webinars, internship programs, articles) - Public access
    Route::get('content/all', [\App\Http\Controllers\ContentController::class, 'getAllContent']);
    
    // Service Packages - Public read access
    Route::get('service-packages', [\App\Http\Controllers\ServicePackageController::class, 'index']);
    Route::get('service-packages/{servicePackage}', [\App\Http\Controllers\ServicePackageController::class, 'show']);
    
    // Single educational content detail (public access)
    Route::get('education/{id}', [\App\Http\Controllers\EducationalContentController::class, 'show'])
        ->whereNumber('id')
        ->name('education.show');
    
    // About Page (Haqqımızda) - Public access (optional auth)
    Route::get('about', [\App\Http\Controllers\AboutPageController::class, 'index']);
    
    // Legal Documents - Public access (active versions only)
    Route::get('legal/privacy-policy', [\App\Http\Controllers\LegalDocumentController::class, 'getActivePrivacyPolicy']);
    Route::get('legal/terms-of-service', [\App\Http\Controllers\LegalDocumentController::class, 'getActiveTermsOfService']);
    
    // Upload endpoint (for authenticated users)
    Route::post('upload', [\App\Http\Controllers\UploadController::class, 'upload'])
        ->middleware('auth:sanctum');
    
    // Optional authentication endpoints (works with or without token)
    Route::get('trainings/{training}/detailed', [\App\Http\Controllers\TrainingController::class, 'detailed'])
        ->middleware('optional.auth'); // Ətraflı training məlumatları - token varsa user məlumatlarını qaytarır

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Broadcasting authentication for real-time notifications
        Route::post('broadcasting/auth', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Support\Facades\Broadcast::auth($request);
        });
        
        Route::post('auth/logout', [\App\Http\Controllers\AuthController::class, 'logout']);
        
        // Contact Form - Requires authentication
        Route::post('contact', [\App\Http\Controllers\ContactController::class, 'store']);
        
        // Dashboard
        Route::get('dashboard', [\App\Http\Controllers\DashboardController::class, 'index']);
        Route::get('user-statistics', [\App\Http\Controllers\DashboardController::class, 'userStatistics']);
        Route::get('training-stats', [\App\Http\Controllers\TrainingStatsController::class, 'index']);
        
        // Admin Reports (admin only)
        Route::prefix('admin/reports')->middleware('role:admin')->group(function () {
            Route::get('overview', [\App\Http\Controllers\ReportsController::class, 'overview']);
            Route::get('users', [\App\Http\Controllers\ReportsController::class, 'users']);
            Route::get('trainings', [\App\Http\Controllers\ReportsController::class, 'trainings']);
            Route::get('exams', [\App\Http\Controllers\ReportsController::class, 'exams']);
            Route::get('certificates', [\App\Http\Controllers\ReportsController::class, 'certificates']);
            Route::get('meetings', [\App\Http\Controllers\ReportsController::class, 'meetings']);
            Route::get('forum', [\App\Http\Controllers\ReportsController::class, 'forum']);
            Route::get('trainers', [\App\Http\Controllers\ReportsController::class, 'trainers']);
        });
        
        // 2FA Management (for authenticated users)
        Route::get('auth/2fa/status', [\App\Http\Controllers\AuthController::class, 'getTwoFactorStatus']);
        Route::post('auth/2fa/enable', [\App\Http\Controllers\AuthController::class, 'enableTwoFactor']);
        Route::post('auth/2fa/verify-enable', [\App\Http\Controllers\AuthController::class, 'verifyTwoFactorActivation']);
        Route::post('auth/2fa/disable', [\App\Http\Controllers\AuthController::class, 'disableTwoFactor']);

        // Training Management
        Route::get('trainings/dropdown', [\App\Http\Controllers\TrainingController::class, 'dropdown'])->middleware('role:admin,trainer');
        Route::get('trainings/future', [\App\Http\Controllers\TrainingController::class, 'future']); // Hər kəs istifadə edə bilər
        Route::get('trainings/ongoing', [\App\Http\Controllers\TrainingController::class, 'ongoing']); // Davam edən kurslar - hər kəs istifadə edə bilər
        Route::get('trainings/all', [\App\Http\Controllers\TrainingController::class, 'getAll']); // Bütün training-lər (pagination-sız)
        Route::apiResource('trainings', \App\Http\Controllers\TrainingController::class)->middleware('role:admin,trainer');
        
        // Training Completion (for students)
        Route::post('trainings/{training}/complete', [\App\Http\Controllers\TrainingController::class, 'markTrainingCompleted']);
        Route::get('trainings/{training}/completion-status', [\App\Http\Controllers\TrainingController::class, 'getTrainingCompletionStatus']);

        // Training Ratings
        Route::post('trainings/{training}/rating', [\App\Http\Controllers\TrainingRatingController::class, 'store']);
        Route::get('trainings/{training}/rating', [\App\Http\Controllers\TrainingRatingController::class, 'show']);
        Route::delete('trainings/{training}/rating', [\App\Http\Controllers\TrainingRatingController::class, 'destroy']);
        
        // Training Media Management (separate endpoints for advanced file operations)
        Route::post('trainings/{training}/upload-media', [\App\Http\Controllers\TrainingController::class, 'uploadMedia'])->middleware('role:admin,trainer');
        Route::delete('trainings/{training}/media/{mediaId}', [\App\Http\Controllers\TrainingController::class, 'removeMedia'])->middleware('role:admin,trainer');
        Route::get('trainings/{training}/media', [\App\Http\Controllers\TrainingController::class, 'getMedia'])->middleware('role:admin,trainer');
        
        // Training Module Management (admin,trainer only)
        Route::apiResource('trainings.modules', \App\Http\Controllers\TrainingModuleController::class)->middleware('role:admin,trainer');
        
        // Training media files
        Route::get('trainings/{training}/media', [\App\Http\Controllers\TrainingController::class, 'getMediaFiles']);
        Route::post('trainings/{training}/media', [\App\Http\Controllers\TrainingController::class, 'uploadMediaFiles'])->middleware('role:admin,trainer');
        Route::delete('trainings/{training}/media', [\App\Http\Controllers\TrainingController::class, 'deleteMediaFiles'])->middleware('role:admin,trainer');
        
        // Training Lesson Management (admin,trainer only)
        Route::apiResource('modules.lessons', \App\Http\Controllers\TrainingLessonController::class)->middleware('role:admin,trainer');
        Route::post('lessons/upload-temp-media', [\App\Http\Controllers\TrainingLessonController::class, 'uploadTempMedia'])->middleware('role:admin,trainer');
        Route::delete('lessons/delete-temp-media', [\App\Http\Controllers\TrainingLessonController::class, 'deleteTempMedia'])->middleware('role:admin,trainer');
        Route::post('lessons/{lesson}/upload-media', [\App\Http\Controllers\TrainingLessonController::class, 'uploadMedia'])->middleware('role:admin,trainer');
        Route::delete('lessons/{lesson}/remove-media', [\App\Http\Controllers\TrainingLessonController::class, 'removeMedia'])->middleware('role:admin,trainer');
        Route::post('modules/{module}/reorder-lessons', [\App\Http\Controllers\TrainingLessonController::class, 'reorder'])->middleware('role:admin,trainer');
        
        // Lesson Progress (for students)
        Route::get('lessons/{lesson}/progress', [\App\Http\Controllers\TrainingLessonController::class, 'getProgress']);
        Route::post('lessons/{lesson}/complete', [\App\Http\Controllers\TrainingLessonController::class, 'markCompleted']);

        // Categories CRUD (admin only)
        Route::get('categories/dropdown', [\App\Http\Controllers\CategoryController::class, 'dropdown'])->middleware('role:admin,trainer');
        Route::apiResource('categories', \App\Http\Controllers\CategoryController::class)->middleware('role:admin');

        // FAQ Management (admin only)
        Route::get('faqs/categories', [\App\Http\Controllers\FaqController::class, 'categories']);
        Route::get('faqs/stats', [\App\Http\Controllers\FaqController::class, 'stats'])->middleware('role:admin');
        Route::post('faqs/{faq}/helpful', [\App\Http\Controllers\FaqController::class, 'markHelpful']);
        Route::apiResource('faqs', \App\Http\Controllers\FaqController::class)->middleware('role:admin');

        // About Page Management (admin only)
        Route::post('about/blocks', [\App\Http\Controllers\AboutPageController::class, 'store'])->middleware('role:admin');

        // Exams CRUD (admin only for management, admin/trainer for questions)
        Route::get('exams/stats', [\App\Http\Controllers\ExamController::class, 'getStats'])->middleware('role:admin');
        Route::get('exams/comprehensive-stats', [\App\Http\Controllers\ExamController::class, 'getComprehensiveStats'])->middleware('role:admin');
        Route::get('exams/detailed-list', [\App\Http\Controllers\ExamController::class, 'getDetailedExamList'])->middleware('role:admin');
        Route::get('exams/form-data', [\App\Http\Controllers\ExamController::class, 'getFormData'])->middleware('role:admin,trainer');
        Route::get('exams/dropdown', [\App\Http\Controllers\ExamController::class, 'dropdown'])->middleware('role:admin,trainer');
        Route::apiResource('exams', \App\Http\Controllers\ExamController::class)->middleware('role:admin,trainer');
        
        // Public exam endpoints
        Route::get('exams/{exam}/public', [\App\Http\Controllers\ExamController::class, 'showPublic']);
        
        // Exam taking (for students)
        Route::post('exams/{exam}/start', [\App\Http\Controllers\ExamController::class, 'start']);
        Route::post('exams/{exam}/start-attempt', [\App\Http\Controllers\ExamController::class, 'startAttempt']);
        Route::post('exams/{exam}/submit', [\App\Http\Controllers\ExamController::class, 'submit']);
        Route::get('exams/{exam}/result', [\App\Http\Controllers\ExamController::class, 'getUserExamResult']);
        
        // Exam Question Management (admin,trainer only) - For editing existing exams only
        Route::put('exams/{exam}/questions/{question}', [\App\Http\Controllers\ExamController::class, 'updateQuestion'])->middleware('role:admin,trainer');
        Route::delete('exams/{exam}/questions/{question}', [\App\Http\Controllers\ExamController::class, 'deleteQuestion'])->middleware('role:admin,trainer');
        Route::get('exams/{exam}/questions', [\App\Http\Controllers\ExamController::class, 'getExamWithQuestions'])->middleware('role:admin,trainer');
        
        // Exam Taking (for students)
        Route::get('exams/{exam}/take', [\App\Http\Controllers\ExamController::class, 'getExamForTaking']);
        Route::post('exams/{exam}/upload-question-media', [\App\Http\Controllers\ExamController::class, 'uploadQuestionMedia'])->middleware('role:admin,trainer');
        
        // Exam Status Management
        Route::put('exams/{exam}/status', [\App\Http\Controllers\ExamController::class, 'updateStatus'])->middleware('role:admin,trainer');

        // Certificates
        Route::get('certificates', [\App\Http\Controllers\CertificateController::class, 'index']);
        Route::get('certificates/{certificate}', [\App\Http\Controllers\CertificateController::class, 'show']);
        Route::get('certificates/{certificate}/download', [\App\Http\Controllers\CertificateController::class, 'download']);
        Route::get('certificates/{certificate}/preview', [\App\Http\Controllers\CertificateController::class, 'preview']);
        Route::get('certificates/{certificate}/share', [\App\Http\Controllers\CertificateController::class, 'share']);
        Route::get('certificates/{certificate}/qr-code', [\App\Http\Controllers\CertificateController::class, 'qrCode']);
        Route::get('my/certificates', [\App\Http\Controllers\CertificateController::class, 'myCertificates']);
        Route::get('my/results', [\App\Http\Controllers\ProgressController::class, 'myResults']);
        Route::post('certificates/{certificate}/upload-pdf', [\App\Http\Controllers\CertificateController::class, 'uploadPdf']);
        Route::post('certificates/{certificate}/upload-photo', [\App\Http\Controllers\CertificateController::class, 'uploadPhoto']);
        Route::post('certificates/generate-pdf', [\App\Http\Controllers\CertificateController::class, 'generatePdfCertificate']);

        // Forum (admin manages questions; users can view and answer)
        Route::get('forum/questions', [\App\Http\Controllers\ForumController::class, 'listQuestions']);
        Route::get('forum/questions/{question}', [\App\Http\Controllers\ForumController::class, 'showQuestion']);
        Route::get('forum/questions/{question}/answers', [\App\Http\Controllers\ForumController::class, 'getAnswers']);
        
        // User-side commenting (answers)
        Route::post('forum/questions/{question}/answers', [\App\Http\Controllers\ForumController::class, 'answerQuestion']);

        // Poll voting
        Route::post('forum/questions/{question}/vote', [\App\Http\Controllers\ForumController::class, 'vote']);

        // Admin management of forum questions
        Route::post('forum/questions', [\App\Http\Controllers\ForumController::class, 'postQuestion'])->middleware('role:admin');
        Route::patch('forum/questions/{question}', [\App\Http\Controllers\ForumController::class, 'updateQuestion'])->middleware('role:admin');
        Route::delete('forum/questions/{question}', [\App\Http\Controllers\ForumController::class, 'destroyQuestion'])->middleware('role:admin');

        // User-side forum (users: list their questions, create question, write answers, delete own questions)
        Route::get('my/forum/questions', [\App\Http\Controllers\ForumController::class, 'myQuestions']);
        Route::post('my/forum/questions', [\App\Http\Controllers\ForumController::class, 'createMyQuestion']);
        Route::patch('my/forum/questions/{question}', [\App\Http\Controllers\ForumController::class, 'updateMyQuestion']);
        Route::delete('my/forum/questions/{question}', [\App\Http\Controllers\ForumController::class, 'destroyMyQuestion']);

        // Forum stats and cards endpoints
        Route::get('forum/stats', [\App\Http\Controllers\ForumController::class, 'stats']);
        Route::get('forum/cards', [\App\Http\Controllers\ForumController::class, 'cards']);

        // Like/Unlike endpoints for questions and answers
        Route::post('forum/questions/{question}/like', [\App\Http\Controllers\ForumController::class, 'likeQuestion']);
        Route::post('forum/questions/{question}/unlike', [\App\Http\Controllers\ForumController::class, 'unlikeQuestion']);
        Route::post('forum/answers/{answer}/like', [\App\Http\Controllers\ForumController::class, 'likeAnswer']);
        Route::post('forum/answers/{answer}/unlike', [\App\Http\Controllers\ForumController::class, 'unlikeAnswer']);

        // Notifications
        Route::get('notifications', [\App\Http\Controllers\NotificationController::class, 'index']);
        Route::post('notifications/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markRead']);
        Route::post('notifications/mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllRead']);
        Route::get('notifications/unread-count', [\App\Http\Controllers\NotificationController::class, 'unreadCount']);
        Route::get('notifications/preferences', [\App\Http\Controllers\NotificationPreferenceController::class, 'show']);
        Route::patch('notifications/preferences', [\App\Http\Controllers\NotificationPreferenceController::class, 'update']);

        // Payments
        Route::get('payments', [\App\Http\Controllers\PaymentController::class, 'index']);
        Route::post('payments', [\App\Http\Controllers\PaymentController::class, 'store']);
        Route::post('payments/webhook', [\App\Http\Controllers\PaymentController::class, 'webhook'])->withoutMiddleware('auth:sanctum');

        // Progress
        Route::get('progress', [\App\Http\Controllers\ProgressController::class, 'index']);
        Route::post('progress', [\App\Http\Controllers\ProgressController::class, 'store']);
        Route::get('progress/{progress}', [\App\Http\Controllers\ProgressController::class, 'show']);
        Route::put('progress/{progress}', [\App\Http\Controllers\ProgressController::class, 'update']);
        Route::delete('progress/{progress}', [\App\Http\Controllers\ProgressController::class, 'destroy']);
        
        // Lesson Notes (User personal notes for lessons)
        Route::post('lessons/{lesson}/notes', [\App\Http\Controllers\ProgressController::class, 'addLessonNotes']);
        Route::get('lessons/{lesson}/notes', [\App\Http\Controllers\ProgressController::class, 'getLessonNotes']);
        Route::put('lessons/{lesson}/notes', [\App\Http\Controllers\ProgressController::class, 'updateLessonNotes']);
        Route::delete('lessons/{lesson}/notes', [\App\Http\Controllers\ProgressController::class, 'deleteLessonNotes']);

        // Users (basic admin ops)
        Route::get('users/stats', [\App\Http\Controllers\UsersController::class, 'getStats'])->middleware('role:admin');
        Route::get('users', [\App\Http\Controllers\UsersController::class, 'index'])->middleware('role:admin');
        Route::get('users/simple', [\App\Http\Controllers\UsersController::class, 'simpleList']);
        // Trainers list endpoint moved to public routes - see TrainerController@index
        // Route::get('trainers', [\App\Http\Controllers\UsersController::class, 'trainersList']); // REMOVED - use /api/v1/trainers (public)
        Route::get('trainers/list-for-training', [\App\Http\Controllers\TrainerController::class, 'listForTraining'])->middleware('role:admin');
        Route::post('trainers', [\App\Http\Controllers\TrainerController::class, 'store'])->middleware('role:admin');
        Route::get('categories', [\App\Http\Controllers\UsersController::class, 'categoriesList']);
        Route::post('users', [\App\Http\Controllers\UsersController::class, 'store'])->middleware('role:admin');
        Route::get('users/{user}', [\App\Http\Controllers\UsersController::class, 'show'])->middleware('role:admin');
        Route::patch('users/{user}', [\App\Http\Controllers\UsersController::class, 'update'])->middleware('role:admin');
        Route::post('users/{user}/toggle-2fa', [\App\Http\Controllers\UsersController::class, 'toggleTwoFactor'])->middleware('role:admin');
        Route::delete('users/{user}', [\App\Http\Controllers\UsersController::class, 'destroy'])->middleware('role:admin');

        // Profile Management (for authenticated users)
        Route::get('profile', [\App\Http\Controllers\ProfileController::class, 'show']);
        Route::patch('profile', [\App\Http\Controllers\ProfileController::class, 'update']);
        Route::post('profile/change-password', [\App\Http\Controllers\ProfileController::class, 'changePassword']);
        Route::post('profile/confirm-password', [\App\Http\Controllers\ProfileController::class, 'confirmPassword']);
        Route::post('profile/request-email-change', [\App\Http\Controllers\ProfileController::class, 'requestEmailChange']);
        Route::post('profile/verify-email-change', [\App\Http\Controllers\ProfileController::class, 'verifyEmailChange']);
        Route::post('profile/resend-email-change-otp', [\App\Http\Controllers\ProfileController::class, 'resendEmailChangeOtp']);
        Route::post('profile/cancel-email-change', [\App\Http\Controllers\ProfileController::class, 'cancelEmailChange']);
        
        // Profile Photo Management
        Route::post('profile/upload-photo', [\App\Http\Controllers\ProfileController::class, 'uploadProfilePhoto']);
        Route::delete('profile/delete-photo', [\App\Http\Controllers\ProfileController::class, 'deleteProfilePhoto']);
        Route::delete('profile', [\App\Http\Controllers\ProfileController::class, 'deleteAccount']);

        // Google Calendar Authentication
        Route::get('google/auth-url', [\App\Http\Controllers\GoogleAuthController::class, 'getAuthUrl']);
        Route::get('google/check-access', [\App\Http\Controllers\GoogleAuthController::class, 'checkAccess']);
        Route::post('google/revoke-access', [\App\Http\Controllers\GoogleAuthController::class, 'revokeAccess']);
        Route::get('google/oauth2-code', [\App\Http\Controllers\GoogleAuthController::class, 'getOAuth2Code']);

        // Legal Documents Management (admin only)
        Route::prefix('legal')->middleware('role:admin')->group(function () {
            // Privacy Policies
            Route::get('privacy-policies', [\App\Http\Controllers\LegalDocumentController::class, 'indexPrivacyPolicies']);
            Route::get('privacy-policies/{privacyPolicy}', [\App\Http\Controllers\LegalDocumentController::class, 'showPrivacyPolicy']);
            Route::post('privacy-policies', [\App\Http\Controllers\LegalDocumentController::class, 'storePrivacyPolicy']);
            Route::put('privacy-policies/{privacyPolicy}', [\App\Http\Controllers\LegalDocumentController::class, 'updatePrivacyPolicy']);
            Route::delete('privacy-policies/{privacyPolicy}', [\App\Http\Controllers\LegalDocumentController::class, 'destroyPrivacyPolicy']);
            
            // Terms of Service
            Route::get('terms-of-service/list', [\App\Http\Controllers\LegalDocumentController::class, 'indexTermsOfService']);
            Route::get('terms-of-service/{termsOfService}', [\App\Http\Controllers\LegalDocumentController::class, 'showTermsOfService']);
            Route::post('terms-of-service', [\App\Http\Controllers\LegalDocumentController::class, 'storeTermsOfService']);
            Route::put('terms-of-service/{termsOfService}', [\App\Http\Controllers\LegalDocumentController::class, 'updateTermsOfService']);
            Route::delete('terms-of-service/{termsOfService}', [\App\Http\Controllers\LegalDocumentController::class, 'destroyTermsOfService']);
        });
    });

    // Google OAuth2 callback (without auth middleware)
    Route::get('google/callback', [\App\Http\Controllers\GoogleAuthController::class, 'handleCallback']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Meeting Cards (must be before meetings/{id} routes)
        Route::get('meetings/cards', [\App\Http\Controllers\MeetingController::class, 'getCards']);
        
        // Google Meet Management (admin,trainer only)
        Route::apiResource('meetings', \App\Http\Controllers\MeetingController::class)->middleware('role:admin,trainer');
        Route::get('meetings/{meeting}/attendees', [\App\Http\Controllers\MeetingController::class, 'attendees'])->middleware('role:admin,trainer');
        
        // Meeting Registration (for all authenticated users)
        Route::post('meetings/{meeting}/register', [\App\Http\Controllers\MeetingController::class, 'register']);
        Route::delete('meetings/{meeting}/cancel-registration', [\App\Http\Controllers\MeetingController::class, 'cancelRegistration']);
        Route::get('my-meetings', [\App\Http\Controllers\MeetingController::class, 'myRegistrations']);

        // Registrations
        Route::post('trainings/{training}/register', [\App\Http\Controllers\RegistrationController::class, 'registerTraining']);
        Route::delete('trainings/{training}/cancel-registration', [\App\Http\Controllers\RegistrationController::class, 'cancelTrainingRegistration']);
        Route::get('my-training-registrations', [\App\Http\Controllers\RegistrationController::class, 'myTrainingRegistrations']);
        Route::post('exams/{exam}/register', [\App\Http\Controllers\RegistrationController::class, 'registerExam']);
        
        // Webinar Statistics
        Route::get('webinar-stats', [\App\Http\Controllers\WebinarStatsController::class, 'getStats']);
        Route::get('webinar-analytics', [\App\Http\Controllers\WebinarStatsController::class, 'getAnalytics']);

        // Educational Contents (Maarifləndirmə)
        // Public listing endpoints for frontend dashboards
        Route::get('education/stats', [\App\Http\Controllers\EducationalContentController::class, 'stats']);
        Route::get('education/articles', [\App\Http\Controllers\EducationalContentController::class, 'articles']);
        Route::get('education/telimats', [\App\Http\Controllers\EducationalContentController::class, 'telimats']);

        // Like/Save/Unsave endpoints
        Route::post('education/{id}/like', [\App\Http\Controllers\EducationalContentController::class, 'like']);
        Route::post('education/{id}/unlike', [\App\Http\Controllers\EducationalContentController::class, 'unlike']);
        Route::post('education/{id}/save', [\App\Http\Controllers\EducationalContentController::class, 'save']);
        Route::post('education/{id}/unsave', [\App\Http\Controllers\EducationalContentController::class, 'unsave']);
        Route::get('my-saved-articles', [\App\Http\Controllers\EducationalContentController::class, 'mySaved']);

        // Admin/Trainer CRUD
        Route::apiResource('education', \App\Http\Controllers\EducationalContentController::class)->middleware('role:admin,trainer');

        // Internship Programs (Staj Proqramları)
        // Public endpoints for viewing programs
        Route::get('internship-programs', [\App\Http\Controllers\InternshipProgramController::class, 'index'])
            ->middleware('optional.auth'); // Optional auth - token varsa user məlumatlarını qaytarır
        Route::get('internship-programs/featured', [\App\Http\Controllers\InternshipProgramController::class, 'featured']);
        Route::get('internship-programs/categories', [\App\Http\Controllers\InternshipProgramController::class, 'categories']);
        Route::get('internship-programs/trainers', [\App\Http\Controllers\InternshipProgramController::class, 'trainers']);
        Route::get('internship-programs/{internshipProgram}', [\App\Http\Controllers\InternshipProgramController::class, 'show'])
            ->middleware('optional.auth'); // Optional auth - token varsa user məlumatlarını qaytarır
        
        // User application endpoints
        Route::post('internship-programs/{internshipProgram}/apply', [\App\Http\Controllers\InternshipApplicationController::class, 'apply']);
        Route::get('my-internship-applications', [\App\Http\Controllers\InternshipApplicationController::class, 'myApplications']);
        Route::get('internship-applications/{application}', [\App\Http\Controllers\InternshipApplicationController::class, 'show']);
        Route::delete('internship-applications/{application}', [\App\Http\Controllers\InternshipApplicationController::class, 'destroy']);
        Route::get('internship-applications/{application}/download-cv', [\App\Http\Controllers\InternshipApplicationController::class, 'downloadCv']);
        
        // Admin/Trainer CRUD for internship programs
        Route::apiResource('internship-programs', \App\Http\Controllers\InternshipProgramController::class)->middleware('role:admin,trainer');
        
        // Admin-only internship program management
        Route::get('internship-programs/{internshipProgram}/applications', [\App\Http\Controllers\InternshipProgramController::class, 'getApplications'])->middleware('role:admin');
        Route::get('internship-programs/{internshipProgram}/enrolled-users', [\App\Http\Controllers\InternshipProgramController::class, 'getEnrolledUsers'])->middleware('role:admin');
        Route::get('internship-programs/{internshipProgram}/stats', [\App\Http\Controllers\InternshipProgramController::class, 'getStats'])->middleware('role:admin');
        
        // Admin application management
        Route::get('admin/internship-applications', [\App\Http\Controllers\InternshipApplicationController::class, 'index'])->middleware('role:admin');
        Route::put('admin/internship-applications/{application}/status', [\App\Http\Controllers\InternshipApplicationController::class, 'updateStatus'])->middleware('role:admin');
        
        // Admin Exam Management
        Route::prefix('admin')->middleware('role:admin')->group(function () {
            // pending-reviews must be before {exam} route to avoid conflict
            Route::get('exams/pending-reviews', [\App\Http\Controllers\AdminExamController::class, 'getPendingReviews']);
            Route::get('exams/{exam}', [\App\Http\Controllers\AdminExamController::class, 'show']);
            Route::get('exams/{registrationId}/for-grading', [\App\Http\Controllers\AdminExamController::class, 'getExamForGrading']);
            Route::post('exams/{registrationId}/grade-text-questions', [\App\Http\Controllers\AdminExamController::class, 'gradeTextQuestions']);
        });
        
        // Service Packages CRUD (admin only)
        Route::apiResource('service-packages', \App\Http\Controllers\ServicePackageController::class)
            ->except(['index', 'show'])
            ->middleware('role:admin');
    }); // End of v1 prefix group
});

// Admin Exam Management (without v1 prefix for backward compatibility)
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('exams/pending-reviews', [\App\Http\Controllers\AdminExamController::class, 'getPendingReviews']);
    Route::get('exams/{exam}', [\App\Http\Controllers\AdminExamController::class, 'show']);
    Route::get('exams/{registrationId}/for-grading', [\App\Http\Controllers\AdminExamController::class, 'getExamForGrading']);
    Route::post('exams/{registrationId}/grade-text-questions', [\App\Http\Controllers\AdminExamController::class, 'gradeTextQuestions']);
});

// Public certificate verification routes (no authentication required)
Route::prefix('certificates')->group(function () {
    Route::get('verify/{signature}', [\App\Http\Controllers\CertificateController::class, 'viewBySignature']);
    Route::get('verify-page/{signature}', [\App\Http\Controllers\CertificateController::class, 'verifyBySignature']);
    Route::get('download/{signature}', [\App\Http\Controllers\CertificateController::class, 'downloadPdfBySignature']);
    Route::post('generate-pdf-test', [\App\Http\Controllers\CertificateController::class, 'generatePdfCertificateTest']);
});

// Test routes (no authentication required)
Route::prefix('test')->group(function () {
    Route::put('exams/{exam}/status', [\App\Http\Controllers\ExamController::class, 'updateStatus']);
    Route::get('exams/detailed-list', [\App\Http\Controllers\ExamController::class, 'getDetailedExamList']);
    Route::get('trainings', [\App\Http\Controllers\TrainingController::class, 'index']);
    Route::get('trainings/all', [\App\Http\Controllers\TrainingController::class, 'getAll']);
});
