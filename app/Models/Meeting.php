<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Meeting extends Model
{
    protected $fillable = [
        'title',
        'description',
        'google_event_id',
        'google_meet_link',
        'meeting_id',
        'meeting_password',
        'start_time',
        'end_time',
        'timezone',
        'max_attendees',
        'is_recurring',
        'recurrence_rules',
        'status',
        'created_by',
        'training_id',
        'attendees',
        'google_metadata',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_recurring' => 'boolean',
        'recurrence_rules' => 'array',
        'attendees' => 'array',
        'google_metadata' => 'array',
    ];

    /**
     * Get the user who created this meeting
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the associated training (if any)
     */
    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    /**
     * Get meeting registrations
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(MeetingRegistration::class);
    }

    /**
     * Check if meeting is currently live
     */
    public function isLive(): bool
    {
        $now = Carbon::now($this->timezone);
        return $this->status === 'live' || 
               ($this->start_time <= $now && $this->end_time >= $now && $this->status === 'scheduled');
    }

    /**
     * Check if meeting has ended
     */
    public function hasEnded(): bool
    {
        return $this->status === 'ended' || Carbon::now($this->timezone)->gt($this->end_time);
    }

    /**
     * Check if meeting is upcoming
     */
    public function isUpcoming(): bool
    {
        return $this->status === 'scheduled' && Carbon::now($this->timezone)->lt($this->start_time);
    }

    /**
     * Get formatted duration
     */
    public function getDurationAttribute(): string
    {
        $duration = $this->start_time->diffInMinutes($this->end_time);
        $hours = floor($duration / 60);
        $minutes = $duration % 60;
        
        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'm';
        }
        return $minutes . 'm';
    }

    /**
     * Get attendee count
     */
    public function getAttendeeCountAttribute(): int
    {
        return $this->registrations()->count();
    }

    /**
     * Check if user is registered for this meeting
     */
    public function isUserRegistered(int $userId): bool
    {
        return $this->registrations()->where('user_id', $userId)->exists();
    }

    /**
     * Check if meeting has available spots
     */
    public function hasAvailableSpots(): bool
    {
        return $this->attendee_count < $this->max_attendees;
    }

    /**
     * Scope for upcoming meetings
     */
    public function scopeUpcoming($query)
    {
        return $query->where('status', 'scheduled')
                    ->where('start_time', '>', now());
    }

    /**
     * Scope for live meetings
     */
    public function scopeLive($query)
    {
        return $query->where('status', 'live')
                    ->orWhere(function ($q) {
                        $q->where('status', 'scheduled')
                          ->where('start_time', '<=', now())
                          ->where('end_time', '>=', now());
                    });
    }

    /**
     * Scope for past meetings
     */
    public function scopePast($query)
    {
        return $query->where('end_time', '<', now())
                    ->orWhere('status', 'ended');
    }
}
