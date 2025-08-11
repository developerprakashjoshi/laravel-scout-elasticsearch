<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Elastic\Elasticsearch\Client;

class ZeroDowntimeReindex extends Command
{
    protected $signature = 'scout:reindex-zero-downtime {model} {--source-index=} {--target-index=}';
    protected $description = 'Zero-downtime reindexing using Elasticsearch reindex API';

    public function handle()
    {
        $modelClass = $this->argument('model');
        $sourceIndex = $this->option('source-index') ?: $modelClass::make()->searchableAs();
        $targetIndex = $this->option('target-index') ?: $sourceIndex . '_v2';

        $this->info("Starting zero-downtime reindex");
        $this->info("Source index: {$sourceIndex}");
        $this->info("Target index: {$targetIndex}");

        try {
            $client = app(Client::class);

            // Step 1: Create new index with new mapping
            $this->info("Creating new index: {$targetIndex}");
            $this->createNewIndex($client, $targetIndex, $modelClass);

            // Step 2: Reindex data from old to new index
            $this->info("Reindexing data from {$sourceIndex} to {$targetIndex}");
            $this->reindexData($client, $sourceIndex, $targetIndex);

            // Step 3: Update alias to point to new index
            $this->info("Updating alias to point to new index");
            $this->updateAlias($client, $sourceIndex, $targetIndex);

            $this->info("Zero-downtime reindex completed successfully!");

        } catch (\Exception $e) {
            $this->error("Reindex failed: " . $e->getMessage());
            return 1;
        }
    }

    protected function createNewIndex($client, $indexName, $modelClass)
    {
        $mapping = $modelClass::make()->getSearchableMapping();
        
        $client->indices()->create([
            'index' => $indexName,
            'body' => [
                'mappings' => $mapping
            ]
        ]);
    }

    protected function reindexData($client, $sourceIndex, $targetIndex)
    {
        $response = $client->reindex([
            'body' => [
                'source' => [
                    'index' => $sourceIndex
                ],
                'dest' => [
                    'index' => $targetIndex
                ]
            ],
            'wait_for_completion' => false
        ]);

        $taskId = $response['task'];
        $this->info("Reindex task started: {$taskId}");

        // Monitor progress
        $this->monitorTask($client, $taskId);
    }

    protected function monitorTask($client, $taskId)
    {
        $progressBar = $this->output->createProgressBar(100);
        $progressBar->start();

        while (true) {
            $task = $client->tasks()->get(['task_id' => $taskId]);
            $status = $task['status'];

            if (isset($status['total'])) {
                $total = $status['total'];
                $updated = $status['updated'] ?? 0;
                $progress = $total > 0 ? ($updated / $total) * 100 : 0;
                $progressBar->setProgress($progress);
            }

            if ($task['completed']) {
                $progressBar->finish();
                break;
            }

            sleep(5);
        }
    }

    protected function updateAlias($client, $oldIndex, $newIndex)
    {
        // Create alias pointing to new index
        $client->indices()->putAlias([
            'index' => $newIndex,
            'name' => $oldIndex
        ]);

        // Remove old index
        try {
            $client->indices()->delete(['index' => $oldIndex . '_old']);
        } catch (\Exception $e) {
            // Old index doesn't exist, ignore
        }
    }
} 