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
    public function getAuthUrl(): JsonResponse
    {
        try {
            $authUrl = $this->googleCalendarService->getAuthUrl();
            
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
            
            if (!$code) {
                return response()->json([
                    'success' => false,
                    'error' => 'Authorization code not provided'
                ], 400);
            }

            $tokenData = $this->googleCalendarService->fetchAccessToken($code);
            
            if (isset($tokenData['error'])) {
                return response()->json([
                    'success' => false,
                    'error' => $tokenData['error_description'] ?? $tokenData['error']
                ], 400);
            }

            // Store the access token for the authenticated user
            $user = $request->user();
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

            return response()->json([
                'success' => false,
                'error' => 'User not authenticated'
            ], 401);

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

        $hasAccess = !empty($user->google_access_token);
        
        return response()->json([
            'success' => true,
            'has_access' => $hasAccess,
            'user' => $user
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
            
            if (!$code) {
                return response()->json([
                    'success' => false,
                    'message' => 'No OAuth2 code found in session. Please complete the authorization process first.'
                ], 404);
            }
            
            // Clear the code from session after retrieving it
            session()->forget(['oauth2_code', 'oauth2_state']);
            
            return response()->json([
                'success' => true,
                'code' => $code,
                'state' => $state,
                'message' => 'OAuth2 code retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}