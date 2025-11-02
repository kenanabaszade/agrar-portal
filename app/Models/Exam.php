<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_id', 'title', 'description', 'sertifikat_description', 'category', 'passing_score', 'duration_minutes', 'start_date', 'end_date',
        'rules', 'instructions', 'hashtags', 'time_warning_minutes', 'max_attempts',
        'randomize_questions', 'randomize_choices', 'show_results_immediately', 
        'show_correct_answers', 'show_explanations', 'allow_tab_switching', 'track_tab_changes',
        'status', 'exam_question_count', 'auto_submit'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'passing_score' => 'integer',
        'duration_minutes' => 'integer',
        'hashtags' => 'array',
        'time_warning_minutes' => 'integer',
        'max_attempts' => 'integer',
        'randomize_questions' => 'boolean',
        'randomize_choices' => 'boolean',
        'show_results_immediately' => 'boolean',
        'show_correct_answers' => 'boolean',
        'show_explanations' => 'boolean',
        'allow_tab_switching' => 'boolean',
        'track_tab_changes' => 'boolean',
        'exam_question_count' => 'integer',
        'auto_submit' => 'boolean',
    ];

    protected $attributes = [
        'status' => 'published',
    ];

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function questions()
    {
        return $this->hasMany(ExamQuestion::class);
    }

    public function registrations()
    {
        return $this->hasMany(ExamRegistration::class);
    }
}


