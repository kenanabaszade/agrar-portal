<?php

namespace App\Http\Controllers;

use App\Models\EducationalContent;
use App\Models\EducationalContentLike;
use App\Models\SavedEducationalContent;
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
            // First check if uploaded image exists
            $imageUrl = $item->image_path ? url(Storage::url($item->image_path)) : null;
            
            // If no uploaded image, fallback to og_image from SEO
            if (!$imageUrl && isset($item->seo['og_image'])) {
                $imageUrl = $item->seo['og_image'];
            }
            
            return [
                'id' => $item->id,
                'title' => $item->title,
                'short_description' => $item->short_description,
                'desc' => $item->short_description ?? ($item->seo['meta_desc'] ?? null),
                'date' => $item->created_at,
                'created_by' => $item->creator ? ($item->creator->first_name . ' ' . $item->creator->last_name) : null,
                'category' => $item->category,
                'type' => $item->type,
                'image_url' => $imageUrl,
                'stats' => [
                    'views' => $education->views_count ?? 0,
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
            ->with('creator:id,first_name,last_name')
            ->latest();

        $paginated = $query->paginate(20);

        $data = $paginated->getCollection()->map(function (EducationalContent $item) {
            $imageUrl = $item->image_path ? url(Storage::url($item->image_path)) : null;
            
            // If no uploaded image, fallback to og_image from SEO
            if (!$imageUrl && isset($item->seo['og_image'])) {
                $imageUrl = $item->seo['og_image'];
            }
            
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
                'total_views' => $item->views_count ?? 0,
                'date' => $item->created_at,
                'created_by' => $item->creator ? ($item->creator->first_name . ' ' . $item->creator->last_name) : null,
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

        $rules = [
            'type' => ['required', Rule::in(['meqale', 'telimat', 'elan'])],
            'seo' => ['nullable', 'array'],
            // Image validation - always allow nullable, but if file exists, validate it
            'image' => ['nullable', 'file', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'], // 5MB
        ];

        // Meqale
        $rules = array_merge($rules, [
            'title' => ['nullable', 'string', 'max:255'],
            'short_description' => ['nullable', 'string'],
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
            'media_files.*.file' => ['nullable', 'file', 'max:5120'], // 5MB per file

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

        // Try to increase PHP upload limits if possible (for this request)
        // Note: ini_set() may not work if PHP is running in CGI mode
        @ini_set('upload_max_filesize', '10M');
        @ini_set('post_max_size', '20M');
        @ini_set('memory_limit', '256M');

        // Debug: Check if image file is received (before validation)
        $debugInfo = [
            'has_image_file' => $request->hasFile('image'),
            'has_image_in_request' => $request->has('image'),
            'all_files' => array_keys($request->allFiles()),
            'content_type' => $request->header('Content-Type'),
            'php_limits' => [
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'max_file_uploads' => ini_get('max_file_uploads'),
                'memory_limit' => ini_get('memory_limit'),
            ],
            'content_length' => $request->header('Content-Length'),
        ];

        // If image file exists, get debug info before validation
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $debugInfo['image_file_info'] = [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'is_valid' => $file->isValid(),
                'error_code' => $file->getError(),
                'error_message' => $file->getErrorMessage(),
            ];
        }

        try {
            $validated = $request->validate($rules);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Check if error is related to file upload limits
            $errors = $e->errors();
            $hasFileError = false;
            foreach ($errors as $field => $messages) {
                if (in_array($field, ['image', 'media_files.0.file', 'media_files.1.file', 'media_files.2.file'])) {
                    $hasFileError = true;
                    break;
                }
            }
            
            // Add more helpful error message if file upload limit is the issue
            $uploadMaxSize = ini_get('upload_max_filesize');
            $postMaxSize = ini_get('post_max_size');
            $uploadMaxSizeBytes = $this->convertToBytes($uploadMaxSize);
            $postMaxSizeBytes = $this->convertToBytes($postMaxSize);
            $contentLength = (int)($request->header('Content-Length') ?? 0);
            
            if ($hasFileError) {
                $phpLimitErrors = [];
                
                if ($uploadMaxSizeBytes < 5 * 1024 * 1024) {
                    $phpLimitErrors[] = 'upload_max_filesize is too small: ' . $uploadMaxSize . ' (Required: at least 5M)';
                }
                
                if ($postMaxSizeBytes < 10 * 1024 * 1024) {
                    $phpLimitErrors[] = 'post_max_size is too small: ' . $postMaxSize . ' (Required: at least 10M)';
                }
                
                if ($contentLength > $postMaxSizeBytes) {
                    $phpLimitErrors[] = 'Request size (' . round($contentLength / 1024 / 1024, 2) . 'MB) exceeds post_max_size (' . $postMaxSize . ')';
                }
                
                if (!empty($phpLimitErrors)) {
                    $phpLimitErrors[] = 'php.ini location: ' . php_ini_loaded_file();
                    $phpLimitErrors[] = 'After changing php.ini, restart your PHP server (Apache/XAMPP/Laravel server)';
                    $errors['_php_limit'] = $phpLimitErrors;
                }
            }
            
            // Add debug info to validation errors
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $errors,
                'debug' => $debugInfo,
            ], 422);
        }

        return DB::transaction(function () use ($validated, $request, $debugInfo) {
            $data = $validated;
            $data['created_by'] = $request->user()->id;

            if ($request->hasFile('image')) {
                try {
                    $file = $request->file('image');
                    
                    // Debug information
                    $fileDebug = [
                        'file_name' => $file->getClientOriginalName(),
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'is_valid' => $file->isValid(),
                        'error_code' => $file->getError(),
                        'error_message' => $file->getErrorMessage(),
                    ];
                    
                    if (!$file->isValid()) {
                        return response()->json([
                            'message' => 'Invalid image file',
                            'errors' => ['image' => ['The image failed to upload.']],
                            'debug' => array_merge($debugInfo, [
                                'file_debug' => $fileDebug,
                                'error_details' => [
                                    'error_code' => $file->getError(),
                                    'error_message' => $file->getErrorMessage(),
                                    'php_upload_errors' => [
                                        UPLOAD_ERR_OK => 'UPLOAD_ERR_OK - No error',
                                        UPLOAD_ERR_INI_SIZE => 'UPLOAD_ERR_INI_SIZE - File exceeds upload_max_filesize',
                                        UPLOAD_ERR_FORM_SIZE => 'UPLOAD_ERR_FORM_SIZE - File exceeds MAX_FILE_SIZE',
                                        UPLOAD_ERR_PARTIAL => 'UPLOAD_ERR_PARTIAL - File partially uploaded',
                                        UPLOAD_ERR_NO_FILE => 'UPLOAD_ERR_NO_FILE - No file uploaded',
                                        UPLOAD_ERR_NO_TMP_DIR => 'UPLOAD_ERR_NO_TMP_DIR - Missing temporary folder',
                                        UPLOAD_ERR_CANT_WRITE => 'UPLOAD_ERR_CANT_WRITE - Failed to write file',
                                        UPLOAD_ERR_EXTENSION => 'UPLOAD_ERR_EXTENSION - PHP extension stopped upload',
                                    ],
                                ],
                            ]),
                        ], 422);
                    }
                    
                    // Check file size (additional validation)
                    $maxSize = 5 * 1024 * 1024; // 5MB
                    if ($file->getSize() > $maxSize) {
                        return response()->json([
                            'message' => 'Image file too large',
                            'errors' => ['image' => ['Image file exceeds maximum size of 5MB.']],
                            'debug' => array_merge($debugInfo, ['file_debug' => $fileDebug]),
                        ], 422);
                    }
                    
                    // Check mime type
                    $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    if (!in_array($file->getMimeType(), $allowedMimes)) {
                        return response()->json([
                            'message' => 'Invalid image type',
                            'errors' => ['image' => ['Image must be jpeg, jpg, png, gif, or webp.']],
                            'debug' => array_merge($debugInfo, [
                                'file_debug' => $fileDebug,
                                'allowed_mimes' => $allowedMimes,
                            ]),
                        ], 422);
                    }
                    
                    $data['image_path'] = $file->store('education', 'public');
                } catch (\Exception $e) {
                    return response()->json([
                        'message' => 'Image upload failed: ' . $e->getMessage(),
                        'errors' => ['image' => ['The image failed to upload.']],
                        'debug' => array_merge($debugInfo, [
                            'exception' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]),
                    ], 422);
                }
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
                        $stored = $file->store('education/media', 'public');
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
                'debug' => array_merge($debugInfo, [
                    'image_path' => $content->image_path,
                ]),
            ], 201);
        });
    }

    public function show($id, Request $request): JsonResponse
    {
        $education = EducationalContent::with('creator:id,first_name,last_name')->findOrFail($id);
        
        // Increment views count
        $education->increment('views_count');
        $education->refresh(); // Refresh to get updated views_count
        
        // Format image URL (same as articles method)
        // First check if uploaded image exists
        $imageUrl = null;
        if ($education->image_path) {
            $imageUrl = url(Storage::url($education->image_path));
        }
        
        // If no uploaded image, fallback to og_image from SEO
        if (!$imageUrl && isset($education->seo['og_image'])) {
            $imageUrl = $education->seo['og_image'];
        }
        
        // Build the response in the same format as articles method
        $response = [
            'id' => $education->id,
            'title' => $education->title,
            'short_description' => $education->short_description,
            'desc' => $education->short_description ?? ($education->seo['meta_desc'] ?? null),
            'date' => $education->created_at,
            'created_by' => $education->creator ? ($education->creator->first_name . ' ' . $education->creator->last_name) : null,
            'category' => $education->category,
            'type' => $education->type,
            'image_url' => $imageUrl,
            'image_path' => $education->image_path, // Debug: show actual image_path value
                'stats' => [
                    'views' => $item->views_count ?? 0,
                ],
            'status' => 'published',
            
            // Additional detail fields
            'body_html' => $education->body_html,
            'description' => $education->description,
            'sequence' => $education->sequence,
            'hashtags' => $education->hashtags,
            'send_to_our_user' => $education->send_to_our_user,
            'seo' => $education->seo,
        ];
        
        // Format media files URLs
        if (is_array($education->media_files)) {
            $response['media_files'] = collect($education->media_files)->map(function ($m) {
                if (isset($m['path'])) {
                    $m['url'] = url(Storage::url($m['path']));
                }
                return $m;
            })->all();
        } else {
            $response['media_files'] = [];
        }
        
        // Format documents URLs
        if (is_array($education->documents)) {
            $response['documents'] = collect($education->documents)->map(function ($doc) {
                if (isset($doc['path'])) {
                    $doc['url'] = url(Storage::url($doc['path']));
                }
                return $doc;
            })->all();
        } else {
            $response['documents'] = [];
        }
        
        // Add like and saved status if user is authenticated
        if ($request->user()) {
            $response['is_liked'] = $education->isLikedBy($request->user()->id);
            $response['is_saved'] = $education->isSavedBy($request->user()->id);
        } else {
            $response['is_liked'] = false;
            $response['is_saved'] = false;
        }
        
        return response()->json($response);
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
            'image' => ['nullable', 'file', 'image', 'max:5120'], // 5MB

            'title' => ['nullable', 'string', 'max:255'],
            'short_description' => ['nullable', 'string'],
            'body_html' => ['nullable', 'string'],
            'sequence' => ['nullable', 'integer', 'min:1'],
            'hashtags' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'send_to_our_user' => ['nullable', 'boolean'],
            'media_files' => ['nullable', 'array'],
            'media_files.*.name' => ['nullable', 'string', 'max:255'],
            'media_files.*.type' => ['nullable', 'string', 'max:100'],
            'media_files.*.path' => ['nullable', 'string', 'max:2048'],
            'media_files.*.file' => ['nullable', 'file', 'max:5120'], // 5MB per file

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
                $data['image_path'] = $request->file('image')->store('education', 'public');
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
                        $stored = $file->store('education/media', 'public');
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

    // Like/Unlike endpoints
    public function like($id, Request $request): JsonResponse
    {
        $content = EducationalContent::findOrFail($id);
        $user = $request->user();

        $existingLike = EducationalContentLike::where('user_id', $user->id)
            ->where('educational_content_id', $content->id)
            ->first();

        if ($existingLike) {
            return response()->json([
                'message' => 'Content already liked',
                'is_liked' => true,
            ], 400);
        }

        EducationalContentLike::create([
            'user_id' => $user->id,
            'educational_content_id' => $content->id,
        ]);

        $content->increment('likes_count');

        return response()->json([
            'message' => 'Content liked successfully',
            'is_liked' => true,
            'likes_count' => $content->fresh()->likes_count,
        ]);
    }

    public function unlike($id, Request $request): JsonResponse
    {
        $content = EducationalContent::findOrFail($id);
        $user = $request->user();

        $like = EducationalContentLike::where('user_id', $user->id)
            ->where('educational_content_id', $content->id)
            ->first();

        if (!$like) {
            return response()->json([
                'message' => 'Content not liked',
                'is_liked' => false,
            ], 400);
        }

        $like->delete();
        $content->decrement('likes_count');

        return response()->json([
            'message' => 'Content unliked successfully',
            'is_liked' => false,
            'likes_count' => $content->fresh()->likes_count,
        ]);
    }

    // Save/Unsave endpoints
    public function save($id, Request $request): JsonResponse
    {
        $content = EducationalContent::findOrFail($id);
        $user = $request->user();

        $existingSave = SavedEducationalContent::where('user_id', $user->id)
            ->where('educational_content_id', $content->id)
            ->first();

        if ($existingSave) {
            return response()->json([
                'message' => 'Content already saved',
                'is_saved' => true,
            ], 400);
        }

        SavedEducationalContent::create([
            'user_id' => $user->id,
            'educational_content_id' => $content->id,
        ]);

        return response()->json([
            'message' => 'Content saved successfully',
            'is_saved' => true,
        ]);
    }

    public function unsave($id, Request $request): JsonResponse
    {
        $content = EducationalContent::findOrFail($id);
        $user = $request->user();

        $saved = SavedEducationalContent::where('user_id', $user->id)
            ->where('educational_content_id', $content->id)
            ->first();

        if (!$saved) {
            return response()->json([
                'message' => 'Content not saved',
                'is_saved' => false,
            ], 400);
        }

        $saved->delete();

        return response()->json([
            'message' => 'Content unsaved successfully',
            'is_saved' => false,
        ]);
    }

    // Get user's saved contents
    public function mySaved(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $savedContents = SavedEducationalContent::where('user_id', $user->id)
            ->with(['educationalContent.creator'])
            ->latest()
            ->paginate(20);

        $data = $savedContents->getCollection()->map(function ($saved) {
            $content = $saved->educationalContent;
            $content->image_url = $content->image_path ? url(Storage::url($content->image_path)) : 
                ($content->seo['og_image'] ?? null);
            return $content;
        });

        $savedContents->setCollection($data);

        return response()->json($savedContents);
    }

    // Helper methods
    private function convertToBytes(string $size): int
    {
        $size = trim($size);
        $last = strtolower($size[strlen($size) - 1]);
        $value = (int) $size;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
                // no break
            case 'm':
                $value *= 1024;
                // no break
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
    
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
