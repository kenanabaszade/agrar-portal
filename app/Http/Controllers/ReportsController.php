<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Training;
use App\Models\TrainingRegistration;
use App\Models\Exam;
use App\Models\ExamRegistration;
use App\Models\ExamQuestion;
use App\Models\ExamUserAnswer;
use App\Models\Certificate;
use App\Models\Meeting;
use App\Models\MeetingRegistration;
use App\Models\ForumQuestion;
use App\Models\ForumAnswer;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportsController extends Controller
{
    /**
     * Get overview statistics for admin dashboard
     * GET /api/v1/admin/reports/overview
     */
    public function overview(Request $request)
    {
        $dateRange = $this->getDateRange($request);
        $now = Carbon::now();
        $currentMonth = $now->copy()->startOfMonth();
        $lastMonth = $now->copy()->subMonth()->startOfMonth();

        // Users Statistics
        $usersStats = $this->getUsersStatistics($dateRange, $currentMonth, $lastMonth);
        
        // Trainings Statistics
        $trainingsStats = $this->getTrainingsStatistics($dateRange, $currentMonth, $lastMonth);
        
        // Registrations Statistics
        $registrationsStats = $this->getRegistrationsStatistics($dateRange, $currentMonth, $lastMonth);
        
        // Exams Statistics
        $examsStats = $this->getExamsStatistics($dateRange, $currentMonth, $lastMonth);
        
        // Certificates Statistics
        $certificatesStats = $this->getCertificatesStatistics($dateRange, $currentMonth, $lastMonth);
        
        // Meetings Statistics
        $meetingsStats = $this->getMeetingsStatistics($dateRange, $currentMonth, $lastMonth);
        
        // Forum Statistics
        $forumStats = $this->getForumStatistics($dateRange, $currentMonth, $lastMonth);
        
        // Engagement Statistics
        $engagementStats = $this->getEngagementStatistics($dateRange);

        return response()->json([
            'users' => $usersStats,
            'trainings' => $trainingsStats,
            'registrations' => $registrationsStats,
            'exams' => $examsStats,
            'certificates' => $certificatesStats,
            'meetings' => $meetingsStats,
            'forum' => $forumStats,
            'engagement' => $engagementStats,
            'date_range' => [
                'start' => $dateRange['start']->toDateString(),
                'end' => $dateRange['end']->toDateString(),
            ]
        ]);
    }

    /**
     * Get detailed user reports
     * GET /api/v1/admin/reports/users
     */
    public function users(Request $request)
    {
        $dateRange = $this->getDateRange($request);
        $locale = app()->getLocale() ?? 'az';

        // Build query with filters
        $query = User::query();

        // Filter by user type
        if ($request->filled('user_type')) {
            $query->where('user_type', $request->user_type);
        }

        // Filter by region
        if ($request->filled('region')) {
            $query->where('region', $request->region);
        }

        // Filter by gender
        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by email verified
        if ($request->has('email_verified')) {
            $query->where('email_verified', $request->boolean('email_verified'));
        }

        // Filter by date range
        if ($dateRange['start']) {
            $query->where('created_at', '>=', $dateRange['start']);
        }
        if ($dateRange['end']) {
            $query->where('created_at', '<=', $dateRange['end']);
        }

        // Summary statistics
        $summary = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'inactive_users' => User::where('is_active', false)->count(),
            'verified_users' => User::where('email_verified', true)->count(),
            'unverified_users' => User::where('email_verified', false)->count(),
        ];

        // By type
        $byType = [
            'farmers' => User::where('user_type', 'farmer')->count(),
            'trainers' => User::where('user_type', 'trainer')->count(),
            'admins' => User::where('user_type', 'admin')->count(),
        ];

        // By region
        $byRegion = User::select('region', DB::raw('COUNT(*) as count'))
            ->whereNotNull('region')
            ->groupBy('region')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($item) use ($summary) {
                return [
                    'region' => $item->region,
                    'count' => $item->count,
                    'percentage' => $summary['total_users'] > 0 
                        ? round(($item->count / $summary['total_users']) * 100, 2) 
                        : 0
                ];
            });

        // By gender
        $byGender = User::select('gender', DB::raw('COUNT(*) as count'))
            ->whereNotNull('gender')
            ->groupBy('gender')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->gender => $item->count];
            });

        // Registration trend (last 30 days)
        $registrationTrend = User::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'count' => $item->count
                ];
            });

        // Activity statistics
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $activityStats = [
            'users_with_trainings' => User::whereHas('registrations')->count(),
            'users_with_exams' => User::whereHas('examRegistrations')->count(),
            'users_with_certificates' => User::whereHas('certificates')->count(),
            'active_last_7_days' => User::where('last_login_at', '>=', Carbon::now()->subDays(7))->count(),
            'active_last_30_days' => User::where('last_login_at', '>=', $thirtyDaysAgo)->count(),
        ];

        // Top users by achievements
        $topUsers = User::withCount(['certificates', 'registrations'])
            ->whereHas('certificates')
            ->orWhereHas('registrations')
            ->orderBy('certificates_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($user) {
                return [
                    'user_id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'trainings_completed' => TrainingRegistration::where('user_id', $user->id)
                        ->where('status', 'completed')
                        ->count(),
                    'certificates_earned' => $user->certificates_count,
                    'total_score' => ExamRegistration::where('user_id', $user->id)
                        ->where('status', 'passed')
                        ->avg('score') ?? 0
                ];
            });

        // Filtered users list (paginated)
        $perPage = min($request->get('per_page', 15), 100);
        $users = $query->paginate($perPage);

        return response()->json([
            'summary' => $summary,
            'by_type' => $byType,
            'by_region' => $byRegion,
            'by_gender' => $byGender,
            'registration_trend' => $registrationTrend,
            'activity_stats' => $activityStats,
            'top_users' => $topUsers,
            'users' => $users,
        ]);
    }

    /**
     * Get detailed training reports
     * GET /api/v1/admin/reports/trainings
     */
    public function trainings(Request $request)
    {
        $dateRange = $this->getDateRange($request);
        $currentMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        // Build query with filters
        $query = Training::query();

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Filter by trainer
        if ($request->filled('trainer_id')) {
            $query->where('trainer_id', $request->trainer_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by difficulty
        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        // Filter by has_certificate
        if ($request->has('has_certificate')) {
            $query->where('has_certificate', $request->boolean('has_certificate'));
        }

        // Filter by date range
        if ($dateRange['start']) {
            $query->where('created_at', '>=', $dateRange['start']);
        }
        if ($dateRange['end']) {
            $query->where('created_at', '<=', $dateRange['end']);
        }

        // Summary
        $summary = [
            'total_trainings' => Training::count(),
            'published_trainings' => Training::where('status', 'published')->count(),
            'draft_trainings' => Training::where('status', 'draft')->count(),
            'archived_trainings' => Training::where('status', 'archived')->count(),
        ];

        // By type
        $byType = [
            'online' => Training::where('type', 'online')->count(),
            'offline' => Training::where('type', 'offline')->count(),
            'video' => Training::where('type', 'video')->count(),
        ];

        // By category
        $byCategory = Training::select('category', DB::raw('COUNT(*) as count'))
            ->whereNotNull('category')
            ->groupBy('category')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($item) use ($summary) {
                return [
                    'category' => $item->category,
                    'count' => $item->count,
                    'percentage' => $summary['total_trainings'] > 0 
                        ? round(($item->count / $summary['total_trainings']) * 100, 2) 
                        : 0
                ];
            });

        // By difficulty
        $byDifficulty = Training::select('difficulty', DB::raw('COUNT(*) as count'))
            ->whereNotNull('difficulty')
            ->groupBy('difficulty')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->difficulty => $item->count];
            });

        // By status
        $byStatus = Training::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => $item->count];
            });

        // Registration statistics
        $totalRegistrations = TrainingRegistration::count();
        $averageRegistrations = $summary['total_trainings'] > 0 
            ? round($totalRegistrations / $summary['total_trainings'], 2) 
            : 0;

        $mostRegistered = Training::withCount('registrations')
            ->orderBy('registrations_count', 'desc')
            ->first();

        $leastRegistered = Training::withCount('registrations')
            ->get()
            ->filter(function ($training) {
                return $training->registrations_count > 0;
            })
            ->sortBy('registrations_count')
            ->first();

        $registrationStats = [
            'total_registrations' => $totalRegistrations,
            'average_registrations_per_training' => $averageRegistrations,
            'most_registered_training' => $mostRegistered ? [
                'id' => $mostRegistered->id,
                'title' => $mostRegistered->title,
                'registrations_count' => $mostRegistered->registrations_count
            ] : null,
            'least_registered_training' => $leastRegistered ? [
                'id' => $leastRegistered->id,
                'title' => $leastRegistered->title,
                'registrations_count' => $leastRegistered->registrations_count
            ] : null,
        ];

        // Completion statistics
        $totalCompleted = TrainingRegistration::where('status', 'completed')->count();
        $completionRate = $totalRegistrations > 0 
            ? round(($totalCompleted / $totalRegistrations) * 100, 2) 
            : 0;

        $completionStats = [
            'total_completed' => $totalCompleted,
            'completion_rate' => $completionRate,
            'average_completion_time' => null, // Can be calculated if we track completion dates
        ];

        // Top trainings
        $topTrainings = Training::withCount('registrations')
            ->with('trainer')
            ->withAvg('ratings', 'rating')
            ->orderBy('registrations_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($training) {
                $completed = TrainingRegistration::where('training_id', $training->id)
                    ->where('status', 'completed')
                    ->count();
                $completionRate = $training->registrations_count > 0 
                    ? round(($completed / $training->registrations_count) * 100, 2) 
                    : 0;

                return [
                    'training_id' => $training->id,
                    'title' => $training->title,
                    'trainer' => $training->trainer ? 
                        $training->trainer->first_name . ' ' . $training->trainer->last_name : null,
                    'registrations_count' => $training->registrations_count,
                    'completion_rate' => $completionRate,
                    'average_rating' => round($training->ratings_avg_rating ?? 0, 2),
                ];
            });

        // By trainer
        $byTrainer = User::where('user_type', 'trainer')
            ->withCount('trainings')
            ->get()
            ->map(function ($trainer) {
                $totalRegistrations = TrainingRegistration::whereHas('training', function ($q) use ($trainer) {
                    $q->where('trainer_id', $trainer->id);
                })->count();

                $avgRating = \App\Models\TrainingRating::whereHas('training', function ($q) use ($trainer) {
                    $q->where('trainer_id', $trainer->id);
                })->avg('rating');

                return [
                    'trainer_id' => $trainer->id,
                    'trainer_name' => $trainer->first_name . ' ' . $trainer->last_name,
                    'trainings_count' => $trainer->trainings_count,
                    'total_registrations' => $totalRegistrations,
                    'average_rating' => round($avgRating ?? 0, 2),
                ];
            });

        // Filtered trainings list
        $perPage = min($request->get('per_page', 15), 100);
        $trainings = $query->with(['trainer', 'modules'])
            ->withCount('registrations')
            ->paginate($perPage);

        return response()->json([
            'summary' => $summary,
            'by_type' => $byType,
            'by_category' => $byCategory,
            'by_difficulty' => $byDifficulty,
            'by_status' => $byStatus,
            'registration_stats' => $registrationStats,
            'completion_stats' => $completionStats,
            'top_trainings' => $topTrainings,
            'by_trainer' => $byTrainer,
            'trainings' => $trainings,
        ]);
    }

    /**
     * Get detailed exam reports
     * GET /api/v1/admin/reports/exams
     */
    public function exams(Request $request)
    {
        $dateRange = $this->getDateRange($request);
        $currentMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        // Build query with filters
        $query = Exam::query();

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Filter by training_id
        if ($request->filled('training_id')) {
            $query->where('training_id', $request->training_id);
        }

        // Filter by difficulty
        if ($request->filled('difficulty')) {
            $query->whereHas('questions', function ($q) use ($request) {
                $q->where('difficulty', $request->difficulty);
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by passing_score_range
        if ($request->filled('min_score')) {
            $query->where('passing_score', '>=', $request->min_score);
        }
        if ($request->filled('max_score')) {
            $query->where('passing_score', '<=', $request->max_score);
        }

        // Filter by date range
        if ($dateRange['start']) {
            $query->where('created_at', '>=', $dateRange['start']);
        }
        if ($dateRange['end']) {
            $query->where('created_at', '<=', $dateRange['end']);
        }

        // Summary
        $now = Carbon::now();
        $summary = [
            'total_exams' => Exam::count(),
            'active_exams' => Exam::where(function ($q) use ($now) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $now);
            })
            ->count(),
            'completed_exams' => Exam::where(function ($q) use ($now) {
                $q->whereNotNull('end_date')
                    ->where('end_date', '<', $now);
            })->count(),
            'upcoming_exams' => Exam::where(function ($q) use ($now) {
                $q->whereNotNull('start_date')
                    ->where('start_date', '>', $now);
            })->count(),
        ];

        // Registration statistics
        $totalRegistrations = ExamRegistration::count();
        $registrationsByStatus = ExamRegistration::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => $item->count];
            });

        $averageRegistrations = $summary['total_exams'] > 0 
            ? round($totalRegistrations / $summary['total_exams'], 2) 
            : 0;

        $registrationStats = [
            'total_registrations' => $totalRegistrations,
            'registrations_by_status' => $registrationsByStatus,
            'average_registrations_per_exam' => $averageRegistrations,
        ];

        // Performance statistics
        $completedExams = ExamRegistration::where('status', 'completed')
            ->orWhere('status', 'passed')
            ->orWhere('status', 'failed')
            ->count();
        
        $passedExams = ExamRegistration::where('status', 'passed')->count();
        $failedExams = ExamRegistration::where('status', 'failed')->count();
        
        $passRate = $completedExams > 0 
            ? round(($passedExams / $completedExams) * 100, 2) 
            : 0;

        $averageScore = ExamRegistration::whereIn('status', ['completed', 'passed', 'failed'])
            ->whereNotNull('score')
            ->avg('score') ?? 0;

        $highestScore = ExamRegistration::whereNotNull('score')->max('score') ?? 0;
        $lowestScore = ExamRegistration::whereNotNull('score')->min('score') ?? 0;

        $performanceStats = [
            'total_completed' => $completedExams,
            'total_passed' => $passedExams,
            'total_failed' => $failedExams,
            'pass_rate' => $passRate,
            'average_score' => round($averageScore, 2),
            'highest_score' => $highestScore,
            'lowest_score' => $lowestScore,
        ];

        // By difficulty
        $byDifficulty = ExamQuestion::select('difficulty', DB::raw('COUNT(DISTINCT exam_id) as count'))
            ->whereNotNull('difficulty')
            ->groupBy('difficulty')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->difficulty => $item->count];
            });

        // By category
        $byCategory = Exam::select('category', DB::raw('COUNT(*) as count'))
            ->whereNotNull('category')
            ->groupBy('category')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($exam) {
                $registrations = ExamRegistration::whereHas('exam', function ($q) use ($exam) {
                    $q->where('category', $exam->category);
                })
                ->whereIn('status', ['completed', 'passed', 'failed'])
                ->whereNotNull('score');

                $avgScore = $registrations->avg('score') ?? 0;
                $passed = ExamRegistration::whereHas('exam', function ($q) use ($exam) {
                    $q->where('category', $exam->category);
                })
                ->where('status', 'passed')->count();
                
                $total = ExamRegistration::whereHas('exam', function ($q) use ($exam) {
                    $q->where('category', $exam->category);
                })
                ->whereIn('status', ['completed', 'passed', 'failed'])->count();

                $passRate = $total > 0 ? round(($passed / $total) * 100, 2) : 0;

                return [
                    'category' => $exam->category,
                    'count' => $exam->count,
                    'average_score' => round($avgScore, 2),
                    'pass_rate' => $passRate,
                ];
            });

        // Grading statistics
        $autoGraded = ExamRegistration::where('needs_manual_grading', false)
            ->whereNotNull('score')
            ->count();
        $manualGraded = ExamRegistration::where('needs_manual_grading', true)
            ->whereNotNull('graded_at')
            ->count();
        $pendingGrading = ExamRegistration::where('needs_manual_grading', true)
            ->whereNull('graded_at')
            ->count();

        $gradingStats = [
            'auto_graded' => $autoGraded,
            'manual_graded' => $manualGraded,
            'pending_grading' => $pendingGrading,
            'average_grading_time' => null, // Can be calculated if needed
        ];

        // Top performers
        $topPerformers = User::withCount(['examRegistrations as passed_exams' => function ($q) {
                $q->where('status', 'passed');
            }])
            ->whereHas('examRegistrations', function ($q) {
                $q->where('status', 'passed');
            })
            ->get()
            ->map(function ($user) {
                $avgScore = ExamRegistration::where('user_id', $user->id)
                    ->where('status', 'passed')
                    ->avg('score') ?? 0;

                return [
                    'user_id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'total_exams' => ExamRegistration::where('user_id', $user->id)->count(),
                    'average_score' => round($avgScore, 2),
                    'certificates_earned' => Certificate::where('user_id', $user->id)
                        ->whereNotNull('related_exam_id')
                        ->count(),
                ];
            })
            ->sortByDesc('average_score')
            ->take(10)
            ->values();

        // Top exams
        $topExams = Exam::withCount('registrations')
            ->get()
            ->map(function ($exam) {
                $completed = ExamRegistration::where('exam_id', $exam->id)
                    ->whereIn('status', ['completed', 'passed', 'failed'])
                    ->count();
                $completionRate = $exam->registrations_count > 0 
                    ? round(($completed / $exam->registrations_count) * 100, 2) 
                    : 0;

                $avgScore = ExamRegistration::where('exam_id', $exam->id)
                    ->whereNotNull('score')
                    ->avg('score') ?? 0;

                $passed = ExamRegistration::where('exam_id', $exam->id)
                    ->where('status', 'passed')
                    ->count();
                $passRate = $completed > 0 ? round(($passed / $completed) * 100, 2) : 0;

                return [
                    'exam_id' => $exam->id,
                    'title' => $exam->title,
                    'registrations_count' => $exam->registrations_count,
                    'completion_rate' => $completionRate,
                    'average_score' => round($avgScore, 2),
                    'pass_rate' => $passRate,
                ];
            })
            ->sortByDesc('registrations_count')
            ->take(10)
            ->values();

        // Challenging questions
        $challengingQuestions = ExamQuestion::with('exam')
            ->whereHas('userAnswers')
            ->get()
            ->map(function ($question) {
                $totalAnswers = ExamUserAnswer::where('question_id', $question->id)->count();
                
                if ($totalAnswers === 0) {
                    return null;
                }

                // Get correct answer rate
                $correctAnswers = 0;
                $choices = $question->choices;
                
                foreach ($choices as $choice) {
                    if ($choice->is_correct) {
                        $correctAnswers += ExamUserAnswer::where('question_id', $question->id)
                            ->where(function ($q) use ($choice) {
                                $q->where('choice_id', $choice->id)
                                    ->orWhereJsonContains('choice_ids', $choice->id);
                            })
                            ->count();
                    }
                }

                $correctRate = $totalAnswers > 0 
                    ? round(($correctAnswers / $totalAnswers) * 100, 2) 
                    : 0;

                return [
                    'question_id' => $question->id,
                    'exam_title' => $question->exam ? $question->exam->title : null,
                    'question_text' => $question->question_text,
                    'correct_answer_rate' => $correctRate,
                    'total_attempts' => $totalAnswers,
                ];
            })
            ->filter()
            ->sortBy('correct_answer_rate')
            ->take(10)
            ->values();

        // Filtered exams list
        $perPage = min($request->get('per_page', 15), 100);
        $exams = $query->with(['training', 'questions'])
            ->withCount('registrations')
            ->paginate($perPage);

        return response()->json([
            'summary' => $summary,
            'registration_stats' => $registrationStats,
            'performance_stats' => $performanceStats,
            'by_difficulty' => $byDifficulty,
            'by_category' => $byCategory,
            'grading_stats' => $gradingStats,
            'top_performers' => $topPerformers,
            'top_exams' => $topExams,
            'challenging_questions' => $challengingQuestions,
            'exams' => $exams,
        ]);
    }

    // Helper methods

    /**
     * Get date range from request
     */
    private function getDateRange(Request $request)
    {
        $start = null;
        $end = null;

        if ($request->filled('start_date')) {
            $start = Carbon::parse($request->start_date)->startOfDay();
        } elseif ($request->filled('period')) {
            $period = $request->period;
            switch ($period) {
                case 'today':
                    $start = Carbon::today();
                    $end = Carbon::today()->endOfDay();
                    break;
                case 'yesterday':
                    $start = Carbon::yesterday();
                    $end = Carbon::yesterday()->endOfDay();
                    break;
                case 'this_week':
                    $start = Carbon::now()->startOfWeek();
                    $end = Carbon::now()->endOfWeek();
                    break;
                case 'last_week':
                    $start = Carbon::now()->subWeek()->startOfWeek();
                    $end = Carbon::now()->subWeek()->endOfWeek();
                    break;
                case 'this_month':
                    $start = Carbon::now()->startOfMonth();
                    $end = Carbon::now()->endOfMonth();
                    break;
                case 'last_month':
                    $start = Carbon::now()->subMonth()->startOfMonth();
                    $end = Carbon::now()->subMonth()->endOfMonth();
                    break;
                case 'this_year':
                    $start = Carbon::now()->startOfYear();
                    $end = Carbon::now()->endOfYear();
                    break;
                case 'last_year':
                    $start = Carbon::now()->subYear()->startOfYear();
                    $end = Carbon::now()->subYear()->endOfYear();
                    break;
            }
        }

        if ($request->filled('end_date')) {
            $end = Carbon::parse($request->end_date)->endOfDay();
        }

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * Get users statistics for overview
     */
    private function getUsersStatistics($dateRange, $currentMonth, $lastMonth)
    {
        $query = User::query();

        if ($dateRange['start']) {
            $query->where('created_at', '>=', $dateRange['start']);
        }
        if ($dateRange['end']) {
            $query->where('created_at', '<=', $dateRange['end']);
        }

        $totalUsers = User::count();
        $newUsersThisMonth = User::where('created_at', '>=', $currentMonth)->count();
        $newUsersToday = User::whereDate('created_at', Carbon::today())->count();
        
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $activeUsers30Days = User::where('last_login_at', '>=', $thirtyDaysAgo)
            ->orWhere('updated_at', '>=', $thirtyDaysAgo)
            ->distinct()
            ->count();

        $usersByRegion = User::select('region', DB::raw('COUNT(*) as count'))
            ->whereNotNull('region')
            ->groupBy('region')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'region' => $item->region,
                    'count' => $item->count,
                ];
            });

        $usersByGender = User::select('gender', DB::raw('COUNT(*) as count'))
            ->whereNotNull('gender')
            ->groupBy('gender')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->gender => $item->count];
            });

        $usersByType = [
            'farmer' => User::where('user_type', 'farmer')->count(),
            'trainer' => User::where('user_type', 'trainer')->count(),
            'admin' => User::where('user_type', 'admin')->count(),
        ];

        $lastMonthUsers = User::where('created_at', '>=', $lastMonth)
            ->where('created_at', '<', $currentMonth)
            ->count();
        
        $usersGrowth = $lastMonthUsers > 0 
            ? round((($newUsersThisMonth - $lastMonthUsers) / $lastMonthUsers) * 100, 2) 
            : ($newUsersThisMonth > 0 ? 100 : 0);

        return [
            'total_users' => $totalUsers,
            'total_farmers' => $usersByType['farmer'],
            'total_trainers' => $usersByType['trainer'],
            'new_users_today' => $newUsersToday,
            'new_users_this_month' => $newUsersThisMonth,
            'active_users_30_days' => $activeUsers30Days,
            'users_by_region' => $usersByRegion,
            'users_by_gender' => $usersByGender,
            'users_by_type' => $usersByType,
            'users_growth_percentage' => $usersGrowth,
        ];
    }

    /**
     * Get trainings statistics for overview
     */
    private function getTrainingsStatistics($dateRange, $currentMonth, $lastMonth)
    {
        $query = Training::query();

        if ($dateRange['start']) {
            $query->where('created_at', '>=', $dateRange['start']);
        }
        if ($dateRange['end']) {
            $query->where('created_at', '<=', $dateRange['end']);
        }

        $totalTrainings = Training::count();
        $publishedTrainings = Training::where('status', 'published')->count();
        $draftTrainings = Training::where('status', 'draft')->count();
        $onlineTrainings = Training::where('type', 'online')->count();
        $offlineTrainings = Training::where('type', 'offline')->count();
        $videoTrainings = Training::where('type', 'video')->count();

        $trainingsByCategory = Training::select('category', DB::raw('COUNT(*) as count'))
            ->whereNotNull('category')
            ->groupBy('category')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->category,
                    'count' => $item->count,
                ];
            });

        $trainingsByDifficulty = Training::select('difficulty', DB::raw('COUNT(*) as count'))
            ->whereNotNull('difficulty')
            ->groupBy('difficulty')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->difficulty => $item->count];
            });

        $trainingsByStatus = Training::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => $item->count];
            });

        $totalRegistrations = TrainingRegistration::count();
        $averageRegistrations = $totalTrainings > 0 
            ? round($totalRegistrations / $totalTrainings, 2) 
            : 0;

        $mostPopularTrainings = Training::withCount('registrations')
            ->orderBy('registrations_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($training) {
                return [
                    'id' => $training->id,
                    'title' => $training->title,
                    'registrations_count' => $training->registrations_count,
                ];
            });

        $lastMonthTrainings = Training::where('created_at', '>=', $lastMonth)
            ->where('created_at', '<', $currentMonth)
            ->count();
        $thisMonthTrainings = Training::where('created_at', '>=', $currentMonth)->count();
        
        $trainingsGrowth = $lastMonthTrainings > 0 
            ? round((($thisMonthTrainings - $lastMonthTrainings) / $lastMonthTrainings) * 100, 2) 
            : ($thisMonthTrainings > 0 ? 100 : 0);

        return [
            'total_trainings' => $totalTrainings,
            'published_trainings' => $publishedTrainings,
            'draft_trainings' => $draftTrainings,
            'online_trainings' => $onlineTrainings,
            'offline_trainings' => $offlineTrainings,
            'video_trainings' => $videoTrainings,
            'trainings_by_category' => $trainingsByCategory,
            'trainings_by_difficulty' => $trainingsByDifficulty,
            'trainings_by_status' => $trainingsByStatus,
            'average_registrations_per_training' => $averageRegistrations,
            'most_popular_trainings' => $mostPopularTrainings,
            'trainings_growth_percentage' => $trainingsGrowth,
        ];
    }

    /**
     * Get registrations statistics for overview
     */
    private function getRegistrationsStatistics($dateRange, $currentMonth, $lastMonth)
    {
        $query = TrainingRegistration::query();

        if ($dateRange['start']) {
            $query->where('created_at', '>=', $dateRange['start']);
        }
        if ($dateRange['end']) {
            $query->where('created_at', '<=', $dateRange['end']);
        }

        $totalRegistrations = TrainingRegistration::count();
        $pendingRegistrations = TrainingRegistration::where('status', 'pending')->count();
        $approvedRegistrations = TrainingRegistration::where('status', 'approved')->count();
        $completedRegistrations = TrainingRegistration::where('status', 'completed')->count();
        $registrationsThisMonth = TrainingRegistration::where('created_at', '>=', $currentMonth)->count();

        $registrationsByStatus = TrainingRegistration::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => $item->count];
            });

        $lastMonthRegistrations = TrainingRegistration::where('created_at', '>=', $lastMonth)
            ->where('created_at', '<', $currentMonth)
            ->count();
        
        $registrationsGrowth = $lastMonthRegistrations > 0 
            ? round((($registrationsThisMonth - $lastMonthRegistrations) / $lastMonthRegistrations) * 100, 2) 
            : ($registrationsThisMonth > 0 ? 100 : 0);

        return [
            'total_registrations' => $totalRegistrations,
            'pending_registrations' => $pendingRegistrations,
            'approved_registrations' => $approvedRegistrations,
            'completed_registrations' => $completedRegistrations,
            'registrations_this_month' => $registrationsThisMonth,
            'registrations_by_status' => $registrationsByStatus,
            'registrations_growth_percentage' => $registrationsGrowth,
        ];
    }

    /**
     * Get exams statistics for overview
     */
    private function getExamsStatistics($dateRange, $currentMonth, $lastMonth)
    {
        $now = Carbon::now();
        
        $totalExams = Exam::count();
        $activeExams = Exam::where(function ($q) use ($now) {
            $q->whereNull('start_date')->orWhere('start_date', '<=', $now);
        })
        ->where(function ($q) use ($now) {
            $q->whereNull('end_date')->orWhere('end_date', '>=', $now);
        })
        ->count();

        $completedExams = Exam::where(function ($q) use ($now) {
            $q->whereNotNull('end_date')->where('end_date', '<', $now);
        })->count();

        $totalExamRegistrations = ExamRegistration::count();
        
        $avgScore = ExamRegistration::whereIn('status', ['completed', 'passed', 'failed'])
            ->whereNotNull('score')
            ->avg('score') ?? 0;

        $passed = ExamRegistration::where('status', 'passed')->count();
        $totalCompleted = ExamRegistration::whereIn('status', ['completed', 'passed', 'failed'])->count();
        $passRate = $totalCompleted > 0 ? round(($passed / $totalCompleted) * 100, 2) : 0;

        $examsByDifficulty = ExamQuestion::select('difficulty', DB::raw('COUNT(DISTINCT exam_id) as count'))
            ->whereNotNull('difficulty')
            ->groupBy('difficulty')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->difficulty => $item->count];
            });

        $examsRequiringManualGrading = ExamRegistration::where('needs_manual_grading', true)
            ->whereNull('graded_at')
            ->distinct('exam_id')
            ->count('exam_id');

        $lastMonthExams = Exam::where('created_at', '>=', $lastMonth)
            ->where('created_at', '<', $currentMonth)
            ->count();
        $thisMonthExams = Exam::where('created_at', '>=', $currentMonth)->count();
        
        $examsGrowth = $lastMonthExams > 0 
            ? round((($thisMonthExams - $lastMonthExams) / $lastMonthExams) * 100, 2) 
            : ($thisMonthExams > 0 ? 100 : 0);

        return [
            'total_exams' => $totalExams,
            'active_exams' => $activeExams,
            'completed_exams' => $completedExams,
            'total_exam_registrations' => $totalExamRegistrations,
            'average_exam_score' => round($avgScore, 2),
            'exam_pass_rate' => $passRate,
            'exams_by_difficulty' => $examsByDifficulty,
            'exams_requiring_manual_grading' => $examsRequiringManualGrading,
            'exams_growth_percentage' => $examsGrowth,
        ];
    }

    /**
     * Get certificates statistics for overview
     */
    private function getCertificatesStatistics($dateRange, $currentMonth, $lastMonth)
    {
        $totalCertificates = Certificate::count();
        $activeCertificates = Certificate::where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', Carbon::now());
            })
            ->count();
        
        $expiredCertificates = Certificate::where(function ($q) {
            $q->where('status', 'expired')
                ->orWhere(function ($q2) {
                    $q2->whereNotNull('expiry_date')
                        ->where('expiry_date', '<', Carbon::now());
                });
        })->count();

        $certificatesThisMonth = Certificate::where('created_at', '>=', $currentMonth)->count();

        $certificatesByType = [
            'training' => Certificate::whereNotNull('related_training_id')
                ->whereNull('related_exam_id')
                ->count(),
            'exam' => Certificate::whereNotNull('related_exam_id')
                ->whereNull('related_training_id')
                ->count(),
            'combined' => Certificate::whereNotNull('related_training_id')
                ->whereNotNull('related_exam_id')
                ->count(),
        ];

        $lastMonthCertificates = Certificate::where('created_at', '>=', $lastMonth)
            ->where('created_at', '<', $currentMonth)
            ->count();
        
        $certificatesGrowth = $lastMonthCertificates > 0 
            ? round((($certificatesThisMonth - $lastMonthCertificates) / $lastMonthCertificates) * 100, 2) 
            : ($certificatesThisMonth > 0 ? 100 : 0);

        return [
            'total_certificates' => $totalCertificates,
            'active_certificates' => $activeCertificates,
            'expired_certificates' => $expiredCertificates,
            'certificates_this_month' => $certificatesThisMonth,
            'certificates_by_type' => $certificatesByType,
            'certificates_growth_percentage' => $certificatesGrowth,
        ];
    }

    /**
     * Get meetings statistics for overview
     */
    private function getMeetingsStatistics($dateRange, $currentMonth, $lastMonth)
    {
        $now = Carbon::now();
        
        $totalMeetings = Meeting::count();
        $upcomingMeetings = Meeting::where('start_time', '>', $now)->count();
        $completedMeetings = Meeting::where('start_time', '<', $now)->count();
        $totalParticipants = MeetingRegistration::distinct('user_id')->count('user_id');
        
        $averageParticipants = $totalMeetings > 0 
            ? round($totalParticipants / $totalMeetings, 2) 
            : 0;

        $meetingsByCategory = Meeting::select('category', DB::raw('COUNT(*) as count'))
            ->whereNotNull('category')
            ->groupBy('category')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->category,
                    'count' => $item->count,
                ];
            });

        $lastMonthMeetings = Meeting::where('created_at', '>=', $lastMonth)
            ->where('created_at', '<', $currentMonth)
            ->count();
        $thisMonthMeetings = Meeting::where('created_at', '>=', $currentMonth)->count();
        
        $meetingsGrowth = $lastMonthMeetings > 0 
            ? round((($thisMonthMeetings - $lastMonthMeetings) / $lastMonthMeetings) * 100, 2) 
            : ($thisMonthMeetings > 0 ? 100 : 0);

        return [
            'total_meetings' => $totalMeetings,
            'upcoming_meetings' => $upcomingMeetings,
            'completed_meetings' => $completedMeetings,
            'total_participants' => $totalParticipants,
            'average_participants_per_meeting' => $averageParticipants,
            'meetings_by_category' => $meetingsByCategory,
            'meetings_growth_percentage' => $meetingsGrowth,
        ];
    }

    /**
     * Get forum statistics for overview
     */
    private function getForumStatistics($dateRange, $currentMonth, $lastMonth)
    {
        $totalQuestions = ForumQuestion::count();
        $totalAnswers = ForumAnswer::count();
        $totalViews = ForumQuestion::sum('views') ?? 0;
        $totalLikes = ForumQuestion::sum('likes_count') ?? 0;

        $mostAnsweredQuestions = ForumQuestion::withCount('answers')
            ->orderBy('answers_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($question) {
                return [
                    'id' => $question->id,
                    'title' => $question->title,
                    'answers_count' => $question->answers_count,
                ];
            });

        $forumActivityToday = ForumQuestion::whereDate('created_at', Carbon::today())
            ->orWhereHas('answers', function ($q) {
                $q->whereDate('created_at', Carbon::today());
            })
            ->count();

        $lastMonthForum = ForumQuestion::where('created_at', '>=', $lastMonth)
            ->where('created_at', '<', $currentMonth)
            ->count();
        $thisMonthForum = ForumQuestion::where('created_at', '>=', $currentMonth)->count();
        
        $forumGrowth = $lastMonthForum > 0 
            ? round((($thisMonthForum - $lastMonthForum) / $lastMonthForum) * 100, 2) 
            : ($thisMonthForum > 0 ? 100 : 0);

        return [
            'total_questions' => $totalQuestions,
            'total_answers' => $totalAnswers,
            'total_views' => $totalViews,
            'total_likes' => $totalLikes,
            'most_answered_questions' => $mostAnsweredQuestions,
            'forum_activity_today' => $forumActivityToday,
            'forum_growth_percentage' => $forumGrowth,
        ];
    }

    /**
     * Get engagement statistics for overview
     */
    private function getEngagementStatistics($dateRange)
    {
        $totalRegistrations = TrainingRegistration::count();
        $completedRegistrations = TrainingRegistration::where('status', 'completed')->count();
        $trainingCompletionRate = $totalRegistrations > 0 
            ? round(($completedRegistrations / $totalRegistrations) * 100, 2) 
            : 0;

        $totalExamRegistrations = ExamRegistration::count();
        $completedExamRegistrations = ExamRegistration::whereIn('status', ['completed', 'passed', 'failed'])->count();
        $examCompletionRate = $totalExamRegistrations > 0 
            ? round(($completedExamRegistrations / $totalExamRegistrations) * 100, 2) 
            : 0;

        // Average progress calculation (simplified)
        $averageProgress = 0; // Can be calculated based on UserTrainingProgress

        // User retention (simplified - based on last login)
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $usersActiveLast30Days = User::where('last_login_at', '>=', $thirtyDaysAgo)->count();
        $totalUsers = User::count();
        $userRetentionRate = $totalUsers > 0 
            ? round(($usersActiveLast30Days / $totalUsers) * 100, 2) 
            : 0;

        return [
            'training_completion_rate' => $trainingCompletionRate,
            'exam_completion_rate' => $examCompletionRate,
            'average_training_progress' => $averageProgress,
            'user_retention_rate' => $userRetentionRate,
            'average_time_spent_on_platform' => 0, // Can be calculated if tracking
        ];
    }

    /**
     * Get detailed certificate reports
     * GET /api/v1/admin/reports/certificates
     */
    public function certificates(Request $request)
    {
        $dateRange = $this->getDateRange($request);
        $now = Carbon::now();

        // Build query with filters
        $query = Certificate::query();

        // Filter by certificate type
        if ($request->filled('certificate_type')) {
            $type = $request->certificate_type;
            if ($type === 'training') {
                $query->whereNotNull('related_training_id')->whereNull('related_exam_id');
            } elseif ($type === 'exam') {
                $query->whereNotNull('related_exam_id')->whereNull('related_training_id');
            } elseif ($type === 'combined') {
                $query->whereNotNull('related_training_id')->whereNotNull('related_exam_id');
            }
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by is_expired
        if ($request->has('is_expired')) {
            if ($request->boolean('is_expired')) {
                $query->where(function ($q) {
                    $q->where('status', 'expired')
                        ->orWhere(function ($q2) {
                            $q2->whereNotNull('expiry_date')
                                ->where('expiry_date', '<', Carbon::now());
                        });
                });
            } else {
                $query->where(function ($q) {
                    $q->where('status', '!=', 'expired')
                        ->where(function ($q2) {
                            $q2->whereNull('expiry_date')
                                ->orWhere('expiry_date', '>=', Carbon::now());
                        });
                });
            }
        }

        // Filter by training_id
        if ($request->filled('training_id')) {
            $query->where('related_training_id', $request->training_id);
        }

        // Filter by exam_id
        if ($request->filled('exam_id')) {
            $query->where('related_exam_id', $request->exam_id);
        }

        // Filter by date range
        if ($dateRange['start']) {
            $query->where('created_at', '>=', $dateRange['start']);
        }
        if ($dateRange['end']) {
            $query->where('created_at', '<=', $dateRange['end']);
        }

        // Summary
        $summary = [
            'total_certificates' => Certificate::count(),
            'active_certificates' => Certificate::where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('expiry_date')
                        ->orWhere('expiry_date', '>=', $now);
                })
                ->count(),
            'expired_certificates' => Certificate::where(function ($q) {
                $q->where('status', 'expired')
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('expiry_date')
                            ->where('expiry_date', '<', Carbon::now());
                    });
            })->count(),
            'expiring_soon' => Certificate::where('status', 'active')
                ->whereNotNull('expiry_date')
                ->whereBetween('expiry_date', [$now, $now->copy()->addDays(30)])
                ->count(),
        ];

        // By type
        $byType = [
            'training_certificates' => Certificate::whereNotNull('related_training_id')
                ->whereNull('related_exam_id')
                ->count(),
            'exam_certificates' => Certificate::whereNotNull('related_exam_id')
                ->whereNull('related_training_id')
                ->count(),
            'combined_certificates' => Certificate::whereNotNull('related_training_id')
                ->whereNotNull('related_exam_id')
                ->count(),
        ];

        // By status
        $byStatus = Certificate::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => $item->count];
            });

        // Issuance trend (last 30 days)
        $issuanceTrend = Certificate::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'count' => $item->count
                ];
            });

        // By training
        $byTraining = Certificate::select('related_training_id', DB::raw('COUNT(*) as certificates_issued'))
            ->whereNotNull('related_training_id')
            ->groupBy('related_training_id')
            ->orderBy('certificates_issued', 'desc')
            ->get()
            ->map(function ($item) {
                $training = Training::find($item->related_training_id);
                $expired = Certificate::where('related_training_id', $item->related_training_id)
                    ->where(function ($q) {
                        $q->where('status', 'expired')
                            ->orWhere(function ($q2) {
                                $q2->whereNotNull('expiry_date')
                                    ->where('expiry_date', '<', Carbon::now());
                            });
                    })
                    ->count();

                return [
                    'training_id' => $item->related_training_id,
                    'training_title' => $training ? $training->title : null,
                    'certificates_issued' => $item->certificates_issued,
                    'expired_count' => $expired,
                ];
            });

        // By exam
        $byExam = Certificate::select('related_exam_id', DB::raw('COUNT(*) as certificates_issued'))
            ->whereNotNull('related_exam_id')
            ->groupBy('related_exam_id')
            ->orderBy('certificates_issued', 'desc')
            ->get()
            ->map(function ($item) {
                $exam = Exam::find($item->related_exam_id);
                return [
                    'exam_id' => $item->related_exam_id,
                    'exam_title' => $exam ? $exam->title : null,
                    'certificates_issued' => $item->certificates_issued,
                ];
            });

        // Top certificate holders
        $topCertificateHolders = User::withCount(['certificates as active_certificates' => function ($q) use ($now) {
                $q->where('status', 'active')
                    ->where(function ($q2) use ($now) {
                        $q2->whereNull('expiry_date')
                            ->orWhere('expiry_date', '>=', $now);
                    });
            }])
            ->whereHas('certificates')
            ->get()
            ->map(function ($user) {
                return [
                    'user_id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'total_certificates' => $user->certificates()->count(),
                    'active_certificates' => $user->active_certificates,
                ];
            })
            ->sortByDesc('total_certificates')
            ->take(10)
            ->values();

        // Expiry forecast
        $expiryForecast = [
            'expiring_this_month' => Certificate::where('status', 'active')
                ->whereNotNull('expiry_date')
                ->whereBetween('expiry_date', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
                ->count(),
            'expiring_next_month' => Certificate::where('status', 'active')
                ->whereNotNull('expiry_date')
                ->whereBetween('expiry_date', [$now->copy()->addMonth()->startOfMonth(), $now->copy()->addMonth()->endOfMonth()])
                ->count(),
            'expiring_in_3_months' => Certificate::where('status', 'active')
                ->whereNotNull('expiry_date')
                ->whereBetween('expiry_date', [$now, $now->copy()->addMonths(3)])
                ->count(),
        ];

        // Filtered certificates list
        $perPage = min($request->get('per_page', 15), 100);
        $certificates = $query->with(['user', 'training', 'exam'])
            ->paginate($perPage);

        return response()->json([
            'summary' => $summary,
            'by_type' => $byType,
            'by_status' => $byStatus,
            'issuance_trend' => $issuanceTrend,
            'by_training' => $byTraining,
            'by_exam' => $byExam,
            'top_certificate_holders' => $topCertificateHolders,
            'expiry_forecast' => $expiryForecast,
            'certificates' => $certificates,
        ]);
    }

    /**
     * Get detailed meeting reports
     * GET /api/v1/admin/reports/meetings
     */
    public function meetings(Request $request)
    {
        $dateRange = $this->getDateRange($request);
        $now = Carbon::now();

        // Build query with filters
        $query = Meeting::query();

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Filter by trainer_id
        if ($request->filled('trainer_id')) {
            $query->where('trainer_id', $request->trainer_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by is_recurring
        if ($request->has('is_recurring')) {
            $query->where('is_recurring', $request->boolean('is_recurring'));
        }

        // Filter by date range
        if ($dateRange['start']) {
            $query->where('created_at', '>=', $dateRange['start']);
        }
        if ($dateRange['end']) {
            $query->where('created_at', '<=', $dateRange['end']);
        }

        // Summary
        $summary = [
            'total_meetings' => Meeting::count(),
            'upcoming_meetings' => Meeting::where('start_time', '>', $now)->count(),
            'completed_meetings' => Meeting::where('start_time', '<', $now)->count(),
            'cancelled_meetings' => Meeting::where('status', 'cancelled')->count(),
        ];

        // Participation statistics
        $totalRegistrations = MeetingRegistration::count();
        $totalAttendees = MeetingRegistration::distinct('user_id')->count('user_id');
        $averageAttendees = $summary['total_meetings'] > 0 
            ? round($totalAttendees / $summary['total_meetings'], 2) 
            : 0;

        $participationStats = [
            'total_registrations' => $totalRegistrations,
            'total_attendees' => $totalAttendees,
            'average_attendees_per_meeting' => $averageAttendees,
            'attendance_rate' => $totalRegistrations > 0 ? 100 : 0, // Simplified
        ];

        // By category
        $byCategory = Meeting::select('category', DB::raw('COUNT(*) as count'))
            ->whereNotNull('category')
            ->groupBy('category')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($item) {
                $totalParticipants = MeetingRegistration::whereHas('meeting', function ($q) use ($item) {
                    $q->where('category', $item->category);
                })->distinct('user_id')->count('user_id');

                return [
                    'category' => $item->category,
                    'count' => $item->count,
                    'total_participants' => $totalParticipants,
                ];
            });

        // By trainer
        $byTrainer = User::where('user_type', 'trainer')
            ->whereHas('meetingsAsTrainer')
            ->get()
            ->map(function ($trainer) {
                $meetingsCount = Meeting::where('trainer_id', $trainer->id)->count();
                $totalParticipants = MeetingRegistration::whereHas('meeting', function ($q) use ($trainer) {
                    $q->where('trainer_id', $trainer->id);
                })->distinct('user_id')->count('user_id');

                return [
                    'trainer_id' => $trainer->id,
                    'trainer_name' => $trainer->first_name . ' ' . $trainer->last_name,
                    'meetings_count' => $meetingsCount,
                    'total_participants' => $totalParticipants,
                    'average_rating' => 0, // Can be calculated if ratings exist
                ];
            });

        // By status
        $byStatus = Meeting::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => $item->count];
            });

        // Top meetings
        $topMeetings = Meeting::withCount('registrations')
            ->get()
            ->map(function ($meeting) {
                $attendees = MeetingRegistration::where('meeting_id', $meeting->id)->count();
                $attendanceRate = $meeting->registrations_count > 0 
                    ? round(($attendees / $meeting->registrations_count) * 100, 2) 
                    : 0;

                return [
                    'meeting_id' => $meeting->id,
                    'title' => $meeting->title,
                    'date' => $meeting->start_time ? $meeting->start_time->toDateString() : null,
                    'participants_count' => $attendees,
                    'attendance_rate' => $attendanceRate,
                ];
            })
            ->sortByDesc('participants_count')
            ->take(10)
            ->values();

        // Recurring meetings
        $recurringMeetings = [
            'total_recurring' => Meeting::where('is_recurring', true)->count(),
            'total_single' => Meeting::where('is_recurring', false)->count(),
            'most_popular_recurrence' => null, // Can be calculated if needed
        ];

        // Filtered meetings list
        $perPage = min($request->get('per_page', 15), 100);
        $meetings = $query->with(['trainer', 'creator'])
            ->withCount('registrations')
            ->paginate($perPage);

        return response()->json([
            'summary' => $summary,
            'participation_stats' => $participationStats,
            'by_category' => $byCategory,
            'by_trainer' => $byTrainer,
            'by_status' => $byStatus,
            'top_meetings' => $topMeetings,
            'recurring_meetings' => $recurringMeetings,
            'meetings' => $meetings,
        ]);
    }

    /**
     * Get detailed forum reports
     * GET /api/v1/admin/reports/forum
     */
    public function forum(Request $request)
    {
        $dateRange = $this->getDateRange($request);

        // Build query with filters
        $query = ForumQuestion::query();

        // Filter by question_type
        if ($request->filled('question_type')) {
            $query->where('question_type', $request->question_type);
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by is_pinned
        if ($request->has('is_pinned')) {
            $query->where('is_pinned', $request->boolean('is_pinned'));
        }

        // Filter by date range
        if ($dateRange['start']) {
            $query->where('created_at', '>=', $dateRange['start']);
        }
        if ($dateRange['end']) {
            $query->where('created_at', '<=', $dateRange['end']);
        }

        // Summary
        $summary = [
            'total_questions' => ForumQuestion::count(),
            'total_answers' => ForumAnswer::count(),
            'total_views' => ForumQuestion::sum('views') ?? 0,
            'total_likes' => ForumQuestion::sum('likes_count') ?? 0,
        ];

        // Question statistics
        $questionStats = [
            'by_type' => ForumQuestion::select('question_type', DB::raw('COUNT(*) as count'))
                ->groupBy('question_type')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->question_type => $item->count];
                }),
            'by_status' => ForumQuestion::select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->status => $item->count];
                }),
            'average_answers_per_question' => $summary['total_questions'] > 0 
                ? round($summary['total_answers'] / $summary['total_questions'], 2) 
                : 0,
            'most_answered_questions' => ForumQuestion::withCount('answers')
                ->orderBy('answers_count', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($question) {
                    return [
                        'id' => $question->id,
                        'title' => $question->title,
                        'answers_count' => $question->answers_count,
                    ];
                }),
        ];

        // Activity trend (last 30 days)
        $activityTrend = ForumQuestion::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as questions_count'))
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                $answersCount = ForumAnswer::whereDate('created_at', $item->date)->count();
                return [
                    'date' => $item->date,
                    'questions_count' => $item->questions_count,
                    'answers_count' => $answersCount,
                ];
            });

        // Top contributors
        $topContributors = User::withCount(['forumQuestions', 'forumAnswers'])
            ->where(function ($q) {
                $q->has('forumQuestions')->orHas('forumAnswers');
            })
            ->get()
            ->map(function ($user) {
                $totalLikes = ForumQuestion::where('user_id', $user->id)->sum('likes_count') ?? 0;
                $answerLikes = ForumAnswer::where('user_id', $user->id)->sum('likes_count') ?? 0;

                return [
                    'user_id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'questions_asked' => $user->forum_questions_count,
                    'answers_provided' => $user->forum_answers_count,
                    'total_likes' => $totalLikes + $answerLikes,
                ];
            })
            ->sortByDesc('total_likes')
            ->take(10)
            ->values();

        // By category
        $byCategory = ForumQuestion::select('category', DB::raw('COUNT(*) as questions_count'))
            ->whereNotNull('category')
            ->groupBy('category')
            ->orderBy('questions_count', 'desc')
            ->get()
            ->map(function ($item) {
                $answersCount = ForumAnswer::whereHas('question', function ($q) use ($item) {
                    $q->where('category', $item->category);
                })->count();

                return [
                    'category' => $item->category,
                    'questions_count' => $item->questions_count,
                    'answers_count' => $answersCount,
                ];
            });

        // Engagement metrics
        $engagementMetrics = [
            'average_response_time' => null, // Can be calculated if needed
            'questions_resolved_rate' => null, // Can be calculated based on status
            'active_users_last_30_days' => User::whereHas('forumQuestions', function ($q) {
                $q->where('created_at', '>=', Carbon::now()->subDays(30));
            })
            ->orWhereHas('forumAnswers', function ($q) {
                $q->where('created_at', '>=', Carbon::now()->subDays(30));
            })
            ->distinct()
            ->count(),
        ];

        // Filtered questions list
        $perPage = min($request->get('per_page', 15), 100);
        $questions = $query->with(['user'])
            ->withCount('answers')
            ->paginate($perPage);

        return response()->json([
            'summary' => $summary,
            'question_stats' => $questionStats,
            'activity_trend' => $activityTrend,
            'top_contributors' => $topContributors,
            'by_category' => $byCategory,
            'engagement_metrics' => $engagementMetrics,
            'questions' => $questions,
        ]);
    }

    /**
     * Get detailed trainer reports
     * GET /api/v1/admin/reports/trainers
     */
    public function trainers(Request $request)
    {
        $dateRange = $this->getDateRange($request);

        // Build query with filters
        $query = User::where('user_type', 'trainer');

        // Filter by trainer_id
        if ($request->filled('trainer_id')) {
            $query->where('id', $request->trainer_id);
        }

        // Filter by date range (based on created_at)
        if ($dateRange['start']) {
            $query->where('created_at', '>=', $dateRange['start']);
        }
        if ($dateRange['end']) {
            $query->where('created_at', '<=', $dateRange['end']);
        }

        // Summary
        $summary = [
            'total_trainers' => User::where('user_type', 'trainer')->count(),
            'active_trainers' => User::where('user_type', 'trainer')
                ->where('is_active', true)
                ->count(),
            'trainers_with_published_trainings' => User::where('user_type', 'trainer')
                ->whereHas('trainings', function ($q) {
                    $q->where('status', 'published');
                })
                ->count(),
        ];

        // Performance statistics
        $performanceStats = [
            'total_trainings_created' => Training::whereNotNull('trainer_id')->count(),
            'total_exams_created' => Exam::whereHas('training', function ($q) {
                $q->whereNotNull('trainer_id');
            })->count(),
            'total_registrations' => TrainingRegistration::whereHas('training', function ($q) {
                $q->whereNotNull('trainer_id');
            })->count(),
            'average_rating' => \App\Models\TrainingRating::whereHas('training', function ($q) {
                $q->whereNotNull('trainer_id');
            })->avg('rating') ?? 0,
            'total_certificates_issued' => Certificate::whereHas('training', function ($q) {
                $q->whereNotNull('trainer_id');
            })->count(),
        ];

        // By trainer
        $byTrainer = User::where('user_type', 'trainer')
            ->withCount('trainings')
            ->get()
            ->map(function ($trainer) {
                $registrationsCount = TrainingRegistration::whereHas('training', function ($q) use ($trainer) {
                    $q->where('trainer_id', $trainer->id);
                })->count();

                $avgRating = \App\Models\TrainingRating::whereHas('training', function ($q) use ($trainer) {
                    $q->where('trainer_id', $trainer->id);
                })->avg('rating') ?? 0;

                $certificatesIssued = Certificate::whereHas('training', function ($q) use ($trainer) {
                    $q->where('trainer_id', $trainer->id);
                })->count();

                $totalMeetings = Meeting::where('trainer_id', $trainer->id)->count();

                return [
                    'trainer_id' => $trainer->id,
                    'name' => $trainer->first_name . ' ' . $trainer->last_name,
                    'trainings_count' => $trainer->trainings_count,
                    'registrations_count' => $registrationsCount,
                    'average_rating' => round($avgRating, 2),
                    'certificates_issued' => $certificatesIssued,
                    'total_meetings' => $totalMeetings,
                ];
            });

        // Top trainers
        $topTrainers = $byTrainer->map(function ($trainer) {
            // Calculate score based on multiple factors
            $score = ($trainer['trainings_count'] * 10) 
                + ($trainer['registrations_count'] * 2)
                + ($trainer['average_rating'] * 20)
                + ($trainer['certificates_issued'] * 5)
                + ($trainer['total_meetings'] * 3);

            return [
                'trainer_id' => $trainer['trainer_id'],
                'name' => $trainer['name'],
                'score' => round($score, 2),
            ];
        })
        ->sortByDesc('score')
        ->take(10)
        ->values();

        // Trainer engagement
        $trainerEngagement = [
            'most_active_trainers' => $byTrainer->sortByDesc('trainings_count')->take(10)->values(),
            'trainers_by_category' => [], // Can be calculated if trainer categories exist
            'average_trainings_per_trainer' => $summary['total_trainers'] > 0 
                ? round($performanceStats['total_trainings_created'] / $summary['total_trainers'], 2) 
                : 0,
        ];

        // Filtered trainers list
        $perPage = min($request->get('per_page', 15), 100);
        $trainers = $query->withCount('trainings')
            ->paginate($perPage);

        return response()->json([
            'summary' => $summary,
            'performance_stats' => $performanceStats,
            'by_trainer' => $byTrainer,
            'top_trainers' => $topTrainers,
            'trainer_engagement' => $trainerEngagement,
            'trainers' => $trainers,
        ]);
    }
}

