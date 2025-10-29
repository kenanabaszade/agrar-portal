<?php

namespace App\Http\Controllers;

use App\Models\UserTrainingProgress;
use Illuminate\Http\Request;
 
class ProgressController extends Controller
{
   
    public function index(Request $request)
    {
        return UserTrainingProgress::where('user_id', $request->user()->id)->paginate(50);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'training_id' => ['required', 'exists:trainings,id'],
            'module_id' => ['required', 'exists:training_modules,id'],
            'lesson_id' => ['required', 'exists:training_lessons,id'],
            'status' => ['required', 'in:not_started,in_progress,completed'],
            'last_accessed' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'time_spent' => ['nullable', 'integer', 'min:0'],
        ]);

        $progress = UserTrainingProgress::updateOrCreate([
            'user_id' => $request->user()->id,
            'lesson_id' => $validated['lesson_id'],
        ], array_merge($validated, ['user_id' => $request->user()->id]));

        return response()->json($progress, 201);
    }

    /**
     * Get specific progress record
     * GET /api/v1/progress/{progress}
     */
    public function show(UserTrainingProgress $progress)
    {
        // Ensure user can only access their own progress
        if ($progress->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($progress);
    }

    /**
     * Update progress record
     * PUT /api/v1/progress/{progress}
     */
    public function update(Request $request, UserTrainingProgress $progress)
    {
        // Ensure user can only update their own progress
        if ($progress->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => ['sometimes', 'in:not_started,in_progress,completed'],
            'last_accessed' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'time_spent' => ['nullable', 'integer', 'min:0'],
        ]);

        $progress->update($validated);

        return response()->json($progress);
    }

    /**
     * Delete progress record
     * DELETE /api/v1/progress/{progress}
     */
    public function destroy(UserTrainingProgress $progress)
    {
        // Ensure user can only delete their own progress
        if ($progress->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $progress->delete();

        return response()->json(['message' => 'Progress record deleted successfully']);
    }

    /**
     * Add or update notes for a specific lesson
     * POST /api/v1/lessons/{lesson}/notes
     */
    public function addLessonNotes(Request $request, \App\Models\TrainingLesson $lesson)
    {
        $user = auth()->user();
        
        // Check if training is video type (notes only allowed for video trainings)
        $training = $lesson->module->training;
        if ($training->type !== 'video') {
            return response()->json(['message' => 'Notes are only allowed for video trainings.'], 403);
        }

        $validated = $request->validate([
            'notes' => ['required', 'string', 'max:5000'],
        ]);

        $progress = UserTrainingProgress::updateOrCreate([
            'user_id' => $user->id,
            'training_id' => $training->id,
            'module_id' => $lesson->module_id,
            'lesson_id' => $lesson->id,
        ], [
            'status' => 'in_progress', // Mark as in progress if not completed
            'notes' => $validated['notes'],
            'last_accessed' => now(),
        ]);

        return response()->json([
            'message' => 'Notes saved successfully',
            'progress' => $progress
        ]);
    }

    /**
     * Get notes for a specific lesson
     * GET /api/v1/lessons/{lesson}/notes
     */
    public function getLessonNotes(\App\Models\TrainingLesson $lesson)
    {
        $user = auth()->user();
        
        // Check if training is video type (notes only allowed for video trainings)
        $training = $lesson->module->training;
        if ($training->type !== 'video') {
            return response()->json(['message' => 'Notes are only allowed for video trainings.'], 403);
        }

        $progress = UserTrainingProgress::where([
            'user_id' => $user->id,
            'training_id' => $training->id,
            'module_id' => $lesson->module_id,
            'lesson_id' => $lesson->id,
        ])->first();

        return response()->json([
            'lesson_id' => $lesson->id,
            'lesson_title' => $lesson->title,
            'lesson_type' => $lesson->lesson_type,
            'training_type' => $training->type,
            'notes' => $progress ? $progress->notes : null,
            'status' => $progress ? $progress->status : 'not_started',
            'last_accessed' => $progress ? $progress->last_accessed : null,
        ]);
    }

    /**
     * Update notes for a specific lesson
     * PUT /api/v1/lessons/{lesson}/notes
     */
    public function updateLessonNotes(Request $request, \App\Models\TrainingLesson $lesson)
    {
        $user = auth()->user();
        
        // Check if training is video type (notes only allowed for video trainings)
        $training = $lesson->module->training;
        if ($training->type !== 'video') {
            return response()->json(['message' => 'Notes are only allowed for video trainings.'], 403);
        }

        $validated = $request->validate([
            'notes' => ['required', 'string', 'max:5000'],
        ]);

        $progress = UserTrainingProgress::where([
            'user_id' => $user->id,
            'training_id' => $training->id,
            'module_id' => $lesson->module_id,
            'lesson_id' => $lesson->id,
        ])->first();

        if (!$progress) {
            return response()->json(['message' => 'No progress record found for this lesson'], 404);
        }

        $progress->update([
            'notes' => $validated['notes'],
            'last_accessed' => now(),
        ]);

        return response()->json([
            'message' => 'Notes updated successfully',
            'progress' => $progress
        ]);
    }

    /**
     * Delete notes for a specific lesson
     * DELETE /api/v1/lessons/{lesson}/notes
     */
    public function deleteLessonNotes(\App\Models\TrainingLesson $lesson)
    {
        $user = auth()->user();
        
        // Check if training is video type (notes only allowed for video trainings)
        $training = $lesson->module->training;
        if ($training->type !== 'video') {
            return response()->json(['message' => 'Notes are only allowed for video trainings.'], 403);
        }

        $progress = UserTrainingProgress::where([
            'user_id' => $user->id,
            'training_id' => $training->id,
            'module_id' => $lesson->module_id,
            'lesson_id' => $lesson->id,
        ])->first();

        if (!$progress) {
            return response()->json(['message' => 'No progress record found for this lesson'], 404);
        }

        $progress->update([
            'notes' => null,
            'last_accessed' => now(),
        ]);

        return response()->json([
            'message' => 'Notes deleted successfully',
            'progress' => $progress
        ]);
    }
}


