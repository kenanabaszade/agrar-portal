<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Add a simple login route to prevent RouteNotFoundException
Route::get('/login', function () {
    return response()->json([
        'message' => 'Please use the API endpoints for authentication',
        'api_endpoints' => [
            'login' => '/api/v1/auth/login',
            'register' => '/api/v1/auth/register',
            'google_auth' => '/api/v1/google/auth-url'
        ]
    ]);
})->name('login');

// OAuth2 callback handler for Google
Route::get('/oauth2/callback', function (Request $request) {
    $code = $request->get('code');
    $state = $request->get('state');
    $error = $request->get('error');
    
    if ($error) {
        return view('oauth2-error', [
            'error' => $error,
            'message' => 'OAuth2 authorization failed. Please try again.'
        ]);
    }
    
    if (!$code) {
        return view('oauth2-error', [
            'error' => 'No authorization code',
            'message' => 'Google did not provide an authorization code.'
        ]);
    }
    
    // Store the code in session and redirect to a success page
    session(['oauth2_code' => $code, 'oauth2_state' => $state]);
    
    return view('oauth2-success', [
        'code' => $code,
        'message' => 'Authorization successful! You can now close this window and return to your application.'
    ]);
});
