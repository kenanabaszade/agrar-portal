<?php

namespace App\Http\Controllers;

use App\Models\EducationalContent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EducationalContentController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $now = now();
        $periodDays = (int) ($request->query('days', 30));
        $prevStart = $now->copy()->subDays($periodDays * 2);
        $prevEnd = $now->copy()->subDays($periodDays);
        $currStart = $prevEnd;

        $totalArticles = EducationalContent::where('type', 'meqale')->count();
        $totalTelimats = EducationalContent::where('type', 'telimat')->count();

        // Approx video count by checking media_files json contains "video"
        $totalVideos = \DB::table('educational_contents')->whereNotNull('media_files')->where('media_files', 'like', '%"video"%')->count();

        $currArticles = EducationalContent::where('type', 'meqale')->where('created_at', '>=', $currStart)->count();
        $prevArticles = EducationalContent::where('type', 'meqale')->whereBetween('created_at', [$prevStart, $prevEnd])->count();

        $growthArticles = $prevArticles > 0 ? round((($currArticles - $prevArticles) / max(1, $prevArticles)) * 100) : ($currArticles > 0 ? 100 : 0);

        $currVideos = \DB::table('educational_contents')->whereNotNull('media_files')->where('media_files', 'like', '%"video"%')->where('created_at', '>=', $currStart)->count();
        $prevVideos = \DB::table('educational_contents')->whereNotNull('media_files')->where('media_files', 'like', '%"video"%')->whereBetween('created_at', [$prevStart, $prevEnd])->count();
        $growthVideos = $prevVideos > 0 ? round((($currVideos - $prevVideos) / max(1, $prevVideos)) * 100) : ($currVideos > 0 ? 100 : 0);

        return response()->json([
            'articles' => [
                'total' => $totalArticles,
                'growth_percent' => $growthArticles,
            ],
            'videos' => [
                'total' => $totalVideos,
                'growth_percent' => $growthVideos,
            ],
            'telimats' => [
                'total' => $totalTelimats,
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = EducationalContent::query()->with('creator:id,first_name,last_name');

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        $contents = $query->latest()->paginate(20);

        $contents->getCollection()->transform(function ($item) {
            if ($item->image_path) {
                $item->image_url = url(Storage::url($item->image_path));
            }
            return $item;
        });

        return response()->json($contents);
    }

    public function articles(Request $request): JsonResponse
    {
        $query = EducationalContent::query()
            ->where('type', 'meqale')
            ->with('creator:id,first_name,last_name')
            ->latest();

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        $paginated = $query->paginate(20);

        $data = $paginated->getCollection()->map(function (EducationalContent $item) {
            $imageUrl = $item->image_path ? url(Storage::url($item->image_path)) : null;
            return [
                'id' => $item->id,
                'title' => $item->title,
                'desc' => $item->seo['meta_desc'] ?? null,
                'date' => $item->created_at,
                'created_by' => $item->creator ? ($item->creator->first_name . ' ' . $item->creator->last_name) : null,
                'category' => $item->category,
                'type' => $item->type,
                'image_url' => $imageUrl,
                'stats' => [
                    'views' => 0,
                ],
                'status' => 'published',
            ];
        });

        $paginated->setCollection($data);
        return response()->json($paginated);
    }

    public function telimats(Request $request): JsonResponse
    {
        $query = EducationalContent::query()
            ->where('type', 'telimat')
            ->latest();

        $paginated = $query->paginate(20);

        $data = $paginated->getCollection()->map(function (EducationalContent $item) {
            $imageUrl = $item->image_path ? url(Storage::url($item->image_path)) : null;
            $totalSize = 0;
            if (is_array($item->documents)) {
                foreach ($item->documents as $doc) {
                    if (!empty($doc['path'])) {
                        try {
                            $totalSize += Storage::size($doc['path']);
                        } catch (\Throwable $e) {
                        }
                    }
                }
            }
            if (is_array($item->media_files)) {
                foreach ($item->media_files as $m) {
                    if (!empty($m['path'])) {
                        try {
                            $totalSize += Storage::size($m['path']);
                        } catch (\Throwable $e) {
                        }
                    }
                }
            }

            return [
                'id' => $item->id,
                'title' => $item->title ?? ($item->seo['meta_title'] ?? 'Telimat'),
                'image_url' => $imageUrl,
                'size' => $totalSize, // bytes
                'total_views' => 0,
                'date' => $item->created_at,
            ];
        });

        $paginated->setCollection($data);
        return response()->json($paginated);
    }

    public function store(Request $request): JsonResponse
    {
        // Normalize inputs
        if ($request->has('type')) {
            $request->merge(['type' => strtolower((string) $request->input('type'))]);
        }
        if ($request->has('send_to_our_user')) {
            $request->merge([
                'send_to_our_user' => filter_var($request->input('send_to_our_user'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }

        $validated = $request->validate([
            'type' => ['required', Rule::in(['meqale', 'telimat', 'elan'])],
            'seo' => ['nullable', 'array'],
            'image' => ['nullable', 'file', 'image', 'max:5120'],

            // Meqale
            'title' => ['nullable', 'string', 'max:255'],
            'body_html' => ['nullable', 'string'],
            'sequence' => ['nullable', 'integer', 'min:1'],
            'hashtags' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'send_to_our_user' => ['nullable', 'boolean'],

            // Accept media files either by upload or by pre-existing path
            'media_files' => ['nullable', 'array'],
            'media_files.*.name' => ['nullable', 'string', 'max:255'],
            'media_files.*.type' => ['nullable', 'string', 'max:100'],
            'media_files.*.path' => ['nullable', 'string', 'max:2048'],
            'media_files.*.file' => ['nullable', 'file', 'max:51200'], // up to ~50MB per item

            // Telimat
            'description' => ['nullable', 'string'],
            'documents' => ['nullable', 'array'],
            'documents.*.name' => ['required_with:documents', 'string', 'max:255'],
            'documents.*.path' => ['required_with:documents', 'string', 'max:2048'],
            'documents.*.type' => ['required_with:documents', 'string', 'max:100'],

            // Elan
            'announcement_title' => ['nullable', 'string', 'max:255'],
            'announcement_body' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $data = $validated;
            $data['created_by'] = $request->user()->id;

            if ($request->hasFile('image')) {
                $data['image_path'] = $request->file('image')->store('public/education');
            }

            // Process media uploads
            $finalMedia = [];
            if (!empty($validated['media_files']) && is_array($validated['media_files'])) {
                foreach ($validated['media_files'] as $index => $item) {
                    $name = $item['name'] ?? null;
                    $type = $item['type'] ?? null;
                    $path = $item['path'] ?? null;
                    $file = $request->file("media_files.$index.file");

                    if ($file) {
                        $stored = $file->store('public/education/media');
                        $path = $stored;
                        // Infer type from mime
                        $mime = $file->getMimeType();
                        if (!$type) {
                            $type = $this->inferMediaTypeFromMime($mime);
                        }
                        if (!$name) {
                            $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        }
                    }

                    if ($path) {
                        $finalMedia[] = [
                            'name' => $name ?: basename((string) $path),
                            'path' => $path,
                            'type' => $type ?: 'file',
                        ];
                    }
                }
            }

            if (!empty($finalMedia)) {
                $data['media_files'] = $finalMedia;
            }

            $content = EducationalContent::create($data);

            // Decorate URLs
            if ($content->image_path) {
                $content->image_url = url(Storage::url($content->image_path));
            }
            if (is_array($content->media_files)) {
                $content->media_files = collect($content->media_files)->map(function ($m) {
                    $m['url'] = url(Storage::url($m['path']));
                    return $m;
                })->all();
            }

            return response()->json([
                'message' => 'Educational content created',
                'content' => $content,
            ], 201);
        });
    }

    public function show(EducationalContent $education): JsonResponse
    {
        $education->load('creator:id,first_name,last_name');
        if ($education->image_path) {
            $education->image_url = url(Storage::url($education->image_path));
        }
        return response()->json($education);
    }

    public function update(Request $request, EducationalContent $education): JsonResponse
    {
        // Normalize inputs
        if ($request->has('type')) {
            $request->merge(['type' => strtolower((string) $request->input('type'))]);
        }
        if ($request->has('send_to_our_user')) {
            $request->merge([
                'send_to_our_user' => filter_var($request->input('send_to_our_user'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }

        $validated = $request->validate([
            'type' => ['sometimes', Rule::in(['meqale', 'telimat', 'elan'])],
            'seo' => ['nullable', 'array'],
            'image' => ['nullable', 'file', 'image', 'max:5120'],

            'title' => ['nullable', 'string', 'max:255'],
            'body_html' => ['nullable', 'string'],
            'sequence' => ['nullable', 'integer', 'min:1'],
            'hashtags' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'send_to_our_user' => ['nullable', 'boolean'],
            'media_files' => ['nullable', 'array'],
            'media_files.*.name' => ['nullable', 'string', 'max:255'],
            'media_files.*.type' => ['nullable', 'string', 'max:100'],
            'media_files.*.path' => ['nullable', 'string', 'max:2048'],
            'media_files.*.file' => ['nullable', 'file', 'max:51200'],

            'description' => ['nullable', 'string'],
            'documents' => ['nullable', 'array'],
            'documents.*.name' => ['required_with:documents', 'string', 'max:255'],
            'documents.*.path' => ['required_with:documents', 'string', 'max:2048'],
            'documents.*.type' => ['required_with:documents', 'string', 'max:100'],

            'announcement_title' => ['nullable', 'string', 'max:255'],
            'announcement_body' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($validated, $request, $education) {
            $data = $validated;

            if ($request->hasFile('image')) {
                if ($education->image_path) {
                    Storage::delete($education->image_path);
                }
                $data['image_path'] = $request->file('image')->store('public/education');
            }

            // Process media uploads and merge with existing
            $finalMedia = [];
            if (!empty($validated['media_files']) && is_array($validated['media_files'])) {
                foreach ($validated['media_files'] as $index => $item) {
                    $name = $item['name'] ?? null;
                    $type = $item['type'] ?? null;
                    $path = $item['path'] ?? null;
                    $file = $request->file("media_files.$index.file");

                    if ($file) {
                        $stored = $file->store('public/education/media');
                        $path = $stored;
                        $mime = $file->getMimeType();
                        if (!$type) {
                            $type = $this->inferMediaTypeFromMime($mime);
                        }
                        if (!$name) {
                            $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        }
                    }

                    if ($path) {
                        $finalMedia[] = [
                            'name' => $name ?: basename((string) $path),
                            'path' => $path,
                            'type' => $type ?: 'file',
                        ];
                    }
                }
                $data['media_files'] = $finalMedia;
            }

            $education->update($data);

            if ($education->image_path) {
                $education->image_url = url(Storage::url($education->image_path));
            }
            if (is_array($education->media_files)) {
                $education->media_files = collect($education->media_files)->map(function ($m) {
                    $m['url'] = url(Storage::url($m['path']));
                    return $m;
                })->all();
            }

            return response()->json([
                'message' => 'Educational content updated',
                'content' => $education,
            ]);
        });
    }

    public function destroy(EducationalContent $education): JsonResponse
    {
        return DB::transaction(function () use ($education) {
            if ($education->image_path) {
                Storage::delete($education->image_path);
            }
            $education->delete();
            return response()->json(['message' => 'Educational content deleted']);
        });
    }

    // Helper methods
    private function inferMediaTypeFromMime(?string $mime): string
    {
        if (!$mime) {
            return 'file';
        }
        if (str_starts_with($mime, 'image/')) return 'image';
        if (str_starts_with($mime, 'video/')) return 'video';
        if (str_starts_with($mime, 'audio/')) return 'audio';
        if ($mime === 'application/pdf') return 'pdf';
        if (in_array($mime, [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.oasis.opendocument.spreadsheet',
        ])) return 'excel';
        return 'file';
    }
}
