<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'exam_id', 'registration_date', 'status', 'score', 'started_at', 'finished_at', 'certificate_id'
    ];

    protected $casts = [
        'registration_date' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function certificate()
    {
        return $this->belongsTo(Certificate::class);
    }
}


