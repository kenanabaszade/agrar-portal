<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramGoal extends Model
{
    protected $fillable = [
        'internship_program_id',
        'goal',
        'order',
    ];

    /**
     * Get the internship program that owns the goal
     */
    public function internshipProgram(): BelongsTo
    {
        return $this->belongsTo(InternshipProgram::class);
    }
}
