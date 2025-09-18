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
            $table->json('choice_ids')->nullable()->after('choice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_user_answers', function (Blueprint $table) {
            $table->dropColumn('choice_ids');
        });
    }
};
