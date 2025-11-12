<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'related_training_id', 'related_exam_id', 'certificate_number', 'issue_date', 'expiry_date', 'issuer_name', 'issuer_logo_url', 'digital_signature', 'qr_code', 'qr_code_path', 'pdf_url', 'pdf_path', 'photo_path', 'status'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function training()
    {
        return $this->belongsTo(Training::class, 'related_training_id');
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class, 'related_exam_id');
    }

    /**
     * Generate QR code for certificate verification (Simple SVG)
     */
    public function generateQrCode()
    {
        $verificationUrl = url('/api/v1/certificates/' . $this->certificate_number . '/verify');
        
        // Simple QR code using Google Charts API (fallback)
        $qrCodeUrl = 'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=' . urlencode($verificationUrl);
        
        // Return as img tag
        return '<img src="' . $qrCodeUrl . '" alt="QR Code" style="width: 100px; height: 100px;" />';
    }

    /**
     * Get verification URL
     */
    public function getVerificationUrlAttribute()
    {
        return url('/api/v1/certificates/' . $this->certificate_number . '/verify');
    }

    /**
     * Check if certificate is expired
     */
    public function isExpired()
    {
        if (!$this->expiry_date) {
            return false;
        }
        
        return now()->greaterThan($this->expiry_date);
    }

    /**
     * Check if certificate is active
     */
    public function isActive()
    {
        return $this->status === 'active' && !$this->isExpired();
    }

    /**
     * Get PDF URL
     */
    public function getPdfUrlAttribute($value)
    {
        // First check if pdf_path exists (preferred)
        if ($this->pdf_path) {
            // Get full URL from storage path
            $storageUrl = Storage::url($this->pdf_path);
            // Ensure it's a full URL
            return url($storageUrl);
        }
        
        // Fallback to pdf_url field if exists
        if ($value) {
            // If value already contains full URL, return as is
            if (strpos($value, 'http://') === 0 || strpos($value, 'https://') === 0) {
                return $value;
            }
            // If it starts with /, use url() to get full URL
            if (strpos($value, '/') === 0) {
                return url($value);
            }
            // Otherwise, prepend base URL
            return url($value);
        }
        
        return null;
    }

    /**
     * Get download URL
     */
    public function getDownloadUrlAttribute()
    {
        if ($this->pdf_path) {
            $storageUrl = Storage::url($this->pdf_path);
            // Return full URL
            return url($storageUrl);
        }
        return null;
    }

    /**
     * Get preview URL (same as download URL)
     */
    public function getPreviewUrlAttribute()
    {
        return $this->download_url;
    }

    /**
     * Get QR code image URL
     */
    public function getQrCodeUrlAttribute()
    {
        if ($this->qr_code_path) {
            return Storage::url($this->qr_code_path);
        }
        return null;
    }

    /**
     * Generate and save QR code as PNG image
     */
    public function generateAndSaveQrCode()
    {
        $verificationUrl = url('/api/v1/certificates/' . $this->certificate_number . '/verify');
        
        // Generate QR code using Endroid\QrCode library
        $result = (new Builder(
            writer: new PngWriter(),
            writerOptions: [],
            validateResult: false,
            data: $verificationUrl,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10
        ))->build();

        // Generate filename
        $fileName = 'qr_' . $this->certificate_number . '.png';
        $directory = 'certificates/qr_codes';
        $fullPath = $directory . '/' . $fileName;

        // Ensure directory exists
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        // Save QR code image
        Storage::disk('public')->put($fullPath, $result->getString());

        // Update certificate with QR code path
        $this->update([
            'qr_code_path' => $fullPath
        ]);

        return $fullPath;
    }
}


