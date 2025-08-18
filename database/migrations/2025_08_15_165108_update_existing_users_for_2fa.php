<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing users to be verified and have 2FA enabled
        User::whereNull('email_verified_at')->update([
            'email_verified' => true,
            'email_verified_at' => now(),
            'two_factor_enabled' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration doesn't need to be reversed
        // as it's just updating existing data
    }
};
