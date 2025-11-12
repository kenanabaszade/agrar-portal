<?php

namespace App\Mail;

use App\Models\Training;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrainingCompletionNotification extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public Training $training;
    public array $details;

    public function __construct(User $user, Training $training, array $details)
    {
        $this->user = $user;
        $this->training = $training;
        $this->details = $details;
    }

    public function envelope(): Envelope
    {
        $title = $this->details['training_title'] ?? (
            is_array($this->training->title)
                ? ($this->training->title['az'] ?? reset($this->training->title))
                : ($this->training->title ?? 'təlim')
        );

        return new Envelope(
            subject: 'Təbriklər! "' . $title . '" təlimini tamamladınız'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.training-completion',
            with: [
                'user' => $this->user,
                'training' => $this->training,
                'details' => $this->details,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

