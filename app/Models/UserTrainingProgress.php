<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTrainingProgress extends Model
{
    use HasFactory;

    protected $table = 'user_training_progress';

    protected $fillable = [
        'user_id', 'training_id', 'module_id', 'lesson_id', 'status', 'last_accessed', 'completed_at', 'notes', 'time_spent'
    ];

    protected $casts = [
        'last_accessed' => 'datetime',
        'completed_at' => 'datetime',
        'time_spent' => 'integer',
    ];

    /**
     * Get the user that owns the progress.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the training that this progress belongs to.
     */
    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    /**
     * Get the module that this progress belongs to.
     */
    public function module()
    {
        return $this->belongsTo(TrainingModule::class, 'module_id');
    }

    /**
     * Get the lesson that this progress belongs to.
     */
    public function lesson()
    {
        return $this->belongsTo(TrainingLesson::class, 'lesson_id');
    }
}


