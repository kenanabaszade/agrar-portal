<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckDatabaseIndexes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:check-indexes 
                            {--table= : Check specific table only}
                            {--missing : Show only missing indexes}
                            {--analyze : Analyze query performance}
                            {--fix : Suggest missing indexes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check database indexes and identify missing indexes for performance optimization';

    /**
     * Critical tables that should have indexes
     */
    protected $criticalTables = [
        'trainings' => [
            'foreign_keys' => ['trainer_id'],
            'filters' => ['category', 'type', 'status', 'start_date', 'end_date'],
            'sorts' => ['start_date', 'created_at'],
            'composite' => [
                ['trainer_id', 'start_date'],
                ['type', 'start_date'],
                ['status', 'start_date'],
                ['category', 'start_date'],
            ],
        ],
        'exams' => [
            'foreign_keys' => ['training_id'],
            'filters' => ['category', 'status', 'start_date', 'end_date'],
            'sorts' => ['start_date', 'created_at'],
            'composite' => [
                ['training_id', 'start_date'],
                ['status', 'start_date'],
                ['category', 'start_date'],
            ],
        ],
        'training_registrations' => [
            'foreign_keys' => ['user_id', 'training_id'],
            'filters' => ['status', 'registration_date'],
            'sorts' => ['registration_date', 'created_at'],
            'composite' => [
                ['user_id', 'status'],
                ['training_id', 'status'],
                ['user_id', 'registration_date'],
            ],
        ],
        'exam_registrations' => [
            'foreign_keys' => ['user_id', 'exam_id'],
            'filters' => ['status', 'registration_date'],
            'sorts' => ['registration_date', 'started_at', 'finished_at'],
            'composite' => [
                ['user_id', 'status'],
                ['exam_id', 'status'],
                ['user_id', 'registration_date'],
            ],
        ],
        'forum_questions' => [
            'foreign_keys' => ['user_id'],
            'filters' => ['status'],
            'sorts' => ['created_at'],
            'composite' => [
                ['user_id', 'status'],
                ['status', 'created_at'],
            ],
        ],
        'forum_answers' => [
            'foreign_keys' => ['question_id', 'user_id'],
            'filters' => [],
            'sorts' => ['created_at'],
            'composite' => [
                ['question_id', 'created_at'],
            ],
        ],
        'notifications' => [
            'foreign_keys' => ['user_id'],
            'filters' => ['type', 'is_read'],
            'sorts' => ['sent_at', 'created_at'],
            'composite' => [
                ['user_id', 'is_read'],
                ['user_id', 'type'],
            ],
        ],
        'user_training_progress' => [
            'foreign_keys' => ['user_id', 'training_id', 'module_id', 'lesson_id'],
            'filters' => ['status'],
            'sorts' => ['last_accessed', 'completed_at'],
            'composite' => [
                ['user_id', 'training_id'],
                ['user_id', 'status'],
            ],
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Database Index Analysis');
        $this->newLine();

        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($driver === 'pgsql') {
            $this->analyzePostgreSQL();
        } elseif ($driver === 'mysql') {
            $this->analyzeMySQL();
        } else {
            $this->error("Unsupported database driver: {$driver}");
            return 1;
        }

        if ($this->option('fix')) {
            $this->suggestMissingIndexes();
        }

        if ($this->option('analyze')) {
            $this->analyzeQueryPerformance();
        }

        return 0;
    }

    /**
     * Analyze PostgreSQL indexes
     */
    protected function analyzePostgreSQL()
    {
        $table = $this->option('table');
        $tables = $table ? [$table] : $this->getAllTables();

        $this->info('📊 Current Indexes in Database:');
        $this->newLine();

        $allIndexes = [];
        $missingIndexes = [];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            $indexes = $this->getPostgreSQLIndexes($tableName);
            $allIndexes[$tableName] = $indexes;

            if (!$this->option('missing')) {
                $this->displayTableIndexes($tableName, $indexes);
            }

            // Check for missing indexes
            if (isset($this->criticalTables[$tableName])) {
                $missing = $this->findMissingIndexes($tableName, $indexes);
                if (!empty($missing)) {
                    $missingIndexes[$tableName] = $missing;
                }
            }
        }

        if ($this->option('missing') || !empty($missingIndexes)) {
            $this->newLine();
            $this->warn('⚠️  Missing Indexes:');
            $this->newLine();

            foreach ($missingIndexes as $tableName => $missing) {
                $this->line("Table: <fg=cyan>{$tableName}</>");
                foreach ($missing as $index) {
                    $this->line("  ❌ Missing: <fg=yellow>{$index['name']}</>");
                    $this->line("     Columns: " . implode(', ', $index['columns']));
                    $this->line("     Type: {$index['type']}");
                }
                $this->newLine();
            }
        }

        // Summary
        $this->newLine();
        $this->info('📈 Summary:');
        $totalIndexes = array_sum(array_map('count', $allIndexes));
        $totalMissing = array_sum(array_map('count', $missingIndexes));
        $this->line("Total tables analyzed: " . count($tables));
        $this->line("Total indexes found: <fg=green>{$totalIndexes}</>");
        if ($totalMissing > 0) {
            $this->line("Missing indexes: <fg=red>{$totalMissing}</>");
        } else {
            $this->line("Missing indexes: <fg=green>0</> ✅");
        }
    }

    /**
     * Analyze MySQL indexes
     */
    protected function analyzeMySQL()
    {
        $table = $this->option('table');
        $tables = $table ? [$table] : $this->getAllTables();

        $this->info('📊 Current Indexes in Database:');
        $this->newLine();

        $allIndexes = [];
        $missingIndexes = [];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            $indexes = $this->getMySQLIndexes($tableName);
            $allIndexes[$tableName] = $indexes;

            if (!$this->option('missing')) {
                $this->displayTableIndexes($tableName, $indexes);
            }

            // Check for missing indexes
            if (isset($this->criticalTables[$tableName])) {
                $missing = $this->findMissingIndexes($tableName, $indexes);
                if (!empty($missing)) {
                    $missingIndexes[$tableName] = $missing;
                }
            }
        }

        if ($this->option('missing') || !empty($missingIndexes)) {
            $this->newLine();
            $this->warn('⚠️  Missing Indexes:');
            $this->newLine();

            foreach ($missingIndexes as $tableName => $missing) {
                $this->line("Table: <fg=cyan>{$tableName}</>");
                foreach ($missing as $index) {
                    $this->line("  ❌ Missing: <fg=yellow>{$index['name']}</>");
                    $this->line("     Columns: " . implode(', ', $index['columns']));
                    $this->line("     Type: {$index['type']}");
                }
                $this->newLine();
            }
        }

        // Summary
        $this->newLine();
        $this->info('📈 Summary:');
        $totalIndexes = array_sum(array_map('count', $allIndexes));
        $totalMissing = array_sum(array_map('count', $missingIndexes));
        $this->line("Total tables analyzed: " . count($tables));
        $this->line("Total indexes found: <fg=green>{$totalIndexes}</>");
        if ($totalMissing > 0) {
            $this->line("Missing indexes: <fg=red>{$totalMissing}</>");
        } else {
            $this->line("Missing indexes: <fg=green>0</> ✅");
        }
    }

    /**
     * Get PostgreSQL indexes for a table
     */
    protected function getPostgreSQLIndexes($tableName)
    {
        $indexes = DB::select("
            SELECT
                i.relname AS index_name,
                a.attname AS column_name,
                ix.indisunique AS is_unique,
                ix.indisprimary AS is_primary,
                am.amname AS index_type
            FROM
                pg_class t,
                pg_class i,
                pg_index ix,
                pg_attribute a,
                pg_am am
            WHERE
                t.oid = ix.indrelid
                AND i.oid = ix.indexrelid
                AND a.attrelid = t.oid
                AND a.attnum = ANY(ix.indkey)
                AND t.relkind = 'r'
                AND t.relname = ?
                AND i.relam = am.oid
            ORDER BY
                i.relname, a.attnum
        ", [$tableName]);

        // Group by index name
        $grouped = [];
        foreach ($indexes as $index) {
            $name = $index->index_name;
            if (!isset($grouped[$name])) {
                $grouped[$name] = [
                    'name' => $name,
                    'columns' => [],
                    'is_unique' => $index->is_unique,
                    'is_primary' => $index->is_primary,
                    'type' => $index->index_type,
                ];
            }
            $grouped[$name]['columns'][] = $index->column_name;
        }

        return array_values($grouped);
    }

    /**
     * Get MySQL indexes for a table
     */
    protected function getMySQLIndexes($tableName)
    {
        $indexes = DB::select("SHOW INDEXES FROM `{$tableName}`");

        // Group by index name
        $grouped = [];
        foreach ($indexes as $index) {
            $name = $index->Key_name;
            if (!isset($grouped[$name])) {
                $grouped[$name] = [
                    'name' => $name,
                    'columns' => [],
                    'is_unique' => $index->Non_unique == 0,
                    'is_primary' => $index->Key_name === 'PRIMARY',
                    'type' => $index->Index_type ?? 'BTREE',
                ];
            }
            $grouped[$name]['columns'][] = $index->Column_name;
        }

        return array_values($grouped);
    }

    /**
     * Display indexes for a table
     */
    protected function displayTableIndexes($tableName, $indexes)
    {
        $this->line("Table: <fg=cyan>{$tableName}</>");
        
        if (empty($indexes)) {
            $this->line("  <fg=red>No indexes found</>");
            $this->newLine();
            return;
        }

        foreach ($indexes as $index) {
            $type = $index['is_primary'] ? 'PRIMARY KEY' : ($index['is_unique'] ? 'UNIQUE' : 'INDEX');
            $columns = implode(', ', $index['columns']);
            $this->line("  ✓ <fg=green>{$index['name']}</> ({$type})");
            $this->line("    Columns: {$columns}");
        }
        $this->newLine();
    }

    /**
     * Find missing indexes for a table
     */
    protected function findMissingIndexes($tableName, $existingIndexes)
    {
        if (!isset($this->criticalTables[$tableName])) {
            return [];
        }

        $config = $this->criticalTables[$tableName];
        $missing = [];
        $existingColumns = [];

        // Build map of existing indexes by columns
        foreach ($existingIndexes as $index) {
            $key = implode(',', $index['columns']);
            $existingColumns[$key] = true;
        }

        // Check foreign keys
        foreach ($config['foreign_keys'] as $fk) {
            $key = $fk;
            if (!isset($existingColumns[$key])) {
                $missing[] = [
                    'name' => "{$tableName}_{$fk}_idx",
                    'columns' => [$fk],
                    'type' => 'Foreign Key Index',
                ];
            }
        }

        // Check filter columns
        foreach ($config['filters'] as $filter) {
            $key = $filter;
            if (!isset($existingColumns[$key])) {
                $missing[] = [
                    'name' => "{$tableName}_{$filter}_idx",
                    'columns' => [$filter],
                    'type' => 'Filter Index',
                ];
            }
        }

        // Check composite indexes
        foreach ($config['composite'] as $composite) {
            $key = implode(',', $composite);
            if (!isset($existingColumns[$key])) {
                $name = $tableName . '_' . implode('_', $composite) . '_idx';
                $missing[] = [
                    'name' => $name,
                    'columns' => $composite,
                    'type' => 'Composite Index',
                ];
            }
        }

        return $missing;
    }

    /**
     * Get all tables in the database
     */
    protected function getAllTables()
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($driver === 'pgsql') {
            $tables = DB::select("
                SELECT tablename 
                FROM pg_tables 
                WHERE schemaname = 'public'
                ORDER BY tablename
            ");
            return array_column($tables, 'tablename');
        } elseif ($driver === 'mysql') {
            $database = $connection->getDatabaseName();
            $tables = DB::select("SHOW TABLES");
            $key = "Tables_in_{$database}";
            return array_column($tables, $key);
        }

        return [];
    }

    /**
     * Suggest missing indexes
     */
    protected function suggestMissingIndexes()
    {
        $this->newLine();
        $this->info('💡 Suggested Migration Code:');
        $this->newLine();

        $connection = DB::connection();
        $driver = $connection->getDriverName();

        $suggestions = [];
        foreach ($this->criticalTables as $tableName => $config) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            $indexes = $driver === 'pgsql' 
                ? $this->getPostgreSQLIndexes($tableName)
                : $this->getMySQLIndexes($tableName);

            $missing = $this->findMissingIndexes($tableName, $indexes);
            
            if (!empty($missing)) {
                $suggestions[$tableName] = $missing;
            }
        }

        if (empty($suggestions)) {
            $this->line("<fg=green>✅ All recommended indexes are present!</>");
            return;
        }

        $this->line("<?php");
        $this->line("");
        $this->line("use Illuminate\\Database\\Migrations\\Migration;");
        $this->line("use Illuminate\\Database\\Schema\\Blueprint;");
        $this->line("use Illuminate\\Support\\Facades\\Schema;");
        $this->line("");
        $this->line("return new class extends Migration");
        $this->line("{");
        $this->line("    public function up(): void");
        $this->line("    {");

        foreach ($suggestions as $tableName => $missing) {
            $this->line("        Schema::table('{$tableName}', function (Blueprint \$table) {");
            foreach ($missing as $index) {
                if (count($index['columns']) === 1) {
                    $this->line("            \$table->index('{$index['columns'][0]}', '{$index['name']}');");
                } else {
                    $cols = "'" . implode("', '", $index['columns']) . "'";
                    $this->line("            \$table->index([{$cols}], '{$index['name']}');");
                }
            }
            $this->line("        });");
            $this->line("");
        }

        $this->line("    }");
        $this->line("");
        $this->line("    public function down(): void");
        $this->line("    {");

        foreach ($suggestions as $tableName => $missing) {
            $this->line("        Schema::table('{$tableName}', function (Blueprint \$table) {");
            foreach ($missing as $index) {
                $this->line("            \$table->dropIndex('{$index['name']}');");
            }
            $this->line("        });");
            $this->line("");
        }

        $this->line("    }");
        $this->line("};");
    }

    /**
     * Analyze query performance
     */
    protected function analyzeQueryPerformance()
    {
        $this->newLine();
        $this->info('⚡ Query Performance Analysis:');
        $this->newLine();

        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($driver === 'pgsql') {
            $this->analyzePostgreSQLQueries();
        } elseif ($driver === 'mysql') {
            $this->analyzeMySQLQueries();
        }
    }

    /**
     * Analyze PostgreSQL query performance
     */
    protected function analyzePostgreSQLQueries()
    {
        // Check if pg_stat_statements is enabled
        $hasStats = DB::select("
            SELECT EXISTS(
                SELECT 1 FROM pg_extension WHERE extname = 'pg_stat_statements'
            ) as exists
        ");

        if (empty($hasStats) || !$hasStats[0]->exists) {
            $this->warn('⚠️  pg_stat_statements extension is not enabled.');
            $this->line('   To enable it, run: CREATE EXTENSION IF NOT EXISTS pg_stat_statements;');
            $this->newLine();
        }

        // Get slow queries
        try {
            $slowQueries = DB::select("
                SELECT 
                    query,
                    calls,
                    total_exec_time,
                    mean_exec_time,
                    max_exec_time
                FROM pg_stat_statements
                WHERE mean_exec_time > 10
                ORDER BY mean_exec_time DESC
                LIMIT 10
            ");

            if (!empty($slowQueries)) {
                $this->warn('🐌 Slow Queries (>10ms average):');
                $this->newLine();
                foreach ($slowQueries as $query) {
                    $this->line("  Mean time: <fg=red>{$query->mean_exec_time}ms</>");
                    $this->line("  Calls: {$query->calls}");
                    $this->line("  Query: " . substr($query->query, 0, 100) . "...");
                    $this->newLine();
                }
            } else {
                $this->line("<fg=green>✅ No slow queries detected</>");
            }
        } catch (\Exception $e) {
            $this->warn('Could not analyze slow queries: ' . $e->getMessage());
        }
    }

    /**
     * Analyze MySQL query performance
     */
    protected function analyzeMySQLQueries()
    {
        // Check slow query log
        $slowLogEnabled = DB::select("SHOW VARIABLES LIKE 'slow_query_log'");
        
        if (empty($slowLogEnabled) || $slowLogEnabled[0]->Value === 'OFF') {
            $this->warn('⚠️  Slow query log is not enabled.');
            $this->line('   To enable it, set slow_query_log = 1 in MySQL config');
            $this->newLine();
        }

        // Get table statistics
        $this->line('📊 Table Statistics:');
        $this->newLine();

        foreach (array_keys($this->criticalTables) as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            try {
                $stats = DB::select("
                    SELECT 
                        table_rows,
                        data_length,
                        index_length,
                        (data_length + index_length) as total_size
                    FROM information_schema.tables
                    WHERE table_schema = DATABASE()
                    AND table_name = ?
                ", [$tableName]);

                if (!empty($stats)) {
                    $stat = $stats[0];
                    $rows = number_format($stat->table_rows);
                    $size = number_format($stat->total_size / 1024 / 1024, 2);
                    $indexRatio = $stat->index_length > 0 
                        ? number_format(($stat->index_length / $stat->total_size) * 100, 1)
                        : 0;

                    $this->line("  {$tableName}:");
                    $this->line("    Rows: {$rows}");
                    $this->line("    Size: {$size} MB");
                    $this->line("    Index ratio: {$indexRatio}%");
                    $this->newLine();
                }
            } catch (\Exception $e) {
                // Ignore errors
            }
        }
    }
}



