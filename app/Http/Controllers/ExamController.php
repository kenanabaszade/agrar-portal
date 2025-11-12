<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamRegistration;
use App\Models\ExamQuestion;
use App\Models\ExamChoice;
use App\Models\ExamUserAnswer;
use App\Models\Certificate;
use App\Models\TrainingLesson;
use App\Models\UserTrainingProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\TranslationHelper;
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
            
            // Determine status
            if ($exam->start_date && $exam->start_date->isFuture()) {
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
            $locale = app()->getLocale() ?? 'az';
            $trainingsQuery = \App\Models\Training::select('id', 'title', 'category')
                ->with([
                    'trainer:id,first_name,last_name'
                ])
                ->orderByRaw("title->>'{$locale}' ASC")
                ->orderByRaw("title->>'az' ASC"); // Fallback to az if locale doesn't exist

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
            'title' => ['required'],
            'description' => ['nullable'],
            'category' => ['required_without:training_id', 'string', 'max:255'], // Required if no training
            'passing_score' => ['required', 'integer', 'min:0', 'max:100'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:480'], // Max 8 hours
            'start_date' => ['nullable', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            
            // New enhanced fields
            'rules' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'hashtags' => ['nullable', 'array'],
            'hashtags.*' => ['string', 'max:50'],
            'time_warning_minutes' => ['nullable', 'integer', 'min:1', 'max:60'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:10'],
                          'randomize_questions' => ['nullable', 'boolean'],
              'randomize_choices' => ['nullable', 'boolean'],
              'shuffle_questions' => ['nullable', 'boolean'], // Alias for randomize_questions
              'shuffle_choices' => ['nullable', 'boolean'], // Alias for randomize_choices
              'show_results_immediately' => ['nullable', 'boolean'],
              'show_result_immediately' => ['nullable', 'boolean'], // Alias for show_results_immediately
              'show_correct_answers' => ['nullable', 'boolean'],
              'show_explanations' => ['nullable', 'boolean'],
              'allow_tab_switching' => ['nullable', 'boolean'],
              'track_tab_changes' => ['nullable', 'boolean'],
              'exam_question_count' => ['nullable', 'integer', 'min:1'],

              // Questions validation
            'questions' => ['required', 'array', 'min:1'], // At least 1 question required
            'questions.*.question_text' => ['required'],
            'questions.*.question_type' => ['required', 'in:single_choice,multiple_choice,true_false,text'],
            'questions.*.difficulty' => ['nullable', 'in:easy,medium,hard'],
            'questions.*.question_media' => ['nullable', 'array'],
            'questions.*.question_media.*.type' => ['required_with:questions.*.question_media', 'in:image,video,audio,document'],
            'questions.*.question_media.*.url' => ['required_with:questions.*.question_media', 'url'],
            'questions.*.question_media.*.title' => ['nullable', 'string'],
            'questions.*.question_media.*.description' => ['nullable', 'string'],
            'questions.*.explanation' => ['nullable'],
            'questions.*.points' => ['nullable', 'integer', 'min:1'],
            'questions.*.is_required' => ['nullable', 'boolean'],
            'questions.*.sequence' => ['nullable', 'integer', 'min:1'],
            'questions.*.metadata' => ['nullable', 'array'],
            
            // Choices validation (required for single_choice, multiple_choice, and true_false)
            'questions.*.choices' => ['required_if:questions.*.question_type,single_choice,multiple_choice,true_false', 'array'],
            'questions.*.choices.*.choice_text' => ['required'],
            'questions.*.choices.*.choice_media' => ['nullable', 'array'],
            'questions.*.choices.*.choice_media.*.type' => ['required_with:questions.*.choices.*.choice_media', 'in:image,video,audio,document'],
            'questions.*.choices.*.choice_media.*.url' => ['required_with:questions.*.choices.*.choice_media', 'url'],
            'questions.*.choices.*.choice_media.*.title' => ['nullable', 'string'],
            'questions.*.choices.*.choice_media.*.description' => ['nullable', 'string'],
            'questions.*.choices.*.is_correct' => ['required'], // Will be normalized to boolean
            'questions.*.choices.*.explanation' => ['nullable'],
            'questions.*.choices.*.points' => ['nullable', 'integer', 'min:0'],
            'questions.*.choices.*.metadata' => ['nullable', 'array'],
                  ]);

          // Normalize field aliases (map frontend field names to backend field names)
          if (isset($validated['shuffle_questions']) && !isset($validated['randomize_questions'])) {
              $validated['randomize_questions'] = $validated['shuffle_questions'];
              unset($validated['shuffle_questions']);
          }
          if (isset($validated['shuffle_choices']) && !isset($validated['randomize_choices'])) {
              $validated['randomize_choices'] = $validated['shuffle_choices'];
              unset($validated['shuffle_choices']);
          }
          if (isset($validated['show_result_immediately']) && !isset($validated['show_results_immediately'])) {
              $validated['show_results_immediately'] = $validated['show_result_immediately'];
              unset($validated['show_result_immediately']);
          }
          
          // Normalize exam-level multilang fields
          if (isset($validated['title'])) {
              $validated['title'] = TranslationHelper::normalizeTranslation($validated['title']);
          }
          if (isset($validated['description'])) {
              $validated['description'] = TranslationHelper::normalizeTranslation($validated['description']);
          }

          // Normalize multilang fields and is_correct values
          foreach ($validated['questions'] as $index => &$question) {
              // Normalize question_text (multilang)
              if (isset($question['question_text'])) {
                  $question['question_text'] = TranslationHelper::normalizeTranslation($question['question_text']);
              }
              
              // Normalize explanation (multilang)
              if (isset($question['explanation'])) {
                  $question['explanation'] = TranslationHelper::normalizeTranslation($question['explanation']);
              }
              
              // Normalize choices
              if (isset($question['choices']) && is_array($question['choices'])) {
                  foreach ($question['choices'] as &$choice) {
                      // Normalize choice_text (multilang)
                      if (isset($choice['choice_text'])) {
                          $choice['choice_text'] = TranslationHelper::normalizeTranslation($choice['choice_text']);
                      }
                      
                      // Normalize choice explanation (multilang)
                      if (isset($choice['explanation'])) {
                          $choice['explanation'] = TranslationHelper::normalizeTranslation($choice['explanation']);
                      }
                      
                      // Normalize is_correct: convert "on" string to boolean true
                      if (isset($choice['is_correct'])) {
                          // Handle "on" string from HTML checkboxes
                          if ($choice['is_correct'] === 'on' || $choice['is_correct'] === '1' || $choice['is_correct'] === 1) {
                              $choice['is_correct'] = true;
                          } elseif ($choice['is_correct'] === '' || $choice['is_correct'] === '0' || $choice['is_correct'] === 0 || $choice['is_correct'] === false) {
                              $choice['is_correct'] = false;
                          }
                          // Ensure it's boolean
                          $choice['is_correct'] = (bool) $choice['is_correct'];
                      }
                  }
                  unset($choice); // Unset reference
              }
              
              // Validate choices for choice-based questions
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
          unset($question); // Unset reference

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
                  if (in_array($questionData['question_type'], ['single_choice', 'multiple_choice', 'true_false']) && !empty($choices)) {
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

        // Ensure all exam configuration fields are included in response
        // These fields are needed for frontend display
        // Also add alias fields for frontend compatibility
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
        
        // Add alias fields for frontend compatibility (shuffle_questions, shuffle_choices, show_result_immediately)
        // These aliases map to the actual database fields
        $exam->shuffle_questions = $exam->randomize_questions ?? false;
        $exam->shuffle_choices = $exam->randomize_choices ?? false;
        $exam->show_result_immediately = $exam->show_results_immediately ?? false;
        
        // Apply exam_question_count limit to questions if set
        if ($exam->exam_question_count && $exam->exam_question_count > 0 && $exam->questions->count() > $exam->exam_question_count) {
            $allQuestions = $exam->questions;
            
            // Apply randomization if enabled
            if ($exam->randomize_questions || $exam->randomize_questions === true) {
                $allQuestions = $allQuestions->shuffle();
            } else {
                $allQuestions = $allQuestions->sortBy('sequence');
            }
            
            // Limit questions
            $limitedQuestions = $allQuestions->take($exam->exam_question_count);
            
            // Reset sequence to start from 1 for limited questions
            $limitedQuestions->each(function ($question, $index) {
                $question->sequence = $index + 1;
            });
            
            // Apply choice randomization if enabled
            if ($exam->randomize_choices || $exam->randomize_choices === true) {
                $limitedQuestions->each(function ($question) {
                    $question->setRelation('choices', $question->choices->shuffle());
                });
            }
            
            // Replace questions relation with limited questions
            $exam->setRelation('questions', $limitedQuestions);
        } else {
            // Apply choice randomization if enabled (even if no limit)
            if ($exam->randomize_choices || $exam->randomize_choices === true) {
                $exam->questions->each(function ($question) {
                    $question->setRelation('choices', $question->choices->shuffle());
                });
            }
        }

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

        // Ensure all exam configuration fields are included in response
        // These fields are needed for frontend display
        // Also add alias fields for frontend compatibility
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
        
        // Add alias fields for frontend compatibility (shuffle_questions, shuffle_choices, show_result_immediately)
        // These aliases map to the actual database fields
        $exam->shuffle_questions = $exam->randomize_questions ?? false;
        $exam->shuffle_choices = $exam->randomize_choices ?? false;
        $exam->show_result_immediately = $exam->show_results_immediately ?? false;

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
            'training_id' => ['sometimes', 'exists:trainings,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'passing_score' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'duration_minutes' => ['sometimes', 'integer', 'min:1', 'max:480'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            
            // New enhanced fields
            'rules' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'hashtags' => ['nullable', 'array'],
            'hashtags.*' => ['string', 'max:50'],
            'time_warning_minutes' => ['nullable', 'integer', 'min:1', 'max:60'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:10'],
            'randomize_questions' => ['nullable', 'boolean'],
            'randomize_choices' => ['nullable', 'boolean'],
            'shuffle_questions' => ['nullable', 'boolean'], // Alias for randomize_questions
            'shuffle_choices' => ['nullable', 'boolean'], // Alias for randomize_choices
            'show_results_immediately' => ['nullable', 'boolean'],
            'show_result_immediately' => ['nullable', 'boolean'], // Alias for show_results_immediately
            'show_correct_answers' => ['nullable', 'boolean'],
            'show_explanations' => ['nullable', 'boolean'],
            'allow_tab_switching' => ['nullable', 'boolean'],
            'track_tab_changes' => ['nullable', 'boolean'],
            'exam_question_count' => ['nullable', 'integer', 'min:1'],
            'auto_submit' => ['nullable', 'boolean'],
        ]);

        // Normalize field aliases (map frontend field names to backend field names)
        if (isset($validated['shuffle_questions']) && !isset($validated['randomize_questions'])) {
            $validated['randomize_questions'] = $validated['shuffle_questions'];
            unset($validated['shuffle_questions']);
        }
        if (isset($validated['shuffle_choices']) && !isset($validated['randomize_choices'])) {
            $validated['randomize_choices'] = $validated['shuffle_choices'];
            unset($validated['shuffle_choices']);
        }
        if (isset($validated['show_result_immediately']) && !isset($validated['show_results_immediately'])) {
            $validated['show_results_immediately'] = $validated['show_result_immediately'];
            unset($validated['show_result_immediately']);
        }

        // Store original values for audit
        $originalValues = $exam->only(array_keys($validated));
        
        $exam->update($validated);
        
        // Load relationships for response
        $exam->load(['training.trainer', 'questions']);

        // Calculate changes safely
        $changes = [];
        foreach ($validated as $key => $value) {
            if (!isset($originalValues[$key]) || $originalValues[$key] != $value) {
                $changes[$key] = $value;
            }
        }

        // Add audit log
        \App\Models\AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'updated',
            'entity' => 'exam',
            'entity_id' => $exam->id,
            'details' => [
                'title' => $exam->title,
                'changes' => $changes,
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
        $user = $request->user();
        
        // Check if user has exceeded max attempts
        if ($exam->max_attempts) {
            $attemptCount = ExamRegistration::where('user_id', $user->id)
                ->where('exam_id', $exam->id)
                ->count();
                
            if ($attemptCount >= $exam->max_attempts) {
                return response()->json([
                    'message' => 'Maximum attempts exceeded',
                    'max_attempts' => $exam->max_attempts,
                    'attempts_used' => $attemptCount
                ], 422);
            }
        }
        
        // Get next attempt number
        $nextAttemptNumber = ExamRegistration::where('user_id', $user->id)
            ->where('exam_id', $exam->id)
            ->max('attempt_number') + 1;
            
        $reg = ExamRegistration::create([
            'user_id' => $user->id,
            'exam_id' => $exam->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'attempt_number' => $nextAttemptNumber,
        ]);
        
        return response()->json($reg);
    }

    /**
     * Start exam attempt - increments attempt count when user starts exam
     * This endpoint should be called by frontend when user opens/enters exam
     * Returns current attempt number (e.g., "2/3")
     */
    public function startAttempt(Exam $exam, Request $request)
    {
        $user = $request->user();
        
        // Check if user has exceeded max attempts
        $existingRegistrations = ExamRegistration::where('user_id', $user->id)
            ->where('exam_id', $exam->id)
            ->get();
            
        $totalAttempts = $existingRegistrations->count();
        
        if ($exam->max_attempts && $totalAttempts >= $exam->max_attempts) {
            return response()->json([
                'message' => 'Maksimum cəhd sayını keçmisiniz',
                'max_attempts' => $exam->max_attempts,
                'attempts_used' => $totalAttempts,
                'current_attempt' => $totalAttempts,
                'attempt_text' => "{$totalAttempts}/{$exam->max_attempts}",
                'can_start' => false
            ], 403);
        }
        
        // Check if there's already an active (in_progress) attempt
        $activeRegistration = $existingRegistrations->where('status', 'in_progress')->first();
        
        if ($activeRegistration) {
            // Use existing active attempt
            $currentAttempt = $activeRegistration->attempt_number;
        } else {
            // Create new attempt
            $nextAttemptNumber = $existingRegistrations->max('attempt_number') ?? 0;
            $nextAttemptNumber += 1;
            
            $registration = ExamRegistration::create([
                'user_id' => $user->id,
                'exam_id' => $exam->id,
                'status' => 'in_progress',
                'started_at' => now(),
                'attempt_number' => $nextAttemptNumber,
            ]);
            
            $currentAttempt = $nextAttemptNumber;
        }
        
        // Calculate attempt text (e.g., "2/3")
        $attemptText = $exam->max_attempts 
            ? "{$currentAttempt}/{$exam->max_attempts}" 
            : "{$currentAttempt}";
        
        return response()->json([
            'message' => 'Exam attempt started',
            'current_attempt' => $currentAttempt,
            'max_attempts' => $exam->max_attempts,
            'attempt_text' => $attemptText,
            'can_start' => true,
            'registration_id' => $activeRegistration->id ?? $registration->id ?? null
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
        $user = request()->user();
        
        // Get all required questions first
        $allQuestions = $exam->questions()
            ->where('is_required', true)
            ->get();

        // Apply exam_question_count limit if set
        // If exam_question_count is set and less than total questions, randomly select that many
        if ($exam->exam_question_count && $exam->exam_question_count > 0 && $allQuestions->count() > $exam->exam_question_count) {
            // If randomize_questions is true, use random selection
            // Otherwise, take first N questions by sequence
            if ($exam->randomize_questions || $exam->randomize_questions === true) {
                $allQuestions = $allQuestions->shuffle();
            } else {
                $allQuestions = $allQuestions->sortBy('sequence');
            }
            
            // Take only the specified number of questions
            $allQuestions = $allQuestions->take($exam->exam_question_count);
            
            // Reset sequence to start from 1 for limited questions
            $allQuestions->each(function ($question, $index) {
                $question->sequence = $index + 1;
            });
        } else {
            // If no limit or limit >= total, use all questions
            // Apply sorting based on randomize_questions
            if ($exam->randomize_questions || $exam->randomize_questions === true) {
                $allQuestions = $allQuestions->shuffle();
                // Reset sequence after shuffle
                $allQuestions->each(function ($question, $index) {
                    $question->sequence = $index + 1;
                });
            } else {
                $allQuestions = $allQuestions->sortBy('sequence');
            }
        }

        // Get or create user's active registration
        $registration = ExamRegistration::where('user_id', $user->id)
            ->where('exam_id', $exam->id)
            ->where('status', 'in_progress')
            ->latest()
            ->first();

        // If no active registration, create one
        if (!$registration) {
            $existingRegistrations = ExamRegistration::where('user_id', $user->id)
                ->where('exam_id', $exam->id)
                ->get();
            
            $attemptNumber = $existingRegistrations->count() + 1;
            $registration = ExamRegistration::create([
                'user_id' => $user->id,
                'exam_id' => $exam->id,
                'status' => 'in_progress',
                'started_at' => now(),
                'attempt_number' => $attemptNumber
            ]);
        }

        // Save selected question IDs to registration for later reference
        $selectedQuestionIds = $allQuestions->pluck('id')->toArray();
        $registration->update([
            'selected_question_ids' => $selectedQuestionIds,
            'total_questions' => count($selectedQuestionIds)
        ]);

        // Load choices for each question and apply randomization if needed
        $questions = $allQuestions->map(function ($question) use ($exam) {
            // Load choices
            $choicesQuery = $question->choices();
            
            // Apply choice randomization if enabled
            if ($exam->randomize_choices || $exam->randomize_choices === true) {
                $choices = $question->choices()->inRandomOrder()->get();
            } else {
                $choices = $question->choices()->orderBy('id')->get();
            }
            
            // Get question data without correct answers
            $questionData = $question->getForExam();
            
            // Replace choices with randomized/shuffled ones
            $questionData['choices'] = $choices->map(function ($choice) {
                return [
                    'id' => $choice->id,
                    'choice_text' => $choice->choice_text,
                    'choice_media' => $choice->choice_media,
                    'explanation' => null, // Don't show explanation before submission
                    'points' => $choice->points,
                    'metadata' => $choice->metadata,
                    // Don't include is_correct - it will be revealed after submission
                ];
            })->values();
            
            return $questionData;
        })->values();

                // Calculate timing info from registration
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
                'exam_question_count' => $exam->exam_question_count,
                'randomize_questions' => $exam->randomize_questions,
                'randomize_choices' => $exam->randomize_choices,
                'shuffle_questions' => $exam->randomize_questions ?? false,
                'shuffle_choices' => $exam->randomize_choices ?? false,
                'show_results_immediately' => $exam->show_results_immediately,
                'show_result_immediately' => $exam->show_results_immediately ?? false,
                'show_correct_answers' => $exam->show_correct_answers,
                'show_explanations' => $exam->show_explanations,
                'allow_tab_switching' => $exam->allow_tab_switching,
                'track_tab_changes' => $exam->track_tab_changes,
                'auto_submit' => $exam->auto_submit ?? false,
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

        // Check if user has completed the related training (only if training requires completion before exam)
        if ($exam->training_id) {
            $training = \App\Models\Training::find($exam->training_id);
            
            if ($training) {
                // Skip training completion check for video trainings
                // Video trainings don't require registration and users can take exam anytime
                if ($training->type === 'video') {
                    // Video trainings allow exam without completion requirement
                    // Skip this check
                } else {
                    // For non-video trainings, check if training requires completion before exam
                    $requiresCompletion = $training->exam_required ?? false;
                    
                    if ($requiresCompletion) {
                        // Check TrainingRegistration status for non-video trainings
                        $trainingCompletion = \App\Models\TrainingRegistration::where('user_id', $request->user()->id)
                            ->where('training_id', $exam->training_id)
                            ->where('status', 'completed')
                            ->first();
                        
                        if (!$trainingCompletion) {
                            return response()->json([
                                'message' => 'Bu imtahanı vermək üçün əvvəlcə əlaqəli təlimi tamamlamalısınız',
                                'training_id' => $exam->training_id,
                                'training_title' => $training->title ?? 'Unknown Training',
                                'training_type' => $training->type ?? 'unknown'
                            ], 403);
                        }
                    }
                }
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

        // Get active attempt (should exist from start/getExamForTaking)
        $activeRegistration = $existingRegistrations->where('status', 'in_progress')->first();
        
        if (!$activeRegistration) {
            // If no active registration, use firstOrCreate to avoid unique constraint violation
            // Check if there's any existing registration (even if not in_progress)
            $existingRegistration = ExamRegistration::where('user_id', $request->user()->id)
                ->where('exam_id', $exam->id)
                ->first();
            
            if ($existingRegistration) {
                // Use existing registration and update it
                $registration = $existingRegistration;
                // Update status to in_progress if it's not already
                if ($registration->status !== 'in_progress') {
                    $registration->update([
                        'status' => 'in_progress',
                        'started_at' => $registration->started_at ?? now(),
                    ]);
                }
            } else {
                // Create new registration only if none exists
                $attemptNumber = 1;
                $registration = ExamRegistration::create([
                    'user_id' => $request->user()->id,
                    'exam_id' => $exam->id,
                    'status' => 'in_progress',
                    'started_at' => now(),
                    'attempt_number' => $attemptNumber
                ]);
            }
        } else {
            $registration = $activeRegistration;
        }
        
        // Force update the updated_at timestamp
        $registration->touch();

        // Check if time limit exceeded (for new attempts, this will be false initially)
        $timeElapsed = 0; // New attempt, no time elapsed yet
        $timeLimit = $exam->duration_minutes;
        $timeExceeded = false; // Will be checked during submission

        try {
            DB::transaction(function () use ($registration, $data, $exam, $request, $timeExceeded) {
                $totalPoints = 0;
                $earnedPoints = 0;
                $hasTextQuestions = false;
                $textQuestionsCount = 0;
                
                // Get selected question IDs from registration if available (for exam_question_count)
                $selectedQuestionIds = $registration->selected_question_ids ?? null;
                
                // Load all questions with choices in a single query to avoid N+1 problem
                $questionIds = collect($data['answers'])->pluck('question_id');
                
                // If selected_question_ids exist, only validate answers for those questions
                if ($selectedQuestionIds && is_array($selectedQuestionIds)) {
                    // Filter questionIds to only include selected ones
                    $questionIds = $questionIds->filter(function ($id) use ($selectedQuestionIds) {
                        return in_array($id, $selectedQuestionIds);
                    });
                }
                
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
                        
                        // Check if this is a text question
                        if ($question->question_type === 'text') {
                            $hasTextQuestions = true;
                            $textQuestionsCount++;
                        }
                        
                        // Calculate points with error handling
                        try {
                            $questionPoints = $question->calculatePoints($ans);
                            
                            // If questionPoints is null, it means manual grading needed
                            if ($questionPoints === null) {
                                // Text question with answer - will be graded manually
                                $earnedPoints += 0; // Don't add points yet
                            } else {
                                $earnedPoints += $questionPoints;
                            }
                        } catch (\Exception $e) {
                            \Log::error('Error calculating points for question ' . $question->id . ': ' . $e->getMessage());
                            // Skip points calculation if there's an error
                        }
                    }

                    // Store user answer with simplified approach
                    try {
                        // Calculate if answer is correct
                        $isCorrect = false;
                        if ($question->question_type === 'text') {
                            // Text questions need manual grading, is_correct will be set by admin
                            $isCorrect = false; // Will be updated by admin
                        } else {
                            // Use isAnswerCorrect method to check if answer is correct
                            $isCorrect = $question->isAnswerCorrect($ans);
                        }
                        
                        ExamUserAnswer::updateOrCreate([
                            'registration_id' => $registration->id,
                            'question_id' => $question->id,
                        ], [
                            'choice_id' => $ans['choice_id'] ?? null,
                            'choice_ids' => $ans['choice_ids'] ?? null,
                            'answer_text' => $ans['answer_text'] ?? null,
                            'is_correct' => $isCorrect,
                            'answered_at' => now(),
                            'needs_manual_grading' => $question->question_type === 'text' && !empty(trim($ans['answer_text'] ?? '')),
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Error saving user answer for question ' . $question->id . ': ' . $e->getMessage());
                        throw $e; // Re-throw to fail the transaction
                    }
                }

                // Calculate score based on whether there are text questions
                if ($hasTextQuestions) {
                    // If there are text questions, calculate partial score from auto-graded questions only
                    $autoGradedPoints = $totalPoints - ($textQuestionsCount * 1); // Assuming each text question is worth 1 point for now
                    $score = $autoGradedPoints > 0 ? (int) floor(($earnedPoints / $autoGradedPoints) * 100) : 0;
                    $passed = false; // Will be determined after manual grading
                } else {
                    // All questions are auto-graded
                    $score = $totalPoints > 0 ? (int) floor(($earnedPoints / $totalPoints) * 100) : 0;
                    $passed = $score >= (int) $exam->passing_score;
                }

            // Determine final status
            if ($timeExceeded) {
                $finalStatus = 'timeout';
            } elseif ($hasTextQuestions) {
                $finalStatus = 'pending_review'; // Needs manual grading
            } else {
                $finalStatus = $passed ? 'passed' : 'failed';
            }

            // Calculate total questions count
            $totalQuestionsCount = $registration->total_questions;
            if (!$totalQuestionsCount || $totalQuestionsCount === 0) {
                // If not set, calculate from selected_question_ids or all questions
                if ($registration->selected_question_ids && is_array($registration->selected_question_ids)) {
                    $totalQuestionsCount = count($registration->selected_question_ids);
                } else {
                    // Count unique questions answered
                    $answeredQuestionIds = collect($data['answers'])->pluck('question_id')->unique();
                    $totalQuestionsCount = $answeredQuestionIds->count();
                }
            }

            $registration->update([
                'status' => $finalStatus,
                'score' => $score,
                'finished_at' => now(),
                'needs_manual_grading' => $hasTextQuestions,
                'auto_graded_score' => $hasTextQuestions ? $score : null,
                'total_questions' => $totalQuestionsCount,
            ]);

            if ($passed && !$timeExceeded && !$hasTextQuestions) {
                // Check if training provides certificates
                $training = \App\Models\Training::find($exam->training_id);
                if ($training && $training->has_certificate) {
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
     * Get user's exam result/history for a specific exam
     */
    public function getUserExamResult(Exam $exam, Request $request)
    {
        $userId = $request->user()->id;
        
        // Get all user's attempts for this exam
        $attempts = ExamRegistration::where('user_id', $userId)
            ->where('exam_id', $exam->id)
            ->with('certificate')
            ->orderBy('created_at', 'desc')
            ->get();
        
        if ($attempts->isEmpty()) {
            return response()->json([
                'message' => 'Bu imtahan üçün heç bir cəhdiniz yoxdur',
                'exam' => [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'passing_score' => $exam->passing_score,
                    'max_attempts' => $exam->max_attempts,
                ],
                'has_attempts' => false,
                'attempts' => [],
            ]);
        }
        
        // Get best attempt
        $bestAttempt = $attempts->where('status', 'passed')->sortByDesc('score')->first();
        if (!$bestAttempt) {
            $bestAttempt = $attempts->sortByDesc('score')->first();
        }
        
        // Format attempts with questions and answers if allowed
        $formattedAttempts = $attempts->map(function ($attempt) use ($exam) {
            $certificate = null;
            if ($attempt->certificate) {
            $certificate = [
                'id' => $attempt->certificate->id,
                'issue_date' => $attempt->certificate->issue_date->format('d.m.Y'),
                'status' => $attempt->certificate->status,
                'is_active' => $attempt->certificate->isActive(),
                'pdf_url' => $attempt->certificate->pdf_url,
                // Frontend üçün sadə məlumatlar
                'user_name' => $attempt->certificate->user->first_name . ' ' . $attempt->certificate->user->last_name,
                'training_title' => $attempt->certificate->training ? $attempt->certificate->training->title : null,
                'training_category' => $attempt->certificate->training ? $attempt->certificate->training->category : null,
                'exam_title' => $attempt->certificate->exam ? $attempt->certificate->exam->title : null,
                'exam_score' => $attempt->score,
                'passing_score' => $attempt->certificate->exam ? $attempt->certificate->exam->passing_score : null,
            ];
            }
            
            $attemptData = [
                'id' => $attempt->id,
                'score' => $attempt->score,
                'status' => $attempt->status,
                'attempt_number' => $attempt->attempt_number,
                'started_at' => $attempt->started_at,
                'finished_at' => $attempt->finished_at,
                'time_spent_minutes' => $attempt->time_spent_minutes,
                'needs_manual_grading' => $attempt->needs_manual_grading,
                'certificate' => $certificate,
                'created_at' => $attempt->created_at,
            ];
            
            // Add questions with answers if exam settings allow it
            if ($exam->show_correct_answers || $exam->show_explanations) {
                // Get selected question IDs for this attempt
                $selectedQuestionIds = $attempt->selected_question_ids ?? [];
                
                // If no selected questions, get all questions from exam
                if (empty($selectedQuestionIds)) {
                    $questions = ExamQuestion::where('exam_id', $exam->id)
                        ->where('is_required', true)
                        ->with('choices')
                        ->orderBy('sequence')
                        ->get();
                } else {
                    $questions = ExamQuestion::where('exam_id', $exam->id)
                        ->whereIn('id', $selectedQuestionIds)
                        ->with('choices')
                        ->orderBy('sequence')
                        ->get();
                }
                
                // Get user answers for this attempt
                $userAnswers = ExamUserAnswer::where('registration_id', $attempt->id)
                    ->with(['question', 'choice'])
                    ->get()
                    ->keyBy('question_id');
                
                // Format questions with answers
                $attemptData['questions'] = $questions->map(function ($question) use ($userAnswers, $exam) {
                    $userAnswer = $userAnswers->get($question->id);
                    
                    $questionData = [
                        'id' => $question->id,
                        'question_text' => $question->question_text,
                        'question_type' => $question->question_type,
                        'question_media' => $question->question_media,
                        'points' => $question->points,
                        'sequence' => $question->sequence,
                        'is_required' => $question->is_required,
                    ];
                    
                    // Add explanation if allowed
                    if ($exam->show_explanations) {
                        $questionData['explanation'] = $question->explanation;
                    }
                    
                    // Process choices
                    if ($question->choices->isNotEmpty()) {
                        $questionData['choices'] = $question->choices->map(function ($choice) use ($userAnswer, $exam) {
                            $choiceData = [
                                'id' => $choice->id,
                                'choice_text' => $choice->choice_text,
                                'choice_media' => $choice->choice_media,
                                'points' => $choice->points,
                                'sequence' => $choice->sequence,
                            ];
                            
                            // Show correct answer if allowed
                            if ($exam->show_correct_answers) {
                                $choiceData['is_correct'] = $choice->is_correct;
                            }
                            
                            // Show explanation if allowed
                            if ($exam->show_explanations && $choice->explanation) {
                                $choiceData['explanation'] = $choice->explanation;
                            }
                            
                            return $choiceData;
                        });
                    }
                    
                    // Add user answer
                    if ($userAnswer) {
                        $questionData['user_answer'] = [
                            'choice_id' => $userAnswer->choice_id,
                            'choice_ids' => $userAnswer->choice_ids,
                            'answer_text' => $userAnswer->answer_text,
                            'is_correct' => $exam->show_correct_answers ? $userAnswer->is_correct : null,
                            'admin_feedback' => $userAnswer->admin_feedback,
                            'graded_at' => $userAnswer->graded_at,
                        ];
                    } else {
                        $questionData['user_answer'] = null;
                    }
                    
                    return $questionData;
                });
            }
            
            return $attemptData;
        });
        
        // Calculate statistics
        $passedCount = $attempts->where('status', 'passed')->count();
        $failedCount = $attempts->where('status', 'failed')->count();
        $pendingCount = $attempts->where('status', 'pending_review')->count();
        $averageScore = $attempts->avg('score');
        
        return response()->json([
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'description' => $exam->description,
                'passing_score' => $exam->passing_score,
                'max_attempts' => $exam->max_attempts,
                'duration_minutes' => $exam->duration_minutes,
            ],
            'has_attempts' => true,
            'total_attempts' => $attempts->count(),
            'remaining_attempts' => $exam->max_attempts ? max(0, $exam->max_attempts - $attempts->count()) : null,
            'statistics' => [
                'passed_count' => $passedCount,
                'failed_count' => $failedCount,
                'pending_count' => $pendingCount,
                'average_score' => round($averageScore, 2),
                'best_score' => $bestAttempt ? $bestAttempt->score : null,
            ],
            'best_attempt' => $bestAttempt ? $this->formatAttemptWithQuestions($bestAttempt, $exam) : null,
            'attempts' => $formattedAttempts,
        ]);
    }

    /**
     * Format attempt with questions and answers for result display
     */
    private function formatAttemptWithQuestions($attempt, $exam)
    {
        $certificate = null;
        if ($attempt->certificate) {
            $certificate = [
                'id' => $attempt->certificate->id,
                'issue_date' => $attempt->certificate->issue_date->format('d.m.Y'),
                'status' => $attempt->certificate->status,
                'is_active' => $attempt->certificate->isActive(),
                'pdf_url' => $attempt->certificate->pdf_url,
                'user_name' => $attempt->certificate->user->first_name . ' ' . $attempt->certificate->user->last_name,
                'training_title' => $attempt->certificate->training ? $attempt->certificate->training->title : null,
                'training_category' => $attempt->certificate->training ? $attempt->certificate->training->category : null,
                'exam_title' => $attempt->certificate->exam ? $attempt->certificate->exam->title : null,
                'exam_score' => $attempt->score,
                'passing_score' => $attempt->certificate->exam ? $attempt->certificate->exam->passing_score : null,
            ];
        }
        
        $attemptData = [
            'id' => $attempt->id,
            'score' => $attempt->score,
            'status' => $attempt->status,
            'attempt_number' => $attempt->attempt_number,
            'finished_at' => $attempt->finished_at,
            'certificate' => $certificate,
        ];
        
        // Add questions with answers if exam settings allow it
        if ($exam->show_correct_answers || $exam->show_explanations) {
            // Get selected question IDs for this attempt
            $selectedQuestionIds = $attempt->selected_question_ids ?? [];
            
            // If no selected questions, get all questions from exam
            if (empty($selectedQuestionIds)) {
                $questions = ExamQuestion::where('exam_id', $exam->id)
                    ->where('is_required', true)
                    ->with('choices')
                    ->orderBy('sequence')
                    ->get();
            } else {
                $questions = ExamQuestion::where('exam_id', $exam->id)
                    ->whereIn('id', $selectedQuestionIds)
                    ->with('choices')
                    ->orderBy('sequence')
                    ->get();
            }
            
            // Get user answers for this attempt
            $userAnswers = ExamUserAnswer::where('registration_id', $attempt->id)
                ->with(['question', 'choice'])
                ->get()
                ->keyBy('question_id');
            
            // Format questions with answers
            $attemptData['questions'] = $questions->map(function ($question) use ($userAnswers, $exam) {
                $userAnswer = $userAnswers->get($question->id);
                
                $questionData = [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'question_type' => $question->question_type,
                    'question_media' => $question->question_media,
                    'points' => $question->points,
                    'sequence' => $question->sequence,
                    'is_required' => $question->is_required,
                ];
                
                // Add explanation if allowed
                if ($exam->show_explanations) {
                    $questionData['explanation'] = $question->explanation;
                }
                
                // Process choices
                if ($question->choices->isNotEmpty()) {
                    $questionData['choices'] = $question->choices->map(function ($choice) use ($userAnswer, $exam) {
                        $choiceData = [
                            'id' => $choice->id,
                            'choice_text' => $choice->choice_text,
                            'choice_media' => $choice->choice_media,
                            'points' => $choice->points,
                            'sequence' => $choice->sequence,
                        ];
                        
                        // Show correct answer if allowed
                        if ($exam->show_correct_answers) {
                            $choiceData['is_correct'] = $choice->is_correct;
                        }
                        
                        // Show explanation if allowed
                        if ($exam->show_explanations && $choice->explanation) {
                            $choiceData['explanation'] = $choice->explanation;
                        }
                        
                        return $choiceData;
                    });
                }
                
                // Add user answer
                if ($userAnswer) {
                    // Calculate is_correct real-time if not set (for old attempts)
                    $isCorrect = $userAnswer->is_correct;
                    if ($isCorrect === null || ($question->question_type !== 'text' && !$userAnswer->graded_at)) {
                        // Recalculate for non-text questions if not already graded
                        $answerData = [
                            'choice_id' => $userAnswer->choice_id,
                            'choice_ids' => $userAnswer->choice_ids,
                            'answer_text' => $userAnswer->answer_text,
                        ];
                        $isCorrect = $question->isAnswerCorrect($answerData);
                    }
                    
                    $questionData['user_answer'] = [
                        'choice_id' => $userAnswer->choice_id,
                        'choice_ids' => $userAnswer->choice_ids,
                        'answer_text' => $userAnswer->answer_text,
                        'is_correct' => $exam->show_correct_answers ? $isCorrect : null,
                        'admin_feedback' => $userAnswer->admin_feedback,
                        'graded_at' => $userAnswer->graded_at,
                    ];
                } else {
                    $questionData['user_answer'] = null;
                }
                
                return $questionData;
            });
        }
        
        return $attemptData;
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
     * Get exams dropdown list for training form
     * Returns simple list of exams suitable for dropdown/select components
     */
    public function dropdown(Request $request)
    {
        $user = $request->user();
        $locale = app()->getLocale();
        
        $query = Exam::with(['training'])
            ->select('id', 'title', 'training_id', 'category', 'passing_score', 'duration_minutes');

        // If user is trainer, only show exams for their trainings
        if ($user && $user->user_type === 'trainer') {
            $query->whereHas('training', function ($q) use ($user) {
                $q->where('trainer_id', $user->id);
            });
        }

        // Get all exams (not paginated for dropdown)
        $exams = $query->orderByRaw("title->>'{$locale}' ASC")
            ->orderByRaw("title->>'az' ASC")
            ->get();

        // Transform for dropdown format
        $exams = $exams->map(function ($exam) use ($locale) {
            $title = is_array($exam->title) 
                ? ($exam->title[$locale] ?? $exam->title['az'] ?? 'Untitled Exam')
                : $exam->title;
            
            $trainingTitle = null;
            if ($exam->training) {
                $trainingTitle = is_array($exam->training->title)
                    ? ($exam->training->title[$locale] ?? $exam->training->title['az'] ?? 'Untitled Training')
                    : $exam->training->title;
            }

            return [
                'id' => $exam->id,
                'title' => $title,
                'training_id' => $exam->training_id,
                'training_title' => $trainingTitle,
                'category' => $exam->category,
                'passing_score' => $exam->passing_score,
                'duration_minutes' => $exam->duration_minutes,
                'display_text' => $trainingTitle 
                    ? "{$title} ({$trainingTitle})" 
                    : $title,
            ];
        });

        return response()->json($exams);
    }
}


