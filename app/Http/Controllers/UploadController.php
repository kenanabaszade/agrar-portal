<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UploadController extends Controller
{
    /**
     * POST /api/v1/upload
     * Upload image or file
     */
    public function upload(Request $request): JsonResponse
    {
        // Try to increase PHP upload limits if possible
        @ini_set('upload_max_filesize', '10M');
        @ini_set('post_max_size', '20M');
        @ini_set('memory_limit', '256M');

        try {
            // Debug: Check request content
            $allFiles = $request->allFiles();
            $debugInfo = [
                'has_file' => $request->hasFile('file'),
                'all_files' => array_keys($allFiles),
                'content_type' => $request->header('Content-Type'),
                'method' => $request->method(),
                'has_file_input' => $request->has('file'),
                'all_input_keys' => array_keys($request->all()),
            ];

            // Check if file exists - try different field names
            $fileFieldName = null;
            $file = null;

            // Try 'file' first (default)
            if ($request->hasFile('file')) {
                $fileFieldName = 'file';
                $file = $request->file('file');
            } 
            // Try other common field names
            elseif ($request->hasFile('image')) {
                $fileFieldName = 'image';
                $file = $request->file('image');
            }
            elseif ($request->hasFile('upload')) {
                $fileFieldName = 'upload';
                $file = $request->file('upload');
            }
            // Try to get first file from allFiles (if hasFile() fails but file exists in request)
            elseif (!empty($allFiles)) {
                $fileFieldName = array_key_first($allFiles);
                $file = $allFiles[$fileFieldName];
                
                // If file is in allFiles but hasFile() returns false, it might be an upload error
                if (!$file->isValid()) {
                    $errorMessage = $file->getErrorMessage();
                    return response()->json([
                        'success' => false,
                        'message' => 'Fayl yüklənmə xətası',
                        'errors' => [
                            'file' => ['Fayl yüklənmədi: ' . $errorMessage . '. Ola bilsin fayl çox böyükdür və ya PHP upload limitləri aşılıb.']
                        ],
                        'debug' => config('app.debug') ? array_merge($debugInfo, [
                            'php_upload_max_filesize' => ini_get('upload_max_filesize'),
                            'php_post_max_size' => ini_get('post_max_size'),
                            'php_max_file_uploads' => ini_get('max_file_uploads'),
                        ]) : null
                    ], 422);
                }
            }

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation xətası',
                    'errors' => [
                        'file' => ['Fayl tapılmadı. Zəhmət olmasa fayl seçin. Gözlənilən field: file, image və ya upload']
                    ],
                    'debug' => config('app.debug') ? array_merge($debugInfo, [
                        'php_upload_max_filesize' => ini_get('upload_max_filesize'),
                        'php_post_max_size' => ini_get('post_max_size'),
                    ]) : null
                ], 422);
            }

            // Check if file upload was successful
            if (!$file->isValid()) {
                $errorMessage = $file->getErrorMessage();
                return response()->json([
                    'success' => false,
                    'message' => 'Validation xətası',
                    'errors' => [
                        'file' => ['Fayl yüklənmədi: ' . $errorMessage]
                    ]
                ], 422);
            }

            // Now validate other fields
            $validated = $request->validate([
                'folder' => ['nullable', 'string', 'max:255'], // about, team, etc.
                'type' => ['nullable', 'string', 'in:image,video,document'], // file type
            ], [
                'folder.string' => 'Qovluq adı mətn olmalıdır',
                'type.in' => 'Fayl tipi düzgün deyil',
            ]);

            // Validate file size manually (5MB = 5120 KB)
            $maxSizeKB = 5120; // 5MB
            if ($file->getSize() > $maxSizeKB * 1024) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation xətası',
                    'errors' => [
                        'file' => ['Fayl ölçüsü çox böyükdür. Maksimum: ' . $maxSizeKB . 'KB (5MB)']
                    ]
                ], 422);
            }
            $folder = $validated['folder'] ?? 'uploads';
            $type = $validated['type'] ?? 'image';

            // File type validation based on type parameter
            $allowedMimes = [];
            $maxSize = 5120; // 5MB default (for images)

            switch ($type) {
                case 'image':
                    $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    $maxSize = 5120; // 5MB
                    break;
                case 'video':
                    $allowedMimes = ['video/mp4', 'video/avi', 'video/mov', 'video/wmv', 'video/flv', 'video/webm'];
                    $maxSize = 102400; // 100MB
                    break;
                case 'document':
                    $allowedMimes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
                    $maxSize = 20480; // 20MB
                    break;
            }

            // Validate file type
            if (!empty($allowedMimes) && !in_array($file->getMimeType(), $allowedMimes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation xətası',
                    'errors' => [
                        'file' => ['Fayl tipi dəstəklənmir. Gözlənilən: ' . $type]
                    ]
                ], 422);
            }

            // Validate file size
            if ($file->getSize() > $maxSize * 1024) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation xətası',
                    'errors' => [
                        'file' => ['Fayl ölçüsü çox böyükdür. Maksimum: ' . $maxSize . 'KB']
                    ]
                ], 422);
            }

            // Generate unique filename
            $extension = $file->getClientOriginalExtension();
            $filename = $folder . '_' . time() . '_' . uniqid() . '.' . $extension;
            
            // Store file
            $path = $file->storeAs($folder, $filename, 'public');

            // Generate full URL
            $url = Storage::url($path);

            return response()->json([
                'success' => true,
                'message' => 'Fayl uğurla yükləndi',
                'data' => [
                    'url' => $url,
                    'path' => $path,
                    'filename' => $filename,
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'type' => $type,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server xətası',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
