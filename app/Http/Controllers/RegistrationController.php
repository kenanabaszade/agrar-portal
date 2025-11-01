<?php
 
namespace App\Http\Controllers;
 
use App\Models\{Training, TrainingRegistration, Exam, ExamRegistration, Certificate};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
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
            Mail::to($user->email)->send(
                new TrainingRegistrationNotification($training, $user)
            );
            
            \Log::info('Training registration email sent', [
                'user_id' => $user->id,
                'training_id' => $training->id,
                'email' => $user->email
            ]);
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
            'email_sent' => true
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
        
        $registrations = TrainingRegistration::with(['training.trainer', 'training.modules.lessons'])
            ->where('user_id', $user->id)
            ->orderBy('registration_date', 'desc')
            ->paginate(15);

        // Add training details to each registration
        $registrations->getCollection()->transform(function ($registration) use ($user) {
            $training = $registration->training;
            
            // Add offline specific details if training is offline
            if ($training->type === 'offline') {
                $training->offline_details = $training->offline_details;
                $training->address = $training->offline_details['address'] ?? null;
                $training->coordinates = $training->offline_details['coordinates'] ?? null;
                $training->participant_size = $training->offline_details['participant_size'] ?? null;
            }
            
            // Add trainer details
            $training->trainer_name = $training->trainer ? 
                $training->trainer->first_name . ' ' . $training->trainer->last_name : null;
            $training->trainer_email = $training->trainer ? $training->trainer->email : null;
            $training->trainer_phone = $training->trainer ? $training->trainer->phone : null;
            
            // Add banner URL
            $training->banner_url = $training->banner_url;
            
            // Add user progress information for card display
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
            
            $totalLessons = $training->modules->sum(function($module) {
                return $module->lessons->count();
            });
            
            $completedLessons = $progressSummary['completed'] ?? 0;
            $completionPercentage = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100, 2) : 0;
            
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
            
            if ($registration->status === 'completed') {
                // For non-video trainings with registration, use updated_at as completion date
                $isTrainingCompleted = true;
                $completionDate = $registration->updated_at;
            } elseif ($training->type === 'video') {
                // For video trainings, check if user has certificate
                $certificate = \App\Models\Certificate::where('user_id', $user->id)
                    ->where('related_training_id', $training->id)
                    ->first();
                
                if ($certificate) {
                    $isTrainingCompleted = true;
                    $completionDate = $certificate->created_at;
                }
            }
            
            // Calculate total duration in minutes
            $totalDurationMinutes = $training->modules->sum(function($module) {
                return $module->lessons->sum('duration_minutes');
            });
            
            // Add user progress information
            $training->user_progress = [
                'is_completed' => $isTrainingCompleted,
                'completion_date' => $completionDate,
                'certificate_id' => $registration->certificate_id,
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
                    'module_title' => $nextLesson->module->title
                ] : null,
                'progress_summary' => $progressSummary,
                'total_lessons' => $totalLessons,
                'completed_lessons' => $completedLessons,
                'in_progress_lessons' => $progressSummary['in_progress'] ?? 0,
                'not_started_lessons' => $progressSummary['not_started'] ?? 0,
                'completion_percentage' => $completionPercentage,
                'total_duration_minutes' => $totalDurationMinutes,
                'total_duration_hours' => round($totalDurationMinutes / 60, 2)
            ];
            
            return $registration;
        });

        return response()->json($registrations);
    }
}
 
 

