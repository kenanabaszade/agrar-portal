<?php

namespace App\Http\Controllers;

use App\Models\ExamRegistration;
use App\Models\ExamUserAnswer;
use App\Models\ExamQuestion;
use App\Models\Certificate;
use App\Models\Training;
use App\Models\User;
use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Process;

class AdminExamController extends Controller
{
    /**
     * Get exam details for admin (with all questions, no limit)
     */
    public function show(Exam $exam)
    {
        $exam->load([
            'training.trainer',
            'questions.choices',
            'registrations' => function ($query) {
                $query->with('user:id,first_name,last_name,email')
                      ->orderBy('created_at', 'desc')
                      ->limit(10);
            }
        ]);

        // Add statistics
        $totalRegistrations = $exam->registrations()->count();
        $completedRegistrations = $exam->registrations()
            ->whereIn('status', ['passed', 'failed', 'completed'])->count();
        $passedRegistrations = $exam->registrations()
            ->where('status', 'passed')->count();

        $exam->stats = [
            'total_registrations' => $totalRegistrations,
            'completed_registrations' => $completedRegistrations,
            'passed_registrations' => $passedRegistrations,
            'completion_rate' => $totalRegistrations > 0
                ? round(($completedRegistrations / $totalRegistrations) * 100, 1)
                : 0,
            'pass_rate' => $completedRegistrations > 0
                ? round(($passedRegistrations / $completedRegistrations) * 100, 1)
                : 0,
            'average_score' => $exam->registrations()
                ->whereNotNull('score')->avg('score') ?: 0,
            'total_questions' => $exam->questions()->count(),
        ];

        // Ensure all exam configuration fields are included in response
        $exam->makeVisible([
            'exam_question_count',
            'randomize_questions',
            'randomize_choices',
            'show_results_immediately',
            'show_correct_answers',
            'show_explanations',
            'allow_tab_switching',
            'track_tab_changes',
            'auto_submit',
            'time_warning_minutes',
            'max_attempts',
            'passing_score',
            'duration_minutes',
            'start_date',
            'end_date',
            'rules',
            'instructions',
            'hashtags',
        ]);
        
        // Add alias fields for frontend compatibility
        $exam->shuffle_questions = $exam->randomize_questions ?? false;
        $exam->shuffle_choices = $exam->randomize_choices ?? false;
        $exam->show_result_immediately = $exam->show_results_immediately ?? false;

        // NOTE: Admin view shows ALL questions regardless of exam_question_count limit
        // Questions are sorted by sequence, no randomization for admin view
        $exam->questions = $exam->questions->sortBy('sequence')->values();

        // Add user-specific information if authenticated
        if (auth()->check()) {
            $user = auth()->user();

            $userAttempts = $exam->registrations()
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            $userExamAttempts = $userAttempts->count();
            $userLastAttempt = $userAttempts->first();

            $userCanStartExam = true;
            $reason = null;

            if ($exam->start_date && now() < $exam->start_date) {
                $userCanStartExam = false;
                $reason = 'Exam has not started yet';
            }

            if ($exam->end_date && now() > $exam->end_date) {
                $userCanStartExam = false;
                $reason = 'Exam has ended';
            }

            if ($exam->max_attempts && $userAttempts->where('status', 'passed')->count() > 0) {
                $userCanStartExam = false;
                $reason = 'User has already passed this exam';
            }

            if ($exam->max_attempts && $userExamAttempts >= $exam->max_attempts) {
                $userCanStartExam = false;
                $reason = 'Maximum attempts exceeded';
            }

            $activeAttempt = $userAttempts->where('status', 'in_progress')->first();
            if ($activeAttempt) {
                $userCanStartExam = false;
                $reason = 'User has an active attempt in progress';
            }

            $exam->user_info = [
                'user_exam_attempts' => $userExamAttempts,
                'user_can_start_exam' => $userCanStartExam,
                'reason' => $reason,
                'user_last_attempt' => $userLastAttempt ? [
                    'id' => $userLastAttempt->id,
                    'status' => $userLastAttempt->status,
                    'score' => $userLastAttempt->score,
                    'started_at' => $userLastAttempt->started_at,
                    'completed_at' => $userLastAttempt->completed_at,
                    'created_at' => $userLastAttempt->created_at,
                    'time_spent_minutes' => $userLastAttempt->time_spent_minutes
                ] : null,
                'max_attempts' => $exam->max_attempts,
                'remaining_attempts' => $exam->max_attempts ?
                    max(0, $exam->max_attempts - $userExamAttempts) : null
            ];
        } else {
            $exam->user_info = null;
        }

        return response()->json($exam);
    }

    /**
     * Get all pending review exams with detailed information
     */
    public function getPendingReviews(Request $request)
    {
        $pendingExams = ExamRegistration::where('status', 'pending_review')
            ->with([
                'user:id,first_name,last_name,email',
                'exam:id,title,passing_score,training_id',
                'exam.training:id,title,trainer_id',
                'exam.training.trainer:id,first_name,last_name'
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Transform data to include required information
        $pendingExams->getCollection()->transform(function ($registration) {
            $exam = $registration->exam;
            $training = $exam->training ?? null;
            $trainer = $training->trainer ?? null;

            // Calculate correct answers count (auto-graded only)
            $autoGradedAnswers = ExamUserAnswer::where('registration_id', $registration->id)
                ->whereHas('question', function ($query) {
                    $query->where('question_type', '!=', 'text');
                })
                ->with('question')
                ->get();

            $correctAnswersCount = 0;
            foreach ($autoGradedAnswers as $answer) {
                $question = $answer->question;
                if ($question) {
                    // Convert ExamUserAnswer object to array format for isAnswerCorrect
                    $answerData = [
                        'choice_id' => $answer->choice_id,
                        'choice_ids' => $answer->choice_ids,
                        'answer_text' => $answer->answer_text,
                    ];
                    
                    if ($question->isAnswerCorrect($answerData)) {
                        $correctAnswersCount++;
                    }
                }
            }

            // Count text questions
            $textQuestionsCount = ExamUserAnswer::where('registration_id', $registration->id)
                ->whereHas('question', function ($query) {
                    $query->where('question_type', 'text');
                })
                ->count();

            // Get total questions count
            $totalQuestions = $registration->total_questions ?? 
                ExamQuestion::where('exam_id', $exam->id)->count();

            return [
                'id' => $registration->id,
                'user' => [
                    'id' => $registration->user->id,
                    'first_name' => $registration->user->first_name,
                    'last_name' => $registration->user->last_name,
                    'email' => $registration->user->email,
                ],
                'exam' => [
                    'id' => $exam->id,
                    'title' => $exam->title,
                ],
                'training' => $training ? [
                    'id' => $training->id,
                    'title' => $training->title,
                ] : null,
                'trainer' => $trainer ? [
                    'id' => $trainer->id,
                    'first_name' => $trainer->first_name,
                    'last_name' => $trainer->last_name,
                    'full_name' => $trainer->first_name . ' ' . $trainer->last_name,
                ] : null,
                'correct_answers_count' => $correctAnswersCount,
                'total_questions' => $totalQuestions,
                'correct_answers_text' => $correctAnswersCount . '/' . $totalQuestions,
                'current_score' => $registration->auto_graded_score ?? $registration->score ?? 0,
                'passing_score' => $exam->passing_score,
                'text_questions_count' => $textQuestionsCount,
                'started_at' => $registration->started_at,
                'finished_at' => $registration->finished_at,
                'created_at' => $registration->created_at,
            ];
        });

        return response()->json([
            'message' => 'Pending review exams retrieved successfully',
            'data' => $pendingExams
        ]);
    }

    /**
     * Get detailed exam registration for grading
     */
    public function getExamForGrading($registrationId)
    {
        $registration = ExamRegistration::with([
            'user:id,first_name,last_name,email',
            'exam:id,title,passing_score,training_id',
            'exam.training:id,title,trainer_id,has_certificate',
            'exam.training.trainer:id,first_name,last_name',
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

        $exam = $registration->exam;
        $training = $exam->training ?? null;
        $trainer = $training->trainer ?? null;

        // Calculate correct answers count (auto-graded only)
        $autoGradedAnswers = ExamUserAnswer::where('registration_id', $registration->id)
            ->whereHas('question', function ($query) {
                $query->where('question_type', '!=', 'text');
            })
            ->with('question')
            ->get();

        $correctAnswersCount = 0;
        foreach ($autoGradedAnswers as $answer) {
            $question = $answer->question;
            if ($question) {
                // Convert ExamUserAnswer object to array format for isAnswerCorrect
                $answerData = [
                    'choice_id' => $answer->choice_id,
                    'choice_ids' => $answer->choice_ids,
                    'answer_text' => $answer->answer_text,
                ];
                
                if ($question->isAnswerCorrect($answerData)) {
                    $correctAnswersCount++;
                }
            }
        }

        // Get text questions that need manual grading
        $textQuestions = $registration->userAnswers()
            ->whereHas('question', function ($query) {
                $query->where('question_type', 'text');
            })
            ->with('question')
            ->get();

        // Get total questions count
        $totalQuestions = $registration->total_questions ?? 
            ExamQuestion::where('exam_id', $exam->id)->count();

        return response()->json([
            'message' => 'Exam data retrieved successfully',
            'data' => [
                'registration_id' => $registration->id,
                'user' => [
                    'id' => $registration->user->id,
                    'first_name' => $registration->user->first_name,
                    'last_name' => $registration->user->last_name,
                    'email' => $registration->user->email,
                    'full_name' => $registration->user->first_name . ' ' . $registration->user->last_name,
                ],
                'exam' => [
                    'id' => $exam->id,
                    'title' => $exam->title,
                ],
                'training' => $training ? [
                    'id' => $training->id,
                    'title' => $training->title,
                ] : null,
                'trainer' => $trainer ? [
                    'id' => $trainer->id,
                    'first_name' => $trainer->first_name,
                    'last_name' => $trainer->last_name,
                    'full_name' => $trainer->first_name . ' ' . $trainer->last_name,
                ] : null,
                'correct_answers_count' => $correctAnswersCount,
                'total_questions' => $totalQuestions,
                'correct_answers_text' => $correctAnswersCount . '/' . $totalQuestions,
                'current_score' => $registration->auto_graded_score ?? $registration->score ?? 0,
                'passing_score' => $exam->passing_score,
                'text_questions_count' => $textQuestions->count(),
                'started_at' => $registration->started_at,
                'finished_at' => $registration->finished_at,
                'attempt_number' => $registration->attempt_number,
                'text_questions' => $textQuestions->map(function ($answer) {
                    return [
                        'id' => $answer->id,
                        'question_id' => $answer->question_id,
                        'question_text' => $answer->question->question_text,
                        'answer_text' => $answer->answer_text,
                        'answered_at' => $answer->answered_at,
                        'points' => $answer->question->points ?? 0,
                    ];
                })->values(),
            ]
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
            'grades.*.feedback' => ['nullable', 'array'],
            'grades.*.feedback.az' => ['nullable', 'string'],
            'grades.*.feedback.en' => ['nullable', 'string'],
            'grades.*.feedback.ru' => ['nullable', 'string'],
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
            $finalData = DB::transaction(function () use ($registration, $validated) {
                $textQuestionsCorrect = 0;
                $textQuestionsTotal = 0;

                // Update each text question answer
                foreach ($validated['grades'] as $grade) {
                    $answer = ExamUserAnswer::find($grade['answer_id']);
                    if ($answer && $answer->registration_id === $registration->id) {
                        // Prepare multilang feedback
                        $feedback = null;
                        if (isset($grade['feedback']) && is_array($grade['feedback'])) {
                            // Filter out null values
                            $feedback = array_filter($grade['feedback'], function($value) {
                                return $value !== null && $value !== '';
                            });
                            // If empty, set to null
                            $feedback = !empty($feedback) ? $feedback : null;
                        }

                        $answer->update([
                            'is_correct' => $grade['is_correct'],
                            'admin_feedback' => $feedback,
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
                    ->with('question')
                    ->get();

                foreach ($autoGradedAnswers as $answer) {
                    $question = $answer->question;
                    if ($question) {
                        // Convert ExamUserAnswer object to array format for isAnswerCorrect
                        $answerData = [
                            'choice_id' => $answer->choice_id,
                            'choice_ids' => $answer->choice_ids,
                            'answer_text' => $answer->answer_text,
                        ];
                        
                        if ($question->isAnswerCorrect($answerData)) {
                            $autoGradedCorrect++;
                        }
                    }
                }

                $totalCorrect = $autoGradedCorrect + $textQuestionsCorrect;
                $totalQuestions = $registration->total_questions;
                
                // If total_questions is 0 or null, calculate from exam questions
                if (!$totalQuestions || $totalQuestions === 0) {
                    $totalQuestions = ExamQuestion::where('exam_id', $registration->exam_id)->count();
                    // Update registration with correct total_questions
                    $registration->update(['total_questions' => $totalQuestions]);
                }
                
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

                // Return data for response
                return [
                    'total_correct' => $totalCorrect,
                    'total_questions' => $totalQuestions,
                    'final_score' => $finalScore,
                    'passed' => $passed,
                    'auto_graded_correct' => $autoGradedCorrect,
                    'text_questions_correct' => $textQuestionsCorrect,
                ];
            });

            $freshRegistration = ExamRegistration::with('exam')->find($registrationId);

            return response()->json([
                'message' => 'Text questions graded successfully',
                'data' => [
                    'registration_id' => $registrationId,
                    'total_correct' => $finalData['total_correct'],
                    'total_questions' => $finalData['total_questions'],
                    'correct_answers_text' => $finalData['total_correct'] . '/' . $finalData['total_questions'],
                    'final_score' => $finalData['final_score'],
                    'passing_score' => $freshRegistration->exam->passing_score,
                    'status' => $freshRegistration->status,
                    'passed' => $finalData['passed'],
                ]
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

            // Prepare data for certificate generation
            $userData = [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
            ];
            
            $examData = [
                'id' => $exam->id,
                'title' => $exam->title,
                'description' => $exam->description,
                'sertifikat_description' => $exam->sertifikat_description ?? null,
            ];
            
            $trainingData = [
                'id' => $training->id,
                'title' => $training->title,
                'description' => $training->description,
                'certificate_description' => $training->certificate_description ?? null,
            ];

            // Use PHP certificate generator service
            $service = new \App\Services\CertificateGeneratorService();
            $result = $service->generateCertificate($userData, $examData, $trainingData);

            if ($result['success']) {
                // Create certificate record
                $certificate = Certificate::create([
                    'user_id' => $user->id,
                    'related_training_id' => $training->id,
                    'related_exam_id' => $exam->id,
                    'certificate_number' => $result['certificate_number'],
                    'issue_date' => now(),
                    'expiry_date' => now()->addYear(),
                    'status' => 'active',
                    'pdf_path' => $result['pdf_path'],
                    'digital_signature' => $result['digital_signature'],
                ]);

                // Generate and save QR code
                try {
                    $certificate->generateAndSaveQrCode();
                } catch (\Exception $e) {
                    Log::error('Failed to generate QR code for certificate ' . $certificate->id . ': ' . $e->getMessage());
                }

                // Update registration with certificate
                if ($certificate) {
                    $registration->update(['certificate_id' => $certificate->id]);
                }

                Log::info('Certificate generated successfully for user ' . $user->id . ' exam ' . $exam->id);
            } else {
                Log::error('Certificate generation failed: ' . ($result['error'] ?? 'Unknown error'));
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
