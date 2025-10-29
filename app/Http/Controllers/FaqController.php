<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Http\Requests\FaqRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FaqController extends Controller
{
    /**
     * Display a listing of FAQs (no limit - get all)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Faq::with('creator:id,first_name,last_name');

        // Search functionality
        if ($request->filled('search')) {
            $query->search($request->get('search'));
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->byCategory($request->get('category'));
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        if (in_array($sortBy, ['question', 'category', 'helpful_count', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Get all FAQs without pagination as requested
        $faqs = $query->get();

        return response()->json([
            'success' => true,
            'data' => $faqs,
            'total' => $faqs->count()
        ]);
    }

    /**
     * Store a newly created FAQ
     */
    public function store(FaqRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $faq = Faq::create([
            ...$validated,
            'created_by' => $request->user()->id,
            'helpful_count' => 0,
        ]);

        // Add audit log
        \App\Models\AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'created',
            'entity' => 'faq',
            'entity_id' => $faq->id,
            'details' => [
                'question' => $faq->question,
                'category' => $faq->category,
            ]
        ]);

        return response()->json([
            'success' => true,
            'message' => 'FAQ created successfully',
            'data' => $faq->load('creator:id,first_name,last_name')
        ], 201);
    }

    /**
     * Display the specified FAQ
     */
    public function show(Faq $faq): JsonResponse
    {
        $faq->load('creator:id,first_name,last_name');
        
        return response()->json([
            'success' => true,
            'data' => $faq
        ]);
    }

    /**
     * Update the specified FAQ
     */
    public function update(FaqRequest $request, Faq $faq): JsonResponse
    {
        $validated = $request->validated();

        // Store original values for audit
        $originalValues = $faq->only(array_keys($validated));
        
        $faq->update($validated);

        // Add audit log
        \App\Models\AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'updated',
            'entity' => 'faq',
            'entity_id' => $faq->id,
            'details' => [
                'question' => $faq->question,
                'changes' => array_diff_assoc($validated, $originalValues),
                'original' => $originalValues,
            ]
        ]);

        return response()->json([
            'success' => true,
            'message' => 'FAQ updated successfully',
            'data' => $faq->load('creator:id,first_name,last_name')
        ]);
    }

    /**
     * Remove the specified FAQ
     */
    public function destroy(Faq $faq): JsonResponse
    {
        // Store FAQ data for audit log before deletion
        $faqData = [
            'question' => $faq->question,
            'category' => $faq->category,
        ];

        $faqId = $faq->id;
        $faq->delete();

        // Add audit log
        \App\Models\AuditLog::create([
            'user_id' => request()->user()->id,
            'action' => 'deleted',
            'entity' => 'faq',
            'entity_id' => $faqId,
            'details' => $faqData
        ]);

        return response()->json([
            'success' => true,
            'message' => 'FAQ deleted successfully'
        ]);
    }

    /**
     * Mark FAQ as helpful (increment helpful_count)
     */
    public function markHelpful(Faq $faq): JsonResponse
    {
        $faq->increment('helpful_count');

        return response()->json([
            'success' => true,
            'message' => 'FAQ marked as helpful',
            'helpful_count' => $faq->helpful_count
        ]);
    }

    /**
     * Get FAQ categories
     */
    public function categories(): JsonResponse
    {
        $categories = Faq::select('category')
            ->distinct()
            ->where('is_active', true)
            ->orderBy('category')
            ->pluck('category');

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Get FAQ statistics
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_faqs' => Faq::count(),
            'active_faqs' => Faq::where('is_active', true)->count(),
            'inactive_faqs' => Faq::where('is_active', false)->count(),
            'total_helpful_votes' => Faq::sum('helpful_count'),
            'categories_count' => Faq::select('category')->distinct()->count(),
            'most_helpful' => Faq::where('is_active', true)
                ->orderBy('helpful_count', 'desc')
                ->limit(5)
                ->get(['id', 'question', 'helpful_count'])
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
