<?php

namespace App\Http\Controllers;

use App\Models\Training;
use Illuminate\Http\Request;

class TrainingController extends Controller
{
    public function index()
    {
        return Training::with('modules.lessons')->paginate(15);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:255'],
            'trainer_id' => ['required', 'exists:users,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_online' => ['boolean'],
        ]);

        $training = Training::create($validated);
        return response()->json($training, 201);
    }

    public function show(Training $training)
    {
        return $training->load('modules.lessons');
    }

    public function update(Request $request, Training $training)
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:255'],
            'trainer_id' => ['sometimes', 'exists:users,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_online' => ['boolean'],
        ]);

        $training->update($validated);
        return response()->json($training);
    }

    public function destroy(Training $training)
    {
        $training->delete();
        return response()->json(['message' => 'Deleted']);
    }
}


