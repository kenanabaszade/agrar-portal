<?php

namespace App\Http\Controllers;

use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GoogleAuthController extends Controller
{
    private GoogleCalendarService $googleCalendarService;

    public function __construct(GoogleCalendarService $googleCalendarService)
    {
        $this->googleCalendarService = $googleCalendarService;
    }

    /**
     * Get Google OAuth2 authorization URL
     */
    public function getAuthUrl(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $userId = $user->id;
            
            // Include user ID in state parameter
            $authUrl = $this->googleCalendarService->getAuthUrl($userId);
            
            return response()->json([
                'success' => true,
                'auth_url' => $authUrl,
                'message' => 'Please visit this URL to authorize Google Calendar access'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle Google OAuth2 callback and store access token
     */
    public function handleCallback(Request $request): JsonResponse
    {
        try {
            $code = $request->get('code');
            $state = $request->get('state');
            $error = $request->get('error');
            
            // Debug information
            \Log::info('Google OAuth2 Callback', [
                'code' => $code,
                'state' => $state,
                'error' => $error,
                'all_params' => $request->all()
            ]);
            
            if ($error) {
                return response()->json([
                    'success' => false,
                    'error' => 'OAuth2 error: ' . $error
                ], 400);
            }
            
            if (!$code) {
                return response()->json([
                    'success' => false,
                    'error' => 'Authorization code not provided',
                    'debug' => [
                        'received_params' => $request->all(),
                        'code' => $code,
                        'state' => $state
                    ]
                ], 400);
            }

            $tokenData = $this->googleCalendarService->fetchAccessToken($code);
            
            if (isset($tokenData['error'])) {
                return response()->json([
                    'success' => false,
                    'error' => $tokenData['error_description'] ?? $tokenData['error']
                ], 400);
            }

            // Get user ID from state parameter
            $state = $request->get('state');
            $user = null;
            
            if ($state && is_numeric($state)) {
                $user = \App\Models\User::find($state);
            }
            
            if ($user) {
                $user->update([
                    'google_access_token' => json_encode($tokenData)
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Google Calendar access authorized successfully',
                    'user' => $user->fresh()
                ]);
            }

            // Fallback: Store token in session
            session([
                'oauth2_code' => $code,
                'oauth2_state' => $state,
                'oauth2_token' => $tokenData,
                'oauth2_success' => true
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Google Calendar access authorized successfully. Token stored in session.',
                'token_received' => true,
                'user_stored' => false
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if the authenticated user has Google Calendar access
     */
    public function checkAccess(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'User not authenticated'
            ], 401);
        }

        // Check if user has stored access token
        $hasStoredToken = !empty($user->google_access_token);
        
        // Check if there's a token in session (from OAuth2 callback)
        $hasSessionToken = session('oauth2_success') && session('oauth2_token');
        
        // Also check if there's a token in the request session (for API calls)
        if (!$hasSessionToken && $request->hasSession()) {
            $hasSessionToken = $request->session()->has('oauth2_success') && $request->session()->has('oauth2_token');
        }
        
        $hasAccess = $hasStoredToken || $hasSessionToken;
        
        // If we have a session token but no stored token, store it
        if ($hasSessionToken && !$hasStoredToken) {
            $tokenData = session('oauth2_token');
            $user->update([
                'google_access_token' => json_encode($tokenData)
            ]);
            
            // Clear session data
            session()->forget(['oauth2_code', 'oauth2_state', 'oauth2_token', 'oauth2_success']);
        }
        
        // If we have a stored token, assume it's valid for now
        $tokenValid = $hasStoredToken;
        if ($hasStoredToken) {
            // Skip validation for now - assume token is valid if it exists
            $tokenValid = true;
        }

        return response()->json([
            'success' => true,
            'has_access' => $hasAccess,
            'has_stored_token' => $hasStoredToken,
            'has_session_token' => $hasSessionToken,
            'token_valid' => $tokenValid,
            'can_create_meetings' => $hasAccess && $tokenValid,
            'user' => $user->fresh()
        ]);
    }

    /**
     * Revoke Google Calendar access for the authenticated user
     */
    public function revokeAccess(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated'
                ], 401);
            }

            // Clear the stored access token
            $user->update(['google_access_token' => null]);
            
            return response()->json([
                'success' => true,
                'message' => 'Google Calendar access revoked successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get OAuth2 code from session (after user completes authorization)
     */
    public function getOAuth2Code(): JsonResponse
    {
        try {
            $code = session('oauth2_code');
            $state = session('oauth2_state');
            $token = session('oauth2_token');
            $success = session('oauth2_success');
            
            if (!$code && !$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'No OAuth2 data found in session. Please complete the authorization process first.'
                ], 404);
            }
            
            $response = [
                'success' => true,
                'message' => 'OAuth2 data retrieved successfully'
            ];
            
            if ($code) {
                $response['code'] = $code;
                $response['state'] = $state;
            }
            
            if ($success && $token) {
                $response['token_received'] = true;
                $response['has_access_token'] = isset($token['access_token']);
            }
            
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}