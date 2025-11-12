<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Truncates all tables except users, preserving user data.
     * Dynamically gets all tables from database.
     */
    public function up(): void
    {
        // Get database connection
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        
        // Get all table names from database
        $allTables = [];
        
        if ($driver === 'pgsql') {
            // PostgreSQL
            $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
            $allTables = array_map(function($table) {
                return $table->tablename;
            }, $tables);
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            // MySQL/MariaDB
            $database = DB::connection()->getDatabaseName();
            $tables = DB::select("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?", [$database]);
            $allTables = array_map(function($table) {
                return $table->TABLE_NAME;
            }, $tables);
        } elseif ($driver === 'sqlite') {
            // SQLite
            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            $allTables = array_map(function($table) {
                return $table->name;
            }, $tables);
        } else {
            // Fallback: Use Schema facade
            $allTables = Schema::getConnection()->getDoctrineSchemaManager()->listTableNames();
        }
        
        // Tables to exclude (users and Laravel system tables)
        $excludeTables = [
            'users',
            'migrations',
            'password_reset_tokens', // Laravel auth table - keep for user password resets
        ];
        
        // Filter out excluded tables
        $tablesToTruncate = array_filter($allTables, function($table) use ($excludeTables) {
            return !in_array($table, $excludeTables);
        });

        if ($driver === 'mysql' || $driver === 'mariadb') {
            // Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            
            // Truncate each table
            foreach ($tablesToTruncate as $table) {
                try {
                    DB::table($table)->truncate();
                    \Log::info("Truncated table: {$table}");
                } catch (\Exception $e) {
                    \Log::warning("Failed to truncate table {$table}: " . $e->getMessage());
                }
            }
            
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } elseif ($driver === 'pgsql') {
            // PostgreSQL: Truncate with CASCADE to handle foreign keys
            foreach ($tablesToTruncate as $table) {
                try {
                    DB::statement("TRUNCATE TABLE \"{$table}\" RESTART IDENTITY CASCADE;");
                    \Log::info("Truncated table: {$table}");
                } catch (\Exception $e) {
                    \Log::warning("Failed to truncate table {$table}: " . $e->getMessage());
                }
            }
        } elseif ($driver === 'sqlite') {
            // SQLite: Delete and reset auto increment
            foreach ($tablesToTruncate as $table) {
                try {
                    DB::statement("DELETE FROM \"{$table}\";");
                    DB::statement("DELETE FROM sqlite_sequence WHERE name='{$table}';");
                    \Log::info("Truncated table: {$table}");
                } catch (\Exception $e) {
                    \Log::warning("Failed to truncate table {$table}: " . $e->getMessage());
                }
            }
        } else {
            // Fallback: Delete all records
            foreach ($tablesToTruncate as $table) {
                try {
                    DB::table($table)->delete();
                    \Log::info("Deleted all records from table: {$table}");
                } catch (\Exception $e) {
                    \Log::warning("Failed to delete records from table {$table}: " . $e->getMessage());
                }
            }
        }
        
        \Log::info('Database truncation completed. Total tables processed: ' . count($tablesToTruncate));
    }

    /**
     * Reverse the migrations.
     * This operation cannot be reversed as data is permanently deleted.
     */
    public function down(): void
    {
        // This operation cannot be reversed
        // Data has been permanently deleted
    }
};
