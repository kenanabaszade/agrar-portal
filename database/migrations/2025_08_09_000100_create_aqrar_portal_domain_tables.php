<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // trainings
        Schema::create('trainings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->foreignId('trainer_id')->constrained('users')->cascadeOnDelete();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_online')->default(true);
            $table->timestamps();

            $table->index(['trainer_id']);
            $table->index(['category']);
        });

        // training_modules
        Schema::create('training_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('sequence')->default(1);
            $table->timestamps();
            $table->unique(['training_id', 'sequence']);
        });

        // training_lessons
        Schema::create('training_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('training_modules')->cascadeOnDelete();
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('video_url')->nullable();
            $table->string('pdf_url')->nullable();
            $table->unsignedInteger('sequence')->default(1);
            $table->timestamps();
            $table->unique(['module_id', 'sequence']);
        });

        // training_registrations
        Schema::create('training_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete();
            $table->dateTime('registration_date')->useCurrent();
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed', 'cancelled'])->default('pending');
            $table->foreignId('certificate_id')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'training_id']);
            $table->index(['status']);
        });

        // exams
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('passing_score');
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->index(['training_id']);
        });

        // exam_questions
        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->text('question_text');
            $table->enum('question_type', ['single_choice', 'multiple_choice', 'text']);
            $table->unsignedInteger('sequence')->default(1);
            $table->timestamps();
            $table->unique(['exam_id', 'sequence']);
        });

        // exam_choices
        Schema::create('exam_choices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('exam_questions')->cascadeOnDelete();
            $table->string('choice_text');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });

        // certificates (referenced by registrations and exams)
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('related_training_id')->nullable()->constrained('trainings')->nullOnDelete();
            $table->foreignId('related_exam_id')->nullable()->constrained('exams')->nullOnDelete();
            $table->string('certificate_number')->unique();
            $table->date('issue_date');
            $table->date('expiry_date')->nullable();
            $table->string('issuer_name')->nullable();
            $table->string('issuer_logo_url')->nullable();
            $table->text('digital_signature')->nullable();
            $table->string('qr_code')->nullable();
            $table->string('pdf_url')->nullable();
            $table->enum('status', ['active', 'revoked', 'expired'])->default('active');
            $table->timestamps();
            $table->index(['user_id']);
        });

        // exam_registrations
        Schema::create('exam_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->dateTime('registration_date')->useCurrent();
            $table->enum('status', ['pending', 'approved', 'rejected', 'in_progress', 'completed', 'failed', 'passed', 'cancelled'])->default('pending');
            $table->unsignedInteger('score')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->foreignId('certificate_id')->nullable()->constrained('certificates')->nullOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'exam_id']);
            $table->index(['status']);
        });

        // exam_user_answers
        Schema::create('exam_user_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained('exam_registrations')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('exam_questions')->cascadeOnDelete();
            $table->foreignId('choice_id')->nullable()->constrained('exam_choices')->nullOnDelete();
            $table->text('answer_text')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->dateTime('answered_at')->useCurrent();
            $table->timestamps();
            $table->unique(['registration_id', 'question_id']);
        });

        // informational_materials
        Schema::create('informational_materials', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('material_type', ['video', 'pdf', 'article', 'link']);
            $table->string('url');
            $table->string('category')->nullable();
            $table->timestamps();
            $table->index(['material_type']);
        });

        // forum_questions
        Schema::create('forum_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamps();
            $table->index(['user_id']);
        });

        // forum_answers
        Schema::create('forum_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('forum_questions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_accepted')->default(false);
            $table->timestamps();
            $table->index(['question_id']);
        });

        // notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['system', 'training', 'exam', 'payment', 'forum']);
            $table->string('title');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->dateTime('sent_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'is_read']);
        });

        // payments
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->dateTime('payment_date')->useCurrent();
            $table->enum('payment_method', ['card', 'bank_transfer', 'cash', 'mobile_money'])->default('card');
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->foreignId('related_exam_registration_id')->nullable()->constrained('exam_registrations')->nullOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        // user_training_progress
        Schema::create('user_training_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('training_modules')->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained('training_lessons')->cascadeOnDelete();
            $table->enum('status', ['not_started', 'in_progress', 'completed'])->default('not_started');
            $table->dateTime('last_accessed')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'lesson_id']);
        });

        // roles
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
        });

        // permissions
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
        });

        // role_permissions (pivot)
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->unique(['role_id', 'permission_id']);
        });

        // user_roles (pivot)
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->unique(['user_id', 'role_id']);
        });

        // audit_logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('entity');
            $table->unsignedBigInteger('entity_id');
            $table->json('details')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['entity', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('user_training_progress');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('forum_answers');
        Schema::dropIfExists('forum_questions');
        Schema::dropIfExists('informational_materials');
        Schema::dropIfExists('exam_user_answers');
        Schema::dropIfExists('exam_registrations');
        Schema::dropIfExists('exam_choices');
        Schema::dropIfExists('exam_questions');
        Schema::dropIfExists('exams');
        Schema::dropIfExists('training_registrations');
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('training_lessons');
        Schema::dropIfExists('training_modules');
        Schema::dropIfExists('trainings');
    }
};


