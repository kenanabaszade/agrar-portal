<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-admin 
                            {--email=abbasli.umid2003@gmail.com}
                            {--password=Au030624%}
                            {--name=Admin}
                            {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all users and create a new admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->option('email');
        $password = $this->option('password');
        $name = $this->option('name');
        
        $this->info('Starting user cleanup and admin creation...');
        
        // Count existing users
        $userCount = User::count();
        $this->info("Found {$userCount} existing users");
        
        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to delete ALL users and create a new admin? This cannot be undone!')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        } else {
            $this->warn('Force mode enabled - skipping confirmation');
        }
        
        // Delete all users
        $deleted = User::query()->delete();
        $this->info("Deleted {$deleted} users");
        
        // Reset auto increment for PostgreSQL/MySQL to start from 1
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            \Illuminate\Support\Facades\DB::statement("ALTER SEQUENCE users_id_seq RESTART WITH 1");
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE users AUTO_INCREMENT = 1");
        }
        
        // Create new admin user with ID 1
        $admin = User::create([
            'id' => 1, // Force ID to be 1
            'first_name' => $name,
            'last_name' => 'User',
            'email' => $email,
            'password_hash' => Hash::make($password),
            'user_type' => 'admin',
            'is_active' => true,
            'email_verified' => true,
            'email_verified_at' => now(),
            'two_factor_enabled' => false,
        ]);
        
        $this->info("\n✓ Admin user created successfully!");
        $this->info("  ID: {$admin->id}");
        $this->info("  Name: {$admin->first_name} {$admin->last_name}");
        $this->info("  Email: {$admin->email}");
        $this->info("  User Type: {$admin->user_type}");
        $this->info("  Email Verified: " . ($admin->email_verified ? 'Yes' : 'No'));
        $this->info("\nTotal users: " . User::count());
        
        return 0;
    }
}

