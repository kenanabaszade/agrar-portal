<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_id', 'title', 'description', 'category', 'passing_score', 'duration_minutes', 'start_date', 'end_date'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'passing_score' => 'integer',
        'duration_minutes' => 'integer',
    ];

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function questions()
    {
        return $this->hasMany(ExamQuestion::class);
    }

    public function registrations()
    {
        return $this->hasMany(ExamRegistration::class);
    }
}


