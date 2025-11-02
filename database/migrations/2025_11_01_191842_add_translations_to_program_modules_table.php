<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_modules', function (Blueprint $table) {
            $table->json('title_translations')->nullable()->after('title');
            if (Schema::hasColumn('program_modules', 'description')) {
                $table->json('description_translations')->nullable()->after('description');
            }
        });

        \DB::table('program_modules')->get()->each(function ($module) {
            $updates = [];
            if (!empty($module->title)) {
                $updates['title_translations'] = json_encode(['az' => $module->title], JSON_UNESCAPED_UNICODE);
            }
            if (Schema::hasColumn('program_modules', 'description') && !empty($module->description)) {
                $updates['description_translations'] = json_encode(['az' => $module->description], JSON_UNESCAPED_UNICODE);
            }
            if (!empty($updates)) {
                \DB::table('program_modules')->where('id', $module->id)->update($updates);
            }
        });

        \DB::statement('ALTER TABLE program_modules DROP COLUMN title');
        \DB::statement('ALTER TABLE program_modules RENAME COLUMN title_translations TO title');
        
        if (Schema::hasColumn('program_modules', 'description')) {
            \DB::statement('ALTER TABLE program_modules DROP COLUMN description');
            \DB::statement('ALTER TABLE program_modules RENAME COLUMN description_translations TO description');
        }
    }

    public function down(): void
    {
        \DB::statement('ALTER TABLE program_modules RENAME COLUMN title TO title_translations');
        if (Schema::hasColumn('program_modules', 'description')) {
            \DB::statement('ALTER TABLE program_modules RENAME COLUMN description TO description_translations');
        }

        Schema::table('program_modules', function (Blueprint $table) {
            $table->string('title')->after('internship_program_id');
            if (Schema::hasColumn('program_modules', 'description_translations')) {
                $table->text('description')->nullable()->after('title');
            }
        });

        \DB::table('program_modules')->get()->each(function ($module) {
            $updates = [];
            if (!empty($module->title_translations)) {
                $data = json_decode($module->title_translations, true);
                if (is_array($data)) $updates['title'] = $data['az'] ?? reset($data) ?? null;
            }
            if (Schema::hasColumn('program_modules', 'description_translations') && !empty($module->description_translations)) {
                $data = json_decode($module->description_translations, true);
                if (is_array($data)) $updates['description'] = $data['az'] ?? reset($data) ?? null;
            }
            if (!empty($updates)) {
                \DB::table('program_modules')->where('id', $module->id)->update($updates);
            }
        });

        Schema::table('program_modules', function (Blueprint $table) {
            $table->dropColumn('title_translations');
            if (Schema::hasColumn('program_modules', 'description_translations')) {
                $table->dropColumn('description_translations');
            }
        });
    }
};
