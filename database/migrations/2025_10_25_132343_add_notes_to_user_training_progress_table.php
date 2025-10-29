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
        Schema::table('user_training_progress', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('completed_at');
            $table->text('time_spent')->nullable()->after('notes'); // Time spent in seconds
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_training_progress', function (Blueprint $table) {
            $table->dropColumn(['notes', 'time_spent']);
        });
    }
};
