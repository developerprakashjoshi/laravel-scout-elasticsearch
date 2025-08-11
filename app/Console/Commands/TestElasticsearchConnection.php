<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Elastic\Elasticsearch\Client;

class TestElasticsearchConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elasticsearch:test-connection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the connection to Elasticsearch';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Elasticsearch connection...');
        
        try {
            $client = app(Client::class);
            
            $this->info('✅ Client resolved successfully');
            
            // Test the connection
            $response = $client->info();
            
            $this->info('✅ Connection successful!');
            $this->table(
                ['Property', 'Value'],
                [
                    ['Version', $response['version']['number']],
                    ['Cluster Name', $response['cluster_name']],
                    ['Node Name', $response['name']],
                    ['Tagline', $response['tagline']],
                ]
            );
            
            // Test index operations
            $indexName = 'test_connection_' . time();
            
            $this->info("Creating test index: $indexName");
            
            // Create a test index
            $createResponse = $client->indices()->create([
                'index' => $indexName,
                'body' => [
                    'settings' => [
                        'number_of_shards' => 1,
                        'number_of_replicas' => 0
                    ]
                ]
            ]);
            
            $this->info('✅ Test index created successfully');
            
            // Delete the test index
            $client->indices()->delete(['index' => $indexName]);
            $this->info('✅ Test index deleted successfully');
            
            $this->info('🎉 All tests passed! Elasticsearch is working correctly.');
            
        } catch (\Exception $e) {
            $this->error('❌ Connection failed: ' . $e->getMessage());
            $this->error('Error code: ' . $e->getCode());
            
            if ($e instanceof \Elastic\Elasticsearch\Exception\ClientResponseException) {
                $this->error('HTTP Status: ' . $e->getResponse()->getStatusCode());
                $this->error('Response: ' . $e->getResponse()->getBody());
            }
            
            return 1;
        }
        
        return 0;
    }
}
