<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->json('title_translations')->nullable()->after('title');
            $table->json('description_translations')->nullable()->after('description');
        });

        \DB::table('meetings')->get()->each(function ($meeting) {
            $updates = [];
            if (!empty($meeting->title)) {
                $updates['title_translations'] = json_encode(['az' => $meeting->title], JSON_UNESCAPED_UNICODE);
            }
            if (!empty($meeting->description)) {
                $updates['description_translations'] = json_encode(['az' => $meeting->description], JSON_UNESCAPED_UNICODE);
            }
            if (!empty($updates)) {
                \DB::table('meetings')->where('id', $meeting->id)->update($updates);
            }
        });

        \DB::statement('ALTER TABLE meetings DROP COLUMN title, DROP COLUMN description');
        \DB::statement('ALTER TABLE meetings RENAME COLUMN title_translations TO title');
        \DB::statement('ALTER TABLE meetings RENAME COLUMN description_translations TO description');
    }

    public function down(): void
    {
        \DB::statement('ALTER TABLE meetings RENAME COLUMN title TO title_translations');
        \DB::statement('ALTER TABLE meetings RENAME COLUMN description TO description_translations');

        Schema::table('meetings', function (Blueprint $table) {
            $table->string('title')->after('id');
            $table->text('description')->nullable()->after('title');
        });

        \DB::table('meetings')->get()->each(function ($meeting) {
            $updates = [];
            if (!empty($meeting->title_translations)) {
                $data = json_decode($meeting->title_translations, true);
                if (is_array($data)) $updates['title'] = $data['az'] ?? reset($data) ?? null;
            }
            if (!empty($meeting->description_translations)) {
                $data = json_decode($meeting->description_translations, true);
                if (is_array($data)) $updates['description'] = $data['az'] ?? reset($data) ?? null;
            }
            if (!empty($updates)) {
                \DB::table('meetings')->where('id', $meeting->id)->update($updates);
            }
        });

        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn(['title_translations', 'description_translations']);
        });
    }
};
