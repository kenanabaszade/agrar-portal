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
        // For PostgreSQL, we need to drop and recreate the column
        Schema::table('trainings', function (Blueprint $table) {
            $table->dropColumn('type');
        });
        
        Schema::table('trainings', function (Blueprint $table) {
            $table->string('type')->nullable()->after('is_online');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->dropColumn('type');
        });
        
        Schema::table('trainings', function (Blueprint $table) {
            $table->string('type')->nullable()->after('is_online');
        });
    }
};