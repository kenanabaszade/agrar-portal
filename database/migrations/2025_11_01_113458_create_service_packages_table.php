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
        Schema::create('service_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Başlanğıc Paket, Premium Paket, Korporativ Paket
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->nullable(); // null if free
            $table->enum('price_type', ['free', 'monthly', 'annual'])->default('free'); // Pulsuz, Aylıq, İllik
            $table->string('price_label')->nullable(); // "Limitsiz", "Fərdi qiymət" etc
            $table->boolean('is_recommended')->default(false); // Tövsiyə edilir
            $table->json('features')->nullable(); // Array of feature objects
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_packages');
    }
};
