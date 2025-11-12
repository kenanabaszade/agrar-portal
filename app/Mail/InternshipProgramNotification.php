<?php

namespace App\Mail;

use App\Models\InternshipProgram;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InternshipProgramNotification extends Mailable
{
    use Queueable, SerializesModels;

    public InternshipProgram $program;
    public User $user;
    public string $action; // 'created' or 'updated'

    /**
     * Create a new message instance.
     */
    public function __construct(InternshipProgram $program, User $user, string $action = 'created')
    {
        $this->program = $program;
        $this->user = $user;
        $this->action = $action;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->action === 'created' 
            ? 'Yeni Staj Proqramı: ' . $this->program->title
            : 'Staj Proqramı Yeniləndi: ' . $this->program->title;
            
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
            view: 'emails.internship-program-notification',
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






