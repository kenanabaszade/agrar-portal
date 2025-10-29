<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Exam;
use App\Models\ExamRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ExamFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $exam;
    public $registration;

    public function __construct(User $user, Exam $exam, ExamRegistration $registration)
    {
        $this->user = $user;
        $this->exam = $exam;
        $this->registration = $registration;
    }

    public function build()
    {
        return $this->subject('İmtahan nəticəsi')
            ->view('emails.exam-failed')
            ->with([
                'user' => $this->user,
                'exam' => $this->exam,
                'registration' => $this->registration,
            ]);
    }
}

