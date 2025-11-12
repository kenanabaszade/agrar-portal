<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearDatabaseExceptUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:clear-except-users {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all database tables except users and system tables';

    /**
     * Tables to preserve (users and system tables)
     */
    protected $preservedTables = [
        'users',
        'password_reset_tokens',
        'sessions',
        'migrations',
        'jobs',
        'failed_jobs',
        'cache',
        'cache_locks',
        'personal_access_tokens',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force')) {
            if (!$this->confirm('⚠️  WARNING: This will delete ALL data from all tables except users and system tables. Are you sure?')) {
                $this->info('Operation cancelled.');
                return 0;
            }

            if (!$this->confirm('⚠️  This action CANNOT be undone. Are you absolutely sure?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('🔄 Starting database cleanup...');

        try {
            $connection = DB::connection();
            $driverName = $connection->getDriverName();

            // Get all table names based on database driver
            if ($driverName === 'pgsql') {
                // PostgreSQL
                $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
                $tableNames = array_map(function($table) {
                    return $table->tablename;
                }, $tables);
            } else {
                // MySQL/MariaDB
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                $tables = DB::select('SHOW TABLES');
                $databaseName = DB::getDatabaseName();
                $tableKey = 'Tables_in_' . $databaseName;
                $tableNames = array_map(function($table) use ($tableKey) {
                    return $table->$tableKey;
                }, $tables);
            }

            $clearedTables = [];
            $preservedCount = 0;

            foreach ($tableNames as $tableName) {
                // Skip preserved tables
                if (in_array($tableName, $this->preservedTables)) {
                    $preservedCount++;
                    continue;
                }

                // Truncate the table
                try {
                    if ($driverName === 'pgsql') {
                        // PostgreSQL: Use TRUNCATE CASCADE to handle foreign keys
                        DB::statement("TRUNCATE TABLE \"{$tableName}\" CASCADE");
                    } else {
                        DB::table($tableName)->truncate();
                    }
                    $clearedTables[] = $tableName;
                    $this->line("  ✓ Cleared: {$tableName}");
                } catch (\Exception $e) {
                    $this->error("  ✗ Failed to clear {$tableName}: " . $e->getMessage());
                }
            }

            // Re-enable foreign key checks for MySQL
            if ($driverName !== 'pgsql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }

            $this->newLine();
            $this->info("✅ Database cleanup completed!");
            $this->info("   • Cleared tables: " . count($clearedTables));
            $this->info("   • Preserved tables: {$preservedCount}");

            return 0;

        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
}
