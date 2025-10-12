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
        Schema::create('temp_lesson_files', function (Blueprint $table) {
            $table->id();
            $table->string('file_code')->unique();
            $table->string('temp_path');
            $table->string('type');
            $table->string('filename');
            $table->bigInteger('size');
            $table->string('mime_type');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
            
            $table->index(['file_code', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_lesson_files');
    }
};