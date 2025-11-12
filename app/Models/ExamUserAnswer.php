<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamUserAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'registration_id', 'question_id', 'choice_id', 'choice_ids', 'answer_text', 'is_correct', 'answered_at', 'needs_manual_grading', 'admin_feedback', 'graded_at', 'graded_by'
    ];

    protected $casts = [
        'choice_ids' => 'array',
        'is_correct' => 'boolean',
        'answered_at' => 'datetime',
        'needs_manual_grading' => 'boolean',
        'graded_at' => 'datetime',
        'admin_feedback' => 'array',
    ];

    public function registration()
    {
        return $this->belongsTo(ExamRegistration::class);
    }

    public function question()
    {
        return $this->belongsTo(ExamQuestion::class);
    }

    public function choice()
    {
        return $this->belongsTo(ExamChoice::class);
    }

    public function gradedBy()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}


