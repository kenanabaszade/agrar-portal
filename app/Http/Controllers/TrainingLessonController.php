<?php

namespace App\Http\Controllers;

use App\Models\TrainingLesson;
use App\Models\TrainingModule;
use App\Models\UserTrainingProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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
            'media_files' => ['nullable', 'array'],
            'media_files.*.type' => ['required_with:media_files', 'in:image,video,audio,document'],
            'media_files.*.url' => ['required_with:media_files', 'url'],
            'media_files.*.title' => ['nullable', 'string'],
            'media_files.*.description' => ['nullable', 'string'],
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
            'media_files.*.url' => ['required_with:media_files', 'url'],
            'media_files.*.title' => ['nullable', 'string'],
            'media_files.*.description' => ['nullable', 'string'],
            'sequence' => ['nullable', 'integer', 'min:1'],
            'status' => ['sometimes', 'in:draft,published,archived'],
            'is_required' => ['nullable', 'boolean'],
            'min_completion_time' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
        ]);

        $lesson->update($validated);

        return response()->json($lesson->load('module'));
    }

    /**
     * Delete lesson
     */
    public function destroy(TrainingModule $module, TrainingLesson $lesson)
    {
        $lesson->delete();
        return response()->json(['message' => 'Lesson deleted successfully']);
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

        // Check if user is registered for the training
        $training = $lesson->module->training;
        $registration = $training->registrations()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->first();

        if (!$registration) {
            return response()->json(['message' => 'Access denied. Please register for this training.'], 403);
        }

        // Check minimum completion time if set
        if ($lesson->min_completion_time && $validated['time_spent'] < $lesson->min_completion_time) {
            return response()->json([
                'message' => 'Minimum completion time not met',
                'required_time' => $lesson->min_completion_time,
                'spent_time' => $validated['time_spent']
            ], 422);
        }

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
