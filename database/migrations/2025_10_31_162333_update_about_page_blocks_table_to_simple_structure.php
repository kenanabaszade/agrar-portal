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
        // Drop old table
        Schema::dropIfExists('about_page_blocks');
        
        // Create new simple structure
        Schema::create('about_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('banner_image')->nullable();
            $table->text('content')->nullable(); // HTML content
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('about_pages');
        
        // Restore old table structure
        Schema::create('about_page_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->integer('order')->default(0);
            $table->json('data')->nullable();
            $table->json('styles')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('order');
            $table->index('is_active');
        });
    }
};
