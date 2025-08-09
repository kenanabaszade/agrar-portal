<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingLesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'title',
        'content',
        'video_url',
        'pdf_url',
        'sequence',
    ];

    public function module()
    {
        return $this->belongsTo(TrainingModule::class, 'module_id');
    }
}


