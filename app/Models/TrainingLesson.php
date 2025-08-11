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
        'lesson_type',
        'duration_minutes',
        'content',
        'description',
        'video_url',
        'pdf_url',
        'media_files',
        'sequence',
        'status',
        'is_required',
        'min_completion_time',
        'metadata',
    ];

    protected $casts = [
        'media_files' => 'array',
        'metadata' => 'array',
        'is_required' => 'boolean',
        'min_completion_time' => 'integer',
        'duration_minutes' => 'integer',
    ];

    public function module()
    {
        return $this->belongsTo(TrainingModule::class, 'module_id');
    }

    public function progress()
    {
        return $this->hasMany(UserTrainingProgress::class, 'lesson_id');
    }

    /**
     * Get lesson duration in human readable format
     */
    public function getDurationAttribute()
    {
        if (!$this->duration_minutes) {
            return null;
        }

        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'm';
        }

        return $minutes . 'm';
    }

    /**
     * Get media files by type
     */
    public function getMediaByType($type)
    {
        if (!$this->media_files) {
            return collect();
        }

        return collect($this->media_files)->filter(function ($media) use ($type) {
            return $media['type'] === $type;
        });
    }

    /**
     * Check if lesson is published
     */
    public function isPublished()
    {
        return $this->status === 'published';
    }

    /**
     * Get lesson content with media
     */
    public function getFullContent()
    {
        $content = [
            'text' => $this->content,
            'description' => $this->description,
            'media' => $this->media_files ?? [],
            'video_url' => $this->video_url,
            'pdf_url' => $this->pdf_url,
        ];

        return array_filter($content);
    }
}


