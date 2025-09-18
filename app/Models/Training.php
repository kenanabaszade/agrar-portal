<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Training extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'category',
        'trainer_id',
        'start_date',
        'end_date',
        'is_online',
    ];

    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function modules()
    {
        return $this->hasMany(TrainingModule::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category', 'name');
    }
}


