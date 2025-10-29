<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'related_training_id', 'related_exam_id', 'certificate_number', 'issue_date', 'expiry_date', 'issuer_name', 'issuer_logo_url', 'digital_signature', 'qr_code', 'pdf_url', 'pdf_path', 'status'
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
        if ($value) {
            return url($value);
        }
        return null;
    }

    /**
     * Get download URL
     */
    public function getDownloadUrlAttribute()
    {
        return url('/api/v1/certificates/' . $this->id . '/download');
    }
}


