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
        Schema::table('exams', function (Blueprint $table) {
            // Yeni bal sistemi field-ləri
            // exam_question_count - İmtahanda göstəriləcək sual sayı
            $table->integer('exam_question_count')->default(10)->after('max_attempts')->comment('İmtahanda göstəriləcək sual sayı');
            
            // auto_submit - Vaxt bitdikdə avtomatik təqdim etmək
            $table->boolean('auto_submit')->default(false)->after('exam_question_count')->comment('Vaxt bitdikdə avtomatik təqdim etmək');
            
            // NOT: Aşağıdakı field-lər artıq mövcuddur, sadəcə rename edirik
            // randomize_questions -> shuffle_questions (yoxlamaq lazımdır)
            // randomize_choices -> shuffle_choices (yoxlamaq lazımdır)
            // show_results_immediately -> show_result_immediately (yoxlamaq lazımdır)
            // show_correct_answers (artıq mövcuddur)
            // show_explanations (artıq mövcuddur)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn('exam_question_count');
        });
    }
};
