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
        'is_online',
        'type',
        'online_details',
        'offline_details',
        'media_files',
        'has_certificate',
        'difficulty',
    ];

    protected $casts = [
        'media_files' => 'array',
        'online_details' => 'array',
        'offline_details' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_online' => 'boolean',
        'has_certificate' => 'boolean',
    ];

    protected $attributes = [
        'is_online' => true,
        'has_certificate' => false,
    ];

    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function modules()
    {
        return $this->hasMany(TrainingModule::class);
    }

    public function registrations()
    {
        return $this->hasMany(TrainingRegistration::class);
    }

    /**
     * Get all media files with full URLs
     */
    public function getMediaFilesAttribute($value)
    {
        if (!$value) {
            return [];
        }

        $mediaFiles = json_decode($value, true);
        
        return collect($mediaFiles)->map(function ($file) {
            if (isset($file['path']) && !str_starts_with($file['path'], 'http')) {
                $file['url'] = Storage::url($file['path']);
            } else {
                $file['url'] = $file['path'] ?? null;
            }
            return $file;
        })->toArray();
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
}
