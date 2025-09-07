<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingRegistration extends Model
{
    protected $fillable = [
        'meeting_id',
        'user_id',
        'status',
        'registered_at',
        'attended_at',
        'notes',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'attended_at' => 'datetime',
    ];

    /**
     * Get the meeting this registration belongs to
     */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    /**
     * Get the user who registered
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark user as attended
     */
    public function markAsAttended(): void
    {
        $this->update([
            'status' => 'attended',
            'attended_at' => now(),
        ]);
    }

    /**
     * Cancel registration
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }
}
