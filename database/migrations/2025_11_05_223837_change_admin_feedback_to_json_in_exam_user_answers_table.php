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
        $connection = DB::connection();
        $driverName = $connection->getDriverName();

        if ($driverName === 'pgsql') {
            // PostgreSQL: Use ALTER COLUMN
            DB::statement('ALTER TABLE exam_user_answers ALTER COLUMN admin_feedback TYPE JSONB USING admin_feedback::jsonb');
        } else {
            // MySQL/MariaDB: Use MODIFY COLUMN
            DB::statement('ALTER TABLE exam_user_answers MODIFY COLUMN admin_feedback JSON NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = DB::connection();
        $driverName = $connection->getDriverName();

        if ($driverName === 'pgsql') {
            // PostgreSQL: Revert back to text
            DB::statement('ALTER TABLE exam_user_answers ALTER COLUMN admin_feedback TYPE TEXT USING admin_feedback::text');
        } else {
            // MySQL/MariaDB: Revert back to text
            DB::statement('ALTER TABLE exam_user_answers MODIFY COLUMN admin_feedback TEXT NULL');
        }
    }
};
