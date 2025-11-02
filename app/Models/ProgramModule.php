<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasTranslations;

class ProgramModule extends Model
{
    use HasTranslations;

    protected $translatable = ['title', 'description'];

    protected $fillable = [
        'internship_program_id',
        'title',
        'description',
        'order',
    ];

    protected $casts = [
        'title' => 'array',
        'description' => 'array',
    ];

    /**
     * Get the internship program that owns the module
     */
    public function internshipProgram(): BelongsTo
    {
        return $this->belongsTo(InternshipProgram::class);
    }
}
