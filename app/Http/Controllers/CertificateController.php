<?php
 
namespace App\Http\Controllers;
 
use App\Models\Certificate;
use App\Models\ExamRegistration;
use App\Models\User;
use App\Models\Exam;
use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
 
class CertificateController extends Controller
{
    
    public function index()
    {
        return Certificate::with(['user', 'training', 'exam'])
            ->latest()
            ->paginate(20);
    }

    public function show(Certificate $certificate)
    {
        $certificate->load(['user', 'training', 'exam']);
        
        return response()->json([
            'id' => $certificate->id,
            'user' => [
                'id' => $certificate->user->id,
                'name' => $certificate->user->first_name . ' ' . $certificate->user->last_name,
                'email' => $certificate->user->email,
            ],
            'training' => $certificate->training ? [
                'id' => $certificate->training->id,
                'title' => $certificate->training->title,
                'description' => $certificate->training->description,
            ] : null,
            'exam' => $certificate->exam ? [
                'id' => $certificate->exam->id,
                'title' => $certificate->exam->title,
            ] : null,
            'certificate_number' => $certificate->certificate_number,
            'issue_date' => $certificate->issue_date,
            'expiry_date' => $certificate->expiry_date,
            'issuer_name' => $certificate->issuer_name,
            'status' => $certificate->status,
            'is_expired' => $certificate->isExpired(),
            'is_active' => $certificate->isActive(),
            'verification_url' => $certificate->verification_url,
            'download_url' => $certificate->download_url,
            'pdf_url' => $certificate->pdf_url,
            'created_at' => $certificate->created_at,
        ]);
    }

    public function myCertificates(Request $request)
    {
        $query = Certificate::where('user_id', $request->user()->id)
            ->with(['training', 'exam'])
            ->latest();
        
        $certificates = $query->paginate($request->integer('per_page', 20));
        
        $certificates->getCollection()->transform(function ($certificate) {
            return [
                'id' => $certificate->id,
                'training' => $certificate->training ? [
                    'id' => $certificate->training->id,
                    'title' => $certificate->training->title,
                ] : null,
                'exam' => $certificate->exam ? [
                    'id' => $certificate->exam->id,
                    'title' => $certificate->exam->title,
                ] : null,
                'certificate_number' => $certificate->certificate_number,
                'issue_date' => $certificate->issue_date,
                'expiry_date' => $certificate->expiry_date,
                'status' => $certificate->status,
                'is_expired' => $certificate->isExpired(),
                'is_active' => $certificate->isActive(),
                'download_url' => $certificate->download_url,
                'verification_url' => $certificate->verification_url,
                'created_at' => $certificate->created_at,
            ];
        });
        
        return $certificates;
    }

    /**
     * Get certificate data for frontend PDF generation
     */
    public function getCertificateData(Certificate $certificate)
    {
        // Load relationships
        $certificate->load(['user', 'training', 'exam']);
        
        // Check if user can access this certificate
        if (auth()->check()) {
            $user = auth()->user();
            if ($user->id !== $certificate->user_id && !in_array($user->user_type, ['admin', 'trainer'])) {
                return response()->json(['message' => 'Bu sertifikata giriş hüququnuz yoxdur'], 403);
            }
        }
        
        // Get exam score if available
        $examScore = null;
        if ($certificate->exam_id) {
            $examRegistration = ExamRegistration::where('user_id', $certificate->user_id)
                ->where('exam_id', $certificate->exam_id)
                ->where('status', 'passed')
                ->first();
            
            if ($examRegistration) {
                $examScore = $examRegistration->score;
            }
        }
        
        // Return certificate data for frontend PDF generation
        return response()->json([
            'certificate' => [
                'id' => $certificate->id,
                'certificate_number' => $certificate->certificate_number,
                'issue_date' => $certificate->issue_date->format('d.m.Y'),
                'expiry_date' => $certificate->expiry_date ? $certificate->expiry_date->format('d.m.Y') : null,
                'status' => $certificate->status,
                'verification_url' => $certificate->verification_url,
            ],
            'user' => [
                'id' => $certificate->user->id,
                'name' => $certificate->user->first_name . ' ' . $certificate->user->last_name,
                'email' => $certificate->user->email,
            ],
            'training' => $certificate->training ? [
                'id' => $certificate->training->id,
                'title' => $certificate->training->title,
                'description' => $certificate->training->description,
            ] : null,
            'exam' => $certificate->exam ? [
                'id' => $certificate->exam->id,
                'title' => $certificate->exam->title,
                'passing_score' => $certificate->exam->passing_score,
            ] : null,
            'exam_score' => $examScore,
            'qr_code_data' => [
                'verification_url' => $certificate->verification_url,
                'certificate_number' => $certificate->certificate_number,
            ]
        ]);
    }

    /**
     * Upload PDF certificate generated by frontend
     */
    public function uploadPdf(Certificate $certificate, Request $request)
    {
        // Validate PDF file and certificate number
        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:10240', // 10MB max
            'certificate_number' => 'required|string|max:255',
        ]);

        // Check if user can upload for this certificate
        if (auth()->check()) {
            $user = auth()->user();
            if ($user->id !== $certificate->user_id && !in_array($user->user_type, ['admin', 'trainer'])) {
                return response()->json(['message' => 'Bu sertifikat üçün PDF yükləməyə icazəniz yoxdur'], 403);
            }
        }

        // Store PDF file in user-specific directory with frontend-generated certificate number
        $pdfFile = $request->file('pdf_file');
        $certificateNumber = $request->input('certificate_number');
        $fileName = $certificateNumber . '.pdf'; // Use frontend-generated certificate number
        $userDir = 'assets/user/' . $certificate->user_id . '/certificates';
        $path = $pdfFile->storeAs($userDir, $fileName, 'public');

        // Update certificate with PDF path and new certificate number
        $certificate->update([
            'certificate_number' => $certificateNumber,
            'pdf_path' => $path,
            'pdf_url' => Storage::url($path),
        ]);

        return response()->json([
            'message' => 'PDF sertifikat uğurla yükləndi',
            'pdf_url' => $certificate->pdf_url,
            'certificate' => [
                'id' => $certificate->id,
                'certificate_number' => $certificate->certificate_number,
                'pdf_url' => $certificate->pdf_url,
            ]
        ]);
    }

    /**
     * Verify certificate by certificate number
     */
    public function verify($certificateNumber)
    {
        $certificate = Certificate::where('certificate_number', $certificateNumber)
            ->with(['user', 'training', 'exam'])
            ->first();
        
        if (!$certificate) {
            return response()->json([
                'valid' => false,
                'message' => 'Sertifikat tapılmadı.'
            ], 404);
        }
        
        return response()->json([
            'valid' => true,
            'certificate' => [
                'certificate_number' => $certificate->certificate_number,
                'user_name' => $certificate->user->first_name . ' ' . $certificate->user->last_name,
                'training_title' => $certificate->training ? $certificate->training->title : null,
                'exam_title' => $certificate->exam ? $certificate->exam->title : null,
                'issue_date' => $certificate->issue_date->format('d.m.Y'),
                'expiry_date' => $certificate->expiry_date ? $certificate->expiry_date->format('d.m.Y') : null,
                'status' => $certificate->status,
                'is_expired' => $certificate->isExpired(),
                'is_active' => $certificate->isActive(),
                'issuer_name' => $certificate->issuer_name,
            ]
        ]);
    }

    /**
     * Generate PDF certificate (Test endpoint - no auth required)
     */
    public function generatePdfCertificateTest(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'exam_id' => 'required|exists:exams,id',
            'training_id' => 'required|exists:trainings,id',
        ]);

        try {
            // Get data
            $user = User::findOrFail($request->user_id);
            $exam = Exam::findOrFail($request->exam_id);
            $training = Training::findOrFail($request->training_id);

            // Prepare data for certificate generation
            $userData = [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
            ];
            
            $examData = [
                'id' => $exam->id,
                'title' => $exam->title,
                'description' => $exam->description,
                'sertifikat_description' => $exam->sertifikat_description ?? null,
            ];
            
            $trainingData = [
                'id' => $training->id,
                'title' => $training->title,
                'description' => $training->description,
            ];

            // Use PHP certificate generator service
            $service = new \App\Services\CertificateGeneratorService();
            $result = $service->generateCertificate($userData, $examData, $trainingData);
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Certificate generation failed'
                ], 500);
            }

            // Create certificate record
            $certificate = Certificate::create([
                'user_id' => $user->id,
                'related_training_id' => $training->id,
                'related_exam_id' => $exam->id,
                'certificate_number' => $result['certificate_number'],
                'issue_date' => now()->toDateString(),
                'issuer_name' => 'Aqrar Portal',
                'status' => 'active',
                'digital_signature' => $result['digital_signature'],
                'pdf_path' => $result['pdf_path'],
            ]);

            return response()->json([
                'success' => true,
                'certificate' => $certificate,
                'verification_url' => $result['verification_url'],
                'pdf_path' => $result['pdf_path']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate PDF certificate
     */
    public function generatePdfCertificate(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'exam_id' => 'required|exists:exams,id',
            'training_id' => 'required|exists:trainings,id',
        ]);

        try {
            // Get data
            $user = User::findOrFail($request->user_id);
            $exam = Exam::findOrFail($request->exam_id);
            $training = Training::findOrFail($request->training_id);

            // Prepare data for certificate generation
            $userData = [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
            ];
            
            $examData = [
                'id' => $exam->id,
                'title' => $exam->title,
                'description' => $exam->description,
                'sertifikat_description' => $exam->sertifikat_description ?? null,
            ];
            
            $trainingData = [
                'id' => $training->id,
                'title' => $training->title,
                'description' => $training->description,
            ];

            // Use PHP certificate generator service
            $service = new \App\Services\CertificateGeneratorService();
            $result = $service->generateCertificate($userData, $examData, $trainingData);
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Certificate generation failed'
                ], 500);
            }

            // Create certificate record
            $certificate = Certificate::create([
                'user_id' => $user->id,
                'related_training_id' => $training->id,
                'related_exam_id' => $exam->id,
                'certificate_number' => $result['certificate_number'],
                'issue_date' => now()->toDateString(),
                'issuer_name' => 'Aqrar Portal',
                'status' => 'active',
                'digital_signature' => $result['digital_signature'],
                'pdf_path' => $result['pdf_path'],
            ]);

            return response()->json([
                'success' => true,
                'certificate' => $certificate,
                'verification_url' => $result['verification_url'],
                'pdf_path' => $result['pdf_path']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * View certificate by digital signature
     */
    public function viewBySignature($signature)
    {
        \Log::info('Certificate verification request', ['signature' => $signature]);
        
        $certificate = Certificate::where('digital_signature', $signature)->first();
        
        \Log::info('Certificate found', ['certificate' => $certificate ? $certificate->id : 'not found']);
        
        if (!$certificate) {
            return response()->json([
                'valid' => false,
                'message' => 'Sertifikat tapılmadı.',
                'debug' => [
                    'signature' => $signature,
                    'total_certificates' => Certificate::count()
                ]
            ], 404);
        }

        $certificate->load(['user', 'training', 'exam']);
        
        return response()->json([
            'valid' => true,
            'certificate' => [
                'id' => $certificate->id,
                'user' => [
                    'id' => $certificate->user->id,
                    'name' => $certificate->user->first_name . ' ' . $certificate->user->last_name,
                    'email' => $certificate->user->email,
                ],
                'training' => $certificate->training ? [
                    'id' => $certificate->training->id,
                    'title' => $certificate->training->title,
                    'description' => $certificate->training->description,
                ] : null,
                'exam' => $certificate->exam ? [
                    'id' => $certificate->exam->id,
                    'title' => $certificate->exam->title,
                ] : null,
                'certificate_number' => $certificate->certificate_number,
                'issue_date' => $certificate->issue_date,
                'expiry_date' => $certificate->expiry_date,
                'issuer_name' => $certificate->issuer_name,
                'status' => $certificate->status,
                'is_expired' => $certificate->isExpired(),
                'is_active' => $certificate->isActive(),
                'digital_signature' => $certificate->digital_signature,
                'pdf_path' => $certificate->pdf_path,
                'created_at' => $certificate->created_at,
            ]
        ]);
    }

    /**
     * Download PDF certificate by ID
     */
    public function download(Certificate $certificate, Request $request)
    {
        // Check if user can access this certificate
        if (auth()->check()) {
            $user = auth()->user();
            if ($user->id !== $certificate->user_id && !in_array($user->user_type, ['admin', 'trainer'])) {
                return response()->json(['message' => 'Bu sertifikata giriş hüququnuz yoxdur'], 403);
            }
        }
        
        // If no PDF path, try to generate one or return helpful error
        if (!$certificate->pdf_path) {
            // Try to generate PDF if training exists and has certificate enabled
            if ($certificate->related_training_id) {
                $training = Training::find($certificate->related_training_id);
                if ($training && $training->has_certificate && $certificate->related_exam_id) {
                    try {
                        $exam = Exam::find($certificate->related_exam_id);
                        $user = User::find($certificate->user_id);
                        
                        if ($exam && $user) {
                            // Try to generate PDF using the generatePdfCertificate logic
                            $examController = app(\App\Http\Controllers\ExamController::class);
                            $registration = ExamRegistration::where('user_id', $user->id)
                                ->where('exam_id', $exam->id)
                                ->where('status', 'passed')
                                ->first();
                            
                            if ($registration) {
                                // Generate PDF using the helper method
                                $success = $this->generatePdfForCertificate($certificate, $user, $exam, $training);
                                
                                if ($success) {
                                    // Reload certificate to get updated PDF path
                                    $certificate->refresh();
                                    \Log::info('PDF generated successfully for certificate ' . $certificate->id);
                                } else {
                                    \Log::error('PDF generation returned false for certificate ' . $certificate->id);
                                }
                            } else {
                                \Log::warning('No passed registration found for certificate ' . $certificate->id . ', user ' . $user->id . ', exam ' . $exam->id);
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::error('Failed to generate PDF on download: ' . $e->getMessage());
                    }
                }
            }
            
            // If still no PDF path, return helpful error
            if (!$certificate->pdf_path) {
                return response()->json([
                    'error' => 'PDF sertifikat hələ yaradılmayıb.',
                    'message' => 'Sertifikat mövcuddur, lakin PDF faylı yoxdur. PDF-ni yükləmək üçün admin ilə əlaqə saxlayın.',
                    'certificate_id' => $certificate->id,
                    'certificate_number' => $certificate->certificate_number,
                    'can_upload_pdf' => auth()->check() && (
                        auth()->user()->id === $certificate->user_id || 
                        in_array(auth()->user()->user_type, ['admin', 'trainer'])
                    ),
                    'upload_endpoint' => auth()->check() ? '/api/v1/certificates/' . $certificate->id . '/upload-pdf' : null
                ], 404);
            }
        }

        // Check if file exists in storage
        $filePath = storage_path('app/public/' . $certificate->pdf_path);
        
        // Also check the original path in case it's a full path
        if (!file_exists($filePath) && file_exists($certificate->pdf_path)) {
            $filePath = $certificate->pdf_path;
        }
        
        // Also check if it's a relative path from storage root
        if (!file_exists($filePath)) {
            $altPath = storage_path('app/' . $certificate->pdf_path);
            if (file_exists($altPath)) {
                $filePath = $altPath;
            }
        }
        
        if (!file_exists($filePath)) {
            return response()->json([
                'error' => 'PDF faylı mövcud deyil.',
                'message' => 'PDF faylı sistemdə tapılmadı. Zəhmət olmasa admin ilə əlaqə saxlayın.',
                'pdf_path' => $certificate->pdf_path
            ], 404);
        }

        $fileName = "certificate_{$certificate->certificate_number}.pdf";
        
        return response()->download($filePath, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Preview PDF certificate by ID
     */
    public function preview(Certificate $certificate, Request $request)
    {
        // Check if user can access this certificate
        if (auth()->check()) {
            $user = auth()->user();
            if ($user->id !== $certificate->user_id && !in_array($user->user_type, ['admin', 'trainer'])) {
                return response()->json(['message' => 'Bu sertifikata giriş hüququnuz yoxdur'], 403);
            }
        }
        
        // If no PDF path, try to generate one or return helpful error
        if (!$certificate->pdf_path) {
            // Try to generate PDF if training exists and has certificate enabled
            if ($certificate->related_training_id) {
                $training = Training::find($certificate->related_training_id);
                if ($training && $training->has_certificate && $certificate->related_exam_id) {
                    try {
                        $exam = Exam::find($certificate->related_exam_id);
                        $user = User::find($certificate->user_id);
                        
                        if ($exam && $user) {
                            $registration = ExamRegistration::where('user_id', $user->id)
                                ->where('exam_id', $exam->id)
                                ->where('status', 'passed')
                                ->first();
                            
                            if ($registration) {
                                $success = $this->generatePdfForCertificate($certificate, $user, $exam, $training);
                                
                                if ($success) {
                                    $certificate->refresh();
                                    \Log::info('PDF generated successfully for certificate ' . $certificate->id);
                                } else {
                                    \Log::error('PDF generation returned false for certificate ' . $certificate->id);
                                }
                            } else {
                                \Log::warning('No passed registration found for certificate ' . $certificate->id . ', user ' . $user->id . ', exam ' . $exam->id);
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::error('Failed to generate PDF on preview: ' . $e->getMessage());
                    }
                }
            }
            
            // If still no PDF path, return helpful error
            if (!$certificate->pdf_path) {
                $debugInfo = [
                    'certificate_id' => $certificate->id,
                    'has_training' => (bool)$certificate->related_training_id,
                    'has_exam' => (bool)$certificate->related_exam_id,
                ];
                
                return response()->json([
                    'error' => 'PDF sertifikat hələ yaradılmayıb.',
                    'message' => 'Sertifikat mövcuddur, lakin PDF faylı yoxdur. Log fayllarına baxın.',
                    'certificate_id' => $certificate->id,
                    'certificate_number' => $certificate->certificate_number,
                    'debug_info' => $debugInfo,
                ], 404);
            }
        }

        // Check if file exists in storage
        $filePath = storage_path('app/public/' . $certificate->pdf_path);
        
        // Also check the original path in case it's a full path
        if (!file_exists($filePath) && file_exists($certificate->pdf_path)) {
            $filePath = $certificate->pdf_path;
        }
        
        // Also check if it's a relative path from storage root
        if (!file_exists($filePath)) {
            $altPath = storage_path('app/' . $certificate->pdf_path);
            if (file_exists($altPath)) {
                $filePath = $altPath;
            }
        }
        
        if (!file_exists($filePath)) {
            return response()->json([
                'error' => 'PDF faylı mövcud deyil.',
                'message' => 'PDF faylı sistemdə tapılmadı. Zəhmət olmasa admin ilə əlaqə saxlayın.',
                'pdf_path' => $certificate->pdf_path
            ], 404);
        }

        // Validate PDF file has content
        $fileSize = filesize($filePath);
        if ($fileSize === false || $fileSize === 0) {
            \Log::error('PDF file is empty or unreadable', [
                'certificate_id' => $certificate->id,
                'file_path' => $filePath,
                'file_size' => $fileSize
            ]);
            return response()->json([
                'error' => 'PDF faylı boşdur və ya korlanmışdır.',
                'message' => 'PDF faylı düzgün yüklənməyib. Zəhmət olmasa admin ilə əlaqə saxlayın.',
                'certificate_id' => $certificate->id
            ], 500);
        }

        // Validate PDF header (first 4 bytes should be %PDF)
        $handle = fopen($filePath, 'rb');
        if ($handle) {
            $header = fread($handle, 4);
            fclose($handle);
            if ($header !== '%PDF') {
                \Log::error('Invalid PDF file format', [
                    'certificate_id' => $certificate->id,
                    'file_path' => $filePath,
                    'header' => bin2hex($header)
                ]);
                return response()->json([
                    'error' => 'PDF fayl formatı düzgün deyil.',
                    'message' => 'PDF faylı korlanmışdır. Zəhmət olmasa admin ilə əlaqə saxlayın.',
                    'certificate_id' => $certificate->id
                ], 500);
            }
        }

        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Length' => $fileSize,
            'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
    
    /**
     * Generate PDF for certificate using PHP service
     */
    private function generatePdfForCertificate(Certificate $certificate, User $user, Exam $exam, Training $training)
    {
        try {
            \Log::info('Starting PDF generation for certificate ' . $certificate->id, [
                'user_id' => $user->id,
                'exam_id' => $exam->id,
                'training_id' => $training->id
            ]);
            
            // Prepare data for certificate generation
            $userData = [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
            ];
            
            $examData = [
                'id' => $exam->id,
                'title' => $exam->title,
                'description' => $exam->description,
                'sertifikat_description' => $exam->sertifikat_description,
            ];
            
            $trainingData = [
                'id' => $training->id,
                'title' => $training->title,
                'description' => $training->description,
            ];

            // Use PHP certificate generator service
            $service = new \App\Services\CertificateGeneratorService();
            $result = $service->generateCertificate($userData, $examData, $trainingData);
            
            if (!$result['success']) {
                $error = $result['error'] ?? 'Unknown error';
                \Log::error('Certificate generation error: ' . $error, ['result' => $result]);
                return false;
            }

            // Update certificate with PDF path
            $pdfPath = $result['pdf_path'] ?? null;
            if (!$pdfPath) {
                \Log::error('PDF path not in result', ['result' => $result]);
                return false;
            }
            
            $certificate->update([
                'pdf_path' => $pdfPath,
                'pdf_url' => url('/storage/' . $pdfPath),
                'digital_signature' => $result['digital_signature'] ?? $certificate->digital_signature,
            ]);
            
            \Log::info('Certificate updated with PDF', [
                'certificate_id' => $certificate->id,
                'pdf_path' => $pdfPath
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Error generating PDF for certificate: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Download PDF certificate by digital signature
     */
    public function downloadPdfBySignature($signature)
    {
        $certificate = Certificate::where('digital_signature', $signature)->first();
        
        if (!$certificate || !$certificate->pdf_path) {
            return response()->json([
                'error' => 'PDF sertifikat tapılmadı.'
            ], 404);
        }

        $pdfPath = $certificate->pdf_path;
        
        if (!file_exists($pdfPath)) {
            return response()->json([
                'error' => 'PDF faylı mövcud deyil.'
            ], 404);
        }

        return response()->download($pdfPath, "certificate_{$certificate->certificate_number}.pdf");
    }

    /**
     * Show certificate verification page
     */
    public function verifyBySignature($signature)
    {
        $certificate = Certificate::where('digital_signature', $signature)->first();
        
        if (!$certificate) {
            return response()->view('certificate-verification', [
                'certificate' => null,
                'error' => 'Sertifikat tapılmadı'
            ], 404);
        }

        $certificate->load(['user', 'training', 'exam']);
        
        return view('certificate-verification', [
            'certificate' => $certificate
        ]);
    }
}
 
 
 