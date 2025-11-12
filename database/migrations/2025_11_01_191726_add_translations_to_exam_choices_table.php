<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add new JSON columns
        Schema::table('exam_choices', function (Blueprint $table) {
            $table->json('choice_text_translations')->nullable()->after('choice_text');
            if (Schema::hasColumn('exam_choices', 'explanation')) {
                $table->json('explanation_translations')->nullable()->after('explanation');
            }
        });

        // Step 2: Migrate existing data
        \DB::table('exam_choices')->get()->each(function ($choice) {
            $updates = [];

            if (!empty($choice->choice_text)) {
                $updates['choice_text_translations'] = json_encode(['az' => $choice->choice_text], JSON_UNESCAPED_UNICODE);
            }
            if (Schema::hasColumn('exam_choices', 'explanation') && !empty($choice->explanation)) {
                $updates['explanation_translations'] = json_encode(['az' => $choice->explanation], JSON_UNESCAPED_UNICODE);
            }

            if (!empty($updates)) {
                \DB::table('exam_choices')
                    ->where('id', $choice->id)
                    ->update($updates);
            }
        });

        // Step 3: Drop old columns and rename new ones
        \DB::statement('ALTER TABLE exam_choices DROP COLUMN choice_text');
        \DB::statement('ALTER TABLE exam_choices RENAME COLUMN choice_text_translations TO choice_text');

        if (Schema::hasColumn('exam_choices', 'explanation')) {
            \DB::statement('ALTER TABLE exam_choices DROP COLUMN explanation');
            \DB::statement('ALTER TABLE exam_choices RENAME COLUMN explanation_translations TO explanation');
        }
    }

    public function down(): void
    {
        \DB::statement('ALTER TABLE exam_choices RENAME COLUMN choice_text TO choice_text_translations');
        
        if (Schema::hasColumn('exam_choices', 'explanation_translations')) {
            \DB::statement('ALTER TABLE exam_choices RENAME COLUMN explanation TO explanation_translations');
        }

        Schema::table('exam_choices', function (Blueprint $table) {
            $table->string('choice_text')->after('question_id');
            if (Schema::hasColumn('exam_choices', 'explanation_translations')) {
                $table->text('explanation')->nullable();
            }
        });

        \DB::table('exam_choices')->get()->each(function ($choice) {
            $updates = [];

            if (!empty($choice->choice_text_translations)) {
                $textData = json_decode($choice->choice_text_translations, true);
                if (is_array($textData)) {
                    $updates['choice_text'] = $textData['az'] ?? reset($textData) ?? null;
                }
            }

            if (Schema::hasColumn('exam_choices', 'explanation_translations') && !empty($choice->explanation_translations)) {
                $expData = json_decode($choice->explanation_translations, true);
                if (is_array($expData)) {
                    $updates['explanation'] = $expData['az'] ?? reset($expData) ?? null;
                }
            }

            if (!empty($updates)) {
                \DB::table('exam_choices')
                    ->where('id', $choice->id)
                    ->update($updates);
            }
        });

        Schema::table('exam_choices', function (Blueprint $table) {
            $table->dropColumn('choice_text_translations');
            if (Schema::hasColumn('exam_choices', 'explanation_translations')) {
                $table->dropColumn('explanation_translations');
            }
        });
    }
};
