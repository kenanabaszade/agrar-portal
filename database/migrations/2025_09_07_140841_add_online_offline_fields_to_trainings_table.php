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
            $table->string('type')->default('online')->after('is_online'); // 'online' or 'offline'
            $table->json('online_details')->nullable()->after('type'); // For online training details
            $table->json('offline_details')->nullable()->after('online_details'); // For offline training details
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->dropColumn(['type', 'online_details', 'offline_details']);
        });
    }
};
