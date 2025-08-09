<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('health', fn () => ['status' => 'ok']);
    // Auth
    Route::post('auth/register', [\App\Http\Controllers\AuthController::class, 'register']);
    Route::post('auth/login', [\App\Http\Controllers\AuthController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [\App\Http\Controllers\AuthController::class, 'logout']);

        // Trainings CRUD (admin,trainer only)
        Route::apiResource('trainings', \App\Http\Controllers\TrainingController::class)->middleware('role:admin,trainer');

        // Exams CRUD (admin,trainer only)
        Route::apiResource('exams', \App\Http\Controllers\ExamController::class)->middleware('role:admin,trainer');
        Route::post('exams/{exam}/start', [\App\Http\Controllers\ExamController::class, 'start']);
        Route::post('exams/{exam}/submit', [\App\Http\Controllers\ExamController::class, 'submit']);

        // Certificates
        Route::get('certificates', [\App\Http\Controllers\CertificateController::class, 'index']);
        Route::get('certificates/{certificate}', [\App\Http\Controllers\CertificateController::class, 'show']);

        // Forum
        Route::get('forum/questions', [\App\Http\Controllers\ForumController::class, 'listQuestions']);
        Route::post('forum/questions', [\App\Http\Controllers\ForumController::class, 'postQuestion']);
        Route::post('forum/questions/{question}/answers', [\App\Http\Controllers\ForumController::class, 'answerQuestion']);

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


