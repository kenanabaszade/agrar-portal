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
            $table->text('admin_feedback')->nullable()->after('needs_manual_grading');
            $table->dateTime('graded_at')->nullable()->after('admin_feedback');
            $table->foreignId('graded_by')->nullable()->after('graded_at')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_user_answers', function (Blueprint $table) {
            $table->dropForeign(['graded_by']);
            $table->dropColumn(['admin_feedback', 'graded_at', 'graded_by']);
        });
    }
};
