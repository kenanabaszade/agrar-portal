<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramModule extends Model
{
    protected $fillable = [
        'internship_program_id',
        'title',
        'description',
        'order',
    ];

    /**
     * Get the internship program that owns the module
     */
    public function internshipProgram(): BelongsTo
    {
        return $this->belongsTo(InternshipProgram::class);
    }
}
