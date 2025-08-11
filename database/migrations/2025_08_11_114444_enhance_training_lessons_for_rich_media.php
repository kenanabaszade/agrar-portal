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
        Schema::table('training_lessons', function (Blueprint $table) {
            // Add lesson type and duration
            $table->enum('lesson_type', ['text', 'video', 'audio', 'image', 'mixed'])->default('text')->after('title');
            $table->unsignedInteger('duration_minutes')->nullable()->after('lesson_type');
            
            // Add rich media fields
            $table->json('media_files')->nullable()->after('pdf_url'); // Store multiple media files
            $table->text('description')->nullable()->after('content'); // Lesson description
            
            // Add lesson status and requirements
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft')->after('sequence');
            $table->boolean('is_required')->default(true)->after('status');
            $table->unsignedInteger('min_completion_time')->nullable()->after('is_required'); // Minimum time in seconds
            
            // Add lesson metadata
            $table->json('metadata')->nullable()->after('min_completion_time'); // Additional lesson data
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_lessons', function (Blueprint $table) {
            $table->dropColumn([
                'lesson_type',
                'duration_minutes',
                'media_files',
                'description',
                'status',
                'is_required',
                'min_completion_time',
                'metadata'
            ]);
        });
    }
};
