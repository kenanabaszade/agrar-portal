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
        $registrations->getCollection()->transform(function ($registration) {
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
            
            return $registration;
        });

        return response()->json($registrations);
    }
}
 
 

