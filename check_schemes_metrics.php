<?php

require 'vendor/autoload.php';

function runMetrics() {
    $reportPath = 'public/report/report.json';
    if (!file_exists($reportPath)) {
        // Try root report.json if public one doesn't exist (though user ran command to public)
        $reportPath = 'report.json';
        if (!file_exists($reportPath)) {
             echo "report.json not found in public/report or root.\n";
             return;
        }
    }

    $jsonContent = file_get_contents($reportPath);
    $json = json_decode($jsonContent, true);
    
    if (!$json) {
        echo "Invalid JSON in $reportPath.\n";
        return;
    }

    echo "Report source: $reportPath\n\n";
    echo "Class | CCN | MI\n";
    echo "---|---|---\n";

    $results = [];

    foreach ($json as $key => $metrics) {
        $name = $metrics['name'] ?? $key;
        
        if (strpos($name, 'Modules\\Schemes') !== false || strpos($name, 'Modules/Schemes') !== false) {
             $ccn = $metrics['ccn'] ?? 0;
             $mi = $metrics['mi'] ?? 0;
             $results[] = [
                 'name' => $name,
                 'ccn' => $ccn,
                 'mi' => $mi
             ];
        }
    }

    // Sort by CCN descending
    usort($results, function ($a, $b) {
        return $b['ccn'] <=> $a['ccn'];
    });

    foreach ($results as $row) {
        // Show interesting classes (Services, Supports, Repositories) or high CCN
        if ($row['ccn'] > 1 || strpos($row['name'], 'Service') !== false || strpos($row['name'], 'Processor') !== false || strpos($row['name'], 'Finder') !== false) {
            echo "{$row['name']} | {$row['ccn']} | {$row['mi']}\n";
        }
    }
}

runMetrics();
