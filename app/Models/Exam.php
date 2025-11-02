<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTranslations;

class Exam extends Model
{
    use HasFactory, HasTranslations;

    protected $translatable = ['title', 'description', 'sertifikat_description', 'rules', 'instructions'];

    protected $fillable = [
        'training_id', 'title', 'description', 'sertifikat_description', 'category', 'passing_score', 'duration_minutes', 'start_date', 'end_date',
        'rules', 'instructions', 'hashtags', 'time_warning_minutes', 'max_attempts',
        'randomize_questions', 'randomize_choices', 'show_results_immediately', 
        'show_correct_answers', 'show_explanations', 'allow_tab_switching', 'track_tab_changes',
        'status', 'exam_question_count', 'is_required', 'auto_submit'
    ];

    protected $casts = [
        'title' => 'array',
        'description' => 'array',
        'sertifikat_description' => 'array',
        'rules' => 'array',
        'instructions' => 'array',
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
        'is_required' => 'boolean',
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


