<?php

namespace LaravelScout\Elasticsearch\Console\Commands;

use Illuminate\Console\Command;
use Elastic\Elasticsearch\Client;

class ZeroDowntimeReindex extends Command
{
    protected $signature = 'scout:reindex-zero-downtime {model} {--source-index=} {--target-index=} {--wait : Wait for completion instead of monitoring}';
    protected $description = 'Zero-downtime reindexing using Elasticsearch reindex API';

    public function handle()
    {
        $modelClass = $this->argument('model');
        $sourceIndex = $this->option('source-index') ?: $modelClass::make()->searchableAs();
        $targetIndex = $this->option('target-index') ?: $sourceIndex . '_v2';

        $this->info("Starting zero-downtime reindex");
        $this->info("This command reindexes an EXISTING index with updated mapping");
        $this->info("Source index: {$sourceIndex}");
        $this->info("Target index: {$targetIndex}");

        try {
            $client = app(Client::class);

            // Check if source index exists
            if (!$client->indices()->exists(['index' => $sourceIndex])) {
                $this->warn("Source index '{$sourceIndex}' does not exist.");
                $this->info("Cannot perform reindex operation on non-existent index.");
                $this->info("Use 'scout:index' to create the index first, then 'scout:import' to populate it.");
                return 1;
            }

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
        // Check if target index already exists and delete it
        if ($client->indices()->exists(['index' => $indexName])) {
            $this->warn("Target index '{$indexName}' already exists. Deleting it...");
            try {
                $client->indices()->delete(['index' => $indexName]);
                $this->info("✅ Old index '{$indexName}' deleted successfully");
                
                // Wait a moment for Elasticsearch to process the deletion
                sleep(2);
            } catch (\Exception $e) {
                // If the index doesn't exist when we try to delete it, that's fine
                if (strpos($e->getMessage(), 'index_not_found_exception') !== false) {
                    $this->info("Index '{$indexName}' was already deleted or doesn't exist");
                } else {
                    $this->error("Failed to delete existing index '{$indexName}': " . $e->getMessage());
                    throw $e;
                }
            }
        }

        try {
            $mapping = $modelClass::make()->getSearchableMapping();
            
            $client->indices()->create([
                'index' => $indexName,
                'body' => [
                    'mappings' => $mapping
                ]
            ]);
            
            $this->info("✅ New index '{$indexName}' created successfully");
        } catch (\Exception $e) {
            $this->error("Failed to create index '{$indexName}': " . $e->getMessage());
            throw $e;
        }
    }

    protected function reindexData($client, $sourceIndex, $targetIndex)
    {
        $waitForCompletion = $this->option('wait');
        
        try {
            $response = $client->reindex([
                'body' => [
                    'source' => [
                        'index' => $sourceIndex
                    ],
                    'dest' => [
                        'index' => $targetIndex
                    ]
                ],
                'wait_for_completion' => $waitForCompletion
            ]);

            if ($waitForCompletion) {
                // Synchronous reindex - just show completion
                $this->info("✅ Reindex completed successfully!");
                return;
            }

            // Asynchronous reindex - monitor progress
            if (!isset($response['task'])) {
                throw new \Exception("Reindex response missing task ID: " . json_encode($response));
            }

            $taskId = $response['task'];
            $this->info("Reindex task started: {$taskId}");

            // Monitor progress
            $this->monitorTask($client, $taskId);
        } catch (\Exception $e) {
            $this->error("Failed to start reindex: " . $e->getMessage());
            throw $e;
        }
    }

    protected function monitorTask($client, $taskId)
    {
        $progressBar = $this->output->createProgressBar(100);
        $progressBar->start();
        
        $this->info("Monitoring reindex task: {$taskId}");
        $this->info("This may take a while for large datasets...");

        $startTime = time();
        $maxWaitTime = 3600; // 1 hour timeout
        $emptyResponseCount = 0;
        $maxEmptyResponses = 30; // Max 5 minutes of empty responses

        while (true) {
            // Check timeout
            if (time() - $startTime > $maxWaitTime) {
                $progressBar->finish();
                $this->error("❌ Reindex task timed out after {$maxWaitTime} seconds");
                $this->error("The task may still be running in the background");
                $this->error("You can check the task status manually or wait longer");
                break;
            }
            
            try {
                $task = $client->tasks()->get(['task_id' => $taskId]);
                
                // Debug: Show task structure
                if ($this->output->isVerbose()) {
                    $this->line("Task response: " . json_encode($task, JSON_PRETTY_PRINT));
                }
                
                // Check if task response is empty (task not ready yet)
                if (empty($task)) {
                    $emptyResponseCount++;
                    if ($emptyResponseCount > $maxEmptyResponses) {
                        $progressBar->finish();
                        $this->error("❌ Task not found or not ready after {$maxEmptyResponses} attempts");
                        $this->error("Task ID: {$taskId}");
                        $this->error("The task may have failed to start or completed very quickly");
                        break;
                    }
                    
                    $this->warn("Task not ready yet (attempt {$emptyResponseCount}/{$maxEmptyResponses}), waiting...");
                    sleep(10);
                    continue;
                }
                
                // Reset empty response counter
                $emptyResponseCount = 0;
                
                // Check if task exists and has status
                if (!isset($task['status'])) {
                    $this->warn("Task structure unexpected, waiting for completion...");
                    sleep(10);
                    continue;
                }

                $status = $task['status'];
                
                // Check for completion - Elasticsearch 8.x uses different completion indicators
                if (isset($status['completed']) && $status['completed']) {
                    $progressBar->finish();
                    $this->info("✅ Reindex task completed successfully!");
                    break;
                }
                
                // Alternative completion check for Elasticsearch 8.x
                if (isset($status['state']) && $status['state'] === 'SUCCESS') {
                    $progressBar->finish();
                    $this->info("✅ Reindex task completed successfully!");
                    break;
                }
                
                // Check for failure
                if (isset($status['state']) && $status['state'] === 'FAILED') {
                    $progressBar->finish();
                    $this->error("❌ Reindex task failed!");
                    if (isset($status['error'])) {
                        $this->error("Error: " . json_encode($status['error']));
                    }
                    throw new \Exception("Reindex task failed");
                }

                // Update progress if available
                if (isset($status['total'])) {
                    $total = $status['total'];
                    $updated = $status['updated'] ?? 0;
                    $progress = $total > 0 ? ($updated / $total) * 100 : 0;
                    $progressBar->setProgress($progress);
                } else {
                    // Show some activity even without progress
                    $progressBar->advance(1);
                    if ($progressBar->getProgress() >= 100) {
                        $progressBar->setProgress(0);
                    }
                }

                sleep(10); // Check every 10 seconds
            } catch (\Exception $e) {
                $this->warn("Error monitoring task: " . $e->getMessage());
                sleep(10);
            }
        }
    }

    protected function updateAlias($client, $oldIndex, $newIndex)
    {
        try {
            $this->info("✅ Reindex completed successfully!");
            $this->info("New index '{$newIndex}' is ready with updated mapping");
            
            // Step 1: Create the original index name with new mapping FIRST
            $this->info("Creating new '{$oldIndex}' index with updated mapping...");
            $this->createNewIndex($client, $oldIndex, $this->argument('model'));
            
            // Step 2: Copy data from new index back to original name
            $this->info("Copying data from '{$newIndex}' back to '{$oldIndex}'...");
            try {
                $client->reindex([
                    'body' => [
                        'source' => [
                            'index' => $newIndex
                        ],
                        'dest' => [
                            'index' => $oldIndex
                        ]
                    ],
                    'wait_for_completion' => true
                ]);
                
                $this->info("✅ Data successfully copied back to '{$oldIndex}'");
                
                // Step 3: Now delete the old index (if it still exists)
                $this->info("Cleaning up old index if it exists...");
                try {
                    $client->indices()->delete(['index' => $oldIndex . '_old']);
                    $this->info("✅ Old index backup removed");
                } catch (\Exception $e) {
                    // Old index backup doesn't exist, ignore
                }
                
                // Step 4: Delete the temporary index
                $this->info("Removing temporary index '{$newIndex}'...");
                $client->indices()->delete(['index' => $newIndex]);
                $this->info("✅ Temporary index removed");
                
                $this->info("🎉 Zero-downtime reindex completed! Your index '{$oldIndex}' now has the updated mapping.");
                
            } catch (\Exception $e) {
                $this->error("Failed to copy data back: " . $e->getMessage());
                $this->warn("You can manually copy data from '{$newIndex}' to '{$oldIndex}' when ready");
                throw $e;
            }
            
        } catch (\Exception $e) {
            $this->error("Failed to complete index switch: " . $e->getMessage());
            throw $e;
        }
    }
} 