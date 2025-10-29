<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('educational_contents', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['meqale', 'telimat', 'elan']);

            // Common fields
            $table->json('seo')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->string('image_path')->nullable();

            // Article specific (Meqale)
            $table->string('title')->nullable();
            $table->longText('body_html')->nullable();
            $table->unsignedInteger('sequence')->default(1);
            $table->string('hashtags')->nullable();
            $table->string('category')->nullable();
            $table->boolean('send_to_our_user')->default(false);
            $table->json('media_files')->nullable(); // array of {name,path,type}

            // Telimat specific
            $table->text('description')->nullable();
            $table->json('documents')->nullable(); // array of {name,path,type}

            // Elan specific
            $table->string('announcement_title')->nullable();
            $table->text('announcement_body')->nullable();

            $table->timestamps();

            $table->index(['type', 'created_by']);
            $table->index(['category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('educational_contents');
    }
};



