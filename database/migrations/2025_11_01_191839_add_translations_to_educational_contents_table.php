<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('educational_contents', function (Blueprint $table) {
            $table->json('title_translations')->nullable()->after('title');
            $table->json('short_description_translations')->nullable()->after('short_description');
            $table->json('body_html_translations')->nullable()->after('body_html');
            if (Schema::hasColumn('educational_contents', 'description')) {
                $table->json('description_translations')->nullable()->after('description');
            }
            if (Schema::hasColumn('educational_contents', 'announcement_title')) {
                $table->json('announcement_title_translations')->nullable()->after('announcement_title');
            }
            if (Schema::hasColumn('educational_contents', 'announcement_body')) {
                $table->json('announcement_body_translations')->nullable()->after('announcement_body');
            }
        });

        \DB::table('educational_contents')->get()->each(function ($content) {
            $updates = [];
            $fields = ['title', 'short_description', 'body_html', 'description', 'announcement_title', 'announcement_body'];
            foreach ($fields as $field) {
                if (Schema::hasColumn('educational_contents', $field) && !empty($content->{$field})) {
                    $updates[$field . '_translations'] = json_encode(['az' => $content->{$field}], JSON_UNESCAPED_UNICODE);
                }
            }
            if (!empty($updates)) {
                \DB::table('educational_contents')->where('id', $content->id)->update($updates);
            }
        });

        \DB::statement('ALTER TABLE educational_contents DROP COLUMN IF EXISTS title, DROP COLUMN IF EXISTS short_description, DROP COLUMN IF EXISTS body_html');
        \DB::statement('ALTER TABLE educational_contents RENAME COLUMN title_translations TO title');
        \DB::statement('ALTER TABLE educational_contents RENAME COLUMN short_description_translations TO short_description');
        \DB::statement('ALTER TABLE educational_contents RENAME COLUMN body_html_translations TO body_html');

        if (Schema::hasColumn('educational_contents', 'description')) {
            \DB::statement('ALTER TABLE educational_contents DROP COLUMN description');
            \DB::statement('ALTER TABLE educational_contents RENAME COLUMN description_translations TO description');
        }
        if (Schema::hasColumn('educational_contents', 'announcement_title')) {
            \DB::statement('ALTER TABLE educational_contents DROP COLUMN announcement_title');
            \DB::statement('ALTER TABLE educational_contents RENAME COLUMN announcement_title_translations TO announcement_title');
        }
        if (Schema::hasColumn('educational_contents', 'announcement_body')) {
            \DB::statement('ALTER TABLE educational_contents DROP COLUMN announcement_body');
            \DB::statement('ALTER TABLE educational_contents RENAME COLUMN announcement_body_translations TO announcement_body');
        }
    }

    public function down(): void
    {
        $renameFields = ['title', 'short_description', 'body_html'];
        if (Schema::hasColumn('educational_contents', 'description')) $renameFields[] = 'description';
        if (Schema::hasColumn('educational_contents', 'announcement_title')) $renameFields[] = 'announcement_title';
        if (Schema::hasColumn('educational_contents', 'announcement_body')) $renameFields[] = 'announcement_body';

        foreach ($renameFields as $field) {
            \DB::statement("ALTER TABLE educational_contents RENAME COLUMN {$field} TO {$field}_translations");
        }

        Schema::table('educational_contents', function (Blueprint $table) {
            $table->string('title')->nullable()->after('image_path');
            $table->text('short_description')->nullable()->after('title');
            $table->longText('body_html')->nullable()->after('short_description');
            if (Schema::hasColumn('educational_contents', 'description_translations')) {
                $table->text('description')->nullable();
            }
            if (Schema::hasColumn('educational_contents', 'announcement_title_translations')) {
                $table->string('announcement_title')->nullable();
            }
            if (Schema::hasColumn('educational_contents', 'announcement_body_translations')) {
                $table->text('announcement_body')->nullable();
            }
        });

        \DB::table('educational_contents')->get()->each(function ($content) {
            $updates = [];
            $fields = ['title', 'short_description', 'body_html', 'description', 'announcement_title', 'announcement_body'];
            foreach ($fields as $field) {
                $transField = $field . '_translations';
                if (Schema::hasColumn('educational_contents', $transField) && !empty($content->{$transField})) {
                    $data = json_decode($content->{$transField}, true);
                    if (is_array($data)) {
                        $updates[$field] = $data['az'] ?? reset($data) ?? null;
                    }
                }
            }
            if (!empty($updates)) {
                \DB::table('educational_contents')->where('id', $content->id)->update($updates);
            }
        });

        Schema::table('educational_contents', function (Blueprint $table) {
            $dropFields = ['title_translations', 'short_description_translations', 'body_html_translations'];
            if (Schema::hasColumn('educational_contents', 'description_translations')) $dropFields[] = 'description_translations';
            if (Schema::hasColumn('educational_contents', 'announcement_title_translations')) $dropFields[] = 'announcement_title_translations';
            if (Schema::hasColumn('educational_contents', 'announcement_body_translations')) $dropFields[] = 'announcement_body_translations';
            $table->dropColumn($dropFields);
        });
    }
};
