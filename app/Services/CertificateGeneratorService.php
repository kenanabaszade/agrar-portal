<?php

namespace App\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Carbon\Carbon;

class CertificateGeneratorService
{
    private string $basePath;
    private string $certificateDir;
    private string $outputDir;

    public function __construct()
    {
        $this->basePath = base_path();
        $this->certificateDir = $this->basePath . DIRECTORY_SEPARATOR . 'certificate';
        $this->outputDir = storage_path('app/public/certificates');
        
        // Ensure output directory exists
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }

    /**
     * Generate SHA256 digital signature for certificate
     */
    private function generateDigitalSignature(array $userData, array $examData): string
    {
        $signatureString = sprintf(
            "%d_%d_%s_%s_%s_%s",
            $userData['id'],
            $examData['id'],
            $examData['title'],
            $userData['first_name'],
            $userData['last_name'],
            Carbon::now()->toIso8601String()
        );
        
        return hash('sha256', $signatureString);
    }

    /**
     * Generate QR code for certificate verification
     * Returns base64 encoded PNG image
     */
    private function generateQrCode(string $url): string
    {
        try {
            $builder = new Builder(
                writer: new PngWriter(),
                writerOptions: [],
                validateResult: false,
                data: $url,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::Low,
                size: 300,
                margin: 10
            );
            
            $result = $builder->build();
            
            // Get the PNG data and encode to base64
            $pngData = $result->getString();
            return base64_encode($pngData);
        } catch (\Exception $e) {
            Log::error('QR code generation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create HTML certificate with dynamic data
     */
    private function createCertificateHtml(
        array $userData, 
        array $examData, 
        array $trainingData, 
        string $digitalSignature
    ): string {
        // Generate PDF URL
        $pdfUrl = url("/storage/certificates/certificate_{$digitalSignature}.pdf");
        
        // Generate verification URL
        $verificationUrl = url("/api/certificates/verify-page/{$digitalSignature}");
        
        // Generate QR code with PDF URL
        $qrBase64 = $this->generateQrCode($pdfUrl);
        
        // Format date
        $issueDate = Carbon::now()->format('d / m / Y');
        
        // Generate certificate number
        $certNumber = sprintf("AZ-%d-%06d", Carbon::now()->year, $userData['id']);
        
        // Read template
        $templatePath = $this->certificateDir . DIRECTORY_SEPARATOR . 'certificate.html';
        if (!file_exists($templatePath)) {
            throw new \Exception("Certificate template not found at: {$templatePath}");
        }
        
        $htmlContent = file_get_contents($templatePath);
        
        // Certificate description
        $certDescription = $examData['sertifikat_description'] ?? 
            "{$trainingData['title']} üzrə imtahanı uğurla başa vurmuşdur.";
        
        // Get image path - convert to file:// URL for Chrome
        $imagePath = $this->certificateDir . DIRECTORY_SEPARATOR . 'image.png';
        $imageFileUrl = $this->convertToFileUrl($imagePath);
        
        // Convert external image URLs to base64 for better reliability in headless Chrome
        // Find all external image URLs in HTML and convert them
        preg_match_all('/src="(https?:\/\/[^"]+)"/', $htmlContent, $externalUrls);
        foreach ($externalUrls[1] as $url) {
            $base64Image = $this->convertImageToBase64($url);
            $htmlContent = str_replace('src="' . $url . '"', 'src="' . $base64Image . '"', $htmlContent);
            Log::debug('Converted external image URL to base64', ['original_url' => $url]);
        }
        
        // Replace placeholders with actual data
        $replacements = [
            'CAHİD HÜMBƏTOV' => strtoupper("{$userData['first_name']} {$userData['last_name']}"),
            'AZ-2025-001234' => $certNumber,
            '27 / 10 / 2025' => $issueDate,
            'İşğaldan azad olunmuş ərazilərdə kənd təsərrüfatının potensialının gücləndirməsi modulları üzrə təlimlərdə imtahanı uğurla başa vurmuşdur.' => $certDescription,
            'PLACEHOLDER_EXAM_NAME' => $examData['title'] ?? '',
            'PLACEHOLDER_QR_CODE' => "data:image/png;base64,{$qrBase64}",
            'href=""' => "href=\"{$verificationUrl}\"",
            './image.png' => $imageFileUrl,
        ];
        
        foreach ($replacements as $placeholder => $replacement) {
            if (strpos($htmlContent, $placeholder) !== false) {
                $htmlContent = str_replace($placeholder, $replacement, $htmlContent);
                Log::debug("Replaced placeholder: {$placeholder}");
            } else {
                Log::debug("Placeholder not found: {$placeholder}");
            }
        }
        
        return $htmlContent;
    }

    /**
     * Convert file path to file:// URL for Chrome
     */
    private function convertToFileUrl(string $filePath): string
    {
        // Convert Windows path separators to forward slashes
        $filePath = str_replace('\\', '/', $filePath);
        
        // Add file:// protocol
        if (strpos($filePath, 'file://') !== 0) {
            $filePath = 'file:///' . $filePath;
        }
        
        return $filePath;
    }

    /**
     * Convert external image URL to base64 data URI
     * More efficient for headless Chrome which may have network restrictions
     */
    private function convertImageToBase64(string $url): string
    {
        try {
            // If it's already a data URI, return as is
            if (strpos($url, 'data:') === 0) {
                return $url;
            }
            
            // If it's a local file path, convert to file:// URL
            if (strpos($url, 'http') !== 0 && file_exists($url)) {
                $content = file_get_contents($url);
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $url);
                finfo_close($finfo);
                return 'data:' . $mimeType . ';base64,' . base64_encode($content);
            }
            
            // Fetch external URL
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ],
            ]);
            
            $imageData = @file_get_contents($url, false, $context);
            
            if ($imageData === false) {
                Log::warning('Failed to fetch image from URL: ' . $url);
                // Return a transparent 1x1 PNG as fallback
                return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
            }
            
            // Determine MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_buffer($finfo, $imageData);
            finfo_close($finfo);
            
            // Fallback to image/jpeg if detection fails
            if (!$mimeType || !in_array($mimeType, ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp'])) {
                $mimeType = 'image/jpeg';
            }
            
            return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
        } catch (\Exception $e) {
            Log::error('Error converting image to base64: ' . $e->getMessage(), ['url' => $url]);
            // Return transparent 1x1 PNG as fallback
            return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        }
    }

    /**
     * Find Chrome/Chromium executable
     */
    private function findChromeExecutable(): ?string
    {
        $possiblePaths = [];
        
        if (PHP_OS_FAMILY === 'Windows') {
            $possiblePaths = [
                'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
                'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
                'C:\\Users\\' . get_current_user() . '\\AppData\\Local\\Google\\Chrome\\Application\\chrome.exe',
                'C:\\Program Files\\Chromium\\Application\\chrome.exe',
                'C:\\Program Files (x86)\\Chromium\\Application\\chrome.exe',
            ];
        } else {
            // Linux/Mac
            $possiblePaths = [
                '/usr/bin/google-chrome',
                '/usr/bin/chromium',
                '/usr/bin/chromium-browser',
                '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
            ];
        }
        
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                Log::debug('Found Chrome at: ' . $path);
                return $path;
            }
        }
        
        // Try to find via which/where command
        if (PHP_OS_FAMILY === 'Windows') {
            $result = Process::run('where.exe chrome');
            if ($result->successful() && !empty(trim($result->output()))) {
                $foundPath = trim(explode("\n", $result->output())[0]);
                if (file_exists($foundPath)) {
                    Log::debug('Found Chrome via where.exe: ' . $foundPath);
                    return $foundPath;
                }
            }
        } else {
            $result = Process::run('which google-chrome || which chromium || which chromium-browser');
            if ($result->successful() && !empty(trim($result->output()))) {
                $foundPath = trim($result->output());
                if (file_exists($foundPath)) {
                    Log::debug('Found Chrome via which: ' . $foundPath);
                    return $foundPath;
                }
            }
        }
        
        Log::warning('Chrome executable not found in any expected location');
        return null;
    }

    /**
     * Recursively remove a directory (helper for cleanup)
     */
    private function removeDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->removeDirectory($path) : @unlink($path);
        }
        
        return @rmdir($dir);
    }

    /**
     * Generate PDF from HTML using Chrome headless
     */
    private function generatePdf(string $htmlContent, string $outputPath): bool
    {
        try {
            // Find Chrome executable
            $chromePath = $this->findChromeExecutable();
            if (!$chromePath) {
                throw new \Exception('Chrome/Chromium executable not found. Please install Chrome or Chromium browser.');
            }
            
            // Create temporary HTML file
            $tempHtmlPath = tempnam(sys_get_temp_dir(), 'cert_') . '.html';
            file_put_contents($tempHtmlPath, $htmlContent);
            
            // Convert to file:// URL
            $fileUrl = $this->convertToFileUrl($tempHtmlPath);
            
            // Create a temporary directory for Chrome user data (required for headless mode on Windows)
            $userDataDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'chrome_user_data_' . uniqid();
            if (!is_dir($userDataDir)) {
                mkdir($userDataDir, 0777, true);
            }
            
            // Chrome command for PDF generation
            // Use --print-to-pdf option which writes directly to file
            $chromeOptions = [
                '--headless=new',
                '--no-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--disable-software-rasterizer',
                '--run-all-compositor-stages-before-draw',
                '--virtual-time-budget=5000',
                '--user-data-dir=' . escapeshellarg($userDataDir),
                '--print-to-pdf=' . escapeshellarg($outputPath),
                '--print-to-pdf-no-header',
            ];
            
            // Build command with proper escaping
            $command = escapeshellarg($chromePath);
            foreach ($chromeOptions as $option) {
                $command .= ' ' . $option;
            }
            $command .= ' ' . escapeshellarg($fileUrl);
            
            Log::debug('Running Chrome command for PDF generation', ['command' => $command]);
            
            // Run Chrome command
            // On Windows, wrap with cmd /c if needed
            if (PHP_OS_FAMILY === 'Windows') {
                $command = "cmd /c \"{$command}\"";
            }
            
            $result = Process::run($command);
            
            // Clean up temporary HTML file
            if (file_exists($tempHtmlPath)) {
                unlink($tempHtmlPath);
            }
            
            // Clean up Chrome user data directory (with retry for Windows file locks)
            if (is_dir($userDataDir)) {
                // Wait a moment for Chrome to release the directory
                usleep(500000); // 0.5 seconds
                
                // Try to remove directory and its contents
                $this->removeDirectory($userDataDir);
            }
            
            if ($result->failed()) {
                Log::error('Chrome PDF generation failed', [
                    'exit_code' => $result->exitCode(),
                    'error_output' => $result->errorOutput(),
                    'output' => $result->output()
                ]);
                return false;
            }
            
            // Check if PDF was created
            if (!file_exists($outputPath)) {
                Log::error('PDF file was not created at: ' . $outputPath);
                return false;
            }
            
            Log::info('PDF generated successfully', ['path' => $outputPath, 'size' => filesize($outputPath)]);
            return true;
            
        } catch (\Exception $e) {
            Log::error('PDF generation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Main method to generate certificate
     * Returns same structure as Python script: {success, digital_signature, pdf_path, verification_url, certificate_number}
     */
    public function generateCertificate(array $userData, array $examData, array $trainingData): array
    {
        try {
            // Generate digital signature
            $digitalSignature = $this->generateDigitalSignature($userData, $examData);
            
            // Create HTML certificate
            $htmlContent = $this->createCertificateHtml(
                $userData, 
                $examData, 
                $trainingData, 
                $digitalSignature
            );
            
            // Generate PDF
            $pdfFileName = "certificate_{$digitalSignature}.pdf";
            $pdfPath = $this->outputDir . DIRECTORY_SEPARATOR . $pdfFileName;
            
            $success = $this->generatePdf($htmlContent, $pdfPath);
            
            if ($success) {
                return [
                    'success' => true,
                    'digital_signature' => $digitalSignature,
                    'pdf_path' => "certificates/{$pdfFileName}",
                    'verification_url' => url("/api/certificates/verify-page/{$digitalSignature}"),
                    'certificate_number' => sprintf("AZ-%d-%06d", Carbon::now()->year, $userData['id']),
                ];
            } else {
                return ['success' => false, 'error' => 'PDF generation failed'];
            }
        } catch (\Exception $e) {
            Log::error('Certificate generation error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

