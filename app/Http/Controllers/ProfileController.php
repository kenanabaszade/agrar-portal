<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\EmailChangeRequest;
use App\Notifications\OtpNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
            'user_type' => ['sometimes', 'in:farmer,trainer,admin,agronom,veterinary,government,entrepreneur,researcher'],
        ]);

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
     * Request email change with OTP verification
     */
    public function requestEmailChange(Request $request)
    {
        $user = $request->user();
        
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

        if ($existingRequest) {
            return response()->json([
                'message' => 'Email change already in progress. Please wait for the current OTP to expire or verify it first.'
            ], 400);
        }

        // Delete any expired requests
        EmailChangeRequest::where('user_id', $user->id)->delete();

        // Generate OTP for email change
        $otp = $this->generateOtp();
        
        // Create email change request
        EmailChangeRequest::create([
            'user_id' => $user->id,
            'new_email' => $validated['new_email'],
            'otp_code' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        // Send OTP to new email
        $tempUser = new User();
        $tempUser->email = $validated['new_email'];
        $tempUser->first_name = $user->first_name;
        $tempUser->last_name = $user->last_name;
        
        Notification::send($tempUser, new OtpNotification($otp));

        return response()->json([
            'message' => 'OTP sent to new email address. Please verify to complete email change.',
            'new_email' => $validated['new_email'],
        ], 200);
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
        $user = $request->user();
        
        // Find existing email change request
        $emailChangeRequest = EmailChangeRequest::where('user_id', $user->id)
            ->where('otp_expires_at', '>', Carbon::now())
            ->first();

        if (!$emailChangeRequest) {
            return response()->json([
                'message' => 'No email change request found. Please request email change first.'
            ], 400);
        }

        // Generate new OTP
        $otp = $this->generateOtp();
        $emailChangeRequest->update([
            'otp_code' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        // Send OTP to new email
        $tempUser = new User();
        $tempUser->email = $emailChangeRequest->new_email;
        $tempUser->first_name = $user->first_name;
        $tempUser->last_name = $user->last_name;
        
        Notification::send($tempUser, new OtpNotification($otp));

        return response()->json([
            'message' => 'New OTP sent to new email address.',
        ], 200);
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
