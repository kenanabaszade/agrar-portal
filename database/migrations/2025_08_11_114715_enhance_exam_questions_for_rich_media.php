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
        Schema::table('exam_questions', function (Blueprint $table) {
            // Add rich media support for questions
            $table->json('question_media')->nullable()->after('question_text'); // Media files for the question
            $table->text('explanation')->nullable()->after('question_media'); // Explanation of the correct answer
            $table->unsignedInteger('points')->default(1)->after('explanation'); // Points for this question
            $table->boolean('is_required')->default(true)->after('points'); // Whether question is required
            $table->json('metadata')->nullable()->after('is_required'); // Additional question metadata
        });

        Schema::table('exam_choices', function (Blueprint $table) {
            // Add rich media support for choices
            $table->json('choice_media')->nullable()->after('choice_text'); // Media files for the choice
            $table->text('explanation')->nullable()->after('choice_media'); // Explanation for this choice
            $table->unsignedInteger('points')->default(0)->after('explanation'); // Points for selecting this choice
            $table->json('metadata')->nullable()->after('points'); // Additional choice metadata
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->dropColumn([
                'question_media',
                'explanation',
                'points',
                'is_required',
                'metadata'
            ]);
        });

        Schema::table('exam_choices', function (Blueprint $table) {
            $table->dropColumn([
                'choice_media',
                'explanation',
                'points',
                'metadata'
            ]);
        });
    }
};
