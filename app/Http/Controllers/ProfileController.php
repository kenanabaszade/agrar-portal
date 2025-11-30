<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\EmailChangeRequest;
use App\Notifications\OtpNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class ProfileController extends Controller
{
    /**
     * Get current user's profile
     */
    public function show(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'user' => $user,
        ], 200);
    }

    /**
     * Update user profile (basic info without email/password)
     */
    public function update(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'username' => [
                'sometimes', 
                'string', 
                'max:255',
                Rule::unique('users', 'username')->ignore($user->id)
            ],
            'father_name' => ['sometimes', 'string', 'max:255'],
            'region' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:50'],
            'birth_date' => ['sometimes', 'date'],
            'gender' => ['sometimes', 'string', 'max:50'],
            'user_type' => ['sometimes', 'in:farmer,agronom,veterinary,government,entrepreneur,researcher,student'],
        ]);

        if (array_key_exists('user_type', $validated)) {
            $requestedType = (string) $validated['user_type'];
            $currentType = (string) $user->user_type;

            if ($currentType !== $requestedType) {
                // Non-admin users cannot elevate themselves to admin or trainer.
                if ($currentType !== 'admin' && in_array($requestedType, ['admin', 'trainer'], true)) {
                    return response()->json([
                        'message' => 'You are not allowed to change the user type to the requested role.',
                    ], 403);
                }
            }
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user,
        ], 200);
    }

    /**
     * Change password with old password verification
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8'],
            'new_password_confirmation' => ['required', 'same:new_password'],
        ]);

        // Verify current password
        if (!Hash::check($validated['current_password'], (string) $user->password_hash)) {
            return response()->json([
                'message' => 'Current password is incorrect'
            ], 422);
        }

        // Update password
        $user->update([
            'password_hash' => Hash::make($validated['new_password']),
        ]);

        return response()->json([
            'message' => 'Password changed successfully',
        ], 200);
    }

    /**
     * Confirm password before sensitive actions (like account deletion)
     */
    public function confirmPassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (!Hash::check($validated['password'], (string) $user->password_hash)) {
            return response()->json([
                'message' => 'Password is incorrect',
            ], 422);
        }

        return response()->json([
            'message' => 'Password confirmed successfully',
        ], 200);
    }

    /**
     * Delete authenticated user's account
     */
    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (!Hash::check($validated['password'], (string) $user->password_hash)) {
            return response()->json([
                'message' => 'Password is incorrect',
            ], 422);
        }

        // Delete tokens to revoke access
        $user->tokens()->delete();

        // Delete pending email change requests
        $user->emailChangeRequests()->delete();

        $user->delete();

        return response()->json([
            'message' => 'Account deleted successfully',
        ], 200);
    }

    /**
     * Request email change with OTP verification
     */
    public function requestEmailChange(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized. Please login first.'
                ], 401);
            }
            
            $validated = $request->validate([
                'new_email' => ['required', 'email', 'unique:users,email'],
                'password' => ['required', 'string'],
            ]);

            // Verify current password
            if (!Hash::check($validated['password'], (string) $user->password_hash)) {
                return response()->json([
                    'message' => 'Password is incorrect'
                ], 422);
            }

            // Check if email change is already in progress
            $existingRequest = EmailChangeRequest::where('user_id', $user->id)
                ->where('otp_expires_at', '>', Carbon::now())
                ->first();

            // If there's an active request for the same email, just resend OTP
            if ($existingRequest && $existingRequest->new_email === $validated['new_email']) {
                // Generate new OTP and extend expiration
                $otp = $this->generateOtp();
                $existingRequest->update([
                    'otp_code' => $otp,
                    'otp_expires_at' => Carbon::now()->addMinutes(10),
                ]);

                // Resend OTP to new email (bypass notification preferences)
                // Using notifyNow() with tempUser (not loaded from DB) bypasses preference checks
                try {
                    $tempUser = new User();
                    $tempUser->email = $validated['new_email'];
                    $tempUser->first_name = $user->first_name;
                    $tempUser->last_name = $user->last_name;
                    
                    // notifyNow() sends immediately without queue and without checking preferences
                    $tempUser->notifyNow(new OtpNotification($otp));
                } catch (\Exception $emailException) {
                    Log::error('Failed to resend email change OTP notification', [
                        'user_id' => $user->id,
                        'new_email' => $validated['new_email'],
                        'error' => $emailException->getMessage(),
                        'trace' => $emailException->getTraceAsString()
                    ]);
                }

                return response()->json([
                    'message' => 'New OTP sent to email address. Please verify to complete email change.',
                    'new_email' => $validated['new_email'],
                ], 200);
            }

            // If there's an active request for different email, cancel it and create new one
            if ($existingRequest) {
                $existingRequest->delete();
            }

            // Delete any expired requests
            EmailChangeRequest::where('user_id', $user->id)
                ->where('otp_expires_at', '<=', Carbon::now())
                ->delete();

            // Generate OTP for email change
            $otp = $this->generateOtp();
            
            // Create email change request
            $emailChangeRequest = EmailChangeRequest::create([
                'user_id' => $user->id,
                'new_email' => $validated['new_email'],
                'otp_code' => $otp,
                'otp_expires_at' => Carbon::now()->addMinutes(10),
            ]);

            // Send OTP to new email (send immediately, bypass notification preferences)
            // Email change OTP is critical and must be sent regardless of user preferences
            // Using notifyNow() with tempUser (not loaded from DB) bypasses preference checks
            try {
                $tempUser = new User();
                $tempUser->email = $validated['new_email'];
                $tempUser->first_name = $user->first_name;
                $tempUser->last_name = $user->last_name;
                
                // notifyNow() sends immediately without queue and without checking preferences
                // Since tempUser is not loaded from DB, it has no preferences to check
                $tempUser->notifyNow(new OtpNotification($otp));
            } catch (\Exception $emailException) {
                // Log email error but don't fail the request
                Log::error('Failed to send email change OTP notification', [
                    'user_id' => $user->id,
                    'new_email' => $validated['new_email'],
                    'error' => $emailException->getMessage(),
                    'trace' => $emailException->getTraceAsString()
                ]);
            }

            return response()->json([
                'message' => 'OTP sent to new email address. Please verify to complete email change.',
                'new_email' => $validated['new_email'],
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error requesting email change', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'An error occurred while processing your request. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Verify email change OTP
     */
    public function verifyEmailChange(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        // Find the email change request
        $emailChangeRequest = EmailChangeRequest::where('user_id', $user->id)
            ->where('otp_code', $validated['otp'])
            ->where('otp_expires_at', '>', Carbon::now())
            ->first();

        if (!$emailChangeRequest) {
            return response()->json([
                'message' => 'Invalid or expired OTP code. Please request a new email change.'
            ], 400);
        }

        // Update user email
        $user->update([
            'email' => $emailChangeRequest->new_email,
        ]);

        // Delete the email change request
        $emailChangeRequest->delete();

        return response()->json([
            'message' => 'Email changed successfully',
            'user' => $user,
        ], 200);
    }

    /**
     * Resend email change OTP
     */
    public function resendEmailChangeOtp(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized. Please login first.'
                ], 401);
            }
            
            // Find existing email change request (even if expired, we'll renew it)
            $emailChangeRequest = EmailChangeRequest::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$emailChangeRequest) {
                return response()->json([
                    'message' => 'No email change request found. Please request email change first.'
                ], 400);
            }

            // Generate new OTP and extend expiration
            $otp = $this->generateOtp();
            $emailChangeRequest->update([
                'otp_code' => $otp,
                'otp_expires_at' => Carbon::now()->addMinutes(10),
            ]);

            // Send OTP to new email (send immediately, bypass notification preferences)
            // Email change OTP is critical and must be sent regardless of user preferences
            // Using notifyNow() with tempUser (not loaded from DB) bypasses preference checks
            try {
                $tempUser = new User();
                $tempUser->email = $emailChangeRequest->new_email;
                $tempUser->first_name = $user->first_name;
                $tempUser->last_name = $user->last_name;
                
                // notifyNow() sends immediately without queue and without checking preferences
                // Since tempUser is not loaded from DB, it has no preferences to check
                $tempUser->notifyNow(new OtpNotification($otp));
            } catch (\Exception $emailException) {
                // Log email error but don't fail the request
                Log::error('Failed to resend email change OTP notification', [
                    'user_id' => $user->id,
                    'new_email' => $emailChangeRequest->new_email,
                    'error' => $emailException->getMessage(),
                    'trace' => $emailException->getTraceAsString()
                ]);
            }

            return response()->json([
                'message' => 'New OTP sent to new email address.',
                'new_email' => $emailChangeRequest->new_email,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error resending email change OTP', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'An error occurred while resending OTP. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Cancel email change request
     */
    public function cancelEmailChange(Request $request)
    {
        $user = $request->user();
        
        // Delete any email change requests for this user
        $deleted = EmailChangeRequest::where('user_id', $user->id)->delete();

        if ($deleted === 0) {
            return response()->json([
                'message' => 'No email change request found.'
            ], 400);
        }

        return response()->json([
            'message' => 'Email change request cancelled.',
        ], 200);
    }

    /**
     * Upload profile photo
     */
    public function uploadProfilePhoto(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'profile_photo' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'], // 2MB max
        ]);

        // Delete old profile photo if exists
        if ($user->profile_photo) {
            Storage::disk('public')->delete('profile_photos/' . $user->profile_photo);
        }

        // Store new photo
        $file = $request->file('profile_photo');
        $filename = 'user_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('profile_photos', $filename, 'public');

        // Update user profile_photo field
        $user->update([
            'profile_photo' => $filename
        ]);

        return response()->json([
            'message' => 'Profile photo uploaded successfully',
            'profile_photo_url' => $user->profile_photo_url,
            'user' => $user,
        ], 200);
    }

    /**
     * Delete profile photo
     */
    public function deleteProfilePhoto(Request $request)
    {
        $user = $request->user();
        
        if (!$user->profile_photo) {
            return response()->json([
                'message' => 'No profile photo to delete'
            ], 400);
        }

        // Delete file from storage
        Storage::disk('public')->delete('profile_photos/' . $user->profile_photo);

        // Update user profile_photo field
        $user->update([
            'profile_photo' => null
        ]);

        return response()->json([
            'message' => 'Profile photo deleted successfully',
            'user' => $user,
        ], 200);
    }

    /**
     * Generate OTP code
     */
    private function generateOtp()
    {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
