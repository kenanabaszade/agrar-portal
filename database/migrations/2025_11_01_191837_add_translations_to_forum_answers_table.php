<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forum_answers', function (Blueprint $table) {
            $table->json('body_translations')->nullable()->after('body');
        });

        \DB::table('forum_answers')->get()->each(function ($answer) {
            if (!empty($answer->body)) {
                \DB::table('forum_answers')
                    ->where('id', $answer->id)
                    ->update(['body_translations' => json_encode(['az' => $answer->body], JSON_UNESCAPED_UNICODE)]);
            }
        });

        \DB::statement('ALTER TABLE forum_answers DROP COLUMN body');
        \DB::statement('ALTER TABLE forum_answers RENAME COLUMN body_translations TO body');
    }

    public function down(): void
    {
        \DB::statement('ALTER TABLE forum_answers RENAME COLUMN body TO body_translations');

        Schema::table('forum_answers', function (Blueprint $table) {
            $table->text('body')->after('user_id');
        });

        \DB::table('forum_answers')->get()->each(function ($answer) {
            if (!empty($answer->body_translations)) {
                $data = json_decode($answer->body_translations, true);
                if (is_array($data)) {
                    \DB::table('forum_answers')
                        ->where('id', $answer->id)
                        ->update(['body' => $data['az'] ?? reset($data) ?? null]);
                }
            }
        });

        Schema::table('forum_answers', function (Blueprint $table) {
            $table->dropColumn('body_translations');
        });
    }
};
