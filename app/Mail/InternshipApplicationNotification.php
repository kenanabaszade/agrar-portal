<?php

namespace App\Mail;

use App\Models\InternshipApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class InternshipApplicationNotification extends Mailable
{
    use Queueable, SerializesModels;

    public InternshipApplication $application;

    /**
     * Create a new message instance.
     */
    public function __construct(InternshipApplication $application)
    {
        $this->application = $application;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Yeni Staj Proqramı Müraciəti - ' . $this->application->internshipProgram->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.internship-application-notification',
            with: [
                'application' => $this->application,
                'user' => $this->application->user,
                'program' => $this->application->internshipProgram,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk('public', $this->application->cv_file_path)
                ->as($this->application->cv_file_name)
                ->withMime($this->application->cv_mime_type),
        ];
    }
}
