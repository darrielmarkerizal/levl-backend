<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Meilisearch\Client;

class MeilisearchImportAll extends Command
{
    protected $signature = 'meilisearch:import-all {--fresh : Flush indexes before importing}';

    protected $description = 'Import all searchable models to Meilisearch';

    protected array $models = [
        \Modules\Auth\Models\User::class => 'users_index',
        \Modules\Schemes\Models\Course::class => 'courses_index',
        \Modules\Schemes\Models\Category::class => 'categories_index',
        \Modules\Schemes\Models\Tag::class => 'tags_index',
        \Modules\Schemes\Models\Unit::class => 'units_index',
        \Modules\Schemes\Models\Lesson::class => 'lessons_index',
        \Modules\Common\Models\MasterDataItem::class => 'master_data_index',
        \Modules\Learning\Models\Submission::class => 'submissions_index',
        \Modules\Learning\Models\Assignment::class => 'assignments_index',
        \Modules\Learning\Models\Question::class => 'questions_index',
        \Modules\Forums\Models\Thread::class => 'threads_index',
        \Modules\Forums\Models\Reply::class => 'replies_index',
        \Modules\Gamification\Models\Badge::class => 'badges_index',
        \Modules\Gamification\Models\Challenge::class => 'challenges_index',
        \Modules\Grading\Models\Grade::class => 'grades_index',
        \Modules\Common\Models\AuditLog::class => 'audit_logs_index',
        \Modules\Common\Models\LevelConfig::class => 'level_configs_index',
    ];

    public function handle(): int
    {
        $fresh = $this->option('fresh');
        
        if ($fresh) {
            $this->info('Flushing all indexes...');
            foreach ($this->models as $modelClass => $indexName) {
                if (class_exists($modelClass)) {
                    $this->call('scout:flush', ['model' => $modelClass]);
                }
            }
        }

        $this->call('scout:sync-index-settings');
        $this->newLine();

        $client = new Client(config('scout.meilisearch.host'), config('scout.meilisearch.key'));

        foreach ($this->models as $modelClass => $indexName) {
            if (!class_exists($modelClass)) {
                $this->warn("Model {$modelClass} not found, skipping...");
                continue;
            }

            $this->info("Importing {$modelClass}...");
            
            $model = new $modelClass;
            $index = $client->index($indexName);
            
            $query = $model->newQuery();
            
            if (method_exists($model, 'shouldBeSearchable')) {
                $total = $query->count();
                $searchable = 0;
                
                $query->chunkById(500, function ($records) use ($index, $model, &$searchable) {
                    $documents = [];
                    foreach ($records as $record) {
                        if ($record->shouldBeSearchable()) {
                            $documents[] = $record->toSearchableArray();
                            $searchable++;
                        }
                    }
                    
                    if (!empty($documents)) {
                        $index->addDocuments($documents, $model->getKeyName());
                    }
                });
                
                $this->line("  → Imported {$searchable}/{$total} records to {$indexName}");
            } else {
                $total = $query->count();
                $query->chunkById(500, function ($records) use ($index, $model) {
                    $documents = $records->map(fn ($r) => $r->toSearchableArray())->toArray();
                    $index->addDocuments($documents, $model->getKeyName());
                });
                
                $this->line("  → Imported {$total} records to {$indexName}");
            }
        }

        $this->newLine();
        $this->info('Waiting for Meilisearch to process...');
        sleep(3);

        $this->newLine();
        $this->info('Index Statistics:');
        $this->table(
            ['Index', 'Documents', 'Indexing'],
            collect($this->models)->map(function ($indexName) use ($client) {
                try {
                    $stats = $client->index($indexName)->stats();
                    return [$indexName, $stats['numberOfDocuments'], $stats['isIndexing'] ? 'Yes' : 'No'];
                } catch (\Exception $e) {
                    return [$indexName, 'Error', '-'];
                }
            })->toArray()
        );

        $this->newLine();
        $this->info('All models imported successfully!');
        
        return self::SUCCESS;
    }
}
