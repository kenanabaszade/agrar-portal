<?php

namespace App\Http\Controllers;

use App\Models\Training;
use App\Models\TrainingRating;
use Illuminate\Http\Request;

class TrainingRatingController extends Controller
{
    /**
     * Submit or update rating for a training
     * POST /api/v1/trainings/{training}/rating
     */
    public function store(Request $request, Training $training)
    {
        $user = $request->user();

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        // Update or create rating
        $rating = TrainingRating::updateOrCreate(
            [
                'user_id' => $user->id,
                'training_id' => $training->id,
            ],
            [
                'rating' => $validated['rating'],
            ]
        );

        // Refresh training to get updated average rating
        $training->refresh();

        return response()->json([
            'message' => 'Rating submitted successfully',
            'rating' => $rating,
            'training' => [
                'id' => $training->id,
                'average_rating' => $training->average_rating,
                'ratings_count' => $training->ratings_count,
            ],
        ], 201);
    }

    /**
     * Get user's rating for a training
     * GET /api/v1/trainings/{training}/rating
     */
    public function show(Request $request, Training $training)
    {
        $user = $request->user();

        $rating = TrainingRating::where('user_id', $user->id)
            ->where('training_id', $training->id)
            ->first();

        if (!$rating) {
            return response()->json([
                'rating' => null,
                'message' => 'No rating found for this training',
            ], 200);
        }

        return response()->json([
            'rating' => $rating,
        ], 200);
    }

    /**
     * Delete user's rating for a training
     * DELETE /api/v1/trainings/{training}/rating
     */
    public function destroy(Request $request, Training $training)
    {
        $user = $request->user();

        $rating = TrainingRating::where('user_id', $user->id)
            ->where('training_id', $training->id)
            ->first();

        if (!$rating) {
            return response()->json([
                'message' => 'No rating found for this training',
            ], 404);
        }

        $rating->delete();

        // Refresh training to get updated average rating
        $training->refresh();

        return response()->json([
            'message' => 'Rating deleted successfully',
            'training' => [
                'id' => $training->id,
                'average_rating' => $training->average_rating,
                'ratings_count' => $training->ratings_count,
            ],
        ], 200);
    }
}
