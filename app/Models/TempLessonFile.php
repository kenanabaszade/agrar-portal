<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TempLessonFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_code',
        'temp_path',
        'type',
        'filename',
        'size',
        'mime_type',
        'title',
        'description',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        // Auto delete expired files
        static::deleting(function ($tempFile) {
            if (Storage::disk('public')->exists($tempFile->temp_path)) {
                Storage::disk('public')->delete($tempFile->temp_path);
            }
        });
    }

    /**
     * Check if file is expired
     */
    public function isExpired()
    {
        return $this->expires_at->isPast();
    }

    /**
     * Get file URL
     */
    public function getUrlAttribute()
    {
        return Storage::url($this->temp_path);
    }
}