<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTranslations;

class ExamChoice extends Model
{
    use HasFactory, HasTranslations;

    protected $translatable = ['choice_text', 'explanation'];

    protected $fillable = [
        'question_id', 
        'choice_text', 
        'choice_media',
        'explanation',
        'is_correct', 
        'points',
        'metadata'
    ];

    protected $casts = [
        'choice_text' => 'array',
        'explanation' => 'array',
        'is_correct' => 'boolean',
        'choice_media' => 'array',
        'metadata' => 'array',
        'points' => 'integer',
    ];

    public function question()
    {
        return $this->belongsTo(ExamQuestion::class, 'question_id');
    }

    public function userAnswers()
    {
        return $this->hasMany(ExamUserAnswer::class, 'choice_id');
    }

    /**
     * Get choice media by type
     */
    public function getChoiceMediaByType($type)
    {
        if (!$this->choice_media) {
            return collect();
        }

        return collect($this->choice_media)->filter(function ($media) use ($type) {
            return $media['type'] === $type;
        });
    }

    /**
     * Check if choice has media
     */
    public function hasMedia()
    {
        return !empty($this->choice_media);
    }

    /**
     * Get choice for exam (without correct answer info)
     */
    public function getForExam()
    {
        $choiceData = $this->toArray();
        unset($choiceData['is_correct'], $choiceData['explanation']);
        return $choiceData;
    }

    /**
     * Get choice for review (includes all info)
     */
    public function getForReview()
    {
        return $this->toArray();
    }
}


