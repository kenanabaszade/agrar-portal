<?php

namespace App\Http\Controllers;

use App\Models\AboutBlock;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AboutPageController extends Controller
{
    /**
     * GET /api/v1/about
     * Public endpoint - blokları gətir
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Cache 1 saat
            $blocks = cache()->remember('about_blocks', 3600, function () {
                $dbBlocks = AboutBlock::ordered()->get();
                
                if ($dbBlocks->isEmpty()) {
                    return [];
                }
                
                return $dbBlocks->map(function ($block) {
                    $data = $block->data ?? [];
                    
                    // Convert image paths to full URLs
                    $data = $this->convertImagePathsToUrls($data, $block->type);
                    
                    return [
                        'id' => (string) $block->id,
                        'type' => $block->type,
                        'order' => $block->order,
                        'data' => $data,
                        'styles' => $block->styles ?? [],
                    ];
                })->toArray();
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'blocks' => $blocks
                ]
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('About blocks fetch error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Server xətası',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * POST /api/v1/about/blocks
     * Admin endpoint - blokları yadda saxla
     */
    public function store(Request $request): JsonResponse
    {
        // Authentication kontrolü
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Admin kontrolü
        if (!auth()->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Bu əməliyyat üçün yetkiniz yoxdur'
            ], 403);
        }

        // Validation rules
        $validator = Validator::make($request->all(), [
            'blocks' => 'required|array|min:1',
            'blocks.*.id' => 'nullable|string', // Optional - yeni bloklar üçün id olmaya bilər
            'blocks.*.type' => 'required|string|in:hero,cards,stats,timeline,team,values,contact',
            'blocks.*.order' => 'required|integer|min:0',
            'blocks.*.data' => 'required|array',
            'blocks.*.styles' => 'nullable|array',
            
            // Hero validation
            'blocks.*.data.title' => 'nullable|string',
            'blocks.*.data.description' => 'nullable|string',
            'blocks.*.data.image' => 'nullable|string',
            'blocks.*.data.icon' => 'nullable|string',
            'blocks.*.data.iconColor' => 'nullable|string',
            
            // Stats validation
            'blocks.*.data.stats' => 'required_if:blocks.*.type,stats|array',
            'blocks.*.data.stats.*.value' => 'required_with:blocks.*.data.stats|string',
            'blocks.*.data.stats.*.label' => 'required_with:blocks.*.data.stats|string',
            'blocks.*.data.stats.*.icon' => 'nullable|string',
            'blocks.*.data.stats.*.iconColor' => 'nullable|string',
            
            // Timeline validation
            'blocks.*.data.timeline' => 'required_if:blocks.*.type,timeline|array',
            'blocks.*.data.timeline.*.year' => 'required_with:blocks.*.data.timeline|string',
            'blocks.*.data.timeline.*.title' => 'required_with:blocks.*.data.timeline|string',
            'blocks.*.data.timeline.*.description' => 'required_with:blocks.*.data.timeline|string',
            'blocks.*.data.timeline.*.icon' => 'nullable|string',
            'blocks.*.data.timeline.*.iconColor' => 'nullable|string',
            
            // Team validation
            'blocks.*.data.title' => 'nullable|string',
            'blocks.*.data.members' => 'required_if:blocks.*.type,team|array',
            'blocks.*.data.members.*.name' => 'required_with:blocks.*.data.members|string',
            'blocks.*.data.members.*.position' => 'required_with:blocks.*.data.members|string',
            'blocks.*.data.members.*.category' => 'required_with:blocks.*.data.members|string',
            'blocks.*.data.members.*.experience' => 'required_with:blocks.*.data.members|string',
            'blocks.*.data.members.*.image' => 'nullable|string',
            'blocks.*.data.members.*.specializations' => 'nullable|array',
            
            // Values validation
            'blocks.*.data.values' => 'required_if:blocks.*.type,values|array',
            'blocks.*.data.values.*.title' => 'required_with:blocks.*.data.values|string',
            'blocks.*.data.values.*.description' => 'required_with:blocks.*.data.values|string',
            'blocks.*.data.values.*.icon' => 'nullable|string',
            'blocks.*.data.values.*.iconColor' => 'nullable|string',
            
            // Contact validation
            'blocks.*.data.buttons' => 'nullable|array',
            'blocks.*.data.buttons.*.text' => 'required_with:blocks.*.data.buttons|string',
            'blocks.*.data.buttons.*.link' => 'required_with:blocks.*.data.buttons|string',
            'blocks.*.data.buttons.*.icon' => 'nullable|string',
            'blocks.*.data.buttons.*.iconColor' => 'nullable|string',
            'blocks.*.data.buttons.*.type' => 'nullable|in:primary,secondary',
            
        ], [
            'blocks.required' => 'Bloklar massivi tələb olunur',
            'blocks.min' => 'Bloklar massivi boş ola bilməz',
            'blocks.*.type.in' => 'Dəstəklənməyən blok tipi',
            'blocks.*.type.required' => 'Blok tipi məcburidir',
            'blocks.*.order.required' => 'Blok sırası məcburidir',
            'blocks.*.data.required' => 'Blok məlumatları məcburidir',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation xətası',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Köhnə blokları sil (replace strategy)
            AboutBlock::truncate();

            // Yeni blokları yadda saxla
            foreach ($request->blocks as $blockData) {
                AboutBlock::create([
                    'type' => $blockData['type'],
                    'order' => $blockData['order'],
                    'data' => $blockData['data'],
                    'styles' => $blockData['styles'] ?? [],
                ]);
            }

            DB::commit();

            // Cache-i təmizlə
            cache()->forget('about_blocks');

            // Blokları geri qaytar
            $blocks = AboutBlock::ordered()
                ->get()
                ->map(function ($block) {
                    $data = $block->data ?? [];
                    
                    // Convert image paths to full URLs
                    $data = $this->convertImagePathsToUrls($data, $block->type);
                    
                    return [
                        'id' => (string) $block->id,
                        'type' => $block->type,
                        'order' => $block->order,
                        'data' => $data,
                        'styles' => $block->styles ?? [],
                    ];
                })
                ->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Bloklar uğurla yadda saxlanıldı',
                'data' => [
                    'blocks' => $blocks
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('About blocks save error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Server xətası',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Convert image paths to full URLs in block data
     */
    private function convertImagePathsToUrls(array $data, string $type): array
    {
        // Hero block - data.image
        if ($type === 'hero' && isset($data['image']) && !empty($data['image'])) {
            $data['image'] = $this->getFullUrl($data['image']);
        }

        // Team block - data.members[].image
        if ($type === 'team' && isset($data['members']) && is_array($data['members'])) {
            foreach ($data['members'] as $key => $member) {
                if (isset($member['image']) && !empty($member['image'])) {
                    $data['members'][$key]['image'] = $this->getFullUrl($member['image']);
                }
            }
        }

        return $data;
    }

    /**
     * Convert relative path to full URL
     */
    private function getFullUrl(string $path): string
    {
        // If already full URL, return as is
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        // Remove leading slash if exists
        $path = ltrim($path, '/');

        // If path starts with 'storage/', use Storage::url
        if (str_starts_with($path, 'storage/')) {
            return Storage::url(substr($path, 8)); // Remove 'storage/' prefix
        }

        // If path starts with 'images/', 'about/', etc., add 'storage/' prefix
        return Storage::url($path);
    }
}
