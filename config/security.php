<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    |
    | Configure security headers for your application
    |
    */

    'headers' => [
        'X-Frame-Options' => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:;",
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Security
    |--------------------------------------------------------------------------
    |
    | Configure webhook secret for payment processing
    |
    */

    'webhook_secret' => env('WEBHOOK_SECRET', 'your-secret-key-here'),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for various endpoints
    |
    */

    'rate_limits' => [
        'login' => [
            'attempts' => 5,
            'decay_minutes' => 15,
        ],
        'register' => [
            'attempts' => 5,
            'decay_minutes' => 60,
        ],
        'api' => [
            'attempts' => 60,
            'decay_minutes' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Security
    |--------------------------------------------------------------------------
    |
    | Configure file upload restrictions
    |
    */

    'file_upload' => [
        'max_size' => 25600, // 25MB in KB
        'allowed_types' => [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'video' => ['mp4', 'avi', 'mov', 'wmv'],
            'audio' => ['mp3', 'wav', 'ogg', 'm4a'],
            'document' => ['pdf', 'doc', 'docx', 'txt'],
        ],
    ],
];
