<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forum_questions', function (Blueprint $table) {
            $table->enum('question_type', ['general', 'technical', 'discussion', 'poll'])->default('general')->after('status');
            $table->string('summary')->nullable()->after('title');
            $table->json('tags')->nullable()->after('summary');
            $table->string('category')->nullable()->after('tags');
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced'])->nullable()->after('category');
            $table->boolean('is_pinned')->default(false)->after('difficulty');
            $table->boolean('allow_comments')->default(true)->after('is_pinned');
            $table->boolean('is_open')->default(true)->after('allow_comments');
            $table->json('poll_options')->nullable()->after('question_type');
        });
    }

    public function down(): void
    {
        Schema::table('forum_questions', function (Blueprint $table) {
            $table->dropColumn([
                'question_type',
                'summary',
                'tags',
                'category',
                'difficulty',
                'is_pinned',
                'allow_comments',
                'is_open',
                'poll_options',
            ]);
        });
    }
};


