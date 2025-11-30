<?php

namespace App\Services;

use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class VideoCompressionService
{
    private $ffmpeg;
    
    public function __construct()
    {
        try {
            $this->ffmpeg = FFMpeg::create([
                'ffmpeg.binaries' => config('ffmpeg.ffmpeg.binaries.ffmpeg'),
                'ffprobe.binaries' => config('ffmpeg.ffmpeg.binaries.ffprobe'),
                'timeout' => config('ffmpeg.ffmpeg.timeout'),
                'ffmpeg.threads' => config('ffmpeg.ffmpeg.threads'),
            ]);
        } catch (\Exception $e) {
            Log::error('FFmpeg initialization failed', [
                'error' => $e->getMessage(),
                'ffmpeg_path' => config('ffmpeg.ffmpeg.binaries.ffmpeg'),
                'ffprobe_path' => config('ffmpeg.ffmpeg.binaries.ffprobe'),
            ]);
            throw $e;
        }
    }
    
    /**
     * Video-nu sıxışdır
     * 
     * @param string $inputPath Original video path (full path)
     * @param string $outputPath Compressed video path (full path)
     * @param array $options Compression options
     * @return string Compressed video path
     */
    public function compressVideo(string $inputPath, string $outputPath, array $options = []): string
    {
        $startTime = microtime(true);
        
        try {
            Log::info('Starting video compression', [
                'input' => $inputPath,
                'output' => $outputPath,
                'options' => $options,
            ]);
            
            $video = $this->ffmpeg->open($inputPath);
            
            // Video format və codec seçimi
            $format = new X264('libmp3lame', 'libx264');
            
            // Bitrate təyin et (default: 1000 kbps)
            $bitrate = $options['bitrate'] ?? config('ffmpeg.compression.bitrate', 1000);
            $format->setKiloBitrate($bitrate);
            
            // CRF (Constant Rate Factor) - keyfiyyət üçün
            // CRF 23 = yaxşı balans (18-28 arası tövsiyə olunur)
            $crf = $options['crf'] ?? config('ffmpeg.compression.crf', 23);
            $format->setAdditionalParameters(['-crf', (string)$crf]);
            
            // Preset - compression sürəti
            // ultrafast, superfast, veryfast, faster, fast, medium, slow, slower, veryslow
            $preset = $options['preset'] ?? 'fast';
            $format->setAdditionalParameters(['-preset', $preset]);
            
            // Audio quality
            $format->setAudioChannels(2);
            $format->setAudioKiloBitrate(128);
            
            // Two-pass encoding (daha yaxşı keyfiyyət, amma yavaş)
            if ($options['two_pass'] ?? config('ffmpeg.compression.two_pass', false)) {
                return $this->twoPassEncoding($video, $format, $outputPath);
            }
            
            // Single-pass encoding (daha sürətli)
            $video->save($format, $outputPath);
            
            $endTime = microtime(true);
            $compressionTime = round($endTime - $startTime, 2);
            
            $originalSize = filesize($inputPath);
            $compressedSize = filesize($outputPath);
            $compressionRatio = round((1 - $compressedSize / $originalSize) * 100, 2);
            
            Log::info('Video compressed successfully', [
                'input' => $inputPath,
                'output' => $outputPath,
                'original_size' => $originalSize,
                'compressed_size' => $compressedSize,
                'compression_ratio' => $compressionRatio . '%',
                'compression_time' => $compressionTime . 's',
            ]);
            
            return $outputPath;
            
        } catch (\Exception $e) {
            Log::error('Video compression failed', [
                'error' => $e->getMessage(),
                'input' => $inputPath,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Video-nun məlumatlarını al
     */
    public function getVideoInfo(string $videoPath): array
    {
        try {
            $video = $this->ffmpeg->open($videoPath);
            $streams = $video->getStreams();
            
            $videoStream = $streams->videos()->first();
            $audioStream = $streams->audios()->first();
            
            return [
                'duration' => $videoStream->get('duration'),
                'width' => $videoStream->get('width'),
                'height' => $videoStream->get('height'),
                'bitrate' => $videoStream->get('bit_rate'),
                'codec' => $videoStream->get('codec_name'),
                'has_audio' => $audioStream !== null,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get video info', [
                'error' => $e->getMessage(),
                'path' => $videoPath,
            ]);
            return [];
        }
    }
    
    /**
     * Video-nu müxtəlif keyfiyyətdə variantlara böl (HLS üçün)
     */
    public function createMultipleQualities(string $inputPath, string $outputDir): array
    {
        $qualities = config('ffmpeg.hls.qualities', [
            '480p' => ['width' => 854, 'height' => 480, 'bitrate' => 500],
            '720p' => ['width' => 1280, 'height' => 720, 'bitrate' => 1000],
            '1080p' => ['width' => 1920, 'height' => 1080, 'bitrate' => 2000],
        ]);
        
        $outputs = [];
        
        foreach ($qualities as $quality => $config) {
            try {
                $video = $this->ffmpeg->open($inputPath);
                
                // Video ölçüsünü dəyişdir
                $video->filters()
                    ->resize(new \FFMpeg\Coordinate\Dimension($config['width'], $config['height']))
                    ->synchronize();
                
                // Format və bitrate
                $format = new X264('libmp3lame', 'libx264');
                $format->setKiloBitrate($config['bitrate']);
                
                $outputPath = $outputDir . "/{$quality}.mp4";
                $video->save($format, $outputPath);
                
                $outputs[$quality] = $outputPath;
                
                Log::info('Created video quality variant', [
                    'quality' => $quality,
                    'output' => $outputPath,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create quality variant', [
                    'quality' => $quality,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $outputs;
    }
    
    private function twoPassEncoding($video, $format, $outputPath): string
    {
        // First pass
        $video->filters()->custom('-pass', '1');
        $video->save($format, $outputPath . '.pass1');
        
        // Second pass
        $video->filters()->custom('-pass', '2');
        $video->save($format, $outputPath);
        
        // Cleanup
        @unlink($outputPath . '.pass1');
        
        return $outputPath;
    }
}

