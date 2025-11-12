<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'training_id',
        'rating',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    /**
     * Get the user that gave this rating
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the training that was rated
     */
    public function training()
    {
        return $this->belongsTo(Training::class);
    }
}
