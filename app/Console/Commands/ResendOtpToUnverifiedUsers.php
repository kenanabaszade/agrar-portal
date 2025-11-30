<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\OtpNotification;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ResendOtpToUnverifiedUsers extends Command
{
    protected $signature = 'user:resend-otp {email?}';
    protected $description = 'Resend OTP to unverified users';

    public function handle()
    {
        $email = $this->argument('email');
        
        if ($email) {
            $user = User::where('email', $email)->first();
            if (!$user) {
                $this->error("User not found: {$email}");
                return 1;
            }
            
            if ($user->email_verified) {
                $this->warn("User {$email} is already verified");
                return 0;
            }
            
            $this->resendOtp($user);
        } else {
            $users = User::where('email_verified', false)->get();
            
            if ($users->isEmpty()) {
                $this->info('No unverified users found');
                return 0;
            }
            
            $this->info("Found {$users->count()} unverified users");
            
            foreach ($users as $user) {
                $this->resendOtp($user);
            }
        }
        
        return 0;
    }
    
    private function resendOtp(User $user)
    {
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ]);
        
        try {
            $user->notifyNow(new OtpNotification($otp));
            $this->info("✓ OTP sent to {$user->email}");
        } catch (\Exception $e) {
            $this->error("✗ Failed to send OTP to {$user->email}: " . $e->getMessage());
        }
    }
}

