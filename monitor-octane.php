<?php

declare(strict_types=1);

$swooleStats = function (): array {
    if (! extension_loaded('swoole')) {
        return ['error' => 'Swoole extension not loaded'];
    }

    return [
        'cpu_num' => swoole_cpu_num(),
        'version' => SWOOLE_VERSION,
        'coroutine_num' => class_exists('Swoole\Coroutine') ? \Swoole\Coroutine::stats() : 'N/A',
    ];
};

$memoryStats = function (): array {
    return [
        'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
        'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        'memory_limit' => ini_get('memory_limit'),
    ];
};

$systemStats = function (): array {
    $cpuInfo = @file_get_contents('/proc/cpuinfo');
    $memInfo = @file_get_contents('/proc/meminfo');

    $cpuCount = $cpuInfo ? substr_count($cpuInfo, 'processor') : 'N/A';

    $totalMem = 'N/A';
    $freeMem = 'N/A';
    if ($memInfo) {
        preg_match('/MemTotal:\s+(\d+)/', $memInfo, $total);
        preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $available);
        $totalMem = isset($total[1]) ? round((int) $total[1] / 1024 / 1024, 2).' GB' : 'N/A';
        $freeMem = isset($available[1]) ? round((int) $available[1] / 1024 / 1024, 2).' GB' : 'N/A';
    }

    return [
        'cpu_cores' => $cpuCount,
        'total_memory' => $totalMem,
        'available_memory' => $freeMem,
        'load_average' => sys_getloadavg(),
    ];
};

$recommendedSettings = function (int $cpuCores, float $memoryGb): array {
    return [
        'SWOOLE_WORKER_NUM' => $cpuCores * 2,
        'SWOOLE_TASK_WORKER_NUM' => $cpuCores,
        'SWOOLE_REACTOR_NUM' => $cpuCores,
        'SWOOLE_MAX_REQUEST' => 500,
        'SWOOLE_MAX_COROUTINE' => min(1000, (int) ($memoryGb * 250)),
        'OCTANE_GARBAGE_COLLECTION' => 50,
        'DB_MAX_CONNECTIONS' => min(100, $cpuCores * 2 * 10),
    ];
};

echo "=== VPS System Info ===\n";
print_r($systemStats());

echo "\n=== Swoole Info ===\n";
print_r($swooleStats());

echo "\n=== Memory Stats ===\n";
print_r($memoryStats());

echo "\n=== Recommended Settings (2 CPU / 4GB RAM) ===\n";
print_r($recommendedSettings(2, 4.0));
