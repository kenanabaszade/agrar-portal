<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\OtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TwoFactorAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_otp()
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'user_type' => 'farmer'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Registration successful! Please check your email for OTP verification.',
                'email' => 'john.doe@example.com'
            ]);

        $user = User::where('email', 'john.doe@example.com')->first();
        $this->assertNotNull($user);
        $this->assertFalse($user->email_verified);
        $this->assertNotNull($user->otp_code);
        $this->assertNotNull($user->otp_expires_at);

        Notification::assertSentTo($user, OtpNotification::class);
    }

    public function test_user_can_verify_otp_and_login()
    {
        Notification::fake();

        // Register user
        $this->postJson('/api/v1/auth/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'user_type' => 'farmer'
        ]);

        $user = User::where('email', 'john.doe@example.com')->first();
        $otp = $user->otp_code;

        // Verify OTP
        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'email' => 'john.doe@example.com',
            'otp' => $otp
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Email verified successfully!'
            ])
            ->assertJsonStructure([
                'user',
                'token'
            ]);

        $user->refresh();
        $this->assertTrue($user->email_verified);
        $this->assertNull($user->otp_code);
        $this->assertNull($user->otp_expires_at);

        // Now user can login
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'john.doe@example.com',
            'password' => 'password123'
        ]);

        $loginResponse->assertStatus(200)
            ->assertJsonStructure([
                'user',
                'token'
            ]);
    }

    public function test_user_cannot_login_without_verification()
    {
        Notification::fake();

        // Register user
        $this->postJson('/api/v1/auth/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'user_type' => 'farmer'
        ]);

        // Try to login without verification
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'john.doe@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Please verify your email first. Check your inbox for OTP code.',
                'needs_verification' => true
            ]);
    }

    public function test_invalid_otp_returns_error()
    {
        Notification::fake();

        // Register user
        $this->postJson('/api/v1/auth/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'user_type' => 'farmer'
        ]);

        // Try to verify with wrong OTP
        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'email' => 'john.doe@example.com',
            'otp' => '000000'
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Invalid OTP code'
            ]);
    }

    public function test_user_can_resend_otp()
    {
        Notification::fake();

        // Register user
        $this->postJson('/api/v1/auth/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'user_type' => 'farmer'
        ]);

        $user = User::where('email', 'john.doe@example.com')->first();
        $originalOtp = $user->otp_code;

        // Resend OTP
        $response = $this->postJson('/api/v1/auth/resend-otp', [
            'email' => 'john.doe@example.com'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'New OTP sent to your email'
            ]);

        $user->refresh();
        $this->assertNotEquals($originalOtp, $user->otp_code);

        Notification::assertSentTo($user, OtpNotification::class);
    }

    public function test_already_verified_user_cannot_verify_again()
    {
        Notification::fake();

        // Register and verify user
        $this->postJson('/api/v1/auth/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'user_type' => 'farmer'
        ]);

        $user = User::where('email', 'john.doe@example.com')->first();
        $otp = $user->otp_code;

        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => 'john.doe@example.com',
            'otp' => $otp
        ]);

        // Try to verify again
        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'email' => 'john.doe@example.com',
            'otp' => $otp
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Email already verified'
            ]);
    }

    public function test_user_can_enable_2fa_after_login()
    {
        Notification::fake();

        // Register and verify user
        $this->postJson('/api/v1/auth/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'user_type' => 'farmer'
        ]);

        $user = User::where('email', 'john.doe@example.com')->first();
        $otp = $user->otp_code;

        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => 'john.doe@example.com',
            'otp' => $otp
        ]);

        // Login
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'john.doe@example.com',
            'password' => 'password123'
        ]);

        $token = $loginResponse->json('token');

        // Check 2FA status
        $statusResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/api/v1/auth/2fa/status');

        $statusResponse->assertStatus(200)
            ->assertJson([
                'two_factor_enabled' => true,
                'email_verified' => true
            ]);
    }

    public function test_user_can_disable_and_re_enable_2fa()
    {
        Notification::fake();

        // Register, verify, and login user
        $this->postJson('/api/v1/auth/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'user_type' => 'farmer'
        ]);

        $user = User::where('email', 'john.doe@example.com')->first();
        $otp = $user->otp_code;

        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => 'john.doe@example.com',
            'otp' => $otp
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'john.doe@example.com',
            'password' => 'password123'
        ]);

        $token = $loginResponse->json('token');

        // Disable 2FA
        $disableResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/api/v1/auth/2fa/disable');

        $disableResponse->assertStatus(200)
            ->assertJson([
                'message' => 'OTP sent to your email. Please verify to disable 2FA.'
            ]);

        $user->refresh();
        $otp = $user->otp_code;

        // Verify disable
        $verifyDisableResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/api/v1/auth/2fa/verify-disable', [
                'otp' => $otp
            ]);

        $verifyDisableResponse->assertStatus(200)
            ->assertJson([
                'message' => '2FA disabled successfully!'
            ]);

        // Re-enable 2FA
        $enableResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/api/v1/auth/2fa/enable');

        $enableResponse->assertStatus(200)
            ->assertJson([
                'message' => 'OTP sent to your email. Please verify to enable 2FA.'
            ]);

        $user->refresh();
        $otp = $user->otp_code;

        // Verify enable
        $verifyEnableResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/api/v1/auth/2fa/verify-enable', [
                'otp' => $otp
            ]);

        $verifyEnableResponse->assertStatus(200)
            ->assertJson([
                'message' => '2FA enabled successfully!'
            ]);
    }
}
