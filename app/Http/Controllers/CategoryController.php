<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories
     */
    public function index(Request $request)
    {
        $query = Category::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'sort_order');
        $sortOrder = $request->get('sort_order', 'asc');
        
        if (in_array($sortBy, ['name', 'sort_order', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->ordered();
        }

        $perPage = min($request->get('per_page', 15), 100);
        $categories = $query->paginate($perPage);

        return response()->json($categories);
    }

    /**
     * Store a newly created category
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $category = Category::create($validated);

        // Add audit log
        \App\Models\AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'created',
            'entity' => 'category',
            'entity_id' => $category->id,
            'details' => [
                'name' => $category->name,
                'description' => $category->description,
            ]
        ]);

        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category
        ], 201);
    }

    /**
     * Display the specified category
     */
    public function show(Category $category)
    {
        $category->load(['trainings' => function ($query) {
            $query->with('trainer:id,first_name,last_name')->limit(10);
        }]);

        // Add statistics
        $category->stats = [
            'total_trainings' => $category->trainings()->count(),
            'active_trainings' => $category->trainings()->where('is_active', true)->count(),
        ];

        return response()->json($category);
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', 'unique:categories,name,' . $category->id],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        // Store original values for audit
        $originalValues = $category->only(array_keys($validated));
        
        $category->update($validated);

        // Add audit log
        \App\Models\AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'updated',
            'entity' => 'category',
            'entity_id' => $category->id,
            'details' => [
                'name' => $category->name,
                'changes' => array_diff_assoc($validated, $originalValues),
                'original' => $originalValues,
            ]
        ]);

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category
        ]);
    }

    /**
     * Remove the specified category
     */
    public function destroy(Category $category)
    {
        // Check if category has trainings
        $trainingsCount = $category->trainings()->count();
        
        if ($trainingsCount > 0) {
            return response()->json([
                'message' => 'Cannot delete category with existing trainings',
                'trainings_count' => $trainingsCount
            ], 422);
        }

        // Store category data for audit log before deletion
        $categoryData = [
            'name' => $category->name,
            'description' => $category->description,
        ];

        $categoryId = $category->id;
        $category->delete();

        // Add audit log
        \App\Models\AuditLog::create([
            'user_id' => request()->user()->id,
            'action' => 'deleted',
            'entity' => 'category',
            'entity_id' => $categoryId,
            'details' => $categoryData
        ]);

        return response()->json([
            'message' => 'Category deleted successfully'
        ]);
    }

    /**
     * Get active categories for dropdown
     */
    public function dropdown()
    {
        $categories = Category::active()->ordered()->get(['id', 'name']);
        
        return response()->json($categories);
    }
}
