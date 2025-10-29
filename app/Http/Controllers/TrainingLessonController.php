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
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'lesson_type' => ['required', 'in:text,video,audio,image,mixed'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'content' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
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
                    // Move file from temp to final location
                    $finalPath = 'lessons/' . $lesson->id . '/' . basename($tempFile->temp_path);
                    Storage::disk('public')->move($tempFile->temp_path, $finalPath);
                    
                    // Create media file entry
                    $mediaFile = [
                        'type' => $tempFile->type,
                        'url' => Storage::url($finalPath),
                        'filename' => $tempFile->filename,
                        'size' => $tempFile->size,
                        'mime_type' => $tempFile->mime_type,
                        'title' => $tempFile->title,
                        'description' => $tempFile->description,
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
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'lesson_type' => ['sometimes', 'in:text,video,audio,image,mixed'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'content' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'video_url' => ['nullable', 'url'],
            'pdf_url' => ['nullable', 'url'],
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
                        $cert = Certificate::create([
                            'user_id' => $user->id,
                            'related_training_id' => $training->id,
                            'related_exam_id' => null,
                            'certificate_number' => Str::uuid()->toString(),
                            'issue_date' => now()->toDateString(),
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
                        $cert = Certificate::create([
                            'user_id' => $user->id,
                            'related_training_id' => $training->id,
                            'related_exam_id' => null,
                            'certificate_number' => Str::uuid()->toString(),
                            'issue_date' => now()->toDateString(),
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
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:102400'], // 100MB max
            'type' => ['required', 'in:image,video,audio,document'],
            'title' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        $file = $request->file('file');
        $tempPath = $file->store('temp/lessons', 'public');

        $mediaFile = [
            'type' => $validated['type'],
            'url' => Storage::url($tempPath),
            'filename' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'title' => $validated['title'] ?? $file->getClientOriginalName(),
            'description' => $validated['description'] ?? null,
            'temp_path' => $tempPath, // Store temp path for later move
        ];

        // Generate unique code for this file
        $fileCode = 'FILE_' . strtoupper(substr(md5(uniqid()), 0, 8));
        
        // Store temp file info in database
        $tempFile = TempLessonFile::create([
            'file_code' => $fileCode,
            'temp_path' => $tempPath,
            'type' => $validated['type'],
            'filename' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'title' => $validated['title'] ?? $file->getClientOriginalName(),
            'description' => $validated['description'] ?? null,
            'expires_at' => now()->addHours(24), // 24 saat sonra expire
        ]);

        return response()->json([
            'message' => 'Media uploaded successfully',
            'file_code' => $fileCode,
            'temp_url' => Storage::url($tempPath)
        ]);
    }

    /**
     * Delete temporary media file
     */
    public function deleteTempMedia(Request $request)
    {
        $validated = $request->validate([
            'file_code' => ['required', 'string'],
        ]);

        $fileCode = $validated['file_code'];
        
        // Find temp file by code
        $tempFile = TempLessonFile::where('file_code', $fileCode)->first();
        
        if ($tempFile) {
            // Delete physical file
            if (Storage::disk('public')->exists($tempFile->temp_path)) {
                Storage::disk('public')->delete($tempFile->temp_path);
            }
            
            // Delete database record
            $tempFile->delete();
            
            return response()->json([
                'message' => 'Temporary media deleted successfully'
            ]);
        }

        return response()->json([
            'message' => 'File not found'
        ], 404);
    }

    /**
     * Upload media file for lesson
     */
    public function uploadMedia(Request $request, TrainingLesson $lesson)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:102400'], // 100MB max
            'type' => ['required', 'in:image,video,audio,document'],
            'title' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        $file = $request->file('file');
        $path = $file->store('lessons/' . $lesson->id, 'public');

        $mediaFile = [
            'type' => $validated['type'],
            'url' => Storage::url($path),
            'filename' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'title' => $validated['title'] ?? $file->getClientOriginalName(),
            'description' => $validated['description'] ?? null,
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
}
