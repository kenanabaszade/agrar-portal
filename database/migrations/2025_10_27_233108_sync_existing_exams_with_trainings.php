<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Sync existing exams with their trainings
        // Update trainings table to set exam_id for exams that have training_id
        DB::statement("
            UPDATE trainings 
            SET exam_id = (
                SELECT id 
                FROM exams 
                WHERE exams.training_id = trainings.id 
                LIMIT 1
            ),
            has_exam = CASE 
                WHEN EXISTS (
                    SELECT 1 
                    FROM exams 
                    WHERE exams.training_id = trainings.id
                ) THEN true 
                ELSE false 
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset exam_id to null for all trainings
        DB::statement("
            UPDATE trainings 
            SET exam_id = NULL, 
                has_exam = false
        ");
    }
};