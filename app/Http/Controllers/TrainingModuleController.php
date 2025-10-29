<?php

namespace App\Http\Controllers;

use App\Models\Training;
use App\Models\TrainingModule;
use Illuminate\Http\Request;

class TrainingModuleController extends Controller
{
    /**
     * Display a listing of modules for a specific training.
     */
    public function index(Training $training)
    {
        return $training->modules()->with('lessons')->orderBy('sequence')->paginate(15);
    }

    /**
     * Store a newly created module for a training.
     */
    public function store(Request $request, Training $training)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'sequence' => ['nullable', 'integer', 'min:1'],
        ]);

        // Check if module with same title already exists
        $existingModule = $training->modules()->where('title', $validated['title'])->first();
        if ($existingModule) {
            return response()->json([
                'message' => 'Module with this title already exists',
                'existing_module' => $existingModule
            ], 409);
        }

        // If no sequence provided, set it to the next available sequence
        if (!isset($validated['sequence'])) {
            $maxSequence = $training->modules()->max('sequence') ?? 0;
            $validated['sequence'] = $maxSequence + 1;
        }

        $validated['training_id'] = $training->id;
        
        $module = TrainingModule::create($validated);
        
        return response()->json($module->load('lessons'), 201);
    }

    /**
     * Display the specified module.
     */
    public function show(Training $training, TrainingModule $module)
    {
        // Ensure the module belongs to the specified training
        if ($module->training_id !== $training->id) {
            abort(404, 'Module not found in this training');
        }

        return $module->load('lessons');
    }

    /**
     * Update the specified module.
     */
    public function update(Request $request, Training $training, TrainingModule $module)
    {
        // Ensure the module belongs to the specified training
        if ($module->training_id !== $training->id) {
            abort(404, 'Module not found in this training');
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'sequence' => ['sometimes', 'integer', 'min:1'],
        ]);

        $module->update($validated);
        
        return response()->json($module->load('lessons'));
    }

    /**
     * Remove the specified module.
     */
    public function destroy(Training $training, TrainingModule $module)
    {
        // Ensure the module belongs to the specified training
        if ($module->training_id !== $training->id) {
            abort(404, 'Module not found in this training');
        }

        // Check if module has lessons
        if ($module->lessons()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete module with existing lessons. Please delete or move the lessons first.'
            ], 422);
        }

        $module->delete();
        
        return response()->json(['message' => 'Module deleted successfully']);
    }
}
