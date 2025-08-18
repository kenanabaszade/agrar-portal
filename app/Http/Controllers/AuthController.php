<?php
 
namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\OtpNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
 
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
}
 
 
 