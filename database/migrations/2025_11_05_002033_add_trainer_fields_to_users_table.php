<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('trainer_category')->nullable()->after('user_type');
            $table->json('trainer_description')->nullable()->after('trainer_category');
            $table->integer('experience_years')->nullable()->after('trainer_description');
            $table->integer('experience_months')->nullable()->after('experience_years');
            $table->json('specializations')->nullable()->after('experience_months');
            $table->json('qualifications')->nullable()->after('specializations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'trainer_category')) {
                $table->dropColumn([
                    'trainer_category',
                    'trainer_description',
                    'experience_years',
                    'experience_months',
                    'specializations',
                    'qualifications',
                ]);
            }
        });
    }
};
