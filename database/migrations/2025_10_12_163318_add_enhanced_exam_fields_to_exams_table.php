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
            // Rules and instructions
            $table->text('rules')->nullable()->after('description');
            $table->text('instructions')->nullable()->after('rules');
            
            // Hashtags
            $table->json('hashtags')->nullable()->after('instructions');
            
            // Time warning
            $table->integer('time_warning_minutes')->nullable()->after('duration_minutes');
            
            // Max attempts
            $table->integer('max_attempts')->nullable()->after('time_warning_minutes');
            
            // Exam parameters
            $table->boolean('randomize_questions')->default(false)->after('max_attempts');
            $table->boolean('randomize_choices')->default(false)->after('randomize_questions');
            $table->boolean('show_results_immediately')->default(false)->after('randomize_choices');
            $table->boolean('show_correct_answers')->default(false)->after('show_results_immediately');
            $table->boolean('show_explanations')->default(false)->after('show_correct_answers');
            $table->boolean('allow_tab_switching')->default(true)->after('show_explanations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn([
                'rules',
                'instructions', 
                'hashtags',
                'time_warning_minutes',
                'max_attempts',
                'randomize_questions',
                'randomize_choices',
                'show_results_immediately',
                'show_correct_answers',
                'show_explanations',
                'allow_tab_switching'
            ]);
        });
    }
};
