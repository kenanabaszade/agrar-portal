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
        ]);

        $progress = UserTrainingProgress::updateOrCreate([
            'user_id' => $request->user()->id,
            'lesson_id' => $validated['lesson_id'],
        ], array_merge($validated, ['user_id' => $request->user()->id]));

        return response()->json($progress, 201);
    }
}


