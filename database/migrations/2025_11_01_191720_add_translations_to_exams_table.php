<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add new JSON columns
        Schema::table('exams', function (Blueprint $table) {
            $table->json('title_translations')->nullable()->after('title');
            $table->json('description_translations')->nullable()->after('description');
            if (Schema::hasColumn('exams', 'sertifikat_description')) {
                $table->json('sertifikat_description_translations')->nullable()->after('sertifikat_description');
            }
            if (Schema::hasColumn('exams', 'rules')) {
                $table->json('rules_translations')->nullable()->after('rules');
            }
            if (Schema::hasColumn('exams', 'instructions')) {
                $table->json('instructions_translations')->nullable()->after('instructions');
            }
        });

        // Step 2: Migrate existing data
        \DB::table('exams')->get()->each(function ($exam) {
            $updates = [];

            if (!empty($exam->title)) {
                $updates['title_translations'] = json_encode(['az' => $exam->title], JSON_UNESCAPED_UNICODE);
            }
            if (!empty($exam->description)) {
                $updates['description_translations'] = json_encode(['az' => $exam->description], JSON_UNESCAPED_UNICODE);
            }
            if (Schema::hasColumn('exams', 'sertifikat_description') && !empty($exam->sertifikat_description)) {
                $updates['sertifikat_description_translations'] = json_encode(['az' => $exam->sertifikat_description], JSON_UNESCAPED_UNICODE);
            }
            if (Schema::hasColumn('exams', 'rules') && !empty($exam->rules)) {
                $updates['rules_translations'] = json_encode(['az' => $exam->rules], JSON_UNESCAPED_UNICODE);
            }
            if (Schema::hasColumn('exams', 'instructions') && !empty($exam->instructions)) {
                $updates['instructions_translations'] = json_encode(['az' => $exam->instructions], JSON_UNESCAPED_UNICODE);
            }

            if (!empty($updates)) {
                \DB::table('exams')
                    ->where('id', $exam->id)
                    ->update($updates);
            }
        });

        // Step 3: Drop old columns and rename new ones
        \DB::statement('ALTER TABLE exams DROP COLUMN IF EXISTS title, DROP COLUMN IF EXISTS description');
        \DB::statement('ALTER TABLE exams RENAME COLUMN title_translations TO title');
        \DB::statement('ALTER TABLE exams RENAME COLUMN description_translations TO description');

        if (Schema::hasColumn('exams', 'sertifikat_description')) {
            \DB::statement('ALTER TABLE exams DROP COLUMN sertifikat_description');
            \DB::statement('ALTER TABLE exams RENAME COLUMN sertifikat_description_translations TO sertifikat_description');
        }
        if (Schema::hasColumn('exams', 'rules')) {
            \DB::statement('ALTER TABLE exams DROP COLUMN rules');
            \DB::statement('ALTER TABLE exams RENAME COLUMN rules_translations TO rules');
        }
        if (Schema::hasColumn('exams', 'instructions')) {
            \DB::statement('ALTER TABLE exams DROP COLUMN instructions');
            \DB::statement('ALTER TABLE exams RENAME COLUMN instructions_translations TO instructions');
        }
    }

    public function down(): void
    {
        \DB::statement('ALTER TABLE exams RENAME COLUMN title TO title_translations');
        \DB::statement('ALTER TABLE exams RENAME COLUMN description TO description_translations');
        
        if (Schema::hasColumn('exams', 'sertifikat_description_translations')) {
            \DB::statement('ALTER TABLE exams RENAME COLUMN sertifikat_description TO sertifikat_description_translations');
        }
        if (Schema::hasColumn('exams', 'rules_translations')) {
            \DB::statement('ALTER TABLE exams RENAME COLUMN rules TO rules_translations');
        }
        if (Schema::hasColumn('exams', 'instructions_translations')) {
            \DB::statement('ALTER TABLE exams RENAME COLUMN instructions TO instructions_translations');
        }

        Schema::table('exams', function (Blueprint $table) {
            $table->string('title')->after('training_id');
            $table->text('description')->nullable()->after('title');
            if (Schema::hasColumn('exams', 'sertifikat_description_translations')) {
                $table->text('sertifikat_description')->nullable();
            }
            if (Schema::hasColumn('exams', 'rules_translations')) {
                $table->text('rules')->nullable();
            }
            if (Schema::hasColumn('exams', 'instructions_translations')) {
                $table->text('instructions')->nullable();
            }
        });

        \DB::table('exams')->get()->each(function ($exam) {
            $updates = [];

            if (!empty($exam->title_translations)) {
                $titleData = json_decode($exam->title_translations, true);
                if (is_array($titleData)) {
                    $updates['title'] = $titleData['az'] ?? reset($titleData) ?? null;
                }
            }

            if (!empty($exam->description_translations)) {
                $descData = json_decode($exam->description_translations, true);
                if (is_array($descData)) {
                    $updates['description'] = $descData['az'] ?? reset($descData) ?? null;
                }
            }

            if (Schema::hasColumn('exams', 'sertifikat_description_translations') && !empty($exam->sertifikat_description_translations)) {
                $certData = json_decode($exam->sertifikat_description_translations, true);
                if (is_array($certData)) {
                    $updates['sertifikat_description'] = $certData['az'] ?? reset($certData) ?? null;
                }
            }

            if (Schema::hasColumn('exams', 'rules_translations') && !empty($exam->rules_translations)) {
                $rulesData = json_decode($exam->rules_translations, true);
                if (is_array($rulesData)) {
                    $updates['rules'] = $rulesData['az'] ?? reset($rulesData) ?? null;
                }
            }

            if (Schema::hasColumn('exams', 'instructions_translations') && !empty($exam->instructions_translations)) {
                $instData = json_decode($exam->instructions_translations, true);
                if (is_array($instData)) {
                    $updates['instructions'] = $instData['az'] ?? reset($instData) ?? null;
                }
            }

            if (!empty($updates)) {
                \DB::table('exams')
                    ->where('id', $exam->id)
                    ->update($updates);
            }
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['title_translations', 'description_translations']);
            if (Schema::hasColumn('exams', 'sertifikat_description_translations')) {
                $table->dropColumn('sertifikat_description_translations');
            }
            if (Schema::hasColumn('exams', 'rules_translations')) {
                $table->dropColumn('rules_translations');
            }
            if (Schema::hasColumn('exams', 'instructions_translations')) {
                $table->dropColumn('instructions_translations');
            }
        });
    }
};
