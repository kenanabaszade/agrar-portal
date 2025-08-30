<?php
 
namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\OtpNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
 
class AuthController extends Controller
{
    
    
    public function register(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8'],
            'user_type' => ['nullable', Rule::in(['farmer', 'trainer', 'admin'])],
            'region' => ['nullable', 'string', 'max:255'],
        ]);

        // Check if user already exists but not verified
        $existingUser = User::where('email', $validated['email'])->first();
        
        if ($existingUser && !$existingUser->email_verified) {
            // Resend OTP for existing unverified user
            $otp = $this->generateOtp();
            $existingUser->update([
                'otp_code' => $otp,
                'otp_expires_at' => Carbon::now()->addMinutes(10),
            ]);
            
            Notification::send($existingUser, new OtpNotification($otp));
            
            return response()->json([
                'message' => 'OTP sent to your email. Please check your inbox and verify your account.',
                'email' => $validated['email'],
            ], 200);
        }

        // Create new user
        $user = new User();
        $user->first_name = $validated['first_name'];
        $user->last_name = $validated['last_name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        $user->region = $validated['region'] ?? null;
        $user->password_hash = Hash::make($validated['password']);
        $user->user_type = $validated['user_type'] ?? 'farmer';
        $user->email_verified = false;
        $user->save();

        // Generate and send OTP
        $otp = $this->generateOtp();
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        Notification::send($user, new OtpNotification($otp));

        return response()->json([
            'message' => 'Registration successful! Please check your email for OTP verification.',
            'email' => $validated['email'],
        ], 201);
    }

    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->email_verified) {
            return response()->json(['message' => 'Email already verified'], 400);
        }

        if (!$user->otp_code || !$user->otp_expires_at) {
            return response()->json(['message' => 'No OTP found. Please request a new one.'], 400);
        }

        if (Carbon::now()->isAfter($user->otp_expires_at)) {
            return response()->json(['message' => 'OTP has expired. Please request a new one.'], 400);
        }

        if ($user->otp_code !== $validated['otp']) {
            return response()->json(['message' => 'Invalid OTP code'], 422);
        }

        // Verify the user and enable 2FA
        $user->update([
            'email_verified' => true,
            'email_verified_at' => Carbon::now(),
            'two_factor_enabled' => true,
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'message' => 'Email verified successfully!',
            'user' => $user,
            'token' => $token,
        ], 200);
    }

    public function resendOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->email_verified) {
            return response()->json(['message' => 'Email already verified'], 400);
        }

        // Generate new OTP
        $otp = $this->generateOtp();
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        Notification::send($user, new OtpNotification($otp));

        return response()->json([
            'message' => 'New OTP sent to your email',
        ], 200);
    }

    public function enableTwoFactor(Request $request)
    {
        $user = $request->user();
        
        if ($user->two_factor_enabled) {
            return response()->json(['message' => '2FA is already enabled'], 400);
        }

        // Generate and send OTP for 2FA activation
        $otp = $this->generateOtp();
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        Notification::send($user, new OtpNotification($otp));

        return response()->json([
            'message' => 'OTP sent to your email. Please verify to enable 2FA.',
        ], 200);
    }

    public function verifyTwoFactorActivation(Request $request)
    {
        $validated = $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();

        if ($user->two_factor_enabled) {
            return response()->json(['message' => '2FA is already enabled'], 400);
        }

        if (!$user->otp_code || !$user->otp_expires_at) {
            return response()->json(['message' => 'No OTP found. Please request a new one.'], 400);
        }

        if (Carbon::now()->isAfter($user->otp_expires_at)) {
            return response()->json(['message' => 'OTP has expired. Please request a new one.'], 400);
        }

        if ($user->otp_code !== $validated['otp']) {
            return response()->json(['message' => 'Invalid OTP code'], 422);
        }

        // Enable 2FA
        $user->update([
            'two_factor_enabled' => true,
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        return response()->json([
            'message' => '2FA enabled successfully!',
            'user' => $user,
        ], 200);
    }

    public function disableTwoFactor(Request $request)
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (!$user->two_factor_enabled) {
            return response()->json(['message' => '2FA is not enabled'], 400);
        }

        // Verify user's password instead of sending email OTP
        if (!Hash::check($validated['password'], (string) $user->password_hash)) {
            return response()->json(['message' => 'Invalid password'], 422);
        }

        // Disable 2FA immediately after password verification
        $user->update([
            'two_factor_enabled' => false,
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        return response()->json([
            'message' => '2FA disabled successfully!',
            'user' => $user,
        ], 200);
    }

    public function getTwoFactorStatus(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'two_factor_enabled' => $user->two_factor_enabled,
            'email_verified' => $user->email_verified,
        ], 200);
    }



    private function generateOtp()
    {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        /** @var User|null $user */
        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], (string) $user->password_hash)) {
            return response()->json(['message' => 'Invalid credentials'], 422);
        }

        if (!$user->email_verified) {
            return response()->json([
                'message' => 'Please verify your email first. Check your inbox for OTP code.',
                'email' => $user->email,
                'needs_verification' => true
            ], 422);
        }

        // Check if 2FA is enabled for this user
        if ($user->two_factor_enabled) {
            // Generate and send OTP for 2FA verification
            $otp = $this->generateOtp();
            $user->update([
                'otp_code' => $otp,
                'otp_expires_at' => Carbon::now()->addMinutes(10),
            ]);

            Notification::send($user, new OtpNotification($otp));

            return response()->json([
                'message' => '2FA verification required. Please check your email for OTP code.',
                'email' => $user->email,
                'needs_2fa' => true,
                'user_id' => $user->id,
            ], 200);
        }

        // Clear any existing OTP codes when 2FA is disabled
        if ($user->otp_code || $user->otp_expires_at) {
            $user->update([
                'otp_code' => null,
                'otp_expires_at' => null,
            ]);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json(['message' => 'If a user with that email address exists, we will send a password reset link.'], 200);
        }

        // Generate password reset token
        $token = Str::random(64);
        
        // Store the token in password_reset_tokens table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => $token,
                'created_at' => Carbon::now(),
            ]
        );

        // Generate OTP for 2FA verification
        $otp = $this->generateOtp();
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        // Send OTP notification
        Notification::send($user, new OtpNotification($otp));

        return response()->json([
            'message' => 'Password reset OTP sent to your email. Please check your inbox.',
            'email' => $user->email,
        ], 200);
    }

    public function verifyPasswordResetOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if (!$user->otp_code || !$user->otp_expires_at) {
            return response()->json(['message' => 'No OTP found. Please request a new one.'], 400);
        }

        if (Carbon::now()->isAfter($user->otp_expires_at)) {
            return response()->json(['message' => 'OTP has expired. Please request a new one.'], 400);
        }

        if ($user->otp_code !== $validated['otp']) {
            return response()->json(['message' => 'Invalid OTP code'], 422);
        }

        // Get the password reset token
        $resetToken = DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->first();

        if (!$resetToken) {
            return response()->json(['message' => 'No password reset request found. Please try again.'], 400);
        }

        // Clear the OTP after successful verification
        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        return response()->json([
            'message' => 'OTP verified successfully. You can now reset your password.',
            'token' => $resetToken->token,
            'email' => $user->email,
        ], 200);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Verify the reset token
        $resetToken = DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->where('token', $validated['token'])
            ->first();

        if (!$resetToken) {
            return response()->json(['message' => 'Invalid or expired reset token'], 400);
        }

        // Check if token is not expired (24 hours)
        if (Carbon::now()->diffInHours($resetToken->created_at) > 24) {
            DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();
            return response()->json(['message' => 'Reset token has expired. Please request a new one.'], 400);
        }

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Update the password
        $user->update([
            'password_hash' => Hash::make($validated['password']),
        ]);

        // Delete the used reset token
        DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

        return response()->json([
            'message' => 'Password reset successfully. You can now login with your new password.',
        ], 200);
    }

    public function resendPasswordResetOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json(['message' => 'If a user with that email address exists, we will send a password reset OTP.'], 200);
        }

        // Check if there's an existing reset token
        $resetToken = DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->first();

        if (!$resetToken) {
            return response()->json(['message' => 'No password reset request found. Please request a new password reset.'], 400);
        }

        // Generate new OTP
        $otp = $this->generateOtp();
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        Notification::send($user, new OtpNotification($otp));

        return response()->json([
            'message' => 'New password reset OTP sent to your email.',
        ], 200);
    }

    public function verifyLoginOtp(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $user = User::find($validated['user_id']);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if (!$user->two_factor_enabled) {
            return response()->json(['message' => '2FA is not enabled for this user'], 400);
        }

        if (!$user->otp_code || !$user->otp_expires_at) {
            return response()->json(['message' => 'No OTP found. Please login again.'], 400);
        }

        if (Carbon::now()->isAfter($user->otp_expires_at)) {
            return response()->json(['message' => 'OTP has expired. Please login again.'], 400);
        }

        if ($user->otp_code !== $validated['otp']) {
            return response()->json(['message' => 'Invalid OTP code'], 422);
        }

        // Clear the OTP after successful verification
        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        // Generate token for successful login
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'message' => '2FA verification successful!',
            'user' => $user,
            'token' => $token,
        ], 200);
    }

    public function resendLoginOtp(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
        ]);

        $user = User::find($validated['user_id']);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if (!$user->two_factor_enabled) {
            return response()->json(['message' => '2FA is not enabled for this user'], 400);
        }

        // Generate new OTP
        $otp = $this->generateOtp();
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        Notification::send($user, new OtpNotification($otp));

        return response()->json([
            'message' => 'New 2FA OTP sent to your email.',
        ], 200);
    }
}
 
 
 