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
        Schema::table('meetings', function (Blueprint $table) {
            $table->boolean('is_permanent')->default(false)->after('hashtags');
            $table->boolean('has_certificate')->default(false)->after('is_permanent');
            $table->index(['is_permanent', 'status']);
            $table->index(['has_certificate', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropIndex(['is_permanent', 'status']);
            $table->dropIndex(['has_certificate', 'status']);
            $table->dropColumn(['is_permanent', 'has_certificate']);
        });
    }
};
