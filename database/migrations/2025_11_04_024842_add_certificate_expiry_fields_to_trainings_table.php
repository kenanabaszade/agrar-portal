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
        Schema::table('trainings', function (Blueprint $table) {
            $table->json('certificate_description')->nullable()->after('has_certificate');
            $table->boolean('certificate_has_expiry')->default(false)->after('certificate_description');
            $table->integer('certificate_expiry_years')->nullable()->after('certificate_has_expiry');
            $table->integer('certificate_expiry_months')->nullable()->after('certificate_expiry_years');
            $table->integer('certificate_expiry_days')->nullable()->after('certificate_expiry_months');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->dropColumn([
                'certificate_description',
                'certificate_has_expiry',
                'certificate_expiry_years',
                'certificate_expiry_months',
                'certificate_expiry_days'
            ]);
        });
    }
};
