<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanDatabaseKeepAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:clean-keep-admin {--admin-id=1} {--admin-email=abbasli.umid2003@gmail.com} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all data except admin user (id: 1, email: abbasli.umid2003@gmail.com)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $adminId = $this->option('admin-id');
        $adminEmail = $this->option('admin-email');
        
        $this->info('Starting database cleanup...');
        
        // Find admin user by ID
        $adminUser = User::find($adminId);
        
        if (!$adminUser) {
            $this->error("Admin user not found with id={$adminId}");
            $this->info('Available users:');
            User::all(['id', 'email', 'first_name', 'last_name', 'user_type'])->each(function($user) {
                $this->line("  ID: {$user->id}, Email: {$user->email}, Name: {$user->first_name} {$user->last_name}, Type: {$user->user_type}");
            });
            return 1;
        }
        
        // Update email if different
        if ($adminUser->email !== $adminEmail) {
            $this->warn("Admin user email is '{$adminUser->email}', updating to '{$adminEmail}'");
            $adminUser->email = $adminEmail;
            $adminUser->save();
        }
        
        // Update user_type to admin if not already
        if ($adminUser->user_type !== 'admin') {
            $this->warn("Admin user type is '{$adminUser->user_type}', updating to 'admin'");
            $adminUser->user_type = 'admin';
            $adminUser->save();
        }
        
        $this->info("Found admin user: {$adminUser->first_name} {$adminUser->last_name} ({$adminUser->email})");
        
        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to delete ALL data except this admin user? This cannot be undone!')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        } else {
            $this->warn('Force mode enabled - skipping confirmation');
        }
        
        $driver = DB::connection()->getDriverName();
        $this->info("Database driver: {$driver}");
        
        // Get all table names
        $allTables = [];
        
        if ($driver === 'pgsql') {
            $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
            $allTables = array_map(fn($table) => $table->tablename, $tables);
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            $database = DB::connection()->getDatabaseName();
            $tables = DB::select("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?", [$database]);
            $allTables = array_map(fn($table) => $table->TABLE_NAME, $tables);
        } elseif ($driver === 'sqlite') {
            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            $allTables = array_map(fn($table) => $table->name, $tables);
        }
        
        // Tables to exclude
        $excludeTables = [
            'users',
            'migrations',
            'password_reset_tokens',
            'sessions',
            'cache',
            'cache_locks',
            'jobs',
            'job_batches',
            'failed_jobs',
        ];
        
        // Filter tables
        $tablesToClean = array_filter($allTables, fn($table) => !in_array($table, $excludeTables));
        
        $this->info("Found " . count($tablesToClean) . " tables to clean");
        
        // Delete all users except admin
        $deletedUsers = User::where('id', '!=', $adminId)->delete();
        $this->info("Deleted {$deletedUsers} users (kept admin user)");
        
        // Clean other tables
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            
            foreach ($tablesToClean as $table) {
                try {
                    $count = DB::table($table)->count();
                    DB::table($table)->truncate();
                    $this->line("  ✓ Truncated {$table} ({$count} records)");
                } catch (\Exception $e) {
                    $this->warn("  ✗ Failed to truncate {$table}: " . $e->getMessage());
                }
            }
            
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } elseif ($driver === 'pgsql') {
            foreach ($tablesToClean as $table) {
                try {
                    $count = DB::table($table)->count();
                    DB::statement("TRUNCATE TABLE \"{$table}\" RESTART IDENTITY CASCADE;");
                    $this->line("  ✓ Truncated {$table} ({$count} records)");
                } catch (\Exception $e) {
                    $this->warn("  ✗ Failed to truncate {$table}: " . $e->getMessage());
                }
            }
        } elseif ($driver === 'sqlite') {
            foreach ($tablesToClean as $table) {
                try {
                    $count = DB::table($table)->count();
                    DB::statement("DELETE FROM \"{$table}\";");
                    DB::statement("DELETE FROM sqlite_sequence WHERE name='{$table}';");
                    $this->line("  ✓ Deleted from {$table} ({$count} records)");
                } catch (\Exception $e) {
                    $this->warn("  ✗ Failed to delete from {$table}: " . $e->getMessage());
                }
            }
        }
        
        // Verify admin user still exists
        $adminUser = User::find($adminId);
        if ($adminUser) {
            $this->info("\n✓ Database cleanup completed successfully!");
            $this->info("Admin user preserved: {$adminUser->first_name} {$adminUser->last_name} ({$adminUser->email})");
            $this->info("User type: {$adminUser->user_type}");
            $this->info("Total users remaining: " . User::count());
        } else {
            $this->error("\n✗ ERROR: Admin user was deleted! This should not happen.");
            return 1;
        }
        
        return 0;
    }
}

