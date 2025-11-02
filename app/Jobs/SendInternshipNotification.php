<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendInternshipNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $retryAfter = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $userId,
        public array $notificationData
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $user = User::find($this->userId);
            
            if (!$user) {
                Log::warning("User {$this->userId} not found for internship notification");
                return;
            }

            // Note: Create InternshipNotification mailable if it doesn't exist
            // For now, we'll use a generic mail sending
            Mail::raw($this->notificationData['message'] ?? 'Internship notification', function ($message) use ($user) {
                $message->to($user->email)
                        ->subject($this->notificationData['subject'] ?? 'Internship Notification');
            });
            
            Log::info("Internship notification sent to user {$this->userId}");
        } catch (\Exception $e) {
            Log::error("Failed to send internship notification to user {$this->userId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Internship notification job failed for user {$this->userId}: " . $exception->getMessage());
    }
}
