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
        Schema::table('exam_user_answers', function (Blueprint $table) {
            $table->boolean('needs_manual_grading')->default(false)->after('answered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_user_answers', function (Blueprint $table) {
            $table->dropColumn('needs_manual_grading');
        });
    }
};
