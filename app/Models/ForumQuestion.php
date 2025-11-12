<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTranslations;

class ForumQuestion extends Model
{
    use HasFactory, HasTranslations;

    protected $translatable = ['title', 'summary', 'body'];

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
        'is_public',
        'views',
        'likes_count',
    ];

    protected $casts = [
        'title' => 'array',
        'summary' => 'array',
        'body' => 'array',
        'tags' => 'array',
        'poll_options' => 'array',
        'is_pinned' => 'boolean',
        'allow_comments' => 'boolean',
        'is_open' => 'boolean',
        'is_public' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function answers()
    {
        return $this->hasMany(ForumAnswer::class, 'question_id');
    }

    public function pollVotes()
    {
        return $this->hasMany(ForumPollVote::class, 'question_id');
    }

    public function questionViews()
    {
        return $this->hasMany(ForumQuestionView::class, 'question_id');
    }

    public function likes()
    {
        return $this->hasMany(ForumQuestionLike::class, 'question_id');
    }

    public function isLikedBy($userId)
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }

    /**
     * Benzersiz istifadəçilərin sayı (neçə nəfər baxıb)
     */
    public function getUniqueViewersCountAttribute()
    {
        return $this->questionViews()
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');
    }
}


