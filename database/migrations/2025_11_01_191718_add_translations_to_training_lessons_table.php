<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add new JSON columns
        Schema::table('training_lessons', function (Blueprint $table) {
            $table->json('title_translations')->nullable()->after('title');
            $table->json('content_translations')->nullable()->after('content');
            if (Schema::hasColumn('training_lessons', 'description')) {
                $table->json('description_translations')->nullable()->after('description');
            }
        });

        // Step 2: Migrate existing data
        \DB::table('training_lessons')->get()->each(function ($lesson) {
            $updates = [];

            if (!empty($lesson->title)) {
                $updates['title_translations'] = json_encode(['az' => $lesson->title], JSON_UNESCAPED_UNICODE);
            }
            if (!empty($lesson->content)) {
                $updates['content_translations'] = json_encode(['az' => $lesson->content], JSON_UNESCAPED_UNICODE);
            }
            if (Schema::hasColumn('training_lessons', 'description') && !empty($lesson->description)) {
                $updates['description_translations'] = json_encode(['az' => $lesson->description], JSON_UNESCAPED_UNICODE);
            }

            if (!empty($updates)) {
                \DB::table('training_lessons')
                    ->where('id', $lesson->id)
                    ->update($updates);
            }
        });

        // Step 3: Drop old columns and rename new ones
        \DB::statement('ALTER TABLE training_lessons DROP COLUMN IF EXISTS title, DROP COLUMN IF EXISTS content');
        \DB::statement('ALTER TABLE training_lessons RENAME COLUMN title_translations TO title');
        \DB::statement('ALTER TABLE training_lessons RENAME COLUMN content_translations TO content');
        
        if (Schema::hasColumn('training_lessons', 'description')) {
            \DB::statement('ALTER TABLE training_lessons DROP COLUMN description');
            \DB::statement('ALTER TABLE training_lessons RENAME COLUMN description_translations TO description');
        }
    }

    public function down(): void
    {
        \DB::statement('ALTER TABLE training_lessons RENAME COLUMN title TO title_translations');
        \DB::statement('ALTER TABLE training_lessons RENAME COLUMN content TO content_translations');
        
        if (Schema::hasColumn('training_lessons', 'description_translations')) {
            \DB::statement('ALTER TABLE training_lessons RENAME COLUMN description TO description_translations');
        }

        Schema::table('training_lessons', function (Blueprint $table) {
            $table->string('title')->after('module_id');
            $table->text('content')->nullable()->after('title');
            if (Schema::hasColumn('training_lessons', 'description_translations')) {
                $table->text('description')->nullable()->after('content');
            }
        });

        \DB::table('training_lessons')->get()->each(function ($lesson) {
            $updates = [];

            if (!empty($lesson->title_translations)) {
                $titleData = json_decode($lesson->title_translations, true);
                if (is_array($titleData)) {
                    $updates['title'] = $titleData['az'] ?? reset($titleData) ?? null;
                }
            }

            if (!empty($lesson->content_translations)) {
                $contentData = json_decode($lesson->content_translations, true);
                if (is_array($contentData)) {
                    $updates['content'] = $contentData['az'] ?? reset($contentData) ?? null;
                }
            }

            if (Schema::hasColumn('training_lessons', 'description_translations') && !empty($lesson->description_translations)) {
                $descData = json_decode($lesson->description_translations, true);
                if (is_array($descData)) {
                    $updates['description'] = $descData['az'] ?? reset($descData) ?? null;
                }
            }

            if (!empty($updates)) {
                \DB::table('training_lessons')
                    ->where('id', $lesson->id)
                    ->update($updates);
            }
        });

        Schema::table('training_lessons', function (Blueprint $table) {
            $table->dropColumn(['title_translations', 'content_translations']);
            if (Schema::hasColumn('training_lessons', 'description_translations')) {
                $table->dropColumn('description_translations');
            }
        });
    }
};
