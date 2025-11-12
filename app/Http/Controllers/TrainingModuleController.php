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
        // Normalize request data: Convert title_az, title_en format to object format
        $requestData = $this->normalizeTranslationRequest($request->all());
        $request->merge($requestData);

        $validated = $request->validate([
            'title' => ['required', new \App\Rules\TranslationRule(true)],
            'sequence' => ['nullable', 'integer', 'min:1'],
        ]);

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

        // Normalize request data: Convert title_az, title_en format to object format
        $requestData = $this->normalizeTranslationRequest($request->all());
        $request->merge($requestData);

        $validated = $request->validate([
            'title' => ['sometimes', new \App\Rules\TranslationRule(true)],
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

    /**
     * Normalize translation request data
     * Converts format like {title_az: "...", title_en: "..."} to {title: {az: "...", en: "..."}}
     */
    private function normalizeTranslationRequest(array $data): array
    {
        $translatableFields = ['title'];
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
