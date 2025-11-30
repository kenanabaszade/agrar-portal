<?php

return [
    'ffmpeg' => [
        'binaries' => [
            // FFmpeg binary path - Windows üçün .exe əlavə etməyi unutma
            'ffmpeg' => env('FFMPEG_BIN', 'C:\\ffmpeg\\bin\\ffmpeg.exe'), // Windows default
            'ffprobe' => env('FFPROBE_BIN', 'C:\\ffmpeg\\bin\\ffprobe.exe'), // Windows default
        ],
        'threads' => env('FFMPEG_THREADS', 4), // CPU thread sayı
        'timeout' => env('FFMPEG_TIMEOUT', 3600), // 1 saat
    ],
    
    // Video compression settings
    'compression' => [
        'bitrate' => env('VIDEO_COMPRESSION_BITRATE', 1000), // 1000 kbps
        'crf' => env('VIDEO_COMPRESSION_CRF', 23), // 23 = yaxşı keyfiyyət
        'two_pass' => env('VIDEO_COMPRESSION_TWO_PASS', false), // Two-pass encoding (yavaş amma keyfiyyətli)
    ],
    
    // HLS streaming settings
    'hls' => [
        'enabled' => env('HLS_ENABLED', true), // Default enabled - admin can disable via .env if needed
        'segment_time' => env('HLS_SEGMENT_TIME', 10), // 10 saniyəlik segmentlər
        'qualities' => [
            '480p' => ['width' => 854, 'height' => 480, 'bitrate' => 500],
            '720p' => ['width' => 1280, 'height' => 720, 'bitrate' => 1000],
            '1080p' => ['width' => 1920, 'height' => 1080, 'bitrate' => 2000],
        ],
    ],
];

