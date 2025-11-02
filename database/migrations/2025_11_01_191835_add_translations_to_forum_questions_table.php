<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forum_questions', function (Blueprint $table) {
            $table->json('title_translations')->nullable()->after('title');
            $table->json('summary_translations')->nullable()->after('summary');
            $table->json('body_translations')->nullable()->after('body');
        });

        \DB::table('forum_questions')->get()->each(function ($question) {
            $updates = [];
            if (!empty($question->title)) {
                $updates['title_translations'] = json_encode(['az' => $question->title], JSON_UNESCAPED_UNICODE);
            }
            if (!empty($question->summary)) {
                $updates['summary_translations'] = json_encode(['az' => $question->summary], JSON_UNESCAPED_UNICODE);
            }
            if (!empty($question->body)) {
                $updates['body_translations'] = json_encode(['az' => $question->body], JSON_UNESCAPED_UNICODE);
            }

            if (!empty($updates)) {
                \DB::table('forum_questions')->where('id', $question->id)->update($updates);
            }
        });

        \DB::statement('ALTER TABLE forum_questions DROP COLUMN title, DROP COLUMN summary, DROP COLUMN body');
        \DB::statement('ALTER TABLE forum_questions RENAME COLUMN title_translations TO title');
        \DB::statement('ALTER TABLE forum_questions RENAME COLUMN summary_translations TO summary');
        \DB::statement('ALTER TABLE forum_questions RENAME COLUMN body_translations TO body');
    }

    public function down(): void
    {
        \DB::statement('ALTER TABLE forum_questions RENAME COLUMN title TO title_translations');
        \DB::statement('ALTER TABLE forum_questions RENAME COLUMN summary TO summary_translations');
        \DB::statement('ALTER TABLE forum_questions RENAME COLUMN body TO body_translations');

        Schema::table('forum_questions', function (Blueprint $table) {
            $table->string('title')->after('user_id');
            $table->string('summary')->nullable()->after('title');
            $table->text('body')->after('summary');
        });

        \DB::table('forum_questions')->get()->each(function ($question) {
            $updates = [];
            if (!empty($question->title_translations)) {
                $data = json_decode($question->title_translations, true);
                if (is_array($data)) $updates['title'] = $data['az'] ?? reset($data) ?? null;
            }
            if (!empty($question->summary_translations)) {
                $data = json_decode($question->summary_translations, true);
                if (is_array($data)) $updates['summary'] = $data['az'] ?? reset($data) ?? null;
            }
            if (!empty($question->body_translations)) {
                $data = json_decode($question->body_translations, true);
                if (is_array($data)) $updates['body'] = $data['az'] ?? reset($data) ?? null;
            }
            if (!empty($updates)) {
                \DB::table('forum_questions')->where('id', $question->id)->update($updates);
            }
        });

        Schema::table('forum_questions', function (Blueprint $table) {
            $table->dropColumn(['title_translations', 'summary_translations', 'body_translations']);
        });
    }
};
