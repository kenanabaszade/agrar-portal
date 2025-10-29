<?php

namespace App\Http\Controllers;

use App\Models\InternshipApplication;
use App\Models\InternshipProgram;
use App\Models\User;
use App\Mail\InternshipApplicationConfirmation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class InternshipApplicationController extends Controller
{
    /**
     * Apply for an internship program with CV upload
     */
    public function apply(Request $request, InternshipProgram $internshipProgram): JsonResponse
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return response()->json(['message' => 'Authentication required'], 401);
        }

        $user = auth()->user();

        // Check if program is still accepting applications
        if ($internshipProgram->registration_status !== 'open') {
            return response()->json([
                'message' => 'Bu staj proqramına qeydiyyat bağlıdır'
            ], 400);
        }

        // Check if registration deadline has passed
        if ($internshipProgram->last_register_date && now()->isAfter($internshipProgram->last_register_date)) {
            return response()->json([
                'message' => 'Qeydiyyat müddəti bitmişdir'
            ], 400);
        }

        // Check if user has reached application limit (max 2 applications per program)
        $applicationCount = InternshipApplication::where('user_id', $user->id)
            ->where('internship_program_id', $internshipProgram->id)
            ->count();

        if ($applicationCount >= 2) {
            return response()->json([
                'message' => 'Bu staj proqramına maksimum 2 dəfə müraciət edə bilərsiniz'
            ], 400);
        }

        // Validate the request
        $validated = $request->validate([
            'cv' => 'required|file|mimes:pdf,doc,docx|max:5120', // 5MB max
            'cover_letter' => 'nullable|string|max:2000',
        ]);

        DB::beginTransaction();
        try {
            // Handle CV file upload
            $cvFile = $request->file('cv');
            $filename = 'cv_' . $user->id . '_' . $internshipProgram->id . '_' . time() . '.' . $cvFile->getClientOriginalExtension();
            $path = $cvFile->storeAs('internship_cvs', $filename, 'public');

            // Create the application
            $application = InternshipApplication::create([
                'user_id' => $user->id,
                'internship_program_id' => $internshipProgram->id,
                'cv_file_path' => $path,
                'cv_file_name' => $cvFile->getClientOriginalName(),
                'cv_file_size' => $cvFile->getSize(),
                'cv_mime_type' => $cvFile->getMimeType(),
                'cover_letter' => $validated['cover_letter'] ?? null,
                'status' => 'pending',
            ]);

            // Send email notification to admins
            $this->notifyAdmins($application);

            // Send confirmation email to user
            try {
                Mail::to($user->email)->send(new InternshipApplicationConfirmation($application));
            } catch (\Exception $e) {
                \Log::error('Failed to send confirmation email to user', [
                    'user_id' => $user->id,
                    'application_id' => $application->id,
                    'error' => $e->getMessage()
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Müraciətiniz uğurla göndərildi',
                'application' => $application->load(['user', 'internshipProgram'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Clean up uploaded file if application creation failed
            if (isset($path)) {
                Storage::disk('public')->delete($path);
            }

            return response()->json([
                'message' => 'Müraciət göndərilmədi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's applications
     */
    public function myApplications(): JsonResponse
    {
        $user = auth()->user();
        
        $applications = InternshipApplication::with(['internshipProgram'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'applications' => $applications
        ]);
    }

    /**
     * Get application details
     */
    public function show(InternshipApplication $application): JsonResponse
    {
        // Check if user owns the application or is admin
        if (auth()->user()->id !== $application->user_id && !auth()->user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $application->load(['user', 'internshipProgram', 'reviewer']);

        return response()->json([
            'application' => $application
        ]);
    }

    /**
     * Get all applications for admin (with filtering)
     */
    public function index(Request $request): JsonResponse
    {
        $query = InternshipApplication::with(['user', 'internshipProgram', 'reviewer']);

        // Filter by program
        if ($request->has('program_id')) {
            $query->where('internship_program_id', $request->program_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Search by user name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $applications = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'applications' => $applications,
            'meta' => [
                'total' => $applications->total(),
                'per_page' => $applications->perPage(),
                'current_page' => $applications->currentPage(),
                'last_page' => $applications->lastPage(),
            ]
        ]);
    }

    /**
     * Update application status (admin only)
     */
    public function updateStatus(Request $request, InternshipApplication $application): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,reviewed,accepted,rejected',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $application->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? null,
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        // If accepted, increment enrollment count
        if ($validated['status'] === 'accepted') {
            $application->internshipProgram->increment('current_enrollment');
            
            // Check if program is now full
            if ($application->internshipProgram->current_enrollment >= $application->internshipProgram->max_capacity) {
                $application->internshipProgram->update(['registration_status' => 'full']);
            }
        }

        return response()->json([
            'message' => 'Application status updated successfully',
            'application' => $application->load(['user', 'internshipProgram', 'reviewer'])
        ]);
    }

    /**
     * Download CV file
     */
    public function downloadCv(InternshipApplication $application): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // Check if user owns the application or is admin
        if (auth()->user()->id !== $application->user_id && !auth()->user()->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        if (!Storage::disk('public')->exists($application->cv_file_path)) {
            abort(404, 'CV file not found');
        }

        return Storage::disk('public')->download(
            $application->cv_file_path,
            $application->cv_file_name
        );
    }

    /**
     * Delete application (user can delete their own pending applications)
     */
    public function destroy(InternshipApplication $application): JsonResponse
    {
        // Check if user owns the application or is admin
        if (auth()->user()->id !== $application->user_id && !auth()->user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Users can only delete pending applications
        if (auth()->user()->id === $application->user_id && $application->status !== 'pending') {
            return response()->json([
                'message' => 'Yalnız gözləyən müraciətləri silə bilərsiniz'
            ], 400);
        }

        // Delete CV file
        Storage::disk('public')->delete($application->cv_file_path);

        // Delete application
        $application->delete();

        return response()->json([
            'message' => 'Application deleted successfully'
        ]);
    }

    /**
     * Send email notification to admins about new application
     */
    private function notifyAdmins(InternshipApplication $application): void
    {
        try {
            $admins = User::where('user_type', 'admin')->get();
            
            foreach ($admins as $admin) {
                Mail::to($admin->email)->send(new \App\Mail\InternshipApplicationNotification($application));
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send internship application notification', [
                'application_id' => $application->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}