<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_goals', function (Blueprint $table) {
            $table->json('goal_translations')->nullable()->after('goal');
        });

        \DB::table('program_goals')->get()->each(function ($goal) {
            if (!empty($goal->goal)) {
                \DB::table('program_goals')
                    ->where('id', $goal->id)
                    ->update(['goal_translations' => json_encode(['az' => $goal->goal], JSON_UNESCAPED_UNICODE)]);
            }
        });

        \DB::statement('ALTER TABLE program_goals DROP COLUMN goal');
        \DB::statement('ALTER TABLE program_goals RENAME COLUMN goal_translations TO goal');
    }

    public function down(): void
    {
        \DB::statement('ALTER TABLE program_goals RENAME COLUMN goal TO goal_translations');

        Schema::table('program_goals', function (Blueprint $table) {
            $table->text('goal')->after('internship_program_id');
        });

        \DB::table('program_goals')->get()->each(function ($goal) {
            if (!empty($goal->goal_translations)) {
                $data = json_decode($goal->goal_translations, true);
                if (is_array($data)) {
                    \DB::table('program_goals')
                        ->where('id', $goal->id)
                        ->update(['goal' => $data['az'] ?? reset($data) ?? null]);
                }
            }
        });

        Schema::table('program_goals', function (Blueprint $table) {
            $table->dropColumn('goal_translations');
        });
    }
};
