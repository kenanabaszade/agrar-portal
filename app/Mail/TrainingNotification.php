<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrainingNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $training;
    public $user;
    public $action; // 'created' or 'updated'
    public $googleMeetLink;

    /**
     * Create a new message instance.
     */
    public function __construct($training, $user, $action = 'created', $googleMeetLink = null)
    {
        $this->training = $training;
        $this->user = $user;
        $this->action = $action;
        $this->googleMeetLink = $googleMeetLink;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->action === 'created' 
            ? 'Yeni Təlim: ' . $this->training->title
            : 'Təlim Yeniləndi: ' . $this->training->title;
            
        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.training-notification',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}


