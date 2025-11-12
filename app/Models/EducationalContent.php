<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTranslations;

class EducationalContent extends Model
{
    use HasFactory, HasTranslations;

    protected $translatable = ['title', 'short_description', 'body_html', 'description', 'announcement_title', 'announcement_body'];

    protected $fillable = [
        'type',
        'seo',
        'created_by',
        'image_path',
        'title',
        'short_description',
        'body_html',
        'sequence',
        'hashtags',
        'category',
        'send_to_our_user',
        'media_files',
        'description',
        'documents',
        'announcement_title',
        'announcement_body',
        'likes_count',
        'views_count',
    ];

    protected $casts = [
        'title' => 'array',
        'short_description' => 'array',
        'body_html' => 'array',
        'description' => 'array',
        'announcement_title' => 'array',
        'announcement_body' => 'array',
        'seo' => 'array',
        'media_files' => 'array',
        'documents' => 'array',
        'send_to_our_user' => 'boolean',
        'likes_count' => 'integer',
        'views_count' => 'integer',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function likes()
    {
        return $this->hasMany(EducationalContentLike::class);
    }

    public function savedByUsers()
    {
        return $this->hasMany(SavedEducationalContent::class);
    }

    public function isLikedBy($userId)
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }

    public function isSavedBy($userId)
    {
        return $this->savedByUsers()->where('user_id', $userId)->exists();
    }
}



