<?php

namespace App\Http\Controllers;

use App\Models\UserTrainingProgress;
use Illuminate\Http\Request;
 
class ProgressController extends Controller
{
   
    public function index(Request $request)
    {
        return UserTrainingProgress::where('user_id', $request->user()->id)->paginate(50);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'training_id' => ['required', 'exists:trainings,id'],
            'module_id' => ['required', 'exists:training_modules,id'],
            'lesson_id' => ['required', 'exists:training_lessons,id'],
            'status' => ['required', 'in:not_started,in_progress,completed'],
            'last_accessed' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'time_spent' => ['nullable', 'integer', 'min:0'],
        ]);

        $progress = UserTrainingProgress::updateOrCreate([
            'user_id' => $request->user()->id,
            'lesson_id' => $validated['lesson_id'],
        ], array_merge($validated, ['user_id' => $request->user()->id]));

        return response()->json($progress, 201);
    }

    /**
     * Get specific progress record
     * GET /api/v1/progress/{progress}
     */
    public function show(UserTrainingProgress $progress)
    {
        // Ensure user can only access their own progress
        if ($progress->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($progress);
    }

    /**
     * Update progress record
     * PUT /api/v1/progress/{progress}
     */
    public function update(Request $request, UserTrainingProgress $progress)
    {
        // Ensure user can only update their own progress
        if ($progress->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => ['sometimes', 'in:not_started,in_progress,completed'],
            'last_accessed' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'time_spent' => ['nullable', 'integer', 'min:0'],
        ]);

        $progress->update($validated);

        return response()->json($progress);
    }

    /**
     * Delete progress record
     * DELETE /api/v1/progress/{progress}
     */
    public function destroy(UserTrainingProgress $progress)
    {
        // Ensure user can only delete their own progress
        if ($progress->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $progress->delete();

        return response()->json(['message' => 'Progress record deleted successfully']);
    }

    /**
     * Add or update notes for a specific lesson
     * POST /api/v1/lessons/{lesson}/notes
     */
    public function addLessonNotes(Request $request, \App\Models\TrainingLesson $lesson)
    {
        $user = auth()->user();
        
        // Check if training is video type (notes only allowed for video trainings)
        $training = $lesson->module->training;
        if ($training->type !== 'video') {
            return response()->json(['message' => 'Notes are only allowed for video trainings.'], 403);
        }

        $validated = $request->validate([
            'notes' => ['required', 'string', 'max:5000'],
        ]);

        $progress = UserTrainingProgress::updateOrCreate([
            'user_id' => $user->id,
            'training_id' => $training->id,
            'module_id' => $lesson->module_id,
            'lesson_id' => $lesson->id,
        ], [
            'status' => 'in_progress', // Mark as in progress if not completed
            'notes' => $validated['notes'],
            'last_accessed' => now(),
        ]);

        return response()->json([
            'message' => 'Notes saved successfully',
            'progress' => $progress
        ]);
    }

    /**
     * Get notes for a specific lesson
     * GET /api/v1/lessons/{lesson}/notes
     */
    public function getLessonNotes(\App\Models\TrainingLesson $lesson)
    {
        $user = auth()->user();
        
        // Check if training is video type (notes only allowed for video trainings)
        $training = $lesson->module->training;
        if ($training->type !== 'video') {
            return response()->json(['message' => 'Notes are only allowed for video trainings.'], 403);
        }

        $progress = UserTrainingProgress::where([
            'user_id' => $user->id,
            'training_id' => $training->id,
            'module_id' => $lesson->module_id,
            'lesson_id' => $lesson->id,
        ])->first();

        return response()->json([
            'lesson_id' => $lesson->id,
            'lesson_title' => $lesson->title,
            'lesson_type' => $lesson->lesson_type,
            'training_type' => $training->type,
            'notes' => $progress ? $progress->notes : null,
            'status' => $progress ? $progress->status : 'not_started',
            'last_accessed' => $progress ? $progress->last_accessed : null,
        ]);
    }

    /**
     * Update notes for a specific lesson
     * PUT /api/v1/lessons/{lesson}/notes
     */
    public function updateLessonNotes(Request $request, \App\Models\TrainingLesson $lesson)
    {
        $user = auth()->user();
        
        // Check if training is video type (notes only allowed for video trainings)
        $training = $lesson->module->training;
        if ($training->type !== 'video') {
            return response()->json(['message' => 'Notes are only allowed for video trainings.'], 403);
        }

        $validated = $request->validate([
            'notes' => ['required', 'string', 'max:5000'],
        ]);

        $progress = UserTrainingProgress::where([
            'user_id' => $user->id,
            'training_id' => $training->id,
            'module_id' => $lesson->module_id,
            'lesson_id' => $lesson->id,
        ])->first();

        if (!$progress) {
            return response()->json(['message' => 'No progress record found for this lesson'], 404);
        }

        $progress->update([
            'notes' => $validated['notes'],
            'last_accessed' => now(),
        ]);

        return response()->json([
            'message' => 'Notes updated successfully',
            'progress' => $progress
        ]);
    }

    /**
     * Delete notes for a specific lesson
     * DELETE /api/v1/lessons/{lesson}/notes
     */
    public function deleteLessonNotes(\App\Models\TrainingLesson $lesson)
    {
        $user = auth()->user();
        
        // Check if training is video type (notes only allowed for video trainings)
        $training = $lesson->module->training;
        if ($training->type !== 'video') {
            return response()->json(['message' => 'Notes are only allowed for video trainings.'], 403);
        }

        $progress = UserTrainingProgress::where([
            'user_id' => $user->id,
            'training_id' => $training->id,
            'module_id' => $lesson->module_id,
            'lesson_id' => $lesson->id,
        ])->first();

        if (!$progress) {
            return response()->json(['message' => 'No progress record found for this lesson'], 404);
        }

        $progress->update([
            'notes' => null,
            'last_accessed' => now(),
        ]);

        return response()->json([
            'message' => 'Notes deleted successfully',
            'progress' => $progress
        ]);
    }

    /**
     * Get comprehensive user progress/results
     * GET /api/v1/my/results?period=today|week|year|all
     */
    public function myResults(Request $request)
    {
        $user = $request->user();
        $period = $request->input('period', 'all'); // today, week, year, all
        
        // Calculate date range based on period
        $startDate = null;
        $endDate = now();
        
        switch ($period) {
            case 'today':
                $startDate = now()->startOfDay();
                break;
            case 'week':
                $startDate = now()->startOfWeek();
                break;
            case 'year':
                $startDate = now()->startOfYear();
                break;
            case 'all':
            default:
                $startDate = null; // No limit
                break;
        }
        
        // Key Metrics - Completed Courses
        // A course is completed if:
        // 1. TrainingRegistration status = 'completed', OR
        // 2. User has a certificate for that training (even if registration status is 'approved')
        $completedTrainingsFromRegistrations = \App\Models\TrainingRegistration::where('user_id', $user->id)
            ->where('status', 'completed');
        if ($startDate) {
            $completedTrainingsFromRegistrations->where('updated_at', '>=', $startDate);
        }
        $completedTrainingsIds1 = $completedTrainingsFromRegistrations->pluck('training_id')->toArray();
        
        $completedTrainingsFromCertificates = \App\Models\Certificate::where('user_id', $user->id)
            ->whereNotNull('related_training_id')
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                  ->orWhereDate('expiry_date', '>=', now()->toDateString());
            });
        if ($startDate) {
            $completedTrainingsFromCertificates->where('created_at', '>=', $startDate);
        }
        $completedTrainingsIds2 = $completedTrainingsFromCertificates->pluck('related_training_id')->unique()->toArray();
        
        // Merge and get unique IDs
        if ($startDate) {
            // If date filter applied, only count trainings completed within the period
            $allCompletedTrainingIds = array_unique(array_merge($completedTrainingsIds1, $completedTrainingsIds2));
        } else {
            // If no date filter, get all completed trainings (from registrations OR certificates)
            $allCompletedRegIds = \App\Models\TrainingRegistration::where('user_id', $user->id)
                ->where('status', 'completed')
                ->pluck('training_id')
                ->toArray();
            
            $allCompletedCertIds = \App\Models\Certificate::where('user_id', $user->id)
                ->whereNotNull('related_training_id')
                ->where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('expiry_date')
                      ->orWhereDate('expiry_date', '>=', now()->toDateString());
                })
                ->pluck('related_training_id')
                ->unique()
                ->toArray();
            
            $allCompletedTrainingIds = array_unique(array_merge($allCompletedRegIds, $allCompletedCertIds));
        }
        
        $completedCourses = count($allCompletedTrainingIds);
        
        // Ongoing Courses
        // A course is ongoing if:
        // 1. TrainingRegistration status = 'approved' AND training_id NOT in completed list, OR
        // 2. Video training with progress but no certificate and not completed
        $ongoingTrainingsQuery = \App\Models\TrainingRegistration::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereNotIn('training_id', $allCompletedTrainingIds);
        
        if ($startDate) {
            $ongoingTrainingsQuery->where('updated_at', '>=', $startDate);
        }
        
        $ongoingRegistrations = $ongoingTrainingsQuery->pluck('training_id')->toArray();
        
        // Also include video trainings that user has progress on but no certificate yet and not completed
        $videoTrainingIds = \App\Models\UserTrainingProgress::where('user_id', $user->id)
            ->distinct()
            ->pluck('training_id')
            ->toArray();
        
        $videoTrainingsWithProgress = \App\Models\Training::where('type', 'video')
            ->whereIn('id', $videoTrainingIds)
            ->whereNotIn('id', $allCompletedTrainingIds)
            ->pluck('id')
            ->toArray();
        
        $ongoingCoursesIds = array_unique(array_merge($ongoingRegistrations, $videoTrainingsWithProgress));
        $ongoingCourses = count($ongoingCoursesIds);
        
        // Certificates Earned (only active, non-expired certificates)
        $certificatesEarnedQuery = \App\Models\Certificate::where('user_id', $user->id)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                  ->orWhereDate('expiry_date', '>=', now()->toDateString());
            });
        if ($startDate) {
            $certificatesEarnedQuery->where('created_at', '>=', $startDate);
        }
        $certificatesEarned = $certificatesEarnedQuery->count();
        
        // Webinar Participation
        $webinarParticipationQuery = \App\Models\MeetingRegistration::where('user_id', $user->id)
            ->where('status', 'attended');
        if ($startDate) {
            $webinarParticipationQuery->where('attended_at', '>=', $startDate);
        }
        $webinarParticipation = $webinarParticipationQuery->count();
        
        // Learning Progress - Get ALL trainings user has access to (approved registrations + video trainings with progress)
        $registeredTrainings = \App\Models\TrainingRegistration::where('user_id', $user->id)
            ->where('status', 'approved')
            ->pluck('training_id');
        
        // Get all video training IDs where user has progress
        $videoTrainingIds = \App\Models\UserTrainingProgress::where('user_id', $user->id)
            ->distinct()
            ->pluck('training_id')
            ->toArray();
        
        $videoTrainings = \App\Models\Training::where('type', 'video')
            ->whereIn('id', $videoTrainingIds)
            ->pluck('id');
        
        $allUserTrainingIds = $registeredTrainings->merge($videoTrainings)->unique();
        
        $trainings = \App\Models\Training::whereIn('id', $allUserTrainingIds)
            ->with(['modules.lessons'])
            ->get();
        
        $totalLessons = 0;
        $completedLessons = 0;
        
        foreach ($trainings as $training) {
            if ($training->modules) {
                foreach ($training->modules as $module) {
                    if ($module->lessons) {
                        foreach ($module->lessons as $lesson) {
                            $totalLessons++;
                            $progress = \App\Models\UserTrainingProgress::where('user_id', $user->id)
                                ->where('training_id', $training->id)
                                ->where('lesson_id', $lesson->id)
                                ->where('status', 'completed')
                                ->first();
                            if ($progress) {
                                $completedLessons++;
                            }
                        }
                    }
                }
            }
        }
        
        $overallProgress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100, 1) : 0;
        
        // Total hours spent (from time_spent in seconds, convert to hours)
        $allProgress = \App\Models\UserTrainingProgress::where('user_id', $user->id);
        if ($startDate) {
            $allProgress->where('updated_at', '>=', $startDate);
        }
        $allProgressData = $allProgress->get();
        
        // Cast to integer since time_spent is stored as text in PostgreSQL
        $totalSeconds = $allProgressData->sum(function ($item) {
            return (int)($item->time_spent ?? 0);
        }) ?? 0;
        $totalHours = round($totalSeconds / 3600, 2);
        
        // Daily streak (consecutive days with activity)
        $streak = $this->calculateDailyStreak($user->id, $startDate);
        
        // Weekly and monthly goals
        $weeklyGoal = [
            'hours' => 5, // hours per week
            'progress' => $this->calculateWeeklyProgress($user->id),
        ];
        
        $monthlyGoal = [
            'hours' => 20, // hours per month
            'progress' => $this->calculateMonthlyProgress($user->id),
        ];
        
        // Performance Analytics
        $examRegistrationsQuery = \App\Models\ExamRegistration::where('user_id', $user->id)
            ->whereIn('status', ['passed', 'failed']);
        if ($startDate) {
            $examRegistrationsQuery->where('finished_at', '>=', $startDate);
        }
        $examRegistrations = $examRegistrationsQuery->get();
        
        $averageScore = $examRegistrations->where('score', '!=', null)->avg('score') ?? 0;
        $averageScore = round($averageScore, 1);
        
        // Learning effectiveness (based on pass rate)
        $totalExams = $examRegistrations->count();
        $passedExams = $examRegistrations->where('status', 'passed')->count();
        $learningEffectiveness = $totalExams > 0 ? round(($passedExams / $totalExams) * 100, 1) : 0;
        
        // Knowledge retention rate (compare recent scores vs earlier scores)
        $knowledgeRetentionRate = $this->calculateKnowledgeRetentionRate($user->id, $startDate);
        
        // Latest Achievements
        $achievements = $this->getLatestAchievements($user->id, $startDate);
        
        // Course Progress List
        $courseProgress = $this->getCourseProgressList($user->id, $startDate);
        
        return response()->json([
            'period' => $period,
            'key_metrics' => [
                'completed_courses' => $completedCourses,
                'ongoing_courses' => $ongoingCourses,
                'certificates_earned' => $certificatesEarned,
                'webinar_participation' => $webinarParticipation,
            ],
            'learning_progress' => [
                'overall_progress' => $overallProgress,
                'weekly_goal' => $weeklyGoal,
                'monthly_goal' => $monthlyGoal,
                'total_hours' => $totalHours,
                'daily_streak' => $streak,
            ],
            'performance_analytics' => [
                'average_score' => $averageScore,
                'learning_effectiveness' => $learningEffectiveness,
                'knowledge_retention_rate' => $knowledgeRetentionRate,
                'improvement_message' => $this->getImprovementMessage($averageScore, $learningEffectiveness),
            ],
            'latest_achievements' => $achievements,
            'course_progress' => $courseProgress,
        ]);
    }
    
    /**
     * Calculate daily streak
     */
    private function calculateDailyStreak($userId, $startDate = null)
    {
        $query = \App\Models\UserTrainingProgress::where('user_id', $userId)
            ->where('updated_at', '>=', now()->subDays(30)) // Check last 30 days
            ->selectRaw('DATE(updated_at) as date')
            ->groupBy('date')
            ->orderByDesc('date')
            ->get();
        
        if ($query->isEmpty()) {
            return 0;
        }
        
        $streak = 0;
        $expectedDate = now()->toDateString();
        
        foreach ($query as $record) {
            $recordDate = \Carbon\Carbon::parse($record->date)->toDateString();
            if ($recordDate === $expectedDate) {
                $streak++;
                $expectedDate = \Carbon\Carbon::parse($expectedDate)->subDay()->toDateString();
            } else {
                break;
            }
        }
        
        return $streak;
    }
    
    /**
     * Calculate weekly progress
     */
    private function calculateWeeklyProgress($userId)
    {
        $weekStart = now()->startOfWeek();
        // Cast time_spent to integer for PostgreSQL compatibility
        $totalSeconds = \App\Models\UserTrainingProgress::where('user_id', $userId)
            ->where('updated_at', '>=', $weekStart)
            ->selectRaw('SUM(CAST(time_spent AS INTEGER)) as total')
            ->value('total') ?? 0;
        
        $hours = round($totalSeconds / 3600, 2);
        $goal = 5; // 5 hours per week
        
        return round(($hours / $goal) * 100, 1);
    }
    
    /**
     * Calculate monthly progress
     */
    private function calculateMonthlyProgress($userId)
    {
        $monthStart = now()->startOfMonth();
        // Cast time_spent to integer for PostgreSQL compatibility
        $totalSeconds = \App\Models\UserTrainingProgress::where('user_id', $userId)
            ->where('updated_at', '>=', $monthStart)
            ->selectRaw('SUM(CAST(time_spent AS INTEGER)) as total')
            ->value('total') ?? 0;
        
        $hours = round($totalSeconds / 3600, 2);
        $goal = 20; // 20 hours per month
        
        return round(($hours / $goal) * 100, 1);
    }
    
    /**
     * Calculate knowledge retention rate
     */
    private function calculateKnowledgeRetentionRate($userId, $startDate = null)
    {
        // Get all exam attempts, ordered by date
        $query = \App\Models\ExamRegistration::where('user_id', $userId)
            ->whereIn('status', ['passed', 'failed'])
            ->whereNotNull('score')
            ->orderBy('finished_at', 'asc');
        
        if ($startDate) {
            $query->where('finished_at', '>=', $startDate);
        }
        
        $attempts = $query->get();
        
        if ($attempts->count() < 2) {
            return 0; // Need at least 2 attempts to calculate retention
        }
        
        // Compare first half vs second half scores
        $midPoint = (int)($attempts->count() / 2);
        $firstHalf = $attempts->slice(0, $midPoint);
        $secondHalf = $attempts->slice($midPoint);
        
        $firstHalfAvg = $firstHalf->avg('score');
        $secondHalfAvg = $secondHalf->avg('score');
        
        if ($firstHalfAvg == 0) {
            return 0;
        }
        
        // Retention rate is the percentage of knowledge maintained
        $retentionRate = ($secondHalfAvg / $firstHalfAvg) * 100;
        
        return round($retentionRate, 1);
    }
    
    /**
     * Get latest achievements
     */
    private function getLatestAchievements($userId, $startDate = null)
    {
        $achievements = [];
        
        // Achievement: First 95+ score
        $query = \App\Models\ExamRegistration::where('user_id', $userId)
            ->where('status', 'passed')
            ->where('score', '>=', 95)
            ->orderBy('finished_at', 'desc');
        if ($startDate) {
            $query->where('finished_at', '>=', $startDate);
        }
        $highScore = $query->first();
        if ($highScore) {
            $achievements[] = [
                'title' => 'Əla Nəticə',
                'description' => '95+ bal topladınız',
                'icon' => 'star',
                'date' => $highScore->finished_at->format('Y-m-d'),
                'category' => 'performance',
            ];
        }
        
        // Achievement: Course completion
        $query = \App\Models\TrainingRegistration::where('user_id', $userId)
            ->where('status', 'completed')
            ->orderBy('updated_at', 'desc');
        if ($startDate) {
            $query->where('updated_at', '>=', $startDate);
        }
        $completedTraining = $query->first();
        if ($completedTraining) {
            $achievements[] = [
                'title' => 'Kurs Bitirdiniz',
                'description' => $completedTraining->training->title ?? 'Kurs tamamlandı',
                'icon' => 'certificate',
                'date' => $completedTraining->updated_at->format('Y-m-d'),
                'category' => 'completion',
            ];
        }
        
        // Achievement: Certificate earned
        $query = \App\Models\Certificate::where('user_id', $userId)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc');
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        $certificate = $query->first();
        if ($certificate) {
            $achievements[] = [
                'title' => 'Sertifikat Qazandınız',
                'description' => 'Yeni sertifikat əldə etdiniz',
                'icon' => 'trophy',
                'date' => $certificate->created_at->format('Y-m-d'),
                'category' => 'certificate',
            ];
        }
        
        // Sort by date desc and limit to 5 latest
        usort($achievements, function ($a, $b) {
            return strcmp($b['date'], $a['date']);
        });
        
        return array_slice($achievements, 0, 5);
    }
    
    /**
     * Get course progress list
     */
    private function getCourseProgressList($userId, $startDate = null)
    {
        // Get all trainings user has access to (approved registrations + video trainings with progress)
        $registeredTrainings = \App\Models\TrainingRegistration::where('user_id', $userId)
            ->where('status', 'approved')
            ->with('training')
            ->get();
        
        // Get video trainings with progress
        $videoTrainingIds = \App\Models\UserTrainingProgress::where('user_id', $userId)
            ->distinct()
            ->pluck('training_id')
            ->toArray();
        
        $videoTrainings = \App\Models\Training::where('type', 'video')
            ->whereIn('id', $videoTrainingIds)
            ->get();
        
        $progressList = [];
        
        // Process registered trainings
        foreach ($registeredTrainings as $registration) {
            if ($startDate && $registration->updated_at < $startDate) {
                continue;
            }
            
            $training = $registration->training;
            if (!$training) continue;
            
            // Calculate progress for this training
            $trainingProgress = $this->calculateTrainingProgress($userId, $training);
            
            $progressList[] = [
                'id' => $training->id,
                'title' => $training->title,
                'description' => $training->description,
                'progress_percentage' => $trainingProgress['percentage'],
                'completed_lessons' => $trainingProgress['completed'],
                'total_lessons' => $trainingProgress['total'],
                'last_activity' => $trainingProgress['last_activity'],
                'status' => $registration->status,
                'type' => $training->type ?? 'offline',
            ];
        }
        
        // Process video trainings
        foreach ($videoTrainings as $training) {
            // Skip if already in list
            if (collect($progressList)->contains('id', $training->id)) {
                continue;
            }
            
            // Calculate progress for this training
            $trainingProgress = $this->calculateTrainingProgress($userId, $training);
            
            // Check last activity date if startDate filter is applied
            if ($startDate && $trainingProgress['last_activity']) {
                $lastActivityDate = \Carbon\Carbon::parse($trainingProgress['last_activity']);
                if ($lastActivityDate < $startDate) {
                    continue;
                }
            }
            
            $progressList[] = [
                'id' => $training->id,
                'title' => $training->title,
                'description' => $training->description,
                'progress_percentage' => $trainingProgress['percentage'],
                'completed_lessons' => $trainingProgress['completed'],
                'total_lessons' => $trainingProgress['total'],
                'last_activity' => $trainingProgress['last_activity'],
                'status' => 'in_progress', // Video trainings don't have registration status
                'type' => 'video',
            ];
        }
        
        // Sort by last activity desc
        usort($progressList, function ($a, $b) {
            if ($a['last_activity'] === null && $b['last_activity'] === null) return 0;
            if ($a['last_activity'] === null) return 1;
            if ($b['last_activity'] === null) return -1;
            return strcmp($b['last_activity'], $a['last_activity']);
        });
        
        return $progressList;
    }
    
    /**
     * Calculate progress for a specific training
     */
    private function calculateTrainingProgress($userId, $training)
    {
        $totalLessons = 0;
        $completedLessons = 0;
        $lastActivity = null;
        
        // Load modules and lessons if not loaded
        if (!$training->relationLoaded('modules')) {
            $training->load('modules.lessons');
        }
        
        foreach ($training->modules as $module) {
            if ($module->lessons) {
                foreach ($module->lessons as $lesson) {
                    $totalLessons++;
                    $progress = \App\Models\UserTrainingProgress::where('user_id', $userId)
                        ->where('training_id', $training->id)
                        ->where('lesson_id', $lesson->id)
                        ->where('status', 'completed')
                        ->first();
                    if ($progress) {
                        $completedLessons++;
                        if (!$lastActivity || $progress->updated_at > $lastActivity) {
                            $lastActivity = $progress->updated_at;
                        }
                    } else {
                        // Check if there's any progress (even if not completed) for last activity
                        $anyProgress = \App\Models\UserTrainingProgress::where('user_id', $userId)
                            ->where('training_id', $training->id)
                            ->where('lesson_id', $lesson->id)
                            ->first();
                        if ($anyProgress && (!$lastActivity || $anyProgress->updated_at > $lastActivity)) {
                            $lastActivity = $anyProgress->updated_at;
                        }
                    }
                }
            }
        }
        
        $progressPercentage = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100, 1) : 0;
        
        return [
            'total' => $totalLessons,
            'completed' => $completedLessons,
            'percentage' => $progressPercentage,
            'last_activity' => $lastActivity ? $lastActivity->format('Y-m-d H:i') : null,
        ];
    }
    
    /**
     * Get improvement message based on performance
     */
    private function getImprovementMessage($averageScore, $learningEffectiveness)
    {
        if ($averageScore >= 90 && $learningEffectiveness >= 80) {
            return 'Əla performans! Davam edin.';
        } elseif ($averageScore >= 70 && $learningEffectiveness >= 60) {
            return 'Yaxşı nəticə. Təkmilləşdirmə üçün daha çox öyrənin.';
        } else {
            return 'Performansı yaxşılaşdırmaq üçün daha çox çalışın.';
        }
    }
}


