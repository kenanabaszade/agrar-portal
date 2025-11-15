<?php

namespace App\Services;

use App\Events\NotificationCreated;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Create a notification record and dispatch channels.
     *
     * @param  array{
     *     channels?: array<int, string>,
     *     data?: array<string, mixed>|null,
     *     mail?: Mailable|null
     * }  $options
     */
    public function send(User $user, string $type, string|array $title, string|array $message, array $options = []): Notification
    {
        $channels = $options['channels'] ?? ['database', 'push'];

        if (isset($options['mail'])) {
            $channels[] = 'mail';
        }

        $channels = array_values(array_unique($channels));

        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $this->formatTranslatable($title),
            'message' => $this->formatTranslatable($message),
            'data' => $options['data'] ?? null,
            'channels' => $channels,
            'is_read' => false,
            'sent_at' => now(),
            'created_at' => now(),
        ]);

        if (in_array('push', $channels, true) && $user->wantsPushNotifications()) {
            event(new NotificationCreated($notification));
        }

        if (
            in_array('mail', $channels, true)
            && isset($options['mail'])
            && $options['mail'] instanceof Mailable
        ) {
            $this->sendMail($user, $options['mail']);
        }

        return $notification;
    }

    public function sendMail(User $user, Mailable $mailable): bool
    {
        return $this->sendMailToAddress($user, $user->email, $mailable);
    }

    public function sendMailToAddress(?User $user, ?string $email, Mailable $mailable): bool
    {
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if ($user && !$user->wantsEmailNotifications()) {
            return false;
        }

        try {
            Mail::to($email)->send($mailable);
            return true;
        } catch (\Throwable $throwable) {
            Log::error('Failed to send notification email', [
                'user_id' => $user?->id,
                'email' => $email,
                'error' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }
    }

    private function formatTranslatable(string|array $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        return ['az' => $value];
    }
}

