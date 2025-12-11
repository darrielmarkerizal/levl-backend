<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionException;

class GenerateFilterDocumentation extends Command
{
    protected $signature = 'docs:filters';

    protected $description = 'Generate documentation for all API filters and sorts';

    private array $repositories = [];

    public function handle(): int
    {
        $this->info('Scanning repositories for filter/sort configurations...');

        $this->scanRepositories();

        if (empty($this->repositories)) {
            $this->warn('No repositories with filter/sort configurations found.');

            return self::FAILURE;
        }

        $this->info('Found '.count($this->repositories).' repositories with configurations.');

        $markdown = $this->generateMarkdown();

        $outputPath = base_path('docs/API_FILTERS_AND_SORTS.md');
        File::put($outputPath, $markdown);

        $this->info("Documentation generated successfully at: {$outputPath}");

        return self::SUCCESS;
    }

    private function scanRepositories(): void
    {
        // Scan app/Repositories
        $appRepoPath = app_path('Repositories');
        if (is_dir($appRepoPath)) {
            $this->scanDirectory($appRepoPath);
        }

        // Scan module repositories
        $modulesPath = base_path('Modules');
        if (is_dir($modulesPath)) {
            $modules = glob($modulesPath.'/*', GLOB_ONLYDIR);

            foreach ($modules as $modulePath) {
                $repoPath = $modulePath.'/app/Repositories';
                if (is_dir($repoPath)) {
                    $this->scanDirectory($repoPath);
                }
            }
        }
    }

    private function scanDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $files = glob($directory.'/*.php');

        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            $this->analyzeRepository($file);
        }
    }

    private function analyzeRepository(string $filePath): void
    {
        $content = File::get($filePath);

        // Extract namespace
        if (! preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch)) {
            return;
        }

        $namespace = $namespaceMatch[1];

        // Extract class name
        if (! preg_match('/class\s+(\w+)/', $content, $classMatch)) {
            return;
        }

        $className = $classMatch[1];
        $fullClassName = $namespace.'\\'.$className;

        $approach = $this->detectApproach($content);

        // Check if this repository has any filter/sort configuration
        $hasPropertyConfig = str_contains($content, '$allowedFilters') || str_contains($content, '$allowedSorts');
        $hasSpatieConfig = str_contains($content, '->allowedFilters(') || str_contains($content, '->allowedSorts(');

        if (! $hasPropertyConfig && ! $hasSpatieConfig) {
            return;
        }

        $config = [
            'class' => $fullClassName,
            'file' => str_replace(base_path().'/', '', $filePath),
            'filters' => [],
            'sorts' => [],
            'defaultSort' => null,
            'approach' => $approach,
        ];

        // Extract configuration based on approach
        if ($approach === 'Spatie QueryBuilder') {
            $config['filters'] = $this->extractSpatieFilters($content);
            $config['sorts'] = $this->extractSpatieSorts($content);
            $config['defaultSort'] = $this->extractSpatieDefaultSort($content);
        } else {
            // Try reflection first for property-based config
            try {
                if (class_exists($fullClassName)) {
                    $reflection = new ReflectionClass($fullClassName);

                    if (! $reflection->isAbstract() && ! $reflection->isInterface()) {
                        if ($reflection->hasProperty('allowedFilters')) {
                            $config['filters'] = $this->extractArrayFromSource($content, 'allowedFilters');
                        }

                        if ($reflection->hasProperty('allowedSorts')) {
                            $config['sorts'] = $this->extractArrayFromSource($content, 'allowedSorts');
                        }

                        if ($reflection->hasProperty('defaultSort')) {
                            $config['defaultSort'] = $this->extractStringFromSource($content, 'defaultSort');
                        }
                    }
                }
            } catch (ReflectionException $e) {
                // Fall back to source code extraction
                $config['filters'] = $this->extractArrayFromSource($content, 'allowedFilters');
                $config['sorts'] = $this->extractArrayFromSource($content, 'allowedSorts');
                $config['defaultSort'] = $this->extractStringFromSource($content, 'defaultSort');
            }
        }

        // Only add if we found some configuration
        if (! empty($config['filters']) || ! empty($config['sorts'])) {
            $config['endpoint'] = $this->inferEndpoint($className, $namespace);
            $this->repositories[] = $config;
        }
    }

    private function detectApproach(string $content): string
    {
        if (str_contains($content, 'Spatie\QueryBuilder\QueryBuilder')) {
            return 'Spatie QueryBuilder';
        }

        if (str_contains($content, 'FilterableRepository')) {
            return 'Custom QueryFilter';
        }

        return 'Unknown';
    }

    private function extractSpatieFilters(string $content): array
    {
        $filters = [];

        // Match ->allowedFilters([...])
        if (preg_match('/->allowedFilters\(\[(.*?)\]\)/s', $content, $matches)) {
            $filtersContent = $matches[1];

            // Extract AllowedFilter::exact('field'), AllowedFilter::partial('field'), or plain strings
            preg_match_all('/AllowedFilter::\w+\([\'"]([^\'"]+)[\'"]\)|[\'"]([^\'"]+)[\'"]/', $filtersContent, $filterMatches);

            foreach ($filterMatches[1] as $filter) {
                if (! empty($filter)) {
                    $filters[] = $filter;
                }
            }

            foreach ($filterMatches[2] as $filter) {
                if (! empty($filter) && ! in_array($filter, $filters)) {
                    $filters[] = $filter;
                }
            }
        }

        return $filters;
    }

    private function extractSpatieSorts(string $content): array
    {
        $sorts = [];

        // Match ->allowedSorts([...])
        if (preg_match('/->allowedSorts\(\[(.*?)\]\)/s', $content, $matches)) {
            $sortsContent = $matches[1];

            // Extract quoted strings
            preg_match_all('/[\'"]([^\'"]+)[\'"]/', $sortsContent, $sortMatches);

            $sorts = $sortMatches[1] ?? [];
        }

        return $sorts;
    }

    private function extractSpatieDefaultSort(string $content): ?string
    {
        // Match ->defaultSort('field') or ->defaultSort('-field')
        if (preg_match('/->defaultSort\([\'"]([^\'"]+)[\'"]\)/', $content, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function extractArrayFromSource(string $content, string $propertyName): array
    {
        $pattern = '/protected\s+array\s+\$'.$propertyName.'\s*=\s*\[(.*?)\];/s';

        if (preg_match($pattern, $content, $matches)) {
            $arrayContent = $matches[1];

            // Extract quoted strings
            preg_match_all('/[\'"]([^\'"]+)[\'"]/', $arrayContent, $stringMatches);

            return $stringMatches[1] ?? [];
        }

        return [];
    }

    private function extractStringFromSource(string $content, string $propertyName): ?string
    {
        $pattern = '/protected\s+string\s+\$'.$propertyName.'\s*=\s*[\'"]([^\'"]+)[\'"];/';

        if (preg_match($pattern, $content, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function inferEndpoint(string $className, string $namespace): string
    {
        // Remove "Repository" suffix
        $resource = str_replace('Repository', '', $className);

        // Convert to kebab-case
        $resource = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $resource));

        // Determine module from namespace
        if (preg_match('/Modules\\\\(\w+)\\\\/', $namespace, $matches)) {
            $module = strtolower($matches[1]);

            return "/api/{$module}/{$resource}";
        }

        return "/api/{$resource}";
    }

    private function generateMarkdown(): string
    {
        $markdown = "# API Filters and Sorts Documentation\n\n";
        $markdown .= '**Generated:** '.now()->format('F j, Y \a\t g:i A')."\n\n";
        $markdown .= "This document lists all available filter and sort parameters for API endpoints.\n\n";

        $markdown .= "## Table of Contents\n\n";
        $markdown .= "- [Overview](#overview)\n";
        $markdown .= "- [Filter Operators](#filter-operators)\n";
        $markdown .= "- [Sort Syntax](#sort-syntax)\n";
        $markdown .= "- [Endpoints](#endpoints)\n\n";

        $markdown .= "---\n\n";

        $markdown .= $this->generateOverviewSection();
        $markdown .= $this->generateOperatorsSection();
        $markdown .= $this->generateSortSyntaxSection();
        $markdown .= $this->generateEndpointsSection();

        return $markdown;
    }

    private function generateOverviewSection(): string
    {
        $section = "## Overview\n\n";
        $section .= "All API endpoints support standardized filtering and sorting through query parameters.\n\n";
        $section .= "### Basic Usage\n\n";
        $section .= "```\n";
        $section .= "GET /api/resource?filter[field]=value&sort=-created_at&per_page=20\n";
        $section .= "```\n\n";
        $section .= "### Parameters\n\n";
        $section .= "- `filter[field]` - Filter by field value\n";
        $section .= "- `sort` - Sort by field (prefix with `-` for descending)\n";
        $section .= "- `page` - Page number (default: 1)\n";
        $section .= "- `per_page` - Items per page (default: 15)\n";
        $section .= "- `search` - Full-text search (where supported)\n\n";
        $section .= "---\n\n";

        return $section;
    }

    private function generateOperatorsSection(): string
    {
        $section = "## Filter Operators\n\n";
        $section .= "Filters support various operators for different comparison types:\n\n";
        $section .= "| Operator | SQL Equivalent | Example | Description |\n";
        $section .= "|----------|---------------|---------|-------------|\n";
        $section .= "| (none) or `eq` | `=` | `filter[status]=published` | Exact match (default) |\n";
        $section .= "| `neq` | `!=` | `filter[status]=neq:draft` | Not equal |\n";
        $section .= "| `gt` | `>` | `filter[views]=gt:100` | Greater than |\n";
        $section .= "| `gte` | `>=` | `filter[views]=gte:100` | Greater than or equal |\n";
        $section .= "| `lt` | `<` | `filter[views]=lt:1000` | Less than |\n";
        $section .= "| `lte` | `<=` | `filter[views]=lte:1000` | Less than or equal |\n";
        $section .= "| `like` | `LIKE` | `filter[title]=like:%keyword%` | Partial match |\n";
        $section .= "| `in` | `IN` | `filter[status]=in:draft,published` | Multiple values |\n";
        $section .= "| `between` | `BETWEEN` | `filter[created_at]=between:2025-01-01,2025-12-31` | Range |\n\n";
        $section .= "### Examples\n\n";
        $section .= "```\n";
        $section .= "# Exact match\n";
        $section .= "GET /api/courses?filter[status]=published\n\n";
        $section .= "# Greater than\n";
        $section .= "GET /api/courses?filter[views_count]=gt:100\n\n";
        $section .= "# Multiple values\n";
        $section .= "GET /api/courses?filter[level_tag]=in:beginner,intermediate\n\n";
        $section .= "# Date range\n";
        $section .= "GET /api/courses?filter[created_at]=between:2025-01-01,2025-12-31\n\n";
        $section .= "# Combine multiple filters\n";
        $section .= "GET /api/courses?filter[status]=published&filter[level_tag]=beginner&filter[views_count]=gt:50\n";
        $section .= "```\n\n";
        $section .= "---\n\n";

        return $section;
    }

    private function generateSortSyntaxSection(): string
    {
        $section = "## Sort Syntax\n\n";
        $section .= "Sorting is controlled by the `sort` parameter:\n\n";
        $section .= "- **Ascending:** `sort=field_name`\n";
        $section .= "- **Descending:** `sort=-field_name` (prefix with `-`)\n\n";
        $section .= "### Examples\n\n";
        $section .= "```\n";
        $section .= "# Sort by created_at ascending\n";
        $section .= "GET /api/courses?sort=created_at\n\n";
        $section .= "# Sort by created_at descending\n";
        $section .= "GET /api/courses?sort=-created_at\n\n";
        $section .= "# Sort by title ascending\n";
        $section .= "GET /api/courses?sort=title\n";
        $section .= "```\n\n";
        $section .= "If no sort parameter is provided, the endpoint's default sort will be applied.\n\n";
        $section .= "---\n\n";

        return $section;
    }

    private function generateEndpointsSection(): string
    {
        $section = "## Endpoints\n\n";

        // Group by module
        $byModule = [];
        foreach ($this->repositories as $repo) {
            $endpoint = $repo['endpoint'];
            $module = 'Core';

            if (preg_match('#/api/([^/]+)/#', $endpoint, $matches)) {
                $module = ucfirst($matches[1]);
            }

            $byModule[$module][] = $repo;
        }

        ksort($byModule);

        foreach ($byModule as $module => $repos) {
            $section .= "### {$module} Module\n\n";

            foreach ($repos as $repo) {
                $section .= $this->generateEndpointSection($repo);
            }
        }

        return $section;
    }

    private function generateEndpointSection(array $config): string
    {
        $section = "#### `{$config['endpoint']}`\n\n";
        $section .= "**Repository:** `{$config['class']}`  \n";
        $section .= "**Approach:** {$config['approach']}\n\n";

        // Filters
        if (! empty($config['filters'])) {
            $section .= "**Allowed Filters:**\n\n";
            foreach ($config['filters'] as $filter) {
                $section .= "- `{$filter}`\n";
            }
            $section .= "\n";
        } else {
            $section .= "**Allowed Filters:** None configured\n\n";
        }

        // Sorts
        if (! empty($config['sorts'])) {
            $section .= "**Allowed Sorts:**\n\n";
            foreach ($config['sorts'] as $sort) {
                $section .= "- `{$sort}`\n";
            }
            $section .= "\n";
        } else {
            $section .= "**Allowed Sorts:** None configured\n\n";
        }

        // Default sort
        if ($config['defaultSort']) {
            $section .= "**Default Sort:** `{$config['defaultSort']}`\n\n";
        }

        // Example
        $section .= "**Example:**\n\n";
        $section .= "```\n";

        if (! empty($config['filters'])) {
            $firstFilter = $config['filters'][0];
            $section .= "GET {$config['endpoint']}?filter[{$firstFilter}]=value";

            if (! empty($config['sorts'])) {
                $firstSort = $config['sorts'][0];
                $section .= "&sort=-{$firstSort}";
            }

            $section .= "\n";
        } elseif (! empty($config['sorts'])) {
            $firstSort = $config['sorts'][0];
            $section .= "GET {$config['endpoint']}?sort=-{$firstSort}\n";
        } else {
            $section .= "GET {$config['endpoint']}\n";
        }

        $section .= "```\n\n";
        $section .= "---\n\n";

        return $section;
    }
}
