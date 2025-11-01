<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationalContentLike extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'educational_content_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function educationalContent()
    {
        return $this->belongsTo(EducationalContent::class);
    }
}
