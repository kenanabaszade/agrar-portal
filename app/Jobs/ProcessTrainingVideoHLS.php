<?php

namespace App\Jobs;

use App\Models\Training;
use App\Services\HLSStreamingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessTrainingVideoHLS implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 2;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $retryAfter = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $trainingId,
        public string $videoPath
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $training = Training::find($this->trainingId);
            
            if (!$training) {
                Log::warning("Training {$this->trainingId} not found for HLS processing");
                return;
            }

            // Check if HLS is enabled
            $hlsEnabled = config('ffmpeg.hls.enabled', false);
            if (!$hlsEnabled) {
                Log::info('HLS streaming is disabled in config', [
                    'training_id' => $this->trainingId,
                ]);
                return;
            }

            // Check if FFmpeg binary exists
            $ffmpegPath = config('ffmpeg.ffmpeg.binaries.ffmpeg');
            if (!file_exists($ffmpegPath)) {
                Log::error('FFmpeg binary not found', [
                    'training_id' => $this->trainingId,
                    'ffmpeg_path' => $ffmpegPath,
                ]);
                return;
            }

            // Get full path to video file (public disk)
            $fullPath = Storage::disk('public')->path($this->videoPath);
            
            if (!file_exists($fullPath)) {
                Log::error('Video file not found', [
                    'training_id' => $this->trainingId,
                    'video_path' => $this->videoPath,
                    'full_path' => $fullPath,
                ]);
                return;
            }

            Log::info('Starting HLS stream creation for training video', [
                'training_id' => $this->trainingId,
                'video_path' => $this->videoPath,
            ]);

            $startTime = microtime(true);

            // Create HLS stream
            $hlsService = new HLSStreamingService();
            $hlsOutputDir = storage_path('app/temp/hls/' . uniqid());
            
            if (!file_exists($hlsOutputDir)) {
                mkdir($hlsOutputDir, 0755, true);
            }

            // Create HLS stream (480p, 720p, 1080p variants)
            $hlsStream = $hlsService->createHLSStream($fullPath, $hlsOutputDir);

            // Move HLS files to training storage (public disk)
            $hlsStoragePath = 'trainings/' . $this->trainingId . '/hls';
            $hlsFiles = glob($hlsOutputDir . '/*');
            
            foreach ($hlsFiles as $hlsFile) {
                if (is_file($hlsFile)) {
                    $relativePath = $hlsStoragePath . '/' . basename($hlsFile);
                    $content = file_get_contents($hlsFile);
                    Storage::disk('public')->put($relativePath, $content);
                }
            }

            // Master playlist path
            $hlsMasterPlaylist = $hlsStoragePath . '/master.m3u8';

            // HLS variants information
            $hlsVariants = [];
            foreach ($hlsStream['playlists'] as $quality => $playlistInfo) {
                $hlsVariants[$quality] = [
                    'playlist' => $hlsStoragePath . '/' . basename($playlistInfo['playlist']),
                    'bandwidth' => $playlistInfo['bandwidth'],
                    'resolution' => $playlistInfo['resolution'],
                ];
            }

            // Clean up temp directory
            $this->deleteDirectory($hlsOutputDir);

            $endTime = microtime(true);
            $processingTime = round($endTime - $startTime, 2);

            Log::info('HLS stream created successfully for training video', [
                'training_id' => $this->trainingId,
                'master_playlist' => $hlsMasterPlaylist,
                'variants' => count($hlsVariants),
                'processing_time' => $processingTime . 's',
            ]);

            // Update training media_files with HLS information
            $mediaFiles = $training->media_files ?? [];
            foreach ($mediaFiles as &$mediaFile) {
                if ($mediaFile['type'] === 'intro_video' && isset($mediaFile['path']) && $mediaFile['path'] === $this->videoPath) {
                    $mediaFile['hls_master_playlist'] = $hlsMasterPlaylist;
                    $mediaFile['hls_variants'] = $hlsVariants;
                    break;
                }
            }
            unset($mediaFile);

            $training->update(['media_files' => $mediaFiles]);

            Log::info('Training updated with HLS information', [
                'training_id' => $this->trainingId,
            ]);

        } catch (\Exception $e) {
            Log::error('HLS stream creation failed for training video', [
                'training_id' => $this->trainingId,
                'video_path' => $this->videoPath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Recursively delete directory
     */
    private function deleteDirectory(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }
        
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        return rmdir($dir);
    }
}


