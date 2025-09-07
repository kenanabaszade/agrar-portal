<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Google Meet Integration Test Page
Route::get('/google-meet-test', function () {
    return view('google-meet-test');
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

// OAuth2 callback handler for Google (exclude from auth middleware)
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
    
    try {
        // Exchange the authorization code for an access token
        $googleCalendarService = app(\App\Services\GoogleCalendarService::class);
        $tokenData = $googleCalendarService->fetchAccessToken($code);
        
        if (isset($tokenData['error'])) {
            return view('oauth2-error', [
                'error' => $tokenData['error'],
                'message' => 'Failed to exchange authorization code for access token: ' . ($tokenData['error_description'] ?? $tokenData['error'])
            ]);
        }
        
        // Store the token data in session for the test page
        session([
            'oauth2_code' => $code, 
            'oauth2_state' => $state,
            'oauth2_token' => $tokenData,
            'oauth2_success' => true
        ]);
        
        return view('oauth2-success', [
            'code' => $code,
            'message' => 'Authorization successful! Access token obtained and stored.',
            'token_received' => true,
            'access_token' => isset($tokenData['access_token']) ? 'Received' : 'Not received'
        ]);
        
    } catch (\Exception $e) {
        return view('oauth2-error', [
            'error' => 'Token exchange failed',
            'message' => 'Failed to exchange authorization code: ' . $e->getMessage()
        ]);
    }
})->withoutMiddleware(['auth']);

