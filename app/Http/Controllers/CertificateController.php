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
     * Generate PDF certificate using Python script (Test endpoint - no auth required)
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

            // Prepare data for Python script
            $data = [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                ],
                'exam' => [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'description' => $exam->description,
                ],
                'training' => [
                    'id' => $training->id,
                    'title' => $training->title,
                    'description' => $training->description,
                ]
            ];

            // Run Python script
            $pythonScript = base_path('certificate_generator.py');
            $jsonData = json_encode($data);
            
            $result = Process::run("python {$pythonScript} '{$jsonData}'");
            
            if ($result->failed()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Python script failed: ' . $result->errorOutput()
                ], 500);
            }

            $output = json_decode($result->output(), true);
            
            if (!$output['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $output['error']
                ], 500);
            }

            // Create certificate record
            $certificate = Certificate::create([
                'user_id' => $user->id,
                'related_training_id' => $training->id,
                'related_exam_id' => $exam->id,
                'certificate_number' => $output['certificate_number'],
                'issue_date' => now()->toDateString(),
                'issuer_name' => 'Aqrar Portal',
                'status' => 'active',
                'digital_signature' => $output['digital_signature'],
                'pdf_path' => $output['pdf_path'],
            ]);

            return response()->json([
                'success' => true,
                'certificate' => $certificate,
                'verification_url' => $output['verification_url'],
                'pdf_path' => $output['pdf_path']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate PDF certificate using Python script
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

            // Prepare data for Python script
            $data = [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                ],
                'exam' => [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'description' => $exam->description,
                ],
                'training' => [
                    'id' => $training->id,
                    'title' => $training->title,
                    'description' => $training->description,
                ]
            ];

            // Run Python script
            $pythonScript = base_path('certificate_generator.py');
            $jsonData = json_encode($data);
            
            $result = Process::run("python {$pythonScript} '{$jsonData}'");
            
            if ($result->failed()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Python script failed: ' . $result->errorOutput()
                ], 500);
            }

            $output = json_decode($result->output(), true);
            
            if (!$output['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $output['error']
                ], 500);
            }

            // Create certificate record
            $certificate = Certificate::create([
                'user_id' => $user->id,
                'related_training_id' => $training->id,
                'related_exam_id' => $exam->id,
                'certificate_number' => $output['certificate_number'],
                'issue_date' => now()->toDateString(),
                'issuer_name' => 'Aqrar Portal',
                'status' => 'active',
                'digital_signature' => $output['digital_signature'],
                'pdf_path' => $output['pdf_path'],
            ]);

            return response()->json([
                'success' => true,
                'certificate' => $certificate,
                'verification_url' => $output['verification_url'],
                'pdf_path' => $output['pdf_path']
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
 
 
 