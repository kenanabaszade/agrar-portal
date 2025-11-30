<?php

namespace App\Jobs;

use App\Models\TempLessonFile;
use App\Services\HLSStreamingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessTempVideoHLS implements ShouldQueue
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
        public string $fileCode,
        public string $tempPath
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $tempFile = TempLessonFile::where('file_code', $this->fileCode)->first();
            
            if (!$tempFile) {
                Log::warning("Temp file {$this->fileCode} not found for HLS processing");
                return;
            }

            // Check if HLS is enabled
            $hlsEnabled = config('ffmpeg.hls.enabled', false);
            if (!$hlsEnabled) {
                Log::info('HLS streaming is disabled in config', [
                    'file_code' => $this->fileCode,
                ]);
                return;
            }

            // Check if FFmpeg binary exists
            $ffmpegPath = config('ffmpeg.ffmpeg.binaries.ffmpeg');
            if (!file_exists($ffmpegPath)) {
                Log::error('FFmpeg binary not found', [
                    'file_code' => $this->fileCode,
                    'ffmpeg_path' => $ffmpegPath,
                ]);
                return;
            }

            // Get full path to video file (public disk for temp files)
            $fullPath = Storage::disk('public')->path($this->tempPath);
            
            if (!file_exists($fullPath)) {
                Log::error('Video file not found', [
                    'file_code' => $this->fileCode,
                    'temp_path' => $this->tempPath,
                    'full_path' => $fullPath,
                ]);
                return;
            }

            Log::info('Starting HLS stream creation for temp video', [
                'file_code' => $this->fileCode,
                'temp_path' => $this->tempPath,
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

            // Move HLS files to temp storage (public disk)
            $hlsStoragePath = 'temp/lessons/hls/' . basename($hlsOutputDir);
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

            Log::info('HLS stream created successfully for temp video', [
                'file_code' => $this->fileCode,
                'master_playlist' => $hlsMasterPlaylist,
                'variants' => count($hlsVariants),
                'processing_time' => $processingTime . 's',
            ]);

            // Update temp file description with HLS information
            $description = json_decode($tempFile->description ?? '{}', true);
            $description['hls_master_playlist'] = $hlsMasterPlaylist;
            $description['hls_variants'] = $hlsVariants;
            
            $tempFile->update(['description' => json_encode($description)]);

            Log::info('Temp file updated with HLS information', [
                'file_code' => $this->fileCode,
            ]);

        } catch (\Exception $e) {
            Log::error('HLS stream creation failed for temp video', [
                'file_code' => $this->fileCode,
                'temp_path' => $this->tempPath,
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


