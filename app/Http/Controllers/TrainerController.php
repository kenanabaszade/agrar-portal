<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;

class TrainerController extends Controller
{
    /**
     * Create a new trainer
     * POST /api/v1/trainers
     */
    public function store(Request $request)
    {
        // Normalize request data before validation if needed
        // Handle trainer_category[az] and trainer_category[en] format
        if ($request->has('trainer_category') && is_array($request->input('trainer_category'))) {
            $trainerCategory = $request->input('trainer_category');
            // Already in array format, no need to normalize
        } elseif ($request->has('trainer_category.az') || $request->has('trainer_category.en')) {
            // Convert dot notation to array
            $trainerCategory = [];
            if ($request->has('trainer_category.az')) {
                $trainerCategory['az'] = $request->input('trainer_category.az');
            }
            if ($request->has('trainer_category.en')) {
                $trainerCategory['en'] = $request->input('trainer_category.en');
            }
            $request->merge(['trainer_category' => $trainerCategory]);
        }

        // Handle trainer_description as JSON string
        if ($request->has('trainer_description') && is_string($request->input('trainer_description'))) {
            $decoded = json_decode($request->input('trainer_description'), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $request->merge(['trainer_description' => $decoded]);
            }
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255', 'unique:users,username'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:50'],
            'how_did_you_hear' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
                          'phone' => ['nullable', 'string', 'max:50'],
              'password' => ['nullable', 'string', 'min:8'], // Optional - will auto-generate if not provided
              'profile_photo' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'gif', 'webp'])->max(2 * 1024)], // 2MB max
              'two_factor_enabled' => ['boolean'],
              // Trainer-specific fields
            'trainer_category' => ['nullable', new \App\Rules\TranslationRule(false)],
            'trainer_description' => ['nullable', new \App\Rules\TranslationRule(false)],
            'experience_years' => ['nullable', 'integer', 'min:0'],
            'experience_months' => ['nullable', 'integer', 'min:0', 'max:11'],
            'specializations' => ['nullable', 'array'],
            'qualifications' => ['nullable', 'array'],
        ]);

        // Generate password if not provided
        $password = $validated['password'] ?? Str::random(12);

        // Normalize trainer_category if provided
        if (isset($validated['trainer_category'])) {
            $validated['trainer_category'] = \App\Services\TranslationHelper::normalizeTranslation($validated['trainer_category']);
        }

        // Normalize trainer_description if provided
        if (isset($validated['trainer_description'])) {
            $validated['trainer_description'] = \App\Services\TranslationHelper::normalizeTranslation($validated['trainer_description']);
        }

        // Normalize specializations - convert simple strings to multilang format
        if (isset($validated['specializations']) && is_array($validated['specializations'])) {
            $normalizedSpecs = [];
            foreach ($validated['specializations'] as $spec) {
                if (is_array($spec) && isset($spec['az'])) {
                    // Already multilang format
                    $normalizedSpecs[] = $spec;
                } elseif (is_string($spec)) {
                    // Legacy format - convert to multilang
                    $normalizedSpecs[] = ['az' => $spec, 'en' => $spec];
                }
            }
            $validated['specializations'] = $normalizedSpecs;
        }

        // Normalize qualifications - convert simple strings to multilang format
        if (isset($validated['qualifications']) && is_array($validated['qualifications'])) {
            $normalizedQuals = [];
            foreach ($validated['qualifications'] as $qual) {
                if (is_array($qual) && isset($qual['az'])) {
                    // Already multilang format
                    $normalizedQuals[] = $qual;
                } elseif (is_string($qual)) {
                    // Legacy format - convert to multilang
                    $normalizedQuals[] = ['az' => $qual, 'en' => $qual];
                }
            }
            $validated['qualifications'] = $normalizedQuals;
        }

        // Create the trainer (automatically set user_type to 'trainer')
        $trainer = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'username' => $validated['username'] ?? null,
            'father_name' => $validated['father_name'] ?? null,
            'region' => $validated['region'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'how_did_you_hear' => $validated['how_did_you_hear'] ?? null,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password_hash' => Hash::make($password),
            'user_type' => 'trainer', // Always set to trainer for this endpoint
            'two_factor_enabled' => $validated['two_factor_enabled'] ?? false,
            'email_verified' => true, // Admin-created trainers are automatically verified
            'trainer_category' => $validated['trainer_category'] ?? null,
            'trainer_description' => $validated['trainer_description'] ?? null,
            'experience_years' => $validated['experience_years'] ?? null,
            'experience_months' => $validated['experience_months'] ?? null,
            'specializations' => $validated['specializations'] ?? null,
                          'qualifications' => $validated['qualifications'] ?? null,
          ]);

          // Handle profile photo upload if provided
          if ($request->hasFile('profile_photo')) {
              $file = $request->file('profile_photo');
              $filename = 'user_' . $trainer->id . '_' . time() . '.' . $file->getClientOriginalExtension();
              $path = $file->storeAs('profile_photos', $filename, 'public');
              
              // Update trainer with profile photo filename
              $trainer->update([
                  'profile_photo' => $filename
              ]);
          }

          // Send welcome email with credentials to the new trainer
        $emailSent = false;
        $emailError = null;
        
        try {
            $trainer->notify(new \App\Notifications\UserCreatedNotification(
                $validated['email'],
                $password,
                $request->user()->first_name . ' ' . $request->user()->last_name
            ));
            $emailSent = true;
        } catch (\Exception $e) {
            // Log the error but don't fail the request
            \Log::error('Failed to send welcome email to trainer', [
                'trainer_id' => $trainer->id,
                'email' => $validated['email'],
                'error' => $e->getMessage()
            ]);
            $emailError = $e->getMessage();
        }

        $message = $emailSent 
            ? 'Trainer created successfully and welcome email sent'
            : 'Trainer created successfully, but failed to send welcome email';

        $response = [
            'message' => $message,
            'trainer' => $trainer,
        ];

        if (!$emailSent && $emailError) {
            $response['email_error'] = 'Failed to send welcome email. Trainer can reset password via forgot password.';
        }

        return response()->json($response, 201);
    }

    /**
     * Get all trainers list
     * GET /api/v1/trainers
     */
    public function index(Request $request)
    {
        $query = User::where('user_type', 'trainer')
            ->select([
                'id',
                'first_name',
                'last_name',
                'profile_photo',
                'trainer_category',
                'specializations',
                'experience_years',
                'experience_months',
                'created_at'
            ]);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  // Search in trainer_category multilang field (both az and en)
                  ->orWhereRaw("trainer_category::text ILIKE ?", ["%{$search}%"])
                  ->orWhereRaw("trainer_category->>'az' ILIKE ?", ["%{$search}%"])
                  ->orWhereRaw("trainer_category->>'en' ILIKE ?", ["%{$search}%"])
                  // Search in specializations multilang field (both az and en)
                  ->orWhereRaw("JSON_SEARCH(specializations, 'one', ?) IS NOT NULL", ["%{$search}%"])
                  ->orWhereRaw("JSON_SEARCH(JSON_EXTRACT(specializations, '$[*].az'), 'one', ?) IS NOT NULL", ["%{$search}%"])
                  ->orWhereRaw("JSON_SEARCH(JSON_EXTRACT(specializations, '$[*].en'), 'one', ?) IS NOT NULL", ["%{$search}%"]);
            });
        }

        // Filter by trainer category (search in multilang field)
        if ($request->filled('trainer_category')) {
            $categoryFilter = $request->get('trainer_category');
            $query->where(function ($q) use ($categoryFilter) {
                $q->whereRaw("trainer_category->>'az' = ?", [$categoryFilter])
                  ->orWhereRaw("trainer_category->>'en' = ?", [$categoryFilter])
                  ->orWhereRaw("trainer_category::text ILIKE ?", ["%{$categoryFilter}%"]);
            });
        }

        // Count published trainings for each trainer
        $query->withCount([
            'trainings' => function ($q) {
                $q->where('status', 'published');
            }
        ]);

        // Sorting
        $sortBy = $request->get('sort_by', 'first_name');
        $sortOrder = $request->get('sort_order', 'asc');

        if (in_array($sortBy, ['first_name', 'last_name', 'trainer_category', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = min($request->get('per_page', 15), 100);
        $trainers = $query->paginate($perPage);

        // Get current locale for translations
        $locale = app()->getLocale() ?? 'az';

        // Transform trainers to add computed fields
        $trainers->getCollection()->transform(function ($trainer) use ($locale) {
            // Process trainer_category - support multilang
            $category = $trainer->trainer_category;
            $categoryFormatted = null;
            $categoryString = null;

            if ($category) {
                if (is_array($category) && isset($category['az'])) {
                    // Multilang format: {"az": "...", "en": "..."}
                    $categoryFormatted = $category;
                    $categoryString = $category[$locale] ?? $category['az'] ?? null;
                } elseif (is_string($category)) {
                    // Legacy format: simple string
                    $categoryFormatted = ['az' => $category, 'en' => $category];
                    $categoryString = $category;
                }
            }

            // Process specializations - support multilang
            $specializations = $trainer->specializations ?? [];
            $specializationsFormatted = [];
            $specializationsStrings = [];

            foreach ($specializations as $spec) {
                if (is_array($spec) && isset($spec['az'])) {
                    // Multilang format: {"az": "...", "en": "..."}
                    $specializationsFormatted[] = $spec;
                    $specializationsStrings[] = $spec[$locale] ?? $spec['az'] ?? '';
                } else {
                    // Legacy format: simple string
                    $specializationsFormatted[] = ['az' => $spec, 'en' => $spec];
                    $specializationsStrings[] = $spec;
                }
            }

            return [
                'id' => $trainer->id,
                'first_name' => $trainer->first_name,
                'last_name' => $trainer->last_name,
                'profile_photo_url' => $trainer->profile_photo_url,
                'trainer_category' => $categoryFormatted,
                'trainer_category_string' => $categoryString,
                'specializations' => $specializationsFormatted,
                'specializations_strings' => $specializationsStrings,
                'experience_years' => $trainer->experience_years ?? 0,
                'experience_months' => $trainer->experience_months ?? 0,
                'experience_formatted' => $trainer->experience_formatted,
                'trainings_count' => $trainer->trainings_count ?? 0,
            ];
        });

        return response()->json($trainers);
    }

    /**
     * Get trainer details
     * GET /api/v1/trainers/{id}
     */
    public function show($id)
    {
        // Validate that id is a valid integer
        if (!is_numeric($id) || $id === 'null' || $id === null) {
            return response()->json([
                'message' => 'Invalid trainer ID'
            ], 422);
        }

        $trainer = User::where('user_type', 'trainer')
            ->findOrFail((int) $id);

        // Load published trainings with full details
        $trainings = Training::where('trainer_id', $trainer->id)
            ->where('status', 'published')
            ->with(['modules.lessons', 'exam'])
            ->withCount(['registrations'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Transform trainings to include all necessary fields
        $trainingsTransformed = $trainings->map(function ($training) {
            // Count media files
            $mediaCounts = $this->countMediaFilesByType($training->media_files ?? []);

            // Count lesson media
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

            return [
                'id' => $training->id,
                'title' => $training->title,
                'description' => $training->description,
                'category' => $training->category,
                'start_date' => $training->start_date?->format('Y-m-d'),
                'end_date' => $training->end_date?->format('Y-m-d'),
                'difficulty' => $training->difficulty,
                'type' => $training->type,
                'status' => $training->status,
                'registrations_count' => $training->registrations_count ?? 0,
                'rating' => [
                    'average_rating' => $training->average_rating,
                    'ratings_count' => $training->ratings_count,
                ],
                'media_counts' => [
                    'videos' => $mediaCounts['videos'],
                    'documents' => $mediaCounts['documents'],
                    'images' => $mediaCounts['images'],
                    'audio' => $mediaCounts['audio'],
                    'total' => $mediaCounts['videos'] + $mediaCounts['documents'] + $mediaCounts['images'] + $mediaCounts['audio'],
                ],
            ];
        });

        // Get current locale for translations
        $locale = app()->getLocale() ?? 'az';

        // Process trainer_category - support multilang
        $category = $trainer->trainer_category;
        $categoryFormatted = null;
        $categoryString = null;

        if ($category) {
            if (is_array($category) && isset($category['az'])) {
                // Multilang format: {"az": "...", "en": "..."}
                $categoryFormatted = $category;
                $categoryString = $category[$locale] ?? $category['az'] ?? null;
            } elseif (is_string($category)) {
                // Legacy format: simple string
                $categoryFormatted = ['az' => $category, 'en' => $category];
                $categoryString = $category;
            }
        }

        // Process specializations - support multilang
        $specializations = $trainer->specializations ?? [];
        $specializationsFormatted = [];
        $specializationsStrings = [];

        foreach ($specializations as $spec) {
            if (is_array($spec) && isset($spec['az'])) {
                // Multilang format: {"az": "...", "en": "..."}
                $specializationsFormatted[] = $spec;
                $specializationsStrings[] = $spec[$locale] ?? $spec['az'] ?? '';
            } else {
                // Legacy format: simple string
                $specializationsFormatted[] = ['az' => $spec, 'en' => $spec];
                $specializationsStrings[] = $spec;
            }
        }

        // Process qualifications - support multilang
        $qualifications = $trainer->qualifications ?? [];
        $qualificationsFormatted = [];
        $qualificationsStrings = [];

        foreach ($qualifications as $qual) {
            if (is_array($qual) && isset($qual['az'])) {
                // Multilang format: {"az": "...", "en": "..."}
                $qualificationsFormatted[] = $qual;
                $qualificationsStrings[] = $qual[$locale] ?? $qual['az'] ?? '';
            } else {
                // Legacy format: simple string
                $qualificationsFormatted[] = ['az' => $qual, 'en' => $qual];
                $qualificationsStrings[] = $qual;
            }
        }

        // Format as comma-separated strings for convenience
        $specializationsString = !empty($specializationsStrings)
            ? implode(', ', $specializationsStrings)
            : null;
        $qualificationsString = !empty($qualificationsStrings)
            ? implode(', ', $qualificationsStrings)
            : null;

                  return response()->json([
              'id' => $trainer->id,
              'first_name' => $trainer->first_name,
              'last_name' => $trainer->last_name,
              'email' => $trainer->email,
              'profile_photo_url' => $trainer->profile_photo_url,
              'trainer_category' => $categoryFormatted,
              'trainer_category_string' => $categoryString,
              'trainer_description' => $trainer->trainer_description,
              'experience_years' => $trainer->experience_years ?? 0,
              'experience_months' => $trainer->experience_months ?? 0,
              'experience_formatted' => $trainer->experience_formatted,
              'specializations' => $specializationsFormatted,
              'specializations_strings' => $specializationsStrings,
              'specializations_string' => $specializationsString,
              'qualifications' => $qualificationsFormatted,
              'qualifications_strings' => $qualificationsStrings,
              'qualifications_string' => $qualificationsString,
              'created_at' => $trainer->created_at?->toISOString(),
              'trainer_rating' => [
                  'average_rating' => $trainer->trainer_average_rating,
                  'ratings_count' => $trainer->trainer_ratings_count,
              ],
              'trainings' => $trainingsTransformed,
              'trainings_count' => $trainings->count(),
          ]);
    }

    /**
     * Get trainers list for training create/edit dropdown
     * GET /api/v1/trainers/list-for-training
     * 
     * Returns simple list of trainers (id, first_name, last_name) for dropdown usage
     * Admin only endpoint
     */
    public function listForTraining(Request $request)
    {
        $trainers = User::select('id', 'first_name', 'last_name')
            ->where('user_type', 'trainer')
            ->where('is_active', true)
            ->orderBy('first_name', 'asc')
            ->get();

        return response()->json([
            'status_code' => 200,
            'data' => $trainers
        ], 200);
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
            }
        }

        return $counts;
    }
}
