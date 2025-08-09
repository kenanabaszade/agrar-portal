<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'related_training_id', 'related_exam_id', 'certificate_number', 'issue_date', 'expiry_date', 'issuer_name', 'issuer_logo_url', 'digital_signature', 'qr_code', 'pdf_url', 'status'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}


