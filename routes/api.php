<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Auth
    Route::post('auth/register', [\App\Http\Controllers\AuthController::class, 'register']);
    Route::post('auth/verify-otp', [\App\Http\Controllers\AuthController::class, 'verifyOtp']);
    Route::post('auth/resend-otp', [\App\Http\Controllers\AuthController::class, 'resendOtp']);
    Route::post('auth/login', [\App\Http\Controllers\AuthController::class, 'login']);
    
    // Password Reset (Public routes)
    Route::post('auth/forgot-password', [\App\Http\Controllers\AuthController::class, 'forgotPassword']);
    Route::post('auth/verify-password-reset-otp', [\App\Http\Controllers\AuthController::class, 'verifyPasswordResetOtp']);
    Route::post('auth/reset-password', [\App\Http\Controllers\AuthController::class, 'resetPassword']);
    Route::post('auth/resend-password-reset-otp', [\App\Http\Controllers\AuthController::class, 'resendPasswordResetOtp']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [\App\Http\Controllers\AuthController::class, 'logout']);
        
        // 2FA Management (for authenticated users)
        Route::get('auth/2fa/status', [\App\Http\Controllers\AuthController::class, 'getTwoFactorStatus']);
        Route::post('auth/2fa/enable', [\App\Http\Controllers\AuthController::class, 'enableTwoFactor']);
        Route::post('auth/2fa/verify-enable', [\App\Http\Controllers\AuthController::class, 'verifyTwoFactorActivation']);
        Route::post('auth/2fa/disable', [\App\Http\Controllers\AuthController::class, 'disableTwoFactor']);

        // Training Management
        Route::apiResource('trainings', \App\Http\Controllers\TrainingController::class)->middleware('role:admin,trainer');
        
        // Training Module Management (admin,trainer only)
        Route::apiResource('trainings.modules', \App\Http\Controllers\TrainingModuleController::class)->middleware('role:admin,trainer');
        
        // Training Lesson Management (admin,trainer only)
        Route::apiResource('modules.lessons', \App\Http\Controllers\TrainingLessonController::class)->middleware('role:admin,trainer');
        Route::post('lessons/{lesson}/upload-media', [\App\Http\Controllers\TrainingLessonController::class, 'uploadMedia'])->middleware('role:admin,trainer');
        Route::delete('lessons/{lesson}/remove-media', [\App\Http\Controllers\TrainingLessonController::class, 'removeMedia'])->middleware('role:admin,trainer');
        Route::post('modules/{module}/reorder-lessons', [\App\Http\Controllers\TrainingLessonController::class, 'reorder'])->middleware('role:admin,trainer');
        
        // Lesson Progress (for students)
        Route::get('lessons/{lesson}/progress', [\App\Http\Controllers\TrainingLessonController::class, 'getProgress']);
        Route::post('lessons/{lesson}/complete', [\App\Http\Controllers\TrainingLessonController::class, 'markCompleted']);

        // Exams CRUD (admin,trainer only)
        Route::apiResource('exams', \App\Http\Controllers\ExamController::class)->middleware('role:admin,trainer');
        Route::post('exams/{exam}/start', [\App\Http\Controllers\ExamController::class, 'start']);
        Route::post('exams/{exam}/submit', [\App\Http\Controllers\ExamController::class, 'submit']);
        
        // Exam Question Management (admin,trainer only)
        Route::post('exams/{exam}/questions', [\App\Http\Controllers\ExamController::class, 'addQuestion'])->middleware('role:admin,trainer');
        Route::put('exams/{exam}/questions/{question}', [\App\Http\Controllers\ExamController::class, 'updateQuestion'])->middleware('role:admin,trainer');
        Route::delete('exams/{exam}/questions/{question}', [\App\Http\Controllers\ExamController::class, 'deleteQuestion'])->middleware('role:admin,trainer');
        Route::get('exams/{exam}/questions', [\App\Http\Controllers\ExamController::class, 'getExamWithQuestions'])->middleware('role:admin,trainer');
        
        // Exam Taking (for students)
        Route::get('exams/{exam}/take', [\App\Http\Controllers\ExamController::class, 'getExamForTaking']);
        Route::post('exams/{exam}/upload-question-media', [\App\Http\Controllers\ExamController::class, 'uploadQuestionMedia'])->middleware('role:admin,trainer');

        // Certificates
        Route::get('certificates', [\App\Http\Controllers\CertificateController::class, 'index']);
        Route::get('certificates/{certificate}', [\App\Http\Controllers\CertificateController::class, 'show']);

        // Forum
        Route::get('forum/questions', [\App\Http\Controllers\ForumController::class, 'listQuestions']);
        Route::post('forum/questions', [\App\Http\Controllers\ForumController::class, 'postQuestion']);
        Route::get('forum/questions/{question}', [\App\Http\Controllers\ForumController::class, 'showQuestion']);
        Route::post('forum/questions/{question}/answers', [\App\Http\Controllers\ForumController::class, 'answerQuestion']);
        Route::get('forum/questions/{question}/answers', [\App\Http\Controllers\ForumController::class, 'getAnswers']);

        // Notifications
        Route::get('notifications', [\App\Http\Controllers\NotificationController::class, 'index']);
        Route::post('notifications/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markRead']);

        // Payments
        Route::get('payments', [\App\Http\Controllers\PaymentController::class, 'index']);
        Route::post('payments', [\App\Http\Controllers\PaymentController::class, 'store']);
        Route::post('payments/webhook', [\App\Http\Controllers\PaymentController::class, 'webhook'])->withoutMiddleware('auth:sanctum');

        // Progress
        Route::get('progress', [\App\Http\Controllers\ProgressController::class, 'index']);
        Route::post('progress', [\App\Http\Controllers\ProgressController::class, 'store']);

        // Users (basic admin ops)
        Route::get('users', [\App\Http\Controllers\UsersController::class, 'index'])->middleware('role:admin');
        Route::get('users/{user}', [\App\Http\Controllers\UsersController::class, 'show'])->middleware('role:admin');
        Route::patch('users/{user}', [\App\Http\Controllers\UsersController::class, 'update'])->middleware('role:admin');

        // Registrations
        Route::post('trainings/{training}/register', [\App\Http\Controllers\RegistrationController::class, 'registerTraining']);
        Route::post('exams/{exam}/register', [\App\Http\Controllers\RegistrationController::class, 'registerExam']);
    });
});


