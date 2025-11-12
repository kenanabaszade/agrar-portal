<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->json('question_translations')->nullable()->after('question');
            $table->json('answer_translations')->nullable()->after('answer');
        });

        \DB::table('faqs')->get()->each(function ($faq) {
            $updates = [];
            if (!empty($faq->question)) {
                $updates['question_translations'] = json_encode(['az' => $faq->question], JSON_UNESCAPED_UNICODE);
            }
            if (!empty($faq->answer)) {
                $updates['answer_translations'] = json_encode(['az' => $faq->answer], JSON_UNESCAPED_UNICODE);
            }
            if (!empty($updates)) {
                \DB::table('faqs')->where('id', $faq->id)->update($updates);
            }
        });

        \DB::statement('ALTER TABLE faqs DROP COLUMN question, DROP COLUMN answer');
        \DB::statement('ALTER TABLE faqs RENAME COLUMN question_translations TO question');
        \DB::statement('ALTER TABLE faqs RENAME COLUMN answer_translations TO answer');
    }

    public function down(): void
    {
        \DB::statement('ALTER TABLE faqs RENAME COLUMN question TO question_translations');
        \DB::statement('ALTER TABLE faqs RENAME COLUMN answer TO answer_translations');

        Schema::table('faqs', function (Blueprint $table) {
            $table->text('question')->after('id');
            $table->text('answer')->after('question');
        });

        \DB::table('faqs')->get()->each(function ($faq) {
            $updates = [];
            if (!empty($faq->question_translations)) {
                $data = json_decode($faq->question_translations, true);
                if (is_array($data)) $updates['question'] = $data['az'] ?? reset($data) ?? null;
            }
            if (!empty($faq->answer_translations)) {
                $data = json_decode($faq->answer_translations, true);
                if (is_array($data)) $updates['answer'] = $data['az'] ?? reset($data) ?? null;
            }
            if (!empty($updates)) {
                \DB::table('faqs')->where('id', $faq->id)->update($updates);
            }
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->dropColumn(['question_translations', 'answer_translations']);
        });
    }
};
