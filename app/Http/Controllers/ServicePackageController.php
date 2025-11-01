<?php

namespace App\Http\Controllers;

use App\Models\ServicePackage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class ServicePackageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ServicePackage::query();

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by recommended
        if ($request->has('is_recommended')) {
            $query->where('is_recommended', $request->boolean('is_recommended'));
        }

        // Filter by price type
        if ($request->has('price_type')) {
            $query->where('price_type', $request->price_type);
        }

        // Order by sort_order, then by created_at
        $packages = $query->orderBy('sort_order', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $packages,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'price_type' => ['required', Rule::in(['free', 'monthly', 'annual'])],
            'price_label' => 'nullable|string|max:255',
            'is_recommended' => 'boolean',
            'features' => 'nullable|array',
            'features.*' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $package = ServicePackage::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Service package created successfully',
            'data' => $package,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ServicePackage $servicePackage): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $servicePackage,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServicePackage $servicePackage): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'price_type' => ['sometimes', 'required', Rule::in(['free', 'monthly', 'annual'])],
            'price_label' => 'nullable|string|max:255',
            'is_recommended' => 'boolean',
            'features' => 'nullable|array',
            'features.*' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $servicePackage->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Service package updated successfully',
            'data' => $servicePackage->fresh(),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServicePackage $servicePackage): JsonResponse
    {
        $servicePackage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Service package deleted successfully',
        ]);
    }
}
