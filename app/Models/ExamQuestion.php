<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTranslations;

class ExamQuestion extends Model
{
    use HasFactory, HasTranslations;

    protected $translatable = ['question_text', 'explanation'];

    protected $fillable = [
        'exam_id', 
        'question_text', 
        'question_media',
        'explanation',
        'question_type',
        'difficulty',
        'points',
        'is_required',
        'sequence',
        'metadata'
    ];

    protected $casts = [
        'question_text' => 'array',
        'explanation' => 'array',
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
        try {
            if ($this->question_type === 'single_choice') {
                $choiceId = $answer['choice_id'] ?? null;
                if (!$choiceId) return 0;
                
                // Use already loaded choices from relationship to avoid query
                $choice = $this->choices->where('id', $choiceId)->first();
                return $choice && $choice->is_correct ? ($choice->points ?: $this->points) : 0;
            }

            if ($this->question_type === 'multiple_choice') {
                $selectedChoiceIds = collect($answer['choice_ids'] ?? []);
                if ($selectedChoiceIds->isEmpty()) return 0;
                
                // Use already loaded choices from relationship
                $allChoices = $this->choices;
                $correctChoiceIds = $allChoices->where('is_correct', true)->pluck('id');
                
                // Simple scoring: if all correct choices selected and no incorrect ones
                $correctSelected = $selectedChoiceIds->intersect($correctChoiceIds);
                $incorrectSelected = $selectedChoiceIds->diff($correctChoiceIds);
                
                // All correct selected and no incorrect selected
                if ($correctSelected->count() === $correctChoiceIds->count() && $incorrectSelected->isEmpty()) {
                    return $this->points;
                }
                
                return 0;
            }

            if ($this->question_type === 'true_false') {
                $choiceId = $answer['choice_id'] ?? null;
                if (!$choiceId) return 0;
                
                $choice = $this->choices->where('id', $choiceId)->first();
                return $choice && $choice->is_correct ? ($choice->points ?: $this->points) : 0;
            }

            if ($this->question_type === 'text') {
                // For text questions, return null to indicate manual grading needed
                $answerText = trim($answer['answer_text'] ?? '');
                return !empty($answerText) ? null : 0; // null means needs manual grading
            }

            return 0;
        } catch (\Exception $e) {
            \Log::error('Error in calculatePoints for question ' . $this->id . ': ' . $e->getMessage());
            return 0; // Return 0 points if calculation fails
        }
    }

    /**
     * Check if an answer is correct (for new scoring system)
     */
    public function isAnswerCorrect($answer)
    {
        try {
            if ($this->question_type === 'single_choice') {
                $choiceId = $answer['choice_id'] ?? null;
                
                // If choice_id is not provided, try to find by answer_text
                if (!$choiceId && isset($answer['answer_text'])) {
                    $choice = $this->choices->where('choice_text', $answer['answer_text'])->first();
                    return $choice && $choice->is_correct;
                }
                
                if (!$choiceId) return false;
                
                $choice = $this->choices->where('id', $choiceId)->first();
                return $choice && $choice->is_correct;
            }

            if ($this->question_type === 'multiple_choice') {
                // Handle both choice_ids array and single choice_id
                $choiceIds = $answer['choice_ids'] ?? [];
                if (empty($choiceIds) && isset($answer['choice_id'])) {
                    $choiceIds = [$answer['choice_id']];
                }
                
                // If choice_ids is still empty, try to find by answer_text
                if (empty($choiceIds) && isset($answer['answer_text'])) {
                    $choice = $this->choices->where('choice_text', $answer['answer_text'])->first();
                    if ($choice) {
                        $choiceIds = [$choice->id];
                    }
                }
                
                if (empty($choiceIds)) return false;
                
                $correctChoices = $this->choices->where('is_correct', true)->pluck('id')->toArray();
                $selectedChoices = $choiceIds;
                
                // Check if all correct choices are selected and no incorrect ones
                return count($correctChoices) === count($selectedChoices) && 
                       empty(array_diff($correctChoices, $selectedChoices));
            }

            if ($this->question_type === 'true_false') {
                $choiceId = $answer['choice_id'] ?? null;
                if (!$choiceId) return false;
                
                $choice = $this->choices->where('id', $choiceId)->first();
                return $choice && $choice->is_correct;
            }

            if ($this->question_type === 'text') {
                // Text questions always return false for auto-grading
                // They will be graded manually
                return false;
            }

            return false;
        } catch (\Exception $e) {
            \Log::error('Error in isAnswerCorrect for question ' . $this->id . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if question needs manual grading
     */
    public function needsManualGrading()
    {
        return $this->question_type === 'text';
    }
}


