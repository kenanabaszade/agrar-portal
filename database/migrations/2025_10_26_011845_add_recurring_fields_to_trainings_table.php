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
            $table->boolean('is_recurring')->default(false)->after('meeting_id');
            $table->string('recurrence_frequency')->nullable()->after('is_recurring');
            $table->date('recurrence_end_date')->nullable()->after('recurrence_frequency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->dropColumn(['is_recurring', 'recurrence_frequency', 'recurrence_end_date']);
        });
    }
};
