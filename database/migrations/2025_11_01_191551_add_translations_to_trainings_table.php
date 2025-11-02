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
        // Step 1: Add new JSON columns with temporary names
        Schema::table('trainings', function (Blueprint $table) {
            $table->json('title_translations')->nullable()->after('title');
            $table->json('description_translations')->nullable()->after('description');
        });

        // Step 2: Migrate existing data to JSON format
        \DB::table('trainings')->get()->each(function ($training) {
            $titleTranslations = null;
            $descriptionTranslations = null;

            // Convert title to JSON format
            if (!empty($training->title)) {
                $titleTranslations = json_encode(['az' => $training->title], JSON_UNESCAPED_UNICODE);
            }

            // Convert description to JSON format
            if (!empty($training->description)) {
                $descriptionTranslations = json_encode(['az' => $training->description], JSON_UNESCAPED_UNICODE);
            }

            \DB::table('trainings')
                ->where('id', $training->id)
                ->update([
                    'title_translations' => $titleTranslations,
                    'description_translations' => $descriptionTranslations,
                ]);
        });

        // Step 3: Drop old columns and add new ones with original names
        // We need to use raw SQL because Laravel doesn't support changing column types easily
        \DB::statement('ALTER TABLE trainings DROP COLUMN title, DROP COLUMN description');
        \DB::statement('ALTER TABLE trainings RENAME COLUMN title_translations TO title');
        \DB::statement('ALTER TABLE trainings RENAME COLUMN description_translations TO description');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Rename JSON columns to temporary names
        \DB::statement('ALTER TABLE trainings RENAME COLUMN title TO title_translations');
        \DB::statement('ALTER TABLE trainings RENAME COLUMN description TO description_translations');

        // Step 2: Add back old string columns
        Schema::table('trainings', function (Blueprint $table) {
            $table->string('title')->after('id');
            $table->text('description')->nullable()->after('title');
        });

        // Step 3: Migrate data back from JSON to string format
        \DB::table('trainings')->get()->each(function ($training) {
            $title = null;
            $description = null;

            // Extract from JSON (prefer 'az', fallback to first available)
            if (!empty($training->title_translations)) {
                $titleData = json_decode($training->title_translations, true);
                if (is_array($titleData)) {
                    $title = $titleData['az'] ?? reset($titleData) ?? null;
                }
            }

            if (!empty($training->description_translations)) {
                $descData = json_decode($training->description_translations, true);
                if (is_array($descData)) {
                    $description = $descData['az'] ?? reset($descData) ?? null;
                }
            }

            \DB::table('trainings')
                ->where('id', $training->id)
                ->update([
                    'title' => $title,
                    'description' => $description,
                ]);
        });

        // Step 4: Drop translation columns
        Schema::table('trainings', function (Blueprint $table) {
            $table->dropColumn(['title_translations', 'description_translations']);
        });
    }
};
