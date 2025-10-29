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
        // Drop the existing check constraint
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_user_type_check');
        
        // Add the new check constraint with all user types
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_user_type_check CHECK (user_type IN ('farmer', 'trainer', 'admin', 'agronom', 'veterinary', 'government', 'entrepreneur', 'researcher'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new constraint
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_user_type_check');
        
        // Restore the old constraint
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_user_type_check CHECK (user_type IN ('farmer', 'trainer', 'admin'))");
    }
};
