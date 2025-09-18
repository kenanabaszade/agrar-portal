<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamRegistration;
use App\Models\ExamQuestion;
use App\Models\ExamChoice;
use App\Models\ExamUserAnswer;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ExamController extends Controller
{
    
    /**
     * Get exams list with pagination, search and filtering for admin dashboard
     */
    public function index(Request $request)
    {
        $query = Exam::with(['training.trainer', 'questions'])
            ->withCount(['questions', 'registrations']);

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
            
            // Determine status with null checks
            if ($exam->start_date && $exam->start_date->isFuture()) {
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

            // Calculate completion rate
            $totalRegistrations = $exam->registrations_count;
            $completedRegistrations = $exam->registrations()
                ->whereIn('status', ['passed', 'failed', 'completed'])->count();
            
            $exam->completion_rate = $totalRegistrations > 0 
                ? round(($completedRegistrations / $totalRegistrations) * 100, 1) 
                : 0;

            // Pass rate
            $passedRegistrations = $exam->registrations()
                ->where('status', 'passed')->count();
            
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
            'training_id' => ['nullable', 'exists:trainings,id'], // Optional for independent exams
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required_without:training_id', 'string', 'max:255'], // Required if no training
            'passing_score' => ['required', 'integer', 'min:0', 'max:100'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:480'], // Max 8 hours
            'start_date' => ['nullable', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            
            // Questions validation
            'questions' => ['required', 'array', 'min:1'], // At least 1 question required
            'questions.*.question_text' => ['required', 'string'],
            'questions.*.question_type' => ['required', 'in:single_choice,multiple_choice,text'],
            'questions.*.difficulty' => ['nullable', 'in:easy,medium,hard'],
            'questions.*.question_media' => ['nullable', 'array'],
            'questions.*.question_media.*.type' => ['required_with:questions.*.question_media', 'in:image,video,audio,document'],
            'questions.*.question_media.*.url' => ['required_with:questions.*.question_media', 'url'],
            'questions.*.question_media.*.title' => ['nullable', 'string'],
            'questions.*.question_media.*.description' => ['nullable', 'string'],
            'questions.*.explanation' => ['nullable', 'string'],
            'questions.*.points' => ['nullable', 'integer', 'min:1'],
            'questions.*.is_required' => ['nullable', 'boolean'],
            'questions.*.sequence' => ['nullable', 'integer', 'min:1'],
            'questions.*.metadata' => ['nullable', 'array'],
            
            // Choices validation (required for single_choice and multiple_choice)
            'questions.*.choices' => ['required_if:questions.*.question_type,single_choice,multiple_choice', 'array'],
            'questions.*.choices.*.choice_text' => ['required', 'string'],
            'questions.*.choices.*.choice_media' => ['nullable', 'array'],
            'questions.*.choices.*.choice_media.*.type' => ['required_with:questions.*.choices.*.choice_media', 'in:image,video,audio,document'],
            'questions.*.choices.*.choice_media.*.url' => ['required_with:questions.*.choices.*.choice_media', 'url'],
            'questions.*.choices.*.choice_media.*.title' => ['nullable', 'string'],
            'questions.*.choices.*.choice_media.*.description' => ['nullable', 'string'],
            'questions.*.choices.*.is_correct' => ['required', 'boolean'],
            'questions.*.choices.*.explanation' => ['nullable', 'string'],
            'questions.*.choices.*.points' => ['nullable', 'integer', 'min:0'],
            'questions.*.choices.*.metadata' => ['nullable', 'array'],
        ]);

        // Additional validation: ensure at least one correct answer for choice questions
        foreach ($validated['questions'] as $index => $question) {
            if (in_array($question['question_type'], ['single_choice', 'multiple_choice'])) {
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
        $exam = DB::transaction(function () use ($validated, $request) {
            // Extract exam data
            $examData = collect($validated)->except('questions')->toArray();
            
            // If training is provided, use training's category
            if ($examData['training_id']) {
                $training = \App\Models\Training::find($examData['training_id']);
                $examData['category'] = $training->category;
            }
            // Otherwise, use the provided category for independent exam
            
            $exam = Exam::create($examData);
            
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
                if (in_array($questionData['question_type'], ['single_choice', 'multiple_choice']) && !empty($choices)) {
                    foreach ($choices as $choiceData) {
                        $question->choices()->create([
                            'choice_text' => $choiceData['choice_text'],
                            'choice_media' => $choiceData['choice_media'] ?? null,
                            'explanation' => $choiceData['explanation'] ?? null,
                            'is_correct' => $choiceData['is_correct'],
                            'points' => $choiceData['points'] ?? 0,
                            'metadata' => $choiceData['metadata'] ?? null,
                        ]);
                    }
                }
            }
            
            return $exam;
        });
        
        // Load relationships for response
        $exam->load(['training.trainer', 'questions.choices']);
        
        // Add audit log
        \App\Models\AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'created',
            'entity' => 'exam',
            'entity_id' => $exam->id,
            'details' => [
                'title' => $exam->title,
                'exam_type' => $exam->training_id ? 'training_based' : 'independent',
                'training_id' => $exam->training_id,
                'training_title' => $exam->training ? $exam->training->title : null,
                'category' => $exam->category,
                'questions_count' => $exam->questions()->count(),
                'total_points' => $exam->questions()->sum('points'),
                'difficulty_breakdown' => $exam->questions()->get()->groupBy('difficulty')->map->count(),
            ]
        ]);

        return response()->json([
            'message' => 'Exam created successfully with questions',
            'exam' => $exam,
            'summary' => [
                'total_questions' => $exam->questions()->count(),
                'total_points' => $exam->questions()->sum('points'),
                'question_types' => $exam->questions()->get()->groupBy('question_type')->map(function($questions) {
                    return $questions->count();
                }),
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

        return response()->json($exam);
    }

    public function update(Request $request, Exam $exam)
    {
        $validated = $request->validate([
            'training_id' => ['sometimes', 'exists:trainings,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'passing_score' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'duration_minutes' => ['sometimes', 'integer', 'min:1', 'max:480'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        // Store original values for audit
        $originalValues = $exam->only(array_keys($validated));
        
        $exam->update($validated);
        
        // Load relationships for response
        $exam->load(['training.trainer', 'questions']);

        // Add audit log
        \App\Models\AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'updated',
            'entity' => 'exam',
            'entity_id' => $exam->id,
            'details' => [
                'title' => $exam->title,
                'changes' => array_diff_assoc($validated, $originalValues),
                'original' => $originalValues,
            ]
        ]);

        return response()->json([
            'message' => 'Exam updated successfully',
            'exam' => $exam
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
        $exam->delete();

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
        $reg = ExamRegistration::firstOrCreate([
            'user_id' => $request->user()->id,
            'exam_id' => $exam->id,
        ], [
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
        if ($reg->status !== 'in_progress') {
            $reg->update(['status' => 'in_progress', 'started_at' => now()]);
        }
        return response()->json($reg);
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
        $questions = $exam->questions()
            ->where('is_required', true)
            ->with(['choices' => function ($query) {
                $query->orderBy('id');
            }])
            ->orderBy('sequence')
            ->get()
            ->map(function ($question) {
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
                'duration_minutes' => $exam->duration_minutes,
                'passing_score' => $exam->passing_score,
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

        $registration = ExamRegistration::where('user_id', $request->user()->id)
            ->where('exam_id', $exam->id)
            ->first();

        if (!$registration) {
            return response()->json(['message' => 'No registration found for this exam'], 404);
        }

        // Check if time limit exceeded
        $timeElapsed = $registration->started_at ? $registration->started_at->diffInMinutes(now()) : 0;
        $timeLimit = $exam->duration_minutes;
        $timeExceeded = $timeElapsed > $timeLimit;

        try {
            DB::transaction(function () use ($registration, $data, $exam, $request, $timeExceeded) {
                $totalPoints = 0;
                $earnedPoints = 0;
                
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

                    if ($question->is_required) {
                        $totalPoints += $question->points;
                        
                        // Calculate points with error handling
                        try {
                            $questionPoints = $question->calculatePoints($ans);
                            $earnedPoints += $questionPoints;
                        } catch (\Exception $e) {
                            \Log::error('Error calculating points for question ' . $question->id . ': ' . $e->getMessage());
                            // Skip points calculation if there's an error
                        }
                    }

                    // Store user answer with simplified approach
                    try {
                        ExamUserAnswer::updateOrCreate([
                            'registration_id' => $registration->id,
                            'question_id' => $question->id,
                        ], [
                            'choice_id' => $ans['choice_id'] ?? null,
                            'choice_ids' => $ans['choice_ids'] ?? null,
                            'answer_text' => $ans['answer_text'] ?? null,
                            'answered_at' => now(),
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Error saving user answer for question ' . $question->id . ': ' . $e->getMessage());
                        throw $e; // Re-throw to fail the transaction
                    }
                }

                $score = $totalPoints > 0 ? (int) floor(($earnedPoints / $totalPoints) * 100) : 0;
                $passed = $score >= (int) $exam->passing_score;

            // Determine final status
            $finalStatus = $timeExceeded ? 'timeout' : ($passed ? 'passed' : 'failed');

            $registration->update([
                'status' => $finalStatus,
                'score' => $score,
                'finished_at' => now(),
            ]);

            if ($passed && !$timeExceeded) {
                $cert = Certificate::create([
                    'user_id' => $request->user()->id,
                    'related_training_id' => $exam->training_id,
                    'related_exam_id' => $exam->id,
                    'certificate_number' => Str::uuid()->toString(),
                    'issue_date' => now()->toDateString(),
                    'issuer_name' => 'Aqrar Portal',
                    'status' => 'active',
                ]);
                $registration->update(['certificate_id' => $cert->id]);
            }
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

        $response = ExamRegistration::find($registration->id)->load('certificate');
        
        // Add timing information to response
        $response->time_elapsed_minutes = $timeElapsed;
        $response->time_limit_minutes = $timeLimit;
        $response->time_exceeded = $timeExceeded;

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
}


