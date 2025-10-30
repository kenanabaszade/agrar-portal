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
            // Index for category filtering
            $table->index('category', 'trainings_category_idx');
            
            // Composite index for trainer-based queries with sorting
            $table->index(['trainer_id', 'start_date'], 'trainings_trainer_start_idx');
            
            // Composite index for type-based queries with sorting
            $table->index(['type', 'start_date'], 'trainings_type_start_idx');
            
            // Index for date-based filtering and sorting
            $table->index('start_date', 'trainings_start_date_idx');
        });

        Schema::table('exams', function (Blueprint $table) {
            // Index for category filtering
            $table->index('category', 'exams_category_idx');
            
            // Composite index for training-based queries with sorting
            $table->index(['training_id', 'start_date'], 'exams_training_start_idx');
            
            // Composite index for status-based queries with sorting
            $table->index(['status', 'start_date'], 'exams_status_start_idx');
            
            // Index for date-based filtering and sorting
            $table->index('start_date', 'exams_start_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->dropIndex('trainings_category_idx');
            $table->dropIndex('trainings_trainer_start_idx');
            $table->dropIndex('trainings_type_start_idx');
            $table->dropIndex('trainings_start_date_idx');
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropIndex('exams_category_idx');
            $table->dropIndex('exams_training_start_idx');
            $table->dropIndex('exams_status_start_idx');
            $table->dropIndex('exams_start_date_idx');
        });
    }
};
