<?php

/**
 * Repository Filter/Sort Configuration Analysis Script
 *
 * This script scans all repositories in the project and analyzes their
 * filtering and sorting approaches to identify:
 * - Repositories using Spatie QueryBuilder
 * - Repositories using custom QueryFilter
 * - Repositories with no filtering
 * - Current filter/sort configurations
 */

require __DIR__.'/../vendor/autoload.php';

class RepositoryAnalyzer
{
    private array $results = [
        'spatie' => [],
        'custom' => [],
        'none' => [],
        'summary' => [
            'total' => 0,
            'spatie_count' => 0,
            'custom_count' => 0,
            'none_count' => 0,
        ],
    ];

    public function analyze(): void
    {
        echo "🔍 Scanning repositories...\n\n";

        $repositoryFiles = $this->findRepositoryFiles();

        foreach ($repositoryFiles as $file) {
            $this->analyzeRepository($file);
        }

        $this->generateReport();
    }

    private function findRepositoryFiles(): array
    {
        $files = [];

        // Scan main app/Repositories
        $files = array_merge($files, glob(__DIR__.'/../app/Repositories/*Repository.php'));

        // Scan module repositories
        $moduleDirs = glob(__DIR__.'/../Modules/*/app/Repositories', GLOB_ONLYDIR);
        foreach ($moduleDirs as $dir) {
            $moduleFiles = glob($dir.'/*Repository.php');
            $files = array_merge($files, $moduleFiles);
        }

        return $files;
    }

    private function analyzeRepository(string $filePath): void
    {
        $this->results['summary']['total']++;

        $content = file_get_contents($filePath);
        $relativePath = str_replace(__DIR__.'/../', '', $filePath);

        $className = $this->extractClassName($content);
        $namespace = $this->extractNamespace($content);

        $analysis = [
            'file' => $relativePath,
            'class' => $className,
            'namespace' => $namespace,
            'extends' => $this->extractExtends($content),
            'implements' => $this->extractImplements($content),
            'uses_spatie' => $this->usesSpatie($content),
            'uses_custom_filter' => $this->usesCustomFilter($content),
            'uses_filterable_trait' => $this->usesFilterableTrait($content),
            'allowed_filters' => $this->extractAllowedFilters($content),
            'allowed_sorts' => $this->extractAllowedSorts($content),
            'default_sort' => $this->extractDefaultSort($content),
            'has_paginate_method' => $this->hasPaginateMethod($content),
            'filter_methods' => $this->extractFilterMethods($content),
        ];

        // Categorize repository
        if ($analysis['uses_spatie']) {
            $this->results['spatie'][] = $analysis;
            $this->results['summary']['spatie_count']++;
        } elseif ($analysis['uses_custom_filter'] || $analysis['uses_filterable_trait'] || ! empty($analysis['allowed_filters'])) {
            $this->results['custom'][] = $analysis;
            $this->results['summary']['custom_count']++;
        } else {
            $this->results['none'][] = $analysis;
            $this->results['summary']['none_count']++;
        }
    }

    private function extractClassName(string $content): string
    {
        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            return $matches[1];
        }

        return 'Unknown';
    }

    private function extractNamespace(string $content): string
    {
        if (preg_match('/namespace\s+([\w\\\\]+);/', $content, $matches)) {
            return $matches[1];
        }

        return 'Unknown';
    }

    private function extractExtends(string $content): ?string
    {
        if (preg_match('/class\s+\w+\s+extends\s+([\w\\\\]+)/', $content, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function extractImplements(string $content): array
    {
        if (preg_match('/class\s+\w+(?:\s+extends\s+[\w\\\\]+)?\s+implements\s+([\w\\\\,\s]+)/', $content, $matches)) {
            return array_map('trim', explode(',', $matches[1]));
        }

        return [];
    }

    private function usesSpatie(string $content): bool
    {
        return strpos($content, 'Spatie\QueryBuilder') !== false ||
               strpos($content, 'QueryBuilder::for') !== false;
    }

    private function usesCustomFilter(string $content): bool
    {
        return strpos($content, 'QueryFilter') !== false &&
               strpos($content, 'Spatie') === false;
    }

    private function usesFilterableTrait(string $content): bool
    {
        return strpos($content, 'use FilterableRepository') !== false;
    }

    private function extractAllowedFilters(string $content): array
    {
        $filters = [];

        // Extract from property declaration
        if (preg_match('/protected\s+array\s+\$allowedFilters\s*=\s*\[(.*?)\];/s', $content, $matches)) {
            $filterString = $matches[1];
            preg_match_all("/['\"]([^'\"]+)['\"]/", $filterString, $filterMatches);
            $filters = array_merge($filters, $filterMatches[1]);
        }

        // Extract from Spatie allowedFilters() method
        if (preg_match('/->allowedFilters\(\[(.*?)\]\)/s', $content, $matches)) {
            $filterString = $matches[1];
            // Extract filter names from AllowedFilter::partial('name') or AllowedFilter::exact('name')
            preg_match_all("/AllowedFilter::\w+\(['\"]([^'\"]+)['\"]\)/", $filterString, $filterMatches);
            $filters = array_merge($filters, $filterMatches[1]);
            // Also extract simple string filters
            preg_match_all("/['\"]([^'\"]+)['\"]/", $filterString, $simpleMatches);
            foreach ($simpleMatches[1] as $match) {
                if (! in_array($match, $filters)) {
                    $filters[] = $match;
                }
            }
        }

        return array_unique($filters);
    }

    private function extractAllowedSorts(string $content): array
    {
        $sorts = [];

        // Extract from property declaration
        if (preg_match('/protected\s+array\s+\$allowedSorts\s*=\s*\[(.*?)\];/s', $content, $matches)) {
            $sortString = $matches[1];
            preg_match_all("/['\"]([^'\"]+)['\"]/", $sortString, $sortMatches);
            $sorts = $sortMatches[1];
        }

        // Extract from Spatie allowedSorts() method
        if (preg_match('/->allowedSorts\(\[(.*?)\]\)/s', $content, $matches)) {
            $sortString = $matches[1];
            preg_match_all("/['\"]([^'\"]+)['\"]/", $sortString, $sortMatches);
            $sorts = array_merge($sorts, $sortMatches[1]);
        }

        return array_unique($sorts);
    }

    private function extractDefaultSort(string $content): ?string
    {
        // Extract from property declaration
        if (preg_match('/protected\s+string\s+\$defaultSort\s*=\s*[\'"]([^\'"]+)[\'"];/', $content, $matches)) {
            return $matches[1];
        }

        // Extract from Spatie defaultSort() method
        if (preg_match('/->defaultSort\([\'"]([^\'"]+)[\'"]\)/', $content, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function hasPaginateMethod(string $content): bool
    {
        return preg_match('/public\s+function\s+paginate\s*\(/', $content) === 1;
    }

    private function extractFilterMethods(string $content): array
    {
        $methods = [];

        // Look for methods that might handle filtering
        if (preg_match_all('/public\s+function\s+([\w]+)\s*\([^)]*\$filters/', $content, $matches)) {
            $methods = $matches[1];
        }

        return $methods;
    }

    private function generateReport(): void
    {
        echo "\n".str_repeat('=', 80)."\n";
        echo "REPOSITORY FILTER/SORT ANALYSIS REPORT\n";
        echo str_repeat('=', 80)."\n\n";

        echo "📊 SUMMARY\n";
        echo str_repeat('-', 80)."\n";
        echo "Total Repositories: {$this->results['summary']['total']}\n";
        echo "Using Spatie QueryBuilder: {$this->results['summary']['spatie_count']}\n";
        echo "Using Custom QueryFilter: {$this->results['summary']['custom_count']}\n";
        echo "No Filtering: {$this->results['summary']['none_count']}\n\n";

        // Spatie repositories
        if (! empty($this->results['spatie'])) {
            echo "\n🔷 REPOSITORIES USING SPATIE QUERYBUILDER ({$this->results['summary']['spatie_count']})\n";
            echo str_repeat('-', 80)."\n";
            foreach ($this->results['spatie'] as $repo) {
                $this->printRepositoryDetails($repo);
            }
        }

        // Custom filter repositories
        if (! empty($this->results['custom'])) {
            echo "\n🔶 REPOSITORIES USING CUSTOM QUERYFILTER ({$this->results['summary']['custom_count']})\n";
            echo str_repeat('-', 80)."\n";
            foreach ($this->results['custom'] as $repo) {
                $this->printRepositoryDetails($repo);
            }
        }

        // No filtering repositories
        if (! empty($this->results['none'])) {
            echo "\n⚪ REPOSITORIES WITHOUT FILTERING ({$this->results['summary']['none_count']})\n";
            echo str_repeat('-', 80)."\n";
            foreach ($this->results['none'] as $repo) {
                $this->printRepositoryDetails($repo);
            }
        }

        echo "\n".str_repeat('=', 80)."\n";
        echo "Analysis complete!\n";
        echo str_repeat('=', 80)."\n";
    }

    private function printRepositoryDetails(array $repo): void
    {
        echo "\n📁 {$repo['file']}\n";
        echo "   Class: {$repo['class']}\n";

        if ($repo['extends']) {
            echo "   Extends: {$repo['extends']}\n";
        }

        if (! empty($repo['implements'])) {
            echo '   Implements: '.implode(', ', $repo['implements'])."\n";
        }

        if ($repo['uses_spatie']) {
            echo "   ✓ Uses Spatie QueryBuilder\n";
        }

        if ($repo['uses_custom_filter']) {
            echo "   ✓ Uses Custom QueryFilter\n";
        }

        if ($repo['uses_filterable_trait']) {
            echo "   ✓ Uses FilterableRepository trait\n";
        }

        if (! empty($repo['allowed_filters'])) {
            echo '   Allowed Filters: '.implode(', ', $repo['allowed_filters'])."\n";
        } else {
            echo "   Allowed Filters: (none defined)\n";
        }

        if (! empty($repo['allowed_sorts'])) {
            echo '   Allowed Sorts: '.implode(', ', $repo['allowed_sorts'])."\n";
        } else {
            echo "   Allowed Sorts: (none defined)\n";
        }

        if ($repo['default_sort']) {
            echo "   Default Sort: {$repo['default_sort']}\n";
        }

        if ($repo['has_paginate_method']) {
            echo "   ✓ Has paginate() method\n";
        }

        if (! empty($repo['filter_methods'])) {
            echo '   Filter Methods: '.implode(', ', $repo['filter_methods'])."\n";
        }
    }

    public function getResults(): array
    {
        return $this->results;
    }
}

// Run the analyzer
$analyzer = new RepositoryAnalyzer;
$analyzer->analyze();

// Save results to JSON for programmatic access
$resultsFile = __DIR__.'/../storage/app/repository-analysis.json';
file_put_contents($resultsFile, json_encode($analyzer->getResults(), JSON_PRETTY_PRINT));
echo "\n💾 Results saved to: storage/app/repository-analysis.json\n";
