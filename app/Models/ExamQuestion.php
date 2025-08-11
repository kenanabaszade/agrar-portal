<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id', 
        'question_text', 
        'question_media',
        'explanation',
        'question_type', 
        'points',
        'is_required',
        'sequence',
        'metadata'
    ];

    protected $casts = [
        'question_media' => 'array',
        'metadata' => 'array',
        'is_required' => 'boolean',
        'points' => 'integer',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function choices()
    {
        return $this->hasMany(ExamChoice::class, 'question_id');
    }

    public function userAnswers()
    {
        return $this->hasMany(ExamUserAnswer::class, 'question_id');
    }

    /**
     * Get question media by type
     */
    public function getQuestionMediaByType($type)
    {
        if (!$this->question_media) {
            return collect();
        }

        return collect($this->question_media)->filter(function ($media) use ($type) {
            return $media['type'] === $type;
        });
    }

    /**
     * Get all media for this question (question + choices)
     */
    public function getAllMedia()
    {
        $media = [
            'question' => $this->question_media ?? [],
            'choices' => []
        ];

        foreach ($this->choices as $choice) {
            if ($choice->choice_media) {
                $media['choices'][$choice->id] = $choice->choice_media;
            }
        }

        return $media;
    }

    /**
     * Check if question has any media
     */
    public function hasMedia()
    {
        return !empty($this->question_media) || $this->choices->some(function ($choice) {
            return !empty($choice->choice_media);
        });
    }

    /**
     * Get question with choices for exam taking (without correct answers)
     */
    public function getForExam()
    {
        $questionData = $this->toArray();
        unset($questionData['explanation']); // Hide explanation during exam

        // Hide correct answers and explanations from choices
        $choices = $this->choices->map(function ($choice) {
            $choiceData = $choice->toArray();
            unset($choiceData['is_correct'], $choiceData['explanation']);
            return $choiceData;
        });

        $questionData['choices'] = $choices;

        return $questionData;
    }

    /**
     * Get question with answers for review (includes explanations)
     */
    public function getForReview()
    {
        return $this->load('choices');
    }

    /**
     * Calculate points for a given answer
     */
    public function calculatePoints($answer)
    {
        if ($this->question_type === 'single_choice') {
            $choice = $this->choices->find($answer['choice_id'] ?? null);
            return $choice && $choice->is_correct ? $choice->points : 0;
        }

        if ($this->question_type === 'multiple_choice') {
            $selectedChoices = collect($answer['choice_ids'] ?? []);
            $correctChoices = $this->choices->where('is_correct', true);
            
            // All correct choices must be selected and no incorrect ones
            $allCorrectSelected = $correctChoices->every(function ($choice) use ($selectedChoices) {
                return $selectedChoices->contains($choice->id);
            });
            
            $noIncorrectSelected = $this->choices->where('is_correct', false)->every(function ($choice) use ($selectedChoices) {
                return !$selectedChoices->contains($choice->id);
            });

            return ($allCorrectSelected && $noIncorrectSelected) ? $this->points : 0;
        }

        if ($this->question_type === 'text') {
            // For text questions, basic validation (non-empty)
            return !empty($answer['answer_text']) ? $this->points : 0;
        }

        return 0;
    }
}


