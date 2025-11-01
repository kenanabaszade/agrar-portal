<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ForumQuestionView extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'user_id',
        'ip_address',
    ];

    public function question()
    {
        return $this->belongsTo(ForumQuestion::class, 'question_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
