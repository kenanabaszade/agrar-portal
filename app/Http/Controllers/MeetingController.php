<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingRegistration;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\MeetingCreatedNotification;
use Carbon\Carbon;

class MeetingController extends Controller
{
    private GoogleCalendarService $googleCalendarService;

    public function __construct(GoogleCalendarService $googleCalendarService)
    {
        $this->googleCalendarService = $googleCalendarService;
    }

    /**
     * Display a listing of meetings
     */
    public function index(Request $request): JsonResponse
    {
        $query = Meeting::with(['creator', 'trainer', 'registrations']);

        // Filter by status
        if ($request->has('status')) {
            switch ($request->status) {
                case 'upcoming':
                    $query->upcoming();
                    break;
                case 'live':
                    $query->live();
                    break;
                case 'past':
                    $query->past();
                    break;
                default:
                    $query->where('status', $request->status);
            }
        }

        // Filter by creator
        if ($request->has('created_by')) {
            $query->where('created_by', $request->created_by);
        }

        // Filter by trainer
        if ($request->has('trainer_id')) {
            $query->where('trainer_id', $request->trainer_id);
        }

        $meetings = $query->orderBy('start_time', 'asc')->paginate(15);

        return response()->json($meetings);
    }

    /**
     * Get meetings for cards display with enhanced data
     */
    public function getCards(Request $request): JsonResponse
    {
        try {
            $query = Meeting::with(['creator', 'trainer', 'registrations']);

            // Filter by status
            if ($request->has('status')) {
                switch ($request->status) {
                    case 'upcoming':
                        $query->upcoming();
                        break;
                    case 'live':
                        $query->live();
                        break;
                    case 'past':
                        $query->past();
                        break;
                    default:
                        $query->where('status', $request->status);
                }
            }

            // Filter by trainer
            if ($request->has('trainer_id')) {
                $query->where('trainer_id', $request->trainer_id);
            }

            // Filter by category
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            // Filter by language
            if ($request->has('language')) {
                $query->where('language', $request->language);
            }

            // Filter by level
            if ($request->has('level')) {
                $query->where('level', $request->level);
            }

            // Search by title
            if ($request->has('search')) {
                $query->where('title', 'like', '%' . $request->search . '%');
            }

            $meetings = $query->orderBy('start_time', 'desc')->paginate($request->get('per_page', 12));

            // Transform data for cards
            $cards = $meetings->getCollection()->map(function ($meeting) {
                \Log::info('Processing meeting for cards', [
                    'meeting_id' => $meeting->id,
                    'title' => $meeting->title,
                    'image_url_raw' => $meeting->image_url,
                    'has_image' => !is_null($meeting->image_url)
                ]);
                
                return [
                    'id' => $meeting->id,
                    'title' => $meeting->title,
                    'description' => $meeting->description,
                    'status' => $this->getStatusInfo($meeting),
                    'trainer' => [
                        'id' => $meeting->trainer?->id,
                        'name' => $meeting->trainer ? $meeting->trainer->first_name . ' ' . $meeting->trainer->last_name : null,
                        'email' => $meeting->trainer?->email,
                    ],
                    'schedule' => [
                        'date' => $meeting->start_time->format('Y-m-d'),
                        'date_formatted' => $this->formatDateAz($meeting->start_time),
                        'time' => $meeting->start_time->format('H:i'),
                        'timezone' => $meeting->timezone,
                        'duration' => $meeting->start_time->diffInMinutes($meeting->end_time),
                    ],
                    'registration' => [
                        'current' => $meeting->registrations->count(),
                        'max' => $meeting->max_attendees,
                        'percentage' => $meeting->max_attendees > 0 ? 
                            round(($meeting->registrations->count() / $meeting->max_attendees) * 100, 1) : 0,
                    ],
                    'rating' => $this->getRating($meeting),
                    'tags' => $this->getTags($meeting),
                    'platform' => $this->getPlatform($meeting),
                    'image_url' => $meeting->image_url ? url(Storage::url($meeting->image_url)) : null,
                    'has_materials' => $meeting->has_materials,
                    'has_certificate' => $meeting->has_certificate,
                    'is_permanent' => $meeting->is_permanent,
                    'google_meet_link' => $meeting->google_meet_link,
                    'created_at' => $meeting->created_at,
                    'updated_at' => $meeting->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'cards' => $cards,
                    'pagination' => [
                        'current_page' => $meetings->currentPage(),
                        'last_page' => $meetings->lastPage(),
                        'per_page' => $meetings->perPage(),
                        'total' => $meetings->total(),
                        'has_more' => $meetings->hasMorePages(),
                    ],
                    'filters' => [
                        'statuses' => ['planned', 'live', 'completed', 'cancelled'],
                        'categories' => Meeting::distinct()->pluck('category')->filter()->values(),
                        'languages' => Meeting::distinct()->pluck('language')->filter()->values(),
                        'levels' => ['beginner', 'intermediate', 'advanced'],
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch meeting cards',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get status information for card display
     */
    private function getStatusInfo($meeting): array
    {
        $now = Carbon::now();
        $startTime = Carbon::parse($meeting->start_time);
        $endTime = Carbon::parse($meeting->end_time);

        if ($meeting->status === 'cancelled') {
            return [
                'status' => 'cancelled',
                'label' => 'Ləğv edildi',
                'color' => 'red',
                'icon' => 'x-circle'
            ];
        }

        if ($meeting->status === 'ended') {
            return [
                'status' => 'completed',
                'label' => 'Tamamlandı',
                'color' => 'green',
                'icon' => 'check-circle'
            ];
        }

        if ($now->between($startTime, $endTime)) {
            return [
                'status' => 'live',
                'label' => 'Canlı',
                'color' => 'green',
                'icon' => 'play-circle'
            ];
        }

        if ($startTime->isFuture()) {
            return [
                'status' => 'planned',
                'label' => 'Planlaşdırılıb',
                'color' => 'blue',
                'icon' => 'clock'
            ];
        }

        return [
            'status' => 'past',
            'label' => 'Keçmiş',
            'color' => 'gray',
            'icon' => 'calendar'
        ];
    }

    /**
     * Get rating information
     */
    private function getRating($meeting): array
    {
        // Default rating - implement rating system if needed
        $rating = 4.5 + (rand(0, 10) / 10); // Random between 4.5-5.5
        
        return [
            'value' => round($rating, 1),
            'display' => round($rating, 1) . '/5.0',
            'stars' => round($rating),
            'total_reviews' => rand(10, 100)
        ];
    }

    /**
     * Get tags for card display
     */
    private function getTags($meeting): array
    {
        $tags = [];
        
        // Platform tag
        if ($meeting->google_meet_link) {
            $tags[] = [
                'label' => 'Google Meet',
                'color' => 'blue',
                'type' => 'platform'
            ];
        }
        
        // Language tag
        if ($meeting->language) {
            $tags[] = [
                'label' => strtoupper($meeting->language),
                'color' => 'gray',
                'type' => 'language'
            ];
        }
        
        // Category tag
        if ($meeting->category) {
            $tags[] = [
                'label' => $meeting->category,
                'color' => 'green',
                'type' => 'category'
            ];
        }
        
        // Hashtags
        if ($meeting->hashtags && is_array($meeting->hashtags)) {
            foreach (array_slice($meeting->hashtags, 0, 2) as $hashtag) {
                $tags[] = [
                    'label' => $hashtag,
                    'color' => 'purple',
                    'type' => 'hashtag'
                ];
            }
        }
        
        return $tags;
    }

    /**
     * Get platform information
     */
    private function getPlatform($meeting): array
    {
        if ($meeting->google_meet_link) {
            return [
                'name' => 'Google Meet',
                'type' => 'google_meet',
                'url' => $meeting->google_meet_link,
                'icon' => 'video-camera'
            ];
        }
        
        return [
            'name' => 'Təyin edilməyib',
            'type' => 'unknown',
            'url' => null,
            'icon' => 'calendar'
        ];
    }

    /**
     * Format date in Azerbaijani format
     * Example: "28 Yanvar" (28 January)
     */
    private function formatDateAz($date): string
    {
        $carbon = Carbon::parse($date);
        
        $months = [
            1 => 'Yanvar',
            2 => 'Fevral',
            3 => 'Mart',
            4 => 'Aprel',
            5 => 'May',
            6 => 'İyun',
            7 => 'İyul',
            8 => 'Avqust',
            9 => 'Sentyabr',
            10 => 'Oktyabr',
            11 => 'Noyabr',
            12 => 'Dekabr',
        ];
        
        $day = $carbon->day;
        $month = $months[$carbon->month] ?? '';
        
        return $day . ' ' . $month;
    }

    /**
     * Create a new Google Meet meeting
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_time' => ['required', 'date', 'after:now'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'max_attendees' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'trainer_id' => ['nullable', 'integer', 'exists:users,id'],
            'is_recurring' => ['nullable', 'boolean'],
            'recurrence_rules' => ['nullable'],
            'attendees' => ['nullable'],
            'attendees.*.email' => ['nullable', 'email'],
            'attendees.*.name' => ['nullable', 'string'],
            // New enhanced fields validation
            'category' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'has_materials' => ['nullable', 'boolean'],
            'documents' => ['nullable'],
            'documents.*.name' => ['nullable', 'string', 'max:255'],
            'documents.*.url' => ['nullable', 'string', 'url'],
            'documents.*.type' => ['nullable', 'string', 'in:pdf,doc,docx,ppt,pptx,xls,xlsx'],
            'level' => ['nullable', 'in:beginner,intermediate,advanced'],
            'language' => ['nullable', 'string', 'max:10'],
            'hashtags' => ['nullable'],
            'hashtags.*' => ['nullable', 'string', 'max:50'],
            'is_permanent' => ['nullable', 'boolean'],
            'has_certificate' => ['nullable', 'boolean'],
        ]);

        try {
            DB::beginTransaction();

            // Check if user has Google Calendar access
            $user = $request->user();
            if (!$user->google_access_token) {
                return response()->json([
                    'error' => 'Google Calendar access required',
                    'message' => 'Please authorize Google Calendar access first',
                    'auth_url' => url('/api/v1/google/auth-url'),
                    'check_auth_url' => url('/api/v1/google/check-access')
                ], 401);
            }

            // Verify the access token is still valid by testing it
            $this->googleCalendarService->setAccessToken($user->google_access_token);
            $tokenValidation = $this->googleCalendarService->validateAccessToken();
            
            if (!$tokenValidation['valid']) {
                return response()->json([
                    'error' => 'Google Calendar access expired',
                    'message' => 'Your Google Calendar access has expired. Please re-authorize.',
                    'auth_url' => url('/api/v1/google/auth-url'),
                    'details' => $tokenValidation['error'] ?? 'Token validation failed'
                ], 401);
            }

            // Set the user's access token for Google Calendar API
            $this->googleCalendarService->setAccessToken($user->google_access_token);

            // Prepare meeting data for Google Calendar
            $meetingData = [
                'title' => $validated['title'],
                'description' => $validated['description'] ?? '',
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'timezone' => $validated['timezone'] ?? 'UTC',
                'attendees' => $validated['attendees'] ?? [],
            ];

            // Add recurrence if specified
            if (isset($validated['is_recurring']) && $validated['is_recurring'] && isset($validated['recurrence_rules'])) {
                $recurrenceRules = $this->convertToArray($validated['recurrence_rules']);
                $meetingData['recurrence'] = $this->formatRecurrenceRules($recurrenceRules);
            }

            // Create meeting in Google Calendar
            $googleResult = $this->googleCalendarService->createMeeting($meetingData);

            if (!$googleResult['success']) {
                return response()->json([
                    'error' => 'Failed to create Google Meet meeting',
                    'details' => $googleResult['error']
                ], 400);
            }

            // Create meeting in database
            // Handle image upload if provided
            $imagePath = null;
            if ($request->hasFile('image')) {
                // Create unique filename with meeting ID prefix
                $file = $request->file('image');
                $extension = $file->getClientOriginalExtension();
                $filename = 'meeting_' . time() . '_' . uniqid() . '.' . $extension;
                $imagePath = $file->storeAs('meetings/images', $filename, 'public');
                
                \Log::info('Image uploaded successfully', [
                    'filename' => $filename,
                    'path' => $imagePath,
                    'size' => $file->getSize()
                ]);
            } else {
                \Log::info('No image file provided in request');
            }

            $meeting = Meeting::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'google_event_id' => $googleResult['event_id'],
                'google_meet_link' => $googleResult['meet_link'],
                'meeting_id' => $googleResult['meeting_id'],
                'meeting_password' => $googleResult['meeting_password'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'timezone' => $validated['timezone'] ?? 'UTC',
                'max_attendees' => $validated['max_attendees'] ?? 100,
                'is_recurring' => $this->convertToBoolean($validated['is_recurring'] ?? false),
                'recurrence_rules' => $this->convertToArray($validated['recurrence_rules'] ?? null),
                'status' => 'scheduled',
                'created_by' => $request->user()->id,
                'trainer_id' => isset($validated['trainer_id']) && $validated['trainer_id'] !== 'null' ? $validated['trainer_id'] : null,
                'attendees' => $this->convertToArray($validated['attendees'] ?? []),
                'google_metadata' => $googleResult['event_data'],
                // Enhanced fields
                'category' => $validated['category'] ?? null,
                'image_url' => $imagePath,
                'has_materials' => $this->convertToBoolean($validated['has_materials'] ?? false),
                'documents' => $this->convertToArray($validated['documents'] ?? null),
                'level' => $validated['level'] ?? 'beginner',
                'language' => $validated['language'] ?? 'az',
                'hashtags' => $this->convertToArray($validated['hashtags'] ?? null),
                'is_permanent' => $this->convertToBoolean($validated['is_permanent'] ?? false),
                'has_certificate' => $this->convertToBoolean($validated['has_certificate'] ?? false),
            ]);

            DB::commit();

            // Send email notifications to attendees
            $attendees = $this->convertAttendees($validated['attendees'] ?? []);
            if (!empty($attendees)) {
                foreach ($attendees as $attendee) {
                    try {
                        Mail::to($attendee['email'])->send(
                            new MeetingCreatedNotification($meeting, $attendee)
                        );
                    } catch (\Exception $e) {
                        // Log email sending error but don't fail the meeting creation
                        \Log::error('Failed to send meeting notification email', [
                            'email' => $attendee['email'],
                            'meeting_id' => $meeting->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            // Load meeting with relationships and add image URL
            $meeting = $meeting->load(['creator', 'trainer', 'registrations']);
            
            // Add full image URL if image exists
            if ($meeting->image_url) {
                $meeting->image_url = Storage::url($meeting->image_url);
            }

            return response()->json([
                'message' => 'Meeting created successfully',
                'meeting' => $meeting,
                'notifications_sent' => count($attendees)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'error' => 'Failed to create meeting',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified meeting
     */
    public function show(Meeting $meeting): JsonResponse
    {
        $meeting->load(['creator', 'trainer', 'registrations.user']);
        
        return response()->json([
            'meeting' => $meeting,
            'is_live' => $meeting->isLive(),
            'is_upcoming' => $meeting->isUpcoming(),
            'has_ended' => $meeting->hasEnded(),
            'attendee_count' => $meeting->attendee_count,
            'has_available_spots' => $meeting->hasAvailableSpots(),
        ]);
    }

    /**
     * Update the specified meeting
     */
    public function update(Request $request, Meeting $meeting): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_time' => ['sometimes', 'date', 'after:now'],
            'end_time' => ['sometimes', 'date', 'after:start_time'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'max_attendees' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'status' => ['sometimes', 'in:scheduled,live,ended,cancelled'],
            'attendees' => ['nullable'],
            'attendees.*.email' => ['nullable', 'email'],
            'attendees.*.name' => ['nullable', 'string'],
            // New enhanced fields validation
            'category' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'has_materials' => ['nullable', 'boolean'],
            'documents' => ['nullable'],
            'documents.*.name' => ['nullable', 'string', 'max:255'],
            'documents.*.url' => ['nullable', 'string', 'url'],
            'documents.*.type' => ['nullable', 'string', 'in:pdf,doc,docx,ppt,pptx,xls,xlsx'],
            'level' => ['nullable', 'in:beginner,intermediate,advanced'],
            'language' => ['nullable', 'string', 'max:10'],
            'hashtags' => ['nullable'],
            'hashtags.*' => ['nullable', 'string', 'max:50'],
            'is_permanent' => ['nullable', 'boolean'],
            'has_certificate' => ['nullable', 'boolean'],
        ]);

        try {
            DB::beginTransaction();

            // Update in Google Calendar if event details changed
            if (isset($validated['title']) || isset($validated['description']) || 
                isset($validated['start_time']) || isset($validated['end_time'])) {
                
                $googleResult = $this->googleCalendarService->updateMeeting(
                    $meeting->google_event_id,
                    $validated
                );

                if (!$googleResult['success']) {
                    return response()->json([
                        'error' => 'Failed to update Google Meet meeting',
                        'details' => $googleResult['error']
                    ], 400);
                }
            }

            // Handle image upload if provided
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($meeting->image_url && Storage::disk('public')->exists($meeting->image_url)) {
                    Storage::disk('public')->delete($meeting->image_url);
                }
                
                // Store new image with unique filename
                $file = $request->file('image');
                $extension = $file->getClientOriginalExtension();
                $filename = 'meeting_' . $meeting->id . '_' . time() . '.' . $extension;
                $imagePath = $file->storeAs('meetings/images', $filename, 'public');
                
                // Add image path to update data
                $validated['image_url'] = $imagePath;
                
                \Log::info('Meeting image updated', [
                    'meeting_id' => $meeting->id,
                    'filename' => $filename,
                    'path' => $imagePath
                ]);
            }

            // Update meeting in database with converted data
            $updateData = $validated;
            
            // Convert boolean fields
            if (isset($updateData['has_materials'])) {
                $updateData['has_materials'] = $this->convertToBoolean($updateData['has_materials']);
            }
            if (isset($updateData['is_permanent'])) {
                $updateData['is_permanent'] = $this->convertToBoolean($updateData['is_permanent']);
            }
            if (isset($updateData['has_certificate'])) {
                $updateData['has_certificate'] = $this->convertToBoolean($updateData['has_certificate']);
            }
            
            // Convert array fields
            if (isset($updateData['attendees'])) {
                $updateData['attendees'] = $this->convertToArray($updateData['attendees']);
            }
            if (isset($updateData['documents'])) {
                $updateData['documents'] = $this->convertToArray($updateData['documents']);
            }
            if (isset($updateData['hashtags'])) {
                $updateData['hashtags'] = $this->convertToArray($updateData['hashtags']);
            }
            
            $meeting->update($updateData);

            DB::commit();

            return response()->json([
                'message' => 'Meeting updated successfully',
                'meeting' => $meeting->fresh()->load(['creator', 'trainer', 'registrations'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'error' => 'Failed to update meeting',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete the specified meeting
     */
    public function destroy(Meeting $meeting): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Delete from Google Calendar
            $googleResult = $this->googleCalendarService->deleteMeeting($meeting->google_event_id);

            if (!$googleResult['success']) {
                // Log error but continue with database deletion
                \Log::warning('Failed to delete Google Calendar event', [
                    'event_id' => $meeting->google_event_id,
                    'error' => $googleResult['error']
                ]);
            }

            // Delete meeting from database (cascades to registrations)
            $meeting->delete();

            DB::commit();

            return response()->json([
                'message' => 'Meeting deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'error' => 'Failed to delete meeting',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Register user for a meeting
     */
    public function register(Request $request, Meeting $meeting): JsonResponse
    {
        $user = $request->user();

        // Check if user is already registered
        if ($meeting->isUserRegistered($user->id)) {
            return response()->json([
                'error' => 'You are already registered for this meeting'
            ], 400);
        }

        // Check if meeting has available spots
        if (!$meeting->hasAvailableSpots()) {
            return response()->json([
                'error' => 'Meeting is full, no available spots'
            ], 400);
        }

        // Check if meeting is still open for registration
        if ($meeting->hasEnded() || $meeting->status === 'cancelled') {
            return response()->json([
                'error' => 'Meeting is no longer available for registration'
            ], 400);
        }

        try {
            $registration = MeetingRegistration::create([
                'meeting_id' => $meeting->id,
                'user_id' => $user->id,
                'status' => 'registered',
                'registered_at' => now(),
            ]);

            return response()->json([
                'message' => 'Successfully registered for meeting',
                'registration' => $registration->load('user'),
                'meeting' => [
                    'id' => $meeting->id,
                    'title' => $meeting->title,
                    'description' => $meeting->description,
                    'start_time' => $meeting->start_time,
                    'end_time' => $meeting->end_time,
                    'google_meet_link' => $meeting->google_meet_link,
                    'timezone' => $meeting->timezone,
                    'status' => $meeting->status,
                    'trainer' => [
                        'id' => $meeting->trainer?->id,
                        'name' => $meeting->trainer ? $meeting->trainer->first_name . ' ' . $meeting->trainer->last_name : null,
                        'email' => $meeting->trainer?->email,
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to register for meeting',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel user registration for a meeting
     */
    public function cancelRegistration(Request $request, Meeting $meeting): JsonResponse
    {
        $user = $request->user();

        $registration = MeetingRegistration::where('meeting_id', $meeting->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$registration) {
            return response()->json([
                'error' => 'You are not registered for this meeting'
            ], 404);
        }

        if ($registration->status === 'cancelled') {
            return response()->json([
                'error' => 'Registration is already cancelled'
            ], 400);
        }

        $registration->cancel();

        return response()->json([
            'message' => 'Registration cancelled successfully'
        ]);
    }

    /**
     * Get user's meeting registrations
     */
    public function myRegistrations(Request $request): JsonResponse
    {
        $user = $request->user();

        $registrations = MeetingRegistration::with(['meeting.creator', 'meeting.trainer'])
            ->where('user_id', $user->id)
            ->orderBy('registered_at', 'desc')
            ->paginate(15);

        // Transform the data to include Google Meet link and other meeting details
        $transformedRegistrations = $registrations->getCollection()->map(function ($registration) {
            $meeting = $registration->meeting;
            return [
                'id' => $registration->id,
                'meeting_id' => $registration->meeting_id,
                'user_id' => $registration->user_id,
                'status' => $registration->status,
                'registered_at' => $registration->registered_at,
                'meeting' => [
                    'id' => $meeting->id,
                    'title' => $meeting->title,
                    'description' => $meeting->description,
                    'start_time' => $meeting->start_time,
                    'end_time' => $meeting->end_time,
                    'google_meet_link' => $meeting->google_meet_link,
                    'timezone' => $meeting->timezone,
                    'status' => $meeting->status,
                    'max_attendees' => $meeting->max_attendees,
                    'creator' => $meeting->creator ? [
                        'id' => $meeting->creator->id,
                        'name' => $meeting->creator->first_name . ' ' . $meeting->creator->last_name,
                        'email' => $meeting->creator->email,
                    ] : null,
                    'trainer' => $meeting->trainer ? [
                        'id' => $meeting->trainer->id,
                        'name' => $meeting->trainer->first_name . ' ' . $meeting->trainer->last_name,
                        'email' => $meeting->trainer->email,
                    ] : null,
                ]
            ];
        });

        return response()->json([
            'data' => $transformedRegistrations,
            'pagination' => [
                'current_page' => $registrations->currentPage(),
                'last_page' => $registrations->lastPage(),
                'per_page' => $registrations->perPage(),
                'total' => $registrations->total(),
                'has_more' => $registrations->hasMorePages(),
            ]
        ]);
    }

    /**
     * Get meeting attendees
     */
    public function attendees(Meeting $meeting): JsonResponse
    {
        $attendees = $meeting->registrations()
            ->with('user')
            ->where('status', '!=', 'cancelled')
            ->get();

        return response()->json([
            'meeting' => $meeting->only(['id', 'title', 'start_time', 'end_time']),
            'attendees' => $attendees,
            'total_attendees' => $attendees->count(),
            'max_attendees' => $meeting->max_attendees,
        ]);
    }

    /**
     * Format recurrence rules for Google Calendar
     */
    private function formatRecurrenceRules($rules): array
    {
        $recurrence = [];
        
        // Ensure rules is an array
        if (!is_array($rules)) {
            return $recurrence;
        }

        foreach ($rules as $rule) {
            $rrule = 'RRULE:';
            
            if (isset($rule['frequency'])) {
                $rrule .= 'FREQ=' . strtoupper($rule['frequency']);
            }
            
            if (isset($rule['interval'])) {
                $rrule .= ';INTERVAL=' . $rule['interval'];
            }
            
            if (isset($rule['count'])) {
                $rrule .= ';COUNT=' . $rule['count'];
            }
            
            if (isset($rule['until'])) {
                $rrule .= ';UNTIL=' . Carbon::parse($rule['until'])->format('Ymd\THis\Z');
            }

            $recurrence[] = $rrule;
        }

        return $recurrence;
    }

    /**
     * Upload meeting image
     */
    public function uploadImage(Request $request, Meeting $meeting): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
        ]);

        try {
            // Delete old image if exists
            if ($meeting->image_url && Storage::disk('public')->exists($meeting->image_url)) {
                Storage::disk('public')->delete($meeting->image_url);
            }

            // Store new image with unique filename
            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();
            $filename = 'meeting_' . $meeting->id . '_' . time() . '.' . $extension;
            $imagePath = $file->storeAs('meetings/images', $filename, 'public');
            
            // Update meeting with new image path
            $meeting->update([
                'image_url' => $imagePath
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Image uploaded successfully',
                'image_url' => Storage::url($imagePath),
                'image_path' => $imagePath
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to upload image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete meeting image
     */
    public function deleteImage(Meeting $meeting): JsonResponse
    {
        try {
            if ($meeting->image_url && Storage::disk('public')->exists($meeting->image_url)) {
                Storage::disk('public')->delete($meeting->image_url);
            }

            $meeting->update(['image_url' => null]);

            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Convert string to boolean
     */
    private function convertToBoolean($value)
    {
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            return in_array(strtolower($value), ['true', '1', 'yes', 'on']);
        }
        
        return (bool) $value;
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
}
