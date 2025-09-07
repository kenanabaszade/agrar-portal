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
        Schema::create('meeting_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['registered', 'attended', 'cancelled'])->default('registered');
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamp('attended_at')->nullable();
            $table->text('notes')->nullable(); // User notes or admin notes
            $table->timestamps();
            
            // Ensure unique registration per user per meeting
            $table->unique(['meeting_id', 'user_id']);
            
            // Indexes for better performance
            $table->index(['meeting_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_registrations');
    }
};
