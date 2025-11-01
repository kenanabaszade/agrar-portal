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
        // Drop old about_pages table if exists
        Schema::dropIfExists('about_pages');
        
        // Create about_blocks table
        Schema::create('about_blocks', function (Blueprint $table) {
            $table->id();                              // Auto-increment ID
            $table->string('type');                    // hero, cards, stats, timeline, team, values, contact
            $table->integer('order')->default(0);      // Sıralama (0, 1, 2, ...)
            $table->json('data');                      // JSON data - block-un məzmunu
            $table->json('styles')->nullable();        // JSON styles - format və rənglər
            $table->timestamps();                      // created_at, updated_at
            
            // Indexes
            $table->index('order');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('about_blocks');
    }
};
