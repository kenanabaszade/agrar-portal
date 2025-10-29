<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add true_false to question_type enum
        DB::statement("ALTER TABLE exam_questions DROP CONSTRAINT IF EXISTS exam_questions_question_type_check");
        DB::statement("ALTER TABLE exam_questions ADD CONSTRAINT exam_questions_question_type_check CHECK (question_type IN ('single_choice', 'multiple_choice', 'text', 'true_false'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original constraint
        DB::statement("ALTER TABLE exam_questions DROP CONSTRAINT IF EXISTS exam_questions_question_type_check");
        DB::statement("ALTER TABLE exam_questions ADD CONSTRAINT exam_questions_question_type_check CHECK (question_type IN ('single_choice', 'multiple_choice', 'text'))");
    }
};
