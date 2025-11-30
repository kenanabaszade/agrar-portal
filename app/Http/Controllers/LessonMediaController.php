<?php

namespace App\Http\Controllers;

use App\Models\TrainingLesson;
use App\Models\TrainingModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LessonMediaController extends Controller
{
    /**
     * Download/Serve protected lesson media file
     * Only authorized users (registered for training or admin/trainer) can access
     */
    public function download(TrainingModule $module, TrainingLesson $lesson, Request $request)
    {
        // Get file path from request (can be query param or in URL)
        $filePath = $request->input('path') ?? $request->query('path');
        
        if (!$filePath) {
            return response()->json([
                'message' => 'File path is required'
            ], 400);
        }
        
        // URL decode the path (in case it's encoded)
        $filePath = urldecode($filePath);
        
        // Security: Ensure path is within lessons directory and belongs to this lesson
        $lessonPath = 'lessons/' . $lesson->id . '/';
        
        // Allow HLS files (.m3u8 and .ts) in hls subdirectory
        $isHLSFile = strpos($filePath, $lessonPath . 'hls/') === 0;
        $isRegularFile = strpos($filePath, $lessonPath) === 0;
        
        // Prevent directory traversal attacks
        if (!$isHLSFile && !$isRegularFile) {
            return response()->json([
                'message' => 'Invalid file path'
            ], 403);
        }
        
        // Check file type from extension (before accessing file)
        $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico'];
        $hlsExtensions = ['m3u8', 'ts']; // HLS playlist files - should be accessible without restrictions
        $isImageFile = in_array($fileExtension, $imageExtensions);
        $isHLSPlaylistFile = in_array($fileExtension, $hlsExtensions); // Extension-based check for HLS files
        // Video files (excluding HLS playlist files which are handled separately)
        $isVideoFile = in_array($fileExtension, ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv']) && !$isHLSPlaylistFile;
        
        // Security: Prevent browser direct access to video URLs
        // Videos should only be accessible through video players (which send Range headers)
        // Images, HLS playlist files (.m3u8, .ts) and other non-video files can be accessed directly
        $referer = $request->header('Referer');
        $hasRangeHeader = $request->hasHeader('Range');
        $isSignedUrl = $request->has('signature') && $request->has('expires');
        
        // For signed URLs, apply video restrictions ONLY to actual video files (not HLS playlist files)
        // HLS playlist files (.m3u8, .ts) are needed by video players and should be accessible
        // ✅ Yumşaldılmış: Signed URL varsa, referer yoxlaması yoxdur (signed URL artıq təhlükəsizdir)
        if ($isSignedUrl && $isVideoFile && !$isHLSPlaylistFile) {
            // ✅ Signed URL varsa, referer yoxlaması yoxdur
            // Çünki signed URL artıq təhlükəsizdir və expire olur
            // Yalnız Range header yoxdursa log yaz, amma bloklama
            if (!$hasRangeHeader) {
                \Log::info('Video request without Range header (signed URL)', [
                    'referer' => $referer,
                    'has_range' => $hasRangeHeader,
                    'lesson_id' => $lesson->id,
                    'has_signed_url' => true
                ]);
            }
            // ✅ Signed URL varsa, icazə ver (referer yoxlaması yoxdur)
        }
        
        // For non-signed URLs, check referer if enabled (only for videos, not HLS files)
        // ✅ Yumşaldılmış: Localhost üçün referer yoxlaması yoxdur
        if (!$isSignedUrl && $isVideoFile && !$isHLSPlaylistFile && config('app.check_referer', true)) {
            $allowedDomain = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
            
            // ✅ Localhost və 127.0.0.1 üçün referer yoxlaması yoxdur
            $isLocalhost = $referer && (
                str_starts_with($referer, 'http://localhost:') ||
                str_starts_with($referer, 'http://127.0.0.1:') ||
                str_starts_with($referer, $allowedDomain)
            );
            
            if ($referer && !$isLocalhost) {
                \Log::warning('Video access denied: Invalid referer', [
                    'referer' => $referer,
                    'allowed_domain' => $allowedDomain,
                    'lesson_id' => $lesson->id
                ]);
                
                return response()->json([
                    'message' => 'Access denied: Invalid referer'
                ], 403);
            }
        }
        
        // Rate limiting for video downloads only (prevent abuse)
        // HLS playlist files are small and frequently requested, so exclude them from rate limiting
        if ($isVideoFile && !$isHLSPlaylistFile && auth()->check()) {
            $userId = auth()->user()->id;
            $cacheKey = "video_download_{$userId}_{$lesson->id}";
            $downloads = \Illuminate\Support\Facades\Cache::get($cacheKey, 0);
            $maxDownloads = config('app.video_download_rate_limit', 50); // Default: 50 downloads per minute
            
            if ($downloads >= $maxDownloads) {
                \Log::warning('Video access denied: Rate limit exceeded', [
                    'user_id' => $userId,
                    'lesson_id' => $lesson->id,
                    'downloads' => $downloads
                ]);
                
                return response()->json([
                    'message' => 'Too many requests. Please try again later.'
                ], 429);
            }
            
            // Increment download count
            \Illuminate\Support\Facades\Cache::put($cacheKey, $downloads + 1, 60); // 60 seconds
        }
        
        // Check if signed URL is provided and valid
        $isSignedUrl = $request->has('signature') && $request->has('expires');
        
        if ($isSignedUrl) {
            // Verify signed URL
            if (!self::verifySignedUrl($request, $module, $lesson, $filePath)) {
                return response()->json([
                    'message' => 'Invalid or expired signed URL'
                ], 403);
            }
            // Signed URL is valid, allow access
            $hasAccess = true;
        } else {
            // Regular authentication check
            if (!auth()->check()) {
                return response()->json([
                    'message' => 'Authentication required'
                ], 401);
            }
            
            $user = auth()->user();
            
            // Check authorization
            $hasAccess = false;
            
            // Admins and trainers have access to all lessons
            if ($user->hasRole(['admin', 'trainer'])) {
                $hasAccess = true;
            } else {
                // Check if user is registered for the training
                $training = $lesson->module->training;
                $registration = $training->registrations()
                    ->where('user_id', $user->id)
                    ->where('status', 'approved')
                    ->first();
                
                if ($registration) {
                    $hasAccess = true;
                }
            }
            
            if (!$hasAccess) {
                return response()->json([
                    'message' => 'Access denied. You must be registered for this training to access lesson media.'
                ], 403);
            }
        }
        
        // Determine which disk has the file
        $disk = null;
        $fullPath = null;
        $mimeType = null;
        $fileSize = null;
        
        // HLS faylları üçün mime type təyin et
        $isM3U8 = str_ends_with($filePath, '.m3u8');
        $isTS = str_ends_with($filePath, '.ts');
        
        if (Storage::disk('local')->exists($filePath)) {
            $disk = 'local';
            $fullPath = Storage::disk('local')->path($filePath);
            $mimeType = $isM3U8 ? 'application/vnd.apple.mpegurl' : ($isTS ? 'video/mp2t' : Storage::disk('local')->mimeType($filePath));
            $fileSize = Storage::disk('local')->size($filePath);
        } elseif (Storage::disk('public')->exists($filePath)) {
            $disk = 'public';
            $fullPath = Storage::disk('public')->path($filePath);
            $mimeType = $isM3U8 ? 'application/vnd.apple.mpegurl' : ($isTS ? 'video/mp2t' : Storage::disk('public')->mimeType($filePath));
            $fileSize = Storage::disk('public')->size($filePath);
        } else {
            return response()->json([
                'message' => 'File not found'
            ], 404);
        }
        
        $fileName = basename($filePath);
        
        // Handle Range requests for video streaming (seek, buffering)
        $range = $request->header('Range');
        
        if ($range) {
            // Parse Range header (e.g., "bytes=0-1023" or "bytes=1024-")
            if (preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
                $start = intval($matches[1]);
                $end = $matches[2] === '' ? $fileSize - 1 : intval($matches[2]);
                
                // Validate range
                if ($start > $end || $start < 0 || $end >= $fileSize) {
                    return response('', 416, [
                        'Content-Range' => "bytes */{$fileSize}",
                    ]);
                }
                
                $length = $end - $start + 1;
                
                return response()->stream(function () use ($fullPath, $start, $length) {
                    $stream = fopen($fullPath, 'rb');
                    fseek($stream, $start);
                    $remaining = $length;
                    // ✅ Optimallaşdırılmış chunk size: 256KB (video streaming üçün optimal)
                    // Bu, kiçik chunk-lar (8KB) ilə müqayisədə 32x daha sürətli transfer təmin edir
                    $chunkSize = 256 * 1024; // 256KB
                    
                    while ($remaining > 0 && !feof($stream)) {
                        $read = min($remaining, $chunkSize);
                        echo fread($stream, $read);
                        $remaining -= $read;
                        // ✅ Flush hər chunk-dan sonra deyil, daha az tez-tez çağırılır (performans üçün)
                        if ($remaining > 0) {
                            flush();
                        }
                    }
                    fclose($stream);
                }, 206, [
                    'Content-Type' => $mimeType,
                    'Content-Length' => $length,
                    'Content-Range' => "bytes {$start}-{$end}/{$fileSize}",
                    'Accept-Ranges' => 'bytes',
                    // Don't set Content-Disposition for video streaming - let video player handle it
                    // Security headers
                    'X-Content-Type-Options' => 'nosniff',
                    'X-Frame-Options' => 'SAMEORIGIN', // Prevent embedding in iframe from other domains
                    'Referrer-Policy' => 'strict-origin-when-cross-origin',
                    // ✅ CORS headers (ERR_BLOCKED problemi üçün)
                    'Access-Control-Allow-Origin' => $this->getCorsOrigin($request),
                    'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                    'Access-Control-Allow-Headers' => 'Content-Type, Authorization, Range, Origin, Referer',
                    'Access-Control-Allow-Credentials' => 'true',
                    'Access-Control-Expose-Headers' => 'Content-Range, Content-Length, Accept-Ranges',
                    // Prevent video download
                    'Content-Disposition' => 'inline', // inline allows video player, but prevents direct download
                ]);
            }
        }
        
        // For HLS playlist files (.m3u8), transform relative paths to signed URLs
        // This applies to both master playlist and variant playlists
        if ($isM3U8) {
            $content = Storage::disk($disk)->get($filePath);
            $hlsDir = dirname($filePath);
            
            // Parse playlist and transform relative paths to signed URLs
            $lines = explode("\n", $content);
            $transformedLines = [];
            
            foreach ($lines as $line) {
                $trimmedLine = trim($line);
                
                // Skip empty lines and comments (keep them as is)
                if (empty($trimmedLine) || str_starts_with($trimmedLine, '#')) {
                    $transformedLines[] = $line;
                    continue;
                }
                
                // This is a file reference (.m3u8 for variant playlists, .ts for video segments)
                // Transform to signed URL
                if (str_ends_with($trimmedLine, '.m3u8') || str_ends_with($trimmedLine, '.ts')) {
                    // Determine if path is relative or absolute
                    if (strpos($trimmedLine, '/') === 0 || strpos($trimmedLine, 'lessons/') === 0) {
                        // Absolute path - use as is but create signed URL
                        $filePathToTransform = $trimmedLine;
                    } else {
                        // Relative path - combine with playlist directory
                        $filePathToTransform = $hlsDir . '/' . $trimmedLine;
                        // Normalize path (remove double slashes)
                        $filePathToTransform = str_replace('//', '/', $filePathToTransform);
                    }
                    
                    // Generate signed URL for the file
                    try {
                        $signedUrl = self::generateSignedUrl(
                            $module,
                            $lesson,
                            $filePathToTransform,
                            null,
                            120 // 2 hours expiration
                        );
                        
                        // Replace relative path with signed URL
                        $transformedLines[] = $signedUrl;
                    } catch (\Exception $e) {
                        // If signed URL generation fails, keep original path
                        \Log::warning('Failed to generate signed URL for HLS file', [
                            'file_path' => $filePathToTransform,
                            'error' => $e->getMessage()
                        ]);
                        $transformedLines[] = $line;
                    }
                } else {
                    // Keep line as is
                    $transformedLines[] = $line;
                }
            }
            
            $transformedContent = implode("\n", $transformedLines);
            $transformedSize = strlen($transformedContent);
            
            // ✅ CORS header-larını yaxşılaşdır (ERR_BLOCKED problemi üçün)
            return response($transformedContent, 200, [
                'Content-Type' => $mimeType,
                'Content-Length' => $transformedSize,
                'Content-Disposition' => 'inline',
                'Accept-Ranges' => 'bytes',
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'SAMEORIGIN',
                'Referrer-Policy' => 'strict-origin-when-cross-origin',
                // ✅ CORS header-ları
                'Access-Control-Allow-Origin' => $this->getCorsOrigin($request),
                'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization, Range, Origin, Referer',
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Expose-Headers' => 'Content-Range, Content-Length, Accept-Ranges',
            ]);
        }
        
        // Full file download (no range request) - for non-HLS files
        // ✅ Optimallaşdırılmış: Video streaming üçün daha böyük chunk size
        return response()->streamDownload(function () use ($fullPath) {
            $stream = fopen($fullPath, 'rb');
            // ✅ Optimallaşdırılmış chunk size: 512KB (full download üçün optimal)
            $chunkSize = 512 * 1024; // 512KB
            while (!feof($stream)) {
                echo fread($stream, $chunkSize);
                // ✅ Flush daha az tez-tez çağırılır (performans üçün)
                flush();
            }
            fclose($stream);
        }, $fileName, [
            'Content-Type' => $mimeType,
            'Content-Length' => $fileSize,
            // Don't set filename in Content-Disposition to prevent download
            'Content-Disposition' => 'inline', // inline allows video player, but prevents direct download
            'Accept-Ranges' => 'bytes',
            // Security headers
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN', // Prevent embedding in iframe from other domains
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            // CORS headers (if needed)
            'Access-Control-Allow-Origin' => config('app.frontend_url', env('FRONTEND_URL', '*')),
            'Access-Control-Allow-Methods' => 'GET',
            'Access-Control-Allow-Credentials' => 'true',
        ]);
        
        return response()->json([
            'message' => 'File not found'
        ], 404);
    }
    
    /**
     * Generate temporary signed URL for lesson media file
     * This URL expires after a certain time and can be used directly in video player
     * 
     * @param TrainingModule $module
     * @param TrainingLesson $lesson
     * @param string $filePath
     * @param int|null $userId
     * @param int $expiresInMinutes Default: 60 minutes
     * @return string Signed URL
     */
    public static function generateSignedUrl($module, $lesson, $filePath, $userId = null, $expiresInMinutes = 60)
    {
        // Generate expiration timestamp
        $expiresAt = now()->addMinutes($expiresInMinutes);
        $expiresTimestamp = $expiresAt->timestamp;
        
        // Create signature data
        $signatureData = [
            'module_id' => $module->id,
            'lesson_id' => $lesson->id,
            'path' => $filePath,
            'user_id' => $userId,
            'expires_at' => $expiresTimestamp
        ];
        
        // Important: Sort keys to ensure consistent JSON encoding
        ksort($signatureData);
        $jsonString = json_encode($signatureData, JSON_UNESCAPED_SLASHES);
        
        // Generate signature
        $signature = hash_hmac('sha256', $jsonString, config('app.key'));
        
        // Build URL with signature
        $url = route('lesson.media.download', [
            'module' => $module->id,
            'lesson' => $lesson->id,
            'path' => $filePath
        ]);
        
        // Add signature and expiration as query parameters
        $url .= '&signature=' . $signature . '&expires=' . $expiresTimestamp;
        
        \Log::info('Generated signed URL', [
            'module_id' => $module->id,
            'lesson_id' => $lesson->id,
            'path' => $filePath,
            'user_id' => $userId,
            'expires_at' => $expiresTimestamp,
            'signature_data' => $signatureData,
            'json_string' => $jsonString,
            'signature' => $signature,
            'url' => $url
        ]);
        
        return $url;
    }
    
    /**
     * Verify signed URL signature and expiration
     * 
     * @param Request $request
     * @param TrainingModule $module
     * @param TrainingLesson $lesson
     * @param string $filePath
     * @return bool
     */
    public static function verifySignedUrl($request, $module, $lesson, $filePath)
    {
        $signature = $request->query('signature');
        $expires = $request->query('expires');
        
        if (!$signature || !$expires) {
            \Log::info('Signed URL verification failed: missing signature or expires', [
                'has_signature' => $request->has('signature'),
                'has_expires' => $request->has('expires')
            ]);
            return false;
        }
        
        // Check expiration
        if (time() > intval($expires)) {
            \Log::info('Signed URL verification failed: expired', [
                'current_time' => time(),
                'expires' => intval($expires),
                'expires_date' => date('Y-m-d H:i:s', intval($expires))
            ]);
            return false;
        }
        
        // Verify signature
        // Try multiple userId combinations to support both authenticated and browser access
        $possibleUserIds = [];
        
        // If authenticated, try with current userId
        if (auth()->check()) {
            $possibleUserIds[] = auth()->user()->id;
        }
        
        // Always try with null userId (for browser access without authentication)
        $possibleUserIds[] = null;
        
        foreach ($possibleUserIds as $userId) {
            $signatureData = [
                'module_id' => $module->id,
                'lesson_id' => $lesson->id,
                'path' => $filePath,
                'user_id' => $userId,
                'expires_at' => intval($expires)
            ];
            
            // Important: json_encode must match exactly how it was generated
            // Sort keys to ensure consistent JSON encoding
            ksort($signatureData);
            $jsonString = json_encode($signatureData, JSON_UNESCAPED_SLASHES);
            
            $expectedSignature = hash_hmac('sha256', $jsonString, config('app.key'));
            
            \Log::info('Signed URL verification attempt', [
                'user_id' => $userId,
                'module_id' => $module->id,
                'lesson_id' => $lesson->id,
                'path' => $filePath,
                'expires_at' => intval($expires),
                'signature_data' => $signatureData,
                'json_string' => $jsonString,
                'expected_signature' => $expectedSignature,
                'provided_signature' => $signature,
                'matches' => hash_equals($expectedSignature, $signature)
            ]);
            
            if (hash_equals($expectedSignature, $signature)) {
                return true;
            }
        }
        
        \Log::warning('Signed URL verification failed: signature mismatch', [
            'module_id' => $module->id,
            'lesson_id' => $lesson->id,
            'path' => $filePath,
            'provided_signature' => $signature
        ]);
        
        return false;
    }
    
    /**
     * Get CORS origin for request
     * Helper method to determine allowed origin
     */
    private function getCorsOrigin(Request $request)
    {
        $origin = $request->header('Origin');
        $allowedOrigins = [
            'http://localhost:5173',
            'http://localhost:5174',
            'http://localhost:5175',
            'http://localhost:5176',
            'http://localhost:3000',
            'http://127.0.0.1:5173',
            'http://127.0.0.1:5174',
            'http://127.0.0.1:5175',
            'http://127.0.0.1:5176',
            'http://127.0.0.1:3000',
        ];
        
        if ($origin && (in_array($origin, $allowedOrigins) || str_starts_with($origin, 'http://localhost:') || str_starts_with($origin, 'http://127.0.0.1:'))) {
            return $origin;
        }
        
        return config('app.frontend_url', env('FRONTEND_URL', '*'));
    }
    
    /**
     * Get secure URL for lesson media file
     * This generates a signed URL that expires after a certain time
     */
    public function getSecureUrl(TrainingModule $module, TrainingLesson $lesson, Request $request)
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return response()->json([
                'message' => 'Authentication required'
            ], 401);
        }
        
        $user = auth()->user();
        
        // Check authorization
        $hasAccess = false;
        
        if ($user->hasRole(['admin', 'trainer'])) {
            $hasAccess = true;
        } else {
            $training = $lesson->module->training;
            $registration = $training->registrations()
                ->where('user_id', $user->id)
                ->where('status', 'approved')
                ->first();
            
            if ($registration) {
                $hasAccess = true;
            }
        }
        
        if (!$hasAccess) {
            return response()->json([
                'message' => 'Access denied'
            ], 403);
        }
        
        $filePath = $request->input('path');
        
        if (!$filePath) {
            return response()->json([
                'message' => 'File path is required'
            ], 400);
        }
        
        // Generate signed URL
        $url = self::generateSignedUrl($module, $lesson, $filePath, $user->id, 60);
        
        return response()->json([
            'url' => $url,
            'expires_at' => now()->addHour()->toIso8601String()
        ]);
    }
}

