<?php
 
namespace App\Http\Controllers;
 
use App\Models\{Training, TrainingRegistration, Exam, ExamRegistration, Certificate};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegistrationController extends Controller
{
    
    public function registerTraining(Request $request, Training $training)
    {
        $registration = TrainingRegistration::firstOrCreate([
            'user_id' => $request->user()->id,
            'training_id' => $training->id,
        ], [
            'status' => 'approved',
            'registration_date' => now(),
        ]);
        return response()->json($registration, 201);
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
}
 
 

