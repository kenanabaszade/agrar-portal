<?php

namespace Tests\Performance;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;

class BenchmarkTest extends TestCase
{
    use RefreshDatabase;

    private array $metrics = [];
    private int $queryCount = 0;
    private float $startTime = 0;
    private float $startMemory = 0;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed performance test data
        Artisan::call('db:seed', ['--class' => 'PerformanceTestSeeder']);
        
        // Create admin user for testing
        $this->actingAs(User::factory()->create([
            'user_type' => 'admin',
            'email' => 'admin@test.com'
        ]));
    }

    /**
     * Start measuring performance metrics
     */
    private function startMeasuring(string $label): void
    {
        $this->queryCount = 0;
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
        
        // Enable query logging
        DB::enableQueryLog();
        
        // Count queries using listener
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
        
        $metrics = [
            'label' => $label,
            'response_time_ms' => round(($endTime - $this->startTime) * 1000, 2),
            'query_count' => $this->queryCount,
            'memory_used_mb' => round(($endMemory - $this->startMemory) / 1024 / 1024, 2),
            'queries' => collect(DB::getQueryLog())->take(20)->map(function ($query) {
                return [
                    'query' => $query['query'],
                    'time' => $query['time']
                ];
            })->toArray()
        ];
        
        DB::disableQueryLog();
        $this->metrics[$label] = $metrics;
        
        return $metrics;
    }

    /**
     * Test training listings performance
     */
    public function test_training_listings_performance()
    {
        $this->startMeasuring('training_listings');
        
        $response = $this->getJson('/api/v1/trainings?per_page=50');
        
        $metrics = $this->stopMeasuring('training_listings');
        
        $response->assertStatus(200);
        
        dump("Training Listings Metrics:", $metrics);
        
        $this->assertLessThan(5000, $metrics['response_time_ms'], 
            "Training listings should respond in less than 5 seconds");
    }

    /**
     * Test exam listings performance
     */
    public function test_exam_listings_performance()
    {
        $this->startMeasuring('exam_listings');
        
        $response = $this->getJson('/api/v1/exams?per_page=50');
        
        $metrics = $this->stopMeasuring('exam_listings');
        
        $response->assertStatus(200);
        
        dump("Exam Listings Metrics:", $metrics);
        
        $this->assertLessThan(5000, $metrics['response_time_ms'], 
            "Exam listings should respond in less than 5 seconds");
    }

    /**
     * Test training details performance
     */
    public function test_training_details_performance()
    {
        $this->startMeasuring('training_details');
        
        $response = $this->getJson('/api/v1/trainings/1');
        
        $metrics = $this->stopMeasuring('training_details');
        
        $response->assertStatus(200);
        
        dump("Training Details Metrics:", $metrics);
        
        $this->assertLessThan(2000, $metrics['response_time_ms'], 
            "Training details should respond in less than 2 seconds");
    }

    /**
     * Test exam details performance
     */
    public function test_exam_details_performance()
    {
        $this->startMeasuring('exam_details');
        
        $response = $this->getJson('/api/v1/exams/1');
        
        $metrics = $this->stopMeasuring('exam_details');
        
        $response->assertStatus(200);
        
        dump("Exam Details Metrics:", $metrics);
        
        $this->assertLessThan(2000, $metrics['response_time_ms'], 
            "Exam details should respond in less than 2 seconds");
    }

    /**
     * Get all metrics for reporting
     */
    public function getAllMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * Export metrics to markdown format
     */
    public static function exportMetricsToMarkdown(array $metrics, string $filename): void
    {
        $markdown = "# Performance Benchmarks\n\n";
        $markdown .= "**Date:** " . now()->format('Y-m-d H:i:s') . "\n\n";
        $markdown .= "## Summary\n\n";
        $markdown .= "| Endpoint | Response Time (ms) | Query Count | Memory (MB) |\n";
        $markdown .= "|----------|-------------------|-------------|-------------|\n";
        
        foreach ($metrics as $metric) {
            $markdown .= sprintf(
                "| %s | %s | %s | %s |\n",
                $metric['label'],
                $metric['response_time_ms'],
                $metric['query_count'],
                $metric['memory_used_mb']
            );
        }
        
        $markdown .= "\n## Detailed Query Analysis\n\n";
        
        foreach ($metrics as $metric) {
            $markdown .= "### {$metric['label']}\n\n";
            $markdown .= "- **Response Time:** {$metric['response_time_ms']}ms\n";
            $markdown .= "- **Query Count:** {$metric['query_count']}\n";
            $markdown .= "- **Memory Used:** {$metric['memory_used_mb']}MB\n\n";
            
            if (!empty($metric['queries'])) {
                $markdown .= "**Sample Queries (first 20):**\n\n";
                foreach ($metric['queries'] as $i => $query) {
                    $markdown .= ($i + 1) . ". `" . substr($query['query'], 0, 100) . "...` ({$query['time']}ms)\n";
                }
                $markdown .= "\n";
            }
        }
        
        file_put_contents($filename, $markdown);
    }
}

