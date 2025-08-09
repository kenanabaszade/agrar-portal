<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'training_id', 'registration_date', 'status', 'certificate_id'
    ];

    protected $casts = [
        'registration_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function certificate()
    {
        return $this->belongsTo(Certificate::class);
    }
}


