<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GenericNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        protected string $subjectLine,
        protected string $body
    ) {
    }

    public function build(): static
    {
        return $this->subject($this->subjectLine)
            ->html($this->body);
    }
}


