<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Post;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class LargeDatasetSeeder extends Seeder
{
    protected $totalRecords = 1000000;
    protected $batchSize = 1000;
    protected $command;
    protected $startTime;
    protected $memoryLimit;
    protected $useTransactions = true;

    /**
     * Set the command instance
     */
    public function setCommand($command)
    {
        $this->command = $command;
    }

    /**
     * Set custom count
     */
    public function setCount($count)
    {
        $this->totalRecords = $count;
    }

    /**
     * Set custom batch size
     */
    public function setBatchSize($batchSize)
    {
        $this->batchSize = $batchSize;
    }

    /**
     * Enable/disable transactions
     */
    public function setUseTransactions($useTransactions)
    {
        $this->useTransactions = $useTransactions;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->startTime = microtime(true);
        $this->memoryLimit = ini_get('memory_limit');
        
        $this->command->info("Starting to seed {$this->totalRecords} records...");
        $this->command->info("Memory limit: {$this->memoryLimit}");
        $this->command->info("Batch size: {$this->batchSize}");
        $this->command->info("Using transactions: " . ($this->useTransactions ? 'Yes' : 'No'));
        
        // Pre-flight checks
        $this->performPreFlightChecks();
        
        $batches = ceil($this->totalRecords / $this->batchSize);
        
        $progressBar = $this->command->getOutput()->createProgressBar($batches);
        $progressBar->start();
        
        // Sample data for variety
        $categories = ['Technology', 'Business', 'Science', 'Health', 'Education', 'Entertainment', 'Sports', 'Politics'];
        $subCategories = ['dms', 'crm', 'erp', 'hr', 'finance', 'marketing', 'sales', 'support'];
        $statuses = ['draft', 'published', 'archived'];
        $authors = ['John Doe', 'Jane Smith', 'Mike Johnson', 'Sarah Wilson', 'David Brown', 'Lisa Davis', 'Tom Miller', 'Emma Garcia'];
        
        // Sample titles for variety
        $titleTemplates = [
            'Getting Started with {topic}',
            'Advanced {topic} Techniques',
            'Best Practices for {topic}',
            'Complete Guide to {topic}',
            'Understanding {topic} Fundamentals',
            'Mastering {topic} Development',
            'Essential {topic} Concepts',
            'Professional {topic} Solutions'
        ];
        
        $topics = ['Laravel', 'Elasticsearch', 'PHP', 'JavaScript', 'React', 'Vue.js', 'Node.js', 'Python', 'Java', 'C#', 'Ruby', 'Go', 'Rust', 'TypeScript', 'Angular', 'Docker', 'Kubernetes', 'AWS', 'Azure', 'GCP'];
        
        $contentTemplates = [
            'This comprehensive guide covers all aspects of {topic}. Learn the fundamentals, advanced techniques, and best practices.',
            'Discover how to implement {topic} in your projects. From basic setup to production deployment.',
            'Master {topic} with practical examples and real-world scenarios. Perfect for developers at all levels.',
            'Explore the power of {topic} and how it can transform your development workflow.',
            'A deep dive into {topic} architecture, patterns, and optimization strategies.',
            'Learn {topic} from scratch with step-by-step tutorials and hands-on exercises.',
            'Professional insights into {topic} development, testing, and deployment strategies.',
            'Comprehensive coverage of {topic} including advanced features and performance optimization.'
        ];
        
        $tags = [
            ['laravel', 'php', 'web-development'],
            ['elasticsearch', 'search', 'database'],
            ['javascript', 'frontend', 'web'],
            ['react', 'javascript', 'ui'],
            ['python', 'programming', 'data-science'],
            ['docker', 'devops', 'containerization'],
            ['aws', 'cloud', 'infrastructure'],
            ['vue', 'javascript', 'framework']
        ];
        
        $errorCount = 0;
        $successCount = 0;
        
        try {
            if ($this->useTransactions) {
                DB::beginTransaction();
            }
            
            for ($batch = 0; $batch < $batches; $batch++) {
                try {
                    $records = [];
                    
                    for ($i = 0; $i < $this->batchSize; $i++) {
                        $recordNumber = ($batch * $this->batchSize) + $i + 1;
                        
                        // Generate varied data
                        $topic = $topics[array_rand($topics)];
                        $titleTemplate = $titleTemplates[array_rand($titleTemplates)];
                        $contentTemplate = $contentTemplates[array_rand($contentTemplates)];
                        
                        $records[] = [
                            'title' => str_replace('{topic}', $topic, $titleTemplate),
                            'content' => str_replace('{topic}', $topic, $contentTemplate),
                            'author' => $authors[array_rand($authors)],
                            'category' => $categories[array_rand($categories)],
                            'sub_category' => $subCategories[array_rand($subCategories)],
                            'tags' => json_encode($tags[array_rand($tags)]),
                            'status' => $statuses[array_rand($statuses)],
                            'published_at' => now()->subDays(rand(0, 365)),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    
                    // Insert batch with error handling
                    Post::insert($records);
                    $successCount += count($records);
                    
                    $progressBar->advance();
                    
                    // Show progress every 10 batches
                    if ($batch % 10 === 0) {
                        $this->logProgress($batch, $successCount, $errorCount);
                    }
                    
                    // Memory management - garbage collection every 50 batches
                    if ($batch % 50 === 0) {
                        gc_collect_cycles();
                        $this->logMemoryUsage();
                    }
                    
                } catch (\Exception $e) {
                    $errorCount += $this->batchSize;
                    $this->command->error("Error in batch {$batch}: " . $e->getMessage());
                    Log::error("Seeder batch error", [
                        'batch' => $batch,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    // Continue with next batch instead of failing completely
                    continue;
                }
            }
            
            if ($this->useTransactions) {
                DB::commit();
            }
            
        } catch (\Exception $e) {
            if ($this->useTransactions) {
                DB::rollBack();
            }
            
            $this->command->error("Critical error during seeding: " . $e->getMessage());
            Log::error("Critical seeder error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
        
        $progressBar->finish();
        $this->command->newLine();
        
        $this->showFinalResults($successCount, $errorCount);
    }
    
    /**
     * Perform pre-flight checks
     */
    private function performPreFlightChecks(): void
    {
        // Check available memory
        $memoryLimit = $this->parseMemoryLimit($this->memoryLimit);
        $availableMemory = memory_get_peak_usage(true);
        
        if ($memoryLimit > 0 && $availableMemory > ($memoryLimit * 0.8)) {
            $this->command->warn("Memory usage is high. Consider reducing batch size.");
        }
        
        // Check database connection
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
        
        // Check if table exists
        if (!Schema::hasTable('posts')) {
            throw new \Exception("Posts table does not exist. Run migrations first.");
        }
        
        $this->command->info("Pre-flight checks passed.");
    }
    
    /**
     * Parse memory limit string to bytes
     */
    private function parseMemoryLimit($limit): int
    {
        $unit = strtolower(substr($limit, -1));
        $value = (int) substr($limit, 0, -1);
        
        switch ($unit) {
            case 'k': return $value * 1024;
            case 'm': return $value * 1024 * 1024;
            case 'g': return $value * 1024 * 1024 * 1024;
            default: return $value;
        }
    }
    
    /**
     * Log progress information
     */
    private function logProgress(int $batch, int $successCount, int $errorCount): void
    {
        $elapsed = microtime(true) - $this->startTime;
        $recordsPerSecond = $successCount / $elapsed;
        
        $this->command->info(" Processed " . ($batch * $this->batchSize) . " records");
        $this->command->info(" Success: {$successCount}, Errors: {$errorCount}, Rate: " . round($recordsPerSecond, 2) . " records/sec");
    }
    
    /**
     * Log memory usage
     */
    private function logMemoryUsage(): void
    {
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        
        $this->command->info(" Memory usage: " . $this->formatBytes($memoryUsage));
        $this->command->info(" Peak memory: " . $this->formatBytes($memoryPeak));
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Show final results and statistics
     */
    private function showFinalResults(int $successCount, int $errorCount): void
    {
        $elapsed = microtime(true) - $this->startTime;
        $recordsPerSecond = $successCount / $elapsed;
        
        $this->command->info("Successfully seeded {$successCount} records!");
        $this->command->info("Errors: {$errorCount} records");
        $this->command->info("Total time: " . round($elapsed, 2) . " seconds");
        $this->command->info("Average rate: " . round($recordsPerSecond, 2) . " records/second");
        
        // Show final statistics
        $totalPosts = Post::count();
        $this->command->info("Total posts in database: {$totalPosts}");
        
        // Show sample statistics
        $this->showStatistics();
        
        // Performance recommendations
        $this->showPerformanceRecommendations($recordsPerSecond, $elapsed);
    }
    
    /**
     * Show sample statistics after seeding
     */
    private function showStatistics(): void
    {
        $this->command->info('Sample Statistics:');
        
        // Status distribution
        $statusStats = Post::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();
        
        foreach ($statusStats as $stat) {
            $this->command->info("- Status '{$stat->status}': {$stat->count} records");
        }
        
        // Category distribution
        $categoryStats = Post::selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->limit(5)
            ->get();
        
        $this->command->info('Top 5 Categories:');
        foreach ($categoryStats as $stat) {
            $this->command->info("- {$stat->category}: {$stat->count} records");
        }
        
        // Sub-category distribution
        $subCategoryStats = Post::selectRaw('sub_category, COUNT(*) as count')
            ->groupBy('sub_category')
            ->get();
        
        $this->command->info('Sub-Category Distribution:');
        foreach ($subCategoryStats as $stat) {
            $this->command->info("- {$stat->sub_category}: {$stat->count} records");
        }
    }
    
    /**
     * Show performance recommendations
     */
    private function showPerformanceRecommendations(float $recordsPerSecond, float $elapsed): void
    {
        $this->command->info('Performance Analysis:');
        
        if ($recordsPerSecond < 100) {
            $this->command->warn("Low performance detected. Consider:");
            $this->command->warn("- Increasing batch size");
            $this->command->warn("- Disabling transactions");
            $this->command->warn("- Optimizing database configuration");
        } elseif ($recordsPerSecond > 1000) {
            $this->command->info("Excellent performance! Current rate: {$recordsPerSecond} records/sec");
        } else {
            $this->command->info("Good performance. Current rate: {$recordsPerSecond} records/sec");
        }
        
        // Memory efficiency
        $memoryPeak = memory_get_peak_usage(true);
        $memoryEfficiency = $this->totalRecords / ($memoryPeak / 1024 / 1024); // records per MB
        
        $this->command->info("Memory efficiency: " . round($memoryEfficiency, 2) . " records per MB");
        
        if ($memoryEfficiency < 100) {
            $this->command->warn("Low memory efficiency. Consider reducing batch size.");
        }
    }
} 