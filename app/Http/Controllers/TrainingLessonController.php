<?php

namespace App\Http\Controllers;

use App\Models\TrainingLesson;
use App\Models\TrainingModule;
use App\Models\UserTrainingProgress;
use App\Models\{TrainingRegistration, Certificate};
use App\Models\TempLessonFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Services\HLSStreamingService;
use App\Services\VideoThumbnailService;
use App\Jobs\ProcessVideoHLS;

class TrainingLessonController extends Controller
{
    /**
     * List lessons for a module
     */
    public function index(TrainingModule $module)
    {
        $lessons = $module->lessons()
            ->where('status', 'published')
            ->orderBy('sequence')
            ->paginate(20);

        return response()->json($lessons);
    }

    /**
     * Create a new lesson
     */
    public function store(Request $request, TrainingModule $module)
    {
        // Normalize request data: Convert title_az, title_en format to object format
        $requestData = $this->normalizeTranslationRequest($request->all());
        $request->merge($requestData);

        $validated = $request->validate([
            'title' => ['required', new \App\Rules\TranslationRule(true)],
            'lesson_type' => ['required', 'in:text,video,audio,image,mixed'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'content' => ['nullable', new \App\Rules\TranslationRule(false)],
            'description' => ['nullable', new \App\Rules\TranslationRule(false)],
            'video_url' => ['nullable', 'url'],
            'pdf_url' => ['nullable', 'url'],
            'file_codes' => ['nullable', 'array'],
            'file_codes.*' => ['string', 'regex:/^FILE_[A-F0-9]{8}$/'],
            'sequence' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'in:draft,published,archived'],
            'is_required' => ['nullable', 'boolean'],
            'min_completion_time' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
        ]);

        // Set default sequence if not provided
        if (!isset($validated['sequence'])) {
            $validated['sequence'] = $module->lessons()->max('sequence') + 1;
        }

        $lesson = $module->lessons()->create($validated);

        // Process file codes and move files to final location
        if (isset($validated['file_codes']) && is_array($validated['file_codes'])) {
            $finalMediaFiles = [];
            
            foreach ($validated['file_codes'] as $fileCode) {
                // Find temp file by code
                $tempFile = TempLessonFile::where('file_code', $fileCode)
                    ->where('expires_at', '>', now())
                    ->first();
                
                if ($tempFile) {
                    // Move file from temp to final location (use private storage for security)
                    $finalPath = 'lessons/' . $lesson->id . '/' . basename($tempFile->temp_path);
                    
                    // Move from public temp to private storage
                    if (Storage::disk('public')->exists($tempFile->temp_path)) {
                        $fileContent = Storage::disk('public')->get($tempFile->temp_path);
                        Storage::disk('local')->put($finalPath, $fileContent);
                        Storage::disk('public')->delete($tempFile->temp_path);
                    } else {
                        // If already in private, just move
                        Storage::disk('local')->move($tempFile->temp_path, $finalPath);
                    }
                    
                    // HLS fayllarını köçür (əgər varsa)
                    $hlsMasterPlaylist = null;
                    $hlsVariants = [];
                    
                    if ($tempFile->type === 'video') {
                        // Temp fayldan HLS məlumatını al
                        $tempFileData = json_decode($tempFile->description ?? '{}', true);
                        
                        if (isset($tempFileData['hls_master_playlist'])) {
                            // HLS faylları artıq varsa, köçür
                            $hlsTempPath = dirname($tempFileData['hls_master_playlist']);
                            $hlsStoragePath = 'lessons/' . $lesson->id . '/hls/' . basename($hlsTempPath);
                            
                            // HLS fayllarını tap və köçür
                            $hlsFiles = Storage::disk('public')->files($hlsTempPath);
                            
                            foreach ($hlsFiles as $hlsFile) {
                                $relativePath = $hlsStoragePath . '/' . basename($hlsFile);
                                $content = Storage::disk('public')->get($hlsFile);
                                Storage::disk('local')->put($relativePath, $content);
                                Storage::disk('public')->delete($hlsFile);
                            }
                            
                            $hlsMasterPlaylist = $hlsStoragePath . '/master.m3u8';
                            
                            // Variants məlumatını yenilə
                            if (isset($tempFileData['hls_variants'])) {
                                foreach ($tempFileData['hls_variants'] as $quality => $variant) {
                                    $hlsVariants[$quality] = [
                                        'playlist' => $hlsStoragePath . '/' . basename($variant['playlist']),
                                        'bandwidth' => $variant['bandwidth'],
                                        'resolution' => $variant['resolution'],
                                    ];
                                }
                            }
                        } else {
                            // HLS conversion yoxdursa, background job-da başlat
                            $hlsEnabled = config('ffmpeg.hls.enabled', false);
                            if ($hlsEnabled) {
                                \App\Jobs\ProcessVideoHLS::dispatch($lesson->id, $finalPath)
                                    ->delay(now()->addSeconds(5)); // 5 saniyə sonra başlat
                                
                                \Log::info('HLS conversion job dispatched for lesson', [
                                    'lesson_id' => $lesson->id,
                                    'video_path' => $finalPath,
                                ]);
                            }
                        }
                    }
                    
                    // Create media file entry with protected URL
                    $mediaFile = [
                        'type' => $tempFile->type,
                        'url' => route('lesson.media.download', [
                            'module' => $lesson->module_id,
                            'lesson' => $lesson->id,
                            'path' => $finalPath
                        ]),
                        'path' => $finalPath, // Store path for download
                        'filename' => $tempFile->filename,
                        'size' => $tempFile->size,
                        'mime_type' => $tempFile->mime_type,
                        'title' => $tempFile->title,
                        'description' => $tempFile->description,
                        // HLS streaming info
                        'hls_master_playlist' => $hlsMasterPlaylist,
                        'hls_variants' => $hlsVariants,
                    ];
                    
                    $finalMediaFiles[] = $mediaFile;
                    
                    // Delete temp file record
                    $tempFile->delete();
                }
            }
            
            // Update lesson with final media files
            $lesson->update(['media_files' => $finalMediaFiles]);
        }

        return response()->json($lesson->load('module'), 201);
    }

    /**
     * Get lesson details with full content
     */
    public function show(TrainingModule $module, TrainingLesson $lesson)
    {
        // Check if user has access to this lesson
        if (auth()->check()) {
            $user = auth()->user();
            
            // Admins and trainers have access to all lessons
            if ($user->hasRole(['admin', 'trainer'])) {
                // Allow access for admins and trainers
            } else {
                // Check if user is registered for the training
                $training = $lesson->module->training;
                $registration = $training->registrations()
                    ->where('user_id', $user->id)
                    ->where('status', 'approved')
                    ->first();

                if (!$registration) {
                    return response()->json(['message' => 'Access denied. Please register for this training.'], 403);
                }
            }
        }

        // Transform media URLs to protected endpoints
        if ($lesson->media_files && is_array($lesson->media_files)) {
            $lesson->media_files = $lesson->transformMediaUrls($lesson->media_files);
        }
        
        return response()->json([
            'lesson' => $lesson->load('module.training'),
            'content' => $lesson->getFullContent(),
            'duration' => $lesson->duration,
        ]);
    }

    /**
     * Update lesson
     */
    public function update(Request $request, TrainingModule $module, TrainingLesson $lesson)
    {
        // Normalize request data: Convert title_az, title_en format to object format
        $requestData = $this->normalizeTranslationRequest($request->all());
        $request->merge($requestData);

        $validated = $request->validate([
            'title' => ['sometimes', new \App\Rules\TranslationRule(true)],
            'lesson_type' => ['sometimes', 'in:text,video,audio,image,mixed'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'content' => ['nullable', new \App\Rules\TranslationRule(false)],
            'description' => ['nullable', new \App\Rules\TranslationRule(false)],
            'video_url' => ['nullable', 'url'],
            'pdf_url' => ['nullable', 'url'],
            'file_codes' => ['nullable', 'array'],
            'file_codes.*' => ['string', 'regex:/^FILE_[A-F0-9]{8}$/'],
            'media_files' => ['nullable', 'array'],
            'media_files.*.type' => ['required_with:media_files', 'in:image,video,audio,document'],
            'media_files.*.url' => ['required_with:media_files', 'string'],
            'media_files.*.title' => ['nullable', 'string'],
            'media_files.*.description' => ['nullable', 'string'],
            'sequence' => ['nullable', 'integer', 'min:1'],
            'status' => ['sometimes', 'in:draft,published,archived'],
            'is_required' => ['nullable', 'boolean'],
            'min_completion_time' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
        ]);

        // Process file codes and move files to final location (add to existing media_files)
        if (isset($validated['file_codes']) && is_array($validated['file_codes'])) {
            $existingMediaFiles = $lesson->media_files ?? [];
            $newMediaFiles = [];
            
            foreach ($validated['file_codes'] as $fileCode) {
                // Find temp file by code
                $tempFile = TempLessonFile::where('file_code', $fileCode)
                    ->where('expires_at', '>', now())
                    ->first();
                
                if ($tempFile) {
                    // Move file from temp to final location (use private storage for security)
                    $finalPath = 'lessons/' . $lesson->id . '/' . basename($tempFile->temp_path);
                    
                    // Move from public temp to private storage
                    if (Storage::disk('public')->exists($tempFile->temp_path)) {
                        $fileContent = Storage::disk('public')->get($tempFile->temp_path);
                        Storage::disk('local')->put($finalPath, $fileContent);
                        Storage::disk('public')->delete($tempFile->temp_path);
                    } else {
                        // If already in private, just move
                        Storage::disk('local')->move($tempFile->temp_path, $finalPath);
                    }
                    
                    // Create media file entry with protected URL
                    $mediaFile = [
                        'type' => $tempFile->type,
                        'url' => route('lesson.media.download', [
                            'module' => $lesson->module_id,
                            'lesson' => $lesson->id,
                            'path' => $finalPath
                        ]),
                        'path' => $finalPath, // Store path for download
                        'filename' => $tempFile->filename,
                        'size' => $tempFile->size,
                        'mime_type' => $tempFile->mime_type,
                        'title' => $tempFile->title,
                        'description' => $tempFile->description,
                    ];
                    
                    $newMediaFiles[] = $mediaFile;
                    
                    // Delete temp file record
                    $tempFile->delete();
                }
            }
            
            // Merge new files with existing ones
            $validated['media_files'] = array_merge($existingMediaFiles, $newMediaFiles);
        }

        // Handle media files update
        if (isset($validated['media_files'])) {
            $newMediaFiles = [];
            $oldMediaFiles = $lesson->media_files ?? [];
            
            foreach ($validated['media_files'] as $mediaFile) {
                if (isset($mediaFile['temp_path'])) {
                    // This is a new file from temp directory
                    $finalPath = 'lessons/' . $lesson->id . '/' . basename($mediaFile['temp_path']);
                    Storage::disk('public')->move($mediaFile['temp_path'], $finalPath);
                    
                    // Update URL and remove temp_path
                    $mediaFile['url'] = Storage::url($finalPath);
                    unset($mediaFile['temp_path']);
                }
                $newMediaFiles[] = $mediaFile;
            }
            
            // Delete old files that are not in the new list
            $oldUrls = collect($oldMediaFiles)->pluck('url')->toArray();
            $newUrls = collect($newMediaFiles)->pluck('url')->toArray();
            $filesToDelete = array_diff($oldUrls, $newUrls);
            
            foreach ($filesToDelete as $url) {
                $path = str_replace(Storage::url(''), '', $url);
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
            
            $validated['media_files'] = $newMediaFiles;
        }

        $lesson->update($validated);

        return response()->json($lesson->load('module'));
    }

    /**
     * Delete lesson
     */
    public function destroy(TrainingModule $module, TrainingLesson $lesson)
    {
        // Delete all media files associated with this lesson
        $mediaFiles = $lesson->media_files ?? [];
        
        foreach ($mediaFiles as $mediaFile) {
            if (isset($mediaFile['url'])) {
                // Extract file path from URL
                $url = $mediaFile['url'];
                $path = str_replace(Storage::url(''), '', $url);
                
                // Delete file from storage
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        }
        
        // Delete the lesson directory if it exists
        $lessonDir = 'lessons/' . $lesson->id;
        if (Storage::disk('public')->exists($lessonDir)) {
            Storage::disk('public')->deleteDirectory($lessonDir);
        }
        
        $lesson->delete();
        return response()->json(['message' => 'Lesson and all associated media files deleted successfully']);
    }

    /**
     * Mark lesson as completed by user
     */
    public function markCompleted(Request $request, TrainingLesson $lesson)
    {
        $validated = $request->validate([
            'time_spent' => ['nullable', 'integer', 'min:1'], // Time spent in seconds
            'notes' => ['nullable', 'string'],
        ]);

        $user = auth()->user();
        $training = $lesson->module->training;

        // For video trainings, registration is not required
        if ($training->type !== 'video') {
            // Check if user is registered for non-video trainings
            $registration = $training->registrations()
                ->where('user_id', $user->id)
                ->where('status', 'approved')
                ->first();

            if (!$registration) {
                return response()->json(['message' => 'Access denied. Please register for this training.'], 403);
            }
        }

        // Minimum completion time check is disabled
        // Users can complete lessons without time restrictions

        // Update or create progress
        $progress = UserTrainingProgress::updateOrCreate([
            'user_id' => $user->id,
            'training_id' => $training->id,
            'module_id' => $lesson->module_id,
            'lesson_id' => $lesson->id,
        ], [
            'status' => 'completed',
            'completed_at' => now(),
            'time_spent' => $validated['time_spent'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Check if training has certificate and all required lessons completed
        if ($training->has_certificate) {
            $requiredLessonIds = $training->modules()->with(['lessons' => function ($q) {
                $q->where('is_required', true);
            }])->get()->pluck('lessons')->flatten()->pluck('id')->all();

            $completedRequiredCount = UserTrainingProgress::where('user_id', $user->id)
                ->where('training_id', $training->id)
                ->whereIn('lesson_id', $requiredLessonIds)
                ->where('status', 'completed')
                ->count();

            if (count($requiredLessonIds) > 0 && $completedRequiredCount === count($requiredLessonIds)) {
                // For video trainings, create certificate without registration
                if ($training->type === 'video') {
                    // Check if certificate already exists
                    $existingCert = Certificate::where('user_id', $user->id)
                        ->where('related_training_id', $training->id)
                        ->first();

                    if (!$existingCert) {
                        // Calculate expiry date from Training settings
                        $expiryDate = null;
                        if ($training->certificate_has_expiry) {
                            $expiryDate = now();
                            if ($training->certificate_expiry_years) {
                                $expiryDate = $expiryDate->addYears($training->certificate_expiry_years);
                            }
                            if ($training->certificate_expiry_months) {
                                $expiryDate = $expiryDate->addMonths($training->certificate_expiry_months);
                            }
                            if ($training->certificate_expiry_days) {
                                $expiryDate = $expiryDate->addDays($training->certificate_expiry_days);
                            }
                            $expiryDate = $expiryDate->toDateString();
                        }
                        
                        $cert = Certificate::create([
                            'user_id' => $user->id,
                            'related_training_id' => $training->id,
                            'related_exam_id' => null,
                            'certificate_number' => Str::uuid()->toString(),
                            'issue_date' => now()->toDateString(),
                            'expiry_date' => $expiryDate,
                            'issuer_name' => 'Aqrar Portal',
                            'status' => 'active',
                        ]);
                    }
                } else {
                    // For non-video trainings, attach certificate to registration
                    $registration = TrainingRegistration::where('user_id', $user->id)
                        ->where('training_id', $training->id)
                        ->where('status', 'approved')
                        ->first();

                    if ($registration && !$registration->certificate_id) {
                        // Calculate expiry date from Training settings
                        $expiryDate = null;
                        if ($training->certificate_has_expiry) {
                            $expiryDate = now();
                            if ($training->certificate_expiry_years) {
                                $expiryDate = $expiryDate->addYears($training->certificate_expiry_years);
                            }
                            if ($training->certificate_expiry_months) {
                                $expiryDate = $expiryDate->addMonths($training->certificate_expiry_months);
                            }
                            if ($training->certificate_expiry_days) {
                                $expiryDate = $expiryDate->addDays($training->certificate_expiry_days);
                            }
                            $expiryDate = $expiryDate->toDateString();
                        }
                        
                        $cert = Certificate::create([
                            'user_id' => $user->id,
                            'related_training_id' => $training->id,
                            'related_exam_id' => null,
                            'certificate_number' => Str::uuid()->toString(),
                            'issue_date' => now()->toDateString(),
                            'expiry_date' => $expiryDate,
                            'issuer_name' => 'Aqrar Portal',
                            'status' => 'active',
                        ]);
                        $registration->update(['certificate_id' => $cert->id, 'status' => 'completed']);
                    }
                }
            }
        }

        return response()->json([
            'message' => 'Lesson marked as completed',
            'progress' => $progress
        ]);
    }

    /**
     * Get lesson progress for current user
     */
    public function getProgress(TrainingLesson $lesson)
    {
        $user = auth()->user();
        
        $progress = UserTrainingProgress::where([
            'user_id' => $user->id,
            'training_id' => $lesson->module->training_id,
            'module_id' => $lesson->module_id,
            'lesson_id' => $lesson->id,
        ])->first();

        return response()->json([
            'lesson_id' => $lesson->id,
            'progress' => $progress,
            'is_completed' => $progress && $progress->status === 'completed',
        ]);
    }

    /**
     * Upload temporary media files (before lesson creation)
     */
    public function uploadTempMedia(Request $request)
    {
        // Try to increase PHP upload limits for video files (100MB)
        @ini_set('upload_max_filesize', '105M');
        @ini_set('post_max_size', '110M');
        @ini_set('memory_limit', '512M');
        @ini_set('max_execution_time', '600');
        @ini_set('max_input_time', '600');

        // Check if file exists before validation
        if (!$request->hasFile('file')) {
            $uploadMaxSize = ini_get('upload_max_filesize');
            $postMaxSize = ini_get('post_max_size');
            
            return response()->json([
                'message' => 'The file failed to upload.',
                'errors' => [
                    'file' => [
                        'No file was uploaded. Please select a file.',
                        'PHP upload_max_filesize: ' . $uploadMaxSize,
                        'PHP post_max_size: ' . $postMaxSize,
                    ]
                ],
                'debug' => config('app.debug') ? [
                    'php_upload_max_filesize' => $uploadMaxSize,
                    'php_post_max_size' => $postMaxSize,
                    'content_length' => $request->header('Content-Length'),
                    'has_file' => $request->hasFile('file'),
                ] : null,
            ], 422);
        }

        $file = $request->file('file');
        
        // Check if file is valid
        if (!$file->isValid()) {
            $errorCode = $file->getError();
            $errorMessage = $file->getErrorMessage();
            $uploadMaxSize = ini_get('upload_max_filesize');
            $postMaxSize = ini_get('post_max_size');
            
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds PHP upload_max_filesize (' . $uploadMaxSize . ')',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds form MAX_FILE_SIZE limit',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'PHP extension stopped the file upload',
            ];
            
            $specificError = $errorMessages[$errorCode] ?? 'Unknown upload error: ' . $errorMessage;
            
            return response()->json([
                'message' => 'The file failed to upload.',
                'errors' => [
                    'file' => [
                        $specificError,
                        'PHP upload_max_filesize: ' . $uploadMaxSize,
                        'PHP post_max_size: ' . $postMaxSize,
                    ]
                ],
                'debug' => config('app.debug') ? [
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage,
                    'php_upload_max_filesize' => $uploadMaxSize,
                    'php_post_max_size' => $postMaxSize,
                    'file_size' => $file->getSize(),
                    'content_length' => $request->header('Content-Length'),
                ] : null,
            ], 422);
        }

        // First validate type to determine max size
        $typeValidated = $request->validate([
            'type' => ['required', 'in:image,video,audio,document'],
        ]);
        
        $type = $typeValidated['type'];
        
        // Determine max size based on file type (in KB)
        $maxSizeKB = 102400; // 100MB default for video
        
        if ($type === 'video') {
            $maxSizeKB = 102400; // 100MB for video
        } elseif ($type === 'image') {
            $maxSizeKB = 5120; // 5MB for images
        } elseif ($type === 'audio') {
            $maxSizeKB = 10240; // 10MB for audio
        } elseif ($type === 'document') {
            $maxSizeKB = 10240; // 10MB for documents
        }

        // Check file size manually before validation
        $fileSize = $file->getSize();
        $maxSizeBytes = $maxSizeKB * 1024;
        
        if ($fileSize > $maxSizeBytes) {
            $uploadMaxSize = ini_get('upload_max_filesize');
            $postMaxSize = ini_get('post_max_size');
            
            return response()->json([
                'message' => 'The file failed to upload.',
                'errors' => [
                    'file' => [
                        'File size (' . round($fileSize / 1024 / 1024, 2) . 'MB) exceeds maximum allowed size (' . round($maxSizeKB / 1024, 2) . 'MB) for ' . $type . ' files.',
                        'Maximum allowed: ' . round($maxSizeKB / 1024, 2) . 'MB',
                        'Your file: ' . round($fileSize / 1024 / 1024, 2) . 'MB',
                    ]
                ],
                'debug' => config('app.debug') ? [
                    'file_size' => $fileSize,
                    'max_size_bytes' => $maxSizeBytes,
                    'max_size_kb' => $maxSizeKB,
                    'file_type' => $type,
                    'php_upload_max_filesize' => $uploadMaxSize,
                    'php_post_max_size' => $postMaxSize,
                ] : null,
            ], 422);
        }

        // Now validate other fields
        try {
            $validated = $request->validate([
                'title' => ['nullable', 'string'],
                'description' => ['nullable', 'string'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
        
        // Merge validated data
        $validated['type'] = $type;

        $tempPath = $file->store('temp/lessons', 'public');
        $originalSize = $file->getSize();
        $thumbnailPath = null;
        $hlsMasterPlaylist = null;
        $hlsVariants = [];
        $hlsEnabled = config('ffmpeg.hls.enabled', false);

        // Generate unique code for this file (needed for HLS job)
        $fileCode = 'FILE_' . strtoupper(substr(md5(uniqid()), 0, 8));

        // Video HLS streaming və thumbnail generation (if video)
        if ($type === 'video') {
            // Generate thumbnail (tez proses)
            try {
                $fullPath = Storage::disk('public')->path($tempPath);
                $thumbnailService = new VideoThumbnailService();
                $thumbnailPath = $thumbnailService->generateThumbnail($fullPath, 1);
                
                \Log::info('Video thumbnail generated', [
                    'thumbnail_path' => $thumbnailPath,
                ]);
            } catch (\Exception $e) {
                \Log::warning('Thumbnail generation failed', [
                    'error' => $e->getMessage(),
                ]);
            }
            
            // HLS conversion - try synchronous first for small videos, background for large ones
            if ($hlsEnabled) {
                $fileSizeMB = $originalSize / 1024 / 1024;
                $maxSizeForSyncMB = 50; // 50MB - synchronous processing limit
                
                if ($fileSizeMB <= $maxSizeForSyncMB) {
                    // Small video - process synchronously for immediate response
                    try {
                        $hlsService = new \App\Services\HLSStreamingService();
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
                        foreach ($hlsStream['playlists'] as $quality => $playlistInfo) {
                            $hlsVariants[$quality] = [
                                'playlist' => $hlsStoragePath . '/' . basename($playlistInfo['playlist']),
                                'bandwidth' => $playlistInfo['bandwidth'],
                                'resolution' => $playlistInfo['resolution'],
                            ];
                        }
                        
                        // Clean up temp directory
                        $this->deleteDirectory($hlsOutputDir);
                        
                        \Log::info('HLS stream created synchronously for temp video', [
                            'file_code' => $fileCode,
                            'master_playlist' => $hlsMasterPlaylist,
                            'variants' => count($hlsVariants),
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Synchronous HLS processing failed, falling back to background job', [
                            'error' => $e->getMessage(),
                            'file_code' => $fileCode,
                        ]);
                        // Fallback to background job
                        \App\Jobs\ProcessTempVideoHLS::dispatch($fileCode, $tempPath)
                            ->delay(now()->addSeconds(2));
                    }
                } else {
                    // Large video - use background job
                    \App\Jobs\ProcessTempVideoHLS::dispatch($fileCode, $tempPath)
                        ->delay(now()->addSeconds(2));
                    
                    \Log::info('Large video uploaded, HLS conversion job dispatched', [
                        'temp_path' => $tempPath,
                        'file_code' => $fileCode,
                        'file_size_mb' => round($fileSizeMB, 2),
                    ]);
                }
            } else {
                \Log::info('Video uploaded, HLS conversion disabled', [
                    'temp_path' => $tempPath,
                ]);
            }
        }

        $mediaFile = [
            'type' => $validated['type'],
            'url' => Storage::url($tempPath),
            'filename' => $file->getClientOriginalName(),
            'size' => $originalSize,
            'mime_type' => $file->getMimeType(),
            'title' => $validated['title'] ?? $file->getClientOriginalName(),
            'description' => $validated['description'] ?? null,
            'temp_path' => $tempPath, // Store temp path for later move
            'thumbnail_path' => $thumbnailPath,
            // HLS streaming info
            'hls_master_playlist' => $hlsMasterPlaylist ? Storage::url($hlsMasterPlaylist) : null,
            'hls_variants' => $hlsVariants,
        ];

        // Store temp file info in database (HLS məlumatını da saxla)
        $tempFileDescription = $validated['description'] ?? null;
        if ($type === 'video' && ($hlsMasterPlaylist || !empty($hlsVariants))) {
            $hlsData = [
                'hls_master_playlist' => $hlsMasterPlaylist,
                'hls_variants' => $hlsVariants,
            ];
            
            // Parse existing description or use empty array
            $existingData = [];
            if ($tempFileDescription) {
                $decoded = json_decode($tempFileDescription, true);
                if (is_array($decoded)) {
                    $existingData = $decoded;
                }
            }
            
            $tempFileDescription = json_encode(array_merge($existingData, $hlsData));
        }
        
        $tempFile = TempLessonFile::create([
            'file_code' => $fileCode,
            'temp_path' => $tempPath,
            'type' => $validated['type'],
            'filename' => $file->getClientOriginalName(),
            'size' => $originalSize,
            'mime_type' => $file->getMimeType(),
            'title' => $validated['title'] ?? $file->getClientOriginalName(),
            'description' => $tempFileDescription,
            'expires_at' => now()->addHours(24), // 24 saat sonra expire
        ]);

        // Format HLS variants with URLs
        $formattedHlsVariants = [];
        foreach ($hlsVariants as $quality => $variant) {
            $formattedHlsVariants[$quality] = [
                'playlist' => $variant['playlist'],
                'playlist_url' => Storage::url($variant['playlist']),
                'bandwidth' => $variant['bandwidth'],
                'resolution' => $variant['resolution'],
            ];
        }
        
        return response()->json([
            'message' => 'Media uploaded successfully',
            'file_code' => $fileCode,
            'temp_url' => Storage::url($tempPath),
            'original_size' => $originalSize,
            'thumbnail_url' => $thumbnailPath ? Storage::url($thumbnailPath) : null,
            // HLS streaming info
            'hls_master_playlist' => $hlsMasterPlaylist ? Storage::url($hlsMasterPlaylist) : null,
            'hls_variants' => $formattedHlsVariants,
            'hls_processing' => $type === 'video' && $hlsEnabled && empty($hlsVariants) ? 'in_progress' : ($hlsMasterPlaylist ? 'completed' : null),
        ]);
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
            if ($item == '.' || $item == '..') {
                continue;
            }
            
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        
        return rmdir($dir);
    }

    /**
     * Delete temporary media file
     */
    public function deleteTempMedia(Request $request)
    {
        // Accept file_code from both query parameter and request body
        $fileCode = $request->input('file_code') ?? $request->query('file_code');
        
        if (!$fileCode) {
            return response()->json([
                'message' => 'File code is required',
                'errors' => [
                    'file_code' => ['The file_code field is required.']
                ]
            ], 422);
        }
        
        // Find temp file by code
        $tempFile = TempLessonFile::where('file_code', $fileCode)->first();
        
        if (!$tempFile) {
            return response()->json([
                'message' => 'File not found'
            ], 404);
        }
        
        // Delete physical file
        if (Storage::disk('public')->exists($tempFile->temp_path)) {
            Storage::disk('public')->delete($tempFile->temp_path);
        }
        
        // Delete HLS files if they exist (for videos)
        if ($tempFile->type === 'video') {
            try {
                $tempFileData = json_decode($tempFile->description ?? '{}', true);
                if (isset($tempFileData['hls_master_playlist'])) {
                    $hlsDir = dirname($tempFileData['hls_master_playlist']);
                    if (Storage::disk('public')->exists($hlsDir)) {
                        Storage::disk('public')->deleteDirectory($hlsDir);
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to delete HLS files', [
                    'error' => $e->getMessage(),
                    'file_code' => $fileCode
                ]);
            }
        }
        
        // Delete database record
        $tempFile->delete();
        
        return response()->json([
            'message' => 'Temporary media deleted successfully'
        ]);
    }

    /**
     * Upload media file for lesson
     */
    public function uploadMedia(Request $request, TrainingLesson $lesson)
    {
        // Try to increase PHP upload limits for video files (100MB)
        @ini_set('upload_max_filesize', '105M');
        @ini_set('post_max_size', '110M');
        @ini_set('memory_limit', '512M');
        @ini_set('max_execution_time', '600');
        @ini_set('max_input_time', '600');

        // First validate type to determine max size
        $typeValidated = $request->validate([
            'type' => ['required', 'in:image,video,audio,document'],
        ]);
        
        $type = $typeValidated['type'];
        
        // Determine max size based on file type (in KB)
        $maxSizeKB = 102400; // 100MB default for video
        
        if ($type === 'video') {
            $maxSizeKB = 102400; // 100MB for video
        } elseif ($type === 'image') {
            $maxSizeKB = 5120; // 5MB for images
        } elseif ($type === 'audio') {
            $maxSizeKB = 10240; // 10MB for audio
        } elseif ($type === 'document') {
            $maxSizeKB = 10240; // 10MB for documents
        }

        // Now validate file with dynamic max size
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:' . $maxSizeKB], // Dynamic max based on type
            'title' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]);
        
        // Merge validated data
        $validated['type'] = $type;

        $file = $request->file('file');
        // Store in private storage for security (not public)
        $path = $file->store('lessons/' . $lesson->id, 'local');
        $originalSize = $file->getSize();
        $thumbnailPath = null;
        $hlsMasterPlaylist = null;
        $hlsVariants = [];

        // Video HLS streaming və thumbnail generation (if video)
        if ($type === 'video') {
            try {
                $startTime = microtime(true);
                
                // Get full path to original video
                $fullPath = Storage::disk('local')->path($path);
                
                \Log::info('Starting HLS stream creation for uploaded media', [
                    'original_path' => $fullPath,
                    'original_size' => $originalSize,
                    'lesson_id' => $lesson->id,
                ]);
                
                // HLS stream yarat
                $hlsService = new HLSStreamingService();
                $hlsOutputDir = storage_path('app/temp/hls/' . uniqid());
                
                if (!file_exists($hlsOutputDir)) {
                    mkdir($hlsOutputDir, 0755, true);
                }
                
                // HLS stream yarat (480p, 720p, 1080p variantları)
                $hlsStream = $hlsService->createHLSStream($fullPath, $hlsOutputDir);
                
                // HLS fayllarını final storage-a köçür
                $hlsStoragePath = 'lessons/' . $lesson->id . '/hls/' . basename($hlsOutputDir);
                $hlsFiles = glob($hlsOutputDir . '/*');
                
                foreach ($hlsFiles as $hlsFile) {
                    if (is_file($hlsFile)) {
                        $relativePath = $hlsStoragePath . '/' . basename($hlsFile);
                        $content = file_get_contents($hlsFile);
                        Storage::disk('local')->put($relativePath, $content);
                    }
                }
                
                // Master playlist path
                $hlsMasterPlaylist = $hlsStoragePath . '/master.m3u8';
                
                // HLS variants məlumatı
                foreach ($hlsStream['playlists'] as $quality => $playlistInfo) {
                    $hlsVariants[$quality] = [
                        'playlist' => $hlsStoragePath . '/' . basename($playlistInfo['playlist']),
                        'bandwidth' => $playlistInfo['bandwidth'],
                        'resolution' => $playlistInfo['resolution'],
                    ];
                }
                
                // Temp directory-ni sil
                $this->deleteDirectory($hlsOutputDir);
                
                $endTime = microtime(true);
                $processingTime = round($endTime - $startTime, 2);
                
                \Log::info('HLS stream created successfully for uploaded media', [
                    'master_playlist' => $hlsMasterPlaylist,
                    'variants' => count($hlsVariants),
                    'processing_time' => $processingTime . 's',
                ]);
                
                // Generate thumbnail
                try {
                    $thumbnailService = new VideoThumbnailService();
                    $thumbnailPath = $thumbnailService->generateThumbnail($fullPath, 1);
                    
                    \Log::info('Video thumbnail generated for uploaded media', [
                        'thumbnail_path' => $thumbnailPath,
                    ]);
                } catch (\Exception $e) {
                    \Log::warning('Thumbnail generation failed for uploaded media', [
                        'error' => $e->getMessage(),
                    ]);
                }
                
            } catch (\Exception $e) {
                \Log::error('HLS stream creation failed for uploaded media', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                // Continue with original video if HLS fails
            }
        }

        // Create media file entry with protected URL
        $mediaFile = [
            'type' => $validated['type'],
            'url' => route('lesson.media.download', [
                'module' => $lesson->module_id,
                'lesson' => $lesson->id,
                'path' => $path
            ]),
            'path' => $path, // Store path for download
            'filename' => $file->getClientOriginalName(),
            'size' => $originalSize,
            'mime_type' => $file->getMimeType(),
            'title' => $validated['title'] ?? $file->getClientOriginalName(),
            'description' => $validated['description'] ?? null,
            'thumbnail_path' => $thumbnailPath,
            // HLS streaming info
            'hls_master_playlist' => $hlsMasterPlaylist,
            'hls_variants' => $hlsVariants,
        ];

        $currentMedia = $lesson->media_files ?? [];
        $currentMedia[] = $mediaFile;

        $lesson->update(['media_files' => $currentMedia]);

        return response()->json([
            'message' => 'Media uploaded successfully',
            'media_file' => $mediaFile,
            'lesson' => $lesson->fresh()
        ], 201);
    }

    /**
     * Remove media file from lesson
     */
    public function removeMedia(Request $request, TrainingLesson $lesson)
    {
        $validated = $request->validate([
            'media_index' => ['required', 'integer', 'min:0'],
        ]);

        $mediaFiles = $lesson->media_files ?? [];
        
        if (!isset($mediaFiles[$validated['media_index']])) {
            return response()->json(['message' => 'Media file not found'], 404);
        }

        $removedFile = $mediaFiles[$validated['media_index']];
        unset($mediaFiles[$validated['media_index']]);
        
        // Reindex array
        $mediaFiles = array_values($mediaFiles);

        $lesson->update(['media_files' => $mediaFiles]);

        return response()->json([
            'message' => 'Media removed successfully',
            'removed_file' => $removedFile,
            'lesson' => $lesson->fresh()
        ]);
    }

    /**
     * Reorder lessons in a module
     */
    public function reorder(Request $request, TrainingModule $module)
    {
        $validated = $request->validate([
            'lesson_order' => ['required', 'array'],
            'lesson_order.*' => ['required', 'integer', 'exists:training_lessons,id'],
        ]);

        foreach ($validated['lesson_order'] as $index => $lessonId) {
            TrainingLesson::where('id', $lessonId)
                ->where('module_id', $module->id)
                ->update(['sequence' => $index + 1]);
        }

        return response()->json([
            'message' => 'Lessons reordered successfully',
            'lessons' => $module->lessons()->orderBy('sequence')->get()
        ]);
    }

    /**
     * Normalize translation request data
     * Converts format like {title_az: "...", title_en: "..."} to {title: {az: "...", en: "..."}}
     */
    private function normalizeTranslationRequest(array $data): array
    {
        $translatableFields = ['title', 'content', 'description'];
        $normalized = $data;

        foreach ($translatableFields as $field) {
            // Check if field comes as separate language fields (title_az, title_en, etc.)
            $azKey = $field . '_az';
            $enKey = $field . '_en';
            $ruKey = $field . '_ru';

            if (isset($data[$azKey]) || isset($data[$enKey]) || isset($data[$ruKey])) {
                // Build translation object
                $translations = [];
                if (isset($data[$azKey])) {
                    $translations['az'] = $data[$azKey];
                    unset($normalized[$azKey]);
                }
                if (isset($data[$enKey])) {
                    $translations['en'] = $data[$enKey];
                    unset($normalized[$enKey]);
                }
                if (isset($data[$ruKey])) {
                    $translations['ru'] = $data[$ruKey];
                    unset($normalized[$ruKey]);
                }

                // If there's also a direct field value (for backward compatibility)
                if (isset($data[$field]) && !is_array($data[$field])) {
                    // Use direct value as default az if az not provided
                    if (!isset($translations['az'])) {
                        $translations['az'] = $data[$field];
                    }
                }

                $normalized[$field] = $translations;
            } elseif (isset($data[$field]) && !is_array($data[$field])) {
                // Single string value - convert to translation object with az
                $normalized[$field] = ['az' => $data[$field]];
            }
            // If already in object format, keep it as is
        }

        return $normalized;
    }
}
