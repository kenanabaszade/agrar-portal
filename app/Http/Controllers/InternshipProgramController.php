<?php

namespace App\Http\Controllers;

use App\Models\InternshipProgram;
use App\Models\ProgramModule;
use App\Models\ProgramRequirement;
use App\Models\ProgramGoal;
use App\Models\User;
use App\Mail\InternshipProgramNotification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InternshipProgramController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = InternshipProgram::with(['modules', 'requirements', 'goals', 'trainer'])
            ->where('is_active', true);

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by registration status
        if ($request->has('registration_status')) {
            $query->where('registration_status', $request->registration_status);
        }

        // Filter featured programs
        if ($request->has('featured') && $request->featured) {
            $query->where('is_featured', true);
        }

        // Search by title or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                \App\Helpers\TranslationSearchHelper::addMultipleJsonFieldSearch($q, ['title', 'description'], $search);
            });
        }

        $programs = $query->orderBy('is_featured', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        // Calculate unique user count (total_applications) for each program
        $programIds = $programs->pluck('id');
        $uniqueUserCounts = \App\Models\InternshipApplication::whereIn('internship_program_id', $programIds)
            ->selectRaw('internship_program_id, COUNT(DISTINCT user_id) as unique_users')
            ->groupBy('internship_program_id')
            ->pluck('unique_users', 'internship_program_id');

        // If user is authenticated, add their application status
        $user = auth()->user();
        $userApplications = collect();
        if ($user) {
            $userApplications = \App\Models\InternshipApplication::where('user_id', $user->id)
                ->whereIn('internship_program_id', $programIds)
                ->get()
                ->groupBy('internship_program_id');
        }

        $programs->getCollection()->transform(function ($program) use ($uniqueUserCounts, $userApplications) {
            // Add unique user count (total_applications)
            $program->total_applications = $uniqueUserCounts->get($program->id, 0);
            
            // Add user application status if authenticated
            if ($userApplications->has($program->id)) {
                $applications = $userApplications->get($program->id);
                $latestApplication = $applications->sortByDesc('created_at')->first();
                
                $program->user_application = [
                    'id' => $latestApplication->id,
                    'status' => $latestApplication->status,
                    'created_at' => $latestApplication->created_at,
                    'application_count' => $applications->count(),
                    'can_apply_again' => $applications->count() < 2,
                ];
            } else {
                $program->user_application = [
                    'application_count' => 0,
                    'can_apply_again' => true,
                ];
            }
            
            return $program;
        });

        return response()->json([
            'programs' => $programs,
            'meta' => [
                'total' => $programs->total(),
                'per_page' => $programs->perPage(),
                'current_page' => $programs->currentPage(),
                'last_page' => $programs->lastPage(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        // Validate all fields except image first
        $validated = $request->validate([
            'trainer_id' => 'nullable|integer|exists:users,id',
            'trainer_mail' => 'nullable|email|max:255',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image_url' => 'nullable|string|max:500',
            'is_featured' => 'nullable|boolean',
            'registration_status' => 'required|in:open,closed,full',
            'category' => 'required|string|max:100',
            'duration_weeks' => 'required|integer|min:1',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'last_register_date' => 'nullable|date|after_or_equal:today',
            'location' => 'required|string|max:255',
            'current_enrollment' => 'integer|min:0',
            'max_capacity' => 'required|integer|min:1',
            'instructor_name' => 'required_without:trainer_id|string|max:255',
            'instructor_title' => 'required|string|max:255',
            'instructor_initials' => 'nullable|string|max:10',
            'instructor_photo_url' => 'nullable|string|max:500',
            'instructor_description' => 'nullable|string',
            'instructor_rating' => 'nullable|numeric|min:0|max:5',
            'details_link' => 'nullable|string|max:500',
            'cv_requirements' => 'nullable|string',
            'modules' => 'array',
            'modules.*.title' => 'required|string|max:255',
            'modules.*.description' => 'nullable|string',
            'modules.*.order' => 'integer|min:1',
            'requirements' => 'array',
            'requirements.*.requirement' => 'required|string|max:500',
            'requirements.*.order' => 'integer|min:1',
            'goals' => 'array',
            'goals.*.goal' => 'required|string|max:500',
            'goals.*.order' => 'integer|min:1',
        ]);

        // Validate image separately
        if ($request->hasFile('image')) {
            $request->validate([
                'image' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);
        }

        DB::beginTransaction();
        try {
            // If trainer_id is provided, auto-fill instructor data
            if (isset($validated['trainer_id'])) {
                $trainer = \App\Models\User::find($validated['trainer_id']);
                if ($trainer) {
                    $validated['instructor_name'] = $trainer->first_name . ' ' . $trainer->last_name;
                    $validated['instructor_initials'] = strtoupper(substr($trainer->first_name, 0, 1) . substr($trainer->last_name, 0, 1));
                    $validated['instructor_photo_url'] = $trainer->profile_photo_url;
                }
            }

            // Handle image upload
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = 'program_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('internship_programs', $filename, 'public');
                $validated['image_url'] = asset('storage/internship_programs/' . $filename);
                
                // Debug: Log the image upload
                \Log::info('Image uploaded for new program', [
                    'filename' => $filename,
                    'path' => $path,
                    'url' => $validated['image_url']
                ]);
            } else {
                \Log::info('No image file found in request for new program');
            }

            // Convert empty strings to null for nullable fields
            if (isset($validated['trainer_mail']) && $validated['trainer_mail'] === '') {
                $validated['trainer_mail'] = null;
            }

            // Create the internship program
            $program = InternshipProgram::create($validated);

            // Create modules
            if (isset($validated['modules'])) {
                foreach ($validated['modules'] as $moduleData) {
                    $program->modules()->create($moduleData);
                }
            }

            // Create requirements
            if (isset($validated['requirements'])) {
                foreach ($validated['requirements'] as $requirementData) {
                    $program->requirements()->create($requirementData);
                }
            }

            // Create goals
            if (isset($validated['goals'])) {
                foreach ($validated['goals'] as $goalData) {
                    $program->goals()->create($goalData);
                }
            }

            DB::commit();

            // Send notification emails to all users after successful creation
            $this->sendProgramNotifications($program, 'created');

            return response()->json([
                'message' => 'Internship program created successfully',
                'program' => $program->load(['modules', 'requirements', 'goals'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create internship program',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(InternshipProgram $internshipProgram): JsonResponse
    {
        // Refresh to ensure latest data including trainer_mail
        $internshipProgram->refresh();
        
        $program = $internshipProgram->load(['modules', 'requirements', 'goals', 'trainer']);
        
        // Calculate unique user count (total_applications)
        $program->total_applications = \App\Models\InternshipApplication::where('internship_program_id', $internshipProgram->id)
            ->distinct('user_id')
            ->count('user_id');
        
        // If user is authenticated, add their application status
        $user = auth()->user();
        if ($user) {
            $userApplications = \App\Models\InternshipApplication::where('user_id', $user->id)
                ->where('internship_program_id', $internshipProgram->id)
                ->get();
            
            $latestApplication = $userApplications->sortByDesc('created_at')->first();
            
            $program->user_application = $latestApplication ? [
                'id' => $latestApplication->id,
                'status' => $latestApplication->status,
                'created_at' => $latestApplication->created_at,
                'application_count' => $userApplications->count(),
                'can_apply_again' => $userApplications->count() < 2,
            ] : [
                'application_count' => 0,
                'can_apply_again' => true,
            ];
        } else {
            $program->user_application = null;
        }
        
        return response()->json([
            'program' => $program
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, InternshipProgram $internshipProgram): JsonResponse
    {
        // Debug: Check raw request data BEFORE validation
        \Log::info('Raw request data BEFORE validation', [
            'method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'all_input' => $request->all(),
            'trainer_mail_raw' => $request->input('trainer_mail'),
            'end_date_raw' => $request->input('end_date'),
            'has_trainer_mail' => $request->has('trainer_mail'),
            'has_end_date' => $request->has('end_date'),
        ]);
        
        // Validate all fields except image first
        $validated = $request->validate([
            'trainer_id' => 'nullable|integer|exists:users,id',
            'trainer_mail' => 'sometimes|nullable|email|max:255',
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'image_url' => 'nullable|string|max:500',
            'is_featured' => 'boolean',
            'registration_status' => 'sometimes|in:open,closed,full',
            'category' => 'sometimes|string|max:100',
            'duration_weeks' => 'sometimes|integer|min:1',
            'start_date' => 'sometimes|date|after_or_equal:today',
            'end_date' => 'sometimes|nullable|date',
            'last_register_date' => 'sometimes|nullable|date|after_or_equal:today',
            'location' => 'sometimes|string|max:255',
            'current_enrollment' => 'integer|min:0',
            'max_capacity' => 'sometimes|integer|min:1',
            'instructor_name' => 'sometimes|string|max:255',
            'instructor_title' => 'sometimes|string|max:255',
            'instructor_initials' => 'nullable|string|max:10',
            'instructor_photo_url' => 'nullable|string|max:500',
            'instructor_description' => 'nullable|string',
            'instructor_rating' => 'nullable|numeric|min:0|max:5',
            'details_link' => 'nullable|string|max:500',
            'cv_requirements' => 'nullable|string',
            'modules' => 'array',
            'modules.*.id' => 'nullable|integer|exists:program_modules,id',
            'modules.*.title' => 'required|string|max:255',
            'modules.*.description' => 'nullable|string',
            'modules.*.order' => 'integer|min:1',
            'requirements' => 'array',
            'requirements.*.id' => 'nullable|integer|exists:program_requirements,id',
            'requirements.*.requirement' => 'required|string|max:500',
            'requirements.*.order' => 'integer|min:1',
            'goals' => 'array',
            'goals.*.id' => 'nullable|integer|exists:program_goals,id',
            'goals.*.goal' => 'required|string|max:500',
            'goals.*.order' => 'integer|min:1',
        ]);

        // Debug: Check request and validated data
        \Log::info('Update request and validated data', [
            'request_trainer_mail' => $request->input('trainer_mail'),
            'request_end_date' => $request->input('end_date'),
            'validated_trainer_mail' => $validated['trainer_mail'] ?? 'NOT IN VALIDATED',
            'validated_end_date' => $validated['end_date'] ?? 'NOT IN VALIDATED',
            'all_validated_keys' => array_keys($validated),
            'all_request_keys' => array_keys($request->all())
        ]);

        // If fields are in request but not in validated, add them manually
        // Check both with has() and input() for FormData compatibility
        $trainerMail = $request->input('trainer_mail');
        if ($trainerMail !== null && !isset($validated['trainer_mail'])) {
            // Validate email format
            if ($trainerMail && $trainerMail !== '' && !filter_var($trainerMail, FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'trainer_mail' => ['The trainer_mail must be a valid email address.']
                    ]
                ], 422);
            }
            $validated['trainer_mail'] = $trainerMail && $trainerMail !== '' ? $trainerMail : null;
        } elseif (!isset($validated['trainer_mail'])) {
            // If field is not in request at all, set to null if it was previously set
            $validated['trainer_mail'] = null;
        }
        
        $endDate = $request->input('end_date');
        if ($endDate !== null && !isset($validated['end_date'])) {
            // Validate date format
            if ($endDate && $endDate !== '') {
                try {
                    $endDateParsed = \Carbon\Carbon::parse($endDate);
                    $validated['end_date'] = $endDateParsed->format('Y-m-d');
                } catch (\Exception $e) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => [
                            'end_date' => ['The end date must be a valid date.']
                        ]
                    ], 422);
                }
            } else {
                $validated['end_date'] = null;
            }
        } elseif (!isset($validated['end_date'])) {
            // If field is not in request at all, keep existing value
            // Don't set to null to preserve existing value
        }

        // Custom validation for end_date - must be after start_date (either from request or existing)
        if (isset($validated['end_date']) && $validated['end_date']) {
            $startDate = isset($validated['start_date']) 
                ? \Carbon\Carbon::parse($validated['start_date']) 
                : $internshipProgram->start_date;
            $endDateParsed = \Carbon\Carbon::parse($validated['end_date']);
            
            if ($startDate && $endDateParsed->lt($startDate)) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'end_date' => ['The end date must be after or equal to start date.']
                    ]
                ], 422);
            }
            
            // Store as formatted date string
            $validated['end_date'] = $endDateParsed->format('Y-m-d');
        }

        // Validate image separately
        if ($request->hasFile('image')) {
            $request->validate([
                'image' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);
        }

        DB::beginTransaction();
        try {
            // If trainer_id is provided, auto-fill instructor data
            if (isset($validated['trainer_id'])) {
                $trainer = \App\Models\User::find($validated['trainer_id']);
                if ($trainer) {
                    $validated['instructor_name'] = $trainer->first_name . ' ' . $trainer->last_name;
                    $validated['instructor_initials'] = strtoupper(substr($trainer->first_name, 0, 1) . substr($trainer->last_name, 0, 1));
                    $validated['instructor_photo_url'] = $trainer->profile_photo_url;
                }
            }

            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($internshipProgram->image_url) {
                    Storage::disk('public')->delete('internship_programs/' . basename($internshipProgram->image_url));
                }

                // Store new image
                $file = $request->file('image');
                $filename = 'program_' . $internshipProgram->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('internship_programs', $filename, 'public');
                $validated['image_url'] = asset('storage/internship_programs/' . $filename);
                
                // Debug: Log the image upload
                \Log::info('Image uploaded for program ' . $internshipProgram->id, [
                    'filename' => $filename,
                    'path' => $path,
                    'url' => $validated['image_url']
                ]);
            } else {
                \Log::info('No image file found in request');
            }

            // Convert empty strings to null for nullable fields
            if (isset($validated['trainer_mail']) && $validated['trainer_mail'] === '') {
                $validated['trainer_mail'] = null;
            }
            
            // Debug: Log validated data
            \Log::info('Update validated data', [
                'trainer_mail' => $validated['trainer_mail'] ?? 'NOT SET',
                'end_date' => $validated['end_date'] ?? 'NOT SET',
                'all_validated' => array_keys($validated)
            ]);

            // Update the internship program
            \Log::info('About to update with validated data', [
                'validated_keys' => array_keys($validated),
                'trainer_mail' => $validated['trainer_mail'] ?? 'NOT IN VALIDATED',
                'end_date' => $validated['end_date'] ?? 'NOT IN VALIDATED',
                'current_trainer_mail' => $internshipProgram->trainer_mail,
                'current_end_date' => $internshipProgram->end_date,
            ]);
            
            $internshipProgram->update($validated);
            
            \Log::info('After update, before refresh', [
                'trainer_mail' => $internshipProgram->trainer_mail,
                'end_date' => $internshipProgram->end_date,
            ]);

            // Update modules
            if (isset($validated['modules'])) {
                $this->updateModules($internshipProgram, $validated['modules']);
            }

            // Update requirements
            if (isset($validated['requirements'])) {
                $this->updateRequirements($internshipProgram, $validated['requirements']);
            }

            // Update goals
            if (isset($validated['goals'])) {
                $this->updateGoals($internshipProgram, $validated['goals']);
            }

            DB::commit();

            // Refresh the model to get updated data
            $internshipProgram->refresh();

            // Send notification emails to all users after successful update
            $this->sendProgramNotifications($internshipProgram, 'updated');

            return response()->json([
                'message' => 'Internship program updated successfully',
                'program' => $internshipProgram->load(['modules', 'requirements', 'goals'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update internship program',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InternshipProgram $internshipProgram): JsonResponse
    {
        $internshipProgram->delete();

        return response()->json([
            'message' => 'Internship program deleted successfully'
        ]);
    }

    /**
     * Get featured programs
     */
    public function featured(): JsonResponse
    {
        $programs = InternshipProgram::with(['modules', 'requirements', 'goals', 'trainer'])
            ->where('is_featured', true)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'programs' => $programs
        ]);
    }

    /**
     * Get program categories
     */
    public function categories(): JsonResponse
    {
        $categories = InternshipProgram::where('is_active', true)
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values();

        return response()->json([
            'categories' => $categories
        ]);
    }

    /**
     * Get available trainers for internship programs
     */
    public function trainers(): JsonResponse
    {
        $trainers = \App\Models\User::where('user_type', 'trainer')
            ->where('is_active', true)
            ->select('id', 'first_name', 'last_name', 'email', 'phone', 'profile_photo')
            ->get()
            ->map(function ($trainer) {
                return [
                    'id' => $trainer->id,
                    'name' => $trainer->first_name . ' ' . $trainer->last_name,
                    'email' => $trainer->email,
                    'phone' => $trainer->phone,
                    'profile_photo' => $trainer->profile_photo,
                    'profile_photo_url' => $trainer->profile_photo_url,
                    'initials' => strtoupper(substr($trainer->first_name, 0, 1) . substr($trainer->last_name, 0, 1)),
                ];
            });

        return response()->json([
            'trainers' => $trainers
        ]);
    }

    /**
     * Update modules for a program
     */
    private function updateModules(InternshipProgram $program, array $modules): void
    {
        $existingIds = collect($modules)->pluck('id')->filter();
        
        // Delete modules not in the request
        $program->modules()->whereNotIn('id', $existingIds)->delete();

        foreach ($modules as $moduleData) {
            if (isset($moduleData['id'])) {
                // Update existing module
                $program->modules()->where('id', $moduleData['id'])->update($moduleData);
            } else {
                // Create new module
                $program->modules()->create($moduleData);
            }
        }
    }

    /**
     * Update requirements for a program
     */
    private function updateRequirements(InternshipProgram $program, array $requirements): void
    {
        $existingIds = collect($requirements)->pluck('id')->filter();
        
        // Delete requirements not in the request
        $program->requirements()->whereNotIn('id', $existingIds)->delete();

        foreach ($requirements as $requirementData) {
            if (isset($requirementData['id'])) {
                // Update existing requirement
                $program->requirements()->where('id', $requirementData['id'])->update($requirementData);
            } else {
                // Create new requirement
                $program->requirements()->create($requirementData);
            }
        }
    }

    /**
     * Update goals for a program
     */
    private function updateGoals(InternshipProgram $program, array $goals): void
    {
        $existingIds = collect($goals)->pluck('id')->filter();
        
        // Delete goals not in the request
        $program->goals()->whereNotIn('id', $existingIds)->delete();

        foreach ($goals as $goalData) {
            if (isset($goalData['id'])) {
                // Update existing goal
                $program->goals()->where('id', $goalData['id'])->update($goalData);
            } else {
                // Create new goal
                $program->goals()->create($goalData);
            }
        }
    }

    /**
     * Get applications for a specific program (admin only)
     */
    public function getApplications(InternshipProgram $internshipProgram): JsonResponse
    {
        $applications = $internshipProgram->applications()
            ->with(['user', 'reviewer'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'applications' => $applications
        ]);
    }

    /**
     * Get enrolled users for a specific program (admin only)
     */
    public function getEnrolledUsers(InternshipProgram $internshipProgram): JsonResponse
    {
        $enrolledUsers = $internshipProgram->enrolledUsers()
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'enrolled_users' => $enrolledUsers
        ]);
    }

    /**
     * Get program statistics (admin only)
     */
    public function getStats(InternshipProgram $internshipProgram): JsonResponse
    {
        $stats = [
            'total_applications' => $internshipProgram->applications()->count(),
            'pending_applications' => $internshipProgram->applications()->where('status', 'pending')->count(),
            'accepted_applications' => $internshipProgram->applications()->where('status', 'accepted')->count(),
            'rejected_applications' => $internshipProgram->applications()->where('status', 'rejected')->count(),
            'enrollment_percentage' => $internshipProgram->enrollment_percentage,
            'is_full' => $internshipProgram->is_full,
            'remaining_spots' => max(0, $internshipProgram->max_capacity - $internshipProgram->current_enrollment),
        ];

        return response()->json([
            'stats' => $stats
        ]);
    }

    /**
     * Send notification emails to all users about internship program
     */
    private function sendProgramNotifications(InternshipProgram $program, string $action = 'created'): void
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
            $title = match ($action) {
                'updated' => 'Staj proqramı yeniləndi',
                'deleted', 'cancelled' => 'Staj proqramı ləğv edildi',
                default => 'Yeni staj proqramı əlavə olundu',
            };

            $title = is_array($program->title) ? ($program->title['az'] ?? $program->title) : $program->title;
            $message = match ($action) {
                'updated' => "{$title} proqramında dəyişiklik oldu.",
                'deleted', 'cancelled' => "{$title} proqramı ləğv olundu.",
                default => "{$title} adlı yeni staj proqramı mövcuddur.",
            };

            foreach ($users as $user) {
                try {
                    $notificationService->send(
                        $user,
                        'training',
                        ['az' => $title],
                        ['az' => $message],
                        [
                            'data' => [
                                'internship_program_id' => $program->id,
                                'action' => $action,
                            ],
                            'mail' => new InternshipProgramNotification($program, $user, $action),
                        ]
                    );
                    $sentCount++;
                } catch (\Throwable $e) {
                    $failedCount++;
                    \Log::error('Failed to send internship program notification email', [
                        'email' => $user->email,
                        'program_id' => $program->id,
                        'action' => $action,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            \Log::info('Internship program notification emails sent', [
                'program_id' => $program->id,
                'action' => $action,
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
                'total_users' => $users->count()
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to send internship program notifications', [
                'program_id' => $program->id,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
        }
    }
}
