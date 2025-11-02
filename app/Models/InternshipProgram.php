<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasTranslations;

class InternshipProgram extends Model
{
    use HasTranslations;

    protected $translatable = ['title', 'description', 'location', 'instructor_description', 'cv_requirements'];

    protected $fillable = [
        'trainer_id',
        'trainer_mail',
        'title',
        'description',
        'image_url',
        'is_featured',
        'registration_status',
        'category',
        'duration_weeks',
        'start_date',
        'end_date',
        'last_register_date',
        'location',
        'current_enrollment',
        'max_capacity',
        'instructor_name',
        'instructor_title',
        'instructor_initials',
        'instructor_photo_url',
        'instructor_description',
        'instructor_rating',
        'details_link',
        'cv_requirements',
        'is_active',
    ];

    protected $casts = [
        'title' => 'array',
        'description' => 'array',
        'location' => 'array',
        'instructor_description' => 'array',
        'cv_requirements' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'last_register_date' => 'date',
        'instructor_rating' => 'decimal:1',
    ];

    /**
     * Get the trainer for the internship program
     */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    /**
     * Get the modules for the internship program
     */
    public function modules(): HasMany
    {
        return $this->hasMany(ProgramModule::class)->orderBy('order');
    }

    /**
     * Get the requirements for the internship program
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(ProgramRequirement::class)->orderBy('order');
    }

    /**
     * Get the goals for the internship program
     */
    public function goals(): HasMany
    {
        return $this->hasMany(ProgramGoal::class)->orderBy('order');
    }

    /**
     * Get the applications for the internship program
     */
    public function applications(): HasMany
    {
        return $this->hasMany(InternshipApplication::class);
    }

    /**
     * Get the enrolled users for the internship program
     */
    public function enrolledUsers(): HasMany
    {
        return $this->hasMany(InternshipApplication::class)->where('status', 'accepted');
    }

    /**
     * Get the enrollment percentage
     */
    public function getEnrollmentPercentageAttribute(): float
    {
        if ($this->max_capacity == 0) {
            return 0;
        }
        return round(($this->current_enrollment / $this->max_capacity) * 100, 1);
    }

    /**
     * Check if program is full
     */
    public function getIsFullAttribute(): bool
    {
        return $this->current_enrollment >= $this->max_capacity;
    }

    /**
     * Get the instructor's full name with title
     */
    public function getInstructorFullNameAttribute(): string
    {
        return $this->instructor_name . ' - ' . $this->instructor_title;
    }

    /**
     * Get trainer information (if trainer is assigned)
     */
    public function getTrainerInfoAttribute(): ?array
    {
        if (!$this->trainer) {
            return null;
        }

        return [
            'id' => $this->trainer->id,
            'name' => $this->trainer->first_name . ' ' . $this->trainer->last_name,
            'email' => $this->trainer->email,
            'phone' => $this->trainer->phone,
            'profile_photo' => $this->trainer->profile_photo,
            'profile_photo_url' => $this->trainer->profile_photo_url,
        ];
    }

    /**
     * Get instructor information (either from trainer or manual entry)
     */
    public function getInstructorInfoAttribute(): array
    {
        if ($this->trainer) {
            return [
                'name' => $this->trainer->first_name . ' ' . $this->trainer->last_name,
                'title' => $this->instructor_title,
                'initials' => $this->getInitials($this->trainer->first_name, $this->trainer->last_name),
                'photo_url' => $this->trainer->profile_photo_url,
                'description' => $this->instructor_description,
                'rating' => $this->instructor_rating,
                'is_trainer' => true,
                'trainer_id' => $this->trainer->id,
            ];
        }

        return [
            'name' => $this->instructor_name,
            'title' => $this->instructor_title,
            'initials' => $this->instructor_initials,
            'photo_url' => $this->instructor_photo_url,
            'description' => $this->instructor_description,
            'rating' => $this->instructor_rating,
            'is_trainer' => false,
            'trainer_id' => null,
        ];
    }

    /**
     * Generate initials from first and last name
     */
    private function getInitials(string $firstName, string $lastName): string
    {
        return strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
    }
}
