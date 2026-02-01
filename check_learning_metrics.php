<?php

require 'vendor/autoload.php';

use Symfony\Component\Process\Process;

function runMetrics() {
    $process = new Process(['./vendor/bin/phpmetrics', '--report-json=report.json', 'Modules/Learning']);
    $process->setTimeout(300);
    $process->run();

    if (!$process->isSuccessful()) {
        echo "Error running phpmetrics: " . $process->getErrorOutput();
        return;
    }

    if (!file_exists('report.json')) {
        echo "report.json not found.\n";
        return;
    }

    $json = json_decode(file_get_contents('report.json'), true);
    if (!$json) {
        echo "Invalid JSON.\n";
        return;
    }

    $targets = [
        'Modules\Learning\Services\AssignmentService',
        'Modules\Learning\Services\SubmissionService',
        'Modules\Learning\Services\Support\AssignmentFinder',
        'Modules\Learning\Services\Support\AssignmentPrerequisiteProcessor',
        'Modules\Learning\Services\Support\AssignmentOverrideProcessor',
        'Modules\Learning\Services\Support\AssignmentDuplicator',
        'Modules\Learning\Services\Support\SubmissionFinder',
        'Modules\Learning\Services\Support\SubmissionValidator',
        'Modules\Learning\Services\Support\SubmissionLifecycleProcessor',
    ];

    echo "Class | CCN | MI\n";
    echo "---|---|---\n";

    foreach ($targets as $target) {
        $found = false;
        foreach ($json as $key => $metrics) {
            // Check if key matches class name or name field matches
            $metricName = $metrics['name'] ?? '';
            
            if ($metricName === $target || $key === $target) {
                 $ccn = $metrics['ccn'] ?? 'N/A';
                 $mi = $metrics['mi'] ?? 'N/A';
                 echo "$target | $ccn | $mi\n";
                 $found = true;
                 break;
            }
        }
        if (!$found) {
            echo "$target | Not Found | -\n";
        }
    }
}

runMetrics();
