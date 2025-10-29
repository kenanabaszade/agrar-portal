<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Training extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'category',
        'trainer_id',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'timezone',
        'is_online',
        'type',
        'online_details',
        'offline_details',
        'media_files',
        'has_certificate',
        'require_email_verification',
        'has_exam',
        'exam_id',
        'exam_required',
        'min_exam_score',
        'status',
        'difficulty',
        // Google Meet integration fields
        'google_meet_link',
        'google_event_id',
        'meeting_id',
        // Recurring meeting fields
        'is_recurring',
        'recurrence_frequency',
        'recurrence_end_date',
    ];

    protected $casts = [
        'media_files' => 'array',
        'online_details' => 'array',
        'offline_details' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_online' => 'boolean',
        'has_certificate' => 'boolean',
        'require_email_verification' => 'boolean',
        'has_exam' => 'boolean',
        'exam_required' => 'boolean',
    ];

    // Validation rules for training type
    public static function getTypeValidationRules()
    {
        return [
            'type' => 'nullable|string|in:online,offline,video,Online,Offline,Video,ONLINE,OFFLINE,VIDEO'
        ];
    }

    protected $attributes = [
        'is_online' => true,
        'has_certificate' => false,
        'has_exam' => false,
        'exam_required' => false,
    ];

    // Relationships
    public function exam()
    {
        return $this->belongsTo(\App\Models\Exam::class);
    }

    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function modules()
    {
        return $this->hasMany(TrainingModule::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category', 'name');
    }

    public function registrations()
    {
        return $this->hasMany(TrainingRegistration::class);
    }

    /**
     * Get banner image URL
     */
    public function getBannerUrlAttribute()
    {
        $mediaFiles = $this->media_files ?? [];
        
        foreach ($mediaFiles as $file) {
            if ($file['type'] === 'banner') {
                return Storage::url($file['path']);
            }
        }
        
        return null;
    }

    /**
     * Get all banner images with full URLs
     */
    public function getBannerImagesAttribute()
    {
        $mediaFiles = $this->media_files ?? [];
        $banners = [];
        
        foreach ($mediaFiles as $file) {
            if ($file['type'] === 'banner') {
                $banners[] = [
                    'path' => $file['path'],
                    'url' => Storage::url($file['path']),
                    'original_name' => $file['original_name'] ?? null,
                    'mime_type' => $file['mime_type'] ?? null,
                    'size' => $file['size'] ?? null,
                    'uploaded_at' => $file['uploaded_at'] ?? null,
                ];
            }
        }
        
        return $banners;
    }

    /**
     * Get all media files - raw data qaytar
     */
    public function getMediaFilesAttribute($value)
    {
        if (!$value) {
            return [];
        }

        // Raw media files qaytar, URL formatlaşdırmasını TrainingController-də edək
        return json_decode($value, true) ?? [];
    }

    /**
     * Get banner image (first media file with type 'banner')
     */
    public function getBannerImageAttribute()
    {
        $mediaFiles = $this->media_files ?? [];
        $banner = collect($mediaFiles)->firstWhere('type', 'banner');
        return $banner ? $banner['url'] ?? null : null;
    }

    /**
     * Get intro video (first media file with type 'intro_video')
     */
    public function getIntroVideoAttribute()
    {
        $mediaFiles = $this->media_files ?? [];
        $video = collect($mediaFiles)->firstWhere('type', 'intro_video');
        return $video ? $video['url'] ?? null : null;
    }

    /**
     * Get general media files (exclude banner and intro_video types)
     */
    public function getGeneralMediaFilesAttribute()
    {
        $mediaFiles = $this->media_files ?? [];
        return collect($mediaFiles)->whereNotIn('type', ['banner', 'intro_video'])->values()->toArray();
    }

    /**
     * Get online details with default structure
     */
    public function getOnlineDetailsAttribute($value)
    {
        if (!$value) {
            return [
                'participant_size' => '',
                'google_meet_link' => ''
            ];
        }
        return json_decode($value, true);
    }

    /**
     * Get offline details with default structure
     */
    public function getOfflineDetailsAttribute($value)
    {
        if (!$value) {
            return [
                'participant_size' => '',
                'address' => '',
                'coordinates' => ''
            ];
        }
        return json_decode($value, true);
    }

    /**
     * Add a media file to the training
     */
    public function addMediaFile($path, $originalName, $mimeType, $size, $type = 'general')
    {
        $mediaFiles = $this->media_files ?? [];
        
        $newFile = [
            'type' => $type, // 'banner', 'intro_video', 'general'
            'path' => $path,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'size' => $size,
            'uploaded_at' => now()->toISOString(),
            'url' => '/storage/' . $path, // Relative URL - TrainingController-də tam URL-ə çevrilir
        ];

        // If it's a banner or intro_video, replace existing one of same type
        if (in_array($type, ['banner', 'intro_video'])) {
            $mediaFiles = collect($mediaFiles)->reject(function ($file) use ($type) {
                return $file['type'] === $type;
            })->toArray();
        }

        $mediaFiles[] = $newFile;
        $this->update(['media_files' => $mediaFiles]);
    }

    /**
     * Remove a media file from the training
     */
    public function removeMediaFile($path)
    {
        $mediaFiles = $this->media_files ?? [];
        
        $mediaFiles = collect($mediaFiles)->filter(function ($file) use ($path) {
            return $file['path'] !== $path;
        })->values()->toArray();

        $this->update(['media_files' => $mediaFiles]);
        
        // Delete the actual file
        if (Storage::exists($path)) {
            Storage::delete($path);
        }
    }

    /**
     * Remove media files by type
     */
    public function removeMediaFilesByType($type)
    {
        $mediaFiles = $this->media_files ?? [];
        $filesToDelete = collect($mediaFiles)->where('type', $type);
        
        // Delete physical files
        foreach ($filesToDelete as $file) {
            if (Storage::exists($file['path'])) {
                Storage::delete($file['path']);
            }
        }

        // Update media_files array
        $mediaFiles = collect($mediaFiles)->where('type', '!=', $type)->values()->toArray();
        $this->update(['media_files' => $mediaFiles]);
    }

    /**
     * Get training duration in days
     */
    public function getDurationDaysAttribute()
    {
        if (!$this->start_date || !$this->end_date) {
            return null;
        }

        return $this->start_date->diffInDays($this->end_date) + 1; // +1 to include both start and end dates
    }

    /**
     * Get training duration in human readable format
     */
    public function getDurationAttribute()
    {
        if (!$this->start_date || !$this->end_date) {
            return null;
        }

        $days = $this->start_date->diffInDays($this->end_date) + 1;
        
        if ($days == 1) {
            return '1 gün';
        } elseif ($days < 7) {
            return $days . ' gün';
        } elseif ($days < 30) {
            $weeks = floor($days / 7);
            $remainingDays = $days % 7;
            if ($remainingDays == 0) {
                return $weeks . ' həftə';
            } else {
                return $weeks . ' həftə ' . $remainingDays . ' gün';
            }
        } else {
            $months = floor($days / 30);
            $remainingDays = $days % 30;
            if ($remainingDays == 0) {
                return $months . ' ay';
            } else {
                return $months . ' ay ' . $remainingDays . ' gün';
            }
        }
    }

    /**
     * Get total lesson duration in minutes
     */
    public function getTotalLessonDurationMinutesAttribute()
    {
        return $this->modules()
            ->with('lessons')
            ->get()
            ->pluck('lessons')
            ->flatten()
            ->sum('duration_minutes');
    }

    /**
     * Get total lesson duration in human readable format
     */
    public function getTotalLessonDurationAttribute()
    {
        $totalMinutes = $this->total_lesson_duration_minutes;
        
        if ($totalMinutes == 0) {
            return null;
        }

        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        if ($hours == 0) {
            return $minutes . ' dəqiqə';
        } elseif ($minutes == 0) {
            return $hours . ' saat';
        } else {
            return $hours . ' saat ' . $minutes . ' dəqiqə';
        }
    }
}
