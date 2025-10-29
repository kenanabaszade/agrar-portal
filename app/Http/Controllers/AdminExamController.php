<?php

namespace App\Http\Controllers;

use App\Models\ExamRegistration;
use App\Models\ExamUserAnswer;
use App\Models\ExamQuestion;
use App\Models\Certificate;
use App\Models\Training;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Process;

class AdminExamController extends Controller
{
    /**
     * Get all pending review exams
     */
    public function getPendingReviews(Request $request)
    {
        $pendingExams = ExamRegistration::where('status', 'pending_review')
            ->with(['user:id,first_name,last_name,email', 'exam:id,title,passing_score'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'message' => 'Pending review exams retrieved successfully',
            'data' => $pendingExams
        ]);
    }

    /**
     * Get specific exam registration for grading
     */
    public function getExamForGrading($registrationId)
    {
        $registration = ExamRegistration::with([
            'user:id,first_name,last_name,email',
            'exam:id,title,passing_score,training_id',
            'exam.training:id,title,has_certificate',
            'userAnswers.question'
        ])->find($registrationId);

        if (!$registration) {
            return response()->json([
                'message' => 'Exam registration not found'
            ], 404);
        }

        if ($registration->status !== 'pending_review') {
            return response()->json([
                'message' => 'This exam is not pending review'
            ], 400);
        }

        // Get text questions that need manual grading
        $textQuestions = $registration->userAnswers()
            ->whereHas('question', function ($query) {
                $query->where('question_type', 'text');
            })
            ->with('question')
            ->get();

        return response()->json([
            'message' => 'Exam data retrieved successfully',
            'registration' => [
                'id' => $registration->id,
                'user' => $registration->user,
                'exam' => $registration->exam,
                'score' => $registration->score,
                'auto_graded_score' => $registration->auto_graded_score,
                'started_at' => $registration->started_at,
                'finished_at' => $registration->finished_at,
                'attempt_number' => $registration->attempt_number,
            ],
            'text_questions' => $textQuestions->map(function ($answer) {
                return [
                    'id' => $answer->id,
                    'question_id' => $answer->question_id,
                    'question_text' => $answer->question->question_text,
                    'answer_text' => $answer->answer_text,
                    'answered_at' => $answer->answered_at,
                ];
            })
        ]);
    }

    /**
     * Grade text questions for an exam
     */
    public function gradeTextQuestions(Request $request, $registrationId)
    {
        $validated = $request->validate([
            'grades' => ['required', 'array'],
            'grades.*.answer_id' => ['required', 'integer'],
            'grades.*.is_correct' => ['required', 'boolean'],
            'grades.*.feedback' => ['nullable', 'string'],
            'admin_notes' => ['nullable', 'string'],
        ]);

        $registration = ExamRegistration::find($registrationId);
        if (!$registration) {
            return response()->json([
                'message' => 'Exam registration not found'
            ], 404);
        }

        if ($registration->status !== 'pending_review') {
            return response()->json([
                'message' => 'This exam is not pending review'
            ], 400);
        }

        try {
            DB::transaction(function () use ($registration, $validated) {
                $textQuestionsCorrect = 0;
                $textQuestionsTotal = 0;

                // Update each text question answer
                foreach ($validated['grades'] as $grade) {
                    $answer = ExamUserAnswer::find($grade['answer_id']);
                    if ($answer && $answer->registration_id === $registration->id) {
                        $answer->update([
                            'is_correct' => $grade['is_correct'],
                            'admin_feedback' => $grade['feedback'] ?? null,
                            'graded_at' => now(),
                            'graded_by' => auth()->id(),
                        ]);

                        $textQuestionsTotal++;
                        if ($grade['is_correct']) {
                            $textQuestionsCorrect++;
                        }
                    }
                }

                // Calculate final score
                $autoGradedQuestions = $registration->total_questions - $textQuestionsTotal;
                $autoGradedCorrect = 0;

                // Count auto-graded correct answers
                $autoGradedAnswers = ExamUserAnswer::where('registration_id', $registration->id)
                    ->whereHas('question', function ($query) {
                        $query->where('question_type', '!=', 'text');
                    })
                    ->get();

                foreach ($autoGradedAnswers as $answer) {
                    $question = ExamQuestion::find($answer->question_id);
                    if ($question && $question->isAnswerCorrect($answer)) {
                        $autoGradedCorrect++;
                    }
                }

                $totalCorrect = $autoGradedCorrect + $textQuestionsCorrect;
                $totalQuestions = $registration->total_questions;
                $finalScore = $totalQuestions > 0 ? (int) floor(($totalCorrect * 100) / $totalQuestions) : 0;

                // Determine if passed
                $passed = $finalScore >= $registration->exam->passing_score;

                // Update registration
                $registration->update([
                    'status' => $passed ? 'passed' : 'failed',
                    'score' => $finalScore,
                    'admin_notes' => $validated['admin_notes'] ?? null,
                    'graded_at' => now(),
                    'graded_by' => auth()->id(),
                ]);

                // Generate certificate if passed
                if ($passed) {
                    $training = Training::find($registration->exam->training_id);
                    if ($training && $training->has_certificate) {
                        $this->generatePdfCertificate($registration->user, $registration->exam, $training, $registration);
                    }
                }

                // Send email notification
                $this->sendGradingNotification($registration, $passed);
            });

            return response()->json([
                'message' => 'Text questions graded successfully',
                'registration_id' => $registrationId,
                'final_score' => $registration->fresh()->score,
                'status' => $registration->fresh()->status,
            ]);

        } catch (\Exception $e) {
            Log::error('Error grading text questions: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error grading text questions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate PDF certificate using Python script
     */
    private function generatePdfCertificate($user, $exam, $training, $registration)
    {
        try {
            // Prepare data for Python script
            $data = [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                ],
                'exam' => [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'description' => $exam->description,
                ],
                'training' => [
                    'id' => $training->id,
                    'title' => $training->title,
                    'description' => $training->description,
                ],
                'registration' => [
                    'id' => $registration->id,
                    'score' => $registration->score,
                    'finished_at' => $registration->finished_at->format('Y-m-d H:i:s'),
                ],
            ];

            // Write data to temporary file
            $jsonData = json_encode($data);
            $tempFile = tempnam(sys_get_temp_dir(), 'cert_data_');
            file_put_contents($tempFile, $jsonData);

            // Run Python script
            $pythonScript = base_path('certificate_generator.py');
            $result = Process::run("C:\\Python313\\python.exe {$pythonScript} --file {$tempFile}");

            // Clean up temp file
            unlink($tempFile);

            if ($result->successful()) {
                $output = json_decode($result->output(), true);
                
                // Create certificate record
                Certificate::create([
                    'user_id' => $user->id,
                    'related_training_id' => $training->id,
                    'related_exam_id' => $exam->id,
                    'certificate_number' => $output['certificate_number'],
                    'issue_date' => now(),
                    'expiry_date' => now()->addYear(),
                    'status' => 'active',
                    'pdf_path' => $output['pdf_path'],
                    'digital_signature' => $output['digital_signature'],
                ]);

                // Update registration with certificate
                $certificate = Certificate::where('digital_signature', $output['digital_signature'])->first();
                if ($certificate) {
                    $registration->update(['certificate_id' => $certificate->id]);
                }

                Log::info('Certificate generated successfully for user ' . $user->id . ' exam ' . $exam->id);
            } else {
                Log::error('Certificate generation failed: ' . $result->errorOutput());
            }

        } catch (\Exception $e) {
            Log::error('Error generating certificate: ' . $e->getMessage());
        }
    }

    /**
     * Send grading notification email
     */
    private function sendGradingNotification($registration, $passed)
    {
        try {
            $user = $registration->user;
            $exam = $registration->exam;
            
            if ($passed) {
                // Send success email with certificate
                $certificate = Certificate::where('user_id', $user->id)
                    ->where('related_exam_id', $exam->id)
                    ->first();
                
                Mail::to($user->email)->send(new \App\Mail\ExamPassedMail($user, $exam, $registration, $certificate));
            } else {
                // Send failed email
                Mail::to($user->email)->send(new \App\Mail\ExamFailedMail($user, $exam, $registration));
            }
        } catch (\Exception $e) {
            Log::error('Error sending grading notification: ' . $e->getMessage());
        }
    }
}
