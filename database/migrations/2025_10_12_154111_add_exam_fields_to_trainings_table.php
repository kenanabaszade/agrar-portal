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
            $table->boolean('has_exam')->default(false)->after('require_email_verification');
            $table->foreignId('exam_id')->nullable()->constrained('exams')->onDelete('set null')->after('has_exam');
            $table->boolean('exam_required')->default(false)->after('exam_id');
            $table->integer('min_exam_score')->nullable()->after('exam_required');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->dropForeign(['exam_id']);
            $table->dropColumn(['has_exam', 'exam_id', 'exam_required', 'min_exam_score']);
        });
    }
};