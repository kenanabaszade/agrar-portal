<?php

namespace App\Http\Controllers;

use App\Models\PrivacyPolicy;
use App\Models\TermsOfService;
use App\Services\TranslationHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LegalDocumentController extends Controller
{
    /**
     * Get all privacy policies (admin only)
     */
    public function indexPrivacyPolicies(Request $request): JsonResponse
    {
        $policies = PrivacyPolicy::with(['creator', 'updater'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 20));

        return response()->json($policies);
    }

    /**
     * Get active privacy policy (public)
     */
    public function getActivePrivacyPolicy(Request $request): JsonResponse
    {
        $lang = $request->get('lang', 'az');
        
        $policy = PrivacyPolicy::where('is_active', true)
            ->orderBy('effective_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$policy) {
            return response()->json([
                'message' => 'Privacy policy not found'
            ], 404);
        }

        $content = TranslationHelper::getTranslated($policy->content, $lang);

        return response()->json([
            'id' => $policy->id,
            'content' => $content,
            'content_full' => $policy->content, // Full translations
            'version' => $policy->version,
            'effective_date' => $policy->effective_date,
            'created_at' => $policy->created_at,
            'updated_at' => $policy->updated_at,
        ]);
    }

    /**
     * Get privacy policy by ID (admin)
     */
    public function showPrivacyPolicy(PrivacyPolicy $privacyPolicy): JsonResponse
    {
        $privacyPolicy->load(['creator', 'updater']);

        return response()->json([
            'id' => $privacyPolicy->id,
            'content' => $privacyPolicy->content,
            'is_active' => $privacyPolicy->is_active,
            'version' => $privacyPolicy->version,
            'effective_date' => $privacyPolicy->effective_date,
            'created_by' => $privacyPolicy->creator ? [
                'id' => $privacyPolicy->creator->id,
                'name' => $privacyPolicy->creator->first_name . ' ' . $privacyPolicy->creator->last_name,
            ] : null,
            'updated_by' => $privacyPolicy->updater ? [
                'id' => $privacyPolicy->updater->id,
                'name' => $privacyPolicy->updater->first_name . ' ' . $privacyPolicy->updater->last_name,
            ] : null,
            'created_at' => $privacyPolicy->created_at,
            'updated_at' => $privacyPolicy->updated_at,
        ]);
    }

    /**
     * Create privacy policy
     */
    public function storePrivacyPolicy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', new \App\Rules\TranslationRule(true)],
            'is_active' => ['nullable', 'boolean'],
            'version' => ['nullable', 'integer', 'min:1'],
            'effective_date' => ['nullable', 'date'],
        ]);

        // Normalize content translation
        $validated['content'] = TranslationHelper::normalizeTranslation($validated['content']);
        $validated['created_by'] = $request->user()->id;
        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['version'] = $validated['version'] ?? 1;

        // If this is a new active policy, deactivate old ones
        if ($validated['is_active']) {
            PrivacyPolicy::where('is_active', true)->update(['is_active' => false]);
        }

        $policy = PrivacyPolicy::create($validated);

        return response()->json([
            'message' => 'Privacy policy created successfully',
            'data' => $policy->load(['creator', 'updater'])
        ], 201);
    }

    /**
     * Update privacy policy
     */
    public function updatePrivacyPolicy(Request $request, PrivacyPolicy $privacyPolicy): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['sometimes', new \App\Rules\TranslationRule(true)],
            'is_active' => ['nullable', 'boolean'],
            'version' => ['nullable', 'integer', 'min:1'],
            'effective_date' => ['nullable', 'date'],
        ]);

        // Normalize content translation if provided
        if (isset($validated['content'])) {
            $validated['content'] = TranslationHelper::normalizeTranslation($validated['content']);
        }

        $validated['updated_by'] = $request->user()->id;

        // If activating this policy, deactivate others
        if (isset($validated['is_active']) && $validated['is_active']) {
            PrivacyPolicy::where('is_active', true)
                ->where('id', '!=', $privacyPolicy->id)
                ->update(['is_active' => false]);
        }

        $privacyPolicy->update($validated);

        return response()->json([
            'message' => 'Privacy policy updated successfully',
            'data' => $privacyPolicy->load(['creator', 'updater'])
        ]);
    }

    /**
     * Delete privacy policy
     */
    public function destroyPrivacyPolicy(PrivacyPolicy $privacyPolicy): JsonResponse
    {
        $privacyPolicy->delete();

        return response()->json([
            'message' => 'Privacy policy deleted successfully'
        ]);
    }

    /**
     * Get all terms of service (admin only)
     */
    public function indexTermsOfService(Request $request): JsonResponse
    {
        $terms = TermsOfService::with(['creator', 'updater'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 20));

        return response()->json($terms);
    }

    /**
     * Get active terms of service (public)
     */
    public function getActiveTermsOfService(Request $request): JsonResponse
    {
        $lang = $request->get('lang', 'az');
        
        $terms = TermsOfService::where('is_active', true)
            ->orderBy('effective_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$terms) {
            return response()->json([
                'message' => 'Terms of service not found'
            ], 404);
        }

        $content = TranslationHelper::getTranslated($terms->content, $lang);

        return response()->json([
            'id' => $terms->id,
            'content' => $content,
            'content_full' => $terms->content, // Full translations
            'version' => $terms->version,
            'effective_date' => $terms->effective_date,
            'created_at' => $terms->created_at,
            'updated_at' => $terms->updated_at,
        ]);
    }

    /**
     * Get terms of service by ID (admin)
     */
    public function showTermsOfService(TermsOfService $termsOfService): JsonResponse
    {
        $termsOfService->load(['creator', 'updater']);

        return response()->json([
            'id' => $termsOfService->id,
            'content' => $termsOfService->content,
            'is_active' => $termsOfService->is_active,
            'version' => $termsOfService->version,
            'effective_date' => $termsOfService->effective_date,
            'created_by' => $termsOfService->creator ? [
                'id' => $termsOfService->creator->id,
                'name' => $termsOfService->creator->first_name . ' ' . $termsOfService->creator->last_name,
            ] : null,
            'updated_by' => $termsOfService->updater ? [
                'id' => $termsOfService->updater->id,
                'name' => $termsOfService->updater->first_name . ' ' . $termsOfService->updater->last_name,
            ] : null,
            'created_at' => $termsOfService->created_at,
            'updated_at' => $termsOfService->updated_at,
        ]);
    }

    /**
     * Create terms of service
     */
    public function storeTermsOfService(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', new \App\Rules\TranslationRule(true)],
            'is_active' => ['nullable', 'boolean'],
            'version' => ['nullable', 'integer', 'min:1'],
            'effective_date' => ['nullable', 'date'],
        ]);

        // Normalize content translation
        $validated['content'] = TranslationHelper::normalizeTranslation($validated['content']);
        $validated['created_by'] = $request->user()->id;
        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['version'] = $validated['version'] ?? 1;

        // If this is a new active terms, deactivate old ones
        if ($validated['is_active']) {
            TermsOfService::where('is_active', true)->update(['is_active' => false]);
        }

        $terms = TermsOfService::create($validated);

        return response()->json([
            'message' => 'Terms of service created successfully',
            'data' => $terms->load(['creator', 'updater'])
        ], 201);
    }

    /**
     * Update terms of service
     */
    public function updateTermsOfService(Request $request, TermsOfService $termsOfService): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['sometimes', new \App\Rules\TranslationRule(true)],
            'is_active' => ['nullable', 'boolean'],
            'version' => ['nullable', 'integer', 'min:1'],
            'effective_date' => ['nullable', 'date'],
        ]);

        // Normalize content translation if provided
        if (isset($validated['content'])) {
            $validated['content'] = TranslationHelper::normalizeTranslation($validated['content']);
        }

        $validated['updated_by'] = $request->user()->id;

        // If activating this terms, deactivate others
        if (isset($validated['is_active']) && $validated['is_active']) {
            TermsOfService::where('is_active', true)
                ->where('id', '!=', $termsOfService->id)
                ->update(['is_active' => false]);
        }

        $termsOfService->update($validated);

        return response()->json([
            'message' => 'Terms of service updated successfully',
            'data' => $termsOfService->load(['creator', 'updater'])
        ]);
    }

    /**
     * Delete terms of service
     */
    public function destroyTermsOfService(TermsOfService $termsOfService): JsonResponse
    {
        $termsOfService->delete();

        return response()->json([
            'message' => 'Terms of service deleted successfully'
        ]);
    }
}
