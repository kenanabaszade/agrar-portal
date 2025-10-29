<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'exam_id', 'registration_date', 'status', 'score', 'started_at', 'finished_at', 'certificate_id', 'attempt_number', 'needs_manual_grading', 'auto_graded_score', 'selected_question_ids', 'total_questions'
    ];

    protected $casts = [
        'registration_date' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'attempt_number' => 'integer',
        'needs_manual_grading' => 'boolean',
        'auto_graded_score' => 'integer',
        'selected_question_ids' => 'array',
        'total_questions' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function certificate()
    {
        return $this->belongsTo(Certificate::class);
    }

    public function answers()
    {
        return $this->hasMany(ExamUserAnswer::class, 'registration_id');
    }
}


