<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add new JSON column
        Schema::table('training_modules', function (Blueprint $table) {
            $table->json('title_translations')->nullable()->after('title');
        });

        // Step 2: Migrate existing data
        \DB::table('training_modules')->get()->each(function ($module) {
            $titleTranslations = null;
            if (!empty($module->title)) {
                $titleTranslations = json_encode(['az' => $module->title], JSON_UNESCAPED_UNICODE);
            }

            \DB::table('training_modules')
                ->where('id', $module->id)
                ->update(['title_translations' => $titleTranslations]);
        });

        // Step 3: Drop old column and rename new one
        \DB::statement('ALTER TABLE training_modules DROP COLUMN title');
        \DB::statement('ALTER TABLE training_modules RENAME COLUMN title_translations TO title');
    }

    public function down(): void
    {
        \DB::statement('ALTER TABLE training_modules RENAME COLUMN title TO title_translations');

        Schema::table('training_modules', function (Blueprint $table) {
            $table->string('title')->after('training_id');
        });

        \DB::table('training_modules')->get()->each(function ($module) {
            $title = null;
            if (!empty($module->title_translations)) {
                $titleData = json_decode($module->title_translations, true);
                if (is_array($titleData)) {
                    $title = $titleData['az'] ?? reset($titleData) ?? null;
                }
            }

            \DB::table('training_modules')
                ->where('id', $module->id)
                ->update(['title' => $title]);
        });

        Schema::table('training_modules', function (Blueprint $table) {
            $table->dropColumn('title_translations');
        });
    }
};
