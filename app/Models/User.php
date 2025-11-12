<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'father_name',
        'region',
        'birth_date',
        'gender',
        'how_did_you_hear',
        'email',
        'phone',
        'profile_photo',
        'password_hash',
        'user_type',
        'is_active',
        'two_factor_enabled',
        'otp_code',
        'otp_expires_at',
        'email_verified',
        'email_verified_at',
        'google_access_token',
        'google_refresh_token',
        'google_token_expires_at',
        'last_login_at',
        // Trainer-specific fields
        'trainer_category',
        'trainer_description',
        'experience_years',
        'experience_months',
        'specializations',
        'qualifications',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
        protected function casts(): array
        {
            return [
                'email_verified_at' => 'datetime',
                'otp_expires_at' => 'datetime',
                'google_token_expires_at' => 'datetime',
                'last_login_at' => 'datetime',
                'birth_date' => 'date',
                'is_active' => 'boolean',
                'two_factor_enabled' => 'boolean',
                'email_verified' => 'boolean',
                // Trainer-specific casts
                'trainer_category' => 'array',
                'trainer_description' => 'array',
                'specializations' => 'array',
                'qualifications' => 'array',
                'experience_years' => 'integer',
                'experience_months' => 'integer',
            ];
        }

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['profile_photo_url'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole($roles)
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        // Check user_type first (like the middleware does)
        $userRoleTypes = array_map('strtolower', $roles);
        if (in_array(strtolower((string) $this->user_type), $userRoleTypes, true)) {
            return true;
        }

        // Then check attached roles
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    public function emailChangeRequests()
    {
        return $this->hasMany(EmailChangeRequest::class);
    }

    public function registrations()
    {
        return $this->hasMany(TrainingRegistration::class);
    }

    public function userTrainingProgress()
    {
        return $this->hasMany(UserTrainingProgress::class);
    }

    public function internshipPrograms()
    {
        return $this->hasMany(InternshipProgram::class, 'trainer_id');
    }

    /**
     * Get all trainings created by this trainer
     */
    public function trainings()
    {
        return $this->hasMany(Training::class, 'trainer_id');
    }

    /**
     * Get all exam registrations for this user
     */
    public function examRegistrations()
    {
        return $this->hasMany(ExamRegistration::class);
    }

    /**
     * Get all certificates for this user
     */
    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Get all ratings given by this user
     */
    public function trainingRatings()
    {
        return $this->hasMany(\App\Models\TrainingRating::class);
    }

    /**
     * Get trainer registrations (registrations for trainings created by this trainer)
     */
    public function trainerRegistrations()
    {
        return TrainingRegistration::whereHas('training', function ($q) {
            $q->where('trainer_id', $this->id);
        });
    }

    /**
     * Get trainer ratings (ratings for trainings created by this trainer)
     */
    public function trainerRatings()
    {
        return \App\Models\TrainingRating::whereHas('training', function ($q) {
            $q->where('trainer_id', $this->id);
        });
    }

    /**
     * Get forum questions created by this user
     */
    public function forumQuestions()
    {
        return $this->hasMany(ForumQuestion::class);
    }

    /**
     * Get forum answers created by this user
     */
    public function forumAnswers()
    {
        return $this->hasMany(ForumAnswer::class);
    }

    /**
     * Get meetings where this user is the trainer
     */
    public function meetingsAsTrainer()
    {
        return $this->hasMany(Meeting::class, 'trainer_id');
    }

    /**
     * Get meetings created by this user
     */
    public function meetingsAsCreator()
    {
        return $this->hasMany(Meeting::class, 'created_by');
    }

    /**
     * Get trainer's average rating from all their trainings
     */
    public function getTrainerAverageRatingAttribute()
    {
        if ($this->user_type !== 'trainer') {
            return null;
        }

        // Get all ratings for trainings created by this trainer
        $ratings = \App\Models\TrainingRating::whereHas('training', function ($query) {
            $query->where('trainer_id', $this->id);
        })->avg('rating');

        return $ratings ? round((float) $ratings, 2) : null;
    }

    /**
     * Get trainer's total ratings count
     */
    public function getTrainerRatingsCountAttribute()
    {
        if ($this->user_type !== 'trainer') {
            return 0;
        }

        return \App\Models\TrainingRating::whereHas('training', function ($query) {
            $query->where('trainer_id', $this->id);
        })->count();
    }

    /**
     * Get formatted experience string (e.g., "3 il 5 ay", "3 il", "5 ay")
     */
    public function getExperienceFormattedAttribute()
    {
        $years = (int) ($this->experience_years ?? 0);
        $months = (int) ($this->experience_months ?? 0);

        if ($years > 0 && $months > 0) {
            return "{$years} il {$months} ay";
        } elseif ($years > 0) {
            return "{$years} il";
        } elseif ($months > 0) {
            return "{$months} ay";
        }

        return null;
    }

    /**
     * Get the full URL for the profile photo
     */
    public function getProfilePhotoUrlAttribute()
    {
        if ($this->profile_photo) {
            return asset('storage/profile_photos/' . $this->profile_photo);
        }
        return null;
    }
}
