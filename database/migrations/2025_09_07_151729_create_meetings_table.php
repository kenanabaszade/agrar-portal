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
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('google_event_id')->unique(); // Google Calendar event ID
            $table->string('google_meet_link')->nullable(); // Google Meet URL
            $table->string('meeting_id')->nullable(); // Google Meet meeting ID
            $table->string('meeting_password')->nullable(); // Meeting password if any
            $table->datetime('start_time');
            $table->datetime('end_time');
            $table->string('timezone')->default('UTC');
            $table->integer('max_attendees')->default(100);
            $table->boolean('is_recurring')->default(false);
            $table->json('recurrence_rules')->nullable(); // For recurring meetings
            $table->enum('status', ['scheduled', 'live', 'ended', 'cancelled'])->default('scheduled');
            $table->foreignId('created_by')->constrained('users'); // Admin/Trainer who created
            $table->foreignId('training_id')->nullable()->constrained()->onDelete('cascade'); // Optional training association
            $table->json('attendees')->nullable(); // List of registered attendees
            $table->json('google_metadata')->nullable(); // Store additional Google Calendar data
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['start_time', 'end_time']);
            $table->index(['created_by', 'status']);
            $table->index('google_event_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
