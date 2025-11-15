<?php
 
namespace App\Http\Controllers;
 
use App\Models\{Training, TrainingRegistration, Exam, ExamRegistration, Certificate};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\NotificationService;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Mail\TrainingRegistrationNotification;

class RegistrationController extends Controller
{
    
    public function registerTraining(Request $request, Training $training)
    {
        $user = $request->user();
        
        $registration = TrainingRegistration::firstOrCreate([
            'user_id' => $user->id,
            'training_id' => $training->id,
        ], [
            'status' => 'approved',
            'registration_date' => now(),
        ]);

        // Send registration confirmation email
        try {
            $emailSent = app(NotificationService::class)->sendMail(
                $user,
                new TrainingRegistrationNotification($training, $user)
            );

            if ($emailSent) {
                \Log::info('Training registration email sent', [
                    'user_id' => $user->id,
                    'training_id' => $training->id,
                    'email' => $user->email
                ]);
            } else {
                \Log::info('Training registration email skipped (preferences)', [
                    'user_id' => $user->id,
                    'training_id' => $training->id,
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send training registration email', [
                'user_id' => $user->id,
                'training_id' => $training->id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);
        }

        return response()->json([
            'message' => 'Registration successful',
            'registration' => $registration,
            'email_sent' => $emailSent ?? false
        ], 201);
    }

    public function registerExam(Request $request, Exam $exam)
    {
        $registration = ExamRegistration::firstOrCreate([
            'user_id' => $request->user()->id,
            'exam_id' => $exam->id,
        ], [
            'status' => 'approved',
            'registration_date' => now(),
        ]);
        return response()->json($registration, 201);
    }

    public function cancelTrainingRegistration(Request $request, Training $training)
    {
        $user = $request->user();
        
        $registration = TrainingRegistration::where('user_id', $user->id)
            ->where('training_id', $training->id)
            ->first();

        if (!$registration) {
            return response()->json(['message' => 'Registration not found'], 404);
        }

        if ($registration->status === 'cancelled') {
            return response()->json(['message' => 'Registration already cancelled'], 400);
        }

        $registration->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return response()->json([
            'message' => 'Training registration cancelled successfully',
            'registration' => $registration
        ]);
    }

    /**
     * Get user's training registrations
     */
    public function myTrainingRegistrations(Request $request)
    {
        $user = $request->user();
        $perPage = min((int) $request->get('per_page', 15), 100);
        $page = (int) max($request->get('page', 1), 1);

        $registrations = TrainingRegistration::with(['training.trainer', 'training.modules.lessons'])
            ->where('user_id', $user->id)
            ->orderBy('registration_date', 'desc')
            ->get();

        $registrationTrainingIds = $registrations->pluck('training.id')->filter()->unique()->all();

        $videoProgressTrainingIds = \App\Models\UserTrainingProgress::where('user_id', $user->id)
            ->distinct()
            ->pluck('training_id')
            ->toArray();

        $missingVideoTrainingIds = array_values(array_diff($videoProgressTrainingIds, $registrationTrainingIds));

        $virtualRegistrations = collect();
        if (!empty($missingVideoTrainingIds)) {
            $videoTrainings = Training::with(['trainer', 'modules.lessons'])
                ->whereIn('id', $missingVideoTrainingIds)
                ->where('type', 'video')
                ->get();

            $latestProgressTimes = \App\Models\UserTrainingProgress::where('user_id', $user->id)
                ->whereIn('training_id', $missingVideoTrainingIds)
                ->select('training_id')
                ->selectRaw('MAX(updated_at) as last_activity_at')
                ->groupBy('training_id')
                ->pluck('last_activity_at', 'training_id');

            foreach ($videoTrainings as $training) {
                $virtualRegistration = new TrainingRegistration([
                    'user_id' => $user->id,
                    'training_id' => $training->id,
                    'status' => 'in_progress',
                    'registration_date' => $latestProgressTimes[$training->id] ?? now(),
                ]);
                $virtualRegistration->exists = false;
                $virtualRegistration->setAttribute('is_virtual', true);
                $virtualRegistration->setAttribute('created_at', $latestProgressTimes[$training->id] ?? now());
                $virtualRegistration->setAttribute('updated_at', $latestProgressTimes[$training->id] ?? now());
                $virtualRegistration->setRelation('training', $training);

                $virtualRegistrations->push($virtualRegistration);
            }
        }

        $combined = $registrations->merge($virtualRegistrations)
            ->sortByDesc(function ($registration) {
                return $registration->registration_date ?? $registration->updated_at ?? $registration->created_at ?? now();
            })
            ->values();

        $trainingIds = $combined->pluck('training.id')->filter()->unique()->values();

        $certificates = Certificate::where('user_id', $user->id)
            ->whereIn('related_training_id', $trainingIds)
            ->get()
            ->keyBy('related_training_id');

        $completedCounts = \App\Models\UserTrainingProgress::where('user_id', $user->id)
            ->whereIn('training_id', $trainingIds)
            ->where('status', 'completed')
            ->select('training_id', DB::raw('COUNT(*) as completed_count'))
            ->groupBy('training_id')
            ->pluck('completed_count', 'training_id');

        $items = $combined->slice(($page - 1) * $perPage, $perPage)->values();

        $items->transform(function ($registration) use ($user, $certificates, $completedCounts) {
            $training = $registration->training;

            if ($training->type === 'offline') {
                $training->offline_details = $training->offline_details;
                $training->address = $training->offline_details['address'] ?? null;
                $training->coordinates = $training->offline_details['coordinates'] ?? null;
                $training->participant_size = $training->offline_details['participant_size'] ?? null;
            }

            $training->trainer_name = $training->trainer ?
                $training->trainer->first_name . ' ' . $training->trainer->last_name : null;
            $training->trainer_email = $training->trainer ? $training->trainer->email : null;

            $training->banner_url = $training->banner_url;

            $lastProgress = \App\Models\UserTrainingProgress::with('lesson.module')
                ->where('user_id', $user->id)
                ->where('training_id', $training->id)
                ->orderBy('updated_at', 'desc')
                ->first();

            $progressSummary = \App\Models\UserTrainingProgress::where('user_id', $user->id)
                ->where('training_id', $training->id)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $totalLessons = $training->modules->sum(function ($module) {
                return $module->lessons->count();
            });

            $completedLessons = (int) ($completedCounts[$training->id] ?? 0);
            $completionPercentage = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100, 2) : 0;

            $nextLesson = null;
            if ($lastProgress && $lastProgress->status !== 'completed') {
                $currentModule = $lastProgress->lesson->module;
                $nextLesson = $currentModule->lessons()
                    ->where('id', '>', $lastProgress->lesson->id)
                    ->orderBy('id')
                    ->first();

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
                $nextLesson = $training->modules()
                    ->with(['lessons' => function ($query) {
                        $query->orderBy('id');
                    }])
                    ->orderBy('id')
                    ->get()
                    ->pluck('lessons')
                    ->flatten()
                    ->first(function ($lesson) use ($user, $training) {
                        $progress = \App\Models\UserTrainingProgress::where('user_id', $user->id)
                            ->where('training_id', $training->id)
                            ->where('lesson_id', $lesson->id)
                            ->first();
                        return !$progress || $progress->status !== 'completed';
                    });
            }

            $certificate = $certificates->get($training->id);

            if ($totalLessons > 0) {
                $isTrainingCompleted = $completedLessons >= $totalLessons;
            } else {
                $isTrainingCompleted = ($registration->status === 'completed') || (bool) $certificate;
            }

            $completionDate = null;
            if ($isTrainingCompleted) {
                if ($certificate) {
                    $completionDate = $certificate->created_at;
                } elseif ($registration->status === 'completed') {
                    $completionDate = $registration->updated_at;
                }
            }

            $totalDurationMinutes = $training->modules->sum(function ($module) {
                return $module->lessons->sum('duration_minutes');
            });

            $training->user_progress = [
                'is_completed' => $isTrainingCompleted,
                'completion_date' => $completionDate,
                'certificate_id' => $registration->certificate_id ?: ($certificate?->id),
                'current_chapter' => ($lastProgress && $lastProgress->lesson) ? [
                    'id' => $lastProgress->lesson->module->id,
                    'title' => $lastProgress->lesson->module->title,
                    'lesson_id' => $lastProgress->lesson->id,
                    'lesson_title' => $lastProgress->lesson->title,
                ] : null,
                'next_lesson' => $nextLesson ? [
                    'id' => $nextLesson->id,
                    'title' => $nextLesson->title,
                    'module_id' => $nextLesson->module->id,
                    'module_title' => $nextLesson->module->title,
                ] : null,
                'progress_summary' => $progressSummary,
                'total_lessons' => $totalLessons,
                'completed_lessons' => $completedLessons,
                'in_progress_lessons' => $progressSummary['in_progress'] ?? 0,
                'not_started_lessons' => $progressSummary['not_started'] ?? 0,
                'completion_percentage' => $completionPercentage,
                'total_duration_minutes' => $totalDurationMinutes,
                'total_duration_hours' => round($totalDurationMinutes / 60, 2),
                'is_in_progress' => !$isTrainingCompleted,
                'has_certificate' => (bool) $certificate,
                'certificate_id' => $registration->certificate_id ?: ($certificate?->id),
                'is_virtual_registration' => (bool) $registration->getAttribute('is_virtual'),
            ];

            return $registration;
        });

        $paginator = new LengthAwarePaginator(
            $items,
            $combined->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return response()->json($paginator);
    }
}
 
 

