<?php

namespace App\Http\Controllers;

use App\Models\ForumQuestion;
use App\Models\ForumAnswer;
use Illuminate\Http\Request;

class ForumController extends Controller
{
    public function listQuestions()
    {
        return ForumQuestion::with('user')->latest()->paginate(20);
    }

    public function postQuestion(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);
        $question = ForumQuestion::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'body' => $validated['body'],
            'status' => 'open',
        ]);
        return response()->json($question, 201);
    }

    public function answerQuestion(Request $request, ForumQuestion $question)
    {
        $validated = $request->validate([
            'body' => ['required', 'string'],
        ]);
        $answer = ForumAnswer::create([
            'question_id' => $question->id,
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
            'is_accepted' => false,
        ]);
        return response()->json($answer, 201);
    }
}


