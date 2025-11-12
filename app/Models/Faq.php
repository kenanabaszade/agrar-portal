<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasTranslations;

class Faq extends Model
{
    use HasTranslations;

    protected $translatable = ['question', 'answer'];

    protected $fillable = [
        'question',
        'answer',
        'category',
        'created_by',
        'helpful_count',
        'is_active',
    ];

    protected $casts = [
        'question' => 'array',
        'answer' => 'array',
        'is_active' => 'boolean',
        'helpful_count' => 'integer',
    ];

    /**
     * Get the user who created this FAQ
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include active FAQs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to search in question and answer
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('question', 'like', "%{$search}%")
              ->orWhere('answer', 'like', "%{$search}%");
        });
    }
}
