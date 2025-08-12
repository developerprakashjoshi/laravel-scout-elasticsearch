<?php

namespace LaravelScout\Elasticsearch\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Post;
use Illuminate\Support\Facades\Log;

class ReindexBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $startId;
    protected $endId;
    protected $batchSize;

    public function __construct($startId, $endId, $batchSize = 1000)
    {
        $this->startId = $startId;
        $this->endId = $endId;
        $this->batchSize = $batchSize;
    }

    public function handle()
    {
        try {
            $posts = Post::whereBetween('id', [$this->startId, $this->endId])
                ->chunk($this->batchSize, function ($chunk) {
                    foreach ($chunk as $post) {
                        $post->searchable();
                    }
                });

            Log::info("Reindexed batch: {$this->startId} to {$this->endId}");
        } catch (\Exception $e) {
            Log::error("Reindex batch failed: {$this->startId} to {$this->endId}", [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
} 