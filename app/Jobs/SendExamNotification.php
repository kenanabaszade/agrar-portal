<?php

namespace App\Jobs;

use App\Models\User;
use App\Mail\GenericNotificationMail;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendExamNotification implements ShouldQueue
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
                Log::warning("User {$this->userId} not found for exam notification");
                return;
            }

            $mail = new GenericNotificationMail(
                $this->notificationData['subject'] ?? 'Exam Notification',
                $this->notificationData['message'] ?? 'Exam notification'
            );

            $sent = app(NotificationService::class)->sendMail($user, $mail);

            if ($sent) {
                Log::info("Exam notification sent to user {$this->userId}");
            } else {
                Log::info("Exam notification skipped for user {$this->userId} (preferences)");
            }
        } catch (\Exception $e) {
            Log::error("Failed to send exam notification to user {$this->userId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Exam notification job failed for user {$this->userId}: " . $exception->getMessage());
    }
}
