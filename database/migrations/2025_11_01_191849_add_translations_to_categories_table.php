<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->json('name_translations')->nullable()->after('name');
            $table->json('description_translations')->nullable()->after('description');
        });

        \DB::table('categories')->get()->each(function ($category) {
            $updates = [];
            if (!empty($category->name)) {
                $updates['name_translations'] = json_encode(['az' => $category->name], JSON_UNESCAPED_UNICODE);
            }
            if (!empty($category->description)) {
                $updates['description_translations'] = json_encode(['az' => $category->description], JSON_UNESCAPED_UNICODE);
            }
            if (!empty($updates)) {
                \DB::table('categories')->where('id', $category->id)->update($updates);
            }
        });

        \DB::statement('ALTER TABLE categories DROP COLUMN name, DROP COLUMN description');
        \DB::statement('ALTER TABLE categories RENAME COLUMN name_translations TO name');
        \DB::statement('ALTER TABLE categories RENAME COLUMN description_translations TO description');
    }

    public function down(): void
    {
        \DB::statement('ALTER TABLE categories RENAME COLUMN name TO name_translations');
        \DB::statement('ALTER TABLE categories RENAME COLUMN description TO description_translations');

        Schema::table('categories', function (Blueprint $table) {
            $table->string('name')->after('id');
            $table->text('description')->nullable()->after('name');
        });

        \DB::table('categories')->get()->each(function ($category) {
            $updates = [];
            if (!empty($category->name_translations)) {
                $data = json_decode($category->name_translations, true);
                if (is_array($data)) $updates['name'] = $data['az'] ?? reset($data) ?? null;
            }
            if (!empty($category->description_translations)) {
                $data = json_decode($category->description_translations, true);
                if (is_array($data)) $updates['description'] = $data['az'] ?? reset($data) ?? null;
            }
            if (!empty($updates)) {
                \DB::table('categories')->where('id', $category->id)->update($updates);
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['name_translations', 'description_translations']);
        });
    }
};
