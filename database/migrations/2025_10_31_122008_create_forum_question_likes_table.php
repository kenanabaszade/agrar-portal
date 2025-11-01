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
        Schema::create('forum_question_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('forum_questions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            
            // Hər istifadəçi hər suala bir dəfə like qoya bilər
            $table->unique(['question_id', 'user_id']);
            $table->index(['question_id']);
            $table->index(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_question_likes');
    }
};
