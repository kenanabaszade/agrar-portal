<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTranslations;

class Notification extends Model
{
    use HasFactory, HasTranslations;

    protected $translatable = ['title', 'message'];

    public $timestamps = false;

    protected $fillable = ['user_id', 'type', 'title', 'message', 'data', 'channels', 'is_read', 'sent_at', 'created_at'];

    protected $casts = [
        'title' => 'array',
        'message' => 'array',
        'data' => 'array',
        'channels' => 'array',
        'is_read' => 'boolean',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}


