<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add new JSON columns
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->json('question_text_translations')->nullable()->after('question_text');
            if (Schema::hasColumn('exam_questions', 'explanation')) {
                $table->json('explanation_translations')->nullable()->after('explanation');
            }
        });

        // Step 2: Migrate existing data
        \DB::table('exam_questions')->get()->each(function ($question) {
            $updates = [];

            if (!empty($question->question_text)) {
                $updates['question_text_translations'] = json_encode(['az' => $question->question_text], JSON_UNESCAPED_UNICODE);
            }
            if (Schema::hasColumn('exam_questions', 'explanation') && !empty($question->explanation)) {
                $updates['explanation_translations'] = json_encode(['az' => $question->explanation], JSON_UNESCAPED_UNICODE);
            }

            if (!empty($updates)) {
                \DB::table('exam_questions')
                    ->where('id', $question->id)
                    ->update($updates);
            }
        });

        // Step 3: Drop old columns and rename new ones
        \DB::statement('ALTER TABLE exam_questions DROP COLUMN question_text');
        \DB::statement('ALTER TABLE exam_questions RENAME COLUMN question_text_translations TO question_text');

        if (Schema::hasColumn('exam_questions', 'explanation')) {
            \DB::statement('ALTER TABLE exam_questions DROP COLUMN explanation');
            \DB::statement('ALTER TABLE exam_questions RENAME COLUMN explanation_translations TO explanation');
        }
    }

    public function down(): void
    {
        \DB::statement('ALTER TABLE exam_questions RENAME COLUMN question_text TO question_text_translations');
        
        if (Schema::hasColumn('exam_questions', 'explanation_translations')) {
            \DB::statement('ALTER TABLE exam_questions RENAME COLUMN explanation TO explanation_translations');
        }

        Schema::table('exam_questions', function (Blueprint $table) {
            $table->text('question_text')->after('exam_id');
            if (Schema::hasColumn('exam_questions', 'explanation_translations')) {
                $table->text('explanation')->nullable();
            }
        });

        \DB::table('exam_questions')->get()->each(function ($question) {
            $updates = [];

            if (!empty($question->question_text_translations)) {
                $textData = json_decode($question->question_text_translations, true);
                if (is_array($textData)) {
                    $updates['question_text'] = $textData['az'] ?? reset($textData) ?? null;
                }
            }

            if (Schema::hasColumn('exam_questions', 'explanation_translations') && !empty($question->explanation_translations)) {
                $expData = json_decode($question->explanation_translations, true);
                if (is_array($expData)) {
                    $updates['explanation'] = $expData['az'] ?? reset($expData) ?? null;
                }
            }

            if (!empty($updates)) {
                \DB::table('exam_questions')
                    ->where('id', $question->id)
                    ->update($updates);
            }
        });

        Schema::table('exam_questions', function (Blueprint $table) {
            $table->dropColumn('question_text_translations');
            if (Schema::hasColumn('exam_questions', 'explanation_translations')) {
                $table->dropColumn('explanation_translations');
            }
        });
    }
};
