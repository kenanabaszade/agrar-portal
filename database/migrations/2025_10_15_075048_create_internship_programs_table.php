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
        Schema::create('internship_programs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('image_url')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->enum('registration_status', ['open', 'closed', 'full'])->default('open');
            $table->string('category');
            $table->integer('duration_weeks');
            $table->date('start_date');
            $table->string('location');
            $table->integer('current_enrollment')->default(0);
            $table->integer('max_capacity');
            $table->string('instructor_name');
            $table->string('instructor_title');
            $table->string('instructor_initials')->nullable();
            $table->string('instructor_photo_url')->nullable();
            $table->text('instructor_description')->nullable();
            $table->decimal('instructor_rating', 2, 1)->nullable();
            $table->string('details_link')->nullable();
            $table->text('cv_requirements')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internship_programs');
    }
};
