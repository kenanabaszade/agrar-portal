<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['user_id', 'action', 'entity', 'entity_id', 'details', 'created_at'];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
    ];
}


