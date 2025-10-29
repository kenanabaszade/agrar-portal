<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrainingRegistrationNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $training;
    public $user;

    public function __construct($training, $user)
    {
        $this->training = $training;
        $this->user = $user;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Təlim Qeydiyyatı Təsdiqləndi: ' . $this->training->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.training-registration',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}


