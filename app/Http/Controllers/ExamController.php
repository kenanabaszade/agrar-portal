<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamRegistration;
use App\Models\ExamQuestion;
use App\Models\ExamChoice;
use App\Models\ExamUserAnswer;
use App\Models\ExamAnswer;
use App\Models\Certificate;
use App\Models\User;
use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class ExamController extends Controller
{
    
    /**
     * Get exams list with pagination, search and filtering for admin dashboard
     */
    public function index(Request $request)
    {
        $query = Exam::with(['training.trainer:id,first_name,last_name'])
            ->withCount([
                'questions',
                'registrations',
                'registrations as completed_registrations_count' => function ($q) {
                    $q->whereIn('status', ['passed', 'failed', 'completed']);
                },
                'registrations as passed_registrations_count' => function ($q) {
                    $q->where('status', 'passed');
                }
            ])
            ->when($request->boolean('include_questions'), function ($q) {
                $q->with('questions:id,exam_id,question_text,question_type,points');
            });

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%") // Direct exam category
                  ->orWhereHas('training', function ($tq) use ($search) {
                      $tq->where('title', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by category (both direct exam category and training category)
        if ($request->filled('category')) {
            $category = $request->get('category');
            $query->where(function ($q) use ($category) {
                $q->where('category', $category) // Direct exam category
                  ->orWhereHas('training', function ($tq) use ($category) {
                      $tq->where('category', $category); // Training category
                  });
            });
        }

        // Filter by training
        if ($request->filled('training_id')) {
            $query->where('training_id', $request->get('training_id'));
        }

        // Filter by status (based on dates)
        if ($request->filled('status')) {
            $status = $request->get('status');
            $now = now();
            
            switch ($status) {
                case 'upcoming':
                    $query->where('start_date', '>', $now);
                    break;
                case 'active':
                    $query->where(function ($q) use ($now) {
                        $q->where('start_date', '<=', $now)
                          ->where(function ($sq) use ($now) {
                              $sq->whereNull('end_date')
                                ->orWhere('end_date', '>=', $now);
                          });
                    });
                    break;
                case 'ended':
                    $query->where('end_date', '<', $now);
                    break;
            }
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        if (in_array($sortBy, ['title', 'created_at', 'start_date', 'end_date', 'passing_score'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = min($request->get('per_page', 15), 100); // Max 100 per page
        $exams = $query->paginate($perPage);

        // Add computed fields for each exam
        $exams->getCollection()->transform(function ($exam) {
            $now = now();
            
            // Determine status - check database status first, then date-based status
            if ($exam->status === 'draft') {
                $exam->status = 'draft';
            } elseif ($exam->status === 'archived') {
                $exam->status = 'archived';
            } elseif ($exam->start_date && $exam->start_date->isFuture()) {
                $exam->status = 'upcoming';
            } elseif ($exam->end_date && $exam->end_date->isPast()) {
                $exam->status = 'ended';
            } else {
                $exam->status = 'active';
            }

            // Determine exam type and category
            if ($exam->training_id) {
                $exam->exam_type = 'training_based';
                $exam->display_category = $exam->training->category ?? 'No Category';
                $exam->training_title = $exam->training->title ?? 'Unknown Training';
            } else {
                $exam->exam_type = 'independent';
                $exam->display_category = $exam->category ?? 'No Category';
                $exam->training_title = null;
            }

            // Use cached count attributes from withCount (no additional queries!)
            $totalRegistrations = $exam->registrations_count ?? 0;
            $completedRegistrations = $exam->completed_registrations_count ?? 0;
            $passedRegistrations = $exam->passed_registrations_count ?? 0;
            
            $exam->completion_rate = $totalRegistrations > 0 
                ? round(($completedRegistrations / $totalRegistrations) * 100, 1) 
                : 0;

            $exam->pass_rate = $completedRegistrations > 0 
                ? round(($passedRegistrations / $completedRegistrations) * 100, 1) 
                : 0;

            return $exam;
        });

        return response()->json($exams);
    }

    /**
     * Get exam statistics for dashboard
     */
    public function getStats()
    {
        $totalExams = Exam::count();
        $activeExams = Exam::where(function ($q) {
            $now = now();
            $q->where('start_date', '<=', $now)
              ->where(function ($sq) use ($now) {
                  $sq->whereNull('end_date')
                    ->orWhere('end_date', '>=', $now);
              });
        })->count();

        $upcomingExams = Exam::where('start_date', '>', now())->count();
        
        $totalRegistrations = \App\Models\ExamRegistration::count();
        $completedExams = \App\Models\ExamRegistration::whereIn('status', ['passed', 'failed', 'completed'])->count();
        
        $averageScore = \App\Models\ExamRegistration::whereNotNull('score')->avg('score');

        return response()->json([
            'total_exams' => $totalExams,
            'active_exams' => $activeExams,
            'upcoming_exams' => $upcomingExams,
            'total_registrations' => $totalRegistrations,
            'completed_exams' => $completedExams,
            'average_score' => $averageScore ? round($averageScore, 1) : 0,
            'completion_rate' => $totalRegistrations > 0 ? round(($completedExams / $totalRegistrations) * 100, 1) : 0,
        ]);
    }

    /**
     * Get detailed exam list with comprehensive statistics
     */
    public function getDetailedExamList(Request $request)
    {
        $query = Exam::with(['training.trainer', 'questions'])
            ->withCount(['questions', 'registrations']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('training', function ($tq) use ($search) {
                      $tq->where('title', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->get('category'));
        }

        // Filter by training
        if ($request->filled('training_id')) {
            $query->where('training_id', $request->get('training_id'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $status = $request->get('status');
            $now = now();
            
            switch ($status) {
                case 'upcoming':
                    $query->where('start_date', '>', $now);
                    break;
                case 'active':
                    $query->where(function ($q) use ($now) {
                        $q->where('start_date', '<=', $now)
                          ->where(function ($sq) use ($now) {
                              $sq->whereNull('end_date')
                                ->orWhere('end_date', '>=', $now);
                          });
                    });
                    break;
                case 'ended':
                    $query->where('end_date', '<', $now);
                    break;
            }
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        if (in_array($sortBy, ['title', 'created_at', 'start_date', 'end_date', 'passing_score'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = min($request->get('per_page', 15), 100);
        $exams = $query->paginate($perPage);

        // Transform each exam with detailed statistics
        $exams->getCollection()->transform(function ($exam) {
            $now = now();
            
            // Determine status - check database status first, then date-based status
            if ($exam->status === 'draft') {
                $exam->status = 'draft';
            } elseif ($exam->status === 'archived') {
                $exam->status = 'archived';
            } elseif ($exam->start_date && $exam->start_date->isFuture()) {
                $exam->status = 'upcoming';
            } elseif ($exam->end_date && $exam->end_date->isPast()) {
                $exam->status = 'ended';
            } else {
                $exam->status = 'active';
            }

            // Get detailed statistics
            $totalRegistrations = $exam->registrations_count;
            $completedRegistrations = $exam->registrations()
                ->whereIn('status', ['passed', 'failed', 'completed'])->count();
            $passedRegistrations = $exam->registrations()
                ->where('status', 'passed')->count();
            
            // Calculate average score
            $averageScore = $exam->registrations()
                ->whereNotNull('score')
                ->avg('score');
            
            // Calculate success rate (passed / total registrations)
            $successRate = $totalRegistrations > 0 
                ? round(($passedRegistrations / $totalRegistrations) * 100, 1) 
                : 0;

            // Get creator info
            $createdBy = 'System';
            if ($exam->training && $exam->training->trainer) {
                $createdBy = $exam->training->trainer->first_name . ' ' . $exam->training->trainer->last_name;
            }

            return [
                'id' => $exam->id,
                'title' => $exam->title,
                'training_name' => $exam->training ? $exam->training->title : 'Independent Exam',
                'duration' => $exam->duration_minutes,
                'question_count' => $exam->questions_count,
                'created_by' => $createdBy,
                'created_at' => $exam->created_at->format('Y-m-d H:i:s'),
                'participants' => $totalRegistrations,
                'average_score_percentage' => $averageScore ? round($averageScore, 1) : 0,
                'success_rate' => $successRate,
                'passed_count' => $passedRegistrations,
                'minimal_passing_score' => $exam->passing_score,
                'max_attempts' => $exam->max_attempts,
                'status' => $exam->status,
                'start_date' => $exam->start_date ? $exam->start_date->format('Y-m-d') : null,
                'end_date' => $exam->end_date ? $exam->end_date->format('Y-m-d') : null,
                'category' => $exam->category,
            ];
        });

        return response()->json([
            'exams' => $exams,
            'summary' => [
                'total_exams' => $exams->total(),
                'active_exams' => $exams->getCollection()->where('status', 'active')->count(),
                'upcoming_exams' => $exams->getCollection()->where('status', 'upcoming')->count(),
                'ended_exams' => $exams->getCollection()->where('status', 'ended')->count(),
            ]
        ]);
    }

    /**
     * Get comprehensive exam statistics with growth rates
     */
    public function getComprehensiveStats()
    {
        $now = now();
        $lastMonth = $now->copy()->subMonth();
        $lastWeek = $now->copy()->subWeek();
        
        // Current statistics
        $totalExams = Exam::count();
        $activeExams = Exam::where(function ($q) use ($now) {
            $q->where('start_date', '<=', $now)
              ->where(function ($sq) use ($now) {
                  $sq->whereNull('end_date')
                    ->orWhere('end_date', '>=', $now);
              });
        })->count();
        
        $totalRegistrations = \App\Models\ExamRegistration::count();
        $completedRegistrations = \App\Models\ExamRegistration::whereIn('status', ['passed', 'failed', 'completed'])->count();
        $averageScore = \App\Models\ExamRegistration::whereNotNull('score')->avg('score');
        
        // Previous month statistics for growth calculation
        $totalExamsLastMonth = Exam::where('created_at', '<=', $lastMonth)->count();
        $activeExamsLastMonth = Exam::where(function ($q) use ($lastMonth) {
            $q->where('start_date', '<=', $lastMonth)
              ->where(function ($sq) use ($lastMonth) {
                  $sq->whereNull('end_date')
                    ->orWhere('end_date', '>=', $lastMonth);
              });
        })->count();
        
        $totalRegistrationsLastMonth = \App\Models\ExamRegistration::where('created_at', '<=', $lastMonth)->count();
        $completedRegistrationsLastMonth = \App\Models\ExamRegistration::whereIn('status', ['passed', 'failed', 'completed'])
            ->where('created_at', '<=', $lastMonth)->count();
        $averageScoreLastMonth = \App\Models\ExamRegistration::whereNotNull('score')
            ->where('created_at', '<=', $lastMonth)->avg('score');
        
        // Calculate growth rates
        $examGrowth = $totalExamsLastMonth > 0 ? round((($totalExams - $totalExamsLastMonth) / $totalExamsLastMonth) * 100, 1) : 0;
        $activeExamGrowth = $activeExamsLastMonth > 0 ? round((($activeExams - $activeExamsLastMonth) / $activeExamsLastMonth) * 100, 1) : 0;
        $registrationGrowth = $totalRegistrationsLastMonth > 0 ? round((($totalRegistrations - $totalRegistrationsLastMonth) / $totalRegistrationsLastMonth) * 100, 1) : 0;
        $completionGrowth = $completedRegistrationsLastMonth > 0 ? round((($completedRegistrations - $completedRegistrationsLastMonth) / $completedRegistrationsLastMonth) * 100, 1) : 0;
        $scoreGrowth = $averageScoreLastMonth > 0 ? round((($averageScore - $averageScoreLastMonth) / $averageScoreLastMonth) * 100, 1) : 0;
        
        // Weekly growth for more recent trends
        $totalExamsLastWeek = Exam::where('created_at', '<=', $lastWeek)->count();
        $totalRegistrationsLastWeek = \App\Models\ExamRegistration::where('created_at', '<=', $lastWeek)->count();
        
        $weeklyExamGrowth = $totalExamsLastWeek > 0 ? round((($totalExams - $totalExamsLastWeek) / $totalExamsLastWeek) * 100, 1) : 0;
        $weeklyRegistrationGrowth = $totalRegistrationsLastWeek > 0 ? round((($totalRegistrations - $totalRegistrationsLastWeek) / $totalRegistrationsLastWeek) * 100, 1) : 0;
        
        // Pass rate calculation
        $passedRegistrations = \App\Models\ExamRegistration::where('status', 'passed')->count();
        $passRate = $completedRegistrations > 0 ? round(($passedRegistrations / $completedRegistrations) * 100, 1) : 0;
        
        // Recent activity (last 7 days)
        $recentExams = Exam::where('created_at', '>=', $now->copy()->subDays(7))->count();
        $recentRegistrations = \App\Models\ExamRegistration::where('created_at', '>=', $now->copy()->subDays(7))->count();
        
        return response()->json([
            'overview' => [
                'total_exams' => $totalExams,
                'active_exams' => $activeExams,
                'total_registrations' => $totalRegistrations,
                'completed_registrations' => $completedRegistrations,
                'average_score' => $averageScore ? round($averageScore, 1) : 0,
                'pass_rate' => $passRate,
                'completion_rate' => $totalRegistrations > 0 ? round(($completedRegistrations / $totalRegistrations) * 100, 1) : 0,
            ],
            'growth_rates' => [
                'monthly' => [
                    'exams_growth' => $examGrowth,
                    'active_exams_growth' => $activeExamGrowth,
                    'registrations_growth' => $registrationGrowth,
                    'completions_growth' => $completionGrowth,
                    'average_score_growth' => $scoreGrowth,
                ],
                'weekly' => [
                    'exams_growth' => $weeklyExamGrowth,
                    'registrations_growth' => $weeklyRegistrationGrowth,
                ]
            ],
            'recent_activity' => [
                'exams_last_7_days' => $recentExams,
                'registrations_last_7_days' => $recentRegistrations,
            ],
            'comparison' => [
                'last_month' => [
                    'total_exams' => $totalExamsLastMonth,
                    'active_exams' => $activeExamsLastMonth,
                    'total_registrations' => $totalRegistrationsLastMonth,
                    'completed_registrations' => $completedRegistrationsLastMonth,
                    'average_score' => $averageScoreLastMonth ? round($averageScoreLastMonth, 1) : 0,
                ]
            ]
        ]);
    }

    /**
     * Get available data for exam form dropdowns based on user role
     */
    public function getFormData(Request $request)
    {
        $user = $request->user();
        
        // Get categories from proper category system
        $categories = \App\Models\Category::active()->ordered()->get(['id', 'name']);

        // Get trainings based on user role
        $trainingsQuery = \App\Models\Training::select('id', 'title', 'category')
            ->with([
                'trainer:id,first_name,last_name',
                'category:id,name'
            ])
            ->orderBy('title');

        // If user is trainer, only show their trainings
        if ($user->user_type === 'trainer') {
            $trainingsQuery->where('trainer_id', $user->id);
        }
        
        $trainings = $trainingsQuery->get();

        // For trainers: return current user info
        // For admins: return all trainers
        if ($user->user_type === 'trainer') {
            $trainers = collect([
                [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'is_current_user' => true
                ]
            ]);
        } else {
            // Admin can see all trainers
            $trainers = \App\Models\User::where('user_type', 'trainer')
                ->orWhereHas('roles', function ($q) {
                    $q->where('name', 'trainer');
                })
                ->select('id', 'first_name', 'last_name', 'email')
                ->orderBy('first_name')
                ->get()
                ->map(function ($trainer) use ($user) {
                    $trainer->is_current_user = $trainer->id === $user->id;
                    return $trainer;
                });
        }

        return response()->json([
            'categories' => $categories,
            'trainings' => $trainings,
            'trainers' => $trainers,
            'current_user' => [
                'id' => $user->id,
                'user_type' => $user->user_type,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
            ],
            'supports_independent_exams' => true // Frontend can create exams without training
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Exam basic information
            'training_id' => ['nullable', 'exists:trainings,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sertifikat_description' => ['nullable', 'string'],
            'is_required' => ['nullable', 'boolean'],
            'passing_score' => ['required', 'integer', 'min:0', 'max:100'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:480'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:10'],
            
            // New exam system fields
            'exam_question_count' => ['required', 'integer', 'min:1'],
            'shuffle_questions' => ['nullable', 'boolean'],
            'shuffle_choices' => ['nullable', 'boolean'],
            'show_result_immediately' => ['nullable', 'boolean'],
            'show_correct_answers' => ['nullable', 'boolean'],
            'show_explanations' => ['nullable', 'boolean'],
            'auto_submit' => ['nullable', 'boolean'],
            
            // Questions validation
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.question_text' => ['required', 'string'],
            'questions.*.question_type' => ['required', 'in:single_choice,multiple_choice,true_false,text'],
            'questions.*.difficulty' => ['nullable', 'in:easy,medium,hard'],
            'questions.*.question_media' => ['nullable', 'array'],
            'questions.*.explanation' => ['nullable', 'string'],
            'questions.*.is_required' => ['nullable', 'boolean'],
            'questions.*.sequence' => ['nullable', 'integer', 'min:1'],
            
            // Choices validation
            'questions.*.choices' => ['required_if:questions.*.question_type,single_choice,multiple_choice,true_false', 'array'],
            'questions.*.choices.*.choice_text' => ['required', 'string'],
            'questions.*.choices.*.is_correct' => ['required', 'boolean'],
            'questions.*.choices.*.explanation' => ['nullable', 'string'],
            'questions.*.choices.*.sequence' => ['nullable', 'integer', 'min:1'],
        ]);

        // Additional validation
        $totalQuestions = count($validated['questions']);
        $examQuestionCount = $validated['exam_question_count'];
        
        if ($examQuestionCount > $totalQuestions) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => ['exam_question_count' => ["İmtahanda göstəriləcək sual sayı ($examQuestionCount) ümumi sual sayından ($totalQuestions) çox ola bilməz"]]
            ], 422);
        }
        
        // Validate correct answers for choice questions
        foreach ($validated['questions'] as $index => $question) {
            if (in_array($question['question_type'], ['single_choice', 'multiple_choice', 'true_false'])) {
                if (!isset($question['choices']) || empty($question['choices'])) {
                    return response()->json([
                        'message' => 'Validation failed',
                        'errors' => ["questions.{$index}.choices" => ['Choice questions must have at least one choice']]
                    ], 422);
                }
                
                $hasCorrectAnswer = collect($question['choices'])->contains('is_correct', true);
                if (!$hasCorrectAnswer) {
                    return response()->json([
                        'message' => 'Validation failed',
                        'errors' => ["questions.{$index}.choices" => ['At least one choice must be marked as correct']]
                    ], 422);
                }
            }
        }

        // Create exam and questions in a database transaction
        $exam = DB::transaction(function () use ($validated) {
            // Extract exam data
            $examData = collect($validated)->except('questions')->toArray();
            
            // Set defaults
            $examData['is_required'] = $validated['is_required'] ?? true; // Default true
            $examData['auto_submit'] = $validated['auto_submit'] ?? false; // Default false
            $examData['randomize_questions'] = $validated['shuffle_questions'] ?? true;
            $examData['randomize_choices'] = $validated['shuffle_choices'] ?? true;
            $examData['show_results_immediately'] = $validated['show_result_immediately'] ?? true;
            
            $exam = Exam::create($examData);
            
            // Update training with exam_id if training_id is provided
            if ($examData['training_id'] ?? null) {
                $training = \App\Models\Training::find($examData['training_id']);
                if ($training) {
                    $training->update([
                        'exam_id' => $exam->id,
                        'has_exam' => true
                    ]);
                }
            }
            
            // Create questions
            foreach ($validated['questions'] as $index => $questionData) {
                // Set default values
                $questionData['points'] = $questionData['points'] ?? 1;
                $questionData['is_required'] = $questionData['is_required'] ?? true;
                $questionData['sequence'] = $questionData['sequence'] ?? ($index + 1);
                
                // Extract choices data
                $choices = $questionData['choices'] ?? [];
                unset($questionData['choices']);
                
                // Create question
                $question = $exam->questions()->create([
                    'question_text' => $questionData['question_text'],
                    'question_media' => $questionData['question_media'] ?? null,
                    'explanation' => $questionData['explanation'] ?? null,
                    'question_type' => $questionData['question_type'],
                    'difficulty' => $questionData['difficulty'] ?? 'medium',
                    'points' => $questionData['points'],
                    'is_required' => $questionData['is_required'],
                    'sequence' => $questionData['sequence'],
                    'metadata' => $questionData['metadata'] ?? null,
                ]);

                // Create choices if question type requires them
                if (in_array($questionData['question_type'], ['single_choice', 'multiple_choice', 'true_false']) && !empty($choices)) {
                    foreach ($choices as $choiceIndex => $choiceData) {
                        $question->choices()->create([
                            'choice_text' => $choiceData['choice_text'],
                            'choice_media' => $choiceData['choice_media'] ?? null,
                            'explanation' => $choiceData['explanation'] ?? null,
                            'is_correct' => $choiceData['is_correct'],
                            'points' => 0, // Yeni sistemdə points yoxdur
                            'sequence' => $choiceData['sequence'] ?? ($choiceIndex + 1),
                            'metadata' => $choiceData['metadata'] ?? null,
                        ]);
                    }
                }
            }
            
            return $exam;
        });
        
        // Load relationships for response
        $exam->load(['training', 'questions.choices']);

        return response()->json([
            'message' => 'İmtahan uğurla yaradıldı',
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'description' => $exam->description,
                'training_id' => $exam->training_id,
                'is_required' => $exam->is_required,
                'duration_minutes' => $exam->duration_minutes,
                'passing_score' => $exam->passing_score,
                'max_attempts' => $exam->max_attempts,
                'total_questions' => $exam->questions()->count(),
                'exam_question_count' => $exam->exam_question_count,
                'shuffle_questions' => $exam->randomize_questions,
                'shuffle_choices' => $exam->randomize_choices,
                'show_result_immediately' => $exam->show_results_immediately,
                'show_correct_answers' => $exam->show_correct_answers,
                'show_explanations' => $exam->show_explanations,
                'auto_submit' => $exam->auto_submit,
                'created_at' => $exam->created_at,
                'updated_at' => $exam->updated_at,
            ]
        ], 201);
    }

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

        // Add user-specific information if authenticated
        if (auth()->check()) {
            $user = auth()->user();
            
            // Get user's exam attempts
            $userAttempts = $exam->registrations()
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
            
            $userExamAttempts = $userAttempts->count();
            
            // Get user's last attempt
            $userLastAttempt = $userAttempts->first();
            
            // Check if user can start exam
            $userCanStartExam = true;
            $reason = null;
            
            // Check if exam has start/end dates
            if ($exam->start_date && now() < $exam->start_date) {
                $userCanStartExam = false;
                $reason = 'Exam has not started yet';
            }
            
            if ($exam->end_date && now() > $exam->end_date) {
                $userCanStartExam = false;
                $reason = 'Exam has ended';
            }
            
            // Check if user has already passed (if max_attempts is set)
            if ($exam->max_attempts && $userAttempts->where('status', 'passed')->count() > 0) {
                $userCanStartExam = false;
                $reason = 'User has already passed this exam';
            }
            
            // Check if user has exceeded max attempts
            if ($exam->max_attempts && $userExamAttempts >= $exam->max_attempts) {
                $userCanStartExam = false;
                $reason = 'Maximum attempts exceeded';
            }
            
            // Check if user has an active attempt (started but not completed)
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
     * Get exam details for public access (with user info if authenticated)
     */
    public function showPublic(Exam $exam)
    {
        $exam->load([
            'training.trainer',
            'questions.choices'
        ]);

        // Add basic statistics (without sensitive data)
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

        // Add user-specific information if authenticated
        if (auth()->check()) {
            $user = auth()->user();
            
            // Get user's exam attempts
            $userAttempts = $exam->registrations()
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
            
            $userExamAttempts = $userAttempts->count();
            
            // Get user's last attempt
            $userLastAttempt = $userAttempts->first();
            
            // Check if user can start exam
            $userCanStartExam = true;
            $reason = null;
            
            // Check if exam has start/end dates
            if ($exam->start_date && now() < $exam->start_date) {
                $userCanStartExam = false;
                $reason = 'Exam has not started yet';
            }
            
            if ($exam->end_date && now() > $exam->end_date) {
                $userCanStartExam = false;
                $reason = 'Exam has ended';
            }
            
            // Check if user has already passed (if max_attempts is set)
            if ($exam->max_attempts && $userAttempts->where('status', 'passed')->count() > 0) {
                $userCanStartExam = false;
                $reason = 'User has already passed this exam';
            }
            
            // Check if user has exceeded max attempts
            if ($exam->max_attempts && $userExamAttempts >= $exam->max_attempts) {
                $userCanStartExam = false;
                $reason = 'Maximum attempts exceeded';
            }
            
            // Check if user has an active attempt (started but not completed)
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

    public function update(Request $request, Exam $exam)
    {
        $validated = $request->validate([
            // Exam basic information
            'training_id' => ['sometimes', 'exists:trainings,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sertifikat_description' => ['nullable', 'string'],
            'is_required' => ['nullable', 'boolean'],
            'passing_score' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'duration_minutes' => ['sometimes', 'integer', 'min:1', 'max:480'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:10'],
            
            // New exam system fields
            'exam_question_count' => ['sometimes', 'integer', 'min:1'],
            'shuffle_questions' => ['nullable', 'boolean'],
            'shuffle_choices' => ['nullable', 'boolean'],
            'show_result_immediately' => ['nullable', 'boolean'],
            'show_correct_answers' => ['nullable', 'boolean'],
            'show_explanations' => ['nullable', 'boolean'],
            'auto_submit' => ['nullable', 'boolean'],
            
            // Questions validation (optional for update)
            'questions' => ['sometimes', 'array', 'min:1'],
            'questions.*.question_text' => ['required_with:questions', 'string'],
            'questions.*.question_type' => ['required_with:questions', 'in:single_choice,multiple_choice,true_false,text'],
            'questions.*.difficulty' => ['nullable', 'in:easy,medium,hard'],
            'questions.*.question_media' => ['nullable', 'array'],
            'questions.*.explanation' => ['nullable', 'string'],
            'questions.*.is_required' => ['nullable', 'boolean'],
            'questions.*.sequence' => ['nullable', 'integer', 'min:1'],
            
            // Choices validation
            'questions.*.choices' => ['required_if:questions.*.question_type,single_choice,multiple_choice,true_false', 'array'],
            'questions.*.choices.*.choice_text' => ['required', 'string'],
            'questions.*.choices.*.is_correct' => ['required', 'boolean'],
            'questions.*.choices.*.explanation' => ['nullable', 'string'],
            'questions.*.choices.*.sequence' => ['nullable', 'integer', 'min:1'],
        ]);

        // Additional validation if questions are provided
        if (isset($validated['questions'])) {
            $totalQuestions = count($validated['questions']);
            $examQuestionCount = $validated['exam_question_count'] ?? $exam->exam_question_count;
            
            if ($examQuestionCount > $totalQuestions) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => ['exam_question_count' => ["İmtahanda göstəriləcək sual sayı ($examQuestionCount) ümumi sual sayından ($totalQuestions) çox ola bilməz"]]
                ], 422);
            }
            
            // Validate correct answers for choice questions
            foreach ($validated['questions'] as $index => $question) {
                if (in_array($question['question_type'], ['single_choice', 'multiple_choice', 'true_false'])) {
                    if (!isset($question['choices']) || empty($question['choices'])) {
                        return response()->json([
                            'message' => 'Validation failed',
                            'errors' => ["questions.{$index}.choices" => ['Choice questions must have at least one choice']]
                        ], 422);
                    }
                    
                    $hasCorrectAnswer = collect($question['choices'])->contains('is_correct', true);
                    if (!$hasCorrectAnswer) {
                        return response()->json([
                            'message' => 'Validation failed',
                            'errors' => ["questions.{$index}.choices" => ['At least one choice must be marked as correct']]
                        ], 422);
                    }
                }
            }
        }

        // Update exam in transaction
        $exam = DB::transaction(function () use ($validated, $exam) {
            // Extract exam data (exclude questions)
            $examData = collect($validated)->except('questions')->toArray();
            
            // Set defaults for new fields
            $examData['is_required'] = $validated['is_required'] ?? true; // Default true
            $examData['auto_submit'] = $validated['auto_submit'] ?? false; // Default false
            if (isset($validated['shuffle_questions'])) {
                $examData['randomize_questions'] = $validated['shuffle_questions'];
            }
            if (isset($validated['shuffle_choices'])) {
                $examData['randomize_choices'] = $validated['shuffle_choices'];
            }
            if (isset($validated['show_result_immediately'])) {
                $examData['show_results_immediately'] = $validated['show_result_immediately'];
            }
            
            $exam->update($examData);
            
            // Update training with exam_id if training_id is provided
            if (isset($validated['training_id']) && $validated['training_id']) {
                $training = \App\Models\Training::find($validated['training_id']);
                if ($training) {
                    $training->update([
                        'exam_id' => $exam->id,
                        'has_exam' => true
                    ]);
                }
            }
            
            // If questions are provided, delete old ones and create new ones
            if (isset($validated['questions'])) {
                // Delete old questions and choices
                $exam->questions()->each(function ($question) {
                    $question->choices()->delete();
                });
                $exam->questions()->delete();
                
                // Create new questions
                foreach ($validated['questions'] as $index => $questionData) {
                    // Set default values
                    $questionData['is_required'] = $questionData['is_required'] ?? true;
                    $questionData['sequence'] = $questionData['sequence'] ?? ($index + 1);
                    
                    // Extract choices data
                    $choices = $questionData['choices'] ?? [];
                    unset($questionData['choices']);
                    
                    // Create question
                    $question = $exam->questions()->create([
                        'question_text' => $questionData['question_text'],
                        'question_media' => $questionData['question_media'] ?? null,
                        'explanation' => $questionData['explanation'] ?? null,
                        'question_type' => $questionData['question_type'],
                        'difficulty' => $questionData['difficulty'] ?? 'medium',
                        'points' => 0, // Yeni sistemdə points yoxdur
                        'is_required' => $questionData['is_required'],
                        'sequence' => $questionData['sequence'],
                        'metadata' => null,
                    ]);

                    // Create choices if question type requires them
                    if (in_array($questionData['question_type'], ['single_choice', 'multiple_choice', 'true_false']) && !empty($choices)) {
                        foreach ($choices as $choiceIndex => $choiceData) {
                            $question->choices()->create([
                                'choice_text' => $choiceData['choice_text'],
                                'choice_media' => $choiceData['choice_media'] ?? null,
                                'explanation' => $choiceData['explanation'] ?? null,
                                'is_correct' => $choiceData['is_correct'],
                                'points' => 0, // Yeni sistemdə points yoxdur
                                'sequence' => $choiceData['sequence'] ?? ($choiceIndex + 1),
                                'metadata' => null,
                            ]);
                        }
                    }
                }
            }
            
            return $exam;
        });
        
        // Load relationships for response
        $exam->load(['training', 'questions.choices']);

        return response()->json([
            'message' => 'İmtahan uğurla yeniləndi',
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'description' => $exam->description,
                'training_id' => $exam->training_id,
                'is_required' => $exam->is_required,
                'duration_minutes' => $exam->duration_minutes,
                'passing_score' => $exam->passing_score,
                'max_attempts' => $exam->max_attempts,
                'total_questions' => $exam->questions()->count(),
                'exam_question_count' => $exam->exam_question_count,
                'shuffle_questions' => $exam->randomize_questions,
                'shuffle_choices' => $exam->randomize_choices,
                'show_result_immediately' => $exam->show_results_immediately,
                'show_correct_answers' => $exam->show_correct_answers,
                'show_explanations' => $exam->show_explanations,
                'auto_submit' => $exam->auto_submit,
                'updated_at' => $exam->updated_at,
            ]
        ]);
    }

    public function destroy(Exam $exam)
    {
        // Check if exam has registrations
        $registrationsCount = $exam->registrations()->count();
        
        if ($registrationsCount > 0) {
            return response()->json([
                'message' => 'Cannot delete exam with existing registrations',
                'registrations_count' => $registrationsCount
            ], 422);
        }

        // Store exam data for audit log before deletion
        $examData = [
            'title' => $exam->title,
            'training_id' => $exam->training_id,
            'questions_count' => $exam->questions()->count(),
        ];

        $examId = $exam->id;
        $trainingId = $exam->training_id;
        
        $exam->delete();
        
        // Update training to remove exam_id if exam was linked to a training
        if ($trainingId) {
            $training = \App\Models\Training::find($trainingId);
            if ($training) {
                $training->update([
                    'exam_id' => null,
                    'has_exam' => false
                ]);
            }
        }

        // Add audit log
        \App\Models\AuditLog::create([
            'user_id' => request()->user()->id,
            'action' => 'deleted',
            'entity' => 'exam',
            'entity_id' => $examId,
            'details' => $examData
        ]);

        return response()->json([
            'message' => 'Exam deleted successfully'
        ]);
    }

    // Start exam session
    public function start(Exam $exam, Request $request)
    {
        $user = $request->user();
        
        // Check if user has exceeded max attempts
        if ($exam->max_attempts) {
            $attemptCount = ExamRegistration::where('user_id', $user->id)
                ->where('exam_id', $exam->id)
                ->count();
                
            if ($attemptCount >= $exam->max_attempts) {
                return response()->json([
                    'message' => 'Maksimum cəhd sayına çatdınız',
                    'max_attempts' => $exam->max_attempts,
                    'attempts_used' => $attemptCount
                ], 422);
            }
        }
        
        // Get next attempt number
        $nextAttemptNumber = ExamRegistration::where('user_id', $user->id)
            ->where('exam_id', $exam->id)
            ->max('attempt_number') + 1;
            
        // Get all questions for this exam
        $allQuestions = $exam->questions()->with('choices')->get();
        
        if ($allQuestions->isEmpty()) {
            return response()->json([
                'message' => 'Bu imtahanda sual yoxdur'
            ], 422);
        }
        
        // Select random questions based on exam_question_count
        $selectedQuestions = $allQuestions;
        if ($exam->exam_question_count < $allQuestions->count()) {
            $selectedQuestions = $allQuestions->random($exam->exam_question_count)->values();
        }
        
        // Shuffle questions if enabled
        if ($exam->randomize_questions) {
            $selectedQuestions = $selectedQuestions->shuffle();
        }
        
        // Shuffle choices for each question if enabled
        if ($exam->randomize_choices) {
            $selectedQuestions->each(function ($question) {
                $question->setRelation('choices', $question->choices->shuffle());
            });
        }
        
        // Create exam registration
        $registration = ExamRegistration::create([
            'user_id' => $user->id,
            'exam_id' => $exam->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'attempt_number' => $nextAttemptNumber,
            'selected_question_ids' => $selectedQuestions->pluck('id')->toArray(),
            'total_questions' => $selectedQuestions->count(),
        ]);
        
        // Format questions for frontend
        $formattedQuestions = $selectedQuestions->map(function ($question) {
            $questionData = [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'question_type' => $question->question_type,
                'difficulty' => $question->difficulty,
                'question_media' => $question->question_media,
                'explanation' => $question->explanation,
                'is_required' => $question->is_required,
                'sequence' => $question->sequence,
            ];
            
            // Add choices for choice-based questions
            if (in_array($question->question_type, ['single_choice', 'multiple_choice', 'true_false'])) {
                $questionData['choices'] = $question->choices->map(function ($choice) {
                    return [
                        'id' => $choice->id,
                        'choice_text' => $choice->choice_text,
                        'choice_media' => $choice->choice_media,
                        'explanation' => $choice->explanation,
                        'sequence' => $choice->sequence,
                        // Don't send is_correct to frontend
                    ];
                });
            }
            
            return $questionData;
        });
        
        return response()->json([
            'message' => 'İmtahan başladı',
            'registration' => [
                'id' => $registration->id,
                'exam_id' => $registration->exam_id,
                'status' => $registration->status,
                'attempt_number' => $registration->attempt_number,
                'started_at' => $registration->started_at,
                'total_questions' => $registration->total_questions,
                'time_limit_minutes' => $exam->duration_minutes,
            ],
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'description' => $exam->description,
                'duration_minutes' => $exam->duration_minutes,
                'passing_score' => $exam->passing_score,
                'show_result_immediately' => $exam->show_results_immediately,
                'show_correct_answers' => $exam->show_correct_answers,
                'show_explanations' => $exam->show_explanations,
            ],
            'questions' => $formattedQuestions,
        ]);
    }


    // Update question (Admin/Trainer only)
    public function updateQuestion(Exam $exam, ExamQuestion $question, Request $request)
    {
        // Ensure question belongs to this exam
        abort_unless($question->exam_id === $exam->id, 404);

        $validated = $request->validate([
            'question_text' => ['sometimes', 'string'],
            'question_media' => ['nullable', 'array'],
            'question_media.*.type' => ['required_with:question_media', 'in:image,video,audio,document'],
            'question_media.*.url' => ['required_with:question_media', 'url'],
            'question_media.*.title' => ['nullable', 'string'],
            'question_media.*.description' => ['nullable', 'string'],
            'explanation' => ['nullable', 'string'],
            'points' => ['nullable', 'integer', 'min:1'],
            'is_required' => ['nullable', 'boolean'],
            'question_type' => ['sometimes', 'in:single_choice,multiple_choice,text'],
            'sequence' => ['nullable', 'integer', 'min:1'],
            'choices' => ['sometimes', 'array'],
            'choices.*.choice_text' => ['required', 'string'],
            'choices.*.choice_media' => ['nullable', 'array'],
            'choices.*.choice_media.*.type' => ['required_with:choices.*.choice_media', 'in:image,video,audio,document'],
            'choices.*.choice_media.*.url' => ['required_with:choices.*.choice_media', 'url'],
            'choices.*.choice_media.*.title' => ['nullable', 'string'],
            'choices.*.choice_media.*.description' => ['nullable', 'string'],
            'choices.*.is_correct' => ['required', 'boolean'],
            'choices.*.explanation' => ['nullable', 'string'],
            'choices.*.points' => ['nullable', 'integer', 'min:0'],
            'metadata' => ['nullable', 'array'],
        ]);

        $question->update($validated);

        // Update choices if provided
        if (isset($validated['choices'])) {
            // Delete existing choices
            $question->choices()->delete();
            
            // Add new choices
            foreach ($validated['choices'] as $choice) {
                ExamChoice::create([
                    'question_id' => $question->id,
                    'choice_text' => $choice['choice_text'],
                    'choice_media' => $choice['choice_media'] ?? null,
                    'explanation' => $choice['explanation'] ?? null,
                    'is_correct' => $choice['is_correct'],
                    'points' => $choice['points'] ?? 0,
                    'metadata' => $choice['metadata'] ?? null,
                ]);
            }
        }

        return response()->json($question->load('choices'));
    }

    // Delete question (Admin/Trainer only)
    public function deleteQuestion(Exam $exam, ExamQuestion $question)
    {
        // Ensure question belongs to this exam
        abort_unless($question->exam_id === $exam->id, 404);

        $question->delete();
        return response()->json(['message' => 'Question deleted']);
    }

    // Get exam with all questions and choices
    public function getExamWithQuestions(Exam $exam)
    {
        $questions = $exam->questions()->with(['choices' => function ($query) {
            $query->orderBy('id');
        }])->orderBy('sequence')->get();

        return response()->json([
            'exam' => $exam,
            'questions' => $questions,
            'total_questions' => $questions->count(),
            'total_points' => $questions->sum('points'),
            'has_media' => $questions->some(function ($question) {
                return $question->hasMedia();
            })
        ]);
    }

    // Get exam questions for taking (without correct answers)
    public function getExamForTaking(Exam $exam)
    {
        $query = $exam->questions()
            ->where('is_required', true)
            ->with(['choices' => function ($query) {
                if ($exam->randomize_choices) {
                    $query->inRandomOrder();
                } else {
                    $query->orderBy('id');
                }
            }]);
            
        if ($exam->randomize_questions) {
            $query->inRandomOrder();
        } else {
            $query->orderBy('sequence');
        }
        
        $questions = $query->get()->map(function ($question) {
            return $question->getForExam();
        });

        // Get user's exam registration for timing info
        $registration = ExamRegistration::where('user_id', request()->user()->id)
            ->where('exam_id', $exam->id)
            ->first();

        $timeInfo = null;
        if ($registration && $registration->started_at) {
            $timeElapsed = $registration->started_at->diffInMinutes(now());
            $timeRemaining = max(0, $exam->duration_minutes - $timeElapsed);
            $timeExceeded = $timeElapsed > $exam->duration_minutes;
            
            $timeInfo = [
                'time_elapsed_minutes' => $timeElapsed,
                'time_remaining_minutes' => $timeRemaining,
                'time_limit_minutes' => $exam->duration_minutes,
                'time_exceeded' => $timeExceeded,
                'started_at' => $registration->started_at,
            ];
        }

        return response()->json([
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'description' => $exam->description,
                'rules' => $exam->rules,
                'instructions' => $exam->instructions,
                'hashtags' => $exam->hashtags,
                'duration_minutes' => $exam->duration_minutes,
                'passing_score' => $exam->passing_score,
                'time_warning_minutes' => $exam->time_warning_minutes,
                'max_attempts' => $exam->max_attempts,
                'randomize_questions' => $exam->randomize_questions,
                'randomize_choices' => $exam->randomize_choices,
                'show_results_immediately' => $exam->show_results_immediately,
                'show_correct_answers' => $exam->show_correct_answers,
                'show_explanations' => $exam->show_explanations,
                'allow_tab_switching' => $exam->allow_tab_switching,
                'track_tab_changes' => $exam->track_tab_changes,
            ],
            'questions' => $questions,
            'total_questions' => $questions->count(),
            'total_points' => $questions->sum('points'),
            'time_info' => $timeInfo,
        ]);
    }

    // Submit answers and score exam
    public function submit(Exam $exam, Request $request)
    {
        // Simple validation first
        $data = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*.question_id' => ['required', 'integer'],
            'answers.*.choice_id' => ['nullable', 'integer'],
            'answers.*.choice_ids' => ['nullable', 'array'],
            'answers.*.choice_ids.*' => ['integer'],
            'answers.*.answer_text' => ['nullable', 'string'],
        ]);

        // Check if user has completed the related training
        if ($exam->training_id) {
            $training = \App\Models\Training::find($exam->training_id);
            
            if ($training->type === 'video') {
                // For video trainings, check if user has certificate
                $trainingCompletion = \App\Models\Certificate::where('user_id', $request->user()->id)
                    ->where('related_training_id', $exam->training_id)
                    ->first();
            } else {
                // For non-video trainings, check registration status
            $trainingCompletion = \App\Models\TrainingRegistration::where('user_id', $request->user()->id)
                ->where('training_id', $exam->training_id)
                ->where('status', 'completed')
                ->first();
            }
            
            if (!$trainingCompletion) {
                return response()->json([
                    'message' => 'Bu imtahanı vermək üçün əvvəlcə əlaqəli təlimi tamamlamalısınız',
                    'training_id' => $exam->training_id,
                    'training_title' => $exam->training->title ?? 'Unknown Training'
                ], 403);
            }
        }

        // Check if user has already passed this exam (if max_attempts is set)
        $existingRegistrations = ExamRegistration::where('user_id', $request->user()->id)
            ->where('exam_id', $exam->id)
            ->get();
        
        
        $passedAttempts = $existingRegistrations->where('status', 'passed')->count();
        if ($exam->max_attempts && $passedAttempts > 0) {
            // Get the passed attempt
            $passedAttempt = $existingRegistrations->where('status', 'passed')->first();
            
            // Get certificate if exists
            $certificate = null;
            if ($passedAttempt && $passedAttempt->certificate_id) {
                $cert = Certificate::find($passedAttempt->certificate_id);
                if ($cert) {
                    $certificate = [
                        'id' => $cert->id,
                        'certificate_number' => $cert->certificate_number,
                        'issue_date' => $cert->issue_date->format('d.m.Y'),
                        'status' => $cert->status,
                        'is_active' => $cert->isActive(),
                        'download_url' => $cert->download_url,
                        'verification_url' => $cert->verification_url,
                        'pdf_url' => $cert->pdf_url,
                    ];
                }
            }
            
            return response()->json([
                'message' => 'Bu imtahanı artıq keçmisiniz',
                'status' => 'already_passed',
                'exam' => [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'passing_score' => $exam->passing_score,
                ],
                'last_attempt' => [
                    'id' => $passedAttempt->id,
                    'score' => $passedAttempt->score,
                    'status' => $passedAttempt->status,
                    'completed_at' => $passedAttempt->finished_at,
                    'attempt_number' => $passedAttempt->attempt_number,
                ],
                'certificate' => $certificate,
                'max_attempts' => $exam->max_attempts,
                'attempts_used' => $existingRegistrations->count(),
            ], 403);
        }

        // Check if user has exceeded max attempts
        if ($exam->max_attempts && $existingRegistrations->count() >= $exam->max_attempts) {
            return response()->json([
                'message' => 'Maksimum cəhd sayını keçmisiniz',
                'max_attempts' => $exam->max_attempts,
                'attempts_used' => $existingRegistrations->count()
            ], 403);
        }

        // Check if user has an active attempt
        $activeRegistration = $existingRegistrations->where('status', 'in_progress')->first();
        if (!$activeRegistration) {
            return response()->json([
                'message' => 'Aktiv imtahan tapılmadı. Əvvəlcə imtahanı başlatmalısınız.',
                'exam_id' => $exam->id,
                'exam_title' => $exam->title
            ], 404);
        }

        // Use the existing active registration
        $registration = $activeRegistration;
        
        // Force update the updated_at timestamp
        $registration->touch();

        // Check if time limit exceeded
        $timeElapsed = $registration->started_at ? now()->diffInMinutes($registration->started_at) : 0;
        $timeLimit = $exam->duration_minutes;
        $timeExceeded = $timeElapsed > $timeLimit;

        try {
            DB::transaction(function () use ($registration, $data, $exam, $request, $timeExceeded) {
                // Use registration's total_questions for scoring (this is the actual number of questions shown to user)
                $totalQuestionsForScoring = $registration->total_questions ?? count($data['answers']);
                $correctAnswers = 0;
                $hasTextQuestions = false;
                $textQuestionsCount = 0;
                
                // Load all questions with choices in a single query to avoid N+1 problem
                $questionIds = collect($data['answers'])->pluck('question_id');
                $questions = ExamQuestion::where('exam_id', $exam->id)
                    ->whereIn('id', $questionIds)
                    ->with('choices')
                    ->get()
                    ->keyBy('id');

                foreach ($data['answers'] as $ans) {
                    $question = $questions->get($ans['question_id']);
                    if (!$question) {
                        continue; // Skip invalid question IDs
                    }

                    // Check if this is a text question
                    if ($question->question_type === 'text') {
                        $hasTextQuestions = true;
                        $textQuestionsCount++;
                    } else {
                        // For auto-graded questions, check if answer is correct
                        $isCorrect = $question->isAnswerCorrect($ans);
                        if ($isCorrect) {
                            $correctAnswers++;
                        }
                    }

                    // Store user answer
                    try {
                        ExamUserAnswer::updateOrCreate([
                            'registration_id' => $registration->id,
                            'question_id' => $question->id,
                        ], [
                            'choice_id' => $ans['choice_id'] ?? null,
                            'choice_ids' => $ans['choice_ids'] ?? null,
                            'answer_text' => $ans['answer_text'] ?? null,
                            'answered_at' => now(),
                            'needs_manual_grading' => $question->question_type === 'text' && !empty(trim($ans['answer_text'] ?? '')),
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Error saving user answer for question ' . $question->id . ': ' . $e->getMessage());
                        throw $e; // Re-throw to fail the transaction
                    }
                }

                // Calculate score using new system: (correct_answers * 100) / total_questions
                $autoGradedQuestions = $totalQuestionsForScoring - $textQuestionsCount;
                $score = $autoGradedQuestions > 0 ? (int) floor(($correctAnswers * 100) / $autoGradedQuestions) : 0;
                
                // Determine if passed based on auto-graded questions
                    $passed = $score >= (int) $exam->passing_score;

                // Determine final status based on new logic
            if ($timeExceeded) {
                $finalStatus = 'timeout';
            } elseif ($hasTextQuestions) {
                    // If there are text questions, check if auto-graded score is sufficient
                    if ($passed && $exam->show_results_immediately) {
                        // If auto-graded questions are sufficient and show_result_immediately is true
                        $finalStatus = 'passed';
            } else {
                        // Need manual grading for text questions
                        $finalStatus = 'pending_review';
                    }
                } else {
                    // All questions are auto-graded
                $finalStatus = $passed ? 'passed' : 'failed';
            }

            $registration->update([
                'status' => $finalStatus,
                'score' => $score,
                'finished_at' => now(),
                'needs_manual_grading' => $hasTextQuestions,
                'auto_graded_score' => $hasTextQuestions ? $score : null,
            ]);
        });

        } catch (\Exception $e) {
            \Log::error('Error in exam submission: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error submitting exam: ' . $e->getMessage(),
                'debug' => [
                    'exam_id' => $exam->id,
                    'user_id' => $request->user()->id,
                    'answers_count' => count($data['answers'])
                ]
            ], 500);
        }

        // Refresh the registration to get updated data
        $registration->refresh();
        
        // Handle certificate generation and email notification outside transaction
        try {
            if ($registration->status === 'passed' && !$timeExceeded && !$hasTextQuestions) {
                // Check if training provides certificates
                $training = Training::find($exam->training_id);
                if ($training && $training->has_certificate) {
                    // Generate PDF certificate using Python script
                    $this->generatePdfCertificate($request->user(), $exam, $training, $registration);
                }
            }
            
            // Send email notification based on status
            $this->sendExamNotification($request->user(), $exam, $registration, $registration->status);
        } catch (\Exception $e) {
            \Log::error('Error in post-submission tasks: ' . $e->getMessage());
            // Don't fail the entire request for certificate/email errors
        }
        
        $response = $registration->load('certificate');
        
        // Add timing information to response
        $response->time_elapsed_minutes = $timeElapsed;
        $response->time_limit_minutes = $timeLimit;
        $response->time_exceeded = $timeExceeded;

        // Add message based on status
        if ($response->status === 'pending_review') {
            $response->message = 'Sizin imtahan neticeniz yaxın günlər ərzində hesablanıb sizə bildiriləcək.';
        } elseif ($response->status === 'passed') {
            $response->message = 'Təbriklər! İmtahanı uğurla keçdiniz.';
        } elseif ($response->status === 'failed') {
            $response->message = 'İmtahanı keçə bilmədiniz. Yenidən cəhd edə bilərsiniz.';
        } elseif ($response->status === 'timeout') {
            $response->message = 'Vaxt bitdi. İmtahan avtomatik olaraq tamamlandı.';
        }

        // Add certificate details if exists
        if ($response->certificate) {
            $response->certificate_details = [
                'id' => $response->certificate->id,
                'certificate_number' => $response->certificate->certificate_number,
                'issue_date' => $response->certificate->issue_date,
                'expiry_date' => $response->certificate->expiry_date,
                'status' => $response->certificate->status,
                'is_active' => $response->certificate->isActive(),
                'download_url' => $response->certificate->download_url,
                'verification_url' => $response->certificate->verification_url,
                'pdf_url' => $response->certificate->pdf_url,
            ];
        }

        return response()->json($response);
    }

    /**
     * Generate PDF certificate using Python script
     */
    private function generatePdfCertificate($user, $exam, $training, $registration)
    {
        try {
            // Check if certificate already exists for this user and exam
            $existingCertificate = Certificate::where('user_id', $user->id)
                ->where('related_exam_id', $exam->id)
                ->first();
                
            if ($existingCertificate) {
                Log::info('Certificate already exists for user ' . $user->id . ' and exam ' . $exam->id);
                // Update registration with existing certificate
                $registration->update(['certificate_id' => $existingCertificate->id]);
                return;
            }
            
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
                    'sertifikat_description' => $exam->sertifikat_description,
                ],
                'training' => [
                    'id' => $training->id,
                    'title' => $training->title,
                    'description' => $training->description,
                ]
            ];

            // Run Python script
            $pythonScript = base_path('certificate_generator.py');
            $jsonData = json_encode($data);
            
            // Write JSON to temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'cert_data_');
            file_put_contents($tempFile, $jsonData);
            
            $result = Process::run("C:\\Python313\\python.exe {$pythonScript} --file {$tempFile}");
            
            // Clean up temp file
            unlink($tempFile);
            
            if ($result->failed()) {
                Log::error('Python certificate generation failed: ' . $result->errorOutput());
                return;
            }

            $output = json_decode($result->output(), true);
            
            if (!$output['success']) {
                Log::error('Python certificate generation error: ' . $output['error']);
                return;
            }

            // Create certificate record with duplicate key handling
            try {
                $certificate = Certificate::create([
                    'user_id' => $user->id,
                    'related_training_id' => $training->id,
                    'related_exam_id' => $exam->id,
                    'certificate_number' => $output['certificate_number'],
                    'issue_date' => now()->toDateString(),
                    'issuer_name' => 'Aqrar Portal',
                    'status' => 'active',
                    'digital_signature' => $output['digital_signature'],
                    'pdf_path' => $output['pdf_path'],
                    'pdf_url' => 'http://localhost:8000/storage/' . $output['pdf_path'],
                ]);
                
                // Update registration with certificate
                $registration->update(['certificate_id' => $certificate->id]);
                
            } catch (\Illuminate\Database\QueryException $e) {
                if ($e->getCode() == '23505') { // Unique violation
                    Log::warning('Certificate with number ' . $output['certificate_number'] . ' already exists, using existing certificate');
                    
                    // Find existing certificate
                    $existingCert = Certificate::where('certificate_number', $output['certificate_number'])->first();
                    if ($existingCert) {
                        $registration->update(['certificate_id' => $existingCert->id]);
                        Log::info('Updated registration with existing certificate ID: ' . $existingCert->id);
                    }
                } else {
                    throw $e; // Re-throw if it's not a unique violation
                }
            }

            Log::info('PDF certificate generated successfully', [
                'user_id' => $user->id,
                'exam_id' => $exam->id,
                'certificate_id' => $certificate->id ?? 'existing',
                'digital_signature' => $output['digital_signature']
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating PDF certificate: ' . $e->getMessage());
        }
    }

    /**
     * Get user's exam result/history for a specific exam
     */
    public function getUserExamResult(Exam $exam, Request $request)
    {
        $user = $request->user();
        
        // Check if user has passed this exam
        $passedAttempt = ExamRegistration::where('user_id', $user->id)
            ->where('exam_id', $exam->id)
            ->where('status', 'passed')
            ->first();
        
        if (!$passedAttempt) {
            return response()->json([
                'message' => 'Bu imtahanı hələ keçməmisiniz',
                'exam' => [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'passing_score' => $exam->passing_score,
                ],
                'has_passed' => false,
                'certificate' => null,
            ]);
        }
        
        // Check if user has certificate for this exam
        $certificate = Certificate::where('user_id', $user->id)
            ->where('related_exam_id', $exam->id)
            ->first();
        
        // If no certificate exists, create one using Python script
        if (!$certificate) {
            $training = Training::find($exam->training_id);
            if ($training && $training->has_certificate) {
                Log::info('Creating certificate for user ' . $user->id . ' exam ' . $exam->id);
                $this->generatePdfCertificate($user, $exam, $training, $passedAttempt);
                
                // Reload certificate after creation
                $certificate = Certificate::where('user_id', $user->id)
                    ->where('related_exam_id', $exam->id)
                    ->first();
                
                Log::info('Certificate created: ' . ($certificate ? 'Yes' : 'No'));
            } else {
                Log::info('Training does not have certificate enabled', [
                    'training_id' => $training->id,
                    'has_certificate' => $training ? $training->has_certificate : 'null'
                ]);
            }
        }
        
        // Get all user's attempts for this exam
        $allAttempts = ExamRegistration::where('user_id', $user->id)
            ->where('exam_id', $exam->id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Calculate statistics
        $passedCount = $allAttempts->where('status', 'passed')->count();
        $failedCount = $allAttempts->where('status', 'failed')->count();
        $pendingCount = $allAttempts->where('status', 'pending_review')->count();
        $averageScore = $allAttempts->avg('score');
        $bestScore = $allAttempts->max('score');
        
        // Calculate correct and incorrect answers for best attempt using new system
        $bestAttemptAnswers = ExamUserAnswer::where('registration_id', $passedAttempt->id)->get();
        $totalQuestions = $passedAttempt->total_questions ?? $exam->questions()->count();
        $correctAnswers = 0;
        $incorrectAnswers = 0;
        
        // Get questions with choices for detailed results
        $questionIds = $bestAttemptAnswers->pluck('question_id');
        $questions = ExamQuestion::whereIn('id', $questionIds)
            ->with('choices')
            ->get()
            ->keyBy('id');
        
        // Format questions with user answers and correct answers
        $detailedQuestions = [];
        
        foreach ($bestAttemptAnswers as $answer) {
            $question = $questions->get($answer->question_id);
            if (!$question) continue;
            
            $isCorrect = false;
            if (!$question->needsManualGrading()) {
                $isCorrect = $question->isAnswerCorrect($answer);
                if ($isCorrect) {
                    $correctAnswers++;
                } else {
                    $incorrectAnswers++;
                }
            }
            
            // Format question with user answer and correct answer
            $questionData = [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'question_type' => $question->question_type,
                'difficulty' => $question->difficulty,
                'explanation' => $exam->show_explanations ? $question->explanation : null,
                'is_correct' => $isCorrect,
                'user_answer' => [
                    'choice_id' => $answer->choice_id,
                    'choice_ids' => $answer->choice_ids,
                    'answer_text' => $answer->answer_text,
                    'answered_at' => $answer->answered_at,
                ],
                'correct_answer' => null,
                'choices' => []
            ];
            
            // Add choices with correct answers marked
            if (in_array($question->question_type, ['single_choice', 'multiple_choice', 'true_false'])) {
                $questionData['choices'] = $question->choices->map(function ($choice) use ($exam) {
                    return [
                        'id' => $choice->id,
                        'choice_text' => $choice->choice_text,
                        'is_correct' => $exam->show_correct_answers ? $choice->is_correct : null,
                        'sequence' => $choice->sequence,
                    ];
                });
                
                // Mark correct answer only if show_correct_answers is true
                if ($exam->show_correct_answers) {
                    if ($question->question_type === 'single_choice' || $question->question_type === 'true_false') {
                        $correctChoice = $question->choices->where('is_correct', true)->first();
                        if ($correctChoice) {
                            $questionData['correct_answer'] = [
                                'choice_id' => $correctChoice->id,
                                'choice_text' => $correctChoice->choice_text,
                            ];
                        }
                    } elseif ($question->question_type === 'multiple_choice') {
                        $correctChoices = $question->choices->where('is_correct', true);
                        $questionData['correct_answer'] = [
                            'choice_ids' => $correctChoices->pluck('id')->toArray(),
                            'choices' => $correctChoices->map(function ($choice) {
                                return [
                                    'id' => $choice->id,
                                    'choice_text' => $choice->choice_text,
                                ];
                            })->toArray(),
                        ];
                    }
                }
            }
            
            $detailedQuestions[] = $questionData;
        }
        
        // Format all attempts
        $formattedAttempts = $allAttempts->map(function ($attempt) use ($totalQuestions) {
            $certificate = null;
            if ($attempt->certificate) {
            $certificate = [
                'id' => $attempt->certificate->id,
                    'certificate_number' => $attempt->certificate->certificate_number,
                    'issue_date' => $attempt->certificate->issue_date->format('Y-m-d'),
                    'download_url' => 'http://localhost:8000/storage/' . $attempt->certificate->pdf_path,
                ];
            }
            
            // Calculate correct and incorrect answers for this attempt using new system
            $attemptAnswers = ExamUserAnswer::where('registration_id', $attempt->id)->get();
            $correctAnswers = 0;
            $incorrectAnswers = 0;
            $attemptTotalQuestions = $attempt->total_questions ?? $totalQuestions;
            
            foreach ($attemptAnswers as $answer) {
                $question = ExamQuestion::find($answer->question_id);
                if ($question && !$question->needsManualGrading()) {
                    $isCorrect = $question->isAnswerCorrect($answer);
                    if ($isCorrect) {
                        $correctAnswers++;
                    } else {
                        $incorrectAnswers++;
                    }
                }
            }
            
            return [
                'id' => $attempt->id,
                'exam_id' => $attempt->exam_id,
                'status' => $attempt->status,
                'score' => $attempt->score,
                'time_spent_minutes' => $attempt->time_spent_minutes,
                'attempt_number' => $attempt->attempt_number,
                'started_at' => $attempt->started_at,
                'finished_at' => $attempt->finished_at,
                'created_at' => $attempt->created_at,
                'correct_answers' => $correctAnswers,
                'incorrect_answers' => $incorrectAnswers,
                'total_questions' => $attemptTotalQuestions,
                'certificate' => $certificate,
            ];
        });
        
        // Prepare response
        $response = [
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'description' => $exam->description,
                'passing_score' => $exam->passing_score,
                'max_attempts' => $exam->max_attempts,
                'total_questions' => $totalQuestions,
                'exam_question_count' => $exam->exam_question_count,
                'shuffle_questions' => $exam->randomize_questions,
                'shuffle_choices' => $exam->randomize_choices,
                'show_result_immediately' => $exam->show_results_immediately,
                'show_correct_answers' => $exam->show_correct_answers,
                'show_explanations' => $exam->show_explanations,
            ],
            'best_attempt' => [
                'id' => $passedAttempt->id,
                'exam_id' => $passedAttempt->exam_id,
                'status' => $passedAttempt->status,
                'score' => $passedAttempt->score,
                'time_spent_minutes' => $passedAttempt->time_spent_minutes,
                'attempt_number' => $passedAttempt->attempt_number,
                'started_at' => $passedAttempt->started_at,
                'finished_at' => $passedAttempt->finished_at,
                'created_at' => $passedAttempt->created_at,
                'correct_answers' => $correctAnswers,
                'incorrect_answers' => $incorrectAnswers,
                'total_questions' => $totalQuestions,
                'questions' => $detailedQuestions,
                'certificate' => $certificate ? [
                    'id' => $certificate->id,
                    'certificate_number' => $certificate->certificate_number,
                    'issue_date' => $certificate->issue_date->format('Y-m-d'),
                    'download_url' => 'http://localhost:8000/storage/' . $certificate->pdf_path,
                ] : null,
            ],
            'attempts' => $formattedAttempts,
            'statistics' => [
                'passed_count' => $passedCount,
                'failed_count' => $failedCount,
                'pending_count' => $pendingCount,
                'average_score' => round($averageScore, 2),
                'best_score' => $bestScore,
            ],
            'has_attempts' => $allAttempts->count() > 0,
            'total_attempts' => $allAttempts->count(),
            'remaining_attempts' => $exam->max_attempts ? max(0, $exam->max_attempts - $allAttempts->count()) : null,
        ];
        
        return response()->json($response);
    }

    // Upload media to exam question or choice
    public function uploadQuestionMedia(Request $request, Exam $exam)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'], // 10MB max for security
            'type' => ['required', 'in:image,video,audio,document'],
            'target_type' => ['required', 'in:question,choice'],
            'question_id' => ['required', 'exists:exam_questions,id'],
            'choice_id' => ['nullable', 'exists:exam_choices,id'],
            'title' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        $file = $request->file('file');
        $path = $file->store('exams/' . $exam->id . '/questions/' . $validated['question_id'], 'public');

        $mediaFile = [
            'type' => $validated['type'],
            'url' => Storage::url($path),
            'filename' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'title' => $validated['title'] ?? $file->getClientOriginalName(),
            'description' => $validated['description'] ?? null,
        ];

        if ($validated['target_type'] === 'question') {
            $question = ExamQuestion::where('exam_id', $exam->id)
                ->where('id', $validated['question_id'])
                ->firstOrFail();

            $currentMedia = $question->question_media ?? [];
            $currentMedia[] = $mediaFile;

            $question->update(['question_media' => $currentMedia]);

            return response()->json([
                'message' => 'Question media uploaded successfully',
                'media_file' => $mediaFile,
                'question' => $question->fresh()
            ], 201);
        } else {
            $choice = ExamChoice::where('question_id', $validated['question_id'])
                ->where('id', $validated['choice_id'])
                ->firstOrFail();

            $currentMedia = $choice->choice_media ?? [];
            $currentMedia[] = $mediaFile;

            $choice->update(['choice_media' => $currentMedia]);

            return response()->json([
                'message' => 'Choice media uploaded successfully',
                'media_file' => $mediaFile,
                'choice' => $choice->fresh()
            ], 201);
        }
    }

    /**
     * Update exam status
     */
    public function updateStatus(Request $request, Exam $exam)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,published,archived'
        ]);

        $exam->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Exam status updated successfully',
            'exam' => $exam->fresh()
        ]);
    }

    /**
     * Send exam notification email based on status
     */
    private function sendExamNotification($user, $exam, $registration, $status)
    {
        try {
            switch ($status) {
                case 'passed':
                    $certificate = Certificate::where('user_id', $user->id)
                        ->where('related_exam_id', $exam->id)
                        ->first();
                    \Mail::to($user->email)->send(new \App\Mail\ExamPassedMail($user, $exam, $registration, $certificate));
                    break;
                    
                case 'failed':
                    \Mail::to($user->email)->send(new \App\Mail\ExamFailedMail($user, $exam, $registration));
                    break;
                    
                case 'pending_review':
                    \Mail::to($user->email)->send(new \App\Mail\ExamPendingReviewMail($user, $exam, $registration));
                    break;
            }
        } catch (\Exception $e) {
            \Log::error('Error sending exam notification: ' . $e->getMessage());
        }
    }
}


