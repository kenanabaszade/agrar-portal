<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internship_programs', function (Blueprint $table) {
            $table->json('title_translations')->nullable()->after('title');
            $table->json('description_translations')->nullable()->after('description');
            if (Schema::hasColumn('internship_programs', 'location')) {
                $table->json('location_translations')->nullable()->after('location');
            }
            if (Schema::hasColumn('internship_programs', 'instructor_description')) {
                $table->json('instructor_description_translations')->nullable()->after('instructor_description');
            }
            if (Schema::hasColumn('internship_programs', 'cv_requirements')) {
                $table->json('cv_requirements_translations')->nullable()->after('cv_requirements');
            }
        });

        \DB::table('internship_programs')->get()->each(function ($program) {
            $updates = [];
            $fields = ['title', 'description', 'location', 'instructor_description', 'cv_requirements'];
            foreach ($fields as $field) {
                if (Schema::hasColumn('internship_programs', $field) && !empty($program->{$field})) {
                    $updates[$field . '_translations'] = json_encode(['az' => $program->{$field}], JSON_UNESCAPED_UNICODE);
                }
            }
            if (!empty($updates)) {
                \DB::table('internship_programs')->where('id', $program->id)->update($updates);
            }
        });

        \DB::statement('ALTER TABLE internship_programs DROP COLUMN IF EXISTS title, DROP COLUMN IF EXISTS description');
        \DB::statement('ALTER TABLE internship_programs RENAME COLUMN title_translations TO title');
        \DB::statement('ALTER TABLE internship_programs RENAME COLUMN description_translations TO description');

        if (Schema::hasColumn('internship_programs', 'location')) {
            \DB::statement('ALTER TABLE internship_programs DROP COLUMN location');
            \DB::statement('ALTER TABLE internship_programs RENAME COLUMN location_translations TO location');
        }
        if (Schema::hasColumn('internship_programs', 'instructor_description')) {
            \DB::statement('ALTER TABLE internship_programs DROP COLUMN instructor_description');
            \DB::statement('ALTER TABLE internship_programs RENAME COLUMN instructor_description_translations TO instructor_description');
        }
        if (Schema::hasColumn('internship_programs', 'cv_requirements')) {
            \DB::statement('ALTER TABLE internship_programs DROP COLUMN cv_requirements');
            \DB::statement('ALTER TABLE internship_programs RENAME COLUMN cv_requirements_translations TO cv_requirements');
        }
    }

    public function down(): void
    {
        $renameFields = ['title', 'description'];
        if (Schema::hasColumn('internship_programs', 'location')) $renameFields[] = 'location';
        if (Schema::hasColumn('internship_programs', 'instructor_description')) $renameFields[] = 'instructor_description';
        if (Schema::hasColumn('internship_programs', 'cv_requirements')) $renameFields[] = 'cv_requirements';

        foreach ($renameFields as $field) {
            \DB::statement("ALTER TABLE internship_programs RENAME COLUMN {$field} TO {$field}_translations");
        }

        Schema::table('internship_programs', function (Blueprint $table) {
            $table->string('title')->after('trainer_mail');
            $table->text('description')->after('title');
            if (Schema::hasColumn('internship_programs', 'location_translations')) {
                $table->string('location')->after('last_register_date');
            }
            if (Schema::hasColumn('internship_programs', 'instructor_description_translations')) {
                $table->text('instructor_description')->nullable();
            }
            if (Schema::hasColumn('internship_programs', 'cv_requirements_translations')) {
                $table->text('cv_requirements')->nullable();
            }
        });

        \DB::table('internship_programs')->get()->each(function ($program) {
            $updates = [];
            $fields = ['title', 'description', 'location', 'instructor_description', 'cv_requirements'];
            foreach ($fields as $field) {
                $transField = $field . '_translations';
                if (Schema::hasColumn('internship_programs', $transField) && !empty($program->{$transField})) {
                    $data = json_decode($program->{$transField}, true);
                    if (is_array($data)) {
                        $updates[$field] = $data['az'] ?? reset($data) ?? null;
                    }
                }
            }
            if (!empty($updates)) {
                \DB::table('internship_programs')->where('id', $program->id)->update($updates);
            }
        });

        Schema::table('internship_programs', function (Blueprint $table) {
            $dropFields = ['title_translations', 'description_translations'];
            if (Schema::hasColumn('internship_programs', 'location_translations')) $dropFields[] = 'location_translations';
            if (Schema::hasColumn('internship_programs', 'instructor_description_translations')) $dropFields[] = 'instructor_description_translations';
            if (Schema::hasColumn('internship_programs', 'cv_requirements_translations')) $dropFields[] = 'cv_requirements_translations';
            $table->dropColumn($dropFields);
        });
    }
};
