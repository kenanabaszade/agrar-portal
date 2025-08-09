<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['user_id', 'type', 'title', 'message', 'is_read', 'sent_at', 'created_at'];

    protected $casts = [
        'is_read' => 'boolean',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}


