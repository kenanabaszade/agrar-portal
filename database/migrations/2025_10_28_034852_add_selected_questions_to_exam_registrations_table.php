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
            // Seçilmiş sualların ID-lərini saxlamaq üçün
            $table->json('selected_question_ids')->nullable()->comment('İmtahanda göstərilən sualların ID-ləri');
            
            // Ümumi sual sayı
            $table->integer('total_questions')->default(0)->comment('İmtahanda göstərilən sual sayı');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_registrations', function (Blueprint $table) {
            $table->dropColumn(['selected_question_ids', 'total_questions']);
        });
    }
};
