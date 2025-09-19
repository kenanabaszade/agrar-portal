<?php
  
namespace App\Http\Controllers;

use App\Models\ForumQuestion;
use App\Models\ForumAnswer;
use Illuminate\Http\Request;

class ForumController extends Controller
{
    
    public function listQuestions(Request $request)
    {
        $query = ForumQuestion::with('user');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%")
                  ->orWhere('body', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->get('category'));
        }

        if ($request->filled('question_type')) {
            $query->where('question_type', $request->get('question_type'));
        }

        if ($request->boolean('is_pinned')) {
            $query->where('is_pinned', true);
        }

        if ($tags = $request->get('tags')) {
            // expects comma-separated list
            $tagsArray = is_array($tags) ? $tags : explode(',', $tags);
            $query->whereJsonContains('tags', $tagsArray[0]);
        }

        $query->latest();

        return $query->paginate($request->integer('per_page', 20));
    }

    public function postQuestion(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:300'],
            'body' => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:120'],
            'difficulty' => ['nullable', 'in:beginner,intermediate,advanced'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:30'],
            'question_type' => ['required', 'in:general,technical,discussion,poll'],
            'poll_options' => ['nullable', 'array'],
            'poll_options.*' => ['string', 'max:120'],
            'is_pinned' => ['boolean'],
            'allow_comments' => ['boolean'],
            'is_open' => ['boolean'],
        ]);

        $question = ForumQuestion::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'summary' => $validated['summary'] ?? null,
            'body' => $validated['body'],
            'status' => 'open',
            'category' => $validated['category'] ?? null,
            'difficulty' => $validated['difficulty'] ?? null,
            'tags' => $validated['tags'] ?? [],
            'question_type' => $validated['question_type'],
            'poll_options' => $validated['poll_options'] ?? null,
            'is_pinned' => $validated['is_pinned'] ?? false,
            'allow_comments' => $validated['allow_comments'] ?? true,
            'is_open' => $validated['is_open'] ?? true,
        ]);

        return response()->json($question->load('user'), 201);
    }

    public function showQuestion(ForumQuestion $question)
    {
        return $question->load(['user', 'answers.user']);
    }

    public function answerQuestion(Request $request, ForumQuestion $question)
    {
        $validated = $request->validate([
            'body' => ['required', 'string'],
            'is_helpful' => ['nullable', 'boolean'],
        ]);
        $answer = ForumAnswer::create([
            'question_id' => $question->id,
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
            'is_accepted' => false,
        ]);
        return response()->json($answer, 201);
    }

    public function getAnswers(ForumQuestion $question)
    {
        return $question->answers()->with('user')->latest()->paginate(20);
    }

    public function updateQuestion(Request $request, ForumQuestion $question)
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:300'],
            'body' => ['sometimes', 'string'],
            'category' => ['nullable', 'string', 'max:120'],
            'difficulty' => ['nullable', 'in:beginner,intermediate,advanced'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:30'],
            'question_type' => ['sometimes', 'in:general,technical,discussion,poll'],
            'poll_options' => ['nullable', 'array'],
            'poll_options.*' => ['string', 'max:120'],
            'is_pinned' => ['boolean'],
            'allow_comments' => ['boolean'],
            'is_open' => ['boolean'],
            'status' => ['nullable', 'in:open,closed'],
        ]);

        $question->update($validated);
        return response()->json($question->fresh()->load('user'));
    }

    public function destroyQuestion(ForumQuestion $question)
    {
        $question->delete();
        return response()->json(['message' => 'Question deleted']);
    }

    // User-side endpoints
    public function myQuestions(Request $request)
    {
        return ForumQuestion::where('user_id', $request->user()->id)
            ->latest()
            ->paginate($request->integer('per_page', 20));
    }

    public function createMyQuestion(Request $request)
    {
        // Use same validation as admin create
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:300'],
            'body' => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:120'],
            'difficulty' => ['nullable', 'in:beginner,intermediate,advanced'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:30'],
            'question_type' => ['required', 'in:general,technical,discussion,poll'],
            'poll_options' => ['nullable', 'array'],
            'poll_options.*' => ['string', 'max:120'],
        ]);

        $question = ForumQuestion::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'summary' => $validated['summary'] ?? null,
            'body' => $validated['body'],
            'status' => 'open',
            'category' => $validated['category'] ?? null,
            'difficulty' => $validated['difficulty'] ?? null,
            'tags' => $validated['tags'] ?? [],
            'question_type' => $validated['question_type'],
            'poll_options' => $validated['poll_options'] ?? null,
            'is_pinned' => false,
            'allow_comments' => true,
            'is_open' => true,
        ]);

        return response()->json($question->load('user'), 201);
    }

    // NOTE: Admin yönümlü CRUD mövcud olduğundan, istifadəçi tərəfində update/delete dəstəyi bu mərhələdə çıxarıldı.
}


