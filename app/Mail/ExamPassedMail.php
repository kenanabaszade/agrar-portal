<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Exam;
use App\Models\ExamRegistration;
use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ExamPassedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $exam;
    public $registration;
    public $certificate;

    public function __construct(User $user, Exam $exam, ExamRegistration $registration, Certificate $certificate = null)
    {
        $this->user = $user;
        $this->exam = $exam;
        $this->registration = $registration;
        $this->certificate = $certificate;
    }

    public function build()
    {
        return $this->subject('Təbriklər! İmtahanı uğurla keçdiniz')
            ->view('emails.exam-passed')
            ->with([
                'user' => $this->user,
                'exam' => $this->exam,
                'registration' => $this->registration,
                'certificate' => $this->certificate,
            ]);
    }
}




