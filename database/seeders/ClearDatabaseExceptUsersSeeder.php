<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class ClearDatabaseExceptUsersSeeder extends Seeder
{
    /**
     * The tables that must be preserved.
     *
     * @var array<int, string>
     */
    protected array $preservedTables = [
        'users',
        'migrations',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $connection = Config::get('database.default');
        $driver = Config::get("database.connections.{$connection}.driver");
        $databaseName = $this->resolveDatabaseName($connection);

        $tables = $this->listTables($connection, $driver, $databaseName);

        if (empty($tables)) {
            $this->command?->warn('No tables found to truncate.');
            return;
        }

        $this->disableForeignKeyChecks($connection, $driver);

        collect($tables)
            ->reject(fn ($table) => in_array($table, $this->preservedTables, true))
            ->each(function ($table) use ($connection, $driver) {
                $this->truncateTable($connection, $driver, $table);
                $this->command?->info("Truncated table: {$table}");
            });

        $this->enableForeignKeyChecks($connection, $driver);
    }

    /**
     * Resolve the active database name for the connection.
     */
    protected function resolveDatabaseName(string $connection): string
    {
        $config = Config::get("database.connections.{$connection}");

        return Arr::get($config, 'database') ?? DB::connection($connection)->getDatabaseName();
    }

    /**
     * Truncate or soft-delete table rows depending on the driver.
     */
    protected function truncateTable(string $connection, ?string $driver, string $table): void
    {
        $query = DB::connection($connection)->table($table);

        match ($driver) {
            'sqlite' => $this->clearSqliteTable($connection, $table, $query),
            'sqlsrv' => $this->clearSqlServerTable($connection, $table, $query),
            default => $query->truncate(),
        };
    }

    /**
     * Handle truncation for SQLite connections.
     */
    protected function clearSqliteTable(string $connection, string $table, \Illuminate\Database\Query\Builder $query): void
    {
        $query->delete();
        DB::connection($connection)->statement('DELETE FROM sqlite_sequence WHERE name = ?', [$table]);
    }

    /**
     * Handle truncation for SQL Server connections.
     */
    protected function clearSqlServerTable(string $connection, string $table, \Illuminate\Database\Query\Builder $query): void
    {
        $query->delete();
        DB::connection($connection)->statement("DBCC CHECKIDENT('{$table}', RESEED, 0)");
    }

    /**
     * List all tables for the given connection/driver.
     *
     * @return array<int, string>
     */
    protected function listTables(string $connection, ?string $driver, string $database): array
    {
        $results = match ($driver) {
            'mysql', 'mariadb' => DB::connection($connection)->select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"),
            'pgsql' => DB::connection($connection)->select("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname NOT IN ('pg_catalog', 'information_schema')"),
            'sqlite' => DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"),
            'sqlsrv' => DB::connection($connection)->select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'"),
            default => throw new \RuntimeException("Unsupported database driver [{$driver}]."),
        };

        return collect($results)
            ->map(function ($row) use ($driver, $database) {
                $values = array_values((array) $row);

                if (! empty($values)) {
                    return $values[0];
                }

                throw new \RuntimeException("Unable to determine table name for driver [{$driver}] in database [{$database}].");
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Disable foreign key checks while dropping tables.
     */
    protected function disableForeignKeyChecks(string $connection, ?string $driver): void
    {
        match ($driver) {
            'mysql', 'mariadb' => DB::connection($connection)->statement('SET FOREIGN_KEY_CHECKS = 0'),
            'pgsql' => DB::connection($connection)->statement("SET session_replication_role = 'replica'"),
            'sqlite' => DB::connection($connection)->statement('PRAGMA foreign_keys = OFF'),
            'sqlsrv' => $this->toggleSqlServerConstraints($connection, false),
            default => null,
        };
    }

    /**
     * Enable foreign key checks after dropping tables.
     */
    protected function enableForeignKeyChecks(string $connection, ?string $driver): void
    {
        match ($driver) {
            'mysql', 'mariadb' => DB::connection($connection)->statement('SET FOREIGN_KEY_CHECKS = 1'),
            'pgsql' => DB::connection($connection)->statement("SET session_replication_role = 'origin'"),
            'sqlite' => DB::connection($connection)->statement('PRAGMA foreign_keys = ON'),
            'sqlsrv' => $this->toggleSqlServerConstraints($connection, true),
            default => null,
        };
    }

    /**
     * Toggle SQL Server constraints.
     */
    protected function toggleSqlServerConstraints(string $connection, bool $enable): void
    {
        $constraintsQuery = "
            DECLARE @sql NVARCHAR(MAX) = N'';
            SELECT @sql += 'ALTER TABLE ' + QUOTENAME(s.name) + '.' + QUOTENAME(t.name) + ' ' +
                '" . ($enable ? 'WITH CHECK CHECK' : 'NOCHECK') . " CONSTRAINT ' + QUOTENAME(c.name) + ';'
            FROM sys.tables t
            JOIN sys.schemas s ON t.schema_id = s.schema_id
            JOIN sys.foreign_keys c ON t.object_id = c.parent_object_id;
            EXEC sp_executesql @sql;
        ";

        DB::connection($connection)->unprepared($constraintsQuery);
    }
}

