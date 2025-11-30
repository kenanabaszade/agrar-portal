<?php

namespace App\Services;

use FFMpeg\FFMpeg;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class VideoThumbnailService
{
    private $ffmpeg;
    
    public function __construct()
    {
        try {
            $this->ffmpeg = FFMpeg::create([
                'ffmpeg.binaries' => config('ffmpeg.ffmpeg.binaries.ffmpeg'),
                'ffprobe.binaries' => config('ffmpeg.ffmpeg.binaries.ffprobe'),
            ]);
        } catch (\Exception $e) {
            Log::error('FFmpeg initialization failed for thumbnail service', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Video-dan thumbnail yarat
     * 
     * @param string $videoPath Video file path (full path)
     * @param int $timeInSeconds Thumbnail yaratmaq üçün video-da saniyə (default: 1)
     * @return string Thumbnail path (relative to storage)
     */
    public function generateThumbnail(string $videoPath, int $timeInSeconds = 1): string
    {
        try {
            Log::info('Generating thumbnail', [
                'video' => $videoPath,
                'time' => $timeInSeconds,
            ]);
            
            $video = $this->ffmpeg->open($videoPath);
            
            // Video-dan frame çıxar
            $frame = $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds($timeInSeconds));
            
            // Thumbnail path
            $thumbnailFileName = pathinfo($videoPath, PATHINFO_FILENAME) . '_thumb_' . uniqid() . '.jpg';
            $tempPath = storage_path('app/temp/thumbnails');
            
            // Temp directory yarat (yoxdursa)
            if (!file_exists($tempPath)) {
                mkdir($tempPath, 0755, true);
            }
            
            $thumbnailPath = $tempPath . '/' . $thumbnailFileName;
            
            // Thumbnail yarat
            $frame->save($thumbnailPath);
            
            // Storage-a köçür (lessons directory-ə)
            $lessonId = $this->extractLessonIdFromPath($videoPath);
            $targetPath = "lessons/{$lessonId}/thumbnails/{$thumbnailFileName}";
            
            // Local disk-ə köçür
            $file = new \Illuminate\Http\File($thumbnailPath);
            Storage::disk('local')->putFileAs(
                "lessons/{$lessonId}/thumbnails",
                $file,
                $thumbnailFileName
            );
            
            // Temp faylı sil
            @unlink($thumbnailPath);
            
            Log::info('Thumbnail generated successfully', [
                'video' => $videoPath,
                'thumbnail' => $targetPath,
            ]);
            
            return $targetPath;
            
        } catch (\Exception $e) {
            Log::error('Thumbnail generation failed', [
                'error' => $e->getMessage(),
                'video' => $videoPath,
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Return null or throw exception based on your preference
            return '';
        }
    }
    
    /**
     * Bir neçə thumbnail yarat (carousel üçün)
     * 
     * @param string $videoPath Video file path
     * @param int $count Thumbnail sayı
     * @return array Thumbnail paths
     */
    public function generateMultipleThumbnails(string $videoPath, int $count = 5): array
    {
        try {
            $video = $this->ffmpeg->open($videoPath);
            $streams = $video->getStreams();
            $videoStream = $streams->videos()->first();
            $duration = $videoStream->get('duration');
            
            $thumbnails = [];
            $interval = $duration / ($count + 1);
            
            for ($i = 1; $i <= $count; $i++) {
                $time = (int)($interval * $i);
                $thumbnail = $this->generateThumbnail($videoPath, $time);
                if ($thumbnail) {
                    $thumbnails[] = $thumbnail;
                }
            }
            
            return $thumbnails;
            
        } catch (\Exception $e) {
            Log::error('Multiple thumbnails generation failed', [
                'error' => $e->getMessage(),
                'video' => $videoPath,
            ]);
            return [];
        }
    }
    
    /**
     * Video path-dən lesson ID-ni çıxar
     */
    private function extractLessonIdFromPath(string $path): string
    {
        // Path format: storage/app/private/lessons/2/video.mp4
        if (preg_match('/lessons\/(\d+)\//', $path, $matches)) {
            return $matches[1];
        }
        
        // Default: temp
        return 'temp';
    }
}

