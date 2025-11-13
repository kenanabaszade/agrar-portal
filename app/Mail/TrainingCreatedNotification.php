<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrainingCreatedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $training;
    public $attendee;
    public $googleMeetLink;

    /**
     * Create a new message instance.
     */
    public function __construct($training, $attendee, $googleMeetLink = null)
    {
        $this->training = $training;
        $this->attendee = $attendee;
        $this->googleMeetLink = $googleMeetLink;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Yeni Online Təlim: ' . $this->training->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.training-created',
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








