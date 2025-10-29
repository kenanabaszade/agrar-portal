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
        Schema::table('exam_registrations', function (Blueprint $table) {
            $table->boolean('needs_manual_grading')->default(false)->after('finished_at');
            $table->integer('auto_graded_score')->nullable()->after('needs_manual_grading');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_registrations', function (Blueprint $table) {
            $table->dropColumn(['needs_manual_grading', 'auto_graded_score']);
        });
    }
};
