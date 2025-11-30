<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->index('type', 'trainings_type_idx');
            $table->index('status', 'trainings_status_idx');
            $table->index('end_date', 'trainings_end_date_idx');
            $table->index(['type', 'start_date'], 'trainings_type_start_date_idx');
            $table->index(['status', 'start_date'], 'trainings_status_start_date_idx');
            $table->index(['category', 'start_date'], 'trainings_category_start_date_idx');
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->index('status', 'exams_status_idx');
            $table->index('end_date', 'exams_end_date_idx');
            $table->index(['status', 'start_date'], 'exams_status_start_date_idx');
            $table->index(['category', 'start_date'], 'exams_category_start_date_idx');
        });

        Schema::table('training_registrations', function (Blueprint $table) {
            $table->index('user_id', 'training_registrations_user_id_idx');
            $table->index('training_id', 'training_registrations_training_id_idx');
            $table->index('registration_date', 'training_registrations_registration_date_idx');
            $table->index(['user_id', 'status'], 'training_registrations_user_id_status_idx');
            $table->index(['training_id', 'status'], 'training_registrations_training_id_status_idx');
            $table->index(['user_id', 'registration_date'], 'training_registrations_user_id_registration_date_idx');
        });

        Schema::table('exam_registrations', function (Blueprint $table) {
            $table->index('user_id', 'exam_registrations_user_id_idx');
            $table->index('exam_id', 'exam_registrations_exam_id_idx');
            $table->index('registration_date', 'exam_registrations_registration_date_idx');
            $table->index(['user_id', 'status'], 'exam_registrations_user_id_status_idx');
            $table->index(['exam_id', 'status'], 'exam_registrations_exam_id_status_idx');
            $table->index(['user_id', 'registration_date'], 'exam_registrations_user_id_registration_date_idx');
        });

        Schema::table('forum_questions', function (Blueprint $table) {
            $table->index('status', 'forum_questions_status_idx');
            $table->index(['user_id', 'status'], 'forum_questions_user_id_status_idx');
            $table->index(['status', 'created_at'], 'forum_questions_status_created_at_idx');
        });

        Schema::table('forum_answers', function (Blueprint $table) {
            $table->index('user_id', 'forum_answers_user_id_idx');
            $table->index(['question_id', 'created_at'], 'forum_answers_question_id_created_at_idx');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index('user_id', 'notifications_user_id_idx');
            $table->index('type', 'notifications_type_idx');
            $table->index('is_read', 'notifications_is_read_idx');
            $table->index(['user_id', 'type'], 'notifications_user_id_type_idx');
        });

        Schema::table('user_training_progress', function (Blueprint $table) {
            $table->index('user_id', 'user_training_progress_user_id_idx');
            $table->index('training_id', 'user_training_progress_training_id_idx');
            $table->index('module_id', 'user_training_progress_module_id_idx');
            $table->index('lesson_id', 'user_training_progress_lesson_id_idx');
            $table->index('status', 'user_training_progress_status_idx');
            $table->index(['user_id', 'training_id'], 'user_training_progress_user_id_training_id_idx');
            $table->index(['user_id', 'status'], 'user_training_progress_user_id_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->dropIndex('trainings_type_idx');
            $table->dropIndex('trainings_status_idx');
            $table->dropIndex('trainings_end_date_idx');
            $table->dropIndex('trainings_type_start_date_idx');
            $table->dropIndex('trainings_status_start_date_idx');
            $table->dropIndex('trainings_category_start_date_idx');
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropIndex('exams_status_idx');
            $table->dropIndex('exams_end_date_idx');
            $table->dropIndex('exams_status_start_date_idx');
            $table->dropIndex('exams_category_start_date_idx');
        });

        Schema::table('training_registrations', function (Blueprint $table) {
            $table->dropIndex('training_registrations_user_id_idx');
            $table->dropIndex('training_registrations_training_id_idx');
            $table->dropIndex('training_registrations_registration_date_idx');
            $table->dropIndex('training_registrations_user_id_status_idx');
            $table->dropIndex('training_registrations_training_id_status_idx');
            $table->dropIndex('training_registrations_user_id_registration_date_idx');
        });

        Schema::table('exam_registrations', function (Blueprint $table) {
            $table->dropIndex('exam_registrations_user_id_idx');
            $table->dropIndex('exam_registrations_exam_id_idx');
            $table->dropIndex('exam_registrations_registration_date_idx');
            $table->dropIndex('exam_registrations_user_id_status_idx');
            $table->dropIndex('exam_registrations_exam_id_status_idx');
            $table->dropIndex('exam_registrations_user_id_registration_date_idx');
        });

        Schema::table('forum_questions', function (Blueprint $table) {
            $table->dropIndex('forum_questions_status_idx');
            $table->dropIndex('forum_questions_user_id_status_idx');
            $table->dropIndex('forum_questions_status_created_at_idx');
        });

        Schema::table('forum_answers', function (Blueprint $table) {
            $table->dropIndex('forum_answers_user_id_idx');
            $table->dropIndex('forum_answers_question_id_created_at_idx');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_user_id_idx');
            $table->dropIndex('notifications_type_idx');
            $table->dropIndex('notifications_is_read_idx');
            $table->dropIndex('notifications_user_id_type_idx');
        });

        Schema::table('user_training_progress', function (Blueprint $table) {
            $table->dropIndex('user_training_progress_user_id_idx');
            $table->dropIndex('user_training_progress_training_id_idx');
            $table->dropIndex('user_training_progress_module_id_idx');
            $table->dropIndex('user_training_progress_lesson_id_idx');
            $table->dropIndex('user_training_progress_status_idx');
            $table->dropIndex('user_training_progress_user_id_training_id_idx');
            $table->dropIndex('user_training_progress_user_id_status_idx');
        });
    }
};
