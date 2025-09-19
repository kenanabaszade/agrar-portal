<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForumQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'summary',
        'body',
        'status',
        'question_type',
        'poll_options',
        'tags',
        'category',
        'difficulty',
        'is_pinned',
        'allow_comments',
        'is_open',
    ];

    protected $casts = [
        'tags' => 'array',
        'poll_options' => 'array',
        'is_pinned' => 'boolean',
        'allow_comments' => 'boolean',
        'is_open' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function answers()
    {
        return $this->hasMany(ForumAnswer::class, 'question_id');
    }
}


