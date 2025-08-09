<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_id',
        'title',
        'sequence',
    ];

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function lessons()
    {
        return $this->hasMany(TrainingLesson::class, 'module_id');
    }
}


