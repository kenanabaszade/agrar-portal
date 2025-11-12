<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Training;
use App\Models\TrainingRegistration;
use App\Models\Certificate;
use App\Models\Meeting;
use App\Models\MeetingRegistration;
use App\Models\UserTrainingProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $now = Carbon::now();
        $currentMonth = $now->copy()->startOfMonth();
        $lastMonth = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        // Welcome Section
        $welcomeData = [
            'user_name' => $user->first_name . ' ' . $user->last_name,
            'username' => $user->username ?? $user->email,
            'active_trainings_count' => Training::where('start_date', '<=', $now)
                ->where(function ($query) use ($now) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>=', $now);
                })
                ->count(),
            'new_users_percentage' => $this->calculateNewUsersPercentage($currentMonth, $lastMonth)
        ];

        // Stats Section
        $statsData = [
            'total_trainings' => Training::count(),
            'active_farmers' => $this->getActiveFarmersCount(),
            'certificates_issued' => Certificate::count(),
            'new_users_this_month' => User::where('created_at', '>=', $currentMonth)->count(),
            'growth_data' => [
                'trainings_growth' => $this->calculateGrowthPercentageWithCurrent(
                    Training::where('created_at', '<', $lastMonth)->count(),
                    Training::count()
                ),
                'users_growth' => $this->calculateGrowthPercentageWithCurrent(
                    User::where('created_at', '<', $lastMonth)->count(),
                    User::count()
                ),
                'certificates_growth' => $this->calculateGrowthPercentageWithCurrent(
                    Certificate::where('created_at', '<', $lastMonth)->count(),
                    Certificate::count()
                )
            ]
        ];

        // Recent Activities
        $recentActivities = $this->getRecentActivities();

        // Popular Trainings
        $popularTrainings = $this->getPopularTrainings();

        // Upcoming Events
        $upcomingEvents = $this->getUpcomingEvents();

        return response()->json([
            'welcome' => $welcomeData,
            'stats' => $statsData,
            'recent_activities' => $recentActivities,
            'popular_trainings' => $popularTrainings,
            'upcoming_events' => $upcomingEvents
        ]);
    }

    private function calculateNewUsersPercentage($currentMonth, $lastMonth)
    {
        $currentMonthUsers = User::where('created_at', '>=', $currentMonth)->count();
        $lastMonthUsers = User::where('created_at', '>=', $lastMonth)
            ->where('created_at', '<', $currentMonth)->count();

        if ($lastMonthUsers == 0) {
            return $currentMonthUsers > 0 ? 100 : 0;
        }

        return round((($currentMonthUsers - $lastMonthUsers) / $lastMonthUsers) * 100, 2);
    }

    private function calculateGrowthPercentage($oldValue, $newValue)
    {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : 0;
        }
        return round((($newValue - $oldValue) / $oldValue) * 100, 2);
    }

    private function calculateGrowthPercentageWithCurrent($oldValue, $currentValue)
    {
        if ($oldValue == 0) {
            return $currentValue > 0 ? 100 : 0;
        }
        return round((($currentValue - $oldValue) / $oldValue) * 100, 2);
    }

    private function getRecentActivities()
    {
        $activities = [];

        // Recent training additions
        $recentTrainings = Training::with('trainer')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        foreach ($recentTrainings as $training) {
            $activities[] = [
                'type' => 'training_added',
                'title' => 'Yeni təlim əlavə edildi',
                'description' => $training->title . ' təlimi ' . $training->trainer->first_name . ' ' . $training->trainer->last_name . ' tərəfindən',
                'time_ago' => $this->getTimeAgo($training->created_at)
            ];
        }

        // Recent user registrations
        $recentUsers = User::where('user_type', 'farmer')
            ->orderBy('created_at', 'desc')
            ->limit(2)
            ->get();

        if ($recentUsers->count() > 0) {
            $activities[] = [
                'type' => 'user_registration',
                'title' => 'Yeni istifadəçi qeydiyyatı',
                'description' => $recentUsers->count() . ' yeni fermer qeydiyyatdan keçdi',
                'time_ago' => $this->getTimeAgo($recentUsers->first()->created_at)
            ];
        }

        // Recent certificates
        $recentCertificates = Certificate::with('training')
            ->orderBy('created_at', 'desc')
            ->limit(2)
            ->get();

        foreach ($recentCertificates as $certificate) {
            $activities[] = [
                'type' => 'certificate_issued',
                'title' => 'Sertifikatlar verildi',
                'description' => $certificate->training->title . ' təlimi üçün ' . $certificate->id . ' sertifikat',
                'time_ago' => $this->getTimeAgo($certificate->created_at)
            ];
        }

        // Recent webinars/meetings
        $recentMeetings = Meeting::orderBy('start_time', 'desc')
            ->limit(1)
            ->get();

        foreach ($recentMeetings as $meeting) {
            $participants = MeetingRegistration::where('meeting_id', $meeting->id)->count();
            $activities[] = [
                'type' => 'webinar_completed',
                'title' => 'Vebinar tamamlandı',
                'description' => $meeting->title . ' vebinarında ' . $participants . ' iştirakçı',
                'time_ago' => $this->getTimeAgo($meeting->start_time)
            ];
        }

        // Sort by time and limit to 4
        usort($activities, function ($a, $b) {
            return strtotime($b['time_ago']) - strtotime($a['time_ago']);
        });

        return array_slice($activities, 0, 4);
    }

    private function getPopularTrainings()
    {
        return Training::with(['trainer', 'modules.lessons'])
            ->withCount('registrations')
            ->orderBy('registrations_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($training) {
                // Calculate total duration from lessons
                $totalMinutes = 0;
                foreach ($training->modules as $module) {
                    foreach ($module->lessons as $lesson) {
                        $totalMinutes += $lesson->duration_minutes ?? 0;
                    }
                }

                return [
                    'id' => $training->id,
                    'title' => $training->title,
                    'trainer_name' => $training->trainer->first_name . ' ' . $training->trainer->last_name,
                    'difficulty' => $training->difficulty ?? 'beginner',
                    'duration_hours' => round($totalMinutes / 60, 1),
                    'participants_count' => $training->registrations_count
                ];
            });
    }

    private function getUpcomingEvents()
    {
        $now = Carbon::now();
        $upcomingEvents = [];

        // Upcoming trainings
        $upcomingTrainings = Training::where('start_date', '>', $now)
            ->orderBy('start_date', 'asc')
            ->limit(3)
            ->get();

        foreach ($upcomingTrainings as $training) {
            $registrations = TrainingRegistration::where('training_id', $training->id)->count();
            $upcomingEvents[] = [
                'id' => $training->id,
                'title' => $training->title,
                'type' => 'training',
                'datetime' => $training->start_date,
                'formatted_time' => $this->formatUpcomingTime($training->start_date),
                'registrations_count' => $registrations
            ];
        }

        // Upcoming meetings
        $upcomingMeetings = Meeting::where('start_time', '>', $now)
            ->orderBy('start_time', 'asc')
            ->limit(3)
            ->get();

        foreach ($upcomingMeetings as $meeting) {
            $registrations = MeetingRegistration::where('meeting_id', $meeting->id)->count();
            $upcomingEvents[] = [
                'id' => $meeting->id,
                'title' => $meeting->title,
                'type' => 'webinar',
                'datetime' => $meeting->start_time,
                'formatted_time' => $this->formatUpcomingTime($meeting->start_time),
                'registrations_count' => $registrations
            ];
        }

        // Sort by datetime and limit to 5
        usort($upcomingEvents, function ($a, $b) {
            return strtotime($a['datetime']) - strtotime($b['datetime']);
        });

        return array_slice($upcomingEvents, 0, 5);
    }

    private function getTimeAgo($datetime)
    {
        $carbon = Carbon::parse($datetime);
        $now = Carbon::now();
        
        if ($carbon->diffInHours($now) < 1) {
            return $carbon->diffInMinutes($now) . ' dəqiqə əvvəl';
        } elseif ($carbon->diffInDays($now) < 1) {
            return $carbon->diffInHours($now) . ' saat əvvəl';
        } else {
            return $carbon->diffInDays($now) . ' gün əvvəl';
        }
    }

    private function formatUpcomingTime($datetime)
    {
        $carbon = Carbon::parse($datetime);
        $now = Carbon::now();
        
        // If the date is in the past, show how many days ago
        if ($carbon->isPast()) {
            $daysAgo = $carbon->diffInDays($now);
            $hoursAgo = $carbon->diffInHours($now);
            
            if ($daysAgo == 0) {
                if ($hoursAgo < 1) {
                    return $carbon->diffInMinutes($now) . ' dəqiqə əvvəl';
                } else {
                    return $hoursAgo . ' saat əvvəl';
                }
            } else {
                return $daysAgo . ' gün əvvəl';
            }
        }
        
        // If the date is in the future
        if ($carbon->isTomorrow()) {
            return 'Sabah saat ' . $carbon->format('H:i');
        } elseif ($carbon->isToday()) {
            return 'Bu gün saat ' . $carbon->format('H:i');
        } elseif ($now->diffInDays($carbon) <= 7) {
            $daysLeft = floor($now->diffInDays($carbon));
            $hoursLeft = floor($now->diffInHours($carbon) % 24);
            
            if ($daysLeft == 1) {
                return 'Sabah saat ' . $carbon->format('H:i');
            } elseif ($daysLeft <= 3) {
                return $daysLeft . ' gün qalıb';
            } else {
                if ($hoursLeft > 0) {
                    return $daysLeft . ' gün ' . $hoursLeft . ' saat qalıb';
                } else {
                    return $daysLeft . ' gün qalıb';
                }
            }
        } else {
            return $carbon->format('d.m.Y H:i');
        }
    }

    private function getActiveFarmersCount()
    {
        $now = Carbon::now();
        $thirtyDaysAgo = $now->copy()->subDays(30);
        
        return User::where('user_type', 'farmer')
            ->where('is_active', true)
            ->where(function ($query) use ($now, $thirtyDaysAgo) {
                // Son 30 gün ərzində aktiv olan istifadəçilər
                $query->where('last_login_at', '>=', $thirtyDaysAgo)
                    ->orWhere('updated_at', '>=', $thirtyDaysAgo)
                    ->orWhereHas('registrations', function ($q) use ($thirtyDaysAgo) {
                        // Training-ə qeydiyyatdan keçənlər
                        $q->where('created_at', '>=', $thirtyDaysAgo);
                    })
                    ->orWhereHas('userTrainingProgress', function ($q) use ($thirtyDaysAgo) {
                        // Təlim tərəqqisi olanlar
                        $q->where('updated_at', '>=', $thirtyDaysAgo);
                    });
            })
            ->count();
    }

    /**
     * Get user-specific training statistics
     * GET /api/v1/user-statistics
     */
    public function userStatistics(Request $request)
    {
        $user = $request->user();
        $now = Carbon::now();
        $currentMonth = $now->copy()->startOfMonth();
        $currentWeekStart = $now->copy()->startOfWeek();
        $lastWeekStart = $currentWeekStart->copy()->subWeek();
        $lastMonthStart = $currentMonth->copy()->subMonth();

        // 1. Completed Courses (Tamamlanan Kurslar)
        $completedCourses = $this->getCompletedCourses($user);
        $completedThisMonth = $this->getCompletedCoursesThisMonth($user, $currentMonth);

        // 2. Ongoing Courses (Davam edən Kurslar)
        $ongoingCourses = $this->getOngoingCourses($user);

        // 3. Certificates Earned (Qazanılan Sertifikatlar)
        $certificatesEarned = $this->getCertificatesEarned($user);
        $newCertificates = $this->getNewCertificates($user, $currentMonth);

        // 4. Total Learning Hours (Ümumi Öyrənmə Saatı)
        $totalLearningMinutes = $this->getTotalLearningMinutes($user);
        $learningMinutesThisWeek = $this->getLearningMinutesThisWeek($user, $currentWeekStart);

        $trainingSummaries = $this->getUserTrainingSummaries($user);
        $ongoingCourses = $this->getOngoingCourses($user, $trainingSummaries);
        $averageProgress = $this->getAverageProgress($user, $trainingSummaries);

        return response()->json([
            'completed_courses' => [
                'count' => $completedCourses,
                'this_month_change' => $completedThisMonth,
                'goal_percentage' => $this->calculateGoalPercentage($completedCourses, $completedCourses + $ongoingCourses),
                'count_of_all_courses' => $completedCourses + $ongoingCourses,
                'user_completes' => $completedCourses,
            ],
            'ongoing_courses' => [
                'count' => $ongoingCourses,
                'average_progress' => $averageProgress
            ],
            'certificates_earned' => [
                'count' => $certificatesEarned,
                'new_certificates' => $newCertificates > 0,
                'completion_percentage' => $this->calculateGoalPercentage($certificatesEarned, $completedCourses)
            ],
            'total_learning_hours' => [
                'minutes' => (int) round($totalLearningMinutes),
                'this_week_change' => (int) round($learningMinutesThisWeek),
                'goal_percentage' => $this->calculateGoalPercentage($totalLearningMinutes, 50 * 60),
                'total_learning_minutes' => $trainingSummaries ? $this->sumTrainingDurations($trainingSummaries) : (int) round($totalLearningMinutes),
                'user_complete_percentage' => $trainingSummaries ? $this->calculateUserCompletionPercentage($trainingSummaries) : 0,
            ]
        ]);
    }

    /**
     * Get completed courses count
     */
    private function getCompletedCourses($user)
    {
        // Count trainings where user has completed via registration
        $completedViaRegistration = TrainingRegistration::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();
        
        // Get unique training IDs from completed registrations
        $completedTrainingIds = TrainingRegistration::where('user_id', $user->id)
            ->where('status', 'completed')
            ->pluck('training_id');
        
        // Count certificates for trainings NOT in completed registrations (video trainings)
        $completedViaCertificate = Certificate::where('user_id', $user->id)
            ->whereNotNull('related_training_id')
            ->whereNotIn('related_training_id', $completedTrainingIds)
            ->count();
        
        return $completedViaRegistration + $completedViaCertificate;
    }

    /**
     * Get completed courses this month
     */
    private function getCompletedCoursesThisMonth($user, $currentMonth)
    {
        // Count registrations completed this month
        $completedViaRegistration = TrainingRegistration::where('user_id', $user->id)
            ->where('status', 'completed')
            ->where('updated_at', '>=', $currentMonth)
            ->count();
        
        // Get unique training IDs from completed registrations
        $completedTrainingIds = TrainingRegistration::where('user_id', $user->id)
            ->where('status', 'completed')
            ->pluck('training_id');
        
        // Count certificates issued this month for video trainings
        $completedViaCertificate = Certificate::where('user_id', $user->id)
            ->whereNotNull('related_training_id')
            ->where('created_at', '>=', $currentMonth)
            ->whereNotIn('related_training_id', $completedTrainingIds)
            ->count();
        
        return $completedViaRegistration + $completedViaCertificate;
    }

    /**
     * Get ongoing courses count
     */
    private function getOngoingCourses($user, ?array $trainingSummaries = null)
    {
        $summaries = $trainingSummaries ?? $this->getUserTrainingSummaries($user);

        if (empty($summaries)) {
            return 0;
        }

        return collect($summaries)
            ->where('is_completed', false)
            ->count();
    }

    /**
     * Get average progress percentage for ongoing courses
     */
    private function getAverageProgress($user, ?array $trainingSummaries = null)
    {
        $summaries = $trainingSummaries ?? $this->getUserTrainingSummaries($user);

        if (empty($summaries)) {
            return 0;
        }

        $ongoing = array_filter($summaries, function ($summary) {
            return $summary['is_completed'] === false;
        });

        if (empty($ongoing)) {
            return 0;
        }

        $sum = array_reduce($ongoing, function ($carry, $summary) {
            return $carry + ($summary['completion_percentage'] ?? 0);
        }, 0.0);

        $average = $sum / count($ongoing);

        return (int) round($average, 0);
    }

    /**
     * Get certificates earned count
     */
    private function getCertificatesEarned($user)
    {
        return Certificate::where('user_id', $user->id)
            ->count();
    }

    /**
     * Get new certificates this month
     */
    private function getNewCertificates($user, $currentMonth)
    {
        return Certificate::where('user_id', $user->id)
            ->where('created_at', '>=', $currentMonth)
            ->count();
    }

    /**
     * Get total learning hours
     */
    private function getTotalLearningMinutes($user)
    {
        // Get all completed lessons with their actual durations
        $completedProgress = UserTrainingProgress::with('lesson')
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->get();
        
        $totalMinutes = 0;
        foreach ($completedProgress as $progress) {
            // Use lesson's actual duration_minutes, or fallback to time_spent if duration not set
            if ($progress->lesson && $progress->lesson->duration_minutes > 0) {
                $totalMinutes += $progress->lesson->duration_minutes;
            } elseif ($progress->time_spent) {
                // Fallback to time_spent (in seconds) converted to minutes
                $totalMinutes += (int) $progress->time_spent / 60;
            }
        }

        return $totalMinutes;
    }

    /**
     * Get learning hours this week
     */
    private function getLearningMinutesThisWeek($user, $currentWeekStart)
    {
        // Get completed lessons this week
        $progresses = UserTrainingProgress::with('lesson')
            ->where('user_id', $user->id)
            ->where('completed_at', '>=', $currentWeekStart)
            ->where('status', 'completed')
            ->get();
        
        $totalMinutes = 0;
        foreach ($progresses as $progress) {
            // Use lesson's actual duration_minutes, or fallback to time_spent if duration not set
            if ($progress->lesson && $progress->lesson->duration_minutes > 0) {
                $totalMinutes += $progress->lesson->duration_minutes;
            } elseif ($progress->time_spent) {
                // Fallback to time_spent (in seconds) converted to minutes
                $totalMinutes += (int) $progress->time_spent / 60;
            }
        }

        return $totalMinutes;
    }

    /**
     * Calculate goal percentage (simple helper)
     */
    private function calculateGoalPercentage($current, $goal)
    {
        if ($goal == 0) {
            return 0;
        }
        $percentage = ($current / $goal) * 100;
        return min(round($percentage, 0), 100); // Cap at 100%
    }

    /**
     * Build training progress summaries for the given user.
     */
    private function getUserTrainingSummaries($user): array
    {
        $registrations = TrainingRegistration::where('user_id', $user->id)->get(['training_id', 'status']);
        $registrationStatuses = $registrations->pluck('status', 'training_id')->toArray();
        $trainingIdsFromRegistrations = $registrations->pluck('training_id')->toArray();

        $progressStats = UserTrainingProgress::where('user_id', $user->id)
            ->select('training_id')
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_lessons")
            ->groupBy('training_id')
            ->get();
        $completedLessonsMap = $progressStats->pluck('completed_lessons', 'training_id')->map(fn($value) => (int) $value)->toArray();
        $progressTrainingIds = $progressStats->pluck('training_id')->toArray();

        $certificateMap = Certificate::where('user_id', $user->id)
            ->whereNotNull('related_training_id')
            ->pluck('id', 'related_training_id')
            ->toArray();
        $certificateTrainingIds = array_keys($certificateMap);

        $trainingIds = array_unique(array_merge($trainingIdsFromRegistrations, $progressTrainingIds, $certificateTrainingIds));

        if (empty($trainingIds)) {
            return [];
        }

        $trainings = Training::with(['modules.lessons'])
            ->whereIn('id', $trainingIds)
            ->get()
            ->keyBy('id');

        $summaries = [];

        foreach ($trainingIds as $trainingId) {
            if (!isset($trainings[$trainingId])) {
                continue;
            }

            $training = $trainings[$trainingId];
            $totalLessons = $training->modules->sum(function ($module) {
                return $module->lessons->count();
            });
            $completedLessons = $completedLessonsMap[$trainingId] ?? 0;
            $registrationStatus = $registrationStatuses[$trainingId] ?? null;
            $hasCertificate = array_key_exists($trainingId, $certificateMap);

            if ($totalLessons > 0) {
                $isCompleted = $completedLessons >= $totalLessons;
                $completionPercentage = $totalLessons > 0
                    ? round(($completedLessons / $totalLessons) * 100, 2)
                    : 0;
            } else {
                $isCompleted = ($registrationStatus === 'completed') || $hasCertificate;
                $completionPercentage = $isCompleted ? 100 : 0;
            }

            $summaries[$trainingId] = [
                'training_id' => $trainingId,
                'total_lessons' => $totalLessons,
                'completed_lessons' => $completedLessons,
                'registration_status' => $registrationStatus,
                'has_certificate' => $hasCertificate,
                'is_completed' => $isCompleted,
                'completion_percentage' => $completionPercentage,
                'total_duration_minutes' => $training->modules->sum(function ($module) {
                    return $module->lessons->sum('duration_minutes');
                }),
            ];
        }

        return $summaries;
    }

    private function sumTrainingDurations(array $summaries): int
    {
        $totalMinutes = 0;

        foreach ($summaries as $summary) {
            if (!empty($summary['total_duration_minutes'])) {
                $totalMinutes += (int) $summary['total_duration_minutes'];
            }
        }

        return $totalMinutes;
    }

    private function calculateUserCompletionPercentage(array $summaries): int
    {
        $totalLessons = 0;
        $completedLessons = 0;

        foreach ($summaries as $summary) {
            $totalLessons += (int) ($summary['total_lessons'] ?? 0);
            $completedLessons += (int) ($summary['completed_lessons'] ?? 0);
        }

        if ($totalLessons === 0) {
            return 0;
        }

        return (int) round(($completedLessons / $totalLessons) * 100);
    }
}
