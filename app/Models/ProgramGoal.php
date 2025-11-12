<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasTranslations;

class ProgramGoal extends Model
{
    use HasTranslations;

    protected $translatable = ['goal'];

    protected $fillable = [
        'internship_program_id',
        'goal',
        'order',
    ];

    protected $casts = [
        'goal' => 'array',
    ];

    /**
     * Get the internship program that owns the goal
     */
    public function internshipProgram(): BelongsTo
    {
        return $this->belongsTo(InternshipProgram::class);
    }
}
