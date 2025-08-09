<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTrainingProgress extends Model
{
    use HasFactory;

    protected $table = 'user_training_progress';

    protected $fillable = [
        'user_id', 'training_id', 'module_id', 'lesson_id', 'status', 'last_accessed', 'completed_at'
    ];

    protected $casts = [
        'last_accessed' => 'datetime',
        'completed_at' => 'datetime',
    ];
}


