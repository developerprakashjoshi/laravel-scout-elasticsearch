<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\LargeDatasetSeeder;

class SeedLargeDataset extends Command
{
    protected $signature = 'seed:large-dataset {--count=1000000 : Number of records to seed} {--batch-size=1000 : Batch size for processing} {--no-transactions : Disable database transactions for better performance}';
    protected $description = 'Seed a large dataset of posts for testing';

    public function handle()
    {
        $count = $this->option('count');
        $batchSize = $this->option('batch-size');
        $useTransactions = !$this->option('no-transactions');

        $this->info("Starting to seed {$count} records with batch size {$batchSize}...");
        $this->info("Using transactions: " . ($useTransactions ? 'Yes' : 'No'));
        
        // Confirm before proceeding
        if (!$this->confirm("This will create {$count} records. Do you want to continue?")) {
            $this->info('Seeding cancelled.');
            return;
        }

        try {
            // Create a custom seeder instance with the specified count
            $seeder = new LargeDatasetSeeder();
            $seeder->setCommand($this);
            
            // Set custom count and batch size
            $seeder->setCount($count);
            $seeder->setBatchSize($batchSize);
            $seeder->setUseTransactions($useTransactions);
            
            $seeder->run();
            
            $this->info("Successfully seeded {$count} records!");
            
            // Show final count
            $totalPosts = \App\Models\Post::count();
            $this->info("Total posts in database: {$totalPosts}");
            
        } catch (\Exception $e) {
            $this->error("Error seeding data: " . $e->getMessage());
            return 1;
        }
    }
} 