<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForumPollVote extends Model
{
    use HasFactory;

    protected $fillable = ['question_id', 'user_id', 'option'];

    public function question()
    {
        return $this->belongsTo(ForumQuestion::class, 'question_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}



