<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingRegistration;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
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
        $query = Meeting::with(['creator', 'training', 'registrations']);

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

        // Filter by training
        if ($request->has('training_id')) {
            $query->where('training_id', $request->training_id);
        }

        $meetings = $query->orderBy('start_time', 'asc')->paginate(15);

        return response()->json($meetings);
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
            'training_id' => ['nullable', 'exists:trainings,id'],
            'is_recurring' => ['boolean'],
            'recurrence_rules' => ['nullable', 'array'],
            'attendees' => ['nullable', 'array'],
            'attendees.*.email' => ['required_with:attendees', 'email'],
            'attendees.*.name' => ['nullable', 'string'],
        ]);

        try {
            DB::beginTransaction();

            // Check if user has Google Calendar access
            $user = $request->user();
            if (!$user->google_access_token) {
                return response()->json([
                    'error' => 'Google Calendar access required',
                    'message' => 'Please authorize Google Calendar access first',
                    'auth_url' => url('/api/v1/google/auth-url')
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
                $meetingData['recurrence'] = $this->formatRecurrenceRules($validated['recurrence_rules']);
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
                'is_recurring' => $validated['is_recurring'] ?? false,
                'recurrence_rules' => $validated['recurrence_rules'] ?? null,
                'status' => 'scheduled',
                'created_by' => $request->user()->id,
                'training_id' => $validated['training_id'] ?? null,
                'attendees' => $validated['attendees'] ?? [],
                'google_metadata' => $googleResult['event_data'],
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Meeting created successfully',
                'meeting' => $meeting->load(['creator', 'training', 'registrations'])
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
        $meeting->load(['creator', 'training', 'registrations.user']);
        
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
            'attendees' => ['nullable', 'array'],
            'attendees.*.email' => ['required_with:attendees', 'email'],
            'attendees.*.name' => ['nullable', 'string'],
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

            // Update meeting in database
            $meeting->update($validated);

            DB::commit();

            return response()->json([
                'message' => 'Meeting updated successfully',
                'meeting' => $meeting->fresh()->load(['creator', 'training', 'registrations'])
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
                'registration' => $registration->load('user')
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

        $registrations = MeetingRegistration::with(['meeting.creator', 'meeting.training'])
            ->where('user_id', $user->id)
            ->orderBy('registered_at', 'desc')
            ->paginate(15);

        return response()->json($registrations);
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
    private function formatRecurrenceRules(array $rules): array
    {
        $recurrence = [];

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
}
