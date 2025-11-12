<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TruncateAllExceptUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:truncate-all-except-users {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate all database tables except users table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force')) {
            if (!$this->confirm('⚠️  WARNING: This will delete ALL data from all tables except users. Are you sure?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('Starting truncation of all tables except users...');

        // Get database connection
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        // Get all tables from database
        $allTables = $this->getAllTables($driver);
        
        // Tables to preserve (keep users)
        $preserveTables = ['users'];

        // Filter out tables to preserve
        $tablesToTruncate = array_filter($allTables, function ($table) use ($preserveTables) {
            return !in_array($table, $preserveTables);
        });

        if (empty($tablesToTruncate)) {
            $this->warn('No tables found to truncate.');
            return 0;
        }

        $this->info('Found ' . count($tablesToTruncate) . ' tables to truncate.');
        
        if (!$this->option('force')) {
            $this->table(['Table Name'], array_map(fn($t) => [$t], $tablesToTruncate));
            
            if (!$this->confirm('Do you want to proceed with truncating these tables?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        // Disable foreign key checks for MySQL/MariaDB
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        $truncated = 0;
        $errors = [];

        foreach ($tablesToTruncate as $table) {
            try {
                if (Schema::hasTable($table)) {
                    if ($driver === 'pgsql') {
                        // PostgreSQL: Use CASCADE to handle foreign keys
                        DB::statement("TRUNCATE TABLE \"{$table}\" RESTART IDENTITY CASCADE;");
                    } elseif ($driver === 'sqlite') {
                        // SQLite: Delete and reset auto increment
                        DB::statement("DELETE FROM \"{$table}\";");
                        DB::statement("DELETE FROM sqlite_sequence WHERE name='{$table}';");
                    } else {
                        // MySQL/MariaDB: Use truncate
                        DB::table($table)->truncate();
                    }
                    $truncated++;
                    $this->line("✓ Truncated: {$table}");
                }
            } catch (\Exception $e) {
                $errors[] = "{$table}: {$e->getMessage()}";
                $this->error("✗ Error truncating {$table}: {$e->getMessage()}");
            }
        }

        // Re-enable foreign key checks for MySQL/MariaDB
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $this->newLine();
        $this->info("✅ Successfully truncated {$truncated} table(s).");

        if (!empty($errors)) {
            $this->warn('⚠️  Some tables had errors:');
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
        }

        return 0;
    }

    /**
     * Get all tables from the database
     */
    private function getAllTables(string $driver): array
    {
        $connection = DB::connection();
        
        if ($driver === 'mysql' || $driver === 'mariadb') {
            $tables = DB::select("SHOW TABLES");
            $tableKey = 'Tables_in_' . $connection->getDatabaseName();
            return array_map(fn($table) => $table->$tableKey, $tables);
        } elseif ($driver === 'pgsql') {
            $tables = DB::select("
                SELECT tablename 
                FROM pg_tables 
                WHERE schemaname = 'public'
            ");
            return array_map(fn($table) => $table->tablename, $tables);
        } elseif ($driver === 'sqlite') {
            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name != 'sqlite_sequence'");
            return array_map(fn($table) => $table->name, $tables);
        } else {
            // Fallback: try to get tables from schema
            return [];
        }
    }
}


