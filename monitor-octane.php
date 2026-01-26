#!/usr/bin/env php
<?php

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

/*
 * Octane Performance Monitoring Script
 *
 * This script provides insights into your Octane server's performance
 * and worker statistics.
 */

use Illuminate\Support\Facades\App;

// Initialize the Laravel application
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

if (!App::environment('local', 'testing')) {
    die("This script should only be run in local/testing environments\n");
}

// Check if we're running Octane with Swoole
if (extension_loaded('swoole') && app()->bound(\Swoole\Http\Server::class)) {
    echo "Octane Swoole Server Statistics:\n";
    echo str_repeat("-", 40) . "\n";

    $server = app()->get(\Swoole\Http\Server::class);
    $stats = $server->stats();

    foreach ($stats as $key => $value) {
        echo sprintf("%-25s: %s\n", ucfirst(str_replace('_', ' ', $key)), $value);
    }

    echo str_repeat("-", 40) . "\n";
    echo "Worker Information:\n";
    echo "- Total Workers: " . ($stats['worker_num'] ?? 'Unknown') . "\n";
    echo "- Task Workers: " . ($stats['task_worker_num'] ?? 'Unknown') . "\n";
    echo "- Idle Workers: " . ($stats['idle_worker_num'] ?? 'Unknown') . "\n";
    echo "- Active Connections: " . ($stats['connection_num'] ?? 'Unknown') . "\n";
    echo "- Accepted Connections: " . ($stats['accept_count'] ?? 'Unknown') . "\n";
    echo "- Closed Connections: " . ($stats['close_count'] ?? 'Unknown') . "\n";
    echo "- Queued Requests: " . ($stats['request_count'] - $stats['response_count'] ?? 0) . "\n";

    // Memory usage information
    echo str_repeat("-", 40) . "\n";
    echo "Memory Usage:\n";
    echo "- Current Process Memory: " . round(memory_get_usage(true) / 1024 / 1024, 2) . " MB\n";
    echo "- Peak Memory Usage: " . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB\n";

} else {
    echo "Octane Swoole server is not running.\n";
    echo "Make sure you've started Octane with: php artisan octane:start\n";
}

echo "\nAdditional Performance Tips:\n";
echo "- Monitor memory usage with: ps aux | grep swoole\n";
echo "- Check logs at: " . storage_path('logs/swoole_http.log') . "\n";
echo "- Restart Octane workers periodically to prevent memory leaks\n";
echo "- Run performance benchmarks with: php benchmark-octane.php\n";