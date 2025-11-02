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
        // For PostgreSQL, modify the check constraint
        if (DB::getDriverName() === 'pgsql') {
            // Drop the existing constraint
            DB::statement("ALTER TABLE exam_registrations DROP CONSTRAINT IF EXISTS exam_registrations_status_check");
            // Add new constraint with additional status values - PostgreSQL uses simpler syntax for varchar check
            DB::statement("ALTER TABLE exam_registrations ADD CONSTRAINT exam_registrations_status_check CHECK (status IN ('pending', 'approved', 'rejected', 'in_progress', 'completed', 'failed', 'passed', 'cancelled', 'pending_review', 'timeout'))");
        } else {
            // For MySQL, modify the enum directly
            Schema::table('exam_registrations', function (Blueprint $table) {
                $table->enum('status', ['pending', 'approved', 'rejected', 'in_progress', 'completed', 'failed', 'passed', 'cancelled', 'pending_review', 'timeout'])
                    ->default('pending')
                    ->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // Drop the constraint
            DB::statement("ALTER TABLE exam_registrations DROP CONSTRAINT IF EXISTS exam_registrations_status_check");
            // Restore original constraint
            DB::statement("ALTER TABLE exam_registrations ADD CONSTRAINT exam_registrations_status_check CHECK (status IN ('pending', 'approved', 'rejected', 'in_progress', 'completed', 'failed', 'passed', 'cancelled'))");
        } else {
            Schema::table('exam_registrations', function (Blueprint $table) {
                $table->enum('status', ['pending', 'approved', 'rejected', 'in_progress', 'completed', 'failed', 'passed', 'cancelled'])
                    ->default('pending')
                    ->change();
            });
        }
    }
};
