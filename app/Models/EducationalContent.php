<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationalContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'seo',
        'created_by',
        'image_path',
        'title',
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
    ];

    protected $casts = [
        'seo' => 'array',
        'media_files' => 'array',
        'documents' => 'array',
        'send_to_our_user' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}



