<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\FormatPagination;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Register role middleware alias
        Route::aliasMiddleware('role', \App\Http\Middleware\EnsureUserHasRole::class);

        // Apply pagination formatting to all API routes
        app('router')->pushMiddlewareToGroup('api', FormatPagination::class);
    }
}
