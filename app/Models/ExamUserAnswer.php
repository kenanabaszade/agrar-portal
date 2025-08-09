<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamUserAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'registration_id', 'question_id', 'choice_id', 'answer_text', 'is_correct', 'answered_at'
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'answered_at' => 'datetime',
    ];
}


