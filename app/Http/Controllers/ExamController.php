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
    
    public function index()
    {
        return Exam::with('questions.choices')->paginate(15);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'training_id' => ['required', 'exists:trainings,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'passing_score' => ['required', 'integer', 'min:0'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
        $exam = Exam::create($validated);
        return response()->json($exam, 201);
    }

    public function show(Exam $exam)
    {
        return $exam->load('questions.choices');
    }

    public function update(Request $request, Exam $exam)
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'passing_score' => ['sometimes', 'integer', 'min:0'],
            'duration_minutes' => ['sometimes', 'integer', 'min:1'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
        $exam->update($validated);
        return response()->json($exam);
    }

    public function destroy(Exam $exam)
    {
        $exam->delete();
        return response()->json(['message' => 'Deleted']);
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

    // Add question to exam (Admin/Trainer only)
    public function addQuestion(Exam $exam, Request $request)
    {
        $validated = $request->validate([
            'question_text' => ['required', 'string'],
            'question_type' => ['required', 'in:single_choice,multiple_choice,text'],
            'question_media' => ['nullable', 'array'],
            'question_media.*.type' => ['required_with:question_media', 'in:image,video,audio,document'],
            'question_media.*.url' => ['required_with:question_media', 'url'],
            'question_media.*.title' => ['nullable', 'string'],
            'question_media.*.description' => ['nullable', 'string'],
            'explanation' => ['nullable', 'string'],
            'points' => ['nullable', 'integer', 'min:1'],
            'is_required' => ['nullable', 'boolean'],
            'sequence' => ['nullable', 'integer', 'min:1'],
            'choices' => ['required_if:question_type,single_choice,multiple_choice', 'array'],
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

        $question = ExamQuestion::create([
            'exam_id' => $exam->id,
            'question_text' => $validated['question_text'],
            'question_media' => $validated['question_media'] ?? null,
            'explanation' => $validated['explanation'] ?? null,
            'question_type' => $validated['question_type'],
            'points' => $validated['points'] ?? 1,
            'is_required' => $validated['is_required'] ?? true,
            'sequence' => $validated['sequence'] ?? ExamQuestion::where('exam_id', $exam->id)->max('sequence') + 1,
            'metadata' => $validated['metadata'] ?? null,
        ]);

        // Add choices if question type requires them
        if (in_array($validated['question_type'], ['single_choice', 'multiple_choice']) && isset($validated['choices'])) {
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

        return response()->json($question->load('choices'), 201);
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
        $data = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*.question_id' => ['required', 'exists:exam_questions,id'],
            'answers.*.choice_id' => ['nullable', 'exists:exam_choices,id'],
            'answers.*.choice_ids' => ['nullable', 'array'],
            'answers.*.choice_ids.*' => ['integer', 'exists:exam_choices,id'],
            'answers.*.answer_text' => ['nullable', 'string'],
        ]);

        $registration = ExamRegistration::where('user_id', $request->user()->id)
            ->where('exam_id', $exam->id)
            ->firstOrFail();

        // Check if time limit exceeded
        $timeElapsed = $registration->started_at->diffInMinutes(now());
        $timeLimit = $exam->duration_minutes;
        $timeExceeded = $timeElapsed > $timeLimit;

        DB::transaction(function () use ($registration, $data, $exam, $request, $timeExceeded) {
            $totalPoints = 0;
            $earnedPoints = 0;
            $totalQuestions = ExamQuestion::where('exam_id', $exam->id)->where('is_required', true)->count();

            foreach ($data['answers'] as $ans) {
                $question = ExamQuestion::where('exam_id', $exam->id)
                    ->where('id', $ans['question_id'])->firstOrFail();

                if ($question->is_required) {
                    $totalPoints += $question->points;
                    $earnedPoints += $question->calculatePoints($ans);
                }

                // Store user answer
                ExamUserAnswer::updateOrCreate([
                    'registration_id' => $registration->id,
                    'question_id' => $question->id,
                ], [
                    'choice_id' => $ans['choice_id'] ?? null,
                    'choice_ids' => $ans['choice_ids'] ?? null,
                    'answer_text' => $ans['answer_text'] ?? null,
                    'answered_at' => now(),
                ]);
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


