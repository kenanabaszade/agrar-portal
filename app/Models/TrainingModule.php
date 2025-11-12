<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTranslations;

class TrainingModule extends Model
{
    use HasFactory, HasTranslations;

    protected $translatable = ['title'];

    protected $fillable = [
        'training_id',
        'title',
        'sequence',
    ];

    protected $casts = [
        'title' => 'array',
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


