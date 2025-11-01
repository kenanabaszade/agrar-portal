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
        Schema::create('forum_question_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('forum_questions')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('ip_address')->nullable(); // Unauthenticated users üçün
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['question_id']);
            $table->index(['user_id']);
            $table->index(['ip_address']);
            // Composite index for faster lookups
            $table->index(['question_id', 'user_id']);
            $table->index(['question_id', 'ip_address']);
            
            // Note: Unique constraint application səviyyəsində yoxlanılır (firstOrCreate istifadə edilir)
            // Çünki user_id nullable olduğu üçün unique constraint MySQL-də problemli ola bilər
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_question_views');
    }
};
