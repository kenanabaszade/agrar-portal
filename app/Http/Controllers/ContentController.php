<?php

namespace App\Http\Controllers;

use App\Models\Training;
use App\Models\Meeting;
use App\Models\InternshipProgram;
use App\Models\EducationalContent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ContentController extends Controller
{
    /**
     * Get all trainings, webinars, internship programs, and articles
     */
    public function getAllContent(Request $request): JsonResponse
    {
        try {
            // Get all trainings
            $trainings = Training::with(['trainer:id,first_name,last_name,email'])
                ->latest()
                ->get()
                ->map(function ($training) {
                    return [
                        'id' => $training->id,
                        'type' => 'training',
                        'title' => $training->title,
                        'description' => $training->description,
                        'category' => $training->category,
                        'trainer' => $training->trainer ? [
                            'id' => $training->trainer->id,
                            'name' => $training->trainer->first_name . ' ' . $training->trainer->last_name,
                            'email' => $training->trainer->email,
                        ] : null,
                        'start_date' => $training->start_date,
                        'end_date' => $training->end_date,
                        'is_online' => $training->is_online,
                        'type_detail' => $training->type,
                        'status' => $training->status,
                        'difficulty' => $training->difficulty,
                        'has_certificate' => $training->has_certificate,
                        'created_at' => $training->created_at,
                        'updated_at' => $training->updated_at,
                    ];
                });

            // Get all webinars (meetings)
            $webinars = Meeting::with(['trainer:id,first_name,last_name,email', 'creator:id,first_name,last_name'])
                ->latest()
                ->get()
                ->map(function ($meeting) {
                    return [
                        'id' => $meeting->id,
                        'type' => 'webinar',
                        'title' => $meeting->title,
                        'description' => $meeting->description,
                        'category' => $meeting->category,
                        'trainer' => $meeting->trainer ? [
                            'id' => $meeting->trainer->id,
                            'name' => $meeting->trainer->first_name . ' ' . $meeting->trainer->last_name,
                            'email' => $meeting->trainer->email,
                        ] : null,
                        'start_time' => $meeting->start_time,
                        'end_time' => $meeting->end_time,
                        'timezone' => $meeting->timezone,
                        'status' => $meeting->status,
                        'image_url' => $meeting->image_url ? url(Storage::url($meeting->image_url)) : null,
                        'google_meet_link' => $meeting->google_meet_link,
                        'has_certificate' => $meeting->has_certificate,
                        'has_materials' => $meeting->has_materials,
                        'is_permanent' => $meeting->is_permanent,
                        'created_at' => $meeting->created_at,
                        'updated_at' => $meeting->updated_at,
                    ];
                });

            // Get all internship programs
            $internshipPrograms = InternshipProgram::with(['trainer:id,first_name,last_name,email'])
                ->where('is_active', true)
                ->latest()
                ->get()
                ->map(function ($program) {
                    return [
                        'id' => $program->id,
                        'type' => 'internship_program',
                        'title' => $program->title,
                        'description' => $program->description,
                        'category' => $program->category,
                        'trainer' => $program->trainer ? [
                            'id' => $program->trainer->id,
                            'name' => $program->trainer->first_name . ' ' . $program->trainer->last_name,
                            'email' => $program->trainer->email,
                        ] : null,
                        'image_url' => $program->image_url,
                        'is_featured' => $program->is_featured,
                        'registration_status' => $program->registration_status,
                        'duration_weeks' => $program->duration_weeks,
                        'start_date' => $program->start_date,
                        'end_date' => $program->end_date,
                        'location' => $program->location,
                        'current_enrollment' => $program->current_enrollment,
                        'max_capacity' => $program->max_capacity,
                        'instructor_name' => $program->instructor_name,
                        'instructor_title' => $program->instructor_title,
                        'instructor_rating' => $program->instructor_rating,
                        'created_at' => $program->created_at,
                        'updated_at' => $program->updated_at,
                    ];
                });

            // Get all articles (meqaleler)
            $articles = EducationalContent::with(['creator:id,first_name,last_name'])
                ->where('type', 'meqale')
                ->latest()
                ->get()
                ->map(function ($article) {
                    $imageUrl = $article->image_path ? url(Storage::url($article->image_path)) : null;
                    
                    // If no uploaded image, fallback to og_image from SEO
                    if (!$imageUrl && isset($article->seo['og_image'])) {
                        $imageUrl = $article->seo['og_image'];
                    }

                    return [
                        'id' => $article->id,
                        'type' => 'article',
                        'title' => $article->title,
                        'short_description' => $article->short_description,
                        'description' => $article->body_html,
                        'category' => $article->category,
                        'creator' => $article->creator ? [
                            'id' => $article->creator->id,
                            'name' => $article->creator->first_name . ' ' . $article->creator->last_name,
                        ] : null,
                        'image_url' => $imageUrl,
                        'hashtags' => $article->hashtags,
                        'likes_count' => $article->likes_count ?? 0,
                        'views_count' => $article->views_count ?? 0,
                        'created_at' => $article->created_at,
                        'updated_at' => $article->updated_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'trainings' => $trainings,
                    'webinars' => $webinars,
                    'internship_programs' => $internshipPrograms,
                    'articles' => $articles,
                    'counts' => [
                        'trainings' => $trainings->count(),
                        'webinars' => $webinars->count(),
                        'internship_programs' => $internshipPrograms->count(),
                        'articles' => $articles->count(),
                        'total' => $trainings->count() + $webinars->count() + $internshipPrograms->count() + $articles->count(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch content',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

