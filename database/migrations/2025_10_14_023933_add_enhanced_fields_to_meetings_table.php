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
        Schema::table('meetings', function (Blueprint $table) {
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('meetings', 'category')) {
                $table->string('category')->nullable()->after('trainer_id');
            }
            
            if (!Schema::hasColumn('meetings', 'image_url')) {
                $table->string('image_url')->nullable()->after('description');
            }
            
            if (!Schema::hasColumn('meetings', 'has_materials')) {
                $table->boolean('has_materials')->default(false)->after('image_url');
            }
            
            if (!Schema::hasColumn('meetings', 'documents')) {
                $table->json('documents')->nullable()->after('has_materials');
            }
            
            if (!Schema::hasColumn('meetings', 'level')) {
                $table->enum('level', ['beginner', 'intermediate', 'advanced'])->default('beginner')->after('documents');
            }
            
            if (!Schema::hasColumn('meetings', 'language')) {
                $table->string('language', 10)->default('az')->after('level');
            }
            
            if (!Schema::hasColumn('meetings', 'hashtags')) {
                $table->json('hashtags')->nullable()->after('language');
            }
            
            // Skip indexes for now to avoid conflicts
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['category', 'level']);
            $table->dropIndex(['language', 'status']);
            $table->dropIndex(['has_materials']);
            
            // Drop columns
            $table->dropColumn([
                'category',
                'image_url',
                'has_materials',
                'documents',
                'level',
                'language',
                'hashtags'
            ]);
        });
    }
};