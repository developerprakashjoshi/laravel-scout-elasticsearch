<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Elastic\Elasticsearch\Client;
use App\Models\Post;

class AddNewFieldsReindex extends Command
{
    protected $signature = 'scout:add-fields {model} {--new-fields=} {--source-index=} {--target-index=} {--batch-size=1000}';
    protected $description = 'Add new fields to existing index with zero downtime';

    public function handle()
    {
        $modelClass = $this->argument('model');
        $newFields = $this->option('new-fields') ? explode(',', $this->option('new-fields')) : [];
        $sourceIndex = $this->option('source-index') ?: $modelClass::make()->searchableAs();
        $targetIndex = $this->option('target-index') ?: $sourceIndex . '_v2';
        $batchSize = $this->option('batch-size');

        $this->info("Adding new fields to index with zero downtime");
        $this->info("Source index: {$sourceIndex}");
        $this->info("Target index: {$targetIndex}");
        $this->info("New fields: " . implode(', ', $newFields));

        try {
            $client = app(Client::class);

            // Step 1: Create new index with updated mapping
            $this->info("Creating new index with updated mapping");
            $this->createNewIndex($client, $targetIndex, $modelClass, $newFields);

            // Step 2: Reindex data with new fields
            $this->info("Reindexing data with new fields");
            $this->reindexWithNewFields($client, $sourceIndex, $targetIndex, $modelClass, $newFields, $batchSize);

            // Step 3: Update alias
            $this->info("Updating alias to new index");
            $this->updateAlias($client, $sourceIndex, $targetIndex);

            $this->info("New fields added successfully!");

        } catch (\Exception $e) {
            $this->error("Failed to add new fields: " . $e->getMessage());
            return 1;
        }
    }

    protected function createNewIndex($client, $indexName, $modelClass, $newFields)
    {
        $mapping = $modelClass::make()->getSearchableMapping();
        
        // Add new fields to mapping
        foreach ($newFields as $field) {
            $mapping['properties'][$field] = $this->getFieldMapping($field);
        }

        $client->indices()->create([
            'index' => $indexName,
            'body' => [
                'mappings' => $mapping
            ]
        ]);
    }

    protected function reindexWithNewFields($client, $sourceIndex, $targetIndex, $modelClass, $newFields, $batchSize)
    {
        // Use Elasticsearch reindex API with script to add new fields
        $script = $this->buildScript($newFields);
        
        $response = $client->reindex([
            'body' => [
                'source' => [
                    'index' => $sourceIndex
                ],
                'dest' => [
                    'index' => $targetIndex
                ],
                'script' => [
                    'source' => $script,
                    'lang' => 'painless'
                ]
            ],
            'wait_for_completion' => false
        ]);

        $taskId = $response['task'];
        $this->info("Reindex task started: {$taskId}");

        // Monitor progress
        $this->monitorTask($client, $taskId);
    }

    protected function buildScript($newFields)
    {
        $script = "ctx._source = ctx._source + [";
        
        foreach ($newFields as $field) {
            $script .= "'{$field}': null, ";
        }
        
        $script = rtrim($script, ', ') . "]";
        
        return $script;
    }

    protected function monitorTask($client, $taskId)
    {
        $progressBar = $this->output->createProgressBar(100);
        $progressBar->start();

        while (true) {
            try {
                $task = $client->tasks()->get(['task_id' => $taskId]);
                $status = $task['status'] ?? [];

                if (isset($status['total'])) {
                    $total = $status['total'];
                    $updated = $status['updated'] ?? 0;
                    $progress = $total > 0 ? ($updated / $total) * 100 : 0;
                    $progressBar->setProgress($progress);
                }

                if (isset($task['completed']) && $task['completed']) {
                    $progressBar->finish();
                    break;
                }

                sleep(5);
            } catch (\Exception $e) {
                $this->error("Error monitoring task: " . $e->getMessage());
                break;
            }
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

    protected function getFieldMapping($field)
    {
        // Default mapping for new fields
        return [
            'type' => 'text',
            'analyzer' => 'standard',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword'
                ]
            ]
        ];
    }
} 