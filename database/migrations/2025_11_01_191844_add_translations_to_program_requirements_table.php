<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_requirements', function (Blueprint $table) {
            $table->json('requirement_translations')->nullable()->after('requirement');
        });

        \DB::table('program_requirements')->get()->each(function ($req) {
            if (!empty($req->requirement)) {
                \DB::table('program_requirements')
                    ->where('id', $req->id)
                    ->update(['requirement_translations' => json_encode(['az' => $req->requirement], JSON_UNESCAPED_UNICODE)]);
            }
        });

        \DB::statement('ALTER TABLE program_requirements DROP COLUMN requirement');
        \DB::statement('ALTER TABLE program_requirements RENAME COLUMN requirement_translations TO requirement');
    }

    public function down(): void
    {
        \DB::statement('ALTER TABLE program_requirements RENAME COLUMN requirement TO requirement_translations');

        Schema::table('program_requirements', function (Blueprint $table) {
            $table->text('requirement')->after('internship_program_id');
        });

        \DB::table('program_requirements')->get()->each(function ($req) {
            if (!empty($req->requirement_translations)) {
                $data = json_decode($req->requirement_translations, true);
                if (is_array($data)) {
                    \DB::table('program_requirements')
                        ->where('id', $req->id)
                        ->update(['requirement' => $data['az'] ?? reset($data) ?? null]);
                }
            }
        });

        Schema::table('program_requirements', function (Blueprint $table) {
            $table->dropColumn('requirement_translations');
        });
    }
};
