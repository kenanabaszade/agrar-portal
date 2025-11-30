<?php
 
namespace App\Http\Controllers;
 
use App\Models\Training;
use App\Models\TrainingRegistration;
use App\Models\UserTrainingProgress;
use App\Models\Certificate;
use App\Models\Exam;
use App\Models\User;
use App\Services\GoogleCalendarService;
use App\Services\TranslationHelper;
use App\Services\CertificateGeneratorService;
use App\Services\NotificationService;
use App\Mail\TrainingCreatedNotification;
use App\Mail\TrainingNotification;
use App\Mail\TrainingCompletionNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;

class TrainingController extends Controller
{
    private ?GoogleCalendarService $googleCalendarService = null;

    public function __construct()
    {
        // Lazy load GoogleCalendarService only when needed to avoid autoload issues
        try {
            $this->googleCalendarService = app(GoogleCalendarService::class);
        } catch (\Throwable $e) {
            // If Google API client is not available, service will be null
            // Methods that use it should check for null before use
            \Log::warning('GoogleCalendarService could not be instantiated: ' . $e->getMessage());
        }
    }
    
    /**
     * Get GoogleCalendarService instance, creating it if needed
     */
    private function getGoogleCalendarService(): ?GoogleCalendarService
    {
        if ($this->googleCalendarService === null) {
            try {
                $this->googleCalendarService = app(GoogleCalendarService::class);
            } catch (\Throwable $e) {
                \Log::warning('GoogleCalendarService could not be instantiated: ' . $e->getMessage());
                return null;
            }
        }
        return $this->googleCalendarService;
    }
    
    public function index(Request $request)
    {
        $query = Training::with(['trainer:id,first_name,last_name', 'exam:id,title'])
            ->withCount([
                'registrations',
                'registrations as started_registrations_count' => function ($q) {
                    $q->whereHas('userTrainingProgress', function ($p) {
                        $p->where('status', 'in_progress');
                    });
                },
                'registrations as completed_registrations_count' => function ($q) {
                    $q->whereHas('userTrainingProgress', function ($p) {
                        $p->where('status', 'completed');
                    });
                },
                'modules',
                'lessons'
            ])
            ->when($request->boolean('include_modules'), function ($q) {
                $q->with('modules.lessons:id,module_id,title,video_url,pdf_url,duration_minutes');
            });

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
        $trainings->getCollection()->transform(function ($training) use ($request) {
            // Use cached count attributes from withCount (no additional queries!)
            $participantMetrics = $this->calculateParticipantMetrics($training);
            $totalParticipants = $participantMetrics['participants_count'];
            $completedLessonParticipants = $participantMetrics['completed_lesson_participants'];

            $totalRegistrations = $training->registrations_count ?? 0;
            $completedRegistrations = $training->completed_registrations_count ?? 0;
            $startedRegistrations = $training->started_registrations_count ?? 0;

            // Ensure video trainings reflect participants who completed lessons
            $totalRegistrations = max($totalRegistrations, $totalParticipants);

            // Calculate completion percentage
            $completionRate = $totalRegistrations > 0 ? round(($completedRegistrations / $totalRegistrations) * 100, 2) : 0;
            $progressRate = $totalRegistrations > 0 ? round((($completedRegistrations + $startedRegistrations) / $totalRegistrations) * 100, 2) : 0;

            // Count media files from training only (lightweight)
            $trainingMediaFiles = $training->media_files ?? [];
            $mediaCounts = $this->countMediaFilesByType($trainingMediaFiles);
            
            // Only count module/lesson media if modules are loaded
            if ($request->boolean('include_modules') && $training->relationLoaded('modules')) {
                foreach ($training->modules as $module) {
                    foreach ($module->lessons as $lesson) {
                        if (!empty($lesson->video_url)) {
                            $mediaCounts['videos']++;
                        }
                        if (!empty($lesson->pdf_url)) {
                            $mediaCounts['documents']++;
                        }
                        
                        $lessonMedia = $lesson->media_files ?? [];
                        $lessonCounts = $this->countMediaFilesByType($lessonMedia);
                        $mediaCounts['videos'] += $lessonCounts['videos'];
                        $mediaCounts['documents'] += $lessonCounts['documents'];
                        $mediaCounts['images'] += $lessonCounts['images'];
                        $mediaCounts['audio'] += $lessonCounts['audio'];
                    }
                }
            }
            
            $mediaStats = [
                'videos_count' => $mediaCounts['videos'],
                'documents_count' => $mediaCounts['documents'],
                'images_count' => $mediaCounts['images'],
                'audio_count' => $mediaCounts['audio'],
                'total_media' => $mediaCounts['videos'] + $mediaCounts['documents'] + $mediaCounts['images'] + $mediaCounts['audio'],
                'training_media_count' => count($trainingMediaFiles),
                'modules_count' => $training->modules_count ?? 0,
                'lessons_count' => $training->lessons_count ?? 0
            ];

            // Add statistics to training object
            $training->statistics = [
                'total_registrations' => $totalRegistrations,
                'participants_count' => $totalParticipants,
                'completed_lessons_participants' => $completedLessonParticipants,
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
        // Normalize request data: Convert title_az, title_en format to object format
        $requestData = $this->normalizeTranslationRequest($request->all());
        $request->merge($requestData);

        $validated = $request->validate([
            'title' => ['required', new \App\Rules\TranslationRule(true)],
            'description' => ['nullable', new \App\Rules\TranslationRule(false)],
            'category' => ['nullable', new \App\Rules\TranslationRule(false)],
            'trainer_id' => ['required', 'exists:users,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'is_online' => ['nullable', 'boolean'],
            'type' => ['nullable', 'string', 'regex:/^(online|offline|video)$/i'],
            'online_details' => ['nullable', 'array'],
            'online_details.participant_size' => ['nullable', 'string'],
            'online_details.google_meet_link' => ['nullable', 'string'],
            'offline_details' => ['nullable'],
            'offline_details.*.participant_size' => ['nullable', 'string'],
            'offline_details.*.address' => ['nullable', 'string'],
            'offline_details.*.coordinates' => ['nullable', 'string'],
            'offline_details.participant_size' => ['nullable', 'string'],
            'offline_details.address' => ['nullable', 'string'],
            'offline_details.coordinates' => ['nullable', 'string'],
            'has_certificate' => ['nullable', 'boolean'],
            'require_email_verification' => ['nullable', 'boolean'],
            'has_exam' => ['nullable', 'boolean'],
            'exam_id' => ['nullable', 'integer', 'exists:exams,id'],
            'exam_required' => ['nullable', 'boolean'],
            'min_exam_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'status' => ['nullable', 'string', 'in:draft,published,archived,cancelled'],
            'difficulty' => ['nullable', 'string', 'in:beginner,intermediate,advanced,expert'],
            'banner_image' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'gif', 'webp'])->max(5 * 1024)], // 5MB max
            'intro_video' => ['nullable', File::types(['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'])->max(100 * 1024)], // 100MB max
            'media_files.*' => ['nullable', 'file', 'max:' . (100 * 1024)], // 100MB max per file
            // New fields for Google Meet integration
            'google_meet_enabled' => ['nullable', 'boolean'],
            'meeting_start_time' => ['nullable', 'date'],
            'meeting_end_time' => ['nullable', 'date', 'after:meeting_start_time'],
            'attendees' => ['nullable', 'array'],
            'attendees.*.email' => ['nullable', 'email'],
            'attendees.*.name' => ['nullable', 'string'],
            // Recurring meeting fields
            'is_recurring' => ['nullable', 'boolean'],
            'recurrence_frequency' => ['nullable', 'string', 'in:daily,weekly,monthly'],
            'recurrence_end_date' => ['nullable', 'date', 'after:start_date'],
        ]);

        // Normalize translation fields
        if (isset($validated['title'])) {
            $validated['title'] = TranslationHelper::normalizeTranslation($validated['title']);
        }
        if (isset($validated['description'])) {
            $validated['description'] = TranslationHelper::normalizeTranslation($validated['description']);
        }
        if (isset($validated['category'])) {
            $validated['category'] = TranslationHelper::normalizeTranslation($validated['category']);
        }
        if (isset($validated['certificate_description'])) {
            $validated['certificate_description'] = TranslationHelper::normalizeTranslation($validated['certificate_description']);
        }

        // Set default values
        $validated['is_online'] = $validated['is_online'] ?? true;
        $validated['has_certificate'] = $validated['has_certificate'] ?? false;
        $validated['google_meet_enabled'] = $validated['google_meet_enabled'] ?? false;

        // Fix offline_details if it comes as array from frontend
        if (isset($validated['offline_details']) && is_array($validated['offline_details'])) {
            // If it's an array with numeric keys (like [0 => {...}]), get the first element
            if (isset($validated['offline_details'][0]) && is_array($validated['offline_details'][0])) {
                $validated['offline_details'] = $validated['offline_details'][0];
            }
        }

        // Remove file inputs from validated data as they're not database fields
        unset($validated['banner_image'], $validated['intro_video'], $validated['media_files']);

        try {
            DB::beginTransaction();

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
                
                // Process video into HLS quality variants (background job)
                $hlsEnabled = config('ffmpeg.hls.enabled', false);
                if ($hlsEnabled) {
                    \App\Jobs\ProcessTrainingVideoHLS::dispatch($training->id, $videoPath)
                        ->delay(now()->addSeconds(5)); // Small delay to ensure training is saved
                }
            }

            // Handle general media files
            if ($request->hasFile('media_files')) {
                foreach ($request->file('media_files') as $file) {
                    // Store in training-specific folder: trainings/{id}/media/
                    $path = $file->store("trainings/{$training->id}/media", 'public');
                    $mediaFiles[] = [
                        'type' => 'general',
                        'path' => $path,
                        'url' => Storage::url($path),
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

            // Handle Google Meet integration for online trainings
            $googleMeetLink = null;
            $googleEventId = null;
            $meetingId = null;
            $recurringMeetings = [];
            
            if ($validated['type'] === 'online' && 
                isset($validated['google_meet_enabled']) && 
                $validated['google_meet_enabled'] && 
                isset($validated['meeting_start_time']) && 
                isset($validated['meeting_end_time'])) {
                
                // Check if user has Google Calendar access
                $user = $request->user();
                $googleService = $this->getGoogleCalendarService();
                if ($user->google_access_token && $googleService) {
                    // Set the user's access token for Google Calendar API
                    $googleService->setAccessToken($user->google_access_token);
                    
                    // Verify the access token is still valid
                    $tokenValidation = $googleService->validateAccessToken();
                    
                    if ($tokenValidation['valid']) {
                        // Prepare meeting data for Google Calendar
                        // Extract translated title/description for Google Calendar (use default language)
                        $meetingTitle = is_array($validated['title']) ? ($validated['title']['az'] ?? reset($validated['title'])) : $validated['title'];
                        $meetingDescription = '';
                        if (isset($validated['description'])) {
                            $meetingDescription = is_array($validated['description']) ? ($validated['description']['az'] ?? reset($validated['description']) ?? '') : ($validated['description'] ?? '');
                        }
                        $meetingData = [
                            'title' => $meetingTitle,
                            'description' => $meetingDescription,
                            'start_time' => $validated['meeting_start_time'],
                            'end_time' => $validated['meeting_end_time'],
                            'timezone' => $validated['timezone'] ?? 'UTC',
                            'attendees' => $validated['attendees'] ?? [],
                        ];

                        // Check if this is a recurring meeting
                        if (isset($validated['is_recurring']) && $validated['is_recurring'] && 
                            isset($validated['recurrence_frequency']) && isset($validated['recurrence_end_date'])) {
                            
                            // Create recurring meetings
                            $recurringMeetings = $this->createRecurringMeetings($meetingData, $validated, $user, $googleService);
                            
                            if (!empty($recurringMeetings)) {
                                // Use the first meeting as the main meeting
                                $firstMeeting = $recurringMeetings[0];
                                $googleMeetLink = $firstMeeting['meet_link'];
                                $googleEventId = $firstMeeting['event_id'];
                                $meetingId = $firstMeeting['meeting_id'];
                                
                                // Update training with Google Meet information
                                $training->update([
                                    'google_meet_link' => $googleMeetLink,
                                    'google_event_id' => $googleEventId,
                                    'meeting_id' => $meetingId,
                                    'is_recurring' => true,
                                    'recurrence_frequency' => $validated['recurrence_frequency'],
                                    'recurrence_end_date' => $validated['recurrence_end_date'],
                                ]);
                            }
                        } else {
                            // Create single meeting
                            $googleResult = $googleService->createMeeting($meetingData);

                            if ($googleResult['success']) {
                                $googleMeetLink = $googleResult['meet_link'];
                                $googleEventId = $googleResult['event_id'];
                                $meetingId = $googleResult['meeting_id'];
                                
                                // Update training with Google Meet information
                                $training->update([
                                    'google_meet_link' => $googleMeetLink,
                                    'google_event_id' => $googleEventId,
                                    'meeting_id' => $meetingId,
                                ]);
                            }
                        }
                    }
                }
            }

            DB::commit();

            // Send email notifications to all users for any training type
            $this->sendTrainingNotifications($training, 'created', $googleMeetLink);

            // Load training with all related data for response
            $training->load(['modules.lessons', 'trainer']);
            
            // Add offline specific details to response if training is offline
            if ($training->type === 'offline') {
                $training->offline_details = $training->offline_details;
                $training->address = $training->offline_details['address'] ?? null;
                $training->coordinates = $training->offline_details['coordinates'] ?? null;
                $training->participant_size = $training->offline_details['participant_size'] ?? null;
                
                // Add trainer details
                $training->trainer_name = $training->trainer ? 
                    $training->trainer->first_name . ' ' . $training->trainer->last_name : null;
                $training->trainer_email = $training->trainer ? $training->trainer->email : null;
                $training->trainer_phone = $training->trainer ? $training->trainer->phone : null;
            }

            return response()->json([
                'message' => 'Training created successfully',
                'training' => $training,
                'google_meet_link' => $googleMeetLink,
                'notifications_sent' => User::where('email', '!=', null)->where('email', '!=', '')->count()
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'error' => 'Failed to create training',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Training $training)
    {
        return $training->load('modules.lessons', 'exam');
    }

    public function update(Request $request, Training $training)
    {
        // Normalize request data: Convert title_az, title_en format to object format
        $requestData = $this->normalizeTranslationRequest($request->all());
        $request->merge($requestData);

        $validated = $request->validate([
            'title' => ['sometimes', new \App\Rules\TranslationRule(true)],
            'description' => ['nullable', new \App\Rules\TranslationRule(false)],
            'category' => ['nullable', new \App\Rules\TranslationRule(false)],
            'trainer_id' => ['sometimes', 'exists:users,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'is_online' => ['nullable', 'boolean'],
            'type' => ['nullable', 'string', 'regex:/^(online|offline|video)$/i'],
            'online_details' => ['nullable', 'array'],
            'online_details.participant_size' => ['nullable', 'string'],
            'online_details.google_meet_link' => ['nullable', 'string'],
            'offline_details' => ['nullable'],
            'offline_details.*.participant_size' => ['nullable', 'string'],
            'offline_details.*.address' => ['nullable', 'string'],
            'offline_details.*.coordinates' => ['nullable', 'string'],
            'offline_details.participant_size' => ['nullable', 'string'],
            'offline_details.address' => ['nullable', 'string'],
            'offline_details.coordinates' => ['nullable', 'string'],
            'has_certificate' => ['nullable', 'boolean'],
            'certificate_description' => ['nullable', new \App\Rules\TranslationRule(false)],
            'certificate_has_expiry' => ['nullable', 'boolean'],
            'certificate_expiry_years' => ['nullable', 'integer', 'min:0'],
            'certificate_expiry_months' => ['nullable', 'integer', 'min:0', 'max:11'],
            'certificate_expiry_days' => ['nullable', 'integer', 'min:0', 'max:30'],
            'require_email_verification' => ['nullable', 'boolean'],
            'has_exam' => ['nullable', 'boolean'],
            'exam_id' => ['nullable', 'integer', 'exists:exams,id'],
            'exam_required' => ['nullable', 'boolean'],
            'min_exam_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'exam_for_certificate' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'in:draft,published,archived,cancelled'],
            'difficulty' => ['nullable', 'string', 'in:beginner,intermediate,advanced,expert'],
            'banner_image' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'gif', 'webp'])->max(5 * 1024)], // 5MB max
            'intro_video' => ['nullable', File::types(['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'])->max(100 * 1024)], // 100MB max
            'media_files.*' => ['nullable', 'file', 'max:' . (100 * 1024)], // 100MB max per file
            'remove_banner' => ['nullable', 'boolean'],
            'remove_intro_video' => ['nullable', 'boolean'],
            'remove_media_files' => ['nullable', 'array'], // Array of file paths to remove
            'remove_media_files.*' => ['string'],
            // Google Meet integration fields for update
            'google_meet_enabled' => ['nullable', 'boolean'],
            'meeting_start_time' => ['nullable', 'date'],
            'meeting_end_time' => ['nullable', 'date', 'after:meeting_start_time'],
            'attendees' => ['nullable', 'array'],
            'attendees.*.email' => ['nullable', 'email'],
            'attendees.*.name' => ['nullable', 'string'],
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
            
            // Process video into HLS quality variants (background job)
            $hlsEnabled = config('ffmpeg.hls.enabled', false);
            if ($hlsEnabled) {
                \App\Jobs\ProcessTrainingVideoHLS::dispatch($training->id, $videoPath)
                    ->delay(now()->addSeconds(5)); // Small delay to ensure training is saved
            }
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
                // Store in training-specific folder: trainings/{id}/media/
                $path = $file->store("trainings/{$training->id}/media", 'public');
                $training->addMediaFile(
                    $path,
                    $file->getClientOriginalName(),
                    $file->getMimeType(),
                    $file->getSize(),
                    'general'
                );
            }
        }

        // Fix offline_details if it comes as array from frontend
        if (isset($validated['offline_details']) && is_array($validated['offline_details'])) {
            // If it's an array with numeric keys (like [0 => {...}]), get the first element
            if (isset($validated['offline_details'][0]) && is_array($validated['offline_details'][0])) {
                $validated['offline_details'] = $validated['offline_details'][0];
            }
        }

        // Normalize translation fields
        if (isset($validated['title'])) {
            $validated['title'] = TranslationHelper::normalizeTranslation($validated['title']);
        }
        if (isset($validated['description'])) {
            $validated['description'] = TranslationHelper::normalizeTranslation($validated['description']);
        }
        if (isset($validated['category'])) {
            $validated['category'] = TranslationHelper::normalizeTranslation($validated['category']);
        }
        if (isset($validated['certificate_description'])) {
            $validated['certificate_description'] = TranslationHelper::normalizeTranslation($validated['certificate_description']);
        }

        // Remove file inputs and control flags from validated data
        unset($validated['banner_image'], $validated['intro_video'], $validated['media_files'], 
              $validated['remove_banner'], $validated['remove_intro_video'], $validated['remove_media_files']);

        try {
            DB::beginTransaction();

            // Handle Google Meet integration for online trainings
            $googleMeetLink = $training->google_meet_link;
            $googleEventId = $training->google_event_id;
            $meetingId = $training->meeting_id;
            
            // Check if Google Meet needs to be updated
            $needsGoogleMeetUpdate = false;
            $needsGoogleMeetCreation = false;
            
            if (($validated['type'] ?? $training->type) === 'online') {
                // If Google Meet is enabled and meeting times are provided
                if (isset($validated['google_meet_enabled']) && $validated['google_meet_enabled'] && 
                    isset($validated['meeting_start_time']) && isset($validated['meeting_end_time'])) {
                    
                    // If training already has Google Meet, update it
                    if ($training->google_event_id) {
                        $needsGoogleMeetUpdate = true;
                    } else {
                        // If no existing Google Meet, create new one
                        $needsGoogleMeetCreation = true;
                    }
                }
                // If Google Meet is disabled, remove it
                elseif (isset($validated['google_meet_enabled']) && !$validated['google_meet_enabled']) {
                    if ($training->google_event_id) {
                        // Delete existing Google Meet
                        $user = $request->user();
                        $googleService = $this->getGoogleCalendarService();
                        if ($user->google_access_token && $googleService) {
                            $googleService->setAccessToken($user->google_access_token);
                            $googleService->deleteMeeting($training->google_event_id);
                        }
                        
                        // Clear Google Meet fields
                        $validated['google_meet_link'] = null;
                        $validated['google_event_id'] = null;
                        $validated['meeting_id'] = null;
                    }
                }
            }

            // Update Google Meet if needed
            if ($needsGoogleMeetUpdate || $needsGoogleMeetCreation) {
                $user = $request->user();
                $googleService = $this->getGoogleCalendarService();
                if ($user->google_access_token && $googleService) {
                    $googleService->setAccessToken($user->google_access_token);
                    
                    // Verify the access token is still valid
                    $tokenValidation = $googleService->validateAccessToken();
                    
                    if ($tokenValidation['valid']) {
                        // Prepare meeting data for Google Calendar
                        // Extract translated title/description for Google Calendar (use default language)
                        $currentTitle = $validated['title'] ?? $training->title;
                        $currentDescription = $validated['description'] ?? $training->description ?? '';
                        
                        $meetingTitle = is_array($currentTitle) ? ($currentTitle['az'] ?? reset($currentTitle)) : $currentTitle;
                        $meetingDescription = is_array($currentDescription) ? ($currentDescription['az'] ?? reset($currentDescription) ?? '') : $currentDescription;
                        
                        $meetingData = [
                            'title' => $meetingTitle,
                            'description' => $meetingDescription,
                            'start_time' => $validated['meeting_start_time'],
                            'end_time' => $validated['meeting_end_time'],
                            'timezone' => $validated['timezone'] ?? $training->timezone ?? 'UTC',
                            'attendees' => $validated['attendees'] ?? [],
                        ];

                        if ($needsGoogleMeetUpdate) {
                            // Update existing Google Meet
                            $googleResult = $googleService->updateMeeting($training->google_event_id, $meetingData);
                        } else {
                            // Create new Google Meet
                            $googleResult = $googleService->createMeeting($meetingData);
                        }

                        if ($googleResult['success']) {
                            $googleMeetLink = $googleResult['meet_link'];
                            $googleEventId = $googleResult['event_id'];
                            $meetingId = $googleResult['meeting_id'];
                            
                            // Update training with Google Meet information
                            $validated['google_meet_link'] = $googleMeetLink;
                            $validated['google_event_id'] = $googleEventId;
                            $validated['meeting_id'] = $meetingId;
                        }
                    }
                }
            }

            // Update training
            $training->update($validated);

            // Send email notifications to all users for any training type
            $this->sendTrainingNotifications($training, 'updated', $googleMeetLink);

            DB::commit();

            return response()->json([
                'message' => 'Training updated successfully',
                'training' => $training->fresh()->load('modules.lessons'),
                'google_meet_link' => $googleMeetLink,
                'google_meet_updated' => $needsGoogleMeetUpdate || $needsGoogleMeetCreation,
                'notifications_sent' => User::where('email', '!=', null)->where('email', '!=', '')->count()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'error' => 'Failed to update training',
                'details' => $e->getMessage()
            ], 500);
        }
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
            'intro_video' => ['nullable', File::types(['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'])->max(100 * 1024)], // 100MB max
            'media_files.*' => ['nullable', 'file', 'max:' . (100 * 1024)], // 100MB max per file
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
            
            // Process video into HLS quality variants (background job)
            $hlsEnabled = config('ffmpeg.hls.enabled', false);
            if ($hlsEnabled) {
                \App\Jobs\ProcessTrainingVideoHLS::dispatch($training->id, $videoPath)
                    ->delay(now()->addSeconds(5)); // Small delay to ensure training is saved
            }
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

    /**
     * Get training media files
     */
    public function getMediaFiles(Training $training)
    {
        return response()->json([
            'training_id' => $training->id,
            'training_title' => $training->title,
            'media_files' => $training->media_files ?? []
        ]);
    }

    /**
     * Upload media files to training
     */
    public function uploadMediaFiles(Request $request, Training $training)
    {
        $validated = $request->validate([
            'media_files.*' => ['required', 'file', 'max:' . (100 * 1024)], // 100MB max per file
        ]);

        $mediaFiles = $training->media_files ?? [];

        if ($request->hasFile('media_files')) {
            foreach ($request->file('media_files') as $file) {
                // Store in training-specific folder: trainings/{id}/media/
                $path = $file->store("trainings/{$training->id}/media", 'public');
                $mediaFiles[] = [
                    'type' => 'general',
                    'path' => $path,
                    'url' => Storage::url($path),
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_at' => now()->toISOString(),
                ];
            }
        }

        $training->update(['media_files' => $mediaFiles]);

        return response()->json([
            'message' => 'Media files uploaded successfully',
            'media_files' => $mediaFiles
        ]);
    }

    /**
     * Delete media files from training
     */
    public function deleteMediaFiles(Request $request, Training $training)
    {
        $validated = $request->validate([
            'file_paths' => ['required', 'array'],
            'file_paths.*' => ['string'],
        ]);

        $mediaFiles = $training->media_files ?? [];
        $filesToDelete = $validated['file_paths'];

        // Delete physical files
        foreach ($filesToDelete as $filePath) {
            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
        }

        // Remove from media_files array
        $updatedMediaFiles = array_filter($mediaFiles, function($mediaFile) use ($filesToDelete) {
            return !in_array($mediaFile['path'], $filesToDelete);
        });

        $training->update(['media_files' => array_values($updatedMediaFiles)]);

        return response()->json([
            'message' => 'Media files deleted successfully',
            'deleted_files' => $filesToDelete,
            'remaining_files' => $updatedMediaFiles
        ]);
    }

    /**
     * Get trainings dropdown for exam creation
     * GET /api/v1/trainings/dropdown
     */
        public function dropdown(Request $request)
    {
        $user = $request->user();

        $locale = app()->getLocale() ?? 'az';
        $query = Training::select('id', 'title', 'category')
            ->orderByRaw("title->>'{$locale}' ASC")
            ->orderByRaw("title->>'az' ASC"); // Fallback to az if locale doesn't exist

        // If user is trainer, only show their trainings
        if ($user->user_type === 'trainer') {
            $query->where('trainer_id', $user->id);
        }

        $trainings = $query->get();

        return response()->json([
            'trainings' => $trainings,
            'message' => 'Trainings retrieved successfully'
        ]);
    }

    /**
     * Get public trainings (no authentication required)
     * GET /api/v1/trainings/public
     */
    public function public(Request $request)
    {
        // Build cache key based on request parameters
        $cacheKey = 'trainings:public:' . md5(json_encode([
            'search' => $request->get('search'),
            'category' => $request->get('category'),
            'trainer_id' => $request->get('trainer_id'),
            'difficulty' => $request->get('difficulty'),
            'type' => $request->get('type'),
            'sort_by' => $request->get('sort_by', 'created_at'),
            'sort_order' => $request->get('sort_order', 'desc'),
            'per_page' => $request->get('per_page', 15),
            'page' => $request->get('page', 1),
        ]));

        // Try to get from cache first (5 minutes cache)
        $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
        if ($cached && !$request->has('nocache')) {
            return response()->json($cached);
        }

        // Optimize: Select only necessary columns for trainings
        // Note: is_offline doesn't exist as a column, it's calculated from type
        $query = Training::select([
            'id', 'title', 'description', 'category', 'difficulty', 
            'type', 'is_online', 'media_files', 'trainer_id'
        ])
        ->with([
            // Optimize: Only select necessary columns for modules
            'modules' => function ($q) {
                $q->select('id', 'training_id', 'title', 'sequence');
            },
            // Optimize: Only select necessary columns for lessons (duration_minutes for calculation)
            'modules.lessons' => function ($q) {
                $q->select('id', 'module_id', 'title', 'duration_minutes');
            },
            // Optimize: Only select trainer name fields
            'trainer' => function ($q) {
                $q->select('id', 'first_name', 'last_name');
            }
        ])
        ->withCount([
            'registrations',
            // Optimize: Count modules in SQL (avoid loading all data)
            'modules as modules_count'
        ])
        ->where('status', 'published'); // Only show published trainings

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

        // Optimize: Pre-load ratings for all trainings in batch (avoid N+1)
        $trainingIds = $trainings->pluck('id');
        $ratingsData = \App\Models\TrainingRating::selectRaw('training_id, AVG(rating) as avg_rating, COUNT(*) as count')
            ->whereIn('training_id', $trainingIds)
            ->groupBy('training_id')
            ->get()
            ->keyBy('training_id');

        // Optimize: Pre-load user data if authenticated (batch queries)
        $userRegistrations = [];
        $userCertificates = [];
        $userProgressData = [];
        
        if (auth()->check()) {
            $user = auth()->user();
            $userId = $user->id;
            
            // Batch load all registrations for user
            $userRegistrations = \App\Models\TrainingRegistration::where('user_id', $userId)
                ->whereIn('training_id', $trainingIds)
                ->get()
                ->keyBy('training_id');
            
            // Batch load all certificates for user
            $userCertificates = \App\Models\Certificate::where('user_id', $userId)
                ->whereIn('related_training_id', $trainingIds)
                ->get()
                ->keyBy('related_training_id');
            
            // Batch load all progress data for user
            $progressData = \App\Models\UserTrainingProgress::selectRaw('training_id, COUNT(*) as completed_count')
                ->where('user_id', $userId)
                ->whereIn('training_id', $trainingIds)
                ->where('status', 'completed')
                ->groupBy('training_id')
                ->get()
                ->keyBy('training_id');
            
            $userProgressData = $progressData;
        }

        // Transform trainings with optimized data
        $trainings->getCollection()->transform(function ($training) use ($ratingsData, $userRegistrations, $userCertificates, $userProgressData) {
            // Get banner file (only one needed)
            $bannerFile = collect($training->media_files ?? [])->firstWhere('type', 'banner');
            $bannerUrl = $bannerFile ? url('storage/' . $bannerFile['path']) : null;

            // Use pre-calculated count from withCount (faster than counting in PHP)
            $modulesCount = $training->modules_count ?? $training->modules->count();
            
            // Calculate lessons count from loaded modules (already eager loaded, no extra query)
            $lessonsCount = $training->modules->sum(fn($m) => $m->lessons->count());

            // Calculate is_offline from type
            $isOffline = strtolower($training->type ?? '') === 'offline';

            // Get rating from pre-loaded data (avoid N+1)
            $ratingInfo = $ratingsData->get($training->id);
            $averageRating = $ratingInfo ? round((float) $ratingInfo->avg_rating, 2) : null;
            $ratingsCount = $ratingInfo ? (int) $ratingInfo->count : 0;

            // Get user completion from pre-loaded data (avoid N+1)
            $userRegistration = $userRegistrations[$training->id] ?? null;
            $userCertificate = $userCertificates[$training->id] ?? null;
            
            $isRegistered = (bool) $userRegistration;
            $isCompleted = false;
            
            if ($userRegistration) {
                $isCompleted = $userRegistration->status === 'completed';
                if ($training->type === 'video' && $userCertificate) {
                    $isCompleted = true;
                }
            } elseif ($userCertificate) {
                $isCompleted = true;
            }

            // Get user progress from pre-loaded data (avoid N+1)
            $progressInfo = $userProgressData[$training->id] ?? null;
            $completedLessons = $progressInfo ? (int) $progressInfo->completed_count : 0;
            $percentage = $lessonsCount > 0 ? round(($completedLessons / $lessonsCount) * 100, 2) : 0;

            // Build optimized response with only necessary fields
            return [
                'id' => $training->id,
                'title' => $training->title,
                'description' => $training->description,
                'category' => $training->category,
                'difficulty' => $training->difficulty,
                'type' => $training->type,
                'is_online' => $training->is_online,
                'is_offline' => $isOffline,
                'media_files' => $bannerFile ? [[
                    'type' => 'banner',
                    'url' => $bannerUrl,
                ]] : [],
                'modules' => $training->modules->map(function ($module) {
                    return [
                        'id' => $module->id,
                        'title' => $module->title,
                        'lessons' => $module->lessons->map(function ($lesson) {
                            return [
                                'id' => $lesson->id,
                                'title' => $lesson->title,
                                'duration_minutes' => $lesson->duration_minutes,
                            ];
                        }),
                    ];
                }),
                'media_statistics' => [
                    'modules_count' => $modulesCount,
                    'lessons_count' => $lessonsCount,
                ],
                'trainer' => $training->trainer ? [
                    'id' => $training->trainer->id,
                    'first_name' => $training->trainer->first_name,
                    'last_name' => $training->trainer->last_name,
                ] : null,
                'user_completion' => [
                    'is_registered' => $isRegistered,
                    'is_completed' => $isCompleted,
                ],
                'user_progress' => auth()->check() && $isRegistered ? [
                    'percentage' => $percentage,
                ] : null,
                'rating' => [
                    'average_rating' => $averageRating,
                    'ratings_count' => $ratingsCount,
                ],
            ];
        });

        // Build paginated response
        $responseData = [
            'data' => $trainings->items(),
            'current_page' => $trainings->currentPage(),
            'per_page' => $trainings->perPage(),
            'total' => $trainings->total(),
            'last_page' => $trainings->lastPage(),
            'from' => $trainings->firstItem(),
            'to' => $trainings->lastItem(),
        ];

        // Cache the response for 5 minutes (only if not authenticated, as user data changes)
        if (!auth()->check()) {
            Cache::put($cacheKey, $responseData, 300); // 5 minutes
        }

        return response()->json($responseData);
    }

    /**
     * Get detailed training information (public access with optional user registration status)
     * GET /api/v1/trainings/{training}/detailed
     */
    public function detailed(Training $training)
    {
        // ✅ Timeout və memory limit artır (bu endpoint çox kompleksdir)
        @ini_set('max_execution_time', 600); // 10 dəqiqə
        @ini_set('memory_limit', '512M');
        
        // Only show published trainings for public access
        if ($training->status !== 'published') {
            return response()->json(['message' => 'Training not found'], 404);
        }

        // ✅ Optimized eager loading - yalnız lazım olan field-ləri yüklə
        $training->load([
            'modules' => function ($query) {
                $query->select(['id', 'training_id', 'sequence', 'title', 'created_at', 'updated_at']);
            },
            'modules.lessons' => function ($query) {
                $query->select([
                    'id',
                    'module_id',
                    'title',
                    'sequence',
                    'lesson_type',
                    'duration_minutes',
                    'status',
                    'is_required',
                    'min_completion_time',
                    'media_files',
                    'metadata',
                    'content',
                    'description',
                    'created_at',
                    'updated_at'
                ]);
            },
            'trainer:id,first_name,last_name,email,user_type',
            'exam:id,title'
        ]);
        
        // Get authenticated user (if any)
        $userId = auth()->check() ? auth()->user()->id : null;
        
        // Transform lesson media URLs to signed URLs (temporary URLs that expire)
        foreach ($training->modules as $module) {
            foreach ($module->lessons as $lesson) {
                // Get media_files (may already be transformed by accessor)
                $mediaFiles = $lesson->media_files ?? [];
                
                if ($mediaFiles && is_array($mediaFiles)) {
                    $lesson->media_files = collect($mediaFiles)->map(function ($mediaFile) use ($module, $lesson, $userId) {
                        // Extract path from URL if path field doesn't exist
                        $filePath = $mediaFile['path'] ?? null;
                        
                        if (!$filePath && isset($mediaFile['url'])) {
                            // Extract path from protected endpoint URL
                            // URL format: /api/v1/modules/1/lessons/2/media/download?path=lessons%2F2%2Fvideo.mp4
                            $url = $mediaFile['url'];
                            if (preg_match('/[?&]path=([^&]+)/', $url, $matches)) {
                                $filePath = urldecode($matches[1]);
                            } elseif (preg_match('/\/lessons\/(\d+)\/([^\/]+)$/', $url, $matches)) {
                                // Fallback: extract from URL path
                                $filePath = 'lessons/' . $matches[1] . '/' . $matches[2];
                            }
                        }
                        
                        // Generate signed URL for all media files (videos, images, PDFs, etc.)
                        if ($filePath) {
                            try {
                                // Generate temporary signed URL (expires in 2 hours)
                                // Use null userId so signed URL works in browser without authentication
                                $signedUrl = \App\Http\Controllers\LessonMediaController::generateSignedUrl(
                                    $module,
                                    $lesson,
                                    $filePath,
                                    null, // Always use null for browser compatibility
                                    120 // 2 hours expiration
                                );
                                
                                // Add signed URL and expiration info
                                $mediaFile['signed_url'] = $signedUrl;
                                $mediaFile['signed_url_expires_at'] = now()->addHours(2)->toIso8601String();
                                
                                // Keep path field for reference
                                if (!isset($mediaFile['path'])) {
                                    $mediaFile['path'] = $filePath;
                                }
                                
                                // Keep original protected URL as fallback
                                if (!isset($mediaFile['url'])) {
                                    $mediaFile['url'] = route('lesson.media.download', [
                                        'module' => $module->id,
                                        'lesson' => $lesson->id,
                                        'path' => $filePath
                                    ]);
                                }
                                
                                // For video files, also handle HLS master playlist URL və variants (əgər varsa)
                                if (isset($mediaFile['type']) && $mediaFile['type'] === 'video') {
                                    // Əvvəlcə database-dən yoxla, sonra disk-dən dinamik yoxla
                                    $hlsMasterPlaylist = $mediaFile['hls_master_playlist'] ?? null;
                                    $hlsVariants = $mediaFile['hls_variants'] ?? [];
                                    
                                    // Əgər database-də yoxdursa, disk-dən yoxla
                                    if (!$hlsMasterPlaylist && $filePath) {
                                        $hlsInfo = $this->detectHLSFiles($lesson->id, $filePath);
                                        if ($hlsInfo) {
                                            $hlsMasterPlaylist = $hlsInfo['master_playlist'];
                                            $hlsVariants = $hlsInfo['variants'];
                                        }
                                    }
                                    
                                    if ($hlsMasterPlaylist) {
                                        // HLS playlist üçün signed URL yarat
                                        $hlsPlaylistPath = $hlsMasterPlaylist;
                                        $hlsSignedUrl = \App\Http\Controllers\LessonMediaController::generateSignedUrl(
                                            $module,
                                            $lesson,
                                            $hlsPlaylistPath,
                                            null,
                                            120
                                        );
                                        $mediaFile['hls_master_playlist'] = $hlsMasterPlaylist;
                                        $mediaFile['hls_master_playlist_url'] = $hlsSignedUrl;
                                        
                                        // HLS variants üçün signed URL-lər yarat (quality selection üçün)
                                        if (!empty($hlsVariants) && is_array($hlsVariants)) {
                                            $mediaFile['hls_variants'] = [];
                                            foreach ($hlsVariants as $quality => $variant) {
                                                $variantData = $variant;
                                                if (isset($variant['playlist'])) {
                                                    $variantPlaylistPath = $variant['playlist'];
                                                    $variantSignedUrl = \App\Http\Controllers\LessonMediaController::generateSignedUrl(
                                                        $module,
                                                        $lesson,
                                                        $variantPlaylistPath,
                                                        null,
                                                        120
                                                    );
                                                    $variantData['playlist_url'] = $variantSignedUrl;
                                                }
                                                $mediaFile['hls_variants'][$quality] = $variantData;
                                            }
                                        }
                                    }
                                }
                            } catch (\Exception $e) {
                                // Log error but don't fail
                                \Log::error('Failed to generate signed URL', [
                                    'error' => $e->getMessage(),
                                    'module_id' => $module->id,
                                    'lesson_id' => $lesson->id,
                                    'file_path' => $filePath
                                ]);
                                
                                // Fallback to regular protected URL transformation
                                if (isset($mediaFile['path']) && !isset($mediaFile['url'])) {
                                    $mediaFile['url'] = route('lesson.media.download', [
                                        'module' => $module->id,
                                        'lesson' => $lesson->id,
                                        'path' => $mediaFile['path']
                                    ]);
                                } else {
                                    // Transform existing URLs if needed
                                    $mediaFile = $lesson->transformMediaUrls([$mediaFile])[0] ?? $mediaFile;
                                }
                            }
                        } else {
                            // If no path available, use regular protected URL transformation
                            if (isset($mediaFile['path']) && !isset($mediaFile['url'])) {
                                $mediaFile['url'] = route('lesson.media.download', [
                                    'module' => $module->id,
                                    'lesson' => $lesson->id,
                                    'path' => $mediaFile['path']
                                ]);
                            } else {
                                // Transform existing URLs if needed
                                $mediaFile = $lesson->transformMediaUrls([$mediaFile])[0] ?? $mediaFile;
                            }
                        }
                        
                        return $mediaFile;
                    })->toArray();
                }
            }
        }
        
        // Add statistics
        $participantMetrics = $this->calculateParticipantMetrics($training);
        $totalParticipants = $participantMetrics['participants_count'];
        $completedLessonParticipants = $participantMetrics['completed_lesson_participants'];

        $totalRegistrations = max($training->registrations()->count(), $totalParticipants);
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

        $completionRate = $totalRegistrations > 0 ? round(($completedRegistrations / $totalRegistrations) * 100, 2) : 0;
        $progressRate = $totalRegistrations > 0 ? round((($completedRegistrations + $startedRegistrations) / $totalRegistrations) * 100, 2) : 0;

        $training->statistics = [
            'total_registrations' => $totalRegistrations,
            'participants_count' => $totalParticipants,
            'completed_lessons_participants' => $completedLessonParticipants,
            'started_count' => $startedRegistrations,
            'completed_count' => $completedRegistrations,
            'completion_rate' => $completionRate,
            'progress_rate' => $progressRate
        ];

        // Add duration information
        $training->duration_days = $training->duration_days;
        $training->duration = $training->duration;
        $training->total_lesson_duration_minutes = $training->total_lesson_duration_minutes;
        $training->total_lesson_duration = $training->total_lesson_duration;

        // Add banner URL
        $training->banner_url = $training->banner_url;
        $training->banner_images = $training->banner_images;

        // Add full URLs to media files and HLS information for intro_video
        if ($training->media_files) {
            $training->media_files = collect($training->media_files)->map(function ($file) use ($training) {
                $file['url'] = url('storage/' . $file['path']);
                
                // Add HLS information for intro_video if available
                if (isset($file['type']) && $file['type'] === 'intro_video') {
                    $hlsMasterPlaylist = $file['hls_master_playlist'] ?? null;
                    $hlsVariants = $file['hls_variants'] ?? [];
                    
                    // If HLS info exists, add signed URLs for playlists
                    if ($hlsMasterPlaylist) {
                        // Generate signed URL for master playlist
                        $masterPlaylistPath = $hlsMasterPlaylist;
                        $masterPlaylistUrl = url('storage/' . $masterPlaylistPath);
                        $file['hls_master_playlist'] = $masterPlaylistPath;
                        $file['hls_master_playlist_url'] = $masterPlaylistUrl;
                        
                        // Add signed URLs for variant playlists
                        if (!empty($hlsVariants)) {
                            $file['hls_variants'] = [];
                            foreach ($hlsVariants as $quality => $variant) {
                                $variantPlaylistPath = $variant['playlist'];
                                $variantPlaylistUrl = url('storage/' . $variantPlaylistPath);
                                
                                $file['hls_variants'][$quality] = [
                                    'playlist' => $variantPlaylistPath,
                                    'playlist_url' => $variantPlaylistUrl,
                                    'bandwidth' => $variant['bandwidth'] ?? null,
                                    'resolution' => $variant['resolution'] ?? null,
                                ];
                            }
                        }
                    }
                }
                
                return $file;
            })->toArray();
        }

        // Add time information
        $training->start_time = $training->start_time;
        $training->end_time = $training->end_time;
        $training->timezone = $training->timezone;

        // Check user registration status if token is provided
        $userRegistration = null;
        if (auth()->check()) {
            $user = auth()->user();
            $userRegistration = $training->registrations()
                ->where('user_id', $user->id)
                ->first();
        }
        
        // Debug: Log authentication status
        \Log::info('Detailed endpoint debug', [
            'auth_check' => auth()->check(),
            'user_id' => auth()->check() ? auth()->user()->id : null,
            'training_id' => $training->id,
            'registrations_count' => $training->registrations()->count(),
            'token_from_header' => request()->header('Authorization'),
            'bearer_token' => request()->bearerToken()
        ]);
        

        // Add user registration status
        $training->user_registration = $userRegistration ? [
            'is_registered' => true,
            'status' => $userRegistration->status,
            'registration_date' => $userRegistration->registration_date,
            'certificate_id' => $userRegistration->certificate_id,
            'can_complete' => $userRegistration->status === 'approved'
        ] : [
            'is_registered' => false,
            'status' => null,
            'registration_date' => null,
            'certificate_id' => null,
            'can_complete' => $training->type === 'video' // Video trainings don't require registration
        ];

        // Add user progress information if authenticated
        if (auth()->check()) {
            $user = auth()->user();
            
            // Get user's last progress
            $lastProgress = \App\Models\UserTrainingProgress::with('lesson.module')
                ->where('user_id', $user->id)
                ->where('training_id', $training->id)
                ->orderBy('updated_at', 'desc')
                ->first();
            
            // Get user's progress summary
            $progressSummary = \App\Models\UserTrainingProgress::where('user_id', $user->id)
                ->where('training_id', $training->id)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();
            
            // Get next lesson to complete
            $nextLesson = null;
            if ($lastProgress && $lastProgress->status !== 'completed') {
                // Find next lesson in the same module
                $currentModule = $lastProgress->lesson->module;
                $nextLesson = $currentModule->lessons()
                    ->where('id', '>', $lastProgress->lesson->id)
                    ->orderBy('id')
                    ->first();
                
                // If no next lesson in current module, find first lesson in next module
                if (!$nextLesson) {
                    $nextModule = $training->modules()
                        ->where('id', '>', $currentModule->id)
                        ->orderBy('id')
                        ->first();
                    
                    if ($nextModule) {
                        $nextLesson = $nextModule->lessons()
                            ->orderBy('id')
                            ->first();
                    }
                }
            } else {
                // Find first incomplete lesson
                $nextLesson = $training->modules()
                    ->with(['lessons' => function($query) {
                        $query->orderBy('id');
                    }])
                    ->orderBy('id')
                    ->get()
                    ->pluck('lessons')
                    ->flatten()
                    ->first(function($lesson) use ($user, $training) {
                        $progress = \App\Models\UserTrainingProgress::where('user_id', $user->id)
                            ->where('training_id', $training->id)
                            ->where('lesson_id', $lesson->id)
                            ->first();
                        return !$progress || $progress->status !== 'completed';
                    });
            }
            
            // Check if training is completed
            $isTrainingCompleted = false;
            $completionDate = null;
            $certificateId = null;
            
            if ($userRegistration && $userRegistration->status === 'completed') {
                // For non-video trainings with registration
                $isTrainingCompleted = true;
                $completionDate = $userRegistration->updated_at;
                $certificateId = $userRegistration->certificate_id;
            } elseif ($training->type === 'video') {
                // For video trainings, check if user has certificate
                $certificate = \App\Models\Certificate::where('user_id', $user->id)
                    ->where('related_training_id', $training->id)
                    ->first();
                
                if ($certificate) {
                    $isTrainingCompleted = true;
                    $completionDate = $certificate->created_at;
                    $certificateId = $certificate->id;
                }
            }
            
            $training->user_progress = [
                'is_completed' => $isTrainingCompleted,
                'completion_date' => $completionDate,
                'certificate_id' => $certificateId,
                'last_lesson' => ($lastProgress && $lastProgress->lesson) ? [
                    'id' => $lastProgress->lesson->id,
                    'title' => $lastProgress->lesson->title,
                    'module_id' => $lastProgress->lesson->module->id,
                    'module_title' => $lastProgress->lesson->module->title,
                    'status' => $lastProgress->status,
                    'updated_at' => $lastProgress->updated_at
                ] : null,
                'next_lesson' => $nextLesson ? [
                    'id' => $nextLesson->id,
                    'title' => $nextLesson->title,
                    'module_id' => $nextLesson->module->id,
                    'module_title' => $nextLesson->module->title
                ] : null,
                'progress_summary' => $progressSummary,
                'total_lessons' => $training->modules->sum(function($module) {
                    return $module->lessons->count();
                }),
                'completed_lessons' => $progressSummary['completed'] ?? 0,
                'in_progress_lessons' => $progressSummary['in_progress'] ?? 0,
                'not_started_lessons' => $progressSummary['not_started'] ?? 0,
                'completion_percentage' => $training->modules->sum(function($module) {
                    return $module->lessons->count();
                }) > 0 ? round((($progressSummary['completed'] ?? 0) / $training->modules->sum(function($module) {
                    return $module->lessons->count();
                })) * 100, 2) : 0
            ];
                  } else {
              $training->user_progress = null;
          }

          // Add training rating information
          $training->rating = [
              'average_rating' => $training->average_rating,
              'ratings_count' => $training->ratings_count,
          ];

          // Add user's rating if authenticated
          if (auth()->check()) {
              $user = auth()->user();
              $userRating = \App\Models\TrainingRating::where('user_id', $user->id)
                  ->where('training_id', $training->id)
                  ->first();
              
              $training->user_rating = $userRating ? [
                  'rating' => $userRating->rating,
                  'created_at' => $userRating->created_at,
                  'updated_at' => $userRating->updated_at,
              ] : null;
          } else {
              $training->user_rating = null;
          }

          // Add trainer rating information if trainer exists
          if ($training->trainer) {
              $training->trainer->trainer_average_rating = $training->trainer->trainer_average_rating;
              $training->trainer->trainer_ratings_count = $training->trainer->trainer_ratings_count;
          }

          return response()->json($training);
      }

    /**
     * Get future trainings (trainings that haven't started yet)
     * GET /api/v1/trainings/future
     */
    public function future(Request $request)
    {
        $query = Training::with(['modules.lessons', 'trainer'])
            ->withCount(['registrations'])
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '>', now());
            });

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
        $sortBy = $request->get('sort_by', 'start_date');
        $sortOrder = $request->get('sort_order', 'asc');
        
        if (in_array($sortBy, ['title', 'created_at', 'start_date', 'end_date', 'difficulty'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = min($request->get('per_page', 15), 100);
        $trainings = $query->paginate($perPage);

        // Add basic statistics for each training
        $trainings->getCollection()->transform(function ($training) {
            // Calculate registration statistics
            $participantMetrics = $this->calculateParticipantMetrics($training);
            $totalParticipants = $participantMetrics['participants_count'];
            $completedLessonParticipants = $participantMetrics['completed_lesson_participants'];

            $totalRegistrations = max($training->registrations_count, $totalParticipants);
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

            // Add statistics to training object
            $training->statistics = [
                'total_registrations' => $totalRegistrations,
                'participants_count' => $totalParticipants,
                'completed_lessons_participants' => $completedLessonParticipants,
                'started_count' => $startedRegistrations,
                'completed_count' => $completedRegistrations,
                'completion_rate' => $completionRate,
                'progress_rate' => $progressRate
            ];

            return $training;
        });

        return $trainings;
    }

    /**
     * Get ongoing trainings (trainings that are currently running)
     * GET /api/v1/trainings/ongoing
     */
    public function ongoing(Request $request)
    {
        $query = Training::with(['modules.lessons', 'trainer'])
            ->withCount(['registrations'])
            ->where('status', 'published')
            ->where(function ($q) {
                $q->where(function ($subQ) {
                    // Training has started but not ended
                    $subQ->where('start_date', '<=', now())
                         ->where(function ($endQ) {
                             $endQ->whereNull('end_date')
                                  ->orWhere('end_date', '>=', now());
                         });
                });
            });

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
        $sortBy = $request->get('sort_by', 'start_date');
        $sortOrder = $request->get('sort_order', 'asc');
        
        if (in_array($sortBy, ['title', 'created_at', 'start_date', 'end_date', 'difficulty'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = min($request->get('per_page', 15), 100);
        $trainings = $query->paginate($perPage);

        // Add basic statistics for each training
        $trainings->getCollection()->transform(function ($training) {
            // Calculate registration statistics
            $participantMetrics = $this->calculateParticipantMetrics($training);
            $totalParticipants = $participantMetrics['participants_count'];
            $completedLessonParticipants = $participantMetrics['completed_lesson_participants'];

            $totalRegistrations = max($training->registrations_count, $totalParticipants);
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

            // Add statistics to training object
            $training->statistics = [
                'total_registrations' => $totalRegistrations,
                'participants_count' => $totalParticipants,
                'completed_lessons_participants' => $completedLessonParticipants,
                'started_count' => $startedRegistrations,
                'completed_count' => $completedRegistrations,
                'completion_rate' => $completionRate,
                'progress_rate' => $progressRate
            ];

            return $training;
        });

        return $trainings;
    }

    /**
     * Mark training as completed by user
     * POST /api/v1/trainings/{training}/complete
     */
    public function markTrainingCompleted(Request $request, Training $training)
    {
        $user = auth()->user();
        
        // For video trainings, registration is not required
        if ($training->type !== 'video') {
            // Check if user is registered for non-video trainings
            $registration = $training->registrations()
                ->where('user_id', $user->id)
                ->where('status', 'approved')
                ->first();

            if (!$registration) {
                return response()->json(['message' => 'Access denied. Please register for this training.'], 403);
            }
        } else {
            // For video trainings, create a virtual registration or skip registration check
            $registration = null;
        }

        // Check if training is already completed (only for non-video trainings with registration)
        if ($registration && $registration->status === 'completed') {
            return response()->json([
                'message' => 'Training already completed',
                'registration' => $registration,
                'certificate_id' => $registration->certificate_id
            ]);
        }

        // For video trainings, skip lesson completion check
        if ($training->type !== 'video') {
            // Get all required lessons for this training
            $requiredLessonIds = $training->modules()
                ->whereHas('lessons', function ($q) {
                    $q->where('is_required', true);
                })->get()->pluck('lessons')->flatten()->pluck('id')->all();

            // Check if all required lessons are completed
            $completedRequiredCount = UserTrainingProgress::where('user_id', $user->id)
                ->where('training_id', $training->id)
                ->whereIn('lesson_id', $requiredLessonIds)
                ->where('status', 'completed')
                ->count();

            if (count($requiredLessonIds) > 0 && $completedRequiredCount < count($requiredLessonIds)) {
                return response()->json([
                    'message' => 'Cannot complete training. Not all required lessons are completed.',
                    'completed_lessons' => $completedRequiredCount,
                    'required_lessons' => count($requiredLessonIds),
                    'remaining_lessons' => count($requiredLessonIds) - $completedRequiredCount
                ], 422);
            }
        }

        $issuedCertificate = null;

        // Create certificate if training has certificate
        $certificate = null;
        if ($training->has_certificate) {
            // For video trainings, create certificate without registration
            if ($training->type === 'video') {
                // Check if certificate already exists
                $existingCert = Certificate::where('user_id', $user->id)
                    ->where('related_training_id', $training->id)
                    ->first();

                if (!$existingCert) {
                    $certificate = Certificate::create([
                        'user_id' => $user->id,
                        'related_training_id' => $training->id,
                        'related_exam_id' => null,
                        'certificate_number' => Str::uuid()->toString(),
                        'issue_date' => now()->toDateString(),
                        'issuer_name' => 'Aqrar Portal',
                        'status' => 'active',
                    ]);

                    $this->autoGenerateCertificatePdf($certificate, $training);
                    $issuedCertificate = $certificate;
                } else {
                    if (!$existingCert->pdf_path) {
                        $this->autoGenerateCertificatePdf($existingCert, $training);
                    }
                    $issuedCertificate = $existingCert;
                }
            } else {
                // For non-video trainings, attach certificate to registration
                if ($registration && !$registration->certificate_id) {
                    $certificate = Certificate::create([
                        'user_id' => $user->id,
                        'related_training_id' => $training->id,
                        'related_exam_id' => null,
                        'certificate_number' => Str::uuid()->toString(),
                        'issue_date' => now()->toDateString(),
                        'issuer_name' => 'Aqrar Portal',
                        'status' => 'active',
                    ]);

                    if ($registration->certificate_id !== $certificate->id) {
                        $registration->certificate_id = $certificate->id;
                    }
                    if ($registration->status !== 'completed') {
                        $registration->status = 'completed';
                    }
                    $registration->save();

                    $this->autoGenerateCertificatePdf($certificate, $training);
                    $issuedCertificate = $certificate;
                } elseif ($registration && $registration->status !== 'completed') {
                    $registration->status = 'completed';
                    $registration->save();
                }
            }
        } else {
            // Mark as completed without certificate (only for non-video trainings)
            if ($registration) {
                if ($registration->status !== 'completed') {
                    $registration->status = 'completed';
                    $registration->save();
                }
            }
        }

        if (!$issuedCertificate && isset($certificate)) {
            $issuedCertificate = $certificate;
        }

        if ($issuedCertificate && $training->has_certificate && !$issuedCertificate->pdf_path && !$training->exam_for_certificate) {
            if ($this->autoGenerateCertificatePdf($issuedCertificate, $training)) {
                $issuedCertificate->refresh();
            }
        }

        if ($issuedCertificate) {
            $issuedCertificate->refresh();
        }

        $this->sendTrainingCompletionEmail($user, $training, $issuedCertificate);

        // Get exam information if exists
        $examInfo = null;
        if ($training->exam_id) {
            // Load exam relationship if not already loaded
            $training->load('exam');
            $exam = $training->exam;
            if ($exam) {
                $examInfo = [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'description' => $exam->description,
                    'duration_minutes' => $exam->duration_minutes,
                    'passing_score' => $exam->passing_score,
                    'is_required' => $training->exam_required,
                    'min_score' => $training->min_exam_score
                ];
            }
        }

        return response()->json([
            'message' => 'Training completed successfully',
            'registration' => $registration ? $registration->fresh() : null,
            'certificate' => $this->formatCertificateResponse($issuedCertificate),
            'completion_date' => now()->toISOString(),
            'exam' => $examInfo
        ]);
    }

    /**
     * Get training completion status for user
     * GET /api/v1/trainings/{training}/completion-status
     */
    public function getTrainingCompletionStatus(Training $training)
    {
        $user = auth()->user();
        
        // Check if user is registered for the training
        $registration = $training->registrations()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->first();

        if (!$registration) {
            return response()->json(['message' => 'Access denied. Please register for this training.'], 403);
        }

        // Get all required lessons for this training
        $requiredLessonIds = $training->modules()
            ->whereHas('lessons', function ($q) {
                $q->where('is_required', true);
            })->get()->pluck('lessons')->flatten()->pluck('id')->all();

        // Get completed required lessons
        $completedRequiredCount = UserTrainingProgress::where('user_id', $user->id)
            ->where('training_id', $training->id)
            ->whereIn('lesson_id', $requiredLessonIds)
            ->where('status', 'completed')
            ->count();

        // Get all lessons (required + optional)
        $allLessonIds = $training->modules()
            ->get()->pluck('lessons')->flatten()->pluck('id')->all();

        $completedAllCount = UserTrainingProgress::where('user_id', $user->id)
            ->where('training_id', $training->id)
            ->whereIn('lesson_id', $allLessonIds)
            ->where('status', 'completed')
            ->count();

        $isCompleted = $registration->status === 'completed';
        $canComplete = count($requiredLessonIds) > 0 && $completedRequiredCount >= count($requiredLessonIds);

        return response()->json([
            'training_id' => $training->id,
            'training_title' => $training->title,
            'is_completed' => $isCompleted,
            'can_complete' => $canComplete,
            'registration_status' => $registration->status,
            'certificate_id' => $registration->certificate_id,
            'progress' => [
                'completed_required_lessons' => $completedRequiredCount,
                'total_required_lessons' => count($requiredLessonIds),
                'completed_all_lessons' => $completedAllCount,
                'total_lessons' => count($allLessonIds),
                'completion_percentage' => count($requiredLessonIds) > 0 
                    ? round(($completedRequiredCount / count($requiredLessonIds)) * 100, 2) 
                    : 0
            ]
        ]);
    }

    /**
     * Get online trainings (type = 'online')
     * GET /api/v1/trainings/online
     */
    public function online(Request $request)
    {
        $query = Training::with(['modules.lessons', 'trainer'])
            ->withCount(['registrations'])
            ->where('status', 'published')
            ->where('type', 'online');

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
            $participantMetrics = $this->calculateParticipantMetrics($training);
            $totalParticipants = $participantMetrics['participants_count'];
            $completedLessonParticipants = $participantMetrics['completed_lesson_participants'];

            $totalRegistrations = max($training->registrations_count, $totalParticipants);
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
                'participants_count' => $totalParticipants,
                'completed_lessons_participants' => $completedLessonParticipants,
                'started_count' => $startedRegistrations,
                'completed_count' => $completedRegistrations,
                'completion_rate' => $completionRate,
                'progress_rate' => $progressRate
            ];

            $training->media_statistics = $mediaStats;

            // Add banner URL
            $training->banner_url = $training->banner_url;
            $training->banner_images = $training->banner_images;

            // Add duration information
            $training->duration_days = $training->duration_days;
            $training->duration = $training->duration;
            $training->total_lesson_duration_minutes = $training->total_lesson_duration_minutes;
            $training->total_lesson_duration = $training->total_lesson_duration;

            // Add time information
            $training->start_time = $training->start_time;
            $training->end_time = $training->end_time;
            $training->timezone = $training->timezone;

            return $training;
        });

        return $trainings;
    }

    /**
     * Get offline trainings (type = 'offline')
     * GET /api/v1/trainings/offline
     */
    public function offline(Request $request)
    {
        $query = Training::with(['modules.lessons', 'trainer', 'registrations'])
            ->withCount(['registrations'])
            ->where('status', 'published')
            ->where('type', 'offline');

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

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        if (in_array($sortBy, ['title', 'created_at', 'start_date', 'end_date', 'difficulty'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Get all offline trainings without pagination
        $trainings = $query->get();

        // Add comprehensive details for each training
        $trainings->transform(function ($training) {
            // Calculate registration statistics
            $participantMetrics = $this->calculateParticipantMetrics($training);
            $totalParticipants = $participantMetrics['participants_count'];
            $completedLessonParticipants = $participantMetrics['completed_lesson_participants'];

            $totalRegistrations = max($training->registrations_count, $totalParticipants);
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
                'participants_count' => $totalParticipants,
                'completed_lessons_participants' => $completedLessonParticipants,
                'started_count' => $startedRegistrations,
                'completed_count' => $completedRegistrations,
                'completion_rate' => $completionRate,
                'progress_rate' => $progressRate
            ];

            $training->media_statistics = $mediaStats;

            // Add banner URL
            $training->banner_url = $training->banner_url;
            $training->banner_images = $training->banner_images;

            // Add duration information
            $training->duration_days = $training->duration_days;
            $training->duration = $training->duration;
            $training->total_lesson_duration_minutes = $training->total_lesson_duration_minutes;
            $training->total_lesson_duration = $training->total_lesson_duration;

            // Add time information
            $training->start_time = $training->start_time;
            $training->end_time = $training->end_time;
            $training->timezone = $training->timezone;

            // Add offline specific details
            $training->offline_details = $training->offline_details;
            $training->address = $training->offline_details['address'] ?? null;
            $training->coordinates = $training->offline_details['coordinates'] ?? null;
            $training->participant_size = $training->offline_details['participant_size'] ?? null;

            // Add trainer details
            $training->trainer_name = $training->trainer ? 
                $training->trainer->first_name . ' ' . $training->trainer->last_name : null;
            $training->trainer_email = $training->trainer ? $training->trainer->email : null;
            $training->trainer_phone = $training->trainer ? $training->trainer->phone : null;

            // Add exam information if exists
            if ($training->exam) {
                $training->exam_details = [
                    'id' => $training->exam->id,
                    'title' => $training->exam->title,
                    'description' => $training->exam->description,
                    'duration_minutes' => $training->exam->duration_minutes,
                    'total_questions' => $training->exam->questions_count ?? 0,
                    'passing_score' => $training->exam->passing_score,
                    'is_required' => $training->exam_required,
                    'min_score' => $training->min_exam_score
                ];
            } else {
                $training->exam_details = null;
            }

            // Add certificate information
            $training->certificate_info = [
                'has_certificate' => $training->has_certificate,
                'require_email_verification' => $training->require_email_verification
            ];

            // Add full URLs to media files
            if ($training->media_files) {
                $training->media_files = collect($training->media_files)->map(function ($file) {
                    $file['url'] = url('storage/' . $file['path']);
                    return $file;
                })->toArray();
            }

            // Add registration details for current user if authenticated
            if (auth()->check()) {
                $user = auth()->user();
                $userRegistration = $training->registrations()
                    ->where('user_id', $user->id)
                    ->first();
                
                $training->user_registration = $userRegistration ? [
                    'is_registered' => true,
                    'status' => $userRegistration->status,
                    'registration_date' => $userRegistration->registration_date,
                    'certificate_id' => $userRegistration->certificate_id,
                    'can_complete' => $userRegistration->status === 'approved'
                ] : [
                    'is_registered' => false,
                    'status' => null,
                    'registration_date' => null,
                    'certificate_id' => null,
                    'can_complete' => false
                ];
            } else {
                $training->user_registration = [
                    'is_registered' => false,
                    'status' => null,
                    'registration_date' => null,
                    'certificate_id' => null,
                    'can_complete' => false
                ];
            }

            return $training;
        });

        return response()->json([
            'data' => $trainings,
            'total' => $trainings->count()
        ]);
    }

    /**
     * Get detailed offline training information
     * GET /api/v1/trainings/offline/{training}
     */
    public function offlineDetail(Training $training)
    {
        // Check if training is offline type
        if ($training->type !== 'offline') {
            return response()->json(['message' => 'Training is not offline type'], 404);
        }

        // Only show published trainings
        if ($training->status !== 'published') {
            return response()->json(['message' => 'Training not found'], 404);
        }

        // Load all related data
        $training->load(['modules.lessons', 'trainer', 'registrations', 'exam']);
        
        // Get authenticated user (if any)
        $userId = auth()->check() ? auth()->user()->id : null;
        
        // Transform lesson media URLs to signed URLs (temporary URLs that expire)
        foreach ($training->modules as $module) {
            foreach ($module->lessons as $lesson) {
                // Get media_files (may already be transformed by accessor)
                $mediaFiles = $lesson->media_files ?? [];
                
                if ($mediaFiles && is_array($mediaFiles)) {
                    $lesson->media_files = collect($mediaFiles)->map(function ($mediaFile) use ($module, $lesson, $userId) {
                        // Extract path from URL if path field doesn't exist
                        $filePath = $mediaFile['path'] ?? null;
                        
                        if (!$filePath && isset($mediaFile['url'])) {
                            // Extract path from protected endpoint URL
                            // URL format: /api/v1/modules/1/lessons/2/media/download?path=lessons%2F2%2Fvideo.mp4
                            $url = $mediaFile['url'];
                            if (preg_match('/[?&]path=([^&]+)/', $url, $matches)) {
                                $filePath = urldecode($matches[1]);
                            } elseif (preg_match('/\/lessons\/(\d+)\/([^\/]+)$/', $url, $matches)) {
                                // Fallback: extract from URL path
                                $filePath = 'lessons/' . $matches[1] . '/' . $matches[2];
                            }
                        }
                        
                        // Generate signed URL for all media files (videos, images, PDFs, etc.)
                        if ($filePath) {
                            try {
                                // Generate temporary signed URL (expires in 2 hours)
                                // Use null userId so signed URL works in browser without authentication
                                $signedUrl = \App\Http\Controllers\LessonMediaController::generateSignedUrl(
                                    $module,
                                    $lesson,
                                    $filePath,
                                    null, // Always use null for browser compatibility
                                    120 // 2 hours expiration
                                );
                                
                                // Add signed URL and expiration info
                                $mediaFile['signed_url'] = $signedUrl;
                                $mediaFile['signed_url_expires_at'] = now()->addHours(2)->toIso8601String();
                                
                                // Keep path field for reference
                                if (!isset($mediaFile['path'])) {
                                    $mediaFile['path'] = $filePath;
                                }
                                
                                // Keep original protected URL as fallback
                                if (!isset($mediaFile['url'])) {
                                    $mediaFile['url'] = route('lesson.media.download', [
                                        'module' => $module->id,
                                        'lesson' => $lesson->id,
                                        'path' => $filePath
                                    ]);
                                }
                            } catch (\Exception $e) {
                                // Log error but don't fail
                                \Log::error('Failed to generate signed URL', [
                                    'error' => $e->getMessage(),
                                    'module_id' => $module->id,
                                    'lesson_id' => $lesson->id,
                                    'file_path' => $filePath
                                ]);
                                
                                // Fallback to regular protected URL transformation
                                if (isset($mediaFile['path']) && !isset($mediaFile['url'])) {
                                    $mediaFile['url'] = route('lesson.media.download', [
                                        'module' => $module->id,
                                        'lesson' => $lesson->id,
                                        'path' => $mediaFile['path']
                                    ]);
                                } else {
                                    // Transform existing URLs if needed
                                    $mediaFile = $lesson->transformMediaUrls([$mediaFile])[0] ?? $mediaFile;
                                }
                            }
                        } else {
                            // If no path available, use regular protected URL transformation
                            if (isset($mediaFile['path']) && !isset($mediaFile['url'])) {
                                $mediaFile['url'] = route('lesson.media.download', [
                                    'module' => $module->id,
                                    'lesson' => $lesson->id,
                                    'path' => $mediaFile['path']
                                ]);
                            } else {
                                // Transform existing URLs if needed
                                $mediaFile = $lesson->transformMediaUrls([$mediaFile])[0] ?? $mediaFile;
                            }
                        }
                        
                        return $mediaFile;
                    })->toArray();
                }
            }
        }

        // Calculate comprehensive statistics
        $participantMetrics = $this->calculateParticipantMetrics($training);
        $totalParticipants = $participantMetrics['participants_count'];
        $completedLessonParticipants = $participantMetrics['completed_lesson_participants'];

        $totalRegistrations = max($training->registrations()->count(), $totalParticipants);
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

        $completionRate = $totalRegistrations > 0 ? round(($completedRegistrations / $totalRegistrations) * 100, 2) : 0;
        $progressRate = $totalRegistrations > 0 ? round((($completedRegistrations + $startedRegistrations) / $totalRegistrations) * 100, 2) : 0;

        // Count media files by type (training + modules + lessons)
        $trainingMediaFiles = $training->media_files ?? [];
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
            'participants_count' => $totalParticipants,
            'completed_lessons_participants' => $completedLessonParticipants,
            'started_count' => $startedRegistrations,
            'completed_count' => $completedRegistrations,
            'completion_rate' => $completionRate,
            'progress_rate' => $progressRate
        ];

        $training->media_statistics = $mediaStats;

        // Add banner URL
        $training->banner_url = $training->banner_url;
        $training->banner_images = $training->banner_images;

        // Add duration information
        $training->duration_days = $training->duration_days;
        $training->duration = $training->duration;
        $training->total_lesson_duration_minutes = $training->total_lesson_duration_minutes;
        $training->total_lesson_duration = $training->total_lesson_duration;

        // Add time information
        $training->start_time = $training->start_time;
        $training->end_time = $training->end_time;
        $training->timezone = $training->timezone;

        // Add offline specific details
        $training->offline_details = $training->offline_details;
        $training->address = $training->offline_details['address'] ?? null;
        $training->coordinates = $training->offline_details['coordinates'] ?? null;
        $training->participant_size = $training->offline_details['participant_size'] ?? null;

        // Add trainer details
        $training->trainer_name = $training->trainer ? 
            $training->trainer->first_name . ' ' . $training->trainer->last_name : null;
        $training->trainer_email = $training->trainer ? $training->trainer->email : null;
        $training->trainer_phone = $training->trainer ? $training->trainer->phone : null;

        // Add exam information if exists
        if ($training->exam) {
            $training->exam_details = [
                'id' => $training->exam->id,
                'title' => $training->exam->title,
                'description' => $training->exam->description,
                'duration_minutes' => $training->exam->duration_minutes,
                'total_questions' => $training->exam->questions_count ?? 0,
                'passing_score' => $training->exam->passing_score,
                'is_required' => $training->exam_required,
                'min_score' => $training->min_exam_score
            ];
        } else {
            $training->exam_details = null;
        }

        // Add certificate information
        $training->certificate_info = [
            'has_certificate' => $training->has_certificate,
            'require_email_verification' => $training->require_email_verification
        ];

        // Add full URLs to media files
        if ($training->media_files) {
            $training->media_files = collect($training->media_files)->map(function ($file) {
                $file['url'] = url('storage/' . $file['path']);
                return $file;
            })->toArray();
        }

        // Add registration details for current user if authenticated
        if (auth()->check()) {
            $user = auth()->user();
            $userRegistration = $training->registrations()
                ->where('user_id', $user->id)
                ->first();
            
            // Debug log
            \Log::info('OfflineDetail Debug', [
                'training_id' => $training->id,
                'user_id' => $user->id,
                'auth_check' => auth()->check(),
                'registration_found' => $userRegistration ? true : false,
                'registration_status' => $userRegistration ? $userRegistration->status : null
            ]);
            
            // Force debug output
            error_log("DEBUG: Training ID: " . $training->id . ", User ID: " . $user->id . ", Registration Found: " . ($userRegistration ? 'YES' : 'NO'));
            
            $training->user_registration = $userRegistration ? [
                'is_registered' => true,
                'status' => $userRegistration->status,
                'registration_date' => $userRegistration->registration_date,
                'certificate_id' => $userRegistration->certificate_id,
                'can_complete' => $userRegistration->status === 'approved'
            ] : [
                'is_registered' => false,
                'status' => null,
                'registration_date' => null,
                'certificate_id' => null,
                'can_complete' => false
            ];
        } else {
            $training->user_registration = [
                'is_registered' => false,
                'status' => null,
                'registration_date' => null,
                'certificate_id' => null,
                'can_complete' => false
            ];
        }

        // Add detailed module and lesson information
        $training->detailed_modules = $training->modules->map(function ($module) {
            return [
                'id' => $module->id,
                'title' => $module->title,
                'description' => $module->description,
                'order' => $module->order,
                'lessons' => $module->lessons->map(function ($lesson) {
                    return [
                        'id' => $lesson->id,
                        'title' => $lesson->title,
                        'description' => $lesson->description,
                        'content' => $lesson->content,
                        'video_url' => $lesson->video_url,
                        'pdf_url' => $lesson->pdf_url,
                        'duration_minutes' => $lesson->duration_minutes,
                        'is_required' => $lesson->is_required,
                        'order' => $lesson->order,
                        'media_files' => $lesson->media_files ?? []
                    ];
                })
            ];
        });

        return response()->json($training);
    }

    /**
     * Send training notifications to all users
     */
    private function sendTrainingNotifications($training, $action = 'created', $googleMeetLink = null)
    {
        try {
            // Get all users with valid email addresses
            $users = User::where('email', '!=', null)
                ->where('email', '!=', '')
                ->where('email_verified', true)
                ->get(['id', 'first_name', 'last_name', 'email']);

            $sentCount = 0;
            $failedCount = 0;
            $notificationService = app(NotificationService::class);
            
            // Get localized training title
            $trainingTitle = is_array($training->title) 
                ? ($training->title['az'] ?? reset($training->title))
                : $training->title;
            
            $title = match ($action) {
                'updated' => 'Təlim yeniləndi',
                'deleted', 'cancelled' => 'Təlim ləğv edildi',
                default => 'Yeni təlim əlavə olundu',
            };

            $message = match ($action) {
                'updated' => "{$trainingTitle} təlimində dəyişiklik edildi.",
                'deleted', 'cancelled' => "{$trainingTitle} təlimi ləğv olundu.",
                default => "{$trainingTitle} adlı yeni təlim əlavə olundu.",
            };

            if ($googleMeetLink) {
                $message .= " Google Meet keçidi: {$googleMeetLink}";
            }

            foreach ($users as $user) {
                try {
                    $notificationService->send(
                        $user,
                        'training',
                        ['az' => $title],
                        ['az' => $message],
                        [
                            'data' => [
                                'training_id' => $training->id,
                                'action' => $action,
                                'google_meet_link' => $googleMeetLink,
                            ],
                            'mail' => new TrainingNotification($training, $user, $action, $googleMeetLink),
                        ]
                    );
                    $sentCount++;
                } catch (\Throwable $e) {
                    $failedCount++;
                    \Log::error('Failed to send training notification email', [
                        'email' => $user->email,
                        'training_id' => $training->id,
                        'action' => $action,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            \Log::info('Training notification emails sent', [
                'training_id' => $training->id,
                'action' => $action,
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
                'total_users' => $users->count()
            ]);

            return [
                'sent' => $sentCount,
                'failed' => $failedCount,
                'total' => $users->count()
            ];

        } catch (\Exception $e) {
            \Log::error('Failed to send training notifications', [
                'training_id' => $training->id,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
            
            return [
                'sent' => 0,
                'failed' => 0,
                'total' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Convert attendees specifically
     */
    private function convertAttendees($value)
    {
        $attendees = $this->convertToArray($value);
        
        // Filter out invalid attendees
        return array_filter($attendees, function($attendee) {
            return is_array($attendee) && 
                   isset($attendee['email']) && 
                   !empty($attendee['email']) && 
                   filter_var($attendee['email'], FILTER_VALIDATE_EMAIL);
        });
    }

    /**
     * Convert string to array
     */
    private function convertToArray($value)
    {
        if (is_array($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            // Try to decode JSON string
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
            
            // If it's not valid JSON, return empty array
            return [];
        }
        
        if (is_null($value)) {
            return [];
        }
        
        // For any other type, return empty array
        return [];
    }

    /**
     * Create recurring meetings for training
     */
    private function createRecurringMeetings($meetingData, $validated, $user, $googleService = null)
    {
        $recurringMeetings = [];
        $startDate = \Carbon\Carbon::parse($validated['start_date']);
        $endDate = \Carbon\Carbon::parse($validated['recurrence_end_date']);
        $frequency = $validated['recurrence_frequency'];
        
        // Get Google service if not provided
        if ($googleService === null) {
            $googleService = $this->getGoogleCalendarService();
        }
        
        if (!$googleService) {
            return $recurringMeetings;
        }
        
        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($endDate)) {
            // Create meeting data for this specific date
            $dayMeetingData = $meetingData;
            $dayMeetingData['start_time'] = $currentDate->format('Y-m-d') . ' ' . 
                \Carbon\Carbon::parse($validated['meeting_start_time'])->format('H:i:s');
            $dayMeetingData['end_time'] = $currentDate->format('Y-m-d') . ' ' . 
                \Carbon\Carbon::parse($validated['meeting_end_time'])->format('H:i:s');
            
            // Create meeting in Google Calendar
            $googleResult = $googleService->createMeeting($dayMeetingData);
            
            if ($googleResult['success']) {
                $recurringMeetings[] = [
                    'date' => $currentDate->format('Y-m-d'),
                    'meet_link' => $googleResult['meet_link'],
                    'event_id' => $googleResult['event_id'],
                    'meeting_id' => $googleResult['meeting_id'],
                ];
            }
            
            // Move to next date based on frequency
            switch ($frequency) {
                case 'daily':
                    $currentDate->addDay();
                    break;
                case 'weekly':
                    $currentDate->addWeek();
                    break;
                case 'monthly':
                    $currentDate->addMonth();
                    break;
            }
        }
        
        return $recurringMeetings;
    }

    /**
     * Get all trainings without pagination
     */
    public function getAll(Request $request)
    {
        $trainings = Training::with(['trainer', 'modules.lessons'])
            ->withCount(['registrations'])
            ->orderBy('created_at', 'desc')
            ->get();

        $trainings->transform(function ($training) {
            $participantMetrics = $this->calculateParticipantMetrics($training);
            $training->participants_count = $participantMetrics['participants_count'];
            $training->completed_lessons_participants = $participantMetrics['completed_lesson_participants'];

            $training->media_statistics = $this->calculateMediaStatistics($training);
            return $training;
        });

        return response()->json([
            'trainings' => $trainings,
            'total_count' => $trainings->count()
        ]);
    }

    private function autoGenerateCertificatePdf(Certificate $certificate, Training $training): bool
    {
        if ($certificate->pdf_path) {
            return true;
        }

        try {
            $user = $certificate->relationLoaded('user') ? $certificate->user : $certificate->user()->first();
            if (!$user) {
                $user = User::find($certificate->user_id);
            }

            if (!$user) {
                \Log::warning('Cannot generate certificate PDF: user not found', [
                    'certificate_id' => $certificate->id,
                    'user_id' => $certificate->user_id,
                ]);
                return false;
            }

            $exam = null;
            if ($certificate->related_exam_id) {
                $exam = Exam::find($certificate->related_exam_id);
            } elseif ($training->relationLoaded('exam') && $training->exam) {
                $exam = $training->exam;
            } elseif ($training->exam_id) {
                $exam = Exam::find($training->exam_id);
            }

            $trainingTitle = $this->getLocalizedValue($training->title);
            $trainingDescription = $this->getLocalizedValue($training->description);
            $trainingCertificateDescription = $this->extractCertificateDescription($training);

            $userData = [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'username' => $user->username ?? null,
            ];

            $examData = $exam ? [
                'id' => $exam->id,
                'title' => $this->getLocalizedValue($exam->title ?? null, $trainingTitle),
                'description' => $this->getLocalizedValue($exam->description ?? null),
                'sertifikat_description' => $exam->sertifikat_description ?? null,
            ] : [
                'id' => $training->id,
                'title' => $trainingTitle,
                'description' => $trainingDescription,
                'sertifikat_description' => $trainingCertificateDescription,
            ];

            $trainingData = [
                'id' => $training->id,
                'title' => $trainingTitle,
                'description' => $trainingDescription,
                'certificate_description' => $training->certificate_description ?? null,
            ];

            /** @var CertificateGeneratorService $service */
            $service = app(CertificateGeneratorService::class);
            $result = $service->generateCertificate($userData, $examData, $trainingData);

            if (!($result['success'] ?? false)) {
                \Log::warning('Certificate PDF generation failed', [
                    'certificate_id' => $certificate->id,
                    'training_id' => $training->id,
                    'error' => $result['error'] ?? null,
                ]);
                return false;
            }

            $updateData = [
                'pdf_path' => $result['pdf_path'] ?? $certificate->pdf_path,
                'pdf_url' => isset($result['pdf_path']) ? url('/storage/' . $result['pdf_path']) : $certificate->pdf_url,
            ];

            if (!empty($result['digital_signature'])) {
                $updateData['digital_signature'] = $result['digital_signature'];
            }

            if (!empty($result['certificate_number'])) {
                $updateData['certificate_number'] = $result['certificate_number'];
            }

            $certificate->update($updateData);

            if (!$certificate->qr_code_path) {
                $certificate->generateAndSaveQrCode();
            }

            return true;
        } catch (\Throwable $e) {
            \Log::error('Failed to auto generate certificate PDF', [
                'certificate_id' => $certificate->id,
                'training_id' => $training->id,
                'message' => $e->getMessage(),
            ]);
        }

        return false;
    }

    private function getLocalizedValue($value, $fallback = ''): string
    {
        if (is_array($value)) {
            if (!empty($value['az'])) {
                return trim((string) $value['az']);
            }

            foreach ($value as $v) {
                if (!empty($v)) {
                    return trim((string) $v);
                }
            }
        } elseif (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        if (is_string($fallback) && trim($fallback) !== '') {
            return trim($fallback);
        }

        return '';
    }

    private function extractCertificateDescription(Training $training)
    {
        $description = $training->certificate_description ?? null;
        if (is_array($description)) {
            return $description['az'] ?? reset($description);
        }

        return $description;
    }

    private function sendTrainingCompletionEmail(User $user, Training $training, ?Certificate $certificate = null): void
    {
        try {
            $training->loadMissing(['modules.lessons', 'trainer']);

            $modulesCount = $training->modules->count();
            $lessonsCount = $training->modules->sum(function ($module) {
                return $module->lessons->count();
            });
            $totalDurationMinutes = $training->modules->sum(function ($module) {
                return $module->lessons->sum('duration_minutes');
            });
            $completedLessonsCount = UserTrainingProgress::where('user_id', $user->id)
                ->where('training_id', $training->id)
                ->where('status', 'completed')
                ->count();

            $trainingTitle = $this->getLocalizedValue($training->title);
            $trainingDescription = $this->getLocalizedValue($training->description);
            $categoryName = $this->getLocalizedValue($training->category);
            $trainerName = $training->trainer ? trim(($training->trainer->first_name ?? '') . ' ' . ($training->trainer->last_name ?? '')) : null;

            if ($certificate) {
                $certificate->refresh();
            }

            $details = [
                'training_title' => $trainingTitle,
                'training_description' => $trainingDescription,
                'category' => $categoryName,
                'trainer_name' => $trainerName ?: null,
                'training_type' => $training->type,
                'modules_count' => $modulesCount,
                'lessons_count' => $lessonsCount,
                'completed_lessons_count' => $completedLessonsCount,
                'total_duration_minutes' => $totalDurationMinutes,
                'has_certificate' => (bool) $training->has_certificate,
                'certificate_requires_exam' => (bool) ($training->exam_for_certificate || $training->exam_required),
                'certificate_available' => $certificate && $certificate->pdf_path,
                'certificate_download_url' => ($certificate && $certificate->pdf_path) ? url("/api/v1/certificates/{$certificate->id}/download") : null,
                'certificate_preview_url' => ($certificate && $certificate->pdf_path) ? url("/api/v1/certificates/{$certificate->id}/preview") : null,
                'certificate_number' => $certificate?->certificate_number,
                'certificate_id' => $certificate?->id,
                'has_exam' => (bool) $training->has_exam,
                'exam_required' => (bool) $training->exam_required,
            ];

            app(NotificationService::class)->send(
                $user,
                'training',
                ['az' => "{$trainingTitle} tamamlandı"],
                ['az' => "{$trainingTitle} təlimini uğurla tamamladınız."],
                [
                    'data' => [
                        'training_id' => $training->id,
                        'certificate_id' => $certificate?->id,
                        'type' => 'completion',
                    ],
                    'mail' => new TrainingCompletionNotification($user, $training, $details),
                ]
            );
        } catch (\Throwable $e) {
            \Log::error('Failed to send training completion email', [
                'user_id' => $user->id,
                'training_id' => $training->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function formatCertificateResponse(?Certificate $certificate): ?array
    {
        if (!$certificate) {
            return null;
        }

        $certificate->refresh();

        $data = $certificate->toArray();
        $downloadUrl = url("/api/v1/certificates/{$certificate->id}/download");
        $previewUrl = url("/api/v1/certificates/{$certificate->id}/preview");

        $data['pdf_url'] = $certificate->pdf_url;
        $data['download_url'] = $certificate->pdf_path ? $downloadUrl : null;
        $data['preview_url'] = $certificate->pdf_path ? $previewUrl : null;

        return $data;
    }

    /**
     * Normalize translation request data
     * Converts format like {title_az: "...", title_en: "..."} to {title: {az: "...", en: "..."}}
     */
    private function normalizeTranslationRequest(array $data): array
    {
        $translatableFields = ['title', 'description'];
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

    /**
     * Calculate participant metrics for a training, including lesson completions.
     */
    private function calculateParticipantMetrics(Training $training): array
    {
        $registrationUserIds = $training->registrations()->pluck('user_id');

        $progressUserIds = UserTrainingProgress::where('training_id', $training->id)
            ->where('status', 'completed')
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        $certificateUserIds = Certificate::where('related_training_id', $training->id)
            ->pluck('user_id');

        $participantUserIds = collect()
            ->merge($registrationUserIds)
            ->merge($progressUserIds)
            ->merge($certificateUserIds)
            ->filter()
            ->unique()
            ->values();

        return [
            'participants_count' => $participantUserIds->count(),
            'completed_lesson_participants' => $progressUserIds->unique()->count(),
        ];
    }

    /**
     * Calculate aggregate media statistics for a training, including lesson media.
     */
    private function calculateMediaStatistics(Training $training): array
    {
        $mediaCounts = [
            'videos' => 0,
            'documents' => 0,
            'images' => 0,
            'audio' => 0,
        ];

        $trainingMediaFiles = $training->media_files ?? [];
        $trainingCounts = $this->countMediaFilesByType($trainingMediaFiles);
        $mediaCounts = $this->mergeMediaCounts($mediaCounts, $trainingCounts);

        $modules = $training->relationLoaded('modules') ? $training->modules : collect();
        $lessonsCount = 0;

        foreach ($modules as $module) {
            $lessons = $module->relationLoaded('lessons') ? $module->lessons : collect();
            $lessonsCount += $lessons->count();

            foreach ($lessons as $lesson) {
                if (!empty($lesson->video_url)) {
                    $mediaCounts['videos']++;
                }
                if (!empty($lesson->pdf_url)) {
                    $mediaCounts['documents']++;
                }

                $lessonMediaFiles = $lesson->media_files ?? [];
                if (!empty($lessonMediaFiles)) {
                    $lessonCounts = $this->countMediaFilesByType($lessonMediaFiles);
                    $mediaCounts = $this->mergeMediaCounts($mediaCounts, $lessonCounts);
                }
            }
        }

        return [
            'videos_count' => $mediaCounts['videos'],
            'documents_count' => $mediaCounts['documents'],
            'images_count' => $mediaCounts['images'],
            'audio_count' => $mediaCounts['audio'],
            'total_media' => array_sum($mediaCounts),
            'training_media_count' => count($trainingMediaFiles),
            'modules_count' => $modules->count(),
            'lessons_count' => $lessonsCount,
        ];
    }

    /**
     * Merge media counters.
     */
    private function mergeMediaCounts(array $base, array $additional): array
    {
        foreach ($additional as $key => $value) {
            if (isset($base[$key])) {
                $base[$key] += $value;
            }
        }

        return $base;
    }

    /**
     * Helper method to count media files by type
     * 
     * @param array $mediaFiles
     * @return array
     */
    private function countMediaFilesByType(array $mediaFiles): array
    {
        $counts = [
            'videos' => 0,
            'documents' => 0,
            'images' => 0,
            'audio' => 0
        ];

        foreach ($mediaFiles as $file) {
            $mimeType = $file['mime_type'] ?? '';
            $fileType = $file['type'] ?? '';

            if ($fileType === 'intro_video' || $fileType === 'video' || str_contains($mimeType, 'video')) {
                $counts['videos']++;
            } elseif ($fileType === 'document' || str_contains($mimeType, 'pdf') || str_contains($mimeType, 'doc')) {
                $counts['documents']++;
            } elseif ($fileType === 'banner' || $fileType === 'image' || str_contains($mimeType, 'image')) {
                $counts['images']++;
            } elseif ($fileType === 'audio' || str_contains($mimeType, 'audio')) {
                $counts['audio']++;
            } else {
                $counts['documents']++;
            }
        }

        return $counts;
    }

    /**
     * Detect HLS files dynamically from disk storage
     * Checks if HLS files exist even if not saved in database
     * 
     * @param int $lessonId
     * @param string $videoPath Original video path
     * @return array|null Returns HLS info or null if not found
     */
    private function detectHLSFiles(int $lessonId, string $videoPath): ?array
    {
        try {
            // Check for HLS directory: lessons/{lessonId}/hls/
            // HLS files can be in: lessons/{lessonId}/hls/master.m3u8 (direct)
            // OR: lessons/{lessonId}/hls/{randomId}/master.m3u8 (subdirectory)
            $hlsBaseDir = "lessons/{$lessonId}/hls";
            
            // First, check if there's a direct master.m3u8 file
            $masterPlaylistPath = "{$hlsBaseDir}/master.m3u8";
            $masterPlaylistExists = false;
            $disk = null;
            
            if (\Storage::disk('local')->exists($masterPlaylistPath)) {
                $masterPlaylistExists = true;
                $disk = 'local';
            } elseif (\Storage::disk('public')->exists($masterPlaylistPath)) {
                $masterPlaylistExists = true;
                $disk = 'public';
            }
            
            // If not found directly, check subdirectories
            if (!$masterPlaylistExists) {
                // Check for subdirectories in hls folder
                $subdirs = [];
                
                // Check local disk
                if (\Storage::disk('local')->exists($hlsBaseDir)) {
                    $localDirs = \Storage::disk('local')->directories($hlsBaseDir);
                    foreach ($localDirs as $dir) {
                        $subMasterPath = $dir . '/master.m3u8';
                        if (\Storage::disk('local')->exists($subMasterPath)) {
                            $masterPlaylistPath = $subMasterPath;
                            $masterPlaylistExists = true;
                            $disk = 'local';
                            break;
                        }
                    }
                }
                
                // Check public disk if still not found
                if (!$masterPlaylistExists && \Storage::disk('public')->exists($hlsBaseDir)) {
                    $publicDirs = \Storage::disk('public')->directories($hlsBaseDir);
                    foreach ($publicDirs as $dir) {
                        $subMasterPath = $dir . '/master.m3u8';
                        if (\Storage::disk('public')->exists($subMasterPath)) {
                            $masterPlaylistPath = $subMasterPath;
                            $masterPlaylistExists = true;
                            $disk = 'public';
                            break;
                        }
                    }
                }
            }
            
            if (!$masterPlaylistExists) {
                return null;
            }
            
            // Read master playlist file
            $masterPlaylistContent = \Storage::disk($disk)->get($masterPlaylistPath);
            
            if (empty($masterPlaylistContent)) {
                return null;
            }
            
            // Get the HLS directory from master playlist path (could be direct or in subdirectory)
            $hlsDir = dirname($masterPlaylistPath);
            
            // Parse master playlist to extract quality variants
            $variants = [];
            $lines = explode("\n", $masterPlaylistContent);
            $currentBandwidth = null;
            $currentResolution = null;
            $currentQuality = null;
            
            foreach ($lines as $line) {
                $line = trim($line);
                
                // Skip empty lines and comments (except EXT-X-STREAM-INF)
                if (empty($line) || strpos($line, '#EXTM3U') === 0 || strpos($line, '#EXT-X-VERSION') === 0) {
                    continue;
                }
                
                // Parse EXT-X-STREAM-INF line for bandwidth and resolution
                if (preg_match('/#EXT-X-STREAM-INF:BANDWIDTH=(\d+)(?:,RESOLUTION=(\d+x\d+))?/', $line, $matches)) {
                    $currentBandwidth = intval($matches[1]);
                    $currentResolution = $matches[2] ?? null;
                    
                    // Determine quality name from resolution
                    if ($currentResolution) {
                        if (preg_match('/(\d+)x(\d+)/', $currentResolution, $resMatches)) {
                            $height = intval($resMatches[2]);
                            if ($height >= 1080) {
                                $currentQuality = '1080p';
                            } elseif ($height >= 720) {
                                $currentQuality = '720p';
                            } elseif ($height >= 480) {
                                $currentQuality = '480p';
                            } else {
                                $currentQuality = $height . 'p';
                            }
                        }
                    } else {
                        // Fallback: use bandwidth to determine quality
                        if ($currentBandwidth >= 2000000) {
                            $currentQuality = '1080p';
                        } elseif ($currentBandwidth >= 1000000) {
                            $currentQuality = '720p';
                        } else {
                            $currentQuality = '480p';
                        }
                    }
                }
                // Next line after EXT-X-STREAM-INF is the playlist filename
                elseif ($currentBandwidth && !empty($line) && !str_starts_with($line, '#')) {
                    $playlistFilename = $line;
                    // Playlist filename might be relative (e.g., "480p.m3u8") or absolute path
                    // If it's relative, combine with hlsDir; if absolute, use as is
                    if (strpos($playlistFilename, '/') === 0 || strpos($playlistFilename, 'lessons/') === 0) {
                        $playlistPath = $playlistFilename;
                    } else {
                        $playlistPath = "{$hlsDir}/{$playlistFilename}";
                    }
                    
                    if ($currentQuality) {
                        $variants[$currentQuality] = [
                            'playlist' => $playlistPath,
                            'bandwidth' => $currentBandwidth,
                            'resolution' => $currentResolution ?? 'unknown',
                        ];
                    }
                    
                    // Reset for next variant
                    $currentBandwidth = null;
                    $currentResolution = null;
                    $currentQuality = null;
                }
            }
            
            if (empty($variants)) {
                return null;
            }
            
            return [
                'master_playlist' => $masterPlaylistPath,
                'variants' => $variants,
            ];
            
        } catch (\Exception $e) {
            \Log::error('Failed to detect HLS files', [
                'lesson_id' => $lessonId,
                'video_path' => $videoPath,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
