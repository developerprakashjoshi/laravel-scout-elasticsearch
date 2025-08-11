<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Post;
use App\Jobs\ReindexBatchJob;
use Illuminate\Support\Facades\Log;

class ReindexLargeDataset extends Command
{
    protected $signature = 'scout:reindex-large {model} {--batch-size=1000} {--chunk-size=10000} {--queue}';
    protected $description = 'Reindex large datasets in batches with progress tracking';

    public function handle()
    {
        $modelClass = $this->argument('model');
        $batchSize = $this->option('batch-size');
        $chunkSize = $this->option('chunk-size');
        $useQueue = $this->option('queue');

        $this->info("Starting large dataset reindex for: {$modelClass}");
        $this->info("Batch size: {$batchSize}, Chunk size: {$chunkSize}");

        // Get total count
        $totalRecords = $modelClass::count();
        $this->info("Total records to reindex: {$totalRecords}");

        // Calculate batches
        $batches = ceil($totalRecords / $chunkSize);
        $this->info("Will process in {$batches} batches");

        $progressBar = $this->output->createProgressBar($batches);
        $progressBar->start();

        // Process in chunks
        $modelClass::select('id')
            ->orderBy('id')
            ->chunk($chunkSize, function ($chunk) use ($batchSize, $useQueue, $progressBar) {
                $startId = $chunk->first()->id;
                $endId = $chunk->last()->id;

                if ($useQueue) {
                    // Dispatch to queue
                    ReindexBatchJob::dispatch($startId, $endId, $batchSize);
                } else {
                    // Process immediately
                    $this->processBatch($startId, $endId, $batchSize);
                }

                $progressBar->advance();
            });

        $progressBar->finish();
        $this->newLine();
        $this->info('Reindexing completed!');

        if ($useQueue) {
            $this->info('Jobs dispatched to queue. Monitor with: php artisan queue:work');
        }
    }

    protected function processBatch($startId, $endId, $batchSize)
    {
        Post::whereBetween('id', [$startId, $endId])
            ->chunk($batchSize, function ($chunk) {
                foreach ($chunk as $post) {
                    $post->searchable();
                }
            });
    }
} 