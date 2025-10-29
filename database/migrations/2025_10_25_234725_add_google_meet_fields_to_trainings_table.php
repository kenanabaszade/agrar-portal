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
            $table->string('google_meet_link')->nullable()->after('type');
            $table->string('google_event_id')->nullable()->after('google_meet_link');
            $table->string('meeting_id')->nullable()->after('google_event_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->dropColumn(['google_meet_link', 'google_event_id', 'meeting_id']);
        });
    }
};
