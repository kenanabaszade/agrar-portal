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
            // Remove the separate fields as we'll use only media_files JSON
            $table->dropColumn(['banner_image_url', 'intro_video_url']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            // Restore the separate fields if we need to rollback
            $table->string('banner_image_url')->nullable()->after('is_online');
            $table->string('intro_video_url')->nullable()->after('banner_image_url');
        });
    }
};
