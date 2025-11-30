<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IncreasePostSize
{
    /**
     * Handle an incoming request.
     * Increase PHP upload limits before ValidatePostSize middleware runs
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Increase PHP limits for file uploads (especially for lessons/lessons upload-temp-media)
        if ($request->is('api/v1/lessons/*') || $request->is('api/v1/trainings/*')) {
            @ini_set('upload_max_filesize', '105M'); // 100MB + buffer
            @ini_set('post_max_size', '110M'); // Must be larger than upload_max_filesize
            @ini_set('memory_limit', '512M');
            @ini_set('max_execution_time', '600');
            @ini_set('max_input_time', '600');
        }
        
        return $next($request);
    }
}

