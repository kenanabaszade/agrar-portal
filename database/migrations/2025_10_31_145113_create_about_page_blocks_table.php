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
        Schema::create('about_page_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // hero, heading, stats, etc.
            $table->integer('order')->default(0);
            $table->json('data')->nullable(); // Block data (title, description, image, etc.)
            $table->json('styles')->nullable(); // Block styles
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('order');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('about_page_blocks');
    }
};
