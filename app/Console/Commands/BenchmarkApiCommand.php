<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use App\Models\Training;
use App\Models\Exam;

class BenchmarkApiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'benchmark:api 
                            {--seed : Seed performance test data}
                            {--output= : Output file path}
                            {--endpoints=all : Comma-separated endpoints to benchmark (all,trainings,exams)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Benchmark API performance for critical endpoints';

    private array $metrics = [];
    private int $queryCount = 0;
    private float $startTime = 0;
    private float $startMemory = 0;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting API Performance Benchmarks...');
        $this->newLine();

        // Seed data if requested
        if ($this->option('seed')) {
            $this->info('📊 Seeding performance test data...');
            Artisan::call('db:seed', ['--class' => 'PerformanceTestSeeder']);
            $this->info('✅ Test data seeded');
            $this->newLine();
        }

        // Check if data exists
        $trainingCount = Training::count();
        $examCount = Exam::count();
        
        if ($trainingCount === 0 || $examCount === 0) {
            $this->error('❌ No test data found. Run with --seed option first.');
            return 1;
        }

        $this->info("📈 Found {$trainingCount} trainings and {$examCount} exams");
        $this->newLine();

        // Get endpoints to benchmark
        $endpoints = $this->option('endpoints') === 'all' 
            ? ['trainings', 'exams'] 
            : explode(',', $this->option('endpoints'));

        // Run benchmarks
        if (in_array('trainings', $endpoints) || $this->option('endpoints') === 'all') {
            $this->benchmarkTrainingListings();
            $this->benchmarkTrainingDetails();
        }

        if (in_array('exams', $endpoints) || $this->option('endpoints') === 'all') {
            $this->benchmarkExamListings();
            $this->benchmarkExamDetails();
        }

        // Display results
        $this->displayResults();

        // Export to file
        $outputFile = $this->option('output') ?: base_path('PERFORMANCE_BEFORE.md');
        $this->exportToMarkdown($outputFile);
        
        $this->newLine();
        $this->info("📄 Results exported to: {$outputFile}");
        
        return 0;
    }

    /**
     * Benchmark training listings endpoint
     */
    private function benchmarkTrainingListings(): void
    {
        $this->info('Testing: GET /api/v1/trainings?per_page=50');
        
        $this->startMeasuring();
        
        // Simulate HTTP request
        $user = User::where('user_type', 'admin')->first() ?? User::first();
        $trainings = Training::with(['modules.lessons', 'trainer', 'registrations', 'exam'])
            ->withCount(['registrations'])
            ->paginate(50);
        
        // Force evaluation
        $trainings->getCollection()->transform(function ($training) {
            $training->registrations()->whereHas('userTrainingProgress', 
                fn($q) => $q->where('status', 'completed'))->count();
            $training->registrations()->whereHas('userTrainingProgress', 
                fn($q) => $q->where('status', 'in_progress'))->count();
            return $training;
        });
        
        $metrics = $this->stopMeasuring('GET /api/v1/trainings (per_page=50)');
        $this->info("  ⏱️  {$metrics['response_time_ms']}ms | 🔍 {$metrics['query_count']} queries | 💾 {$metrics['memory_used_mb']}MB");
    }

    /**
     * Benchmark training details endpoint
     */
    private function benchmarkTrainingDetails(): void
    {
        $this->info('Testing: GET /api/v1/trainings/1');
        
        $this->startMeasuring();
        
        $training = Training::with(['modules.lessons', 'trainer', 'registrations', 'exam'])->first();
        
        $metrics = $this->stopMeasuring('GET /api/v1/trainings/{id}');
        $this->info("  ⏱️  {$metrics['response_time_ms']}ms | 🔍 {$metrics['query_count']} queries | 💾 {$metrics['memory_used_mb']}MB");
    }

    /**
     * Benchmark exam listings endpoint
     */
    private function benchmarkExamListings(): void
    {
        $this->info('Testing: GET /api/v1/exams?per_page=50');
        
        $this->startMeasuring();
        
        $exams = Exam::with(['training.trainer', 'questions'])
            ->withCount(['questions', 'registrations'])
            ->paginate(50);
        
        // Force evaluation
        $exams->getCollection()->transform(function ($exam) {
            $exam->registrations()->whereIn('status', ['passed','failed','completed'])->count();
            $exam->registrations()->where('status', 'passed')->count();
            return $exam;
        });
        
        $metrics = $this->stopMeasuring('GET /api/v1/exams (per_page=50)');
        $this->info("  ⏱️  {$metrics['response_time_ms']}ms | 🔍 {$metrics['query_count']} queries | 💾 {$metrics['memory_used_mb']}MB");
    }

    /**
     * Benchmark exam details endpoint
     */
    private function benchmarkExamDetails(): void
    {
        $this->info('Testing: GET /api/v1/exams/1');
        
        $this->startMeasuring();
        
        $exam = Exam::with(['training.trainer', 'questions'])->first();
        
        $metrics = $this->stopMeasuring('GET /api/v1/exams/{id}');
        $this->info("  ⏱️  {$metrics['response_time_ms']}ms | 🔍 {$metrics['query_count']} queries | 💾 {$metrics['memory_used_mb']}MB");
    }

    /**
     * Start measuring performance
     */
    private function startMeasuring(): void
    {
        $this->queryCount = 0;
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
        
        DB::enableQueryLog();
        DB::flushQueryLog();
        
        DB::listen(function ($query) {
            $this->queryCount++;
        });
    }

    /**
     * Stop measuring and record metrics
     */
    private function stopMeasuring(string $label): array
    {
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        $queries = DB::getQueryLog();
        
        $metrics = [
            'label' => $label,
            'response_time_ms' => round(($endTime - $this->startTime) * 1000, 2),
            'query_count' => $this->queryCount,
            'memory_used_mb' => round(($endMemory - $this->startMemory) / 1024 / 1024, 2),
            'queries' => collect($queries)->take(30)->map(function ($query) {
                return [
                    'query' => $query['query'],
                    'bindings' => $query['bindings'],
                    'time' => $query['time']
                ];
            })->toArray()
        ];
        
        DB::disableQueryLog();
        $this->metrics[$label] = $metrics;
        
        return $metrics;
    }

    /**
     * Display benchmark results
     */
    private function displayResults(): void
    {
        $this->newLine();
        $this->info('📊 Benchmark Results Summary');
        $this->newLine();
        
        $headers = ['Endpoint', 'Response Time (ms)', 'Queries', 'Memory (MB)'];
        $rows = [];
        
        foreach ($this->metrics as $metric) {
            $rows[] = [
                $metric['label'],
                $metric['response_time_ms'],
                $metric['query_count'],
                $metric['memory_used_mb']
            ];
        }
        
        $this->table($headers, $rows);
    }

    /**
     * Export metrics to markdown file
     */
    private function exportToMarkdown(string $filename): void
    {
        $markdown = "# API Performance Benchmarks - BEFORE Optimization\n\n";
        $markdown .= "**Date:** " . now()->format('Y-m-d H:i:s') . "\n";
        $markdown .= "**Database:** " . config('database.default') . "\n";
        $markdown .= "**PHP Version:** " . PHP_VERSION . "\n";
        $markdown .= "**Laravel Version:** " . app()->version() . "\n\n";
        
        // Summary table
        $markdown .= "## Summary\n\n";
        $markdown .= "| Endpoint | Response Time (ms) | Query Count | Memory (MB) |\n";
        $markdown .= "|----------|-------------------|-------------|-------------|\n";
        
        $totalTime = 0;
        $totalQueries = 0;
        $totalMemory = 0;
        
        foreach ($this->metrics as $metric) {
            $markdown .= sprintf(
                "| %s | %s | %s | %s |\n",
                $metric['label'],
                $metric['response_time_ms'],
                $metric['query_count'],
                $metric['memory_used_mb']
            );
            $totalTime += $metric['response_time_ms'];
            $totalQueries += $metric['query_count'];
            $totalMemory += $metric['memory_used_mb'];
        }
        
        $markdown .= sprintf(
            "| **TOTAL** | **%s** | **%s** | **%s** |\n\n",
            round($totalTime, 2),
            $totalQueries,
            round($totalMemory, 2)
        );
        
        // Detailed analysis
        $markdown .= "## Detailed Analysis\n\n";
        
        foreach ($this->metrics as $metric) {
            $markdown .= "### {$metric['label']}\n\n";
            $markdown .= "**Performance Metrics:**\n";
            $markdown .= "- Response Time: {$metric['response_time_ms']}ms\n";
            $markdown .= "- Query Count: {$metric['query_count']}\n";
            $markdown .= "- Memory Used: {$metric['memory_used_mb']}MB\n\n";
            
            if (!empty($metric['queries'])) {
                $markdown .= "**Query Samples (first 30):**\n\n";
                foreach ($metric['queries'] as $i => $query) {
                    $shortQuery = substr(str_replace(["\n", "\r"], ' ', $query['query']), 0, 150);
                    $markdown .= ($i + 1) . ". `{$shortQuery}...` ({$query['time']}ms)\n";
                }
                $markdown .= "\n";
            }
            
            $markdown .= "---\n\n";
        }
        
        // Performance issues identified
        $markdown .= "## Performance Issues Identified\n\n";
        $markdown .= "### N+1 Query Problems\n\n";
        $markdown .= "1. **Training Listings:** Multiple queries per training for registration statistics\n";
        $markdown .= "2. **Exam Listings:** Repeated queries for completion and pass rates\n";
        $markdown .= "3. **Heavy Eager Loading:** Loading full module/lesson/question datasets unnecessarily\n\n";
        
        $markdown .= "### Optimization Opportunities\n\n";
        $markdown .= "- Replace N+1 queries with SQL aggregations (withCount)\n";
        $markdown .= "- Remove unnecessary eager loading\n";
        $markdown .= "- Add conditional loading with query parameters\n";
        $markdown .= "- Implement database indexes on frequently queried columns\n";
        $markdown .= "- Add caching layer for frequently accessed data\n";
        $markdown .= "- Queue notification jobs for async processing\n\n";
        
        file_put_contents($filename, $markdown);
    }
}

