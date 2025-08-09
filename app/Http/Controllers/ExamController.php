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

    // Submit answers and score exam
    public function submit(Exam $exam, Request $request)
    {
        $data = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*.question_id' => ['required', 'exists:exam_questions,id'],
            'answers.*.choice_id' => ['nullable', 'exists:exam_choices,id'],
            'answers.*.answer_text' => ['nullable', 'string'],
        ]);

        $registration = ExamRegistration::where('user_id', $request->user()->id)
            ->where('exam_id', $exam->id)
            ->firstOrFail();

        DB::transaction(function () use ($registration, $data, $exam, $request) {
            $correctCount = 0;
            $totalCount = ExamQuestion::where('exam_id', $exam->id)->count();

            foreach ($data['answers'] as $ans) {
                $question = ExamQuestion::where('exam_id', $exam->id)
                    ->where('id', $ans['question_id'])->firstOrFail();

                $isCorrect = false;
                if ($question->question_type === 'single_choice' && !empty($ans['choice_id'])) {
                    $isCorrect = ExamChoice::where('id', $ans['choice_id'])
                        ->where('question_id', $question->id)
                        ->where('is_correct', true)
                        ->exists();
                } elseif ($question->question_type === 'text') {
                    // naive text grading: require non-empty
                    $isCorrect = !empty($ans['answer_text']);
                }

                ExamUserAnswer::updateOrCreate([
                    'registration_id' => $registration->id,
                    'question_id' => $question->id,
                ], [
                    'choice_id' => $ans['choice_id'] ?? null,
                    'answer_text' => $ans['answer_text'] ?? null,
                    'is_correct' => $isCorrect,
                    'answered_at' => now(),
                ]);

                if ($isCorrect) {
                    $correctCount++;
                }
            }

            $score = $totalCount > 0 ? (int) floor(($correctCount / $totalCount) * 100) : 0;
            $passed = $score >= (int) $exam->passing_score;

            $registration->update([
                'status' => $passed ? 'passed' : 'failed',
                'score' => $score,
                'finished_at' => now(),
            ]);

            if ($passed) {
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

        return response()->json(ExamRegistration::find($registration->id)->load('certificate'));
    }
}


