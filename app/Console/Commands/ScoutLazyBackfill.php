<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Elastic\Elasticsearch\Client;
use App\Models\Post;

class ScoutLazyBackfill extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scout:lazy-backfill {field : The field name to add} {--type=text : The Elasticsearch field type} {--batch-size=1000 : Batch size for processing} {--force : Force backfill all existing documents}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add new field to Elasticsearch index using lazy backfill with update_by_query';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fieldName = $this->argument('field');
        $fieldType = $this->option('type');
        $batchSize = $this->option('batch-size');
        $forceBackfill = $this->option('force');

        $this->info("🚀 Starting Lazy Backfill for field '{$fieldName}'");
        $this->info("This is the industry best practice for adding new fields with zero downtime");

        try {
            $client = app(Client::class);
            $indexName = 'posts';

            // Check if index exists
            if (!$client->indices()->exists(['index' => $indexName])) {
                $this->error("Index '{$indexName}' does not exist. Please create it first with: php artisan scout:index posts");
                return 1;
            }

            // Step 1: Add new field to mapping
            $this->info("📝 Step 1: Adding field '{$fieldName}' to index mapping...");
            $this->addFieldToMapping($client, $indexName, $fieldName, $fieldType);

            // Step 2: Check if field already exists in documents
            $this->info("🔍 Step 2: Checking field presence in existing documents...");
            $fieldExists = $this->checkFieldExists($client, $indexName, $fieldName);

            if ($fieldExists && !$forceBackfill) {
                $this->info("✅ Field '{$fieldName}' already exists in documents. No backfill needed.");
                $this->info("💡 Use --force flag if you want to re-backfill all documents.");
                return 0;
            }

            // Step 3: Lazy backfill existing documents
            if ($forceBackfill || !$fieldExists) {
                $this->info("🔄 Step 3: Starting lazy backfill for existing documents...");
                $this->lazyBackfillDocuments($client, $indexName, $fieldName, $batchSize);
            }

            $this->info("🎉 Lazy backfill completed successfully!");
            $this->info("✅ Field '{$fieldName}' is now available for search and indexing");
            $this->info("💡 New documents will automatically include this field");
            $this->info("💡 Existing documents have been updated with the new field");

        } catch (\Exception $e) {
            $this->error("❌ Lazy backfill failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Add new field to existing index mapping
     */
    protected function addFieldToMapping($client, $indexName, $fieldName, $fieldType)
    {
        try {
            // Get current mapping
            $currentMapping = $client->indices()->getMapping(['index' => $indexName]);
            $properties = $currentMapping[$indexName]['mappings']['properties'] ?? [];

            // Check if field already exists
            if (isset($properties[$fieldName])) {
                $this->warn("Field '{$fieldName}' already exists in mapping. Skipping...");
                return;
            }

            // Define the new field mapping
            $newFieldMapping = $this->getFieldMapping($fieldName, $fieldType);

            // Update the mapping
            $client->indices()->putMapping([
                'index' => $indexName,
                'body' => [
                    'properties' => [
                        $fieldName => $newFieldMapping
                    ]
                ]
            ]);

            $this->info("✅ Field '{$fieldName}' added to index mapping successfully!");

        } catch (\Exception $e) {
            $this->error("Failed to add field to mapping: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if field exists in existing documents
     */
    protected function checkFieldExists($client, $indexName, $fieldName)
    {
        try {
            // Search for documents that have the field
            $response = $client->search([
                'index' => $indexName,
                'body' => [
                    'query' => [
                        'exists' => [
                            'field' => $fieldName
                        ]
                    ],
                    'size' => 1
                ]
            ]);

            $hasField = $response['hits']['total']['value'] > 0;
            $this->info("Field '{$fieldName}' exists in documents: " . ($hasField ? 'Yes' : 'No'));
            
            return $hasField;

        } catch (\Exception $e) {
            $this->warn("Could not check field existence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lazy backfill existing documents using update_by_query
     */
    protected function lazyBackfillDocuments($client, $indexName, $fieldName, $batchSize)
    {
        try {
            // Get total document count
            $countResponse = $client->count(['index' => $indexName]);
            $totalDocuments = $countResponse['count'];
            
            if ($totalDocuments === 0) {
                $this->info("No documents to backfill.");
                return;
            }

            $this->info("Total documents to process: {$totalDocuments}");
            $this->info("Batch size: {$batchSize}");

            // Use update_by_query with script to add the field
            $this->info("Using update_by_query with script to add field...");
            
            // For text fields, we'll set a default value
            $defaultValue = $this->getDefaultValue($fieldName);
            
            $response = $client->updateByQuery([
                'index' => $indexName,
                'body' => [
                    'script' => [
                        'source' => "if (ctx._source.containsKey('{$fieldName}') == false) { ctx._source.{$fieldName} = '{$defaultValue}'; }",
                        'lang' => 'painless'
                    ],
                    'query' => [
                        'bool' => [
                            'must_not' => [
                                'exists' => [
                                    'field' => $fieldName
                                ]
                            ]
                        ]
                    ]
                ],
                'wait_for_completion' => true,
                'scroll_size' => $batchSize
            ]);

            $this->info("✅ Update by query completed successfully!");
            $this->info("Updated documents: " . ($response['updated'] ?? 'Unknown'));

        } catch (\Exception $e) {
            $this->error("Failed to backfill documents: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get the field mapping configuration
     */
    protected function getFieldMapping(string $fieldName, string $fieldType): array
    {
        $mappings = [
            'text' => [
                'type' => 'text',
                'analyzer' => 'standard',
                'fields' => [
                    'keyword' => [
                        'type' => 'keyword'
                    ]
                ]
            ],
            'keyword' => [
                'type' => 'keyword'
            ],
            'integer' => [
                'type' => 'integer'
            ],
            'date' => [
                'type' => 'date',
                'format' => 'strict_date_optional_time||epoch_millis'
            ],
            'boolean' => [
                'type' => 'boolean'
            ],
            'float' => [
                'type' => 'float'
            ]
        ];

        return $mappings[$fieldType] ?? $mappings['text'];
    }

    /**
     * Get default value for a field based on field name
     */
    protected function getDefaultValue(string $fieldName): string
    {
        $defaults = [
            'misc' => 'others',
            'category' => 'general',
            'status' => 'draft',
            'tags' => '[]',
            'priority' => 'medium',
            'rating' => '0',
            'is_active' => 'true'
        ];

        return $defaults[$fieldName] ?? 'default';
    }
}
