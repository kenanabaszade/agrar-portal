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
        Schema::create('forum_answer_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('answer_id')->constrained('forum_answers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            
            // Hər istifadəçi hər cavaba bir dəfə like qoya bilər
            $table->unique(['answer_id', 'user_id']);
            $table->index(['answer_id']);
            $table->index(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_answer_likes');
    }
};
