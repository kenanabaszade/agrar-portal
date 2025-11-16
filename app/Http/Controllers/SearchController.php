<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Training;
use App\Models\Meeting;
use App\Models\InternshipProgram;
use App\Models\User;
use App\Models\Exam;
use App\Models\EducationalContent;
use App\Models\ForumQuestion;
use App\Models\TrainingRegistration;
use App\Models\ExamRegistration;

class SearchController extends Controller
{
    /**
     * Global search across all content types
     */
    public function globalSearch(Request $request): JsonResponse
    {
        // Validation
        $validated = $request->validate([
            'q' => 'required|string|min:2|max:255',
            'lang' => 'nullable|string|in:az,en,ru',
            'exclude_types' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        // Parse parameters
        $query = trim($validated['q']);
        $lang = $validated['lang'] ?? 'az';
        $excludeTypes = !empty($validated['exclude_types'])
            ? explode(',', str_replace(' ', '', $validated['exclude_types']))
            : ['certificates']; // Default exclude certificates
        $limit = (int) ($validated['limit'] ?? 10);

        // Results array
        $results = [];

        // Search each type (if not excluded)
        if (!in_array('video_trainings', $excludeTypes)) {
            $results['video_trainings'] = $this->searchVideoTrainings($query, $lang, $limit);
        }

        if (!in_array('online_trainings', $excludeTypes)) {
            $results['online_trainings'] = $this->searchOnlineTrainings($query, $lang, $limit);
        }

        if (!in_array('onsite_trainings', $excludeTypes)) {
            $results['onsite_trainings'] = $this->searchOnsiteTrainings($query, $lang, $limit);
        }

        if (!in_array('webinars', $excludeTypes)) {
            $results['webinars'] = $this->searchWebinars($query, $lang, $limit);
        }

        if (!in_array('internship_programs', $excludeTypes)) {
            $results['internship_programs'] = $this->searchInternshipPrograms($query, $lang, $limit);
        }

        if (!in_array('trainers', $excludeTypes)) {
            $results['trainers'] = $this->searchTrainers($query, $lang, $limit);
        }

        if (!in_array('exams', $excludeTypes)) {
            $results['exams'] = $this->searchExams($query, $lang, $limit);
        }

        if (!in_array('articles', $excludeTypes)) {
            $results['articles'] = $this->searchArticles($query, $lang, $limit);
        }

        if (!in_array('guides', $excludeTypes)) {
            $results['guides'] = $this->searchGuides($query, $lang, $limit);
        }

        if (!in_array('qna', $excludeTypes)) {
            $results['qna'] = $this->searchQnA($query, $lang, $limit);
        }

        if (!in_array('results', $excludeTypes)) {
            $results['results'] = $this->searchResults($query, $lang, $limit);
        }

        // Ensure all types are present (even if empty)
        $allTypes = [
            'video_trainings', 'online_trainings', 'onsite_trainings',
            'webinars', 'internship_programs', 'trainers', 'exams',
            'articles', 'guides', 'qna', 'results'
        ];

        foreach ($allTypes as $type) {
            if (!isset($results[$type])) {
                $results[$type] = [];
            }
        }

        // Calculate total
        $total = array_sum(array_map('count', $results));

        // Return response
        return response()->json([
            'data' => $results,
            'meta' => [
                'query' => $query,
                'total' => $total,
                'excluded_types' => $excludeTypes,
            ],
        ]);
    }

    /**
     * Get translated field value
     */
    private function getTranslatedField($field, string $lang): ?string
    {
        if (is_null($field)) {
            return null;
        }

        // If field is already a string, return as is
        if (is_string($field)) {
            return $field;
        }

        // If field is array/object (JSON)
        if (is_array($field) || is_object($field)) {
            $fieldArray = (array) $field;

            // Try requested language first
            if (isset($fieldArray[$lang]) && !empty($fieldArray[$lang])) {
                return (string) $fieldArray[$lang];
            }

            // Fallback: az → en → ru → first available
            $fallbackOrder = ['az', 'en', 'ru'];
            foreach ($fallbackOrder as $fallbackLang) {
                if (isset($fieldArray[$fallbackLang]) && !empty($fieldArray[$fallbackLang])) {
                    return (string) $fieldArray[$fallbackLang];
                }
            }

            // If nothing found, return first available
            if (!empty($fieldArray)) {
                return (string) reset($fieldArray);
            }
        }

        return null;
    }

    /**
     * Search video trainings
     */
    private function searchVideoTrainings(string $query, string $lang, int $limit): array
    {
        $searchPattern = '%' . $query . '%';

        return Training::where('type', 'video')
            ->where(function ($q) use ($searchPattern) {
                $q->where(DB::raw("title::text"), 'ILIKE', $searchPattern)
                    ->orWhere(DB::raw("description::text"), 'ILIKE', $searchPattern)
                    ->orWhere('category', 'ILIKE', $searchPattern)
                    ->orWhereHas('trainer', function ($trainerQuery) use ($searchPattern) {
                        $trainerQuery->where('first_name', 'ILIKE', $searchPattern)
                            ->orWhere('last_name', 'ILIKE', $searchPattern);
                    });
            })
            ->with(['trainer:id,first_name,last_name', 'lessons' => function ($query) {
                $query->select('training_lessons.id', 'training_lessons.module_id', 'training_lessons.duration_minutes');
            }])
            ->limit($limit)
            ->get()
            ->map(function ($training) use ($lang) {
                $imageUrl = null;
                if ($training->media_files && is_array($training->media_files)) {
                    foreach ($training->media_files as $media) {
                        if (isset($media['type']) && $media['type'] === 'banner') {
                            $imageUrl = $media['url'] ?? (isset($media['path']) ? Storage::url($media['path']) : null);
                            break;
                        }
                    }
                }

                $trainer = null;
                if ($training->trainer) {
                    $trainer = [
                        'id' => $training->trainer->id,
                        'first_name' => $this->getTranslatedField($training->trainer->first_name, $lang),
                        'last_name' => $this->getTranslatedField($training->trainer->last_name, $lang),
                    ];
                }

                // Category is stored as string in trainings table
                $categoryName = $training->category;

                return [
                    'id' => $training->id,
                    'title' => $this->getTranslatedField($training->title, $lang),
                    'description' => $this->getTranslatedField($training->description, $lang),
                    'category' => $categoryName,
                    'image' => $imageUrl,
                    'trainer' => $trainer,
                    'difficulty' => $training->difficulty,
                    'duration' => $this->calculateTrainingDuration($training),
                ];
            })
            ->toArray();
    }

    /**
     * Search online trainings
     */
    private function searchOnlineTrainings(string $query, string $lang, int $limit): array
    {
        $searchPattern = '%' . $query . '%';

        return Training::where('type', 'online')
            ->where(function ($q) use ($searchPattern) {
                $q->where(DB::raw("title::text"), 'ILIKE', $searchPattern)
                    ->orWhere(DB::raw("description::text"), 'ILIKE', $searchPattern)
                    ->orWhere('category', 'ILIKE', $searchPattern)
                    ->orWhereHas('trainer', function ($trainerQuery) use ($searchPattern) {
                        $trainerQuery->where('first_name', 'ILIKE', $searchPattern)
                            ->orWhere('last_name', 'ILIKE', $searchPattern);
                    });
            })
            ->with(['trainer:id,first_name,last_name', 'lessons' => function ($query) {
                $query->select('training_lessons.id', 'training_lessons.module_id', 'training_lessons.duration_minutes');
            }])
            ->limit($limit)
            ->get()
            ->map(function ($training) use ($lang) {
                $imageUrl = null;
                if ($training->media_files && is_array($training->media_files)) {
                    foreach ($training->media_files as $media) {
                        if (isset($media['type']) && $media['type'] === 'banner') {
                            $imageUrl = $media['url'] ?? (isset($media['path']) ? Storage::url($media['path']) : null);
                            break;
                        }
                    }
                }

                $trainer = null;
                if ($training->trainer) {
                    $trainer = [
                        'id' => $training->trainer->id,
                        'first_name' => $this->getTranslatedField($training->trainer->first_name, $lang),
                        'last_name' => $this->getTranslatedField($training->trainer->last_name, $lang),
                    ];
                }

                // Category is stored as string in trainings table
                $categoryName = $training->category;

                return [
                    'id' => $training->id,
                    'title' => $this->getTranslatedField($training->title, $lang),
                    'description' => $this->getTranslatedField($training->description, $lang),
                    'category' => $categoryName,
                    'image' => $imageUrl,
                    'trainer' => $trainer,
                    'difficulty' => $training->difficulty,
                    'duration' => $this->calculateTrainingDuration($training),
                ];
            })
            ->toArray();
    }

    /**
     * Search onsite trainings
     */
    private function searchOnsiteTrainings(string $query, string $lang, int $limit): array
    {
        $searchPattern = '%' . $query . '%';

        return Training::where('type', 'offline')
            ->where(function ($q) use ($searchPattern) {
                $q->where(DB::raw("title::text"), 'ILIKE', $searchPattern)
                    ->orWhere(DB::raw("description::text"), 'ILIKE', $searchPattern)
                    ->orWhere('category', 'ILIKE', $searchPattern)
                    ->orWhereHas('trainer', function ($trainerQuery) use ($searchPattern) {
                        $trainerQuery->where('first_name', 'ILIKE', $searchPattern)
                            ->orWhere('last_name', 'ILIKE', $searchPattern);
                    });
            })
            ->with(['trainer:id,first_name,last_name', 'lessons' => function ($query) {
                $query->select('training_lessons.id', 'training_lessons.module_id', 'training_lessons.duration_minutes');
            }])
            ->limit($limit)
            ->get()
            ->map(function ($training) use ($lang) {
                $imageUrl = null;
                if ($training->media_files && is_array($training->media_files)) {
                    foreach ($training->media_files as $media) {
                        if (isset($media['type']) && $media['type'] === 'banner') {
                            $imageUrl = $media['url'] ?? (isset($media['path']) ? Storage::url($media['path']) : null);
                            break;
                        }
                    }
                }

                $trainer = null;
                if ($training->trainer) {
                    $trainer = [
                        'id' => $training->trainer->id,
                        'first_name' => $this->getTranslatedField($training->trainer->first_name, $lang),
                        'last_name' => $this->getTranslatedField($training->trainer->last_name, $lang),
                    ];
                }

                // Category is stored as string in trainings table
                $categoryName = $training->category;

                return [
                    'id' => $training->id,
                    'title' => $this->getTranslatedField($training->title, $lang),
                    'description' => $this->getTranslatedField($training->description, $lang),
                    'category' => $categoryName,
                    'image' => $imageUrl,
                    'trainer' => $trainer,
                    'difficulty' => $training->difficulty,
                    'duration' => $this->calculateTrainingDuration($training),
                ];
            })
            ->toArray();
    }

    /**
     * Search webinars (meetings)
     */
    private function searchWebinars(string $query, string $lang, int $limit): array
    {
        $searchPattern = '%' . $query . '%';

        return Meeting::where(function ($q) use ($searchPattern) {
                $q->where(DB::raw("title::text"), 'ILIKE', $searchPattern)
                    ->orWhere(DB::raw("description::text"), 'ILIKE', $searchPattern)
                    ->orWhereHas('trainer', function ($trainerQuery) use ($searchPattern) {
                        $trainerQuery->where('first_name', 'ILIKE', $searchPattern)
                            ->orWhere('last_name', 'ILIKE', $searchPattern);
                    });
            })
            ->with(['trainer:id,first_name,last_name'])
            ->limit($limit)
            ->get()
            ->map(function ($meeting) use ($lang) {
                $trainer = null;
                if ($meeting->trainer) {
                    $firstName = $this->getTranslatedField($meeting->trainer->first_name, $lang);
                    $lastName = $this->getTranslatedField($meeting->trainer->last_name, $lang);
                    $trainer = [
                        'name' => trim(($firstName ?? '') . ' ' . ($lastName ?? '')),
                        'id' => $meeting->trainer->id,
                    ];
                }

                $statusLabels = [
                    'scheduled' => ['az' => 'Gözlənilir', 'en' => 'Scheduled', 'ru' => 'Запланировано'],
                    'live' => ['az' => 'Canlı', 'en' => 'Live', 'ru' => 'В прямом эфире'],
                    'ended' => ['az' => 'Bitdi', 'en' => 'Ended', 'ru' => 'Завершено'],
                    'cancelled' => ['az' => 'Ləğv edildi', 'en' => 'Cancelled', 'ru' => 'Отменено'],
                ];

                $statusLabel = $statusLabels[$meeting->status][$lang] ?? $statusLabels[$meeting->status]['az'] ?? $meeting->status;

                return [
                    'id' => $meeting->id,
                    'title' => $this->getTranslatedField($meeting->title, $lang),
                    'description' => $this->getTranslatedField($meeting->description, $lang),
                    'trainer' => $trainer,
                    'status' => [
                        'status' => $meeting->status,
                        'label' => $statusLabel,
                    ],
                ];
            })
            ->toArray();
    }

    /**
     * Search internship programs
     */
    private function searchInternshipPrograms(string $query, string $lang, int $limit): array
    {
        $searchPattern = '%' . $query . '%';

        return InternshipProgram::where(function ($q) use ($searchPattern) {
                $q->where(DB::raw("title::text"), 'ILIKE', $searchPattern)
                    ->orWhere(DB::raw("description::text"), 'ILIKE', $searchPattern)
                    ->orWhere('category', 'ILIKE', $searchPattern);
            })
            ->limit($limit)
            ->get()
            ->map(function ($program) use ($lang) {
                return [
                    'id' => $program->id,
                    'title' => $this->getTranslatedField($program->title, $lang),
                    'description' => $this->getTranslatedField($program->description, $lang),
                    'category' => $program->category, // Category is stored as string
                    'company_name' => $program->instructor_name ? $this->getTranslatedField($program->instructor_name, $lang) : null,
                ];
            })
            ->toArray();
    }

    /**
     * Search trainers
     */
    private function searchTrainers(string $query, string $lang, int $limit): array
    {
        $searchPattern = '%' . $query . '%';

        return User::where('user_type', 'trainer')
            ->where(function ($q) use ($searchPattern) {
                $q->where('first_name', 'ILIKE', $searchPattern)
                    ->orWhere('last_name', 'ILIKE', $searchPattern)
                    ->orWhere('region', 'ILIKE', $searchPattern);
            })
            ->limit($limit)
            ->get()
            ->map(function ($user) use ($lang) {
                $trainerDescription = null;
                if (isset($user->trainer_description)) {
                    $trainerDescription = $this->getTranslatedField($user->trainer_description, $lang);
                }

                return [
                    'id' => $user->id,
                    'first_name' => $this->getTranslatedField($user->first_name, $lang),
                    'last_name' => $this->getTranslatedField($user->last_name, $lang),
                    'trainer_description' => $trainerDescription,
                    'region' => $this->getTranslatedField($user->region, $lang),
                ];
            })
            ->toArray();
    }

    /**
     * Search exams
     */
    private function searchExams(string $query, string $lang, int $limit): array
    {
        $searchPattern = '%' . $query . '%';

        return Exam::where(function ($q) use ($searchPattern) {
                $q->where(DB::raw("title::text"), 'ILIKE', $searchPattern)
                    ->orWhere(DB::raw("description::text"), 'ILIKE', $searchPattern)
                    ->orWhere('category', 'ILIKE', $searchPattern);
            })
            ->limit($limit)
            ->get()
            ->map(function ($exam) use ($lang) {
                return [
                    'id' => $exam->id,
                    'title' => $this->getTranslatedField($exam->title, $lang),
                    'description' => $this->getTranslatedField($exam->description, $lang),
                    'category' => $exam->category, // Category is stored as string
                ];
            })
            ->toArray();
    }

    /**
     * Search articles
     */
    private function searchArticles(string $query, string $lang, int $limit): array
    {
        $searchPattern = '%' . $query . '%';

        return EducationalContent::where('type', 'meqale')
            ->where(function ($q) use ($searchPattern) {
                $q->where(DB::raw("title::text"), 'ILIKE', $searchPattern)
                    ->orWhere(DB::raw("short_description::text"), 'ILIKE', $searchPattern)
                    ->orWhere(DB::raw("body_html::text"), 'ILIKE', $searchPattern)
                    ->orWhere('category', 'ILIKE', $searchPattern);
            })
            ->limit($limit)
            ->get()
            ->map(function ($content) use ($lang) {
                return [
                    'id' => $content->id,
                    'title' => $this->getTranslatedField($content->title, $lang),
                    'short_description' => $this->getTranslatedField($content->short_description, $lang),
                    'category' => $content->category, // Category is stored as string
                ];
            })
            ->toArray();
    }

    /**
     * Search guides
     */
    private function searchGuides(string $query, string $lang, int $limit): array
    {
        $searchPattern = '%' . $query . '%';

        return EducationalContent::where('type', 'telimat')
            ->where(function ($q) use ($searchPattern) {
                $q->where(DB::raw("title::text"), 'ILIKE', $searchPattern)
                    ->orWhere(DB::raw("description::text"), 'ILIKE', $searchPattern)
                    ->orWhere('category', 'ILIKE', $searchPattern);
            })
            ->limit($limit)
            ->get()
            ->map(function ($content) use ($lang) {
                return [
                    'id' => $content->id,
                    'title' => $this->getTranslatedField($content->title, $lang),
                    'description' => $this->getTranslatedField($content->description, $lang),
                    'category' => $content->category, // Category is stored as string
                ];
            })
            ->toArray();
    }

    /**
     * Search QnA (forum questions)
     */
    private function searchQnA(string $query, string $lang, int $limit): array
    {
        $searchPattern = '%' . $query . '%';

        return ForumQuestion::where(function ($q) use ($searchPattern) {
                $q->where(DB::raw("title::text"), 'ILIKE', $searchPattern)
                    ->orWhere(DB::raw("body::text"), 'ILIKE', $searchPattern)
                    ->orWhere(DB::raw("summary::text"), 'ILIKE', $searchPattern)
                    ->orWhere('category', 'ILIKE', $searchPattern);
            })
            ->limit($limit)
            ->get()
            ->map(function ($question) use ($lang) {
                return [
                    'id' => $question->id,
                    'title' => $this->getTranslatedField($question->title, $lang),
                    'body' => $this->getTranslatedField($question->body, $lang),
                    'category' => $question->category, // Category is stored as string
                ];
            })
            ->toArray();
    }

    /**
     * Search results (training and exam results)
     */
    private function searchResults(string $query, string $lang, int $limit): array
    {
        $searchPattern = '%' . $query . '%';
        $halfLimit = max(1, (int) ($limit / 2));

        // Training results - check status = 'completed' or has certificate
        $trainingResults = TrainingRegistration::where(function ($q) {
                $q->where('status', 'completed')
                    ->orWhereNotNull('certificate_id');
            })
            ->whereHas('training', function ($trainingQuery) use ($searchPattern) {
                $trainingQuery->where(DB::raw("title::text"), 'ILIKE', $searchPattern)
                    ->orWhere('category', 'ILIKE', $searchPattern);
            })
            ->with(['training:id,title,category'])
            ->limit($halfLimit)
            ->get()
            ->map(function ($registration) use ($lang) {
                if (!$registration->training) {
                    return null;
                }

                // Calculate completion percentage from UserTrainingProgress
                $progress = \App\Models\UserTrainingProgress::where('user_id', $registration->user_id)
                    ->where('training_id', $registration->training_id)
                    ->get();
                
                // Get total lessons count (need to load relationship)
                $totalLessons = $registration->training->load('lessons')->lessons->count();
                $completedLessons = $progress->where('status', 'completed')->count();
                $completionPercentage = $totalLessons > 0 
                    ? round(($completedLessons / $totalLessons) * 100) 
                    : ($registration->status === 'completed' ? 100 : 0);

                return [
                    'id' => $registration->id,
                    'course' => [
                        'title' => $this->getTranslatedField($registration->training->title, $lang),
                        'category' => $registration->training->category, // Category is stored as string
                    ],
                    'score' => $completionPercentage,
                    'completed_at' => $registration->updated_at?->toIso8601String(),
                    'type' => 'training',
                ];
            })
            ->filter()
            ->values();

        // Exam results
        $examResults = ExamRegistration::whereIn('status', ['completed', 'passed'])
            ->whereHas('exam', function ($examQuery) use ($searchPattern) {
                $examQuery->where(DB::raw("title::text"), 'ILIKE', $searchPattern)
                    ->orWhere('category', 'ILIKE', $searchPattern);
            })
            ->with(['exam:id,title,category'])
            ->limit($halfLimit)
            ->get()
            ->map(function ($registration) use ($lang) {
                if (!$registration->exam) {
                    return null;
                }

                return [
                    'id' => $registration->id,
                    'course' => [
                        'title' => $this->getTranslatedField($registration->exam->title, $lang),
                        'category' => $registration->exam->category, // Category is stored as string
                    ],
                    'score' => $registration->score ?? 0,
                    'completed_at' => $registration->finished_at?->toIso8601String(),
                    'type' => 'exam',
                ];
            })
            ->filter()
            ->values();

        // Merge and limit
        return $trainingResults->merge($examResults)
            ->take($limit)
            ->values()
            ->toArray();
    }

    /**
     * Calculate training duration from lessons
     */
    private function calculateTrainingDuration($training): ?int
    {
        // Ensure lessons are loaded
        if (!$training->relationLoaded('lessons')) {
            $training->load('lessons:id,module_id,duration_minutes');
        }

        if (!$training->lessons || $training->lessons->isEmpty()) {
            return null;
        }

        $totalMinutes = $training->lessons->sum(function ($lesson) {
            return $lesson->duration_minutes ?? 0;
        });

        return $totalMinutes > 0 ? $totalMinutes : null;
    }
}

