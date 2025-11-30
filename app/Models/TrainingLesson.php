<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTranslations;

class TrainingLesson extends Model
{
    use HasFactory, HasTranslations;

    protected $translatable = ['title', 'content', 'description'];

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
        'title' => 'array',
        'content' => 'array',
        'description' => 'array',
        'media_files' => 'array',
        'metadata' => 'array',
        'is_required' => 'boolean',
        'min_completion_time' => 'integer',
        'duration_minutes' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($lesson) {
            // Delete all media files when lesson is being deleted
            $mediaFiles = $lesson->media_files ?? [];
            
            foreach ($mediaFiles as $mediaFile) {
                if (isset($mediaFile['url'])) {
                    $url = $mediaFile['url'];
                    $path = str_replace(\Storage::url(''), '', $url);
                    
                    if (\Storage::disk('public')->exists($path)) {
                        \Storage::disk('public')->delete($path);
                    }
                }
            }
            
            // Delete the lesson directory
            $lessonDir = 'lessons/' . $lesson->id;
            if (\Storage::disk('public')->exists($lessonDir)) {
                \Storage::disk('public')->deleteDirectory($lessonDir);
            }
        });
    }

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
        // Transform media files URLs to protected endpoints
        $mediaFiles = $this->transformMediaUrls($this->media_files ?? []);
        
        $content = [
            'text' => $this->content,
            'description' => $this->description,
            'media' => $mediaFiles,
            'video_url' => $this->video_url,
            'pdf_url' => $this->pdf_url,
        ];

        return array_filter($content);
    }
    
    /**
     * Transform media file URLs to protected endpoints
     * Converts /storage/lessons/{id}/file.mp4 to protected API endpoint
     */
    public function transformMediaUrls($mediaFiles)
    {
        if (empty($mediaFiles) || !is_array($mediaFiles)) {
            return $mediaFiles;
        }
        
        return collect($mediaFiles)->map(function ($mediaFile) {
            // If URL is already a protected endpoint, keep it and preserve path
            if (isset($mediaFile['url']) && (strpos($mediaFile['url'], '/api/v1/modules/') === 0 || strpos($mediaFile['url'], 'http') === 0 && strpos($mediaFile['url'], '/api/v1/modules/') !== false)) {
                // Extract path from URL if path field doesn't exist
                if (!isset($mediaFile['path']) && preg_match('/[?&]path=([^&]+)/', $mediaFile['url'], $matches)) {
                    $mediaFile['path'] = urldecode($matches[1]);
                }
                return $mediaFile;
            }
            
            // If URL is /storage/lessons/{id}/file.mp4, convert to protected endpoint
            if (isset($mediaFile['url']) && strpos($mediaFile['url'], '/storage/lessons/') === 0) {
                // Extract path from URL
                $path = str_replace('/storage/', '', $mediaFile['url']);
                
                // Extract lesson ID from path (lessons/{id}/file.mp4)
                if (preg_match('/^lessons\/(\d+)\//', $path, $matches)) {
                    $lessonId = $matches[1];
                    $moduleId = $this->module_id;
                    
                    // Convert to protected endpoint URL
                    $mediaFile['url'] = route('lesson.media.download', [
                        'module' => $moduleId,
                        'lesson' => $lessonId,
                        'path' => $path
                    ]);
                    
                    // Preserve path field
                    if (!isset($mediaFile['path'])) {
                        $mediaFile['path'] = $path;
                    }
                }
            }
            
            // If path exists but URL doesn't, create protected URL
            if (isset($mediaFile['path']) && !isset($mediaFile['url'])) {
                $path = $mediaFile['path'];
                if (preg_match('/^lessons\/(\d+)\//', $path, $matches)) {
                    $lessonId = $matches[1];
                    $moduleId = $this->module_id;
                    
                    $mediaFile['url'] = route('lesson.media.download', [
                        'module' => $moduleId,
                        'lesson' => $lessonId,
                        'path' => $path
                    ]);
                }
            }
            
            return $mediaFile;
        })->toArray();
    }
    
    /**
     * Accessor to automatically transform media URLs when lesson is loaded
     * Note: This may not work with JSON casting, so we also transform in controllers
     */
    public function getMediaFilesAttribute($value)
    {
        // JSON cast already decoded the value
        $mediaFiles = is_string($value) ? json_decode($value, true) : $value;
        
        if (!is_array($mediaFiles) || empty($mediaFiles)) {
            return $mediaFiles;
        }
        
        // If already transformed (has protected URL), return as is
        $hasProtectedUrl = collect($mediaFiles)->contains(function ($file) {
            return isset($file['url']) && (
                strpos($file['url'], '/api/v1/modules/') === 0 ||
                strpos($file['url'], 'http') === 0 && strpos($file['url'], '/api/v1/modules/') !== false
            );
        });
        
        if ($hasProtectedUrl) {
            return $mediaFiles;
        }
        
        // Transform URLs if needed
        return $this->transformMediaUrls($mediaFiles);
    }
}


