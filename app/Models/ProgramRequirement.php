<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramRequirement extends Model
{
    protected $fillable = [
        'internship_program_id',
        'requirement',
        'order',
    ];

    /**
     * Get the internship program that owns the requirement
     */
    public function internshipProgram(): BelongsTo
    {
        return $this->belongsTo(InternshipProgram::class);
    }
}
