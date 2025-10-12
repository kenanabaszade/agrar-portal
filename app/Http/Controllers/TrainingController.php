<?php
 
namespace App\Http\Controllers;
 
use App\Models\Training;
use App\Models\TrainingRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;

class TrainingController extends Controller
{
    
    public function index(Request $request)
    {
        $query = Training::with(['modules.lessons', 'trainer', 'registrations'])
            ->withCount(['registrations']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->get('category'));
        }

        // Filter by trainer
        if ($request->filled('trainer_id')) {
            $query->where('trainer_id', $request->get('trainer_id'));
        }

        // Filter by difficulty
        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->get('difficulty'));
        }

        // Filter by type (online/offline)
        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        if (in_array($sortBy, ['title', 'created_at', 'start_date', 'end_date', 'difficulty'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = min($request->get('per_page', 15), 100);
        $trainings = $query->paginate($perPage);

        // Add statistics and media counts for each training
        $trainings->getCollection()->transform(function ($training) {
            // Calculate registration statistics
            $totalRegistrations = $training->registrations_count;
            $completedRegistrations = $training->registrations()
                ->whereHas('userTrainingProgress', function ($query) {
                    $query->where('status', 'completed');
                })
                ->count();
            
            $startedRegistrations = $training->registrations()
                ->whereHas('userTrainingProgress', function ($query) {
                    $query->where('status', 'in_progress');
                })
                ->count();

            // Calculate completion percentage
            $completionRate = $totalRegistrations > 0 ? round(($completedRegistrations / $totalRegistrations) * 100, 2) : 0;
            $progressRate = $totalRegistrations > 0 ? round((($completedRegistrations + $startedRegistrations) / $totalRegistrations) * 100, 2) : 0;

            // Count media files by type (training + modules + lessons)
            $trainingMediaFiles = $training->media_files ?? [];
            
            // Get all modules and their lessons
            $modules = $training->modules;
            
            // Initialize counters
            $totalVideos = 0;
            $totalDocuments = 0;
            $totalImages = 0;
            $totalAudio = 0;
            
            // Count training media files
            foreach ($trainingMediaFiles as $file) {
                $mimeType = $file['mime_type'] ?? '';
                if ($file['type'] === 'intro_video' || str_contains($mimeType, 'video')) {
                    $totalVideos++;
                } elseif (str_contains($mimeType, 'pdf') || str_contains($mimeType, 'doc')) {
                    $totalDocuments++;
                } elseif ($file['type'] === 'banner' || str_contains($mimeType, 'image')) {
                    $totalImages++;
                } elseif (str_contains($mimeType, 'audio')) {
                    $totalAudio++;
                }
            }
            
            // Count lesson media files and URLs
            foreach ($modules as $module) {
                $lessons = $module->lessons;
                foreach ($lessons as $lesson) {
                    // Count video_url
                    if (!empty($lesson->video_url)) {
                        $totalVideos++;
                    }
                    
                    // Count pdf_url
                    if (!empty($lesson->pdf_url)) {
                        $totalDocuments++;
                    }
                    
                    // Count lesson media_files
                    $lessonMedia = $lesson->media_files ?? [];
                    foreach ($lessonMedia as $file) {
                        if (isset($file['type'])) {
                            switch ($file['type']) {
                                case 'video':
                                    $totalVideos++;
                                    break;
                                case 'document':
                                    $totalDocuments++;
                                    break;
                                case 'image':
                                    $totalImages++;
                                    break;
                                case 'audio':
                                    $totalAudio++;
                                    break;
                            }
                        }
                    }
                }
            }
            
            $mediaStats = [
                'videos_count' => $totalVideos,
                'documents_count' => $totalDocuments,
                'images_count' => $totalImages,
                'audio_count' => $totalAudio,
                'total_media' => $totalVideos + $totalDocuments + $totalImages + $totalAudio,
                'training_media_count' => count($trainingMediaFiles),
                'modules_count' => $modules->count(),
                'lessons_count' => $modules->sum(function ($module) {
                    return $module->lessons->count();
                })
            ];

            // Add statistics to training object
            $training->statistics = [
                'total_registrations' => $totalRegistrations,
                'started_count' => $startedRegistrations,
                'completed_count' => $completedRegistrations,
                'completion_rate' => $completionRate,
                'progress_rate' => $progressRate
            ];

            $training->media_statistics = $mediaStats;

            return $training;
        });

        return $trainings;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:255'],
            'trainer_id' => ['required', 'exists:users,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_online' => ['nullable', 'boolean'],
            'type' => ['nullable', 'string', 'regex:/^(online|offline|video)$/i'],
            'online_details' => ['nullable', 'array'],
            'online_details.participant_size' => ['nullable', 'string'],
            'online_details.google_meet_link' => ['nullable', 'string'],
            'offline_details' => ['nullable', 'array'],
            'offline_details.participant_size' => ['nullable', 'string'],
            'offline_details.address' => ['nullable', 'string'],
            'offline_details.coordinates' => ['nullable', 'string'],
            'has_certificate' => ['nullable', 'boolean'],
            'difficulty' => ['nullable', 'string', 'in:beginner,intermediate,advanced,expert'],
            'banner_image' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'gif', 'webp'])->max(5 * 1024)], // 5MB max
            'intro_video' => ['nullable', File::types(['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'])->max(20 * 1024)], // 20MB max
            'media_files.*' => ['nullable', 'file', 'max:' . (50 * 1024)], // 50MB max per file
        ]);

        // Set default values
        $validated['is_online'] = $validated['is_online'] ?? true;
        $validated['has_certificate'] = $validated['has_certificate'] ?? false;

        // Remove file inputs from validated data as they're not database fields
        unset($validated['banner_image'], $validated['intro_video'], $validated['media_files']);

        $training = Training::create($validated);
        $mediaFiles = [];

        // Handle banner image upload
        if ($request->hasFile('banner_image')) {
            $bannerPath = $request->file('banner_image')->store('trainings/banners', 'public');
            $mediaFiles[] = [
                'type' => 'banner',
                'path' => $bannerPath,
                'original_name' => $request->file('banner_image')->getClientOriginalName(),
                'mime_type' => $request->file('banner_image')->getMimeType(),
                'size' => $request->file('banner_image')->getSize(),
                'uploaded_at' => now()->toISOString(),
            ];
        }

        // Handle intro video upload
        if ($request->hasFile('intro_video')) {
            $videoPath = $request->file('intro_video')->store('trainings/videos', 'public');
            $mediaFiles[] = [
                'type' => 'intro_video',
                'path' => $videoPath,
                'original_name' => $request->file('intro_video')->getClientOriginalName(),
                'mime_type' => $request->file('intro_video')->getMimeType(),
                'size' => $request->file('intro_video')->getSize(),
                'uploaded_at' => now()->toISOString(),
            ];
        }

        // Handle general media files
        if ($request->hasFile('media_files')) {
            foreach ($request->file('media_files') as $file) {
                $path = $file->store('trainings/media', 'public');
                $mediaFiles[] = [
                    'type' => 'general',
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_at' => now()->toISOString(),
                ];
            }
        }

        // Update training with all media files
        if (!empty($mediaFiles)) {
            $training->update(['media_files' => $mediaFiles]);
        }

        return response()->json($training->load('modules.lessons'), 201);
    }

    public function show(Training $training)
    {
        return $training->load('modules.lessons');
    }

    public function update(Request $request, Training $training)
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:255'],
            'trainer_id' => ['sometimes', 'exists:users,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_online' => ['nullable', 'boolean'],
            'type' => ['nullable', 'string', 'regex:/^(online|offline|video)$/i'],
            'online_details' => ['nullable', 'array'],
            'online_details.participant_size' => ['nullable', 'string'],
            'online_details.google_meet_link' => ['nullable', 'string'],
            'offline_details' => ['nullable', 'array'],
            'offline_details.participant_size' => ['nullable', 'string'],
            'offline_details.address' => ['nullable', 'string'],
            'offline_details.coordinates' => ['nullable', 'string'],
            'has_certificate' => ['nullable', 'boolean'],
            'difficulty' => ['nullable', 'string', 'in:beginner,intermediate,advanced,expert'],
            'banner_image' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'gif', 'webp'])->max(5 * 1024)], // 5MB max
            'intro_video' => ['nullable', File::types(['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'])->max(20 * 1024)], // 20MB max
            'media_files.*' => ['nullable', 'file', 'max:' . (50 * 1024)], // 50MB max per file
            'remove_banner' => ['nullable', 'boolean'],
            'remove_intro_video' => ['nullable', 'boolean'],
            'remove_media_files' => ['nullable', 'array'], // Array of file paths to remove
            'remove_media_files.*' => ['string'],
        ]);

        // Handle banner image replacement/removal
        if ($request->hasFile('banner_image')) {
            // Remove existing banner
            $training->removeMediaFilesByType('banner');
            // Add new banner
            $bannerPath = $request->file('banner_image')->store('trainings/banners', 'public');
            $training->addMediaFile(
                $bannerPath,
                $request->file('banner_image')->getClientOriginalName(),
                $request->file('banner_image')->getMimeType(),
                $request->file('banner_image')->getSize(),
                'banner'
            );
        } elseif ($request->boolean('remove_banner')) {
            $training->removeMediaFilesByType('banner');
        }

        // Handle intro video replacement/removal
        if ($request->hasFile('intro_video')) {
            // Remove existing intro video
            $training->removeMediaFilesByType('intro_video');
            // Add new intro video
            $videoPath = $request->file('intro_video')->store('trainings/videos', 'public');
            $training->addMediaFile(
                $videoPath,
                $request->file('intro_video')->getClientOriginalName(),
                $request->file('intro_video')->getMimeType(),
                $request->file('intro_video')->getSize(),
                'intro_video'
            );
        } elseif ($request->boolean('remove_intro_video')) {
            $training->removeMediaFilesByType('intro_video');
        }

        // Handle specific media files removal
        if ($request->has('remove_media_files')) {
            $filesToRemove = $request->input('remove_media_files', []);
            foreach ($filesToRemove as $fileToRemove) {
                $training->removeMediaFile($fileToRemove);
            }
        }

        // Handle additional media files
        if ($request->hasFile('media_files')) {
            foreach ($request->file('media_files') as $file) {
                $path = $file->store('trainings/media', 'public');
                $training->addMediaFile(
                    $path,
                    $file->getClientOriginalName(),
                    $file->getMimeType(),
                    $file->getSize(),
                    'general'
                );
            }
        }

        // Remove file inputs and control flags from validated data
        unset($validated['banner_image'], $validated['intro_video'], $validated['media_files'], 
              $validated['remove_banner'], $validated['remove_intro_video'], $validated['remove_media_files']);

        $training->update($validated);
        return response()->json($training->fresh()->load('modules.lessons'));
    }

    public function destroy(Training $training)
    {
        // Delete all associated media files
        $mediaFiles = $training->getRawOriginal('media_files') ? json_decode($training->getRawOriginal('media_files'), true) : [];
        foreach ($mediaFiles as $file) {
            if (Storage::disk('public')->exists($file['path'])) {
                Storage::disk('public')->delete($file['path']);
            }
        }

        $training->delete();
        return response()->json(['message' => 'Training and associated media deleted successfully']);
    }

    /**
     * Register user for training (duplicate of RegistrationController method)
     * This method is referenced in routes but was missing
     */
    public function register(Request $request, Training $training)
    {
        $registration = TrainingRegistration::firstOrCreate([
            'user_id' => $request->user()->id,
            'training_id' => $training->id,
        ], [
            'status' => 'approved',
            'registration_date' => now(),
        ]);
        
        return response()->json($registration, 201);
    }

    /**
     * Upload media files to training (separate endpoint)
     * POST /api/v1/trainings/{training}/upload-media
     */
    public function uploadMedia(Request $request, Training $training)
    {
        $validated = $request->validate([
            'banner_image' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'gif', 'webp'])->max(5 * 1024)],
            'intro_video' => ['nullable', File::types(['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'])->max(20 * 1024)],
            'media_files.*' => ['nullable', 'file', 'max:' . (50 * 1024)],
            'type' => ['nullable', 'string', 'in:banner,intro_video,general'],
        ]);

        $uploadedFiles = [];

        // Handle banner image upload
        if ($request->hasFile('banner_image')) {
            $bannerPath = $request->file('banner_image')->store('trainings/banners', 'public');
            $training->addMediaFile(
                $bannerPath,
                $request->file('banner_image')->getClientOriginalName(),
                $request->file('banner_image')->getMimeType(),
                $request->file('banner_image')->getSize(),
                'banner'
            );
            $uploadedFiles[] = [
                'type' => 'banner',
                'path' => $bannerPath,
                'original_name' => $request->file('banner_image')->getClientOriginalName(),
            ];
        }

        // Handle intro video upload
        if ($request->hasFile('intro_video')) {
            $videoPath = $request->file('intro_video')->store('trainings/videos', 'public');
            $training->addMediaFile(
                $videoPath,
                $request->file('intro_video')->getClientOriginalName(),
                $request->file('intro_video')->getMimeType(),
                $request->file('intro_video')->getSize(),
                'intro_video'
            );
            $uploadedFiles[] = [
                'type' => 'intro_video',
                'path' => $videoPath,
                'original_name' => $request->file('intro_video')->getClientOriginalName(),
            ];
        }

        // Handle general media files
        if ($request->hasFile('media_files')) {
            foreach ($request->file('media_files') as $file) {
                $path = $file->store('trainings/media', 'public');
                $training->addMediaFile(
                    $path,
                    $file->getClientOriginalName(),
                    $file->getMimeType(),
                    $file->getSize(),
                    'general'
                );
                $uploadedFiles[] = [
                    'type' => 'general',
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                ];
            }
        }

        return response()->json([
            'message' => 'Media files uploaded successfully',
            'uploaded_files' => $uploadedFiles,
            'training' => $training->fresh()
        ], 201);
    }

    /**
     * Get all media files for a training
     * GET /api/v1/trainings/{training}/media
     */
    public function getMedia(Training $training)
    {
        return response()->json([
            'training_id' => $training->id,
            'media_files' => $training->media_files,
            'banner_image' => $training->banner_image,
            'intro_video' => $training->intro_video,
            'general_media_files' => $training->general_media_files,
        ]);
    }

    /**
     * Remove a specific media file from training
     * DELETE /api/v1/trainings/{training}/media/{mediaId}
     */
    public function removeMedia(Request $request, Training $training, $mediaId)
    {
        $mediaFiles = $training->media_files ?? [];
        
        // Find the media file by path (using mediaId as path)
        $mediaFile = collect($mediaFiles)->firstWhere('path', $mediaId);
        
        if (!$mediaFile) {
            return response()->json(['error' => 'Media file not found'], 404);
        }

        // Remove the file
        $training->removeMediaFile($mediaId);

        return response()->json([
            'message' => 'Media file removed successfully',
            'removed_file' => $mediaFile
        ]);
    }
}
