<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomValidatePostSize
{
    /**
     * Handle an incoming request.
     * Custom ValidatePostSize that allows larger uploads for lessons/trainings
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip validation for lessons and trainings endpoints (they handle their own limits)
        if ($request->is('api/v1/lessons/*') || $request->is('api/v1/trainings/*')) {
            return $next($request);
        }
        
        // For other endpoints, use Laravel's default ValidatePostSize logic
        // Check if POST data exceeds php.ini post_max_size
        $maxSize = $this->getPostMaxSize();
        
        if ($maxSize > 0 && $request->server('CONTENT_LENGTH') > $maxSize) {
            throw new \Illuminate\Http\Exceptions\PostTooLargeException;
        }
        
        return $next($request);
    }
    
    /**
     * Determine the server 'post_max_size' as bytes.
     */
    protected function getPostMaxSize(): int
    {
        $postMaxSize = ini_get('post_max_size');
        
        if ($postMaxSize === false) {
            return 0;
        }
        
        $postMaxSize = trim($postMaxSize);
        $metric = strtoupper(substr($postMaxSize, -1));
        $postMaxSize = (int) $postMaxSize;
        
        return match ($metric) {
            'K' => $postMaxSize * 1024,
            'M' => $postMaxSize * 1048576,
            'G' => $postMaxSize * 1073741824,
            default => $postMaxSize,
        };
    }
}

