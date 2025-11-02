<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTranslations;

class ServicePackage extends Model
{
    use HasFactory, HasTranslations;

    protected $translatable = ['name', 'description'];

    protected $fillable = [
        'name',
        'description',
        'price',
        'price_type',
        'price_label',
        'is_recommended',
        'features',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'price' => 'decimal:2',
        'is_recommended' => 'boolean',
        'features' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
