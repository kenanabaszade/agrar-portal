<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasTranslations;

class ProgramRequirement extends Model
{
    use HasTranslations;

    protected $translatable = ['requirement'];

    protected $fillable = [
        'internship_program_id',
        'requirement',
        'order',
    ];

    protected $casts = [
        'requirement' => 'array',
    ];

    /**
     * Get the internship program that owns the requirement
     */
    public function internshipProgram(): BelongsTo
    {
        return $this->belongsTo(InternshipProgram::class);
    }
}
