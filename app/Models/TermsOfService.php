<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasTranslations;

class TermsOfService extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    protected $table = 'terms_of_service';

    protected $translatable = ['content'];

    protected $fillable = [
        'content',
        'is_active',
        'version',
        'effective_date',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'content' => 'array',
        'is_active' => 'boolean',
        'version' => 'integer',
        'effective_date' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
