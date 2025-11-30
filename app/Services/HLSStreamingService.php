<?php

namespace App\Services;

use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class HLSStreamingService
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
            Log::error('FFmpeg initialization failed for HLS service', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Video-nu HLS formatına çevir (müxtəlif keyfiyyətdə variantlar)
     * 
     * @param string $inputPath Original video path (full path)
     * @param string $outputDir HLS output directory (full path)
     * @return array Master playlist və quality playlist-ləri
     */
    public function createHLSStream(string $inputPath, string $outputDir): array
    {
        $startTime = microtime(true);
        
        try {
            Log::info('Starting HLS stream creation', [
                'input' => $inputPath,
                'output_dir' => $outputDir,
            ]);
            
            // Output directory yarat
            if (!file_exists($outputDir)) {
                mkdir($outputDir, 0755, true);
            }
            
            $qualities = config('ffmpeg.hls.qualities', [
                '480p' => ['width' => 854, 'height' => 480, 'bitrate' => 500],
                '720p' => ['width' => 1280, 'height' => 720, 'bitrate' => 1000],
                '1080p' => ['width' => 1920, 'height' => 1080, 'bitrate' => 2000],
            ]);
            
            $segmentTime = config('ffmpeg.hls.segment_time', 10); // 10 saniyəlik segmentlər
            $playlistFiles = [];
            
            foreach ($qualities as $quality => $config) {
                try {
                    Log::info('Creating HLS variant', [
                        'quality' => $quality,
                        'config' => $config,
                    ]);
                    
                    $video = $this->ffmpeg->open($inputPath);
                    
                    // Video ölçüsünü dəyişdir (əgər lazımdırsa)
                    $originalVideo = $video->getStreams()->videos()->first();
                    $originalWidth = $originalVideo->get('width');
                    $originalHeight = $originalVideo->get('height');
                    
                    // Əgər original video kiçikdirsə, resize etmə
                    if ($originalWidth >= $config['width'] && $originalHeight >= $config['height']) {
                        $video->filters()
                            ->resize(new \FFMpeg\Coordinate\Dimension($config['width'], $config['height']))
                            ->synchronize();
                    }
                    
                    // HLS format
                    $format = new X264('aac', 'libx264');
                    $format->setKiloBitrate($config['bitrate']);
                    
                    // HLS parametrləri
                    $segmentPattern = $outputDir . "/{$quality}_%03d.ts";
                    $playlistPath = $outputDir . "/{$quality}.m3u8";
                    
                    $format->setAdditionalParameters([
                        '-hls_time', (string)$segmentTime,           // Segment müddəti
                        '-hls_list_size', '0',                        // Bütün segmentləri saxla
                        '-hls_segment_filename', $segmentPattern,      // Segment fayl adı
                        '-hls_flags', 'independent_segments',         // Independent segments
                        '-f', 'hls',
                    ]);
                    
                    // Video-nu HLS formatına çevir
                    $video->save($format, $playlistPath);
                    
                    $playlistFiles[$quality] = [
                        'playlist' => $playlistPath,
                        'bandwidth' => $config['bitrate'] * 1000, // kbps to bps
                        'resolution' => $config['width'] . 'x' . $config['height'],
                    ];
                    
                    Log::info('HLS variant created', [
                        'quality' => $quality,
                        'playlist' => $playlistPath,
                    ]);
                    
                } catch (\Exception $e) {
                    Log::error('Failed to create HLS variant', [
                        'quality' => $quality,
                        'error' => $e->getMessage(),
                    ]);
                    // Continue with other qualities
                }
            }
            
            // Master playlist yarat
            $masterPlaylist = $this->createMasterPlaylist($playlistFiles, $outputDir);
            
            $endTime = microtime(true);
            $processingTime = round($endTime - $startTime, 2);
            
            Log::info('HLS stream creation completed', [
                'master_playlist' => $masterPlaylist,
                'variants' => count($playlistFiles),
                'processing_time' => $processingTime . 's',
            ]);
            
            return [
                'master_playlist' => $masterPlaylist,
                'playlists' => $playlistFiles,
                'output_dir' => $outputDir,
            ];
            
        } catch (\Exception $e) {
            Log::error('HLS stream creation failed', [
                'error' => $e->getMessage(),
                'input' => $inputPath,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Master playlist yarat (bütün keyfiyyət variantlarını birləşdirir)
     */
    private function createMasterPlaylist(array $playlists, string $outputDir): string
    {
        $masterPlaylist = "#EXTM3U\n";
        $masterPlaylist .= "#EXT-X-VERSION:3\n\n";
        
        // Quality-ləri bandwidth-ə görə sırala (aşağıdan yuxarıya)
        uasort($playlists, function($a, $b) {
            return $a['bandwidth'] <=> $b['bandwidth'];
        });
        
        foreach ($playlists as $quality => $playlistInfo) {
            $bandwidth = $playlistInfo['bandwidth'];
            $resolution = $playlistInfo['resolution'];
            $playlistPath = $playlistInfo['playlist'];
            $playlistFileName = basename($playlistPath);
            
            // Bandwidth və resolution təyin et
            $masterPlaylist .= "#EXT-X-STREAM-INF:BANDWIDTH={$bandwidth},RESOLUTION={$resolution}\n";
            $masterPlaylist .= $playlistFileName . "\n\n";
        }
        
        $masterPath = $outputDir . '/master.m3u8';
        file_put_contents($masterPath, $masterPlaylist);
        
        Log::info('Master playlist created', [
            'path' => $masterPath,
            'variants' => count($playlists),
        ]);
        
        return $masterPath;
    }
    
    /**
     * HLS stream-i storage-a köçür
     */
    public function moveToStorage(string $hlsOutputDir, string $lessonId): string
    {
        $storagePath = "lessons/{$lessonId}/hls";
        
        // Bütün faylları storage-a köçür
        $files = glob($hlsOutputDir . '/*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $relativePath = $storagePath . '/' . basename($file);
                $content = file_get_contents($file);
                Storage::disk('local')->put($relativePath, $content);
            }
        }
        
        return $storagePath;
    }
}

