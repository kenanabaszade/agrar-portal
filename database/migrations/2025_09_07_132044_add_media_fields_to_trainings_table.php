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
            $table->string('banner_image_url')->nullable()->after('is_online');
            $table->string('intro_video_url')->nullable()->after('banner_image_url');
            $table->json('media_files')->nullable()->after('intro_video_url'); // Store array of media files with metadata
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->dropColumn(['banner_image_url', 'intro_video_url', 'media_files']);
        });
    }
};
