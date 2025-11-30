<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AnalyzeLoginPerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analyze:login-performance 
                            {--email= : Test with specific email}
                            {--iterations=10 : Number of iterations to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze login performance and identify bottlenecks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Login Performance Analysis');
        $this->newLine();

        // Check database indexes
        $this->checkDatabaseIndexes();

        // Test login query performance
        $this->testLoginQuery();

        // Analyze update queries
        $this->analyzeUpdateQueries();

        // Check email sending
        $this->checkEmailSending();

        // Overall recommendations
        $this->showRecommendations();

        return 0;
    }

    /**
     * Check database indexes for login-related queries
     */
    protected function checkDatabaseIndexes()
    {
        $this->info('📊 Database Indexes Check:');
        $this->newLine();

        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($driver === 'pgsql') {
            $indexes = DB::select("
                SELECT
                    i.relname AS index_name,
                    a.attname AS column_name
                FROM
                    pg_class t,
                    pg_class i,
                    pg_index ix,
                    pg_attribute a
                WHERE
                    t.oid = ix.indrelid
                    AND i.oid = ix.indexrelid
                    AND a.attrelid = t.oid
                    AND a.attnum = ANY(ix.indkey)
                    AND t.relkind = 'r'
                    AND t.relname = 'users'
                    AND a.attname = 'email'
            ");

            if (empty($indexes)) {
                $this->error('  ❌ No index on users.email column!');
                $this->warn('     This will cause slow login queries.');
            } else {
                $this->line('  ✅ Index found on users.email');
            }
        } elseif ($driver === 'mysql') {
            $indexes = DB::select("SHOW INDEXES FROM users WHERE Column_name = 'email'");
            
            if (empty($indexes)) {
                $this->error('  ❌ No index on users.email column!');
                $this->warn('     This will cause slow login queries.');
            } else {
                $this->line('  ✅ Index found on users.email');
            }
        }

        $this->newLine();
    }

    /**
     * Test login query performance
     */
    protected function testLoginQuery()
    {
        $this->info('⚡ Login Query Performance Test:');
        $this->newLine();

        $email = $this->option('email');
        
        if (!$email) {
            $user = User::first();
            if (!$user) {
                $this->warn('  No users found in database. Skipping query test.');
                $this->newLine();
                return;
            }
            $email = $user->email;
        }

        $iterations = (int) $this->option('iterations');
        $times = [];

        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            
            DB::enableQueryLog();
            $user = User::where('email', $email)->first();
            $queries = DB::getQueryLog();
            DB::disableQueryLog();
            
            $end = microtime(true);
            $times[] = ($end - $start) * 1000; // Convert to milliseconds
        }

        $avgTime = array_sum($times) / count($times);
        $minTime = min($times);
        $maxTime = max($times);

        $this->line("  Query: SELECT * FROM users WHERE email = ?");
        $this->line("  Iterations: {$iterations}");
        $this->line("  Average time: <fg=cyan>{$avgTime}ms</>");
        $this->line("  Min time: <fg=green>{$minTime}ms</>");
        $this->line("  Max time: <fg=red>{$maxTime}ms</>");

        if ($avgTime > 50) {
            $this->error('  ⚠️  Query is slow! Consider adding/checking index on email column.');
        } elseif ($avgTime > 20) {
            $this->warn('  ⚠️  Query could be faster. Check index usage.');
        } else {
            $this->line('  ✅ Query performance is good.');
        }

        // Check if index is being used
        if (!empty($queries)) {
            $query = $queries[0];
            $this->line("  Query time: {$query['time']}ms");
        }

        $this->newLine();
    }

    /**
     * Analyze update queries
     */
    protected function analyzeUpdateQueries()
    {
        $this->info('📝 Update Query Analysis:');
        $this->newLine();

        $this->line('  Current login process performs multiple UPDATE queries:');
        $this->line('    1. Update OTP (if 2FA enabled)');
        $this->line('    2. Clear OTP (if exists)');
        $this->line('    3. Update last_login_at');
        $this->newLine();
        $this->warn('  ⚠️  Multiple UPDATE queries can be slow.');
        $this->line('  💡 Recommendation: Combine all updates into a single query.');
        $this->newLine();
    }

    /**
     * Check email sending
     */
    protected function checkEmailSending()
    {
        $this->info('📧 Email Sending Analysis:');
        $this->newLine();

        $config = config('queue.default');
        $this->line("  Queue driver: <fg=cyan>{$config}</>");

        if ($config === 'sync') {
            $this->error('  ❌ Queue driver is "sync" - emails are sent synchronously!');
            $this->warn('     This blocks the login response until email is sent.');
            $this->line('  💡 Recommendation: Use "database" or "redis" queue driver.');
        } else {
            $this->line('  ✅ Queue driver is configured for async processing.');
        }

        // Check if OtpNotification implements ShouldQueue
        $notificationClass = \App\Notifications\OtpNotification::class;
        
        // Check using reflection to see if it implements ShouldQueue
        $reflection = new \ReflectionClass($notificationClass);
        $implements = class_implements($notificationClass);
        
        if (isset($implements[\Illuminate\Contracts\Queue\ShouldQueue::class])) {
            $this->line('  ✅ OtpNotification implements ShouldQueue');
        } else {
            $this->warn('  ⚠️  OtpNotification does not implement ShouldQueue');
            $this->line('  💡 Recommendation: Make OtpNotification queued.');
        }
        
        // Check if uses Queueable trait
        $traits = class_uses_recursive($notificationClass);
        if (in_array(\Illuminate\Bus\Queueable::class, $traits)) {
            $this->line('  ✅ OtpNotification uses Queueable trait');
        }

        $this->newLine();
    }

    /**
     * Show recommendations
     */
    protected function showRecommendations()
    {
        $this->info('💡 Performance Recommendations:');
        $this->newLine();

        $recommendations = [
            '1. Database Indexes' => [
                'Ensure users.email has an index (usually automatic with UNIQUE constraint)',
                'Check if index is being used with EXPLAIN ANALYZE',
            ],
            '2. Query Optimization' => [
                'Combine multiple UPDATE queries into one',
                'Use select() to limit columns if not all are needed',
            ],
            '3. Email Sending' => [
                'Use queue for async email sending',
                'Ensure OtpNotification implements ShouldQueue',
                'Configure queue worker: php artisan queue:work',
            ],
            '4. Response Optimization' => [
                'Return only necessary user fields in login response',
                'Avoid loading relationships unless needed',
            ],
            '5. Caching' => [
                'Consider caching user data if frequently accessed',
                'Use Redis for session storage if available',
            ],
        ];

        foreach ($recommendations as $category => $items) {
            $this->line("  <fg=cyan>{$category}:</>");
            foreach ($items as $item) {
                $this->line("    • {$item}");
            }
            $this->newLine();
        }
    }
}

