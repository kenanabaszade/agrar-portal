<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class DeleteUserByEmail extends Command
{
    protected $signature = 'user:delete-by-email {email} {--force}';
    protected $description = 'Delete user by email address';

    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User not found: {$email}");
            return 1;
        }
        
        $this->info("Found user:");
        $this->line("  ID: {$user->id}");
        $this->line("  Name: {$user->first_name} {$user->last_name}");
        $this->line("  Email: {$user->email}");
        $this->line("  User Type: {$user->user_type}");
        $this->line("  Email Verified: " . ($user->email_verified ? 'Yes' : 'No'));
        
        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to delete this user?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }
        
        $user->delete();
        
        $this->info("\n✓ User deleted successfully!");
        
        return 0;
    }
}

