<?php
  
namespace App\Http\Controllers;

use App\Models\ForumQuestion;
use App\Models\ForumAnswer;
use App\Models\ForumPollVote;
use Illuminate\Http\Request;

class ForumController extends Controller
{
    
    public function listQuestions(Request $request)
    {
        $query = ForumQuestion::with('user:id,first_name,last_name,username,profile_photo,user_type')
            ->when(!$request->user() || !$request->user()->hasRole(['admin','trainer']), function ($q) {
                $q->where('is_public', true);
            });

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
            'is_public' => ['boolean'],
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
            'is_public' => $validated['is_public'] ?? true,
        ]);

        return response()->json($question->load('user:id,first_name,last_name,username,profile_photo,user_type'), 201);
    }

    public function showQuestion(ForumQuestion $question)
    {
        // increment views if question is visible for current user
        $canView = $question->is_public || ($requestUser = request()->user()) && $requestUser->hasRole(['admin','trainer']);
        if ($canView) {
            $question->increment('views');
        }
        return $question->load([
            'user:id,first_name,last_name,username,profile_photo,user_type',
            'answers.user:id,first_name,last_name,username,profile_photo,user_type'
        ]);
    }

    public function answerQuestion(Request $request, ForumQuestion $question)
    {
        if (!$question->allow_comments || !$question->is_open || $question->status === 'closed') {
            return response()->json(['message' => 'Comments are disabled for this question'], 400);
        }
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
        return response()->json($answer->load('user:id,first_name,last_name,username,profile_photo,user_type'), 201);
    }

    public function getAnswers(ForumQuestion $question)
    {
        return $question->answers()
            ->with('user:id,first_name,last_name,username,profile_photo,user_type')
            ->latest()
            ->paginate(20);
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
            'is_public' => ['boolean'],
        ]);

        $question->update($validated);
        return response()->json($question->fresh()->load('user:id,first_name,last_name,username,profile_photo,user_type'));
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
            'is_public' => ['boolean'],
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
            'is_public' => $validated['is_public'] ?? true,
        ]);

        return response()->json($question->load('user:id,first_name,last_name,username,profile_photo,user_type'), 201);
    }

    public function updateMyQuestion(Request $request, ForumQuestion $question)
    {
        // Check ownership
        if ($question->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized - You can only edit your own questions'], 403);
        }

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
        ]);

        $question->update($validated);
        return response()->json($question->fresh()->load('user:id,first_name,last_name,username,profile_photo,user_type'));
    }

    public function destroyMyQuestion(Request $request, ForumQuestion $question)
    {
        // Check ownership
        if ($question->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized - You can only delete your own questions'], 403);
        }

        $question->delete();
        return response()->json(['message' => 'Question deleted successfully']);
    }

    public function vote(Request $request, ForumQuestion $question)
    {
        if ($question->question_type !== 'poll') {
            return response()->json(['message' => 'Voting is only allowed on poll type questions'], 400);
        }
        $validated = $request->validate([
            'option' => ['required', 'string', 'max:120'],
        ]);
        // ensure option is valid
        $options = $question->poll_options ?? [];
        if (!in_array($validated['option'], $options, true)) {
            return response()->json(['message' => 'Invalid poll option'], 422);
        }

        $vote = ForumPollVote::updateOrCreate(
            ['question_id' => $question->id, 'user_id' => $request->user()->id],
            ['option' => $validated['option']]
        );

        $totals = ForumPollVote::selectRaw('option, COUNT(*) as votes')
            ->where('question_id', $question->id)
            ->groupBy('option')
            ->pluck('votes', 'option');

        return response()->json(['vote' => $vote, 'totals' => $totals]);
    }

    public function stats(Request $request)
    {
        // totals
        $totalQuestions = ForumQuestion::count();
        $answeredQuestions = ForumQuestion::whereHas('answers')->count();
        $totalAnswers = ForumAnswer::count();
        $totalViews = ForumQuestion::sum('views');

        // growth vs предыдущие 30 дней
        $now = now();
        $from = $now->copy()->subDays(30);
        $prevFrom = $from->copy()->subDays(30);
        $prevTo = $from;

        $currQuestions = ForumQuestion::whereBetween('created_at', [$from, $now])->count();
        $prevQuestions = ForumQuestion::whereBetween('created_at', [$prevFrom, $prevTo])->count();

        $currAnswers = ForumAnswer::whereBetween('created_at', [$from, $now])->count();
        $prevAnswers = ForumAnswer::whereBetween('created_at', [$prevFrom, $prevTo])->count();

        $currViews = ForumQuestion::whereBetween('updated_at', [$from, $now])->sum('views');
        $prevViews = ForumQuestion::whereBetween('updated_at', [$prevFrom, $prevTo])->sum('views');

        $growth = function ($curr, $prev) {
            if ($prev == 0) return $curr > 0 ? 100.0 : 0.0;
            return round((($curr - $prev) / $prev) * 100, 1);
        };

        return response()->json([
            'totals' => [
                'questions' => $totalQuestions,
                'answered' => $answeredQuestions,
                'answers' => $totalAnswers,
                'monthly_activity' => $totalViews,
            ],
            'growth' => [
                'questions' => $growth($currQuestions, $prevQuestions),
                'answers' => $growth($currAnswers, $prevAnswers),
                'activity' => $growth($currViews, $prevViews),
            ],
        ]);
    }

    public function cards(Request $request)
    {
        $query = ForumQuestion::with('user:id,first_name,last_name,username,profile_photo,user_type')
            ->when(!$request->user() || !$request->user()->hasRole(['admin','trainer']), function ($q) {
                $q->where('is_public', true);
            });

        $perPageParam = $request->get('per_page');
        if ($perPageParam === 'all' || (is_numeric($perPageParam) && (int)$perPageParam === 0)) {
            $collection = $query->latest()->get();
            $items = $collection->map(function ($q) {
                $createdAtBaku = $q->created_at->timezone('Asia/Baku');
                $authorFullName = trim(((string) optional($q->user)->first_name).' '.((string) optional($q->user)->last_name));
                $authorDisplay = $authorFullName !== '' ? $authorFullName : ((string) optional($q->user)->username);
                return [
                    'id' => $q->id,
                    'title' => $q->title,
                    'summary' => $q->summary,
                    'author' => $authorDisplay,
                    'author_user_type' => optional($q->user)->user_type,
                    'author_profile_photo' => optional($q->user)->profile_photo,
                    'author_profile_photo_url' => optional($q->user)->profile_photo_url,
                    'created_date' => $createdAtBaku->toDateString(),
                    'created_time' => $createdAtBaku->format('H:i'),
                    'views' => $q->views,
                    'comments' => $q->answers()->count(),
                    'type' => $q->question_type,
                    'hashtags' => array_slice($q->tags ?? [], 0, 2),
                    'status' => $q->status,
                ];
            });
            return response()->json(['data' => $items, 'meta' => ['total' => $items->count(), 'per_page' => 'all']]);
        }

        $paginator = $query->latest()->paginate($request->integer('per_page', 20));
        $items = collect($paginator->items())->map(function ($q) {
            $createdAtBaku = $q->created_at->timezone('Asia/Baku');
            $authorFullName = trim(((string) optional($q->user)->first_name).' '.((string) optional($q->user)->last_name));
            $authorDisplay = $authorFullName !== '' ? $authorFullName : ((string) optional($q->user)->username);
            return [
                'id' => $q->id,
                'title' => $q->title,
                'summary' => $q->summary,
                'author' => $authorDisplay,
                'created_date' => $createdAtBaku->toDateString(),
                'created_time' => $createdAtBaku->format('H:i'),
                'views' => $q->views,
                'comments' => $q->answers()->count(),
                'type' => $q->question_type,
                'hashtags' => array_slice($q->tags ?? [], 0, 2),
                'status' => $q->status,
            ];
        });

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    // NOTE: Admin yönümlü CRUD mövcud olduğundan, istifadəçi tərəfində update/delete dəstəyi bu mərhələdə çıxarıldı.
}


