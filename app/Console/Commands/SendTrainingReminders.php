<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Training;
use App\Models\User;
use App\Mail\TrainingReminderNotification;
use App\Services\NotificationService;
use Carbon\Carbon;

class SendTrainingReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'training:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send training reminder emails 3 hours before training starts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting training reminder process...');

        // Get current time
        $now = Carbon::now();
        
        // Calculate 3 hours from now
        $reminderTime = $now->copy()->addHours(3);
        
        // Find trainings that start in 3 hours
        $trainings = Training::where('type', 'online')
            ->where('status', 'published')
            ->whereNotNull('google_meet_link')
            ->where(function ($query) use ($reminderTime) {
                $query->where(function ($q) use ($reminderTime) {
                    // For single day trainings
                    $q->whereDate('start_date', $reminderTime->toDateString())
                      ->whereTime('start_time', $reminderTime->format('H:i:s'));
                })
                ->orWhere(function ($q) use ($reminderTime) {
                    // For recurring trainings
                    $q->where('is_recurring', true)
                      ->where('recurrence_end_date', '>=', $reminderTime->toDateString())
                      ->whereTime('start_time', $reminderTime->format('H:i:s'));
                });
            })
            ->get();

        $this->info("Found {$trainings->count()} trainings starting in 3 hours");

        $notificationService = app(NotificationService::class);

        foreach ($trainings as $training) {
            $this->info("Processing training: {$training->title}");
            
            // Get all users
            $users = User::where('email', '!=', null)
                ->where('email', '!=', '')
                ->get(['id', 'first_name', 'last_name', 'email']);

            $emailsSent = 0;
            
            foreach ($users as $user) {
                try {
                    if ($notificationService->sendMail(
                        $user,
                        new TrainingReminderNotification($training, $user)
                    )) {
                        $emailsSent++;
                    }
                } catch (\Throwable $e) {
                    $this->error("Failed to send reminder to {$user->email}: " . $e->getMessage());
                }
            }
            
            $this->info("Sent {$emailsSent} reminder emails for training: {$training->title}");
        }

        $this->info('Training reminder process completed!');
    }
}