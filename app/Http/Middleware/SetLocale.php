<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Services\TranslationHelper;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get language from request
        $lang = TranslationHelper::getCurrentLanguage();

        // Set application locale
        App::setLocale($lang);

        // Add language to request for easy access
        $request->merge(['current_lang' => $lang]);

        return $next($request);
    }
}

