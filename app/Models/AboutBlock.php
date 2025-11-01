<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutBlock extends Model
{
    protected $table = 'about_blocks';

    protected $fillable = [
        'type',
        'order',
        'data',
        'styles',
    ];

    protected $casts = [
        'data' => 'array',
        'styles' => 'array',
        'order' => 'integer',
    ];

    /**
     * Scope a query to order by order field
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }
}
