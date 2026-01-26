#!/usr/bin/env php
<?php

require_once __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Performance benchmarking script for Laravel Octane
class OctaneBenchmark
{
    private string $baseUrl;
    private int $requests;
    private int $concurrency;
    
    public function __construct(string $baseUrl = 'http://127.0.0.1:8000', int $requests = 1000, int $concurrency = 100)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->requests = $requests;
        $this->concurrency = $concurrency;
    }
    
    public function run(): array
    {
        echo "Starting Octane performance benchmark...\n";
        echo "URL: {$this->baseUrl}\n";
        echo "Requests: {$this->requests}\n";
        echo "Concurrency: {$this->concurrency}\n";
        echo "----------------------------------------\n";
        
        $startTime = microtime(true);
        $responses = [];
        
        // Send concurrent requests
        $promises = [];
        for ($i = 0; $i < $this->requests; $i++) {
            $promises[] = Http::async()->get($this->baseUrl . '/api/health');
        }
        
        // Wait for all requests to complete
        foreach ($promises as $promise) {
            try {
                $response = $promise->wait();
                $responses[] = [
                    'status' => $response->status(),
                    'time' => $response->totalDuration(),
                    'size' => strlen($response->body())
                ];
            } catch (\Exception $e) {
                $responses[] = [
                    'status' => 0,
                    'time' => 0,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        $totalTime = microtime(true) - $startTime;
        
        return $this->analyzeResults($responses, $totalTime);
    }
    
    private function analyzeResults(array $responses, float $totalTime): array
    {
        $successful = array_filter($responses, fn($r) => $r['status'] >= 200 && $r['status'] < 300);
        $failed = array_filter($responses, fn($r) => $r['status'] < 200 || $r['status'] >= 300);
        
        $times = array_column($successful, 'time');
        sort($times);
        
        $results = [
            'total_requests' => count($responses),
            'successful_requests' => count($successful),
            'failed_requests' => count($failed),
            'success_rate' => count($responses) > 0 ? (count($successful) / count($responses)) * 100 : 0,
            'total_time_seconds' => round($totalTime, 2),
            'requests_per_second' => count($responses) > 0 ? round(count($responses) / $totalTime, 2) : 0,
            'average_response_time_ms' => count($successful) > 0 ? round((array_sum($times) / count($successful)) * 1000, 2) : 0,
            'median_response_time_ms' => count($times) > 0 ? round($times[floor(count($times) / 2)] * 1000, 2) : 0,
            'p95_response_time_ms' => count($times) > 0 ? round($times[(int)(count($times) * 0.95)] * 1000, 2) : 0,
            'p99_response_time_ms' => count($times) > 0 ? round($times[(int)(count($times) * 0.99)] * 1000, 2) : 0,
            'slowest_request_ms' => count($times) > 0 ? round(max($times) * 1000, 2) : 0,
            'fastest_request_ms' => count($times) > 0 ? round(min($times) * 1000, 2) : 0,
        ];
        
        $this->displayResults($results);
        
        return $results;
    }
    
    private function displayResults(array $results): void
    {
        echo "\nPERFORMANCE RESULTS:\n";
        echo "========================================\n";
        echo "Total Requests:       {$results['total_requests']}\n";
        echo "Successful:           {$results['successful_requests']}\n";
        echo "Failed:               {$results['failed_requests']}\n";
        echo "Success Rate:         {$results['success_rate']}%\n";
        echo "Total Time:           {$results['total_time_seconds']}s\n";
        echo "Requests/sec:         {$results['requests_per_second']}\n";
        echo "Avg Response Time:    {$results['average_response_time_ms']}ms\n";
        echo "Median Response Time: {$results['median_response_time_ms']}ms\n";
        echo "P95 Response Time:    {$results['p95_response_time_ms']}ms\n";
        echo "P99 Response Time:    {$results['p99_response_time_ms']}ms\n";
        echo "Slowest Request:      {$results['slowest_request_ms']}ms\n";
        echo "Fastest Request:      {$results['fastest_request_ms']}ms\n";
        echo "========================================\n";
        
        // Performance assessment
        if ($results['requests_per_second'] > 500) {
            echo "\n✅ EXCELLENT: Performance is excellent (>500 RPS)\n";
        } elseif ($results['requests_per_second'] > 300) {
            echo "\n✅ GOOD: Performance is good (300-500 RPS)\n";
        } elseif ($results['requests_per_second'] > 200) {
            echo "\n⚠️  AVERAGE: Performance is average (200-300 RPS)\n";
        } else {
            echo "\n❌ POOR: Performance needs improvement (<200 RPS)\n";
        }
    }
}

// Run the benchmark
$benchmark = new OctaneBenchmark('http://127.0.0.1:8000', 1000, 100);
$results = $benchmark->run();

// Save results to file
file_put_contents(
    storage_path('logs/octane_benchmark_' . date('Y-m-d_H-i-s') . '.json'), 
    json_encode($results, JSON_PRETTY_PRINT)
);